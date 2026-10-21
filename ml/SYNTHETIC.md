# Controlled synthetic feature generation

This is file-only simulation software. No generated record is an enrolled student, an observed LMS event, an official outcome, or a labeled training example. Adviser approval to explore augmentation does not establish statistical validity or authorize changing frozen v5 or the official target methodology. Current training/evaluation authorization remains separate.

## Commands

Run from `ml/`, using the existing `.venv`. No additional dependencies are needed.

```powershell
.venv/Scripts/python.exe -m lmms_ml synthetic <immutable-real-unlabeled-ml-ready.csv> --schema lmms_ml/contract.json --scenario fprog_1a_v1 --rows 60 --seed 20261020 --output data/synthetic/fprog1a-dev.csv
```

The scenario, row count, seed, feature contract and output are mandatory. `--schema` explicitly names the ML-side frozen v5 contract, **not** the labeled-export companion schema. The input must be a research-safe **unlabeled** ML-ready CSV. PII columns, existing provenance columns, targets/outcomes, malformed data, duplicate pairs, non-v5 versions and mixed cutoffs are rejected using the shared dataset validator's feature-only mode. Default labeled validation/training behavior remains unchanged.

```powershell
.venv/Scripts/python.exe -m lmms_ml synthetic <immutable-real-unlabeled-ml-ready.csv> --schema lmms_ml/contract.json --scenario general_augmentation_v1 --class-code FPROG_1B --material-opportunities 1 --rows 60 --seed 20261020 --output data/synthetic/fprog1b-dev.csv
.venv/Scripts/python.exe -m lmms_ml combine-features <same-immutable-real-unlabeled-ml-ready.csv> --synthetic data/synthetic/fprog1a-dev.csv --output data/synthetic/mixed-dev.csv
```

General augmentation requires one explicit class, at least 20 reference rows by default, consistent opportunity metadata, and an explicit material bound. No class is chosen automatically. Quizzes come from its tagged + untagged content counts and due tasks from `assigned_task_count`. Frozen exports do not contain total material availability; the operator must supply and review that bound, which cannot be below observed distinct accesses. Content counts do not prove individual historical accessibility; this remains a virtual scenario.

Outputs are exclusively created beneath `ml/data/synthetic/` (ignored by Git). Existing CSV or companion files cause failure; there is no overwrite mode. No database connection, PHP subprocess, Laravel configuration, seeder, feature snapshot, label, outcome or model training is involved.

## Scenario configuration

`scenarios/fprog_1a_v1.json` and `scenarios/general_augmentation_v1.json` contain the reference selection, empirical weights, reference-size requirements, opportunity bounds, measurement policy and profiles. Edit scenario configuration deliberately and keep its hash in the run manifest; change the scenario version when changing its meaning. Never change the frozen Laravel v5 definitions to accommodate simulations.

FPROG references **both FPROG_1B and FPROG_1C**, requiring the two class codes to be present. It describes a virtual FPROG_1A class with **two quizzes, one due task and one material**, at most 12 sessions per accessed material and 100 activity events per generated observation. These are configured simulation opportunities; the actual inactive FPROG_1A had no evidence at audit. No real roster is loaded, and the 60 generated vectors are **not imputations for its 60 genuine students**. Synthetic lab-session metadata uses `SYN-FPROG1A`, never actual class ID 9.

| Profile | FPROG weight | General weight |
| --- | --- | --- |
| strong | 15% | 15% |
| average | 30% | 25% |
| borderline | 25% | 25% |
| struggling/engaged | 15% | 15% |
| struggling/low-engagement | 10% | 10% |
| irregular/missing | 5% | 10% |

These are simulation assumptions, not population estimates, passing rules or risk labels. Largest-remainder allocation followed by seeded shuffling gives reproducible mixtures (for FPROG's 60 rows: 9, 18, 15, 9, 6, 3).

## Generation methodology and limitations

The generator estimates aggregate score means, coverage, session/event intensity and pairwise correlations from the selected real reference pool. Correlation estimates use pairwise observations, shrink toward independence and are projected to a valid correlation matrix. Too little observed score evidence (default fewer than five values) disables that score generation. Configured profile score means blend 75% profile assumption with 25% observed mean; correlated latent variation and independent residual noise create broad overlapping performance/engagement patterns. No donor student's vector or identifier is copied. All score values are bounded only by 0–100; none are used to derive risk labels.

This is a **heuristic scenario generator**, not a fitted/validated joint model of student behavior. The profiles deliberately change representation; empirical calibration and overlap do not prove educational realism or remove synthetic-origin cues. It does not promise exact preservation of correlations or origin indistinguishability.

Quiz counts use a bounded participation draw, and a quiz average requires at least one represented quiz. Task submission rates are constructed from discrete submissions/due-task opportunities; lateness cannot exceed submissions. Material accesses cannot exceed availability, sessions require accessed materials, and session/event caps are explicit. Missingness uses empirical opportunity-conditioned quiz/task coverage plus configurable profile missingness. Counts are not independently drawn without these constraints. Opportunities, no evidence, and legitimate zero remain distinct.

Globally all-missing numeric predictors remain ungenerated. More conservatively, **attendance rates, activity durations and reading-average durations are always missing in these initial scenarios**, even if a future input contains some numeric values: a feature CSV alone cannot attest verified measurement provenance. `reading_total_seconds=0` represents the frozen sum over no generated measurable-duration records; it is not a claim of zero engagement. No measured durations are invented or reconstructed. Topic quiz/task JSON stays `{}`; these scenarios do not invent topic assignments or performance. Even existing topic-quiz evidence does not automatically authorize a synthetic topic mapping.

The archived October 3 baseline is the currently inspected file reference, not the current database. Its FPROG pool contains 126 rows, 57 observed quiz averages, 120 task averages, 46 reading averages, and zero attendance/activity-duration measurements. The command always uses the explicitly supplied export and records its cutoff/hash. No current database records are fetched. A source cutoff retained in synthetic metadata is a comparison coordinate, not proof that generated activity occurred then.

**Before any future augmented model analysis, select real holdout students first and estimate generator distributions from real training students only.** Do not estimate correlations/profiles/missingness using held-out observations. This generator does not split, evaluate or train models; the existing official training loader rejects its provenance-bearing/unlabeled outputs. Mixed-data training would require a separately reviewed integration that preserves untouched real evaluation groups. No classifier or origin-discrimination model is trained here.

## Provenance and artifacts

Each row has `data_origin=synthetic`, `synthetic_scenario`, deterministic `synthetic_run_id` and `synthetic_profile`. Synthetic grouping IDs have unmistakable forms such as `SYN-FPROG1A-<run-prefix>-000001`. Their namespace does not overlap real HMAC grouping keys. None of these fields can enter X: the model projection is an explicit predictor allowlist, and the official training loader rejects these added columns entirely. Keep all provenance when sharing data; never strip it to bypass training gates.

Every run creates:

- `<output>.csv`: synthetic feature vectors only, without target/outcome columns.
- `<output-stem>.manifest.json`: input filename/hash, generator version/source hash, contract/scenario hashes, complete profile/opportunity/rule configuration, source classes and aggregate reference counts, seed, requested/generated counts, deterministic run ID, UTC timestamp, Git commit, output hash, unsupported-measurement policy and warnings. `training_eligible=false` and `real_participants_added=0` are explicit.
- `<output-stem>.distributions.json`: real-reference versus synthetic count, missingness, mean, median, sample SD, min/max for every scalar predictor. Nonoverlapping observed ranges, absolute standardized mean difference ≥1.5, or missingness difference ≥50 percentage points are flagged as inspection concerns. These are report heuristics, not academic thresholds. No regeneration or post-hoc adjustment hides a flag. Examine joint plausibility separately.

The run ID hashes input/scenario/generator-version/seed/row-count/class-bound settings. Identical inputs and pinned software produce byte-identical CSVs and IDs. Manifest timestamps and Git fields describe the particular execution and need not be byte-identical. Preserve manifests and distribution flags with the files. Hashes provide integrity checks, not authenticity or institutional approval.

The combine utility requires the exact real source hash used by the generator and validates the synthetic manifest/hash, identifiers, opportunity constraints and schema. It writes a **new** development-candidate file. Real rows receive `data_origin=real`; their existing cells are preserved. Synthetic rows keep all provenance. The combined manifest separately records real rows, unique real students, synthetic rows and total rows. Synthetic rows never increase the participant count. No official outcomes or targets are added; mixed data remain training-ineligible.

## Tests and operational boundaries

```powershell
.venv/Scripts/python.exe -m pytest -q
```

Tests generate fixture files only inside temporary directories, replacing the output boundary in the test process. They verify deterministic seeds, exact counts, overlap/profile allocation, bounds, missingness versus zero, origin metadata, exclusion from X, rejection by official training validation, immutable input bytes, hash verification, collision/path safety, combination counts and absence of network/database access. All earlier ML tests remain applicable.

No real-dataset synthetic run is automatically performed by installation or tests. No generated feature vector is claimed to be an official participant observation, risk label, academic outcome or evidence of model performance.
