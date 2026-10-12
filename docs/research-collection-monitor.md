# Research data collection monitoring — frozen v5

```shell
php artisan risk:collection-status
php artisan risk:collection-status --classes=1,2,3
php artisan risk:collection-status --output=collection-status.csv
php artisan risk:collection-status --json
php artisan risk:collection-status --classes=1,2,3 --json --output=selected-collection-status.csv
```

This is a read-only researcher CLI report, not a model or risk calculation. It captures one current timestamp, rounded to seconds, and uses it throughout each report. No migration, collection lifecycle change, feature formula change, predictor addition, feature-version change or target-generation operation is performed. Feature extraction uses StudentRiskFeatureService::build, never snapshot persistence. Source counts reuse RiskSourceDataAuditService; official outcomes use the shared latest imported revision reader. A database transaction gives each invocation a consistent read view.

Without --classes, selection matches current dataset collection: active classes with students enrolled by the shared cutoff. Explicit positive class IDs allow monitoring other existing classes, including empty/inactive ones. Duplicate enrollment records do not duplicate student/class rows. Overall student counts are distinct people; feature completeness denominators are student-class rows. A student in two classes contributes two observations but one overall student. Students awaiting outcomes means distinct people missing at least one of their class outcomes; awaiting rows counts the individual student/class pairs.

## Report content

Per class and overall, the monitor shows class descriptors/enrollment, cutoff-created quizzes and tasks, topic tagging, non-simulated quiz attempts, observed submissions, due task opportunities, students with usable quiz/task averages, and completeness of all finalized core/optional predictors. Detailed topic performance coverage comes directly from the frozen feature service, not a newly chosen scalar encoding.

Learning engagement distinguishes source material sessions/accessing students, legacy duration records, visible_v1 sessions and accepted bounded internal-visible duration evidence available by cutoff. External opens do not qualify as verified duration. Measurements of zero remain legitimate evidence, while NULL is not evidence. Existing reading predictors retain their current legacy-compatible formulas: reading_avg_seconds coverage must not be mistaken for verified collection coverage. Legacy duration records are counted separately, not reconstructed or corrected.

Verified generic UI-active measurements use the existing ActivityDurationService cutoff semantics through the source audit, excluding all historical activity_logs durations. Event counts and source student counts remain descriptive. Verified attendance uses attendance_sessions status counts and the frozen attendance-rate calculation from finalized roster entries. Legacy attendances never supply attendance feature coverage. Session counts describe selected classes; usable attendance coverage describes the current enrolled cohort, so historical meetings may exist without usable rates for that cohort.

Official outcome counts describe latest imported final revisions for selected classes recorded by cutoff; feature/outcome matches describe current enrolled pairs with exactly one such outcome. Outcomes for withdrawn students can remain in the official total without matching a current pair. This is collection availability, not validation of an earlier prediction-point training join; use risk:label-readiness for that separate timing/duplicate audit.

The passing criterion is displayed as configured numeric value/direction, NOT CONFIGURED, or INVALID CONFIGURATION. Unconfigured/invalid criteria show target generation DISABLED. Even with a criterion, the monitor never invokes labeling or trains a model. With zero outcomes it prints NOT READY / awaiting official outcomes, meaning supervised training lacks its ground-truth source. Outcomes without current matching pairs or an unset criterion remain explicit prerequisites; outcomes plus a criterion still do not establish training readiness automatically.

## Interpretation and privacy

Coverage means a non-NULL feature value, including legitimate zero. Fully populated count/total columns can still describe no observed activity; inspect source counts and separate verified-evidence coverage. No percentage is declared universally sufficient. Partial reading coverage is described by its missing-row count, not an invented low-coverage threshold. Neutral No data yet, Collecting, Available and Not applicable statuses describe the presence of observations or opportunities. They are not failure flags or performance forecasts.

Text/JSON/CSV contain only aggregate values and class metadata. Individual names, school IDs, email addresses, raw user IDs and grouping keys are not emitted. --json prints a clean aggregate JSON object; --output can be combined with it. CSV output is a long-form aggregate table with scope/class descriptors, cutoff, version, category, measure and value, including coverage/status/passing-readiness notes. Files are stored under storage/app/risk-datasets and existing files are not overwritten. Formula-like class descriptor text is escaped for CSV consumption. CLI access belongs to authorized researchers/operators; no web dashboard or public endpoint was added.

## Live production-derived report

JSON/CSV shared cutoff: **2026-10-03 15:19:14 Asia/Manila**. Files: collection-status-20261003-151914.csv and matching .json. The separately invoked text report is also saved under the matching artifact prefix and carries its own captured cutoff.

Overall: 323 distinct students, 329 student-class rows, six classes. Five quizzes, four due tasks, 159 real attempts from 158 distinct students, 186 submissions. Quiz-average coverage 48.02% (158 rows), task-average 52.28% (172), submission-rate 64.74% (213), reading-average 26.14% (86).

270 material sessions from 128 distinct students are all legacy records; 178 have a legacy duration available by cutoff. There are no visible_v1 material sessions or verified material-duration measurements. 559 recorded activity events represent 249 distinct students; there are no verified generic UI-active measurements. Verified attendance sessions (active/finalized/cancelled) are all zero. All five quizzes and four tasks are untagged; both topic-performance coverage rates are zero. Imported official outcomes and feature/outcome matches are zero; 323 people / 329 pairs await outcomes. Passing criterion is NOT CONFIGURED; target generation is DISABLED.

| Class | Rows | Quiz average % | Task average % | Submission rate % | Reading average % |
| --- | ---: | ---: | ---: | ---: | ---: |
| SYSADM_4C | 58 | 89.66 | 0 | 0 | 5.17 |
| AUTOMATA_4A | 36 | 0 | 33.33 | 100 | 11.11 |
| SYSADM_4B | 51 | 96.08 | 78.43 | 100 | 64.71 |
| FPROG_1C | 63 | 90.48 | 95.24 | 100 | 33.33 |
| SYSADM_4A | 58 | 0 | 0 | 0 | 0 |
| FPROG_1B | 63 | 0 | 95.24 | 100 | 39.68 |

Verified material duration, generic activity duration, attendance and both topic performances have 0% coverage in every class. SYSADM_4C and SYSADM_4A have no tasks; AUTOMATA_4A and FPROG_1B have no quizzes. SYSADM_4A has a quiz but no real attempts, material sessions or recorded activity events. Missing opportunity-based values are expected until those activities exist. The collection priorities are real use of the verified viewers/UI/attendance lifecycle, more measured reading sessions, appropriate professor content tagging, usable task grading where applicable, and eventual official final records. No feature redesign or synthetic filling is performed.

Validation: 86 relevant Laravel tests passed / 919 assertions, eight JavaScript tests passed. Full suite: 91 passed, the same 20 existing authentication/profile failures / 925 assertions. New tests cover quiz/no-quiz classes, tagged/untagged content, simulation exclusion, shared cutoff/cohort counting, verified versus legacy measurements, attendance status/rate coverage, no outcomes, criterion status, class selection, CSV overwrite protection, aggregate privacy, no snapshots/outcomes written, unchanged feature values and v5. All existing predictor/target/outcome/export/attendance/topic/material/activity tests continue passing.
