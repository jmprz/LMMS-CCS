# Later academic research outcome policy v1

## Research change and boundaries

The 2026-10-21 request authorizes a new research-defined endpoint using later
required quiz/task evidence, including confirmed nonparticipation. This changes
the target construct from an official final institutional grade. It is versioned
separately as **research-later-academic-v1**, not silently substituted into old
v5 official-grade labels. Feature measurements remain frozen at **v5**; the new
policy is not a new predictor, official grade, or institutional passing rule.

`config/risk_target.php`, `RiskTargetService`, official preview/commit/revisions,
the existing labeled exporter, frozen predictor code/configuration, archived
baseline and accepted synthetic v2 are untouched. The prior official-outcome
workflow still works as documented. Do not claim this research endpoint is an
official final academic grade or mix the two target definitions in one experiment.

The new policy is **disabled**, with no threshold, direction, window, assessment
types, minimum count or aggregation approval configured. There is no new labeled
export or automatic training entry point. Tests use isolated software parameters,
not approved live research values. No actual outcome generation is run here.

## Exact implemented rule

An approved outcome-evidence bundle supplies a frozen per-student/class inventory
of academic opportunities, required/eligible flags, collection completeness,
completion state, timestamps and scores. Never infer eligibility from current
membership or from the existence/absence of attempts/submissions alone.

1. Count only approved assessment types explicitly verified as **required and
   eligible for that student**. Their first availability and closure must be in
   the completed later outcome window. Optional, excused/ineligible assessments
   do not contribute. No eligible opportunity, or fewer than the approved minimum,
   means indeterminate/excluded, never automatic risk.
2. If any counted observation is unavailable, collection is incomplete, or a
   completed assessment is ungraded (`score = null`), the student/class outcome
   is indeterminate. Preserve that unknown; do not turn it into zero.
3. With complete verified collection, eligible count > 0, and **no completed
   eligible assessment**, the configured `at_risk` nonparticipation rule assigns
   risk. `exclude` is also supported, if explicitly approved. This branch remains
   blocked along with all classification until the whole policy is configured.
4. Otherwise compute **equal-opportunity percentage**:

   `performance = sum(opportunity_percent) / count(eligible opportunities)`

   A completed graded assessment contributes `100 * score / max_score`.
   A **confirmed required non-completion** contributes zero by this explicit
   proposed aggregation rule. This is not coercion of a missing score: ungraded,
   unavailable or unverified observations were excluded in step 2.
5. Compare the unrounded mean with the approved percentage threshold. `gte`
   passes at or above it; `lte` passes at or below it. Equality passes. Passing
   is `not_at_risk`, failing is `at_risk`. A completed genuine zero is performance
   evidence and uses this comparison; it is not the no-participation branch.

The supported aggregation must be explicitly selected and approved. It gives each
eligible assessment equal weight after point normalization, not equal weight to
quiz/task categories and not pooled raw-points weighting. Thus differing class
opportunity counts use their own denominators; difficulty, assessment quantity,
and category mixtures can still confound comparisons. Approve class comparability
before pooling. Minimum count applies to eligible opportunities; no additional
minimum completed count is silently introduced.

No material/recommendation engagement, early feature values, identifiers or origin
values determine performance/risk. Experiment `weak_topic_threshold` is unrelated.

## Configuration, all live decisions pending

New config: `config/research_outcomes.php`.

| Environment input | Purpose / allowed setting |
| --- | --- |
| `RESEARCH_OUTCOME_ENABLED` | Defaults false; explicitly enable only after review |
| `RESEARCH_OUTCOME_APPROVAL_REFERENCE` | Required policy approval reference |
| `RESEARCH_OUTCOME_THRESHOLD` | Approved finite percentage boundary in 0–100; no default |
| `RESEARCH_OUTCOME_DIRECTION` | `gte` or `lte`; no default |
| `RESEARCH_OUTCOME_MIN_OPPORTUNITIES` | Explicit positive integer; no default |
| `RESEARCH_OUTCOME_NO_PARTICIPATION` | `at_risk` or `exclude`; no default |
| `RESEARCH_OUTCOME_AGGREGATION` | Explicit `equal_opportunity_percentage`; no default |
| `RESEARCH_OUTCOME_START`, `RESEARCH_OUTCOME_END` | Approved local `YYYY-MM-DD HH:MM:SS` window |
| `RESEARCH_OUTCOME_TYPES` | Explicit comma-separated subset of `quiz,task`; defaults empty |

`policy_version` is fixed to `research-later-academic-v1`. A new meaning requires
a new policy version and separate artifacts. No environment/cache changes were
made by this implementation. Do not reuse a recommendation threshold or tune
the target boundary against final model performance.

## Evidence contract and source limitations

The existing schema has quizzes linked to lab sessions via `subject_id`, with
`published_at`/`expires_at`; tasks also use `subject_id`, with `created_at` and
`deadline`. Quiz attempts provide `created_at`, `score`, `total_points`, and
`is_simulated`. Submissions provide `submitted_at`/`created_at`; detailed grades
provide `total_score`, `max_score`, and timestamps. These facts do **not** establish
that an assessment was required or historically eligible for each student.
There is no general frozen required-assessment roster in these tables.

Consequently this implementation accepts a **reviewed local evidence bundle**,
not an automatic operational-database absence query. Preparing that bundle from
source records and a professor-approved required/eligible inventory is a remaining
collection prerequisite. Do not fake verification flags or synthesize absent rows
for real students. The service does not certify these human assertions itself.

For real source preparation:

- Freeze per-student eligibility/requirements, exclusions and collection completeness
  with an authorized source reference. Current class membership is insufficient.
- Verify actual first availability; task creation time alone may not prove release.
  Required assessments must first become available after prediction cutoff and
  inside the approved outcome window; early quizzes/tasks retaken later are not
  acceptable target opportunities in this conservative policy version.
- Resolve valid quiz attempts (`is_simulated = false`) and submissions within the
  later window. Select one latest valid completion per opportunity, by event time
  then source ID; preserve its actual score denominator. Do not count attempts
  as multiple opportunities or invent task/quiz score maxima.
- Preserve completion and grade-recording timestamps. Grades may be recorded after
  the window closes, but no later than evidence collection. Missing/ambiguous
  grades remain ungraded, not nonparticipation. Mutable grades require an archived
  evidence capture/hash; there is no claim of reconstructed grade history.
- Explicitly mark `not_completed` only when closed required eligibility and complete
  source collection prove no valid completion. Outages, unavailable records and
  unresolved enrollment/assessment conditions use `unavailable` instead.
- Maintain the predictor-period opportunity registry and never reuse its academic
  opportunity keys for targets. Keep HMAC grouping keys stable; no PII in bundles.

Top-level JSON fields (no extra keys accepted):

```text
policy_version, feature_sha256, prediction_cutoff, outcome_start, outcome_end,
collected_at, data_origin, outcome_origin, approval_reference,
eligibility_verified, predictor_opportunity_keys, rows, simulation
```

`feature_sha256` binds the exact immutable feature CSV. For real bundles use
`data_origin = real`, `outcome_origin = research_observed`, `simulation = null`.
`rows` contains unique `{student_group_key, lab_session_id, opportunities}` objects,
each matching a feature pair. Missing row means indeterminate, not absence. Empty
opportunities explicitly means no assessment inventory for that row, not risk.
Real group keys are 64 lowercase hex characters; class IDs are positive integer
strings. Additional/unmatched pairs and duplicate feature/outcome keys fail.

Each opportunity has exactly:

```text
opportunity_key, type, available_at, closed_at, required, eligible,
eligibility_verified, collection_complete, state, score, max_score,
event_at, recorded_at, event_key, is_simulated
```

`opportunity_key` is `quiz:<class>:<source-key>` or `task:<class>:<source-key>`.
`type` is `quiz` or `task`; boolean fields must be JSON booleans, not strings.
`state` is `completed`, `not_completed`, or `unavailable`. A completed observation
has a unique `quiz_attempt:<source-key>` or `submission:<source-key>`, event and
recording timestamps, a positive finite numeric maximum and either a finite
score in 0..maximum or null (ungraded). Other states have null score/event/key/
recording time. Source keys are non-PII alphanumeric/underscore/hyphen tokens.
All timestamps use the application's timezone and exact second-resolution format.

## Temporal/leakage audit and readiness command

The validator fails contradictory evidence, including:

- Anything except `prediction_cutoff < outcome_start <= outcome_end <= collected_at <= now`.
- A feature row not sharing the exact pinned cutoff or frozen feature version.
- First assessment availability before outcome start, closure outside the window,
  completion before availability or after outcome end, or grade recording before
  completion/after collection. Closure must not precede availability.
- Any target opportunity in the predictor opportunity registry; duplicate
  opportunities within a student/class; any completion event reused across rows.
- Unverified eligibility, wrong origin markers, real simulated attempts, invalid
  score representation, unknown evidence fields or hash mismatch.

The file reader accepts only allowlisted unlabeled feature columns and passes
**only four cohort metadata fields** into the evaluator; predictor values never
construct targets. The unchanged Python loader rejects new target/provenance
fields and does not automatically accept this new target policy as official data.

Future command, only after a reviewed bundle exists (not executed on real data here):

```powershell
php artisan risk:research-outcome-readiness storage/app/risk-datasets/all-classes-20261020-223311-v5-ml-ready.csv path/to/approved-later-evidence.json
```

Output is aggregate JSON: cutoff/window, policy version/hash, boundary, opportunity
definition, minimum count/types, nonparticipation rule, real versus synthetic
feature rows, eligible rows, nonparticipation risk, performance risk, not-at-risk,
excluded/indeterminate rows and balance by the single declared origin. Inspect
origins separately; mixed-origin files are not accepted. Invalid/unconfigured
policy leaves classification counts unavailable rather than inventing zero risk.
`ready_for_review` is not training authorization. Incomplete/invalid inputs return
failure. Errors and reports print no students, group keys or individual scores.

The command performs local reads only, creates no report/dataset files, writes no
database records, exports no labels and never invokes training. It cannot prove
that a dishonest/incorrect source manifest reflects reality: preserve the source
audit and independent eligibility/timing review before enabling the policy.

## Separate synthetic later-evidence generator

`SyntheticResearchOutcomeService::generate()` returns a standalone JSON-compatible
evidence artifact payload; it performs no filesystem/database writes. It accepts
only unmistakable `SYN-...` feature units, a feature hash, explicit seed, approved
policy and separately frozen approved scenario. No live scenario parameters or
outcome window are supplied in this implementation, and no actual artifact is
generated. The accepted v2 CSV/manifest/audit are never changed.

The scenario requires its own name, target-policy version, `approved`/`frozen`
flags, matching approval reference, explicit virtual opportunities (`type`,
`required`, `eligible`), and per-profile `completion_probability`,
`performance_mean`, positive `performance_sd`. Sample completion probabilistically;
sample later percentage scores from overlapping normal distributions, bounded
to valid percentages, using seeded Mt19937 random draws. Profiles condition the
simulation, not the real target classifier. Earlier quiz/task scores and the
recommendation threshold are not used as label rules.

Generated units first receive completion states, scores, maxima, later timestamps
and new virtual event keys; no canonical label appears in the returned artifact.
Its provenance includes `data_origin = synthetic`, `outcome_origin = synthetic`,
generator/scenario version, seed and feature/scenario hashes. Simulated events
are explicitly flagged. The same policy evaluator then reports aggregate labels
for these underlying outcomes. Never store them in `subject_grades`, official
imports/revisions or an artifact presented as institutional results.

No exact target balance is forced; freeze probabilities/dispersion before any
training and do not regenerate to optimize model metrics. Synthetic evidence is
not participants, genuine later academic performance or institutional ground truth.
Any future file persistence must use a new simulation-only filename and preserve
all existing artifacts. Final evaluation augmentation must be calibrated within
genuine fitting partitions, not from real validation/test rows; accepted v2's
full-reference calibration is not an approved final-evaluation augmentation path.

## Remaining gates and validation

Before live aggregate classification or eventual label generation: approve this
new endpoint and its equal-opportunity/missing-work weighting; select the boundary,
direction, minimum opportunities, no-participation option and included types;
fix the actual later window; collect and verify required-eligibility inventories
and later evidence; approve source completeness and ungraded/unavailable handling;
review class comparability and exclusions. Synthetic outcomes require their own
frozen scenario/approval and training-partition calibration policy.

No new research-labeled exporter or training authorization adapter is enabled.
The official-grade Python attestation must not be reused for research-observed
targets. A future reviewed handoff must preserve target-policy version/origins
outside X, enforce real student-group splits and real-only validation/test, and
bind the exact policy/evidence/dataset hashes. No final labels or models are
produced by this change. The previous official-grade path remains independent.

Tests cover no opportunities, authoritative nonparticipation, missing/ungraded
states, zero scores, threshold equality/direction, partial completion, class
denominators, temporal/event overlap, provenance, seeded simulation without SQL,
read-only command behavior, and rejection of target-construction fields from X.
