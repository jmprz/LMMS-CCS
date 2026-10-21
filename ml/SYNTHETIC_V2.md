# FPROG candidate v2

This is an unlabeled, feature-space development simulation. It adds no real
participants or observations and is not eligible for academic-risk training.
Production v5 formulas, configuration, collection and outcome rules are unchanged.
The rejected v1 run remains preserved.

## Reproduction

Run from `ml/` with the existing pinned environment. Outputs must not already exist.
These commands use CSV input only and never connect to a database.

```powershell
.venv/Scripts/python.exe -m lmms_ml synthetic ../storage/app/risk-datasets/all-classes-20261020-223311-v5-ml-ready.csv --schema lmms_ml/contract.json --scenario fprog_1a_v2 --rows 60 --seed 20261020 --output data/synthetic/fprog1a-synthetic-v2-seed20261020.csv
.venv/Scripts/python.exe -m lmms_ml.synthetic_audit --real ../storage/app/risk-datasets/all-classes-20261020-223311-v5-ml-ready.csv --v1 data/synthetic/fprog1a-synthetic-v1-seed20261020.csv --v2 data/synthetic/fprog1a-synthetic-v2-seed20261020.csv
.venv/Scripts/python.exe -m pytest tests -q
```

Use a new basename for any explicitly authorized reproduction. Do not overwrite
the candidate or regenerate to improve diagnostic results. No combined v2 file
was created.

## Measurement rules and assumptions

`scenarios/fprog_1a_v2.json` retains the six configurable overlapping profiles and
their 15/30/25/15/10/5 mixture. At 60 rows this yields 9/18/15/9/6/3 rows.
`lmms_ml/synthetic_v2.py` versions the revised mechanics separately from v1.

- Two virtual quiz opportunities are configured, but represented quiz evidence is
  capped at one, consistent with this reference export. A count of zero means
  no represented quiz score. Five-point scores come from profile/aggregate-mean
  blends and fresh random noise; real rows are not copied. Scores can reach the
  valid maximum 100 even though the reference observed maximum is 95.
- Quiz-presence probability uses pooled FPROG coverage with a modest profile
  engagement modifier. The pool includes a class without quiz opportunities.
  This is an explicit simulation choice, not an estimate of individual absence
  or a reconstruction of actual FPROG_1A assessment availability.
- One virtual task permits submission rates 0 or 100. The reference has 120
  submitted/graded records (seven zeros, 113 hundreds) and six non-submissions
  with missing grades. Only these measurement states are generated. Zero grades
  are submitted/graded states and never a substitute for missing grades.
- Missing task slots use rounded empirical missingness (three at 60 rows), plus
  a modest irregular-profile increment (one). Base missing slots are weighted
  toward lower submission propensity. Near-reference grade completeness limits
  how low submission coverage can be: arbitrary extra non-submissions would
  contradict the requested completeness. The seed produces 56 submitted/graded
  rows, including one zero grade, and four non-submissions.
- The probability of a submitted zero grade is the empirical zero fraction with
  a modest profile modifier. No outcome or academic-risk status is generated
  from scores or profiles. Binary task support limits lower-middle task-score
  representation; lower-middle quiz scores provide that variation instead.
- Lateness blends pooled conditional lateness (80%) and profile propensity
  (20%). The two real classes have sharply different practices; pooled behavior
  is not evidence of equivalent deadlines or teaching practices.
- Material-access, material-session and activity-event draws are exactly those
  of v1 for this seed. No verified attendance, active material duration, active
  activity duration or topic-task evidence is fabricated. Reading totals retain
  the generator's zero placeholder; reading averages and optional attendance/
  activity duration remain NULL. Zero totals do not establish verified exposure.
- Unsupported structured topic fields remain `{}`. Provenance and grouping
  metadata remain outside the scalar predictor allowlist. No grade threshold,
  outcomes or target labels are added.

The generator refuses reference data that does not support the reviewed five-point
quiz, 0/1 represented count, binary task and one-task submission mechanics.

## Candidate review

The real input has 455 rows / 446 unique students; the FPROG reference has 126
rows. Reference and synthetic counts must never be added to claim participants.

| Measurement | Real FPROG | Preserved v1 | Candidate v2 |
| --- | ---: | ---: | ---: |
| Rows | 126 | 60 | 60 |
| Quiz score mean, observed only | 80.44 | 65.70 | 70.78 |
| Quiz score median | 85 | 69.11 | 72.5 |
| Quiz score SD | 17.99 | 23.73 | 22.11 |
| Off-grid quiz scores | 0 | 51 | 0 |
| Quiz missingness | 54.76% | 11.67% | 46.67% |
| Represented quiz count range | 0–1 | 0–2 | 0–1 |
| Task grade support | 0/100 | arbitrary decimals | 0/100 |
| Task missingness | 4.76% | 38.33% | 6.67% |
| Submission-rate mean | 95.24% | 73.33% | 93.33% |
| Late rows | 61 | 9 | 27 |
| Materials accessed mean | 0.651 | 0.650 | 0.650 |
| Material sessions mean | 1.563 | 1.517 | 1.517 |
| Activity count mean | 2.516 | 2.217 | 2.217 |

V2 observed quiz values: 25, 35, 40, 50, 55, 60, 65, 70, 75, 85, 90, 95, 100.
The six scored strong-profile rows average 91.67 and overlap the real upper range.
The two struggling profiles average 55.71 and 49 among their scored rows.
Small per-profile samples mean empirical profile means need not be ordered.

Full stats, sample SD, quartiles, support, missingness and Pearson/Spearman paired
correlations are in the audit JSON/CSV. V2 has no absolute standardized mean
difference at least 1.5, nonoverlapping observed range, or missingness gap at least
50 percentage points under the existing inspection rules. These flags do not
establish realism. No v2 predictor pair has absolute correlation at least 0.95
with at least five complete pairs. Real FPROG_1C has perfect submission/lateness
correlation, a class-specific constraint rather than a desired synthetic target.

## Synthetic realism diagnostic only

Fixed logistic regression and a depth-three decision tree distinguish real from
synthetic origin. This response exists temporarily in memory only. It is not an
academic-risk label and is never exported into feature CSVs. No model is saved.

Five-fold stratified student-grouped validation keeps all rows for each real
student together. Synthetic virtual units are separate groups. Each fold fits
median imputation, missing indicators and scaling using training rows only.
Only the nine scalar predictors observed in both populations are included:
quiz average/count, task average, submission rate, lateness, material access/
sessions, reading total and activity count. Reading average, activity duration
and attendance are excluded because the synthetic side has no observations.
Provenance, class, profiles, group keys, outcomes and academic targets are never X.

| Diagnostic | v1 balanced accuracy / ROC AUC | v2 balanced accuracy / ROC AUC |
| --- | ---: | ---: |
| Logistic regression | 0.931 / 0.969 | 0.521 / 0.564 |
| Shallow decision tree | 0.932 / 0.961 | 0.671 / 0.764 |

The predeclared development review flag is balanced accuracy >=0.90 or ROC AUC
>=0.95 for either fixed model. V1 triggers it; v2 does not. The shallow-tree result
still shows moderate distinguishability. This is a small descriptive check:
reference aggregates used for generation include diagnostic validation students,
and real legacy reading totals differ from unsupported synthetic duration. It is
not independent validation or proof that augmentation helps risk prediction.

**Decision: ACCEPT v2 as candidate synthetic FPROG augmentation for development
review only.** Keep it separate. It is unlabeled and not authorized for final ML
training, participant-count claims or held-out research evaluation. No automatic
regeneration or final-dataset combination was performed.

## Integrity

The `.integrity.json` companion records before/after hashes for the original
real input, every preserved v1 artifact, baseline files and research sources;
source hashes, dependency versions and execution commands are included.
The v1 CSV was also reproduced in memory byte for byte without rewriting it.
No database connection, SQL, Artisan command, outcome/label generation, or final
academic-risk model training was performed during this task.
