<?php

namespace App\Services;

use InvalidArgumentException;

class RiskTargetService
{
    public function criterion(): ?array
    {
        $grade = config('risk_target.passing_grade');
        if ($grade === null || $grade === '') {
            return null;
        }
        if (! is_numeric($grade) || ! is_finite((float) $grade) || ! in_array(config('risk_target.passing_operator'), ['gte', 'lte'], true)) {
            throw new InvalidArgumentException('The configured institutional passing criterion is invalid.');
        }

        return ['passing_grade' => (float) $grade, 'passing_operator' => config('risk_target.passing_operator')];
    }

    public function requireCriterion(): array
    {
        return $this->criterion() ?? throw new InvalidArgumentException('Academic Risk Status cannot be generated: configure the official RISK_PASSING_GRADE in risk_target first. No passing threshold is assumed.');
    }

    public function label($outcome): string
    {
        $criterion = $this->requireCriterion();
        if (! is_numeric($outcome) || ! is_finite((float) $outcome)) {
            throw new InvalidArgumentException('Official outcome must be a finite numeric value.');
        }
        $passes = $criterion['passing_operator'] === 'gte' ? (float) $outcome >= $criterion['passing_grade'] : (float) $outcome <= $criterion['passing_grade'];

        return $passes ? config('risk_target.negative_class') : config('risk_target.positive_class');
    }

    public function categories(): array
    {
        $schema = app(RiskFeatureSchema::class);

        return ['model_x_core' => $schema->core(), 'model_x_optional' => $schema->optional(),
            'structured_topic_encoding_pending' => $schema->structured(), 'target_y' => [config('risk_target.target_name')],
            'ground_truth_source' => [config('risk_target.ground_truth_column')],
            'grouping_metadata' => [...config('risk_features.metadata'), ...config('risk_features.class_metadata')],
            'excluded_predictors' => array_keys(config('risk_features.excluded'))];
    }
}
