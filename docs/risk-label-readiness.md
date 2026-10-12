# Label readiness and official-outcome join

X contains earlier LMMS-observable predictors. Y, academic_risk_status, comes only from later official final academic outcomes evaluated against an institution-confirmed passing criterion. Quiz, task, attendance and engagement predictors never participate in target generation. Feature formulas/version remain v5. No model, imputation, scaling, grouped split or synthetic outcome is introduced.

## Central target configuration

config/risk_target.php defines target_name academic_risk_status, positive_class at_risk (At-Risk), negative_class not_at_risk (Not At-Risk), and ground_truth_column final_outcome. passing_grade reads RISK_PASSING_GRADE and defaults to NULL. No institutional passing number is supplied by this implementation.

RISK_PASSING_OPERATOR defaults to gte to express the research team's requested rule: outcomes below the configured criterion are at_risk; outcomes meeting/exceeding it are not_at_risk. lte is supported if the institution confirms an inverse numeric grading scale. The team must confirm the numeric scale, direction, and passing value against official institutional records before enabling generation. A missing or invalid criterion blocks labeled export before any file is created. Readiness still reports join coverage when no criterion is configured. Test thresholds and test outcomes exist only in isolated SQLite fixtures.

The small_count_warning value 5 is an aggregate inspection warning, not an academic threshold or eligibility rule. No historical status is written to a database; labels are calculated only for the research export (and aggregate distributions when configured).

## Commands

```shell
php artisan risk:label-readiness
php artisan risk:label-readiness storage/app/risk-datasets/all-classes-20261003-143254-v5-ml-ready.csv
php artisan risk:export-labeled-dataset storage/app/risk-datasets/all-classes-20261003-143254-v5-ml-ready.csv --output=labeled-risk-dataset.csv
```

Without a path, readiness selects the most recently modified *-ml-ready.csv under storage/app/risk-datasets (ties follow descending filename order). An explicit file is preferable for reproducible research. The safe local CSV reader validates grouping identifiers, row widths, recognized numeric values and topic JSON. The join additionally requires core fields, cutoff/version metadata, and only the centralized ML-ready column allowlist. Direct identifiers, arbitrary extra fields, excluded variables, existing outcomes and labels are rejected. Missing numeric predictor cells stay unchanged. Invalid numeric cells are reported and block labeled export.

Readiness prints aggregates only: feature rows, distinct students/classes, observed versions/cutoffs, imported official outcomes, revision count, unique outcome students/classes, matched/unmatched sides, duplicate keys, ambiguous matches, class coverage and recording-time anomalies. Configured criteria additionally enable counts/percentages of at_risk/not_at_risk among the official outcome population, including outcomes without matching feature rows. This is not a claim about the balance of a future training split. Small target counts, one-class outcomes, no-outcome classes, duplicates and suspicious timestamps are flagged without exposing identifiers or grouping keys.

## Official revision selection and timing

OfficialOutcomeResearchService centralizes selection for risk:export-outcomes and the join: latest imported, non-NULL-grade revision per final-period subject grade. Legacy-only snapshots without an import link are excluded. Corrected imports select the new revision while preserving prior history. Duplicate final-period student/class records are counted separately, including ambiguous legacy records; duplicate imported outcome keys cannot be silently selected. The join uses the same HMAC student_group_key as predictor exports plus lab_session_id. Keep the grouping secret stable across exports.

Timing checks require feature_cutoff_at strictly before the selected revision's recorded_at. Missing/invalid recording timestamps, earlier recording, equal timestamps and future recording timestamps are aggregate anomalies. Labeled export blocks such matches rather than silently creating potentially leaking examples. No grade is discarded or changed by reporting. recorded_at measures database import recording time, not the academic event date: a later import alone does not establish that the academic outcome itself occurred after observation. Research operators must verify the observation period and official provenance independently. No academic event date is invented.

Labeled export also blocks duplicate feature/outcome keys. Only matched rows are included; unmatched features/outcomes remain in their original sources. Empty matches can produce a header-only export once a criterion is explicitly configured, without fabricating data. Existing output files are never overwritten.

## Exact labeled schema and leakage categories

The labeled CSV retains the input ML-ready header order, then appends final_outcome and academic_risk_status. For the current combined export, the exact order is:

```text
student_group_key,lab_session_id,class_code,subject_name,program,year_level,section,
feature_cutoff_at,feature_version,eligible_session_count,assigned_task_count,
tagged_quiz_count,untagged_quiz_count,tagged_task_count,untagged_task_count,
quiz_avg,quiz_attempt_count,task_avg,submission_rate,late_submission_count,
materials_accessed,material_session_count,reading_total_seconds,reading_avg_seconds,
activity_count,activity_duration_seconds,attendance_rate,
topic_quiz_performance_json,topic_task_performance_json,final_outcome,academic_risk_status
```

Single-class inputs omit the five class-descriptor columns. Optional/supporting columns absent from an otherwise valid ML-ready input are not fabricated. A matching .schema.json companion file specifies the exact exported header, these categories, criterion/direction, target vocabulary, feature cutoffs/versions, selected outcome revision IDs, input SHA-256, no imputation and pending topic encoding:

- model_x_core: the eleven finalized core predictors from risk_features.php.
- model_x_optional: attendance_rate; NULL stays NULL.
- structured_topic_encoding_pending: topic_quiz_performance_json and topic_task_performance_json; no scalar encoding is chosen.
- target_y: academic_risk_status only.
- ground_truth_source: final_outcome only; never X.
- grouping_metadata: student_group_key, class ID/descriptors, cutoff/version, eligibility/assignment/tagging support counts; never automatically X.
- excluded_predictors: current_academic_grade and violation_count.

The metadata category is an allowlist; only columns actually present in the manifest's columns list were exported. The sidecar retains revision provenance without exporting raw student IDs or institutional identifiers. Future Python code must explicitly use X categories, handle topic encoding separately, and keep Y/ground truth/grouping metadata out of model inputs. Later imputation must be fit within the training partition; Laravel performs none. Normal --ml-ready predictor exports remain unlabeled and contain neither final_outcome nor academic_risk_status.

## Live readiness and validation

Selected real feature file: all-classes-20261003-143254-v5-ml-ready.csv, feature cutoff 2026-10-03 14:32:54 Asia/Manila, version v5. Report artifacts: label-readiness-20261003-150705.txt and matching .json, under storage/app/risk-datasets.

329 feature rows, 323 unique students, six classes. Official outcomes/revisions/matched rows: zero. Feature rows without outcomes: 329. Outcomes without features: zero. Duplicate feature pairs, imported outcome pairs and final-record pairs: zero. Invalid numeric feature cells: zero. All six classes have 0% outcome match coverage (class sizes 58, 36, 51, 63, 58, 63). Timing anomaly counts are zero because there are no matches to assess, not because temporal validity has been proven.

The live passing criterion remains NULL. No target distribution is computed. An actual unconfigured labeled-export smoke call returned failure with a clear no-threshold-assumed message and created no file. No official outcomes or sample grades were inserted into the production-derived database.

83 relevant Laravel tests passed / 859 assertions; eight JavaScript tests passed. Full suite has 88 passes and the same 20 existing authentication/profile failures (865 assertions). Tests cover both unmatched sides, key duplicates, duplicate final records, latest revision selection, temporal anomalies, absent/invalid passing criterion, configured threshold equality, inverse direction, PII-column rejection, grouping/target/ground-truth categories, preserved empty cells, companion schema creation and existing outcome/feature/attendance/topic/material/activity behavior.
