<?php

namespace App\Services;

use App\Models\LearningRecommendation;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LearningRecommendationService
{
    /**
     * Generate personalized resources for a completed PRETEST attempt.
     *
     * Safety rules enforced here:
     * - quiz must have a standardized learning topic
     * - quiz must be registered as a pretest in an ACTIVE research experiment
     * - class must be the experimental group with recommendations enabled
     * - student must be consented in that group
     * - quiz percentage must be below the experiment's weak-topic threshold
     * - professor materials are restricted to the same lab session
     * - library resources are approved, topic-matched, subject-matched and program-compatible
     */
    public function generateFromQuizAttempt(QuizAttempt $attempt): int
    {
        $attempt->loadMissing('quiz.labSession');

        $quiz = $attempt->quiz;
        $session = $quiz?->labSession;

        if (!$quiz || !$session || !$quiz->learning_topic_id) {
            return 0;
        }

        $totalPoints = (float) ($attempt->total_points ?? 0);
        if ($totalPoints <= 0) {
            return 0;
        }

        $percentage = round(((float) $attempt->score / $totalPoints) * 100, 2);

        $context = $this->resolveEligibleExperiment(
            (int) $quiz->id,
            (int) $session->id,
            (int) $attempt->user_id
        );

        if (!$context) {
            return 0;
        }

        if ($percentage >= (float) $context->weak_topic_threshold) {
            return 0;
        }

        $created = 0;
        $topicId = (int) $quiz->learning_topic_id;

        // 1) Professor materials: SAME CLASS ONLY + standardized topic match.
        $materialIds = DB::table('materials')
            ->join('professor_material_topic', 'professor_material_topic.material_id', '=', 'materials.id')
            ->where('materials.lab_session_id', $session->id)
            ->where('professor_material_topic.learning_topic_id', $topicId)
            ->pluck('materials.id');

        foreach ($materialIds as $materialId) {
            $recommendation = LearningRecommendation::firstOrCreate(
                [
                    'user_id' => $attempt->user_id,
                    'quiz_attempt_id' => $attempt->id,
                    'learning_topic_id' => $topicId,
                    'material_id' => $materialId,
                ],
                [
                    'lab_session_id' => $session->id,
                    'learning_resource_id' => null,
                    'risk_prediction_id' => null,
                    'research_experiment_id' => $context->research_experiment_id,
                    'trigger_quiz_percentage' => $percentage,
                    'status' => 'recommended',
                    'recommended_at' => now(),
                ]
            );

            if ($recommendation->wasRecentlyCreated) {
                $created++;
            }
        }

        // 2) Admin library resources.
        // During transition we accept either the class code OR a canonical key
        // generated from the subject name (e.g. "Automata Theory" => AUTOMATA_THEORY).
        $subjectKeys = collect([
            strtoupper(trim((string) $session->class_code)),
            strtoupper(Str::slug((string) $session->subject_name, '_')),
        ])->filter()->unique()->values()->all();

        $resourceIds = DB::table('learning_resources')
            ->join('learning_resource_topic', 'learning_resource_topic.learning_resource_id', '=', 'learning_resources.id')
            ->where('learning_resource_topic.learning_topic_id', $topicId)
            ->where('learning_resources.status', 'approved')
            ->whereIn('learning_resources.subject_key', $subjectKeys)
            ->where(function ($query) use ($session) {
                $query->whereNull('learning_resources.program')
                    ->orWhere('learning_resources.program', $session->program);
            })
            ->pluck('learning_resources.id');

        foreach ($resourceIds as $resourceId) {
            $recommendation = LearningRecommendation::firstOrCreate(
                [
                    'user_id' => $attempt->user_id,
                    'quiz_attempt_id' => $attempt->id,
                    'learning_topic_id' => $topicId,
                    'learning_resource_id' => $resourceId,
                ],
                [
                    'lab_session_id' => $session->id,
                    'material_id' => null,
                    'risk_prediction_id' => null,
                    'research_experiment_id' => $context->research_experiment_id,
                    'trigger_quiz_percentage' => $percentage,
                    'status' => 'recommended',
                    'recommended_at' => now(),
                ]
            );

            if ($recommendation->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * Returns the eligible experimental context, otherwise null.
     */
    private function resolveEligibleExperiment(int $quizId, int $labSessionId, int $userId): ?object
    {
        $now = now();

        return DB::table('research_assessments as ra')
            ->join('research_experiments as re', 're.id', '=', 'ra.research_experiment_id')
            ->join('research_groups as rg', function ($join) use ($labSessionId) {
                $join->on('rg.research_experiment_id', '=', 're.id')
                    ->where('rg.lab_session_id', '=', $labSessionId);
            })
            ->join('research_participants as rp', function ($join) use ($userId) {
                $join->on('rp.research_group_id', '=', 'rg.id')
                    ->where('rp.user_id', '=', $userId);
            })
            ->where('ra.quiz_id', $quizId)
            ->where('ra.assessment_type', 'pretest')
            ->where('re.status', 'active')
            ->where('rg.group_type', 'experimental')
            ->where('rg.recommendations_enabled', true)
            ->where('rp.consent_status', 'consented')
            ->where(function ($query) use ($now) {
                $query->whereNull('re.intervention_starts_at')
                    ->orWhere('re.intervention_starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('re.intervention_ends_at')
                    ->orWhere('re.intervention_ends_at', '>=', $now);
            })
            ->select([
                're.id as research_experiment_id',
                're.weak_topic_threshold',
                'rg.id as research_group_id',
            ])
            ->first();
    }
}
