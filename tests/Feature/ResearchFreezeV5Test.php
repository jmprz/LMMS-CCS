<?php

namespace Tests\Feature;

use App\Services\RiskFeatureSchema;
use App\Services\RiskTargetService;
use App\Services\StudentRiskFeatureService;
use Tests\TestCase;

class ResearchFreezeV5Test extends TestCase
{
    public function test_v5_and_finalized_predictor_scope_remain_frozen(): void
    {
        $this->assertSame('v5', StudentRiskFeatureService::FEATURE_VERSION,
            'Review docs/research-freeze-v5.md before changing the live research version.');
        $this->assertSame([
            'quiz_avg', 'quiz_attempt_count', 'task_avg',
            'submission_rate', 'late_submission_count',
            'materials_accessed', 'material_session_count', 'reading_total_seconds', 'reading_avg_seconds',
            'activity_count', 'activity_duration_seconds',
        ], app(RiskFeatureSchema::class)->core(), 'The finalized v5 core predictor scope is frozen.');
        $this->assertSame(['attendance_rate'], app(RiskFeatureSchema::class)->optional());
        $this->assertSame(['topic_quiz_performance_json', 'topic_task_performance_json'], app(RiskFeatureSchema::class)->structured());
    }

    public function test_excluded_predictors_targets_ground_truth_and_grouping_stay_out_of_x(): void
    {
        $categories = app(RiskTargetService::class)->categories();
        $x = [...$categories['model_x_core'], ...$categories['model_x_optional'], ...$categories['structured_topic_encoding_pending']];
        $this->assertSame(['current_academic_grade', 'violation_count'], $categories['excluded_predictors']);
        $this->assertSame(['final_outcome'], $categories['ground_truth_source']);
        $this->assertSame(['academic_risk_status'], $categories['target_y']);
        foreach (['current_academic_grade', 'violation_count', 'final_outcome', 'academic_risk_status', 'student_group_key'] as $nonPredictor) {
            $this->assertNotContains($nonPredictor, $x);
        }
        $this->assertContains('student_group_key', $categories['grouping_metadata']);
        $this->assertSame([], array_values(array_intersect($x, $categories['grouping_metadata'])));
        $columns = app(RiskFeatureSchema::class)->columns(true, true);
        foreach (['current_academic_grade', 'violation_count', 'final_outcome', 'academic_risk_status', 'user_id', 'email', 'school_id', 'name'] as $excludedColumn) {
            $this->assertNotContains($excludedColumn, $columns);
        }
    }
}
