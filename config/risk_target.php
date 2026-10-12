<?php

// Research freeze: v5 is frozen for live study collection. Semantic changes require
// a new feature version; see docs/research-freeze-v5.md before editing target rules.
return [
    'target_name' => 'academic_risk_status',
    'positive_class' => 'at_risk',
    'negative_class' => 'not_at_risk',
    'ground_truth_column' => 'final_outcome',
    'passing_grade' => env('RISK_PASSING_GRADE'),
    // gte follows the requested below-threshold rule; lte supports an officially confirmed inverse scale.
    'passing_operator' => env('RISK_PASSING_OPERATOR', 'gte'),
    'semantics' => 'Later official academic outcome evaluated against the confirmed institutional passing criterion. No LMS predictor participates in target generation.',
    'small_count_warning' => 5, // Inspection warning only, never a passing criterion.
];
