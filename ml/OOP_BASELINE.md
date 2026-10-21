# First genuine OOP temporal baseline

The user explicitly authorized this baseline on 2026-10-21 in the request
“Run First Genuine Temporal Baseline Training”. This is research-observed
later-posttest risk, not an institutional final-grade training run. The original
validation-only cohort/schema remain unchanged. The default official-grade
training command and its provenance gates remain unchanged.

`lmms_ml.temporal_baseline` is a separate adapter pinned to the reviewed dataset,
schema and provenance SHA-256 values. It requires `--authorize-training`, verifies
108 genuine unique students, target counts 37/71 and reasons 36/1/71, and uses
the existing strict temporal loader. It has no database access, outcome generation,
augmentation, weighting or resampling option. Changed inputs require a new review.

From `ml/`, the authorized run is:

```powershell
.venv/Scripts/python.exe -m lmms_ml.temporal_baseline ../storage/app/risk-datasets/oop-temporal-labeled-v1.csv --schema ../storage/app/risk-datasets/oop-temporal-labeled-v1.schema.json --provenance ../storage/app/risk-datasets/oop-temporal-labeled-v1.provenance.json --output results/oop-temporal-baseline-v1 --authorize-training
```

The output directory must not exist; reruns must use a newly reviewed unique path.
The existing seeded GroupShuffleSplit (seed 42, test size 0.2) is preserved, with
five-fold StratifiedGroupKFold only on training students. The holdout is group-aware,
not explicitly stratified; both labels and zero student overlap are checked.
No seed search, hyperparameter tuning or automatic model selection occurs.

The existing six pipelines and baseline parameters are reused without changes.
Activity duration and optional attendance are entirely missing and are excluded
for this run, without changing the frozen collection scope. The other ten core
scalars enter X. Missing numeric values use training-fold medians; legitimate
zeros remain zero. Scaling applies only to Logistic Regression, SVM and KNN.
Fold-specific all-missing exclusions remain handled by the existing imputer.

Outputs include model pipelines, per-model metrics and fold results, comparison
CSV/JSON, confusion matrices, missingness validation, exact zero-based row split
indices, limitations and a manifest. CV variability is population standard
deviation across five folds, not a confidence interval. Saved pipelines are fitted
on the training partition only, never the full cohort. Treat joblib models as
trusted local artifacts; do not load untrusted pickle files.

Of 37 at-risk observations, 36 are verified nonparticipation and only one is
below-threshold performance. Findings primarily concern this combined endpoint;
they do not establish low-grade prediction performance. Two classes, intervention
differences, missing evidence, and only five at-risk holdout students limit
interpretation. Holdout inspection must not drive iterative model/threshold tuning.
