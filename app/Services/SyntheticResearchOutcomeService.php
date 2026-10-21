<?php

namespace App\Services;

use InvalidArgumentException;
use Random\Engine\Mt19937;
use Random\Randomizer;

/** Pure seeded later evidence simulation. No labels, database or filesystem writes. */
class SyntheticResearchOutcomeService
{
    public const VERSION = 'synthetic-later-academic-v1';

    public function generate(array $features, string $featureSha256, array $scenario, int $seed, array $policy): array
    {
        $service = app(ResearchOutcomePolicyService::class);
        if ($service->configurationErrors($policy) || $seed < 0 || ! preg_match('/\A[0-9a-f]{64}\z/', $featureSha256) ||
            ($scenario['approved'] ?? null) !== true || ($scenario['frozen'] ?? null) !== true ||
            ($scenario['policy_version'] ?? null) !== ResearchOutcomePolicyService::VERSION ||
            ! is_string($scenario['name'] ?? null) || ! preg_match('/\A[A-Za-z0-9_-]+\z/', $scenario['name']) ||
            ($scenario['approval_reference'] ?? null) !== $policy['approval_reference'] ||
            ! is_array($scenario['profiles'] ?? null) || ! is_array($scenario['opportunities'] ?? null) || ! $scenario['opportunities']) {
            throw new InvalidArgumentException('Freeze and approve a separate synthetic outcome scenario and target policy before sampling.');
        }
        $rng = new Randomizer(new Mt19937($seed));
        $unit = fn () => ($rng->getInt(0, 16777215) + 0.5) / 16777216;
        $normal = fn () => sqrt(-2 * log($unit())) * cos(2 * M_PI * $unit());
        $start = $service->date($policy['outcome_start']);
        $end = $service->date($policy['outcome_end']);
        $cutoffs = array_unique(array_column($features, 'feature_cutoff_at'));
        if (count($cutoffs) !== 1 || $end->isFuture()) {
            throw new InvalidArgumentException('Explicit shared cutoff and completed simulated window are required.');
        }
        $rows = [];
        $contexts = [];
        $scenarioHash = hash('sha256', json_encode($scenario, JSON_THROW_ON_ERROR));
        foreach ($features as $index => $feature) {
            if (($feature['data_origin'] ?? null) !== 'synthetic' ||
                ! preg_match('/\ASYN-[A-Za-z0-9]+-[0-9a-f]{12}-[0-9]{6}\z/', $feature['student_group_key'] ?? '') ||
                ! preg_match('/\ASYN-[A-Za-z0-9]+\z/', $feature['lab_session_id'] ?? '')) {
                throw new InvalidArgumentException('Only explicit synthetic feature units may receive simulated outcome evidence.');
            }
            $profile = $scenario['profiles'][$feature['synthetic_profile'] ?? ''] ?? null;
            if (! is_array($profile) || array_diff(['completion_probability', 'performance_mean', 'performance_sd'], array_keys($profile))) {
                throw new InvalidArgumentException('Every virtual profile requires approved overlapping outcome parameters.');
            }
            foreach ($profile as $value) {
                if ((! is_int($value) && ! is_float($value)) || ! is_finite((float) $value)) {
                    throw new InvalidArgumentException('Invalid synthetic outcome profile parameters.');
                }
            }
            if ($profile['completion_probability'] < 0 || $profile['completion_probability'] > 1 ||
                $profile['performance_mean'] < 0 || $profile['performance_mean'] > 100 || $profile['performance_sd'] <= 0) {
                throw new InvalidArgumentException('Invalid synthetic outcome profile probabilities or dispersion.');
            }
            $opportunities = [];
            foreach ($scenario['opportunities'] as $opIndex => $op) {
                if (! in_array($op['type'] ?? null, $policy['assessment_types'], true) ||
                    ! is_bool($op['eligible'] ?? null) || ! is_bool($op['required'] ?? null)) {
                    throw new InvalidArgumentException('Explicit approved virtual opportunity eligibility is required.');
                }
                $completed = $op['eligible'] && $op['required'] && $unit() < $profile['completion_probability'];
                $score = $completed ? round(max(0, min(100, $profile['performance_mean'] + $profile['performance_sd'] * $normal())), 4) : null;
                $opportunities[] = ['opportunity_key' => $op['type'].':'.$feature['lab_session_id'].':SIM'.($opIndex + 1),
                    'type' => $op['type'], 'available_at' => $start->format('Y-m-d H:i:s'), 'closed_at' => $end->format('Y-m-d H:i:s'),
                    'required' => $op['required'], 'eligible' => $op['eligible'], 'eligibility_verified' => true,
                    'collection_complete' => true, 'state' => $completed ? 'completed' : 'not_completed',
                    'score' => $score, 'max_score' => 100,
                    'event_at' => $completed ? $end->format('Y-m-d H:i:s') : null,
                    'recorded_at' => $completed ? $end->format('Y-m-d H:i:s') : null,
                    'event_key' => $completed ? ($op['type'] === 'quiz' ? 'quiz_attempt:' : 'submission:').'SIM'.$index.'_'.$opIndex : null,
                    'is_simulated' => true];
            }
            $rows[] = ['student_group_key' => $feature['student_group_key'], 'lab_session_id' => $feature['lab_session_id'], 'opportunities' => $opportunities];
            $contexts[] = array_intersect_key($feature, array_flip(['student_group_key', 'lab_session_id', 'feature_cutoff_at', 'feature_version']));
        }
        $bundle = ['policy_version' => ResearchOutcomePolicyService::VERSION, 'feature_sha256' => $featureSha256,
            'prediction_cutoff' => reset($cutoffs), 'outcome_start' => $policy['outcome_start'], 'outcome_end' => $policy['outcome_end'],
            'collected_at' => $end->format('Y-m-d H:i:s'), 'data_origin' => 'synthetic', 'outcome_origin' => 'synthetic',
            'approval_reference' => $policy['approval_reference'], 'eligibility_verified' => true,
            'predictor_opportunity_keys' => [], 'rows' => $rows,
            'simulation' => ['generator_version' => self::VERSION, 'scenario' => $scenario['name'], 'seed' => $seed,
                'feature_sha256' => $featureSha256, 'scenario_sha256' => $scenarioHash]];
        $service->inspect($contexts, $bundle, $policy); // The identical policy validates both origins.

        return $bundle; // Evidence only. No canonical targets are stored in this artifact.
    }
}
