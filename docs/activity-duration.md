# System activity collection and risk features v5

`activity_count` retains its existing definition: number of recorded activity_logs events for a student/class, created at or before the shared cutoff. It is neither a session count nor a count of unique actions. Existing retries are separate events unless their originating workflow is already idempotent. No new generic-session heartbeat adds activity_logs events.

`activity_duration_seconds` is estimated active interaction time on the LMMS student classroom page, outside the material and quiz viewers. It sums only `activity_duration_sessions` with collection_version `generic_ui_v1`, nonnegative non-NULL duration, and opened_at, created_at and updated_at at/before cutoff. Material engagement remains its own predictor. Quiz attempt elapsed time is not generic engagement. Other LMS pages, embedded external browser activity and activity outside LMMS are not measured. This is an estimate with limited page coverage, not total student effort.

NULL means no reliable duration evidence exists, even when event count is positive. Zero means a verified heartbeat/finalization was received and reported no active seconds. Opening a session alone leaves duration NULL. Unclosed sessions retain only accepted heartbeats; their age is never substituted for engagement. A mutable session updated after cutoff is excluded entirely: historical partial totals cannot be reconstructed from the current total.

## Audited writers

| log_type | Writer | Duration semantics / v5 treatment |
| --- | --- | --- |
| submission | StudentClassController::submitTask | Formerly submission minus task creation (task age); future submission and event duration NULL. Countable event. |
| submission_removed | StudentClassController::deleteTask | Event marker, formerly zero; no measurable duration. |
| quiz | QuizController::submit | Formerly client-start-to-submit elapsed time, with no active-visibility clock; future log duration NULL. QuizAttempt time_spent and deadline behavior unchanged. |
| material | MaterialEngagementService::record | New visible_v1 close has bounded material seconds, historical logs have legacy semantics. Excluded from generic duration to avoid overlap. Opens remain measurable through material_logs. |
| navigation | StudentClassController::logBehavior; admin timeline normalization | Event, formerly the next behavior request could overwrite its duration with an unrelated event gap. This mutation has been removed; new behavior event duration NULL. |
| search | StudentClassController::customSearch / behavior client | Event only; no verified active clock. |
| violation / alert | ViolationEnforcementService / behavior client | Event only; enforcement is unchanged. |
| attendance | ClassroomController start/stop; AuthenticatedSessionController logout | Join/leave/time-in/time-out events, not active engagement evidence. |
| professor_activity | ClassroomController, TaskController, QuizController, MaterialController | Professor action events. |
| professor_session | ClassroomController::toggleSession | Professor wall-clock session, copied to stop event; not student active time. |
| screen_share | ClassroomController::broadcast | Professor wall-clock broadcast, not student active time. |

Behavior logging accepts a client type; unknown types remain recorded and countable under the unchanged count definition but never contribute generic duration. Professor-only types normally belong to professor users and therefore do not enter student/class features. `student_activities` has a model/migrations but no current collection writer or risk-service reader. Attendance/presence heartbeats demonstrate presence, not continuous active interaction. There is no safe historical generic activity duration source, including material timeline logs whose version is not recorded there. All activity_logs durations are therefore excluded from v5; old records remain available to the audit.

## New UI lifecycle

The student classroom page opens an authenticated, student-owned session with an idempotent UUID key. Server-side enrollment is checked at open. A user lock serializes starts and heartbeats; starting another classroom collector closes the previous collector without adding seconds. This deliberately undercounts a superseded tab until it opens a new session, preventing concurrent-tab double counting.

The client samples every second. It accumulates only while the document is visible, focused, has recent interaction within 60 seconds, and no material viewer, quiz viewer, or embedded browser panel is visible. Suspended timer gaps over 1.5 seconds are discarded. A 10-second heartbeat reports cumulative whole seconds and a monotonic sequence. Duplicate/out-of-order sequence values are ignored. The server accepts no more than the reported increase, elapsed server seconds, or 20 seconds per heartbeat. A decreasing cumulative value is rejected. These bounds validate plausibility, not proof of attention; client measurements remain estimates.

Visibility/blur transitions flush a heartbeat; pagehide uses CSRF-protected sendBeacon with keepalive-fetch fallback. Delivery is best effort. Normal pagehide finalizes without inventing a tail. BFCache pageshow starts a new session. Open retries reuse the same key; completed sessions cannot accrue further time. Sessions are not automatically finalized by wall-clock timeout. No beforeunload synchronous network dependency is introduced.

Event retry behavior is intentionally preserved: a submission retry can create another submission event, behavior requests are not deduplicated, quiz idempotency follows its existing workflow, and verified material close logs only once. Event counts must be interpreted as recorded events, not unique assessments or opens. Generic open/heartbeat retries do not inflate either duration or event count.

## Version and audit

Feature version v5 prevents reuse of incompatible v4 snapshots at the same prediction cutoff. Export columns are unchanged and NULL remains an empty CSV cell. `risk:data-audit` preserves legacy raw-duration statistics for comparison and adds explicit v5 exclusions, session and student coverage, class coverage, and valid session/student-class duration distributions. Per-type raw row counts, duration counts and exclusion counts are exported as aggregate metrics. No identities are printed, no historical durations are corrected, and no targets/models are generated.

## Production-derived validation, 2026-10-03

Shared cutoff: **2026-10-03 14:10:52 Asia/Manila**. Dataset: 329 student-class rows, 323 distinct students, six classes, zero duplicate pairs.

| Student event type | Logs | Non-NULL legacy duration | Excluded from v5 duration |
| --- | ---: | ---: | ---: |
| submission | 221 | 221 | 221 |
| quiz | 159 | 159 | 159 |
| material | 150 | 150 | 150 |
| submission_removed | 20 | 20 | 20 |
| navigation | 4 | 4 | 4 |
| search | 2 | 2 | 2 |
| violation | 2 | 2 | 2 |
| alert | 1 | 1 | 1 |
| Total | 559 | 559 | 559 |

103 enrolled-student submission logs exceed 24 hours; no other enrolled-student type does. The complete database additionally contains professor logs and students outside the selected enrollment population; the global audit observed 1,243 logs across alert, attendance, material, navigation, professor_activity, professor_session, quiz, search, submission, submission_removed and violation. screen_share exists in code but not in these database records. Professor logs are not pooled into the student readiness population.

| Class | Rows | Recorded activity events | Valid duration rows | NULL duration rows | Coverage |
| --- | ---: | ---: | ---: | ---: | ---: |
| SYSADM_4C | 58 | 63 | 0 | 58 | 0% |
| AUTOMATA_4A | 36 | 34 | 0 | 36 | 0% |
| SYSADM_4B | 51 | 145 | 0 | 51 | 0% |
| FPROG_1C | 63 | 184 | 0 | 63 | 0% |
| SYSADM_4A | 58 | 0 | 0 | 58 | 0% |
| FPROG_1B | 63 | 133 | 0 | 63 | 0% |

Old v4-compatible raw event sums, recomputed on this same cohort/cutoff: min 0, median 9,298, mean 104,274.05, max 1,318,664 seconds. The old numeric count was 329 because the formula supplied zero without evidence. v5 numeric count is 0, NULL count 329; valid-session min/median/mean/max are all unavailable, not zero. There are zero students with verified generic duration. Collection is now ready for future page visits, but no new production measurements were fabricated for validation.

The SHA-256 hash of all original activity_logs (IDs used internally only) matched before/after: `abe979c9a17cc6a2868d930ed55034d69dfde4552e7b7565b422f1e1536c0d59`. Historical durations were not rewritten.

Generated artifacts under storage/app/risk-datasets:

- all-classes-20261003-141052-v5.csv
- dataset-readiness-activity-v5-20261003-141052.csv
- dataset-readiness-activity-v5-20261003-141052-classes.csv
- source-audit-activity-v5-20261003-141052.csv
- activity-v5-validation-20261003-141052.json (aggregates only)

Validation: 67 relevant Laravel tests / 646 assertions; eight JavaScript tests. Full suite: 72 passed, 20 pre-existing authentication/profile failures (factory required-name fields and registration expectations). No labels, academic-grade module, or ML training was introduced.
