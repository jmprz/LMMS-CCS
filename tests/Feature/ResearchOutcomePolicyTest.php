<?php

namespace Tests\Feature;

use App\Services\ResearchOutcomeFileService;
use App\Services\ResearchOutcomePolicyService;
use App\Services\SyntheticResearchOutcomeService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class ResearchOutcomePolicyTest extends TestCase
{
    private array $policy;

    private array $features;

    private array $bundle;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-01 12:00:00');
        // Software fixtures only; these are not institutional/research decisions.
        $this->policy = ['policy_version' => ResearchOutcomePolicyService::VERSION, 'enabled' => true,
            'approval_reference' => 'SOFTWARE-TEST-ONLY', 'performance_threshold' => 60, 'comparison_direction' => 'gte',
            'minimum_eligible_opportunities' => 1, 'no_participation_rule' => 'at_risk', 'aggregation' => 'equal_opportunity_percentage',
            'outcome_start' => '2026-10-21 00:00:00', 'outcome_end' => '2026-10-25 23:00:00', 'assessment_types' => ['quiz', 'task']];
        $this->features = [['student_group_key' => str_repeat('a', 64), 'lab_session_id' => '1',
            'feature_cutoff_at' => '2026-10-20 22:33:11', 'feature_version' => 'v5']];
        $this->bundle = ['policy_version' => ResearchOutcomePolicyService::VERSION, 'feature_sha256' => str_repeat('b', 64),
            'prediction_cutoff' => $this->features[0]['feature_cutoff_at'],
            'outcome_start' => $this->policy['outcome_start'], 'outcome_end' => $this->policy['outcome_end'],
            'collected_at' => '2026-10-26 12:00:00', 'data_origin' => 'real', 'outcome_origin' => 'research_observed',
            'approval_reference' => 'SOFTWARE-TEST-ONLY', 'eligibility_verified' => true,
            'predictor_opportunity_keys' => ['quiz:1:EARLY'], 'simulation' => null,
            'rows' => [['student_group_key' => $this->features[0]['student_group_key'], 'lab_session_id' => '1', 'opportunities' => [$this->op()]]]];
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function op(array $overrides = []): array
    {
        return array_replace(['opportunity_key' => 'quiz:1:LATER', 'type' => 'quiz',
            'available_at' => '2026-10-21 08:00:00', 'closed_at' => '2026-10-24 18:00:00',
            'required' => true, 'eligible' => true, 'eligibility_verified' => true, 'collection_complete' => true,
            'state' => 'completed', 'score' => 60, 'max_score' => 100,
            'event_at' => '2026-10-22 10:00:00', 'recorded_at' => '2026-10-23 10:00:00',
            'event_key' => 'quiz_attempt:TEST1', 'is_simulated' => false], $overrides);
    }

    private function missing(string $state = 'not_completed'): array
    {
        return $this->op(['state' => $state, 'score' => null, 'event_at' => null, 'recorded_at' => null, 'event_key' => null]);
    }

    private function report(): array
    {
        return app(ResearchOutcomePolicyService::class)->inspect($this->features, $this->bundle, $this->policy);
    }

    public function test_no_opportunity_or_no_outcome_row_is_not_automatically_at_risk(): void
    {
        foreach ([[], [['student_group_key' => str_repeat('a', 64), 'lab_session_id' => '1', 'opportunities' => []]]] as $rows) {
            $this->bundle['rows'] = $rows;
            $report = $this->report();
            $this->assertSame(0, $report['label_balance']['at_risk']);
            $this->assertSame(1, $report['excluded_indeterminate_rows']);
            $this->assertFalse($report['ready_for_review']);
        }
    }

    public function test_required_opportunity_with_confirmed_no_participation_is_at_risk(): void
    {
        $this->bundle['rows'][0]['opportunities'] = [$this->missing()];
        $this->assertSame(1, $this->report()['no_participation_at_risk_rows']);
        $this->policy['no_participation_rule'] = 'exclude';
        $this->assertSame(1, $this->report()['excluded_indeterminate_rows']);
    }

    public function test_completed_below_equal_above_and_zero_performance(): void
    {
        foreach ([0 => 1, 59 => 1, 60 => 0, 61 => 0] as $score => $risk) {
            $this->bundle['rows'][0]['opportunities'][0]['score'] = $score;
            $report = $this->report();
            $this->assertSame($risk, $report['performance_at_risk_rows']);
            $this->assertSame(1 - $risk, $report['not_at_risk_rows']);
            $this->assertSame(0, $report['no_participation_at_risk_rows']);
        }
    }

    public function test_inverse_direction_equality_is_inclusive(): void
    {
        $this->policy['comparison_direction'] = 'lte';
        $this->assertSame(1, $this->report()['not_at_risk_rows']);
        $this->bundle['rows'][0]['opportunities'][0]['score'] = 61;
        $this->assertSame(1, $this->report()['performance_at_risk_rows']);
    }

    public function test_null_unavailable_or_incomplete_collection_is_indeterminate(): void
    {
        foreach ([$this->op(['score' => null]), $this->missing('unavailable'), $this->missing()] as $op) {
            if ($op['state'] === 'not_completed') {
                $op['collection_complete'] = false;
            }
            $this->bundle['rows'][0]['opportunities'] = [$op];
            $report = $this->report();
            $this->assertSame(1, $report['excluded_indeterminate_rows']);
            $this->assertSame(0, $report['label_balance']['at_risk']);
        }
    }

    public function test_optional_or_ineligible_assessment_cannot_create_absence(): void
    {
        foreach (['required', 'eligible'] as $field) {
            $op = $this->missing();
            $op[$field] = false;
            $this->bundle['rows'][0]['opportunities'] = [$op];
            $this->assertSame(0, $this->report()['label_balance']['at_risk']);
            $this->assertSame(0, $this->report()['outcome_eligible_rows']);
        }
    }

    public function test_mixed_quiz_task_uses_eligible_equal_opportunity_percentages(): void
    {
        $quiz = $this->op(['score' => 100]);
        $task = $this->op(['type' => 'task', 'opportunity_key' => 'task:1:T1', 'event_key' => 'submission:T1', 'score' => 10, 'max_score' => 50]);
        $this->bundle['rows'][0]['opportunities'] = [$quiz, $task]; // (100 + 20)/2 = 60, not raw points weighting.
        $this->assertSame(1, $this->report()['not_at_risk_rows']);
        $this->policy['assessment_types'] = ['task'];
        $this->assertSame(1, $this->report()['performance_at_risk_rows']);
        $this->policy['assessment_types'] = ['quiz', 'task'];
        $task = array_replace($task, $this->missing(), ['type' => 'task', 'opportunity_key' => 'task:1:T1']);
        $this->bundle['rows'][0]['opportunities'] = [$quiz, $task]; // Explicit noncompletion contributes 0: mean 50.
        $this->assertSame(1, $this->report()['performance_at_risk_rows']);
    }

    public function test_different_class_opportunity_counts_and_minimum_are_explicit(): void
    {
        $feature = array_replace($this->features[0], ['lab_session_id' => '2']);
        $this->features[] = $feature;
        $ops = [$this->op(['opportunity_key' => 'quiz:2:Q1', 'event_key' => 'quiz_attempt:Q2A']),
            $this->op(['opportunity_key' => 'quiz:2:Q2', 'event_key' => 'quiz_attempt:Q2B'])];
        $this->bundle['rows'][] = ['student_group_key' => $feature['student_group_key'], 'lab_session_id' => '2', 'opportunities' => $ops];
        $this->assertSame(2, $this->report()['not_at_risk_rows']);
        $this->policy['minimum_eligible_opportunities'] = 2;
        $this->assertSame(1, $this->report()['not_at_risk_rows']);
        $this->assertSame(1, $this->report()['excluded_indeterminate_rows']);
    }

    public function test_same_or_pre_cutoff_opportunity_and_reused_events_fail(): void
    {
        foreach (['2026-10-20 22:33:11', '2026-10-19 12:00:00'] as $time) {
            $this->bundle['rows'][0]['opportunities'][0]['available_at'] = $time;
            try {
                $this->report();
                $this->fail('Predictor-period availability accepted.');
            } catch (InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }
        $this->bundle['rows'][0]['opportunities'][0] = $this->op(['opportunity_key' => 'quiz:1:EARLY']);
        $this->expectException(InvalidArgumentException::class);
        $this->report();
    }

    public function test_predictor_window_overlap_and_unfinished_window_fail(): void
    {
        $this->bundle['outcome_start'] = $this->bundle['prediction_cutoff'];
        $this->expectException(InvalidArgumentException::class);
        $this->report();
    }

    public function test_event_after_window_fails(): void
    {
        $this->bundle['rows'][0]['opportunities'][0]['event_at'] = '2026-10-26 01:00:00';
        $this->expectException(InvalidArgumentException::class);
        $this->report();
    }

    public function test_simulated_observed_attempt_is_rejected(): void
    {
        $this->bundle['rows'][0]['opportunities'][0]['is_simulated'] = true;
        $this->expectException(InvalidArgumentException::class);
        $this->report();
    }

    public function test_configuration_is_required_even_for_no_participation(): void
    {
        $this->bundle['rows'][0]['opportunities'] = [$this->missing()];
        foreach (['performance_threshold', 'comparison_direction', 'minimum_eligible_opportunities', 'aggregation', 'outcome_start', 'approval_reference'] as $field) {
            $policy = $this->policy;
            $policy[$field] = null;
            $report = app(ResearchOutcomePolicyService::class)->inspect($this->features, $this->bundle, $policy);
            $this->assertFalse($report['configuration_valid']);
            $this->assertNull($report['label_balance']['at_risk']);
            $this->assertFalse($report['ready_for_review']);
        }
    }

    public function test_unknown_evidence_fields_and_duplicate_pairs_fail(): void
    {
        $this->bundle['rows'][0]['opportunities'][0]['quiz_avg'] = 20;
        $this->expectException(InvalidArgumentException::class);
        $this->report();
    }

    public function test_duplicate_outcome_pairs_fail(): void
    {
        $this->bundle['rows'][] = $this->bundle['rows'][0];
        $this->expectException(InvalidArgumentException::class);
        $this->report();
    }

    public function test_event_cannot_be_reused_in_another_class_opportunity(): void
    {
        $this->bundle['rows'][0]['opportunities'][] = $this->op(['opportunity_key' => 'quiz:1:OTHER']);
        $this->expectException(InvalidArgumentException::class);
        $this->report();
    }

    public function test_future_collection_or_open_outcome_window_fails(): void
    {
        $this->bundle['collected_at'] = '2026-11-02 12:00:00';
        $this->expectException(InvalidArgumentException::class);
        $this->report();
    }

    public function test_eligibility_without_explicit_verification_is_rejected(): void
    {
        $this->bundle['rows'][0]['opportunities'][0]['eligibility_verified'] = false;
        $this->expectException(InvalidArgumentException::class);
        $this->report();
    }

    public function test_real_evidence_cannot_claim_synthetic_origin(): void
    {
        $this->bundle['outcome_origin'] = 'synthetic';
        $this->expectException(InvalidArgumentException::class);
        $this->report();
    }

    public function test_all_policy_settings_start_disabled_unset_and_independent_of_recommendations(): void
    {
        $policy = config('research_outcomes');
        $this->assertFalse($policy['enabled']);
        $this->assertNull($policy['performance_threshold']);
        $this->assertNull($policy['comparison_direction']);
        $this->assertSame([], $policy['assessment_types']);
        $this->assertNotEmpty(app(ResearchOutcomePolicyService::class)->configurationErrors($policy));
        $this->assertSame('final_outcome', config('risk_target.ground_truth_column'));
        $this->assertSame('v5', \App\Services\StudentRiskFeatureService::FEATURE_VERSION);
    }

    public function test_synthetic_outcomes_are_seeded_separate_evidence_and_never_write_official_tables(): void
    {
        $queryCount = 0;
        DB::listen(function () use (&$queryCount) { $queryCount++; });
        $features = [];
        for ($i = 1; $i <= 30; $i++) {
            $features[] = ['student_group_key' => 'SYN-TEST-'.str_repeat('c', 12).'-'.sprintf('%06d', $i),
                'lab_session_id' => 'SYN-TEST', 'feature_cutoff_at' => $this->bundle['prediction_cutoff'], 'feature_version' => 'v5',
                'data_origin' => 'synthetic', 'synthetic_profile' => 'overlapping'];
        }
        $scenario = ['name' => 'SOFTWARE_TEST_ONLY', 'policy_version' => ResearchOutcomePolicyService::VERSION, 'approved' => true,
            'frozen' => true, 'approval_reference' => $this->policy['approval_reference'],
            'profiles' => ['overlapping' => ['completion_probability' => 0.65, 'performance_mean' => 60, 'performance_sd' => 25]],
            'opportunities' => [['type' => 'quiz', 'required' => true, 'eligible' => true], ['type' => 'task', 'required' => true, 'eligible' => true]]];
        $generator = app(SyntheticResearchOutcomeService::class);
        $a = $generator->generate($features, str_repeat('b', 64), $scenario, 1234, $this->policy);
        $this->assertSame($a, $generator->generate($features, str_repeat('b', 64), $scenario, 1234, $this->policy));
        $this->assertNotSame($a, $generator->generate($features, str_repeat('b', 64), $scenario, 1235, $this->policy));
        $this->assertSame('synthetic', $a['outcome_origin']);
        $this->assertSame(0, $queryCount);
        $this->assertStringNotContainsString('academic_risk_status', json_encode($a));
        $this->assertStringNotContainsString('final_outcome', json_encode($a));
        $contexts = array_map(fn ($f) => array_intersect_key($f, array_flip(array_keys($this->features[0]))), $features);
        $report = app(ResearchOutcomePolicyService::class)->inspect($contexts, $a, $this->policy);
        $this->assertSame(30, $report['synthetic_feature_rows']);
        $this->assertSame(0, $report['real_feature_rows']);
        $this->assertFalse($report['labels_exported']);
        $scenario['approved'] = false;
        $this->expectException(InvalidArgumentException::class);
        $generator->generate($features, str_repeat('b', 64), $scenario, 1234, $this->policy);
    }

    public function test_readiness_command_is_file_only_and_prints_no_individual_keys(): void
    {
        $featurePath = tempnam(sys_get_temp_dir(), 'research-policy-feature-');
        $evidencePath = tempnam(sys_get_temp_dir(), 'research-policy-evidence-');
        try {
            $feature = $this->features[0];
            foreach (app(\App\Services\RiskFeatureSchema::class)->core() as $field) {
                $feature[$field] = '';
            }
            $stream = fopen($featurePath, 'w');
            fputcsv($stream, array_keys($feature), escape: '');
            fputcsv($stream, array_values($feature), escape: '');
            fclose($stream);
            $this->bundle['feature_sha256'] = hash_file('sha256', $featurePath);
            file_put_contents($evidencePath, json_encode($this->bundle));
            config(['research_outcomes' => $this->policy]);
            $report = app(ResearchOutcomeFileService::class)->inspect($featurePath, $evidencePath);
            $this->assertStringNotContainsString(str_repeat('a', 64), json_encode($report));
            $this->artisan('risk:research-outcome-readiness', ['feature-csv' => $featurePath, 'evidence-json' => $evidencePath])->assertSuccessful();
            $this->bundle['feature_sha256'] = str_repeat('0', 64);
            file_put_contents($evidencePath, json_encode($this->bundle));
            $this->artisan('risk:research-outcome-readiness', ['feature-csv' => $featurePath, 'evidence-json' => $evidencePath])->assertFailed();
        } finally {
            unlink($featurePath);
            unlink($evidencePath);
        }
    }
}
