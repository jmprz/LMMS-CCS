# Genuine OOP temporal baseline v1

Completed one authorized candidate comparison; no tuning, final selection or full-data refit.

## Dataset and partition

108 genuine rows / 108 unique students / 2 classes. 37 at-risk (34.26%), 71 not-at-risk (65.74%).
Prediction cutoff: 2026-10-15 17:59:59 UTC+08.
Dataset SHA-256: 2968cb244e7ab89b38f35a1b54719d5f32cccec90373e254da8b0f397c549a92
Schema SHA-256: 87e402c03bb4a24934f5089ffd23c24d42375384766101283991d3d58892a95c
Provenance SHA-256: 0756487966cabce556dfd0980cc769198eb5f87fa055e20dff3130e960c1986b
Git commit: 8361dcd4f1f864f2c7ca91c105be79b9eed52a7e; worktree contains uncommitted/untracked code. Executed implementation hashes are in manifest.json.

Seed 42; GroupShuffleSplit 20% holdout; five-fold StratifiedGroupKFold within training students only.
Train: 86 (32 at-risk, 54 not-at-risk). Test: 22 (5 at-risk, 17 not-at-risk). Student overlap: 0.
Holdout is group-aware, not explicitly stratified. Both labels occur in every partition.
No weighting, resampling, augmentation, seed search or hyperparameter tuning.

## Predictors

quiz_avg, quiz_attempt_count, task_avg, submission_rate, late_submission_count, materials_accessed, material_session_count, reading_total_seconds, reading_avg_seconds, activity_count

All-missing activity_duration_seconds and optional attendance_rate excluded without zero substitution. No constants. No metadata, targets or topic JSON enter X.
Training-fold median imputation. StandardScaler only for Logistic Regression, SVM and KNN. Trees remain unscaled. No additional columns dropped in CV.

| Predictor | Missing / 108 | Missing % |
|---|---:|---:|
| quiz_avg | 12 | 11.11 |
| quiz_attempt_count | 0 | 0.00 |
| task_avg | 55 | 50.93 |
| submission_rate | 53 | 49.07 |
| late_submission_count | 0 | 0.00 |
| materials_accessed | 0 | 0.00 |
| material_session_count | 0 | 0.00 |
| reading_total_seconds | 0 | 0.00 |
| reading_avg_seconds | 83 | 76.85 |
| activity_count | 0 | 0.00 |
| activity_duration_seconds | 108 | 100.00 |
| attendance_rate | 108 | 100.00 |

## Holdout metrics

Positive class: at_risk. Matrices are [[TN, FP], [FN, TP]]. Rates are percentages; AUC unitless.

| Model | Matrix | Accuracy | Precision | Recall | Specificity | F1 | Balanced accuracy | AUC |
|---|---|---:|---:|---:|---:|---:|---:|---:|
| logistic_regression | [[17, 0], [3, 2]] | 86.36 | 100.00 | 40.00 | 100.00 | 57.14 | 70.00 | 0.782 |
| decision_tree | [[15, 2], [4, 1]] | 72.73 | 33.33 | 20.00 | 88.24 | 25.00 | 54.12 | 0.412 |
| random_forest | [[14, 3], [4, 1]] | 68.18 | 25.00 | 20.00 | 82.35 | 22.22 | 51.18 | 0.559 |
| gradient_boosting | [[16, 1], [4, 1]] | 77.27 | 50.00 | 20.00 | 94.12 | 28.57 | 57.06 | 0.276 |
| svm | [[16, 1], [4, 1]] | 77.27 | 50.00 | 20.00 | 94.12 | 28.57 | 57.06 | 0.688 |
| knn | [[16, 1], [4, 1]] | 77.27 | 50.00 | 20.00 | 94.12 | 28.57 | 57.06 | 0.712 |

## Training-only CV

Mean +/- population standard deviation across five folds; not a confidence interval. Rates are percentages.

| Model | Recall | F1 | Balanced accuracy | AUC |
|---|---:|---:|---:|---:|
| logistic_regression | 29.43 +/- 16.75 | 29.43 +/- 15.10 | 53.37 +/- 5.51 | 0.662 +/- 0.134 |
| decision_tree | 49.30 +/- 13.94 | 48.15 +/- 12.62 | 61.18 +/- 7.63 | 0.654 +/- 0.091 |
| random_forest | 57.38 +/- 29.86 | 51.07 +/- 17.04 | 64.16 +/- 10.59 | 0.655 +/- 0.065 |
| gradient_boosting | 57.94 +/- 31.70 | 47.05 +/- 16.41 | 61.78 +/- 8.43 | 0.588 +/- 0.120 |
| svm | 40.73 +/- 13.80 | 43.73 +/- 11.59 | 60.03 +/- 8.40 | 0.637 +/- 0.111 |
| knn | 49.22 +/- 21.62 | 44.87 +/- 15.02 | 60.38 +/- 8.78 | 0.644 +/- 0.104 |

## Candidates for review

Random Forest has the highest mean CV F1 (0.511) and balanced accuracy (0.642), with substantial recall variability. Gradient Boosting has slightly higher CV recall (0.579 versus 0.574), weaker CV F1/AUC and poor holdout AUC (0.276).
Logistic Regression has the highest mean CV AUC (0.662), but weak mean CV recall (0.294) and F1 (0.294). Its better descriptive holdout results do not establish a final winner.
An always-not-at-risk prediction would achieve 77.27% holdout accuracy and zero risk recall. Accuracy alone is misleading.
No final model selected. Do not tune against this inspected holdout.

## Limitations

- Of 37 at-risk observations, 36 reflect verified mapped posttest nonparticipation and only one reflects below-70 performance. Models may primarily capture later nonparticipation, not low-score performance.
- 108 genuine students from two classes are a small, class-specific cohort; no population-wide or external-validation claim is supported.
- Only five at-risk students occur in the 22-student holdout. One changed risk prediction changes sensitivity by 20 percentage points.
- GroupShuffleSplit is seeded and group-disjoint, not explicitly stratified. Both labels are checked; holdout balance differs from training balance.
- OOPRG_2A is experimental and OOPRG_2B control. Assignment is never X, but intervention, class differences and opportunity differences may confound interpretation.
- Missingness is substantial for task and reading averages. Existing reading durations may include legacy measurements; numeric coverage does not establish verified active engagement.
- Activity duration and optional attendance are all missing and excluded without zero substitution. Structured topic JSON has no approved scalar encoding and is not X.
- No hyperparameter tuning, automatic winner selection, threshold optimization, full-data refit or deployment. Holdout metrics are descriptive and must not drive final model selection.
- This research-observed temporal target is not an official institutional final grade. No additional offline exemption documents were supplied.

## Integrity and tests

139 Python tests passed; one existing Windows CPU-detection warning in a SMOTE software test. No SMOTE in the research run; baseline runtime warnings: none.
All 324 previously fingerprinted files unchanged: cohort companions, frozen files, .env, baseline snapshot, existing synthetic artifacts.
No database connections or writes by this file-only workflow. No Laravel code or database records modified.
Exact split positions, fold medians, estimator parameters, library versions, metrics, source hashes and model hashes retained in companions.
