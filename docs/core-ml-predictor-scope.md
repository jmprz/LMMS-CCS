# Finalized predictor scope

The authoritative selection is config/risk_features.php, consumed by RiskFeatureSchema, StudentRiskFeatureService::modelInput(), both dataset exporters, and the readiness reporter. Formulas and feature version remain v5. This is a predictor-only export mode, not a declaration that the dataset is ready to train; targets and adequate measurement coverage are still pending.

## Predictors

| Group | Selected fields |
| --- | --- |
| Academic Performance | quiz_avg, quiz_attempt_count, task_avg |
| Academic Behavior | submission_rate, late_submission_count |
| Learning Engagement | materials_accessed, material_session_count, reading_total_seconds, reading_avg_seconds |
| System Engagement | activity_count, activity_duration_seconds |
| Topic Performance (structured, encoding pending) | topic_quiz_performance_json, topic_task_performance_json |
| Optional Attendance | attendance_rate |

eligible_session_count is supporting metadata, never automatically a model input. NULL attendance remains NULL; no zero imputation is introduced. Topic JSON objects retain the existing detailed scores/counts; an empty object means no scored topic evidence, not zero achievement. Scalar topic encoding remains undecided.

## Exclusions

current_academic_grade: "Excluded from core ML features because complete academic grades are not fully observable within LMMS."

violation_count: "Excluded from core ML features because violation monitoring is not consistently observable in the primarily online/asynchronous research deployment."

Their existing application, snapshot and diagnostic support is preserved. StudentRiskFeatureService::diagnosticInput() retains the legacy projection for diagnostic exports. The finalized modelInput() excludes both fields, scalar topic placeholders and supporting counts.

Final academic outcome is reserved for a later supervised ground-truth source, not an early predictor. No outcome, label, threshold, grade-entry module or model training is introduced.

## Exports and grouping

```shell
php artisan risk:export-all-classes --cutoff="2026-10-03 14:32:54" --ml-ready
php artisan risk:export-class 1 --cutoff="2026-10-03 14:32:54" --ml-ready
php artisan risk:dataset-report storage/app/risk-datasets/all-classes-20261003-143254-v5-ml-ready.csv --output=core-readiness.csv
```

Omitting --ml-ready preserves the diagnostic CSV schema, including its raw user_id and excluded diagnostic variables. ML-ready filenames receive a -ml-ready suffix. The finalized mode explicitly selects columns through the schema allowlist; it contains no user_id, student name, email, school/student number, final outcome or risk label. Class descriptors are class-level metadata, not student profile fields.

student_group_key is a deterministic SHA-256 HMAC of a domain-separated user ID, keyed by RISK_DATASET_GROUPING_SECRET when configured, otherwise the existing application key. The grouping key stays the same across classes and exports while that secret stays unchanged. Keep the secret stable across datasets intended for joint splitting. The key is pseudonymous, not proof that a dataset is fully anonymous. It is grouping metadata, not a predictor; later splitting must use it to keep the same student out of both train and test sets. No raw individual identifiers or grouping keys appear in readiness output.

The exact combined ML-ready CSV column order is:

```text
student_group_key,lab_session_id,class_code,subject_name,program,year_level,section,
feature_cutoff_at,feature_version,eligible_session_count,assigned_task_count,
tagged_quiz_count,untagged_quiz_count,tagged_task_count,untagged_task_count,
quiz_avg,quiz_attempt_count,task_avg,submission_rate,late_submission_count,
materials_accessed,material_session_count,reading_total_seconds,reading_avg_seconds,
activity_count,activity_duration_seconds,attendance_rate,
topic_quiz_performance_json,topic_task_performance_json
```

Single-class ML-ready exports omit the five class descriptor columns. assigned_task_count and topic tagging counts describe collection opportunities/content and remain metadata. These fields are excluded from modelInput() along with eligible_session_count.

Readiness reports accept archived numeric user IDs or finalized 64-character grouping keys, print only aggregates, organize retained features into the six research groups, and show the two excluded variables separately with their methodological notes. Supporting metadata is labeled separately. Completeness CSVs include a group column; topic CSVs report scored student-class coverage rather than treating an empty JSON object as observed topic performance. Existing archived scalar topic columns remain readable for diagnostics.

## Current production-derived completeness

Shared cutoff: **2026-10-03 14:32:54 Asia/Manila**. Final export: all-classes-20261003-143254-v5-ml-ready.csv. 329 student-class rows, 323 unique students, six classes, zero duplicate pairs.

| Core predictor | Non-missing / rows | Completeness |
| --- | ---: | ---: |
| quiz_avg | 158 / 329 | 48.02% |
| quiz_attempt_count | 329 / 329 | 100% |
| task_avg | 172 / 329 | 52.28% |
| submission_rate | 213 / 329 | 64.74% |
| late_submission_count | 329 / 329 | 100% |
| materials_accessed | 329 / 329 | 100% |
| material_session_count | 329 / 329 | 100% |
| reading_total_seconds | 329 / 329 | 100% |
| reading_avg_seconds | 86 / 329 | 26.14% |
| activity_count | 329 / 329 | 100% |
| activity_duration_seconds | 0 / 329 | 0% |

Optional attendance: 0 numeric / 329 rows, all NULL; eligible_session_count is zero. Topic JSON fields are available in every row, but scored quiz-topic and task-topic coverage are each 0 / 329 (0%). At this cutoff the selected classes contain five untagged quizzes and four untagged tasks, zero tagged items. Verified activity duration and attendance coverage are 0% in every class. This reports availability, not ML accuracy or model quality. The existing total/count formulas can return zero without measurements; 100% non-missing therefore does not establish trustworthy engagement-duration coverage. Historical material semantics remain as previously audited and were not changed by this selection task.

Final readiness files: dataset-readiness-core-20261003-143254.csv, matching -classes.csv and -topics.csv; methodological exclusions are printed in core-readiness-20261003-143254.txt. core-scope-validation-20261003-143254.json contains aggregates only.

Validation: 70 relevant Laravel tests passed / 726 assertions, eight JavaScript tests passed. Full suite: 75 passed, 20 existing authentication/profile failures / 732 assertions. Selection tests check the exact 11 core fields, exclusions, target/PII absence, retained values, grouping across classes, nullable attendance, supporting metadata and both command flags. Attendance, topic, material and activity regressions pass.
