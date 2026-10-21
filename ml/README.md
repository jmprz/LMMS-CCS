# LMMS v5 training infrastructure

**DO NOT USE FOR FINAL TRAINING UNTIL OFFICIAL OUTCOMES AND THE APPROVED PASSING CRITERION ARE AVAILABLE.**

This package reads explicit local CSV files only. It has no MySQL connector, Laravel bootstrap, database credentials, label construction, or recommendation logic. No script automatically discovers the newest dataset. Nothing in this package makes the current research collection ready for training.

Controlled **unlabeled** feature simulation is documented separately in [SYNTHETIC.md](SYNTHETIC.md). Synthetic/mixed-origin files are excluded from this document's official labeled-training contract and remain training-ineligible.

## Installation and tests

Tested with Python 3.13.9. From the repository root on Windows:

```powershell
python -m venv ml/.venv
ml/.venv/Scripts/python.exe -m pip install -r ml/requirements-lock.txt
Set-Location ml
.venv/Scripts/python.exe -m pytest -q
```

`requirements.txt` pins direct dependencies; `requirements-lock.txt` pins the complete tested environment. Tests generate tiny, explicitly fabricated observations and outcomes inside pytest temporary directories. They exercise model fits and a temporary candidate run solely to test software. Their metrics/artifacts are not research results. No research dataset or database is read by tests.

## Obtain a future legitimate export

Follow `../docs/research-freeze-v5.md`, `../docs/official-final-outcomes.md`, and `../docs/risk-label-readiness.md`. After official outcomes, the institution-approved criterion, and temporal/provenance review are available, an authorized Laravel operator may run:

```powershell
php artisan risk:label-readiness <explicit-unlabeled-v5-ml-ready.csv>
php artisan risk:export-labeled-dataset <explicit-unlabeled-v5-ml-ready.csv> --output=<new-labeled-name.csv>
```

These are future instructions, not commands run for this implementation. Preserve the new CSV and its matching `.schema.json`; never rewrite the baseline. Work on controlled copies outside source control (`ml/data/` is ignored). Keep grouping secrets stable in Laravel; Python does not need them. A CSV alone cannot prove provenance or academic temporal eligibility. The schema's `input_sha256` hashes the **unlabeled input**, not the resulting labeled CSV; Python records the labeled file's own SHA-256 separately.

## Dataset contract

`lmms_ml/contract.json` mirrors `config/risk_features.php`. A regression test compares core/optional/structured fields to the frozen PHP scope. Required columns are all eleven core scalar predictors, `student_group_key`, `lab_session_id`, `feature_cutoff_at`, `feature_version`, `final_outcome`, and `academic_risk_status`.

- Version is exactly `v5`, with one shared `YYYY-MM-DD HH:MM:SS` cutoff.
- Group keys are the exported 64-character lowercase hexadecimal pseudonyms. Class IDs are positive integer metadata.
- Target values are exactly `not_at_risk` and `at_risk`; `at_risk` maps to positive class 1. Labels are read, never inferred/recomputed from outcomes or predictors.
- Optional attendance, class descriptors, supporting opportunity counts and structured topic JSON may be present as specified in the contract. Any other column is rejected, including direct identifiers, excluded predictors, prediction/recommendation results and intervention results.
- `final_outcome` is required non-X ground truth. No institutional outcome range or passing threshold is invented in Python.
- Only empty numeric cells are missing. Zero is a real value. Text such as `NULL`, `NA`, infinity and nonnumeric cells is rejected. Nonnegative counts must be integers; percentage predictors must be within 0–100.
- Duplicate student/class pairs, inconsistent cutoffs, malformed CSVs, invalid topic JSON and missing required columns fail validation. No rows are silently dropped or deduplicated.
- Topic JSON is validated and coverage reported, but never encoded or used in X. A scalar/model encoding remains a separate methodological decision.

The default X is exactly the eleven core predictors. `--include-attendance` explicitly adds the frozen optional predictor. Class IDs/descriptors, group keys, opportunity counts, cutoffs/version, outcomes, targets and JSON never enter numeric preprocessing or estimators. No automatic numeric-column selection is used.

## Validate without training

From `ml/`:

```powershell
.venv/Scripts/python.exe -m lmms_ml validate data/future-labeled.csv --schema data/future-labeled.schema.json --report data/new-validation-report.json
```

The report contains row/student/class counts, duplicate count, target counts/percentages, predictor missingness/ranges, constant/all-missing predictors, invalid percentage/negative/fractional-count counts, unexpected/missing columns and warnings. Structural validation exits 2; success exits 0. At least 50% missing is an inspection warning, not an eligibility threshold. A one-target dataset can be inspected, but cannot train. Report files use exclusive creation and never overwrite. Reports contain no group keys, direct identities, row values or individual outcomes.

## Grouping and preprocessing

One seeded `GroupShuffleSplit` creates a holdout partition (default 20% of students, not necessarily rows). `StratifiedGroupKFold` operates **only within the training partition** (default five folds). Every generated split is checked for student overlap and both target labels. Insufficient groups per target or an infeasible generated fold fails explicitly; there is no random-row fallback or seed-search to optimize balance. A student may have different labels in different classes; their rows still remain together.

Every fresh model pipeline fits its own training-fold medians. Training-all-missing columns are explicitly warned about, excluded for that fit, and listed in the manifest. They are never filled with an invented zero or recovered using validation/test values. A training partition with no observed predictors fails. This can produce different retained columns across CV folds; the manifest records each fold. Constant observed predictors are reported but retained. No missing indicators or new feature definitions are introduced.

Logistic Regression, SVM and KNN additionally fit `StandardScaler` inside the pipeline. Decision Tree, Random Forest and scikit-learn `GradientBoostingClassifier` use no scaling by default. An explicitly selected SMOTE strategy adds scaling before the sampler for every model because Euclidean-neighbor interpolation otherwise depends strongly on duration/count units. This scaling is fit on the real training fold only. Gradient Boosting is the explicit implementation of the approved Gradient Boosting/XGBoost alternative; XGBoost is not installed or silently substituted. SVM uses decision scores for AUC, not calibrated probability. No feature selection, resampling, class weighting or hyperparameter search is silently added.

Opportunity differences, assessment difficulty, missing-not-at-random evidence and unequal observation windows are not solved by scaling/imputation. Review class opportunity audits before approving pooled analysis. Student grouping prevents identity overlap; additional class/generalization sensitivity analyses remain a research-design decision.

## Future authorized candidate evaluation

Only after a human has reviewed the real export, create a separate provenance-review JSON with:

```json
{
  "dataset_sha256": "<SHA-256 of the exact future labeled CSV>",
  "data_origin": "official_research",
  "official_outcomes_verified": true,
  "passing_criterion_approved": true,
  "temporal_eligibility_reviewed": true,
  "reviewed_by": "<authorized reviewer reference>"
}
```

This is an operator attestation, not cryptographic proof. Never use it to relabel a synthetic fixture as official data. Criterion approval includes numeric scale, direction and value. Laravel's recording timestamp alone does not prove the academic outcome happened after observation. Do not change the passing criterion in order to exercise this package.

```powershell
.venv/Scripts/python.exe -m lmms_ml train data/future-labeled.csv --schema data/future-labeled.schema.json --review data/approved-review.json --authorize-training --output runs/unique-approved-run --seed 42 --test-size 0.2 --folds 5
```

Without both the explicit flag and hash-bound review, training is blocked before fitting or output creation. The Laravel schema companion must match the v5 scope/categories and contain a valid configured criterion. No current real dataset qualifies. Output directories must be new. Test fixtures supply attestations only inside isolated temporary tests.

The six fixed candidates are `logistic_regression`, `decision_tree`, `random_forest`, `gradient_boosting`, `svm`, `knn`; `--models` can explicitly select a subset. Default parameters are recorded. KNN requires at least five fit rows in every fold. This prepares candidate comparison, not automatic selection of a thesis final model: no winner selection, threshold optimization, deployment, or full-data refit occurs. Do not repeatedly optimize against holdout results.

## Outputs and reproducibility

A future authorized run produces `validation.json`, fitted candidate `.joblib` pipelines, `comparison.json` and `manifest.json`. Metrics include confusion matrix, accuracy, balanced accuracy, positive-class precision, recall/sensitivity, specificity, F1 and ROC-AUC. Zero-division policy and score type are explicit. CV metrics and holdout metrics are separate; sample counts, unique student counts, label counts and represented class IDs are recorded for every real partition.

The manifest records input filename/SHA-256, schema/review hashes, contract and implementation hashes, v5 version/cutoff, Git commit/dirty status, UTC timestamp, seed, exact predictor order, holdout fraction/CV configuration, deterministic split-index hash, preprocessing medians/retained/dropped fields per fit, estimators/hyperparameters, metrics, model hashes and software versions. No group keys or row-level predictions are exported. Same file, seed, pinned dependencies and source reproduce partitions; bit-for-bit model identity is not guaranteed across platforms.

Failed runs retain a manifest marked failed and must never be interpreted as completed results. No existing run files are overwritten. Models are pickle-based joblib artifacts: load only trusted artifacts from controlled runs. These development controls do not replace institutional approval or data-governance review.

Reference behavior: [group-aware validation and train-only transformations](https://scikit-learn.org/1.7/modules/cross_validation.html), [median imputation](https://scikit-learn.org/1.7/modules/generated/sklearn.impute.SimpleImputer.html).

## Explicit training-only imbalance handling

Default `--imbalance none` preserves the original behavior. No strategy is selected automatically from target balance. Choose exactly one strategy, or explicitly list strategies for comparison:

| Strategy | Behavior |
| --- | --- |
| `none` | No weighting or sampling. |
| `class_weight` | Native `class_weight="balanced"` for Logistic Regression, Decision Tree, Random Forest and SVM. Gradient Boosting and KNN remain unchanged; the manifest explicitly reports unsupported weighting. No sample-weight workaround is silently introduced. |
| `random_over` | Seeded `RandomOverSampler` duplicates minority training rows to match the majority. No smoothed-bootstrap perturbation. |
| `smote` | Seeded SMOTE interpolates minority training feature-space points after training-only median imputation and scaling. Default `--smote-k-neighbors 5`; insufficient minority counts fail before run creation/fitting. Explicitly choose a smaller valid value or another strategy; parameters never change automatically. |

Required order: validate real labeled file → grouped holdout and grouped CV partition planning → fit each training fold's imputer/scaler → fit-only sampler → classifier. Resampling uses `imblearn.pipeline.Pipeline`; validation/test prediction skips the sampler. Preprocessors learn from real training rows before oversampling, not from augmented values. No sampler sees holdout data, validation data, identifiers, class metadata or group keys. The validation/test target distributions are never balanced. Resampling operates on training targets already present in the approved input; it neither derives new labels from X nor changes the target vocabulary.

SMOTE rows are interpolated numerical points, **not genuine students or new research observations**. Count features may become fractional and interpolated combinations may be educationally implausible. Scaling does not solve these concerns, missing-not-at-random evidence, class confounding or unequal opportunities. SMOTE support is a software option requiring explicit methodological review, not an endorsement of its use in this thesis. Never import interpolated points into Laravel or report them as participants.

Future authorized comparison (not performed on research data during implementation):

```powershell
.venv/Scripts/python.exe -m lmms_ml train data/future-labeled.csv --schema data/future-labeled.schema.json --review data/approved-review.json --authorize-training --output runs/unique-approved-comparison --models random_forest --strategies none class_weight random_over smote --smote-k-neighbors 5 --seed 42 --test-size 0.2 --folds 5
```

All variants reuse the exact same real holdout indices and CV folds, prepared once before any sampling. `comparison.json` lists strategies, real/effective row counts, added training rows, mean CV metrics and untouched holdout metrics; it does not rank/select a winner. Do not tune strategies, seeds, neighbors or thresholds against holdout results. Any future tuning must be confined to training-group CV and separately approved.

The manifest retains `real_total_rows`, `real_unique_students`, `real_train_rows`, `real_test_rows`, and real target counts in each partition. Every fitted variant/fold separately records `resampling_strategy`, original/resampled class counts, `effective_training_rows_after_resampling`, effective target counts, duplicated/interpolated training rows, whether interpolation generated points, and `test_synthetic_rows=0` / `evaluation_resampled_rows=0`. CV validation support also remains real and is explicitly recorded. Class weighting leaves effective row counts unchanged and records the actual balanced weights. Effective training rows must never be called participants or used as an increased study sample size. Unique students and real student-class observations remain distinct quantities.

The existing authorization/provenance gates remain mandatory. Tests use only temporary fabricated software fixtures, reuse their fixed target labels, and verify that samplers are never invoked by evaluation. No database, real label creation or final model training is needed to exercise this support.

Reference: [imbalanced-learn Pipeline](https://imbalanced-learn.org/stable/references/pipeline.html), [oversampling semantics](https://imbalanced-learn.org/stable/over_sampling.html).
