# Genuine OOP temporal cohort v1

The user-approved cohort rule establishes an expected later opportunity through
experiment assignment, consent recorded before cutoff, and explicit class-posttest
mapping, unless documented exemption/conflict evidence exists. This approval
supersedes the earlier readiness exclusion of mapped nonparticipants. It does not
change frozen v5 measurements or recommendation thresholds.

The approved cutoff is **2026-10-15 17:59:59 UTC+08:00**. OOPRG_2A uses Quiz 9;
OOPRG_2B uses Quiz 11. Consent, assignment and mapping must predate cutoff. The
window begins 2026-10-15 18:00:00 and ends 2026-10-17 07:59:00. Each assessment's
own publication/closure also constrains accepted completions.

Targets use the existing `research-later-academic-v1` evaluator with a local,
explicitly approved 70%, gte policy. Equality passes. A nonparticipant's canonical
target is at_risk with reason nonparticipation and **NULL raw score and percentage**.
A scored percentage below 70 is at_risk/below_threshold; otherwise it is
not_at_risk/meets_threshold. No institutional final grade is created or inferred.
The known assessment maximum remains 20 for nonparticipants; that is opportunity
metadata, never a zero score. All outcome metadata, reasons and origins stay out
of X. Existing risk_target configuration and official-grade rules remain unchanged.

## Exporter and audit gates

`tools/export_oop_temporal.php` is intentionally local-only: it verifies loopback
3306, lmms_ccs_db_prod_1018 and XAMPP's local data directory before data queries.
All operational queries run in a READ ONLY transaction, rolled back. It never
updates records, official outcomes, saved feature snapshots or configuration.

It checks experiment status/window, unique mappings, pre-cutoff consent/assignment,
current student membership joined by cutoff, conflicting assignments, invalid
attempts, source point/detail consistency and posttest temporal separation.
Exemption checks cover participant withdrawal/decline, active experiment status,
dedicated exemption schema, class activity logs, experiment description and verified
attendance exceptions. Potential exception evidence stops export for review; a
changed cohort is not silently forced back to expected counts. External/offline
exemption records were not supplied and are not claimed to have been inspected.

The source gates require the reviewed 53/55 rosters, 29/43 completers, and actual
recomputed aggregate 36 nonparticipation / 1 below-threshold / 71 passing cases.
These are sanity checks after computation, not generation targets. A change blocks
export instead of modifying observations or selecting favorable attempts. Latest
valid non-simulated attempt by ID is the retry rule; neither posttest currently
has duplicate student attempts. Invalid observed records block reconciliation
rather than silently becoming nonparticipation.

From the repository root:

```powershell
php ml/tools/export_oop_temporal.php --check-only
php ml/tools/export_oop_temporal.php --output=oop-temporal-labeled-v1.csv
```

Existing output files are never overwritten. A later authorized reproduction needs
a new basename; do not regenerate an existing artifact. First-run outputs under
`storage/app/risk-datasets/`:

- `oop-temporal-labeled-v1.csv`: 108 reconstructed predictor rows with canonical Y
  and separately categorized outcome/provenance metadata; no raw user identifiers.
- `.features.csv`: the exact unlabeled 108-row feature reconstruction, preserving
  missing cells and frozen formulas; not a saved database snapshot.
- `.schema.json`: explicit columns/categories, target policy, CSV and feature hashes.
- `.evidence.json`: hash-bound later opportunities, completion states and source
  event keys; grouping metadata is pseudonymous and remains restricted research data.
- `.provenance.json`: class totals, approval basis, source hashes and exclusion scope.

Neither synthetic observations nor official final-outcome tables supply these
targets. The original late-cutoff export is only a preserved cohort/group-key
reference; earlier predictors are freshly computed through the unchanged feature
service at the approved cutoff. Predictor counts/topic metadata are not repurposed
as outcomes. Posttest score/completion metadata never enter X.

## Existing Python CLI: explicit validation-only contract

The default official-grade loader remains unchanged. It correctly rejects this
cohort's research metadata and absence of official final_outcome. No NULL outcome
is filled to get past that loader. Instead, the existing CLI has an explicit
validation-only `oop-temporal-v1` option:

```powershell
ml/.venv/Scripts/python.exe -m lmms_ml validate storage/app/risk-datasets/oop-temporal-labeled-v1.csv --contract oop-temporal-v1 --schema storage/app/risk-datasets/oop-temporal-labeled-v1.schema.json
```

Run that command with `PYTHONPATH=ml`, or from `ml/` use:

```powershell
.venv/Scripts/python.exe -m lmms_ml validate ../storage/app/risk-datasets/oop-temporal-labeled-v1.csv --contract oop-temporal-v1 --schema ../storage/app/risk-datasets/oop-temporal-labeled-v1.schema.json --report ../storage/app/risk-datasets/oop-temporal-labeled-v1.validation.json
```

The validator uses the unchanged feature-only loader on the hash-bound `.features.csv`
and verifies exact predictor-cell equality with the labeled file. It independently
checks mapping, origins, enrollment-eligibility attestations, temporal bounds,
score/denominator/percentage consistency, label/reason consistency and NULL
nonparticipation fields. It reports population, target balance, missingness,
constants, all-missing fields and exclusions. No preprocessing is fitted.

Default X is the eleven frozen core scalars. Attendance remains optional; topic
JSON is validated but has no scalar encoding. Class, group key, experiment group,
outcome quiz/event IDs, completion state, outcome score/percentage, risk reason,
target, policy version and origin never enter X. Direct identifiers and arbitrary
extra columns remain rejected.

`--contract oop-temporal-v1` is not accepted by the training command. This task
authorizes labeling and validation, **not training**. Both schema and validation
report mark training_authorized false. Any eventual training adapter needs a
separate reviewed research-observed target authorization and real student-grouped
evaluation design; never attest these targets as institutional final grades.
Treat experimental/control assignment as interpretation metadata only.
