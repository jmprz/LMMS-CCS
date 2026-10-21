# Final outcome-to-training handoff

Prepared 2026-10-21, UTC+08:00. This guide prepares later authorized execution;
it does not authorize imports, configuration changes, label generation or model
training. Frozen feature semantics remain v5. Preserve the October 3 baseline,
the real feature export and accepted synthetic v2 unchanged.

## Verified implementation and scope

The existing numeric real-outcome route needs no application changes:

| Component | Existing behavior |
| --- | --- |
| `OfficialOutcomeService::preview()` | Owner professor/admin only; exact CSV header, class membership, numeric representation, duplicate checks, required source; creates a private preview batch expiring after 24 hours |
| `OfficialOutcomeService::commit()` | Transactional, ownership/membership and stale-grade rechecks, one current final grade, append-only-by-workflow revisions, idempotent committed-batch retry |
| `OfficialOutcomeResearchService::rows()` | Latest imported non-null revision per final-period grade; unverified legacy-only grades excluded |
| `RiskLabelDatasetService::inspect()` / `risk:label-readiness` | Pseudonymous student/class join; duplicates, coverage, missing matches and recording-time anomalies; missing predictors preserved |
| `RiskTargetService` | Requires a configured finite numeric criterion and `gte`/`lte`; pass is `not_at_risk`, fail is `at_risk`; no predictor constructs a target |
| `risk:export-labeled-dataset` | Matched rows only; blocks missing criterion, duplicate keys, invalid predictor cells and unsafe timestamps; new CSV plus schema/provenance sidecar; no overwrite |
| Python `validate` | Frozen predictor/header and canonical-label validation; optional explicit schema check; no fitting or label construction |
| Python training gate | Requires an exact labeled-file hash and human outcome/criterion/timing attestation plus explicit authorization; grouped splits and training-only preprocessing/resampling |

This is source-code verification, not a new live database or deployment smoke test.
Read [official imports](official-final-outcomes.md), [label readiness](risk-label-readiness.md),
[research freeze](research-freeze-v5.md), and [Python behavior](../ml/README.md).
Historical counts and test results in older documents are dated observations.

## Outcome import template and process

Copy [the header-only template](research-templates/official-final-outcomes-template.csv)
into authorized private storage. It deliberately contains no students or outcomes:

```csv
student_identifier,final_outcome
```

- UTF-8 CSV, at most 1 MB / 5000 records per upload. Create a separate file for
  each class; the selected classroom supplies `lab_session_id`.
- `student_identifier` is the enrolled student's institutional `school_id`, not
  a database ID. Preserve leading zeros as text. Never commit completed imports
  or institutional identifiers into this documentation directory/source control.
- Numeric syntax is `-?\d{1,4}(?:\.\d{1,2})?`: optional minus, one to four integer
  digits, optional decimal with one or two fractional digits. No exponent,
  leading plus, percent sign, thousands separator, categorical code or empty
  outcome. This describes DECIMAL(6,2) representation, not the approved scale.
  Verify the actual institutional scale separately; do not convert arbitrarily.
- Obtain the official source/reference, class/term, and academic outcome period
  or date. The CSV itself has only the two required columns. Supply the source
  through the authorized import form.
- Open `/official-outcomes/{classId}` as the owner professor/admin. Upload,
  preview and reconcile against the official class record. Preview writes a
  private batch but does not update grades. A header-only file is a blank
  template, not a valid import; the application rejects empty imports.
- Resolve all errors before commit. Commit within 24 hours using the same
  importer; changed enrollment or outcomes require a fresh preview. Commit is
  all-or-nothing, then records a current `subject_grades` final grade and an
  import-linked revision.
- Corrections use the same preview/commit path with the official correction
  reference/reason. Prior revisions remain. Never correct results with direct SQL.

Unresolved, missing, INC, withdrawn and dropped results stay out of numeric import
unless their treatment is separately approved. Do not replace them with zero,
a failing grade or a guessed category. Record exclusions privately and reconcile
their aggregate count. Omitting an upload row does not erase an older numeric
outcome: if one already exists for a now-unresolved result, stop and obtain a
reviewed exclusion/revocation procedure before export. The current interface has
no unresolved-state/revocation workflow. Imports also require current membership;
historical members without current enrollment require review, not artificial
re-enrollment. No code change is needed when the approved cohort fits these rules.

## Adviser criterion confirmation sheet

All values below remain **pending**, except behavior already defined in code.
This table is not an `.env` file and supplies no configuration values.

| Decision | Value to obtain |
| --- | --- |
| `RISK_PASSING_GRADE` | Approved finite numeric passing value: pending |
| `RISK_PASSING_OPERATOR` | Explicitly approve `gte` (higher passes) or `lte` (lower passes): pending |
| Official scale | Name, units, permitted range, precision and comparability across classes: pending |
| Equality | Existing operators are inclusive: equality passes; confirm institutional agreement |
| Missing outcome | Exclude unmatched/no usable final outcome; approve and record exclusions |
| INC/withdrawn/dropped/unresolved | Exclude unless separately approved; no numeric substitutes; formal disposition pending |
| Academic timing | Evidence the final outcome follows the feature observation period: pending |
| Approval | Institutional/adviser reference, reviewer and date: pending |

The existing `gte` default is not institutional approval. If equality should
fail, a new reviewed target rule is required; do not reinterpret these operators.
LMMS recommendation eligibility and its experiment-specific threshold are unrelated
to this criterion. Do not substitute either 70 or 75. A future authorized operator
must verify Laravel's effective configuration after applying approved values;
this preparation changes neither `.env` nor caches.

## Exact pre-label command

From `C:\xampp\htdocs\LMMS-CCS`, use the pinned genuine predictor file explicitly:

```powershell
php artisan risk:label-readiness storage/app/risk-datasets/all-classes-20261020-223311-v5-ml-ready.csv
```

This command is documented, not executed for this preparation. With an unset
criterion it reports joins without a target distribution. After criterion
configuration it additionally computes aggregate target counts in memory; run
that stage only when approved. It never persists labels or trains models.

Review matches, missing outcomes, both unmatched sides, duplicates, revisions,
class coverage and recording-time anomalies. Exit success is not training
authorization. Reported target balance covers all selected official outcomes,
including unmatched outcomes; use the actual matched labeled export for training
population balance. Zero timing anomalies without matches proves no timing fact.

`recorded_at` is import time. The exporter requires it strictly after the feature
cutoff and not in the future; independent provenance must also establish that
the academic outcome follows observation. A later import alone is insufficient.
Keep the grouping secret stable for feature/outcome matching.

## Future authorized handoff

Official outcomes -> pre-label join readiness -> approved criterion and temporal
review -> repeated readiness -> labeled dataset export -> Python validation ->
pre-training approval gate -> student-grouped real split -> optional fitting-only
augmentation -> six-model CV comparison -> locked protocol -> final real-only test.
Criterion approval may precede imports; no label export occurs before both exist.

The next two commands are **future instructions only**. Run only after approval,
official imports and readiness review. Output names must be new; if occupied,
choose a new filename and update every handoff reference consistently.

From the repository root:

```powershell
php artisan risk:export-labeled-dataset storage/app/risk-datasets/all-classes-20261020-223311-v5-ml-ready.csv --output=official-labeled-approved-v5.csv
Get-FileHash -Algorithm SHA256 -LiteralPath storage/app/risk-datasets/official-labeled-approved-v5.csv
```

Preserve `official-labeled-approved-v5.csv` and its `.schema.json`. The schema's
`input_sha256` refers to the original unlabeled feature CSV; record a separate
hash for the labeled file. Latest outcome revisions are selected at export time;
later corrections need a new export/review, never an overwrite of an older one.
Retain the selected revision IDs and their private import/source audit chain.

From `ml/`, after that export exists:

```powershell
.venv/Scripts/python.exe -m lmms_ml validate ../storage/app/risk-datasets/official-labeled-approved-v5.csv --schema ../storage/app/risk-datasets/official-labeled-approved-v5.schema.json
```

Validation reports matched labeled row/student/class counts, real target balance,
duplicates, predictor coverage, constants and all-missing fields. It does not
prove institutional provenance or authorize fitting. It checks label vocabulary
and schema, not independent re-derivation of each label from the criterion;
retain the trusted Laravel export and hash-bound human review.

Complete a private copy of [the pre-training report](research-templates/pre-training-report.template.json)
from measured results. `null` means not assessed, never zero. Real label
percentages use genuine matched labeled rows as denominator. Populate class
representation with aggregate real rows/students, target counts and coverage.
Record each CV fold's real fit/validation students, rows, labels and zero overlap;
CV validation students recur across folds and are not an additional population.
Record fitting-partition missingness separately. An all-missing column is not
invented zero evidence; the existing Python imputer excludes it per fitting fold.

[The authorization-review template](research-templates/official-training-review.template.json)
is deliberately invalid for training until independently completed: review flags,
reviewer and hash are null. A future approved copy must use the exact labeled CSV
SHA-256, `data_origin = official_research`, a nonempty reviewer, and genuinely
verified outcome/criterion/timing flags. The CLI also requires explicit training
authorization. A report/template is not permission to train. No training command
is run or authorization granted by this preparation.

Default X remains the eleven frozen core predictors; optional attendance requires
an explicit analysis decision. Topic JSON is not encoded into X. Final outcome,
canonical target, student/class identifiers, grouping keys, import/revision data,
criterion and real/synthetic provenance never enter X. No learned preprocessing
or feature selection sees validation/test data.

## Training-partition-only augmentation proposal

Accepted synthetic v2 remains a development artifact. Its FPROG calibration used
the full reference pool, so its existing rows must not be injected into a final
evaluation whose validation/test students contributed to that calibration.
Feature acceptance does not approve synthetic outcomes.

Proposed future ML-side interface, **not implemented**:

1. Form the real student holdout first, then real grouped CV folds inside the
   outer training set. Seal real validation/test rows from generator calibration.
2. For each fit partition, instantiate a new generator and calibrate it using
   only that partition's approved FPROG reference observations. No full-data
   distribution reports, missingness rates, score means or correlations may be
   reused as fitted statistics. Fixed, approved measurement mechanics may be
   retained as protocol constants and documented as such.
3. Conceptual interfaces are `fit(real_fit_features, approved_config)` and
   `sample(rows, seed)`. If approved outcome calibration uses genuine outcomes,
   it also receives only genuine fitting outcomes. Synthetic outcomes remain a
   separate approved seeded policy, never official `subject_grades` records.
4. Check minimum reference size and required measurement support in each fold.
   If insufficient, fail explicitly or use a preapproved real-only comparison;
   never borrow observations from validation/test. Fit local calibration anew
   for the final outer-training fit, after model/strategy selection.
5. Derive deterministic seeds from a prespecified master seed and partition ID.
   Record generator/config hashes, calibration-source hash, profile allocation,
   outcome-policy approval, added rows and fitting groups' partition hash. Never
   optimize seeds or synthetic label balance against held-out performance.
6. Fit the imputer/scaler on genuine fitting observations, transform genuine and
   approved synthetic fitting rows, then apply any approved training-only sampler
   and classifier. Keep separate counts for generated augmentation and rows
   added by resampling. Real validation/test undergo transformations only.
7. Assert zero genuine-student overlap, zero synthetic/resampled validation/test
   rows, frozen X only, and provenance excluded from X at every fit/evaluation.

The current official training loader intentionally rejects synthetic provenance
and `SYN-...` identifiers. A separately reviewed augmentation input/adapter is
required for this proposal. Do not relax the real-only loader or change v5 feature
definitions to bypass it. The real-only route can proceed without augmentation.

## Final comparison and release boundaries

The existing code supplies Logistic Regression, Decision Tree, Random Forest,
Gradient Boosting, SVM and KNN, with grouped real splits, training-only learned
preprocessing and optional training-only resampling. Both labels must occur in
every fit/evaluation partition and sufficient genuine groups must exist for CV;
do not replace an infeasible real holdout with synthetic validation/test rows.

There is a distinction between existing infrastructure and the preferred staged
protocol: `ml/lmms_ml/runner.py` currently evaluates holdout metrics for each
candidate in the same run. It has no CV-only comparison followed by a separately
authorized final-test release. Preapprove a protocol for the existing fixed
comparison, or separately authorize an ML-runner extension for strict staged
selection. Do not select/tune using holdout results or claim this separate gate
already exists. No such extension is implemented here.

Remaining research decisions are the official scale/value/direction and equality
confirmation; unresolved-outcome exclusions; academic timing/provenance; approved
cohort and coverage sufficiency; split/fold sizes and seed; optional attendance,
augmentation/outcome policy and imbalance strategy; selection metric/protocol;
and explicit training/final-test authorization. The templates contain no invented
counts, thresholds, approvals or measured overlap results.
