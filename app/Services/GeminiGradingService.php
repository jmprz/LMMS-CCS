<?php

namespace App\Services;

use App\Models\CriterionScore;
use App\Models\Rubric;
use App\Models\Submission;
use App\Models\SubmissionGrade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GeminiGradingService
{
    protected string $apiKey;
    protected string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent';

    // Max characters sent to Gemini per submission (prevents excessive token cost)
    protected int $maxContentLength = 8000;

    public function __construct()
    {
        // 🚨 CHANGED: Read directly from .env to avoid caching/config mapping issues
        $this->apiKey = env('GEMINI_API_KEY', '');
    }

    // =========================================================================
    // PUBLIC — Main entry point. Call this right after a Submission is saved.
    // =========================================================================
    public function gradeSubmission(Submission $submission): ?SubmissionGrade
{
    // 1. Find active rubric for this task
    $rubric = Rubric::where('task_id', $submission->task_id)
        ->where('auto_grade_enabled', true)
        ->with('criteria')
        ->first();

    if (! $rubric) {
        Log::info("GeminiGrading: No active rubric for task {$submission->task_id}. Skipping.");
        return null;
    }

    // Load task relationship if not loaded
    $task = $submission->task;

    // 2. Read submission file content
    $content = $this->getSubmissionContent($submission);

    return DB::transaction(function () use ($submission, $rubric, $task, $content) {
        // 3. Create or reset the SubmissionGrade record
        $submissionGrade = SubmissionGrade::updateOrCreate(
            ['submission_id' => $submission->id, 'rubric_id' => $rubric->id],
            [
                'total_score' => 0, 
                'max_score'   => $rubric->total_points, 
                'auto_graded' => true, 
                'graded_by'   => null
            ]
        );

        // 4. Grade each criterion
        $totalScore = 0;

        foreach ($rubric->criteria as $criterion) {
            $result = $this->gradeCriterion($criterion, $content, $submission, $task);

            CriterionScore::updateOrCreate(
                [
                    'submission_grade_id' => $submissionGrade->id, 
                    'criterion_id'        => $criterion->id
                ],
                [
                    'points_earned' => $result['points'],
                    'max_points'    => $criterion['max_points'] ?? $criterion->max_points,
                    'feedback'      => $result['feedback'],
                    'auto_checked'  => $result['auto_checked'],
                ]
            );

            $totalScore += $result['points'];
        }

        // 5. Persist final score & update submission status to auto_graded = true
        $submissionGrade->update(['total_score' => $totalScore]);
        
        $submission->update([
            'grade'       => round($totalScore), 
            'auto_graded' => true 
        ]);

        Log::info("GeminiGrading: Submission #{$submission->id} scored {$totalScore}/{$rubric->total_points}");

        return $submissionGrade->fresh(['criterionScores.criterion']);
    });
}

    // =========================================================================
    // PRIVATE — Route to the right grading strategy
    // =========================================================================
   protected function gradeCriterion($criterion, string $content, Submission $submission, $task): array
{
    $rules = $criterion->checking_rules ?? [];

    if (! empty($rules['levels'])) {
        return $this->gradeWithLevels($criterion, $rules['levels'], $content, $task);
    }

    return match ($criterion->checking_type) {
        'ai', 'text', 'code' => $this->gradeWithAI($criterion, $content, $task),
        'keyword'            => $this->gradeByKeyword($criterion, $content),
        'file'               => $this->gradeByFile($criterion, $submission),
        default              => [
            'points'       => 0,
            'feedback'     => 'Pending manual review.',
            'auto_checked' => false,
        ],
    };
}

    // =========================================================================
    // STRATEGY 1 — Level-based AI grading (primary strategy for new rubrics)
    // =========================================================================
   protected function gradeWithLevels($criterion, array $levels, string $content, $task): array
{
    if (empty($this->apiKey)) return $this->apiKeyMissing();

    usort($levels, fn($a, $b) => (int)($b['points'] ?? 0) - (int)($a['points'] ?? 0));

    $maxPts = $criterion->max_points;
    $levelsText = '';
    foreach ($levels as $lvl) {
        $levelsText .= "\n[{$lvl['label']} — {$lvl['points']} pts]\n{$lvl['description']}\n";
    }

    $prompt = <<<EOT
You are a strict and fair academic grader. 

## TASK CONTEXT
Task: {$task->title}
Instructions: {$task->description}

## CRITERION
Name: {$criterion->criterion_name}

## PERFORMANCE LEVELS
{$levelsText}

## STUDENT SUBMISSION
{$content}

## GRADING INSTRUCTIONS
1. FIRST: Evaluate if the code solves the Task Instructions. 
2. If the code does not address the task (e.g. is irrelevant), score 0 points.
3. Compare the submission against the performance levels above.
4. Provide constructive feedback referencing the task instructions.

## RESPONSE — JSON only
{
  "points_earned": <integer>,
  "level_matched": "<label>",
  "feedback": "<feedback>"
}
EOT;

       try {
            // 🚨 FIX 1 & 2: Added withoutVerifying() for local SSL and bumped timeout to 60
            $response = Http::withoutVerifying()->timeout(60)->post("{$this->apiUrl}?key={$this->apiKey}", [
                'contents'         => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    'temperature'      => 0.1,
                    'responseMimeType' => 'application/json',
                ],
            ]);

            if ($response->successful()) {
                $data   = $response->json();
                $text   = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
                
                // 🚨 FIX 3: Strip out Markdown formatting Gemini often sneaks in
                $cleanJson = str_replace(['```json', '```JSON', '```'], '', $text);
                $result = json_decode(trim($cleanJson), true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \RuntimeException('Invalid JSON from Gemini: ' . $text);
                }

                $points = (float)($result['points_earned'] ?? 0);
                $points = max(0, min($maxPts, $points));

                $levelLabel = trim($result['level_matched'] ?? '');
                $feedback   = trim($result['feedback'] ?? 'No feedback provided.');

                // Prefix the level name so students know which tier they hit
                if ($levelLabel) {
                    $feedback = "Level: {$levelLabel}\n\n{$feedback}";
                }

                return ['points' => $points, 'feedback' => $feedback, 'auto_checked' => true];
            }

            Log::error("GeminiGrading: HTTP {$response->status()} — {$response->body()}");

        } catch (\Exception $e) {
            Log::error('GeminiGrading (levels) exception: ' . $e->getMessage());
        }

        return ['points' => 0, 'feedback' => 'Auto-grading failed (API error). Pending manual review.', 'auto_checked' => false];
    }

    // =========================================================================
    // STRATEGY 2 — Basic AI grading (fallback for criteria without levels)
    // =========================================================================
   protected function gradeWithAI($criterion, string $content, $task): array
{
    if (empty($this->apiKey)) return $this->apiKeyMissing();

    $maxPts = $criterion->max_points;

    $prompt = <<<EOT
You are a strict and fair academic grader. 

## TASK CONTEXT
Task: {$task->title}
Instructions: {$task->description}

## CRITERION
Name: {$criterion->criterion_name}
Max Points: {$maxPts}

## STUDENT SUBMISSION
{$content}

## GRADING INSTRUCTIONS
1. Evaluate if the code solves the Task Instructions. 
2. If the code does not match the task, award 0 points.
3. Be objective and specific in feedback.

## RESPONSE — JSON only
{
  "points_earned": <number>,
  "feedback": "<feedback>"
}
EOT;

       try {
            // 🚨 FIX 1 & 2: Added withoutVerifying() and bumped timeout
            $response = Http::withoutVerifying()->timeout(60)->post("{$this->apiUrl}?key={$this->apiKey}", [
                'contents'         => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0.1, 'responseMimeType' => 'application/json'],
            ]);

            if ($response->successful()) {
                $data   = $response->json();
                $text   = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
                
                // 🚨 FIX 3: Strip out Markdown formatting
                $cleanJson = str_replace(['```json', '```JSON', '```'], '', $text);
                $result = json_decode(trim($cleanJson), true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \RuntimeException('Invalid JSON: ' . $text);
                }

                $points = max(0, min($maxPts, (float)($result['points_earned'] ?? 0)));
                return ['points' => $points, 'feedback' => $result['feedback'] ?? 'No feedback.', 'auto_checked' => true];
            }
        } catch (\Exception $e) {
            Log::error('GeminiGrading (basic AI) exception: ' . $e->getMessage());
        }

        return ['points' => 0, 'feedback' => 'Auto-grading failed. Pending manual review.', 'auto_checked' => false];
    }

    // =========================================================================
    // STRATEGY 3 — Keyword matching
    // =========================================================================
    protected function gradeByKeyword($criterion, string $content): array
    {
        $rules    = $criterion->checking_rules ?? [];
        $keywords = $rules['keywords'] ?? [];
        $matchAll = $rules['match_all']  ?? false;

        if (empty($keywords)) {
            return ['points' => 0, 'feedback' => 'No keywords defined.', 'auto_checked' => true];
        }

        $haystack = strtolower($content);
        $found    = [];
        $missing  = [];

        foreach ($keywords as $kw) {
            str_contains($haystack, strtolower($kw)) ? $found[] = $kw : $missing[] = $kw;
        }

        $matched = count($found);
        $total   = count($keywords);
        $points  = $matchAll
            ? ($matched === $total ? $criterion->max_points : 0)
            : round(($matched / $total) * $criterion->max_points, 1);

        $feedback = "Found {$matched}/{$total} required keywords.";
        if (! empty($missing)) {
            $feedback .= ' Missing: ' . implode(', ', $missing) . '.';
        }

        return ['points' => $points, 'feedback' => $feedback, 'auto_checked' => true];
    }

    // =========================================================================
    // STRATEGY 4 — File validation
    // =========================================================================
    protected function gradeByFile($criterion, Submission $submission): array
    {
        $rules     = $criterion->checking_rules ?? [];
        $allowedEx = array_map('strtolower', $rules['allowed_extensions'] ?? []);
        $ext       = strtolower(pathinfo($submission->original_filename, PATHINFO_EXTENSION));

        if (empty($allowedEx)) {
            return ['points' => $criterion->max_points, 'feedback' => "File '{$submission->original_filename}' submitted.", 'auto_checked' => true];
        }

        if (in_array($ext, $allowedEx)) {
            return ['points' => $criterion->max_points, 'feedback' => "Valid file format (.{$ext}).", 'auto_checked' => true];
        }

        return [
            'points'       => 0,
            'feedback'     => "Invalid file format. Expected: " . implode(', ', $allowedEx) . ". Got: .{$ext}.",
            'auto_checked' => true,
        ];
    }

    // =========================================================================
    // HELPER — Read submission text content
    // =========================================================================
    protected function getSubmissionContent(Submission $submission): string
    {
        $textExtensions = [
            'txt', 'php', 'py', 'js', 'ts', 'java', 'c', 'cpp', 'cs',
            'html', 'htm', 'css', 'sql', 'json', 'xml', 'md', 'rb',
            'go', 'swift', 'kt', 'r', 'sh', 'bat', 'yaml', 'yml', 'vue',
        ];

        $ext = strtolower(pathinfo($submission->original_filename, PATHINFO_EXTENSION));

        if (! in_array($ext, $textExtensions)) {
            return "File submitted: {$submission->original_filename} (binary format — Gemini cannot read this directly. Grade based on file presence only.)";
        }

        try {
            // 🚨 CHANGED: Check the public directory where the controller actually saved it
            $absolutePath = public_path($submission->file_path); 

            if (file_exists($absolutePath)) {
                $raw = file_get_contents($absolutePath);
                
                if (strlen($raw) > $this->maxContentLength) {
                    $raw = substr($raw, 0, $this->maxContentLength) . "\n\n[... content truncated ...]";
                }
                return $raw;
            }
        } catch (\Exception $e) {
            Log::warning("GeminiGrading: Cannot read file for submission #{$submission->id}: " . $e->getMessage());
        }

        return "File: {$submission->original_filename} (content unavailable)";
    }

    // =========================================================================
    // HELPER — Missing API key fallback
    // =========================================================================
    protected function apiKeyMissing(): array
    {
        Log::warning('GeminiGrading: GEMINI_API_KEY is not set in .env');
        return [
            'points'       => 0,
            'feedback'     => 'Auto-grading unavailable: Gemini API key not configured. Pending manual review.',
            'auto_checked' => false,
        ];
    }

    public function storeCode(Request $request)
{
    $code = $request->input('code');
    $extension = $request->input('language') === 'python' ? 'py' : 'js';
    
    // 1. Create a filename
    $fileName = 'submission_' . auth()->id() . '_' . time() . '.' . $extension;
    $path = "submissions/{$fileName}";

    // 2. Save the string content as a physical file
    // This allows your GeminiGradingService to read it exactly like an upload
    Storage::disk('public')->put($path, $code);

    // 3. Create the Submission Record
    $submission = Submission::create([
        'task_id' => $request->task_id,
        'user_id' => auth()->id(),
        'file_path' => $path,
        'original_filename' => $fileName,
    ]);

    // 4. Run your grading service
    $gradingService = new \App\Services\GeminiGradingService();
    $gradingService->gradeSubmission($submission);

    return response()->json(['success' => true]);
}
}