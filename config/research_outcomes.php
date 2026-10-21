<?php

// Separate target-policy version; never reinterprets frozen v5 or official grades.
return [
    'policy_version' => 'research-later-academic-v1',
    'enabled' => env('RESEARCH_OUTCOME_ENABLED', false),
    'approval_reference' => env('RESEARCH_OUTCOME_APPROVAL_REFERENCE'),
    'performance_threshold' => env('RESEARCH_OUTCOME_THRESHOLD'),
    'comparison_direction' => env('RESEARCH_OUTCOME_DIRECTION'),
    'minimum_eligible_opportunities' => env('RESEARCH_OUTCOME_MIN_OPPORTUNITIES'),
    'no_participation_rule' => env('RESEARCH_OUTCOME_NO_PARTICIPATION'), // at_risk or exclude
    'aggregation' => env('RESEARCH_OUTCOME_AGGREGATION'), // equal_opportunity_percentage
    'outcome_start' => env('RESEARCH_OUTCOME_START'),
    'outcome_end' => env('RESEARCH_OUTCOME_END'),
    'assessment_types' => array_values(array_filter(array_map('trim', explode(',', (string) env('RESEARCH_OUTCOME_TYPES', ''))))),
];
