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

    protected int $maxContentLength = 8000;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY', '');
    }

    public function gradeSubmission(Submission $submission): ?SubmissionGrade
    {
        $rubric = Rubric::where('task_id', $submission->task_id)
            ->where('auto_grade_enabled', true)
            ->with('criteria')
            ->first();

        if (! $rubric) {
            Log::info("GeminiGrading: No active rubric for task {$submission->task_id}. Skipping.");
            return null;
        }

        $task = $submission->task;
        $content = $this->getSubmissionContent($submission);

        return DB::transaction(function () use ($submission, $rubric, $task, $content) {
            $submissionGrade = SubmissionGrade::updateOrCreate(
                ['submission_id' => $submission->id, 'rubric_id' => $rubric->id],
                [
                    'total_score' => 0, 
                    'max_score'   => $rubric->total_points, 
                    'auto_graded' => true, 
                    'graded_by'   => null
                ]
            );

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
                        'max_points'    => $criterion->max_points,
                        'feedback'      => $result['feedback'],
                        'auto_checked'  => $result['auto_checked'],
                    ]
                );

                $totalScore += $result['points'];
            }

            $submissionGrade->update(['total_score' => $totalScore]);
            
            $submission->update([
                'grade'       => round($totalScore), 
                'auto_graded' => true 
            ]);

            Log::info("GeminiGrading: Submission #{$submission->id} scored {$totalScore}/{$rubric->total_points}");

            return $submissionGrade->fresh(['criterionScores.criterion']);
        });
    }

    protected function gradeCriterion($criterion, string $content, Submission $submission, $task): array
    {
        $rules = is_array($criterion->checking_rules) 
            ? $criterion->checking_rules 
            : json_decode($criterion->checking_rules, true) ?? [];

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
1. Evaluate if the code/file submission solves the Task Instructions.
2. Select the EXACT level tier from the performance levels above that best fits the work.
3. Assign the EXACT 'points_earned' integer corresponding to that matched level tier.
4. If the submission is invalid or incomplete, assign 0 points.

## RESPONSE — JSON only
{
  "points_earned": <integer>,
  "level_matched": "<label>",
  "feedback": "<feedback>"
}
EOT;

        try {
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
                
                $cleanJson = str_replace(['```json', '```JSON', '```'], '', $text);
                $result = json_decode(trim($cleanJson), true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \RuntimeException('Invalid JSON from Gemini: ' . $text);
                }

                $points = (float)($result['points_earned'] ?? 0);$points = max(0, min($maxPts,$points));

                $levelLabel = trim($result['level_matched'] ?? '');
                $feedback   = trim($result['feedback'] ?? 'No feedback provided.');

                if ($levelLabel) {
                    $feedback = "Level Matched: {$levelLabel} ({$points} pts)\n\n{$feedback}";
                }

                return ['points' => $points, 'feedback' =>$feedback, 'auto_checked' => true];
            }

            Log::error("GeminiGrading: HTTP {$response->status()} — {$response->body()}");

        } catch (\Exception $e) {
            Log::error('GeminiGrading (levels) exception: ' . $e->getMessage());
        }

        return ['points' => 0, 'feedback' => 'Auto-grading failed (API error). Pending manual review.', 'auto_checked' => false];
    }

    protected function gradeWithAI($criterion, string $content,$task): array
    {
        if (empty($this->apiKey)) return$this->apiKeyMissing();

        $maxPts =$criterion->max_points;

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

## RESPONSE — JSON only
{
  "points_earned": <number>,
  "feedback": "<feedback>"
}
EOT;

        try {
            $response = Http::withoutVerifying()->timeout(60)->post("{$this->apiUrl}?key={$this->apiKey}", [
                'contents'         => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0.1, 'responseMimeType' => 'application/json'],
            ]);

            if ($response->successful()) {$data   = $response->json();$text   = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';$cleanJson = str_replace(['```json', '```JSON', '```'], '', $text);
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

    protected function gradeByKeyword($criterion, string $content): array
    {
        $rules = is_array($criterion->checking_rules) 
            ? $criterion->checking_rules 
            : json_decode($criterion->checking_rules, true) ?? [];

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

    protected function gradeByFile($criterion, Submission $submission): array
    {
        $rules = is_array($criterion->checking_rules) 
            ? $criterion->checking_rules 
            : json_decode($criterion->checking_rules, true) ?? [];

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

    protected function getSubmissionContent(Submission $submission): string
    {
        $textExtensions = [
            'txt', 'php', 'py', 'js', 'ts', 'java', 'c', 'cpp', 'cs',
            'html', 'htm', 'css', 'sql', 'json', 'xml', 'md', 'rb',
            'go', 'swift', 'kt', 'r', 'sh', 'bat', 'yaml', 'yml', 'vue',
        ];

        $ext = strtolower(pathinfo($submission->original_filename, PATHINFO_EXTENSION));

        if (! in_array($ext, $textExtensions)) {
            return "File submitted: {$submission->original_filename} (binary format — Gemini cannot read directly. Grade based on file presence only.)";
        }

        try {
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

    protected function apiKeyMissing(): array
    {
        Log::warning('GeminiGrading: GEMINI_API_KEY is not set in .env');
        return [
            'points'       => 0,
            'feedback'     => 'Auto-grading unavailable: Gemini API key not configured. Pending manual review.',
            'auto_checked' => false,
        ];
    }
}