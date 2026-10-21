"""Synthetic realism diagnostic only. Never trains an academic-risk model or writes labels."""
import argparse
import csv
import json
from pathlib import Path

import numpy as np
import pandas as pd
from sklearn.impute import SimpleImputer
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import balanced_accuracy_score, f1_score, roc_auc_score
from sklearn.model_selection import StratifiedGroupKFold
from sklearn.pipeline import make_pipeline
from sklearn.preprocessing import StandardScaler
from sklearn.tree import DecisionTreeClassifier

from .dataset import CONTRACT, load_dataset, sha256
from .synthetic import OUTPUT_ROOT, OUTPUT_COLUMNS, distributions, stats, validate_synthetic


def origin_diagnostic(real, generated, seed=20261020):
    """OOF fixed models, train-only preprocessing; origin is a temporary diagnostic response."""
    features = [f for f in CONTRACT["core"] + CONTRACT["optional"]
                if real[f].notna().any() and generated[f].notna().any()]
    if not features:
        raise ValueError("No shared observed scalar predictors for origin diagnostic.")
    x = pd.concat([real[features], generated[features]], ignore_index=True).astype(float)
    y = np.r_[np.zeros(len(real), dtype=int), np.ones(len(generated), dtype=int)]
    # Same real person stays together, even across classes. Synthetic rows are independent virtual units.
    groups = np.r_["REAL-" + real.student_group_key.astype(str).to_numpy(),
                   [f"VIRTUAL-{i}" for i in range(len(generated))]]
    splitter = StratifiedGroupKFold(n_splits=5, shuffle=True, random_state=seed)
    splits = list(splitter.split(x, y, groups))
    if any(len(np.unique(y[train])) < 2 or len(np.unique(y[test])) < 2 for train, test in splits):
        raise ValueError("Five-fold origin diagnostic needs both origins in every training/validation fold.")
    result = {"purpose": "synthetic realism diagnostic only", "not_thesis_performance": True,
              "response": "temporary real=0 / synthetic=1, never written into datasets",
              "scalar_predictors": features,
              "excluded_no_shared_observations": [f for f in CONTRACT["core"] + CONTRACT["optional"] if f not in features],
              "provenance_metadata_or_academic_labels_in_x": False,
              "real_rows": len(real), "synthetic_rows": len(generated),
              "real_unique_students": real.student_group_key.nunique(),
              "validation": "5-fold stratified student-grouped out-of-fold; fixed models, no tuning",
              "preprocessing": "median imputation with missing indicators, then scaling, fitted only on each training fold; stored CSV NULLs unchanged",
              "limitations": ["Small candidate; descriptive development diagnostic, not an independent realism validation.",
                              "Synthetic generation uses aggregates of the full reference pool, including diagnostic validation students.",
                              "No calibration or regeneration based on this result; scores are not academic-risk performance."],
              "review_thresholds_not_risk_rules": {"balanced_accuracy": 0.90, "roc_auc": 0.95},
              "models": {}}
    factories = {
        "logistic_regression": lambda: LogisticRegression(class_weight="balanced", max_iter=2000, random_state=seed),
        "shallow_decision_tree": lambda: DecisionTreeClassifier(max_depth=3, min_samples_leaf=5, class_weight="balanced", random_state=seed),
    }
    for name, factory in factories.items():
        probabilities = np.full(len(x), np.nan)
        fold_reports = []
        for train, test in splits:
            overlap = len(set(groups[train]) & set(groups[test]))
            if overlap:
                raise ValueError("Origin diagnostic group leakage.")
            pipeline = make_pipeline(SimpleImputer(strategy="median", add_indicator=True, keep_empty_features=True),
                                     StandardScaler(), factory())
            pipeline.fit(x.iloc[train], y[train])
            probability = pipeline.predict_proba(x.iloc[test])[:, 1]
            probabilities[test] = probability
            fold_reports.append({"train_rows": len(train), "validation_rows": len(test), "group_overlap": overlap,
                                 "balanced_accuracy": balanced_accuracy_score(y[test], probability >= 0.5),
                                 "roc_auc": roc_auc_score(y[test], probability)})
        if not np.isfinite(probabilities).all():
            raise ValueError("Incomplete diagnostic validation predictions.")
        balanced = float(balanced_accuracy_score(y, probabilities >= 0.5))
        auc = float(roc_auc_score(y, probabilities))
        result["models"][name] = {"oof_balanced_accuracy": balanced, "oof_roc_auc": auc,
                                  "oof_macro_f1": float(f1_score(y, probabilities >= 0.5, average="macro")),
                                  "extremely_easy_origin_flag": bool(balanced >= 0.90 or auc >= 0.95), "folds": fold_reports}
    result["extremely_easy_origin_flag"] = any(m["extremely_easy_origin_flag"] for m in result["models"].values())
    return result


def expanded_stats(series):
    report = stats(series)
    observed = pd.to_numeric(series, errors="raise").dropna()
    report.update({"p25": float(observed.quantile(0.25)) if len(observed) else None,
                   "p75": float(observed.quantile(0.75)) if len(observed) else None,
                   "zero_count": int(observed.eq(0).sum()),
                   "unique_observed_values": sorted(map(float, observed.unique()))})
    return report


def correlations(frame):
    columns = CONTRACT["core"] + CONTRACT["optional"]
    result = {}
    for method in ("pearson", "spearman"):
        matrix = frame[columns].corr(method=method, min_periods=5)
        pairs = []
        for i, a in enumerate(columns):
            for b in columns[i + 1:]:
                value = matrix.loc[a, b]
                count = int((frame[a].notna() & frame[b].notna()).sum())
                if pd.notna(value):
                    pairs.append({"a": a, "b": b, "paired_rows": count, "correlation": float(value),
                                  "near_deterministic_flag": bool(abs(value) >= 0.95)})
        result[method] = pairs
    return result


def read_candidate(path):
    path = Path(path).resolve(strict=True)
    manifest = json.loads(path.with_suffix(".manifest.json").read_text(encoding="utf-8"))
    if manifest["output_sha256"] != sha256(path) or manifest.get("artifact_kind") != "synthetic_unlabeled_features":
        raise ValueError("Candidate hash/provenance mismatch.")
    frame = pd.read_csv(path)
    validate_synthetic(frame, manifest)
    if len(frame) != manifest["generated_rows"]:
        raise ValueError("Candidate row count mismatch.")
    return frame, manifest


def audit(real_path, v1_path, v2_path):
    dataset = load_dataset(real_path, labeled=False)
    v1, m1 = read_candidate(v1_path)
    v2, m2 = read_candidate(v2_path)
    if m1["scenario"] != "fprog_1a_v1" or m2["scenario"] != "fprog_1a_v2":
        raise ValueError("Explicit preserved v1 and new v2 candidates required.")
    if any(m["input_sha256"] != dataset.report["dataset_sha256"] for m in (m1, m2)):
        raise ValueError("Candidates must share the exact immutable real input.")
    if m1["random_seed"] != m2["random_seed"] or len(v1) != len(v2):
        raise ValueError("Paired revision audit requires the same seed and row count.")
    real = dataset.frame[dataset.frame.class_code.isin(m2["source_class_codes"])]
    populations = {"real_fprog_combined": real, **{code: group for code, group in real.groupby("class_code")},
                   "synthetic_v1": v1, "synthetic_v2": v2}
    comparison = distributions(real, v2)
    diagnostic = {"v1": origin_diagnostic(real, v1), "v2": origin_diagnostic(real, v2)}
    reasons = list(comparison["flags"])
    if diagnostic["v2"]["extremely_easy_origin_flag"]:
        reasons.append("Origin extremely easy to classify in at least one fixed diagnostic model.")
    decision = "REVISE again" if reasons else "ACCEPT v2 as candidate synthetic FPROG augmentation"
    preserved_draws = {c: bool(v1[c].equals(v2[c])) for c in ("materials_accessed", "material_session_count", "activity_count")}
    if not all(preserved_draws.values()):
        raise ValueError("Accepted v1 engagement draws unexpectedly changed.")
    report = {"purpose": "synthetic development candidate audit, not research evidence or model performance",
              "real_input_sha256": dataset.report["dataset_sha256"], "real_rows_all_classes": len(dataset.frame),
              "real_unique_students_all_classes": dataset.frame.student_group_key.nunique(),
              "reference_rows": len(real), "reference_unique_students": real.student_group_key.nunique(),
              "candidate_rows": len(v2), "seed": m2["random_seed"], "feature_version": "v5",
              "preserved_engagement_draws": preserved_draws,
              "summary": {name: {f: expanded_stats(frame[f]) for f in CONTRACT["core"] + CONTRACT["optional"]}
                          for name, frame in populations.items()},
              "correlations": {name: correlations(frame) for name, frame in populations.items()},
              "v2_vs_real": comparison, "origin_diagnostic": diagnostic,
              "profiles": {name: {"rows": len(group), "quiz": expanded_stats(group.quiz_avg),
                                   "task": expanded_stats(group.task_avg), "submission": expanded_stats(group.submission_rate)}
                           for name, group in v2.groupby("synthetic_profile")},
              "mechanics": {"quiz_off_grid": int((v2.quiz_avg.dropna() % 5 != 0).sum()),
                            "attempts_above_observed_max": int(v2.quiz_attempt_count.gt(real.quiz_attempt_count.max()).sum()),
                            "invalid_task_support": int((~v2.task_avg.dropna().isin([0, 100])).sum()),
                            "missing_task_with_submission": int((v2.task_avg.isna() & v2.submission_rate.gt(0)).sum()),
                            "zero_grade_without_submission": int((v2.task_avg.eq(0) & v2.submission_rate.ne(100)).sum())},
              "limitations": ["Virtual opportunities and synthetic profiles are not real exposures, observations, participants or outcomes.",
                              "Pooled quiz coverage includes a structural no-quiz class; pooled lateness spans very different class practices.",
                              "Binary task support and low missingness limit lower-middle task and submission representation.",
                              "Legacy reading values in real data can distinguish origins; verified durations remain unsupported, never fabricated.",
                              "Correlations from few observed paired scores are unstable; deterministic measurement constraints are expected."],
              "review": {"decision": decision, "reasons": reasons, "training_eligible": False,
                         "automatic_regeneration": False, "combined_into_final_ml_data": False,
                         "next_step": "Human methodology review before any revision or use; real-only held-out evaluation remains required."}}
    base = Path(v2_path).resolve()
    if not base.is_relative_to(OUTPUT_ROOT.resolve()):
        raise ValueError("Audit outputs must remain under synthetic development root.")
    outputs = [base.with_suffix(s) for s in (".audit.json", ".audit.csv", ".review.json")]
    if any(p.exists() for p in outputs):
        raise FileExistsError("Audit collision; no overwrite permitted.")
    inputs = {Path(p): sha256(Path(p)) for p in (real_path, v1_path, v2_path)}
    with outputs[0].open("x", encoding="utf-8") as stream:
        json.dump(report, stream, indent=2, allow_nan=False)
        stream.write("\n")
    with outputs[1].open("x", encoding="utf-8", newline="") as stream:
        writer = csv.DictWriter(stream, fieldnames=["population", "predictor"] + list(expanded_stats(v2.quiz_avg)))
        writer.writeheader()
        for population, summary in report["summary"].items():
            for feature, values in summary.items():
                writer.writerow({"population": population, "predictor": feature, **values})
    with outputs[2].open("x", encoding="utf-8") as stream:
        json.dump(report["review"], stream, indent=2, allow_nan=False)
        stream.write("\n")
    if any(sha256(p) != digest for p, digest in inputs.items()):
        raise ValueError("Input changed during audit; reports must not be used.")
    return report


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--real", type=Path, required=True)
    parser.add_argument("--v1", type=Path, required=True)
    parser.add_argument("--v2", type=Path, required=True)
    args = parser.parse_args()
    result = audit(args.real, args.v1, args.v2)
    print(json.dumps({"review": result["review"], "origin_diagnostic": result["origin_diagnostic"]}, indent=2))
