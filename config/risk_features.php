<?php

// Research freeze: v5 is frozen for live study collection. Semantic changes require
// a new feature version; see docs/research-freeze-v5.md before editing this scope.
return [
    'core_groups' => [
        'Academic Performance' => ['quiz_avg', 'quiz_attempt_count', 'task_avg'],
        'Academic Behavior' => ['submission_rate', 'late_submission_count'],
        'Learning Engagement' => ['materials_accessed', 'material_session_count', 'reading_total_seconds', 'reading_avg_seconds'],
        'System Engagement' => ['activity_count', 'activity_duration_seconds'],
    ],
    'optional_groups' => ['Optional Attendance' => ['attendance_rate']],
    'structured_groups' => ['Topic Performance' => ['topic_quiz_performance_json', 'topic_task_performance_json']],
    'excluded' => [
        'current_academic_grade' => 'Excluded from core ML features because complete academic grades are not fully observable within LMMS.',
        'violation_count' => 'Excluded from core ML features because violation monitoring is not consistently observable in the primarily online/asynchronous research deployment.',
    ],
    // Descriptive/supporting columns, never automatically selected as model inputs.
    'metadata' => ['student_group_key', 'lab_session_id', 'feature_cutoff_at', 'feature_version',
        'eligible_session_count', 'assigned_task_count', 'tagged_quiz_count', 'untagged_quiz_count', 'tagged_task_count', 'untagged_task_count'],
    'class_metadata' => ['class_code', 'subject_name', 'program', 'year_level', 'section'],
    // Stable secret across exports; falls back to the existing application key.
    'grouping_secret' => env('RISK_DATASET_GROUPING_SECRET'),
    'aliases' => [
        'quiz_avg' => ['quiz_avg', 'quiz_average'], 'quiz_attempt_count' => ['quiz_attempt_count'],
        'task_avg' => ['task_avg', 'task_average'], 'submission_rate' => ['submission_rate'],
        'late_submission_count' => ['late_submission_count'], 'materials_accessed' => ['materials_accessed'],
        'material_session_count' => ['material_session_count'],
        'reading_total_seconds' => ['reading_total_seconds', 'total_material_engagement_time'],
        'reading_avg_seconds' => ['reading_avg_seconds', 'average_material_engagement_time'],
        'activity_count' => ['activity_count', 'system_activity_count'],
        'activity_duration_seconds' => ['activity_duration_seconds', 'system_activity_duration'],
        'attendance_rate' => ['attendance_rate'], 'eligible_session_count' => ['eligible_session_count'],
        'assigned_task_count' => ['assigned_task_count'],
        // Compatibility for archived diagnostics only; no topic scalar encoding selected.
        'topic_quiz_average' => ['topic_quiz_average'], 'topic_task_average' => ['topic_task_average'],
    ],
    'diagnostic_columns' => ['user_id', 'lab_session_id', 'feature_cutoff_at', 'feature_version',
        'current_academic_grade', 'quiz_avg', 'quiz_attempt_count', 'task_avg', 'submission_rate', 'late_submission_count',
        'materials_accessed', 'material_session_count', 'reading_total_seconds', 'reading_avg_seconds',
        'activity_count', 'activity_duration_seconds', 'violation_count', 'attendance_rate', 'eligible_session_count',
        'assigned_task_count', 'topic_task_average', 'topic_quiz_performance_json', 'topic_task_performance_json',
        'tagged_quiz_count', 'untagged_quiz_count', 'tagged_task_count', 'untagged_task_count'],
];
