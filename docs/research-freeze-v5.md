# LMMS-CCS research freeze: v5

**v5 is frozen for the active live study data-collection period.** Before editing research-critical code, review this document. Changes to retained formulas, predictor scope, feature meaning, collection/eligibility rules or target semantics require an explicitly reviewed **new feature version**. Do not quietly reinterpret existing v5 rows, relax this guard to make a semantic change pass, or mix incompatible measurements under v5.

## Official baseline

- Snapshot: `baseline-v5-20261003-155419`.
- Created/shared cutoff: **2026-10-03 15:54:19 UTC+08:00**, recorded by the application as `Asia/Manila` (the same local time in the client's `Asia/Shanghai` timezone).
- Directory: `storage/app/risk-datasets/research-snapshots/baseline-v5-20261003-155419/`.
- Files: `ml-ready.csv`, `collection-status.csv`, `collection-status.json`, `label-readiness.txt`, `metadata.json`.
- Baseline scope: **329 student-class rows, 323 unique students, six classes**, feature version **v5**.
- Baseline target readiness: **zero official outcomes; passing criterion not configured**. These are baseline facts, not assertions that future live collection must remain empty.

Old snapshots must **never be regenerated, overwritten or rewritten**, including their metadata and checksums. Capture later observations in new, uniquely named snapshot directories with a single shared cutoff. Verify existing artifact hashes against their original metadata. The stored research-code hash represents code when that package was created; comments/documentation added later can change source hashes without changing executable behavior. Never update old metadata to match later code. See [snapshot lifecycle](research-snapshots.md).

## Finalized predictor scope

The authoritative scope is `config/risk_features.php`, projected through `RiskFeatureSchema` and `StudentRiskFeatureService::modelInput()`.

| Category | Retained fields |
| --- | --- |
| Core: Academic Performance | `quiz_avg`, `quiz_attempt_count`, `task_avg` |
| Core: Academic Behavior | `submission_rate`, `late_submission_count` |
| Core: Learning Engagement | `materials_accessed`, `material_session_count`, `reading_total_seconds`, `reading_avg_seconds` |
| Core: System Engagement | `activity_count`, `activity_duration_seconds` |
| Optional Attendance | `attendance_rate` |
| Structured Topic Performance; scalar/model encoding pending | `topic_quiz_performance_json`, `topic_task_performance_json` |

The eleven core scalar predictors must not gain or lose fields during v5 collection. Topic JSON retains existing scored-topic evidence and counts. `{}` means no scored topic evidence, not zero performance. Do not introduce a new scalar topic encoding during this freeze. Keep cutoff guards, latest-valid-attempt/grade rules and simulated-quiz exclusion unchanged. Missing averages stay nullable; no new imputation is authorized. See [scope details](core-ml-predictor-scope.md) and [topic semantics](learning-topic-performance.md).

Excluded predictors:

- `current_academic_grade`: complete academic standing is not fully observable from LMMS quizzes/tasks. Do not derive it from incomplete internal assessments.
- `violation_count`: violation monitoring is not consistently observable in the mainly online/asynchronous research deployment.

Existing diagnostic/storage support for excluded fields does not make them X. Archived diagnostics are not the research-safe ML-ready scope.

## Optional attendance and engagement semantics

Attendance eligibility uses the **frozen student roster** of finalized verified attendance sessions for the selected class, with `finalized_at <= feature_cutoff_at`. Present and late count as attended; absent counts only in the denominator. Excused and anomalous pending entries are excluded; pending entries are logged as a data-quality condition, never silently made absent. `eligible_session_count` is that countable denominator and remains supporting metadata. The rate is the existing rounded percentage of attended/countable sessions; no countable sessions means `attendance_rate = NULL`, denominator zero. Active/cancelled sessions, legacy attendance rows and later class membership do not substitute for verified attendance evidence. See [attendance lifecycle](attendance-sessions.md).

Material opens and measured duration have different meanings. Existing duration predictors retain the frozen formula, which can include legacy values; numeric availability alone does not establish verified active reading time. Future verified collection is a bounded visible/recent-activity proxy, not proof of comprehension. External opens must not claim precise external viewing time. Preserve visibility/focus/idle handling, idempotent heartbeats and server elapsed-time bounds. Never reconstruct historical missing durations from `closed_at - opened_at`. See [material lifecycle and limitations](material-engagement.md).

System activity events remain distinct from verified generic UI-active duration. Preserve the existing collector's visibility/focus/idle rules, viewer exclusions, heartbeat bounds and cutoff checks. Do not substitute historical event durations or wall-clock session age for verified duration. See [activity lifecycle](activity-duration.md).

## Target architecture and privacy

`final_outcome` is the **official ground-truth source only**, never X. Official outcome imports/revisions are separate from early observation features. `academic_risk_status` is target Y only, also never X. `RiskTargetService` evaluates an official outcome against an explicitly configured institutional passing criterion (`RISK_PASSING_GRADE` and its configured direction). No LMS predictor supplies a label; no default passing threshold may be invented. The inspection warning for small counts is not a passing threshold.

Label export remains gated by official data, confirmed configuration, unambiguous joins and timing review. Do not generate labels without official outcomes and a confirmed criterion. Timestamp recording alone does not prove the academic event happened after observation; review official provenance. Research snapshots remain unlabeled even if outcomes/configuration become available. This freeze task authorizes no target changes, labels or model training. See [official outcomes](official-final-outcomes.md) and [label readiness](risk-label-readiness.md).

Research CSVs and printed reports must remain free of directly identifying student information: no names, emails, school/student numbers or raw user IDs. Use ML-ready exports for research packages. Existing diagnostic exports with raw IDs remain restricted diagnostics, not research-sharing artifacts.

`student_group_key` is **grouping metadata only**, never X. It is a stable pseudonymous HMAC identifier, not an anonymous feature. Keep its secret stable across research exports and protect the secret. The same student may appear once per class; any later train/test splitting must keep every row for that student in one partition. Class descriptors, cutoff/version, opportunity/tag counts and attendance denominator are supporting metadata, not automatically predictors. Retain grouping and X/Y/ground-truth separation explicitly when integrating with Python.

## Research-critical file inventory

Review direct edits and changes to their called code, schema or frontend inputs for effects on frozen semantics:

| Area | Critical files |
| --- | --- |
| Feature computation and scope | `app/Services/StudentRiskFeatureService.php`, `app/Services/RiskFeatureSchema.php`, `config/risk_features.php` |
| Target/ground truth and readiness | `config/risk_target.php`, `app/Services/RiskTargetService.php`, `app/Services/RiskLabelDatasetService.php`, `app/Services/OfficialOutcomeService.php`, `app/Services/OfficialOutcomeResearchService.php` |
| Exports | `app/Services/StudentRiskDatasetService.php`; `app/Console/Commands/ExportClassRiskDataset.php`, `ExportAllClassesRiskDataset.php`, `ExportOfficialOutcomes.php`, `ExportLabeledRiskDataset.php` |
| Reporting and inspection | `app/Services/RiskDatasetReportService.php`, `RiskCollectionStatusService.php`, `RiskSourceDataAuditService.php`, `RiskTopicCoverageService.php`; `app/Console/Commands/ReportRiskLabelReadiness.php`, `ReportRiskDataset.php`, `ReportRiskCollectionStatus.php`, `AuditRiskSourceData.php`, `InspectStudentRiskFeatures.php` |
| Snapshot packaging | `app/Services/ResearchSnapshotService.php`, `app/Console/Commands/CreateResearchSnapshot.php` |
| Verified collection | `app/Services/AttendanceSessionService.php`, `MaterialEngagementService.php`, `ActivityDurationService.php`; their models/controllers and `public/js/material-engagement.js`, `public/js/activity-engagement.js` |
| Topic/grade evidence | `app/Http/Controllers/QuizController.php`, `TaskController.php`, `LearningTopicController.php`; quiz/task/submission/topic models and their relationships |
| Collection callers and schemas | `resources/views/student/subject.blade.php`, relevant material/attendance/activity routes, enrollment/check-in/auth callers; migrations defining research source tables, rosters, duration sessions and outcome revisions |

Short freeze notices are intentionally limited to the main scope/config and collection services. Absence of a header elsewhere does not permit a semantic change.

## Regression safeguard and maintenance

`tests/Feature/ResearchFreezeV5Test.php` checks the literal v5 version, exact finalized core list, optional/structured scope, excluded predictors, target/ground-truth separation and grouping metadata. It reads configuration/services only and does not create outcomes, labels, feature snapshots or production records.

This is a review warning and test safeguard, not a filesystem lock or proof that arbitrary formula edits are equivalent. Existing risk and lifecycle tests still verify behavior. Maintenance that preserves semantics may proceed; semantic changes need a documented research decision, a new feature version, migration/compatibility plan and separate future datasets. Preserve old v5 interpretation and artifacts. Do not simply bump the version and rewrite historical rows.

After relevant maintenance run:

```sh
php artisan test
node --test tests/js/material-engagement.test.cjs tests/js/activity-engagement.test.cjs
```

Verify the baseline remains untouched. Do not regenerate it as a verification step.
