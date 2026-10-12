<?php

namespace App\Services;

use RuntimeException;

class RiskFeatureSchema
{
    public function core(): array
    {
        return array_merge(...array_values(config('risk_features.core_groups')));
    }

    public function optional(): array
    {
        return array_merge(...array_values(config('risk_features.optional_groups')));
    }

    public function structured(): array
    {
        return array_merge(...array_values(config('risk_features.structured_groups')));
    }

    public function groups(): array
    {
        return [...config('risk_features.core_groups'), ...config('risk_features.optional_groups'), ...config('risk_features.structured_groups')];
    }

    public function group(string $feature): string
    {
        foreach ($this->groups() as $group => $features) {
            if (in_array($feature, $features, true)) {
                return $group;
            }
        }

        return in_array($feature, ['topic_quiz_average', 'topic_task_average'], true) ? 'Topic Performance' : 'Supporting Metadata';
    }

    public function columns(bool $mlReady = false, bool $classMetadata = false): array
    {
        $columns = $mlReady ? [...config('risk_features.metadata'), ...$this->core(), ...$this->optional(), ...$this->structured()]
            : config('risk_features.diagnostic_columns');
        if ($classMetadata) {
            array_splice($columns, 2, 0, config('risk_features.class_metadata'));
        }

        return $columns;
    }

    public function groupingKey(int $userId): string
    {
        $secret = config('risk_features.grouping_secret') ?: config('app.key');
        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException('ML-ready export requires a stable grouping secret or application key.');
        }

        return hash_hmac('sha256', 'lmms-risk-student:v1:'.$userId, $secret);
    }

    public function topicColumns(array $additional): array
    {
        $columns = [];
        foreach ($this->structured() as $column) {
            $source = substr($column, 0, -5);
            $columns[$column] = json_encode((object) ($additional[$source] ?? []), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }

        return $columns;
    }
}
