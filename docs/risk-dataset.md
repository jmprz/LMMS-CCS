# Risk predictor dataset (v2)

Export a class using an explicit prediction point in `config('app.timezone')`:

```shell
php artisan risk:export-class 1 --cutoff="2026-09-30 17:00:00"
php artisan risk:export-class 1 --cutoff="2026-09-30 17:00:00" --save --output="storage/app/risk-datasets/class-1.csv"
php artisan risk:features 391 1 --cutoff="2026-09-30 17:00:00"
```

The exporter creates a new file and refuses to overwrite an existing one. Its default destination is `storage/app/risk-datasets`. Without `--save`, it only reads the database. With `--save`, it saves/reuses one snapshot per student, class, cutoff and feature version; existing snapshots are immutable. Database snapshot writes are transactional and failed command output is removed. Students are selected from the current membership table, with `role = student` and `joined_at <= cutoff`; duplicate memberships are collapsed. Students who have since been removed from the class cannot be recovered from the current schema.

No final outcome, risk status, label threshold, model training, or inference is implemented. These exports contain candidate predictors for later Python integration, not a labeled training dataset. IDs, cutoff, version, and denominator counts are audit metadata; exclude identifiers from ML predictors.

## Sources and interpretation

### Multiple classes in one CSV

```shell
php artisan risk:export-all-classes --cutoff="2026-10-01 23:59:59"
php artisan risk:export-all-classes --classes=1,2,3 --cutoff="2026-10-01 23:59:59"
php artisan risk:export-all-classes --classes=1,2 --cutoff="2026-10-01 23:59:59" --save --output="combined.csv"
```

The cutoff is required and validated exactly like `risk:export-class`. Without `--classes`, selection uses current `lab_sessions.is_active = true` with at least one student enrolled by cutoff. Explicit IDs may include inactive or empty classes; nonexistent IDs fail the export. Repeated IDs and duplicate memberships do not duplicate rows. A student in two selected classes has one row per class, with class-specific predictors from `StudentRiskFeatureService`.

All current v2 columns are retained, with a union of selected classes' topic columns. Each row also includes `class_code`, `subject_name`, `program`, `year_level`, and `section`, alongside `lab_session_id`. Missing class metadata and features are empty cells. Metadata is the current stored class metadata, not a historical reconstruction. The command prints each class's row count and the overall row count. No eligible classes produces a header-only CSV and a zero-row summary.

Output always goes under `storage/app/risk-datasets/`. `--output` accepts a filename only; existing files are not overwritten. Default filenames identify the selected IDs (or `all-classes`), cutoff and feature version. `--save` saves/reuses snapshots transactionally across all selected classes. The existing single-class command's arguments, column order and behavior are unchanged.

| Predictor | Source / definition |
| --- | --- |
| `current_academic_grade` | Latest recorded non-null `subject_grades.grade` for `midterm`, the interim period documented in the existing migration. `recorded_at`, `created_at`, and `updated_at` must all be at/before cutoff. Raw grade scale is preserved; no conversion or risk threshold is assumed. `final` is never read as a predictor. Missing/undated grades stay NULL. Other interim periods need an explicit future decision. |
| `quiz_avg`, `quiz_attempt_count` | Latest valid non-simulated attempt ID per class quiz at/before cutoff; score present and total points positive. Average of percentage scores, not a points-weighted average. Count represents quizzes with a selected valid attempt, preserving the existing count meaning. |
| `task_avg`, `submission_rate`, `late_submission_count` | Existing due-task and latest-submission formulas; upcoming tasks are excluded. Task average prefers available rubric scores, falling back to submission grade. Submission/late counts count each task once even with multiple rubric rows. Missing averages stay NULL. |
| `materials_accessed`, `material_session_count` | Distinct materials and sessions opened by cutoff in the class. |
| `reading_total_seconds`, `reading_avg_seconds` | Total/average available nonnegative recorded duration seconds. Sessions updated after cutoff contribute session counts but no duration. |
| `activity_count`, `activity_duration_seconds` | Class activity rows created by cutoff and available duration sum. Durations updated after cutoff are excluded. |
| `violation_count` | Timestamped class `activity_logs` of type `violation` or `alert`, as written by `ViolationEnforcementService`. Includes lock-triggering and already-blocked attempts, including non-URL violations. Do not additionally count `blocked_attempts`: URL violations also create those records. This is an event count, not the resettable enforcement counter on `class_student`. Legacy blocked-only rows cannot be reconciled automatically. |
| `attendance_rate` | Existing percentage of distinct recorded eligible class dates attended, with cutoff guards on recorded rows. |
| `topic_quiz_average` | Snapshot JSON map keyed by `quizzes.learning_topic_id`, using the same selected attempts as `quiz_avg`. Untagged quizzes affect the overall average but no topic average. |
| `topic_task_average` | NULL: live `tasks` has only class, title, description, deadline, points and timestamps. No task-topic column, pivot, or model relationship exists. Task title/description matching and rubric criteria are not a reliable topic assignment. Add a separately approved explicit topic relationship before computing this predictor. |

The additional predictors live in the existing `additional_features` JSON field. No database migration is required. Existing v1 snapshots remain unchanged; v2 model input includes the academic, behavioral and topic features. Missing extended fields in older snapshots stay NULL rather than becoming invented zero counts.

## CSV and Python

One student per row, with identical cutoff and column order for the class. Each quiz topic in the class at cutoff gets a numeric `topic_quiz_average__topic_<ID>` column. Missing topic performance and other NULLs are empty cells, which pandas reads as missing values. `topic_task_average` is an empty placeholder column. Snapshot JSON uses a topic-ID map; CSV never puts arrays into numeric predictor cells. Different classes can have different topic columns; align their column union by topic ID before combining datasets. Freeze the feature schema, handle missing values and grade scales explicitly, and fit preprocessing only on the training partition once the label rule is approved.

## Dataset readiness report

```shell
php artisan risk:dataset-report storage/app/risk-datasets/all-classes-20261001-235959-v2.csv
php artisan risk:dataset-report storage/app/risk-datasets/all-classes-20261001-235959-v2.csv --output=dataset-readiness.csv
```

The report reads a local regular CSV file without querying or writing production data. It prints student-class row count, distinct student/class counts, duplicate-pair count, feature completeness, numeric statistics, and per-class completeness. Individual student IDs, names, and free-text class metadata are never printed or exported. Student IDs are used internally for unique counts only. Later train/test splitting must group by student across classes.

Known predictor names and aliases are allowlisted; metadata and any outcome/target columns are ignored. Topic quiz columns are detected as `topic_quiz_average__topic_<ID>`. Engagement time aliases `total_material_engagement_time` and `average_material_engagement_time` map to the existing seconds features; alternate numeric names retain their actual CSV source-column names in the report. Unrecognized columns are ignored to avoid leaking arbitrary personal data. Missing expected features are flagged separately from columns present but 100% empty. `eligible_session_count` and `assigned_task_count` are included as supporting counts.

Empty/whitespace cells and case-insensitive `NULL`, `NA`, `N/A`, `NaN`, and `None` count as missing. Zero is present. Nonempty invalid numeric cells count as present for completeness but are flagged and excluded from numeric statistics. Minimum, maximum, mean and median use finite numeric values; standard deviation is the sample SD (`n - 1` denominator), unavailable for fewer than two valid values. Empty datasets have undefined percentages/statistics, displayed as N/A. Blank lines are ignored, malformed row widths and duplicate/empty headers are rejected, and duplicate data pairs are reported without silently removing rows. Exact medians retain numeric observations in memory, appropriate for current thesis datasets.

Class cell-completeness percentages average across the same detected features, including supporting counts, topic columns and unsupported placeholders. Inspect individual feature completeness rather than treating that percentage as a readiness score. Topic completeness uses all rows as its denominator; missing values may reflect a topic not applicable to a class. Constant populated features are highlighted. Complete counts/durations may still be zero, and this does not establish sufficient engagement or predictive value.

`--output` accepts a filename only and writes both `dataset-readiness.csv` and `dataset-readiness-classes.csv` under `storage/app/risk-datasets/`. The class file contains one row per class/feature with numeric statistics and counts. Existing files are never overwritten. Outputs contain aggregates only. Reporting cannot verify simulated-attempt provenance from an aggregate CSV; the existing feature exporter continues to exclude simulated attempts. No labels, final outcome rules, training or accuracy claims are added.

## Source-data audit

```shell
php artisan risk:data-audit
php artisan risk:data-audit --classes=1,2,3 --cutoff="2026-10-01 23:59:59"
php artisan risk:data-audit --cutoff="2026-10-01 23:59:59" --output=source-data-audit.csv
```

The audit reads the configured database in one transaction, without modifying feature formulas or records. Default selection matches the multi-class exporter: active classes with students enrolled by cutoff. Explicit IDs include inactive classes. An omitted cutoff captures application-timezone `now()` once; an explicit cutoff uses the exporter's strict format and rejects invalid/future dates. Supply the dataset's cutoff for comparisons.

Each class reports metadata, distinct cutoff-enrolled students, real quiz attempts and usable-score coverage, due-task/submission/rubric/fallback coverage, attendance, material sessions, duration distributions, violations, actual grade periods, and quiz topics. Sources are restricted to the cutoff enrollment population via matching student/class pairs; duplicate memberships do not duplicate source rows. Overall student counts are distinct across classes; student-class pairs are separate. Overall medians pool source values, rather than averaging class medians.

`*_total_current` inventories include current quizzes/tasks/materials; cutoff inventories require creation by cutoff. Real attempts require `is_simulated = 0` and attempt creation by cutoff. Usable percentages also require quiz creation, attempt update by cutoff, non-null score and positive points, matching v2 coverage. Task submissions use `submitted_at`, falling back to `created_at`; eligible rows belong to due tasks created by cutoff. Rubric/fallback/ungraded classifications count every eligible submission once. Latest eligible coverage separately chooses maximum submission ID per student/task, matching v2. Rubric records include current records, while usable scores apply grade timestamps and positive denominators. No grade values are printed.

Attendance statistics describe enrolled-student records dated/created by cutoff; v2's eligible-date denominator can also include other class attendance such as professor records. Durations are seconds, with NULL/negative counts. Raw logs opened/created by cutoff are distinguished from durations passing v2's `updated_at` guard. Student-class activity sums include enrolled pairs without logs as zero. Activity type breakdowns flag >8-hour and >24-hour rows, missing end timestamps, and positive durations whose update timestamp still equals creation. These thresholds identify records for inspection and do not cap/delete anything.

Violation activity events use `violation`/`alert`; blocked attempts are separately counted, not added to the feature. Pivot counters are explicitly current, resettable values with no historical event reconstruction. The zero-variance check uses actual per-student/class activity-event counts including zero-event pairs. Grade-period counts inspect actual stored values without assuming all are `midterm`; existing v2 eligibility remains non-null `midterm` with recording/creation/update times by cutoff. Records without recording dates are separate from before/after counts. Topics use the verified quiz foreign key and aggregate topic names, not task-title inference. Task topic support is unavailable in the inspected schema.

Optional output is an exclusively created aggregate CSV under `storage/app/risk-datasets/`, containing one metric row per class plus overall metrics. Existing files are not overwritten; free-text class metadata is protected against spreadsheet formula interpretation. Console output additionally provides categorical distributions. No student names, IDs, emails, school IDs, log content, URLs, labels, or model results are emitted.

## Historical-data limits

The audit also compares recorded submission durations with elapsed seconds from task creation to submission (within one second). The current submission controller writes this task-age measure into both submission records and `submission` activity logs. This can generate multi-day activity totals without evidence of multi-day active work. The comparison supplies aggregate evidence without changing the metric.

The schema stores mutable current values, not revision histories. Timestamp guards exclude grades, attempts, attendance status and durations updated after cutoff because their old values cannot be recovered. This can introduce missing historical values. Existing quiz-topic assignments, task points/deadlines, membership removals, and log content have no historical revision data; backdated exports cannot guarantee perfect reconstruction of those values. Prefer saving snapshots at the actual prediction point. The exporter uses one database transaction for coherent reads and snapshot writes, but snapshots cannot recover information already overwritten. It does not estimate elapsed durations for incomplete sessions or infer missing grades as zero.
