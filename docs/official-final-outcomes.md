# Official final outcome collection

Official final outcomes are professor/institution-provided numeric results, not estimates from LMMS assessments. No grade scale, passing threshold, risk-status mapping or model is defined here. The risk feature version stays v5 and every retained predictor formula is unchanged.

## Existing schema and decision

The existing subject_grades table has user_id, lab_session_id, grading_period, grade DECIMAL(6,2), recorded_at, recorded_by, timestamps and a unique student/class/period key. Its final period is suitable for one current official numeric outcome per student/class. final_outcome is the external import/export name; it is stored as subject_grades.grade with grading_period final. No duplicate academic outcome master table was created.

The live schema was inspected before migration and contained zero subject grade rows. Existing grade models/relations remain in use. ResearchExperiment/recommendation outcome tables concern experiments and interventions, not official final academic results. No reusable official-grade CSV import existed. users.school_id is the institutional identifier already shown in authorized class roster interfaces. student_activities is not a grade source (zero live rows). No activity, grade calculation or risk feature is used to infer official results.

The additive migration 2026_10_03_000002_add_official_outcome_audit adds:

- subject_grades.source (nullable for existing records).
- academic_outcome_imports: UUID batch, class, importer, source/reference, original basename, content SHA-256, private server-side preview, expiry, commit time and timestamps.
- subject_grade_revisions: immutable-by-workflow grade snapshots, recording time/actor/source, optional import link, and capture timestamps.

Audit foreign keys restrict deletion of referenced records. A class/user with recorded outcome audit history cannot simply be hard-deleted through existing cascade behavior; retain/archive it or establish a reviewed research retention procedure. Existing rows are not backfilled, rewritten or deleted by migration. Ordinary role authorization does not grant access to arbitrary database tables; deployment database credentials should limit administrative write access to protect audit history. The workflow is append-only for revisions, not a claim of cryptographic tamper-proof storage.

## Professor/admin workflow

Professors use the Official final outcomes link in their classroom sidebar. Administrators use Outcomes in the class list. The shared route is /official-outcomes/{classId}. The class owner professor or an admin can view, preview and commit. Students and other professors receive 403, including direct POST attempts. Missing batches or another actor's preview return 404. A preview is owned by its importer; an admin can create a separate preview for any class but cannot commit another actor's batch under that actor's identity.

Upload a UTF-8 CSV, up to 1 MB / 5000 records, with the exact header:

```csv
student_identifier,final_outcome
```

student_identifier must be the student's school_id, not a database ID. Preserve leading zeros; spreadsheet software must treat institutional identifiers as text. final_outcome accepts a signed numeric decimal with one to four integer digits and up to two decimal digits, reflecting only DECIMAL(6,2) storage capacity. Empty values, exponent notation, categorical outcomes and excess precision are rejected. This restriction does not choose an academic scale. If institutional outcomes require categorical results or greater precision, confirm their format and adapt storage explicitly before import.

The source/reference is required. A manual single-student record/correction form uses the same preview/commit pipeline, with a source/correction reason. No direct unpreviewed grade-entry endpoint exists.

The preview shows the uploaded identifier, proposed outcome, previous outcome only for a matched member of that class, duplicate rows, invalid values, and unmatched/ambiguous membership. It does not reveal a non-member's identity or grades. Uploaded values and references are escaped by Blade. The original file is not stored; filename/hash and server-side preview provide audit provenance while avoiding an additional raw upload copy. This preview contains sensitive authorized import data and must remain in the private database. No import contents are printed by CLI.

Commit is all-or-nothing. Any invalid row blocks the batch. Previews expire after 24 hours. Commit checks current class ownership, locks the class and membership rows, rechecks student role/institutional identifier, and checks each current grade fingerprint against its preview. Changed membership or outcomes require a new preview. Concurrent imports using this workflow serialize by class; retrying a committed batch adds no extra grade/revision. Identical repeated CSVs in new batches are intentionally separate audited imports, not automatically deduplicated official records.

## Corrections and history

One current final-period grade is updated. Every accepted record appends a revision; the previous revision stays intact. If an existing legacy grade has no revision, its prior grade, recording time/actor and source are captured before the first correction. The new revision references its batch and official source. The page displays current outcomes and their revision histories. Ambiguous legacy final-period records are blocked for administrative review rather than silently merged.

This service must remain the write path for official corrections. Direct database edits bypass workflow guarantees. recorded_at is the server import recording time, not the academic assessment date; the supplied source identifies the institutional record. Existing outcome revisions can support a later dated/versioned research join without reconstructing earlier values from mutable current grades.

## Separate research export

```shell
php artisan risk:export-outcomes
php artisan risk:export-outcomes --classes=1,2 --output=official-outcomes.csv
```

Exports are local administrative CLI operations, not public HTTP downloads. The local command has access to all recorded classes unless --classes restricts selection; OS/database access must be reserved for authorized operators.

Columns:

```text
student_group_key,lab_session_id,final_outcome,recorded_at,outcome_revision_id
```

The exporter selects the newest imported revision of each final-period grade. Unverified legacy-only grades are excluded until explicitly recorded through the official workflow. It preserves outcomes for students later withdrawn from a class. Grouping uses the exact RiskFeatureSchema HMAC key used by predictor exports; join on student_group_key + lab_session_id while retaining the predictor's observation cutoff/version. Keep the grouping secret stable across both exports. No name, email, school ID, internal user ID, free-text source or risk status is exported. Revision ID and recording time identify the selected outcome version; archived CSVs are never overwritten. Source provenance/history remains in the authorized app/database.

Official results belong after the observation period. This architecture records supplied results; research operators must verify timing and official provenance when assembling a future training dataset. No target label, grade-derived predictor, new predictor or feature-version change is introduced. Normal --ml-ready exports remain predictor-only.

## Validation

76 relevant Laravel tests passed / 780 assertions; eight JavaScript tests passed. Full suite: 81 passed / 20 existing authentication/profile failures, 786 assertions. Tests cover owner/admin imports, unauthorized reads/writes, member matching, duplicate/invalid rows, atomic rejection, legacy capture and correction history, preview expiry/staleness, membership changes, commit retries, research join keys, no PII, no outcome/label in predictors and unchanged risk values. Existing attendance/topic/material/activity tests pass.

The migration succeeded on local MySQL. risk:export-outcomes --output=official-outcomes-architecture-20261003.csv produced a header-only file (zero official results). No example outcomes were added to production-derived data.
