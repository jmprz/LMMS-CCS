<?php

namespace App\Services;

use App\Models\CriterionScore;
use App\Models\Rubric;
use App\Models\Submission;
use App\Models\SubmissionGrade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GeminiGradingService
{
    protected string $apiKey;
    protected string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

    // Max characters sent to Gemini per submission (prevents excessive token cost)
    protected int $maxContentLength = 8000;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', '');
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

        // 2. Read submission file
        $content = $this->getSubmissionContent($submission);

        // 3. Create/reset the SubmissionGrade record
        $submissionGrade = SubmissionGrade::updateOrCreate(
            ['submission_id' => $submission->id, 'rubric_id' => $rubric->id],
            ['total_score' => 0, 'max_score' => $rubric->total_points, 'auto_graded' => true, 'graded_by' => null]
        );

        // 4. Grade each criterion
        $totalScore = 0;

        foreach ($rubric->criteria as $criterion) {
            $result = $this->gradeCriterion($criterion, $content, $submission);

            CriterionScore::updateOrCreate(
                ['submission_grade_id' => $submissionGrade->id, 'criterion_id' => $criterion->id],
                [
                    'points_earned' => $result['points'],
                    'max_points'    => $criterion->max_points,
                    'feedback'      => $result['feedback'],
                    'auto_checked'  => $result['auto_checked'],
                ]
            );

            $totalScore += $result['points'];
        }

        // 5. Persist final score
        $submissionGrade->update(['total_score' => $totalScore]);

        $submission->update([
            'grade'       => round($totalScore),
            'auto_graded' => true,
        ]);

        Log::info("GeminiGrading: Submission #{$submission->id} scored {$totalScore}/{$rubric->total_points}");

        return $submissionGrade->fresh(['criterionScores.criterion']);
    }

    // =========================================================================
    // PRIVATE — Route to the right grading strategy
    // =========================================================================
    protected function gradeCriterion($criterion, string $content, Submission $submission): array
    {
        $rules = $criterion->checking_rules ?? [];

        // ── Level-based grading (Google Classroom style rubric) ──
        if (! empty($rules['levels'])) {
            return $this->gradeWithLevels($criterion, $rules['levels'], $content);
        }

        // ── Fallback: original strategies ──
        return match ($criterion->checking_type) {
            'ai', 'text', 'code' => $this->gradeWithAI($criterion, $content),
            'keyword'            => $this->gradeByKeyword($criterion, $content),
            'file'               => $this->gradeByFile($criterion, $submission),
            default              => [
                'points'       => 0,
                'feedback'     => 'Pending manual review by professor.',
                'auto_checked' => false,
            ],
        };
    }

    // =========================================================================
    // STRATEGY 1 — Level-based AI grading (primary strategy for new rubrics)
    // =========================================================================
    protected function gradeWithLevels($criterion, array $levels, string $content): array
    {
        if (empty($this->apiKey)) {
            return $this->apiKeyMissing();
        }

        // Sort levels best → worst so the prompt reads naturally
        usort($levels, fn($a, $b) => (int)($b['points'] ?? 0) - (int)($a['points'] ?? 0));

        $maxPts     = $criterion->max_points;
        $levelsText = '';
        foreach ($levels as $lvl) {
            $label       = $lvl['label']       ?? 'Level';
            $pts         = (int)($lvl['points'] ?? 0);
            $desc        = $lvl['description'] ?? '(No description provided)';
            $levelsText .= "\n[{$label} — {$pts} pts]\n{$desc}\n";
        }

        $extraNote = $criterion->description ? "\nAdditional grading notes: {$criterion->description}" : '';

        $prompt = <<<EOT
You are a strict and fair academic grader. Your task is to grade ONE specific criterion from a student submission.

## CRITERION
Name: {$criterion->criterion_name}{$extraNote}

## PERFORMANCE LEVELS (read all levels before deciding)
{$levelsText}

## STUDENT SUBMISSION
{$content}

## GRADING INSTRUCTIONS
1. Read the entire student submission carefully.
2. Compare the submission against each performance level description.
3. Select the single level that BEST matches the quality of the submission.
4. Award the EXACT point value of that level.
5. Write 2–4 sentences of specific, constructive feedback that references the submission and explains why that level was chosen.
6. If the submission falls clearly between two levels, choose the lower one and explain in feedback how the student can improve.

## RESPONSE — valid JSON only, no extra text or markdown
{
  "points_earned": <integer matching one of the level point values above>,
  "level_matched": "<exact label of the chosen level>",
  "feedback": "<2-4 sentences of specific feedback>"
}
EOT;

        try {
            $response = Http::timeout(45)->post("{$this->apiUrl}?key={$this->apiKey}", [
                'contents'         => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    'temperature'      => 0.1,
                    'responseMimeType' => 'application/json',
                ],
            ]);

            if ($response->successful()) {
                $data   = $response->json();
                $text   = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
                $result = json_decode($text, true);

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
    protected function gradeWithAI($criterion, string $content): array
    {
        if (empty($this->apiKey)) {
            return $this->apiKeyMissing();
        }

        $rules        = $criterion->checking_rules ?? [];
        $customPrompt = $rules['prompt'] ?? '';
        $maxPts       = $criterion->max_points;

        $prompt = <<<EOT
You are a strict and fair academic grader. Grade this student submission for ONE specific criterion.

## CRITERION
Name: {$criterion->criterion_name}
Description: {$criterion->description}
Maximum Points: {$maxPts}
{$customPrompt}

## STUDENT SUBMISSION
{$content}

## INSTRUCTIONS
- Evaluate ONLY this criterion — ignore everything else.
- Be objective and specific in feedback (2-3 sentences).

## RESPONSE — JSON only
{
  "points_earned": <number between 0 and {$maxPts}>,
  "feedback": "<specific feedback explaining the score>"
}
EOT;

        try {
            $response = Http::timeout(45)->post("{$this->apiUrl}?key={$this->apiKey}", [
                'contents'         => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0.1, 'responseMimeType' => 'application/json'],
            ]);

            if ($response->successful()) {
                $data   = $response->json();
                $text   = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
                $result = json_decode($text, true);

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
            if (Storage::exists($submission->file_path)) {
                $raw = Storage::get($submission->file_path);
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
}