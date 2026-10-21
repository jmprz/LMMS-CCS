<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use InvalidArgumentException;

/** Versioned research endpoint, not an official final grade or a v5 predictor. */
class ResearchOutcomePolicyService
{
    public const VERSION = 'research-later-academic-v1';

    public function date(mixed $value): Carbon
    {
        if (! is_string($value) || ! Carbon::canBeCreatedFromFormat($value, 'Y-m-d H:i:s')) {
            throw new InvalidArgumentException('Invalid research outcome timestamp.');
        }
        $date = Carbon::createFromFormat('Y-m-d H:i:s', $value, config('app.timezone'));
        if ($date->format('Y-m-d H:i:s') !== $value) {
            throw new InvalidArgumentException('Invalid research outcome timestamp.');
        }

        return $date;
    }

    private function fields(array $value, array $expected): void
    {
        $keys = array_keys($value);
        sort($keys);
        sort($expected);
        if ($keys !== $expected) {
            throw new InvalidArgumentException('Unapproved or missing research evidence fields.');
        }
    }

    private function finite(mixed $value): bool
    {
        return (is_int($value) || is_float($value)) && is_finite((float) $value);
    }

    public function configurationErrors(array $policy): array
    {
        $errors = [];
        if (($policy['policy_version'] ?? null) !== self::VERSION || ($policy['enabled'] ?? null) !== true) {
            $errors[] = 'Versioned research outcome policy is disabled or invalid.';
        }
        if (! is_string($policy['approval_reference'] ?? null) || trim($policy['approval_reference']) === '') {
            $errors[] = 'Policy approval reference is required.';
        }
        $threshold = $policy['performance_threshold'] ?? null;
        if (! is_numeric($threshold) || ! is_finite((float) $threshold) || $threshold < 0 || $threshold > 100) {
            $errors[] = 'An approved percentage performance threshold is required.';
        }
        if (! in_array($policy['comparison_direction'] ?? null, ['gte', 'lte'], true)) {
            $errors[] = 'Approve a comparison direction: gte or lte.';
        }
        if (filter_var($policy['minimum_eligible_opportunities'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $errors[] = 'Approve a positive minimum eligible opportunity count.';
        }
        if (! in_array($policy['no_participation_rule'] ?? null, ['at_risk', 'exclude'], true)) {
            $errors[] = 'Approve the confirmed no-participation rule.';
        }
        if (($policy['aggregation'] ?? null) !== 'equal_opportunity_percentage') {
            $errors[] = 'Approve the supported equal-opportunity percentage aggregation.';
        }
        $types = $policy['assessment_types'] ?? null;
        if (! is_array($types) || ! $types || array_diff($types, ['quiz', 'task']) || count(array_unique($types)) !== count($types)) {
            $errors[] = 'Approve a unique nonempty subset of quiz/task assessment types.';
        }
        try {
            if ($this->date($policy['outcome_start'] ?? null)->gt($this->date($policy['outcome_end'] ?? null))) {
                $errors[] = 'Outcome window is reversed.';
            }
        } catch (InvalidArgumentException) {
            $errors[] = 'Approve explicit outcome start/end timestamps.';
        }

        return $errors;
    }

    /** No identities or per-student labels are returned by the public readiness report. */
    public function inspect(array $features, array $bundle, ?array $policy = null): array
    {
        $policy ??= config('research_outcomes');
        $this->fields($bundle, ['policy_version', 'feature_sha256', 'prediction_cutoff', 'outcome_start', 'outcome_end', 'collected_at',
            'data_origin', 'outcome_origin', 'approval_reference', 'eligibility_verified', 'predictor_opportunity_keys', 'rows', 'simulation']);
        if (! is_string($bundle['feature_sha256']) || ! preg_match('/\A[0-9a-f]{64}\z/', $bundle['feature_sha256']) ||
            $bundle['policy_version'] !== self::VERSION || $bundle['eligibility_verified'] !== true ||
            ! is_string($bundle['approval_reference']) || trim($bundle['approval_reference']) === '') {
            throw new InvalidArgumentException('Approved outcome eligibility provenance is required.');
        }
        $synthetic = $bundle['data_origin'] === 'synthetic' && $bundle['outcome_origin'] === 'synthetic';
        if (! $synthetic && ! ($bundle['data_origin'] === 'real' && $bundle['outcome_origin'] === 'research_observed')) {
            throw new InvalidArgumentException('Outcome/data origin mismatch.');
        }
        if ($synthetic) {
            if (! is_array($bundle['simulation'])) {
                throw new InvalidArgumentException('Synthetic outcome provenance is required.');
            }
            $this->fields($bundle['simulation'], ['generator_version', 'scenario', 'seed', 'feature_sha256', 'scenario_sha256']);
            foreach (['feature_sha256', 'scenario_sha256'] as $hash) {
                if (! is_string($bundle['simulation'][$hash]) || ! preg_match('/\A[0-9a-f]{64}\z/', $bundle['simulation'][$hash])) {
                    throw new InvalidArgumentException('Synthetic outcome source hashes are required.');
                }
            }
            if (! is_int($bundle['simulation']['seed']) || $bundle['simulation']['seed'] < 0 ||
                ! is_string($bundle['simulation']['scenario']) || ! preg_match('/\A[A-Za-z0-9_-]+\z/', $bundle['simulation']['scenario']) ||
                $bundle['simulation']['generator_version'] !== SyntheticResearchOutcomeService::VERSION) {
                throw new InvalidArgumentException('Invalid synthetic outcome provenance.');
            }
        } elseif ($bundle['simulation'] !== null) {
            throw new InvalidArgumentException('Real observations cannot carry simulated outcome provenance.');
        }
        $cutoff = $this->date($bundle['prediction_cutoff']);
        $start = $this->date($bundle['outcome_start']);
        $end = $this->date($bundle['outcome_end']);
        $collected = $this->date($bundle['collected_at']);
        if (! $start->gt($cutoff) || $start->gt($end) || $end->gt($collected) || $collected->isFuture()) {
            throw new InvalidArgumentException('Leakage/unfinished outcome window: require cutoff < start <= end <= collection <= now.');
        }
        if (! is_array($bundle['predictor_opportunity_keys']) || ! array_is_list($bundle['predictor_opportunity_keys']) ||
            ! is_array($bundle['rows']) || ! array_is_list($bundle['rows']) || ! array_is_list($features) || ! $features) {
            throw new InvalidArgumentException('Explicit feature cohort, evidence rows and predictor opportunity registry are required.');
        }
        foreach ($bundle['predictor_opportunity_keys'] as $key) {
            if (! is_string($key) || ! preg_match('/\A(quiz|task):[A-Za-z0-9_-]+:[A-Za-z0-9_-]+\z/', $key)) {
                throw new InvalidArgumentException('Invalid predictor opportunity registry.');
            }
        }
        $cohort = [];
        foreach ($features as $feature) {
            $this->fields($feature, ['student_group_key', 'lab_session_id', 'feature_cutoff_at', 'feature_version']);
            $groupPattern = $synthetic ? '/\ASYN-[A-Za-z0-9]+-[0-9a-f]{12}-[0-9]{6}\z/' : '/\A[0-9a-f]{64}\z/';
            $classPattern = $synthetic ? '/\ASYN-[A-Za-z0-9]+\z/' : '/\A[1-9][0-9]*\z/';
            if (! is_string($feature['student_group_key']) || ! preg_match($groupPattern, $feature['student_group_key']) ||
                ! is_string($feature['lab_session_id']) || ! preg_match($classPattern, $feature['lab_session_id']) ||
                $feature['feature_version'] !== 'v5' || $feature['feature_cutoff_at'] !== $bundle['prediction_cutoff']) {
                throw new InvalidArgumentException('Invalid cohort origin, frozen feature version or shared cutoff.');
            }
            $pair = $feature['student_group_key'].':'.$feature['lab_session_id'];
            if (isset($cohort[$pair])) {
                throw new InvalidArgumentException('Duplicate feature student/class pair.');
            }
            $cohort[$pair] = true;
        }
        $errors = $this->configurationErrors($policy);
        if (! $errors && ($policy['outcome_start'] !== $bundle['outcome_start'] || $policy['outcome_end'] !== $bundle['outcome_end'] ||
            $policy['approval_reference'] !== $bundle['approval_reference'])) {
            $errors[] = 'Evidence window/approval does not match the configured policy.';
        }
        $totals = ['outcome_eligible_rows' => 0, 'no_participation_at_risk_rows' => 0, 'performance_at_risk_rows' => 0,
            'not_at_risk_rows' => 0, 'excluded_indeterminate_rows' => count($features) - count($bundle['rows'])];
        if ($totals['excluded_indeterminate_rows'] < 0) {
            throw new InvalidArgumentException('Evidence is not a subset of the pinned cohort.');
        }
        $seen = [];
        $eventKeys = [];
        foreach ($bundle['rows'] as $row) {
            $this->fields($row, ['student_group_key', 'lab_session_id', 'opportunities']);
            if (! is_string($row['student_group_key']) || ! is_string($row['lab_session_id'])) {
                throw new InvalidArgumentException('Invalid research outcome cohort pair.');
            }
            $pair = $row['student_group_key'].':'.$row['lab_session_id'];
            if (! isset($cohort[$pair]) || isset($seen[$pair]) || ! is_array($row['opportunities']) || ! array_is_list($row['opportunities'])) {
                throw new InvalidArgumentException('Duplicate/unmatched outcome pair or invalid opportunity list.');
            }
            $seen[$pair] = true;
            $opKeys = [];
            foreach ($row['opportunities'] as $op) {
                $this->validateOpportunity($op, $row['lab_session_id'], $start, $end, $collected, $synthetic);
                if (isset($opKeys[$op['opportunity_key']]) || in_array($op['opportunity_key'], $bundle['predictor_opportunity_keys'], true)) {
                    throw new InvalidArgumentException('Leakage or duplicate academic opportunity detected.');
                }
                $opKeys[$op['opportunity_key']] = true;
                if ($op['event_key'] !== null) {
                    if (isset($eventKeys[$op['event_key']])) {
                        throw new InvalidArgumentException('Academic event reused across outcome observations.');
                    }
                    $eventKeys[$op['event_key']] = true;
                }
            }
            if ($errors) {
                $totals['excluded_indeterminate_rows']++;
                continue;
            }
            $evaluation = $this->evaluate($row['opportunities'], $policy);
            $totals['outcome_eligible_rows'] += (int) $evaluation['eligible'];
            $bucket = match ($evaluation['reason']) {
                'confirmed_no_participation' => 'no_participation_at_risk_rows',
                'below_performance_criterion' => 'performance_at_risk_rows',
                'meets_performance_criterion' => 'not_at_risk_rows',
                default => 'excluded_indeterminate_rows',
            };
            $totals[$bucket]++;
        }
        $labeled = $totals['no_participation_at_risk_rows'] + $totals['performance_at_risk_rows'] + $totals['not_at_risk_rows'];
        $reportedTotals = $totals;
        if ($errors) {
            foreach (['outcome_eligible_rows', 'no_participation_at_risk_rows', 'performance_at_risk_rows', 'not_at_risk_rows'] as $field) {
                $reportedTotals[$field] = null;
            }
        }

        return ['policy_version' => self::VERSION, 'feature_version' => 'v5', 'prediction_cutoff' => $bundle['prediction_cutoff'],
            'outcome_window' => [$bundle['outcome_start'], $bundle['outcome_end']],
            'performance_threshold' => $policy['performance_threshold'] ?? null, 'comparison_direction' => $policy['comparison_direction'] ?? null,
            'aggregation' => $policy['aggregation'] ?? null, 'minimum_eligible_opportunities' => $policy['minimum_eligible_opportunities'] ?? null,
            'no_participation_rule' => $policy['no_participation_rule'] ?? null, 'included_assessment_types' => $policy['assessment_types'] ?? [],
            'opportunity_definition' => 'explicitly verified required/eligible, first available in the later window, closed by its end; collection complete before nonparticipation can be inferred',
            'data_origin' => $bundle['data_origin'], 'outcome_origin' => $bundle['outcome_origin'],
            'real_feature_rows' => $synthetic ? 0 : count($features), 'synthetic_feature_rows' => $synthetic ? count($features) : 0,
            ...$reportedTotals, 'label_balance' => ['origin' => $bundle['data_origin'],
                'at_risk' => $errors ? null : $totals['no_participation_at_risk_rows'] + $totals['performance_at_risk_rows'],
                'not_at_risk' => $errors ? null : $totals['not_at_risk_rows'],
                'at_risk_percent' => ! $errors && $labeled ? round(100 * ($totals['no_participation_at_risk_rows'] + $totals['performance_at_risk_rows']) / $labeled, 2) : null,
                'not_at_risk_percent' => ! $errors && $labeled ? round(100 * $totals['not_at_risk_rows'] / $labeled, 2) : null],
            'policy_sha256' => hash('sha256', json_encode($policy, JSON_THROW_ON_ERROR)),
            'configuration_valid' => ! $errors, 'blockers' => $errors,
            'ready_for_review' => ! $errors && $labeled > 0, 'training_authorized' => false, 'labels_exported' => false];
    }

    private function validateOpportunity(array $op, string $class, Carbon $start, Carbon $end, Carbon $collected, bool $synthetic): void
    {
        $this->fields($op, ['opportunity_key', 'type', 'available_at', 'closed_at', 'required', 'eligible', 'eligibility_verified',
            'collection_complete', 'state', 'score', 'max_score', 'event_at', 'recorded_at', 'event_key', 'is_simulated']);
        if (! in_array($op['type'], ['quiz', 'task'], true) || ! is_string($op['opportunity_key']) ||
            ! preg_match('/\A'.preg_quote($op['type'].':'.$class.':', '/').'[A-Za-z0-9_-]+\z/', $op['opportunity_key'])) {
            throw new InvalidArgumentException('Invalid class-scoped academic opportunity.');
        }
        foreach (['required', 'eligible', 'eligibility_verified', 'collection_complete', 'is_simulated'] as $field) {
            if (! is_bool($op[$field])) {
                throw new InvalidArgumentException('Explicit boolean eligibility/completeness fields are required.');
            }
        }
        if (! $op['eligibility_verified'] || $op['is_simulated'] !== $synthetic) {
            throw new InvalidArgumentException('Unverified eligibility or simulated quiz attempts cannot supply observed outcome evidence.');
        }
        $available = $this->date($op['available_at']);
        $closed = $this->date($op['closed_at']);
        if ($available->lt($start) || $available->gt($closed) || $closed->gt($end)) {
            throw new InvalidArgumentException('Leakage or unfinished assessment: require first availability/close inside the later window.');
        }
        if (! in_array($op['state'], ['completed', 'not_completed', 'unavailable'], true) ||
            ! $this->finite($op['max_score']) || $op['max_score'] <= 0) {
            throw new InvalidArgumentException('Invalid outcome completion state or assessment maximum.');
        }
        if ($op['state'] !== 'completed') {
            if ($op['score'] !== null || $op['event_at'] !== null || $op['event_key'] !== null || $op['recorded_at'] !== null) {
                throw new InvalidArgumentException('Missing/uncompleted observations must not contain invented scores or events.');
            }
        } else {
            $event = $this->date($op['event_at']);
            $recorded = $this->date($op['recorded_at']);
            if ($event->lt($available) || $event->gt($end) || $recorded->lt($event) || $recorded->gt($collected) ||
                ! is_string($op['event_key']) || ! preg_match('/\A(quiz_attempt|submission):[A-Za-z0-9_-]+\z/', $op['event_key']) ||
                ! str_starts_with($op['event_key'], $op['type'] === 'quiz' ? 'quiz_attempt:' : 'submission:')) {
                throw new InvalidArgumentException('Leakage/invalid academic event or recording chronology.');
            }
            if ($op['score'] !== null && (! $this->finite($op['score']) || $op['score'] < 0 || $op['score'] > $op['max_score'])) {
                throw new InvalidArgumentException('Invalid later assessment score.');
            }
        }
    }

    /** Called only after inspect has validated the sealed evidence and configuration. */
    private function evaluate(array $opportunities, array $policy): array
    {
        $eligible = array_values(array_filter($opportunities, fn ($op) => $op['required'] && $op['eligible'] && in_array($op['type'], $policy['assessment_types'], true)));
        $base = ['eligible' => count($eligible) >= (int) $policy['minimum_eligible_opportunities']];
        if (! $base['eligible']) {
            return $base + ['label' => null, 'performance' => null, 'reason' => 'insufficient_opportunities'];
        }
        foreach ($eligible as $op) {
            if (! $op['collection_complete'] || $op['state'] === 'unavailable' || ($op['state'] === 'completed' && $op['score'] === null)) {
                return $base + ['label' => null, 'performance' => null, 'reason' => 'unavailable_or_ungraded'];
            }
        }
        $completed = count(array_filter($eligible, fn ($op) => $op['state'] === 'completed'));
        if ($completed === 0) {
            return $base + ['label' => $policy['no_participation_rule'] === 'at_risk' ? 'at_risk' : null, 'performance' => null,
                'reason' => $policy['no_participation_rule'] === 'at_risk' ? 'confirmed_no_participation' : 'no_participation_excluded'];
        }
        // Confirmed required non-completion contributes zero only by explicit policy,
        // not through coercion of a NULL score or an absent database record.
        $performance = array_sum(array_map(fn ($op) => $op['state'] === 'not_completed' ? 0 : 100 * $op['score'] / $op['max_score'], $eligible)) / count($eligible);
        $passes = $policy['comparison_direction'] === 'gte' ? $performance >= (float) $policy['performance_threshold'] : $performance <= (float) $policy['performance_threshold'];

        return $base + ['label' => $passes ? 'not_at_risk' : 'at_risk', 'performance' => $performance,
            'reason' => $passes ? 'meets_performance_criterion' : 'below_performance_criterion'];
    }
}
