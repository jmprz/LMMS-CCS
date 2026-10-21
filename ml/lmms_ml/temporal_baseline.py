"""Explicitly authorized, file-only OOP baseline; official-grade gates stay intact."""
from __future__ import annotations

import argparse
import csv
import json
import warnings
from pathlib import Path

import joblib
import numpy as np

from .dataset import sha256
from .imbalance import imbalance_record
from .modeling import MODEL_NAMES, build_pipeline, evaluate, prepare_splits, preprocessing_record
from .runner import make_manifest, write_json
from .temporal_dataset import load_temporal_dataset, POLICY_VERSION

DATASET_HASH = "2968cb244e7ab89b38f35a1b54719d5f32cccec90373e254da8b0f397c549a92"
SCHEMA_HASH = "87e402c03bb4a24934f5089ffd23c24d42375384766101283991d3d58892a95c"
PROVENANCE_HASH = "0756487966cabce556dfd0980cc769198eb5f87fa055e20dff3130e960c1986b"
METRICS = ("accuracy", "precision", "recall_sensitivity", "specificity", "f1", "balanced_accuracy", "roc_auc")
EXCLUDED_MISSING = {"activity_duration_seconds", "attendance_rate"}
LIMITATIONS = [
    "Of 37 at-risk observations, 36 reflect verified mapped posttest nonparticipation and only one reflects below-70 performance. Models may primarily capture later nonparticipation, not low-score performance.",
    "108 genuine students from two classes are a small, class-specific cohort; no population-wide or external-validation claim is supported.",
    "Only five at-risk students occur in the 22-student holdout. One changed risk prediction changes sensitivity by 20 percentage points.",
    "GroupShuffleSplit is seeded and group-disjoint, not explicitly stratified. Both labels are checked; holdout balance differs from training balance.",
    "OOPRG_2A is experimental and OOPRG_2B control. Assignment is never X, but intervention, class differences and opportunity differences may confound interpretation.",
    "Missingness is substantial for task and reading averages. Existing reading durations may include legacy measurements; numeric coverage does not establish verified active engagement.",
    "Activity duration and optional attendance are all missing and excluded without zero substitution. Structured topic JSON has no approved scalar encoding and is not X.",
    "No hyperparameter tuning, automatic winner selection, threshold optimization, full-data refit or deployment. Holdout metrics are descriptive and must not drive final model selection.",
    "This research-observed temporal target is not an official institutional final grade. No additional offline exemption documents were supplied.",
]


def verify_inputs(dataset_path, schema_path, provenance_path):
    for path, expected in [(dataset_path, DATASET_HASH), (schema_path, SCHEMA_HASH), (provenance_path, PROVENANCE_HASH)]:
        if sha256(path) != expected:
            raise ValueError("Input hash differs from the explicitly authorized genuine cohort.")
    data = load_temporal_dataset(dataset_path, schema_path)
    provenance = json.loads(provenance_path.read_text(encoding="utf-8"))
    if (data.report["total_rows"] != 108 or data.report["unique_students"] != 108 or
        data.report["class_count"] != 2 or data.report["target_counts"] != {"at_risk": 37, "not_at_risk": 71} or
        data.report["risk_reason_counts"] != {"nonparticipation": 36, "below_threshold": 1, "meets_threshold": 71} or
        provenance.get("dataset_sha256") != DATASET_HASH or
        provenance.get("artifact_kind") != "genuine_research_temporal_cohort" or
        provenance.get("official_institutional_grade") is not False):
        raise ValueError("Cohort counts or provenance differ from the authorized baseline.")
    return data


def baseline_x(data):
    # User explicitly approved excluding these unavailable measurements for this run.
    # Collection formulas and the authoritative eleven-core-field v5 scope are unchanged.
    if set(data.report["all_missing_predictors"]) != EXCLUDED_MISSING:
        raise ValueError("All-missing scope changed; review before fitting.")
    return data.x().drop(columns=["activity_duration_seconds"])


def indices_record(train, test, cv):
    return {"position_basis": "zero-based row position in the SHA-256-bound input CSV; header excluded",
            "train": train.tolist(), "test": test.tolist(),
            "cv": [{"fit": a.tolist(), "validation": b.tolist()} for a, b in cv]}


def run(dataset_path, schema_path, provenance_path, output, *, authorize=False):
    if not authorize:
        raise ValueError("Explicit --authorize-training required for this user-approved baseline.")
    data = verify_inputs(dataset_path, schema_path, provenance_path)
    x, y = baseline_x(data), data.y
    train, test, cv = prepare_splits(data, seed=42, test_size=0.2, folds=5)
    for fit, _ in [(train, test), *cv]:
        if len(fit) < 5 or not x.iloc[fit].notna().any().any():
            raise ValueError("Infeasible training partition; no seed search or row fallback.")
    feature_path = schema_path.parent / json.loads(schema_path.read_text(encoding="utf-8"))["source_feature_filename"]
    bound = {p: sha256(p) for p in [dataset_path, schema_path, provenance_path, feature_path]}
    manifest = make_manifest(data, 42, train, test, cv, list(x.columns))
    manifest.update(dataset_contract="oop-temporal-v1", target_policy_version=POLICY_VERSION,
                    export_schema_sha256=SCHEMA_HASH, provenance_sha256=PROVENANCE_HASH,
                    authorization="Explicit user request: Run First Genuine Temporal Baseline Training, 2026-10-21",
                    source_schema_training_authorized=False,
                    authorization_scope="This separate run only; source validation-only schema and official-grade training contract unchanged",
                    input_hashes={p.name: h for p, h in bound.items()},
                    test_size=0.2, cv_folds=5, resampling_strategy="none", imbalance_strategies=["none"],
                    training_synthetic_rows=0, added_training_rows=0,
                    excluded_all_missing=sorted(EXCLUDED_MISSING),
                    missingness=data.report["predictors"], constant_predictors=data.report["constant_predictors"],
                    risk_reason_counts=data.report["risk_reason_counts"], limitations=LIMITATIONS,
                    selection_policy="All six fixed candidates; no tuning, automatic winner selection or full-data refit",
                    database_access="none; Python reads explicit local files only")
    output.mkdir(parents=True, exist_ok=False)
    caught = []
    try:
        write_json(output / "validation.json", data.report)
        write_json(output / "split-indices.json", indices_record(train, test, cv))
        manifest["files_created"] += ["validation.json", "split-indices.json"]
        comparison = []
        with warnings.catch_warnings(record=True) as caught:
            warnings.simplefilter("always")
            for name in MODEL_NAMES:
                folds = []
                for fit, valid in cv:
                    pipeline = build_pipeline(name, seed=42, strategy="none")
                    pipeline.fit(x.iloc[fit], y[fit])
                    folds.append({"metrics": evaluate(pipeline, x.iloc[valid], y[valid]),
                                  "preprocessing": preprocessing_record(pipeline),
                                  "imbalance": imbalance_record(pipeline, y[fit], "none"),
                                  "real_validation_rows": len(valid), "validation_synthetic_rows": 0})
                pipeline = build_pipeline(name, seed=42, strategy="none")
                pipeline.fit(x.iloc[train], y[train])
                parameters = pipeline.named_steps["model"].get_params()
                if parameters.get("class_weight") is not None or "sampler" in pipeline.named_steps:
                    raise ValueError("Baseline unexpectedly weighted or sampled.")
                result = {"estimator": type(pipeline.named_steps["model"]).__name__, "hyperparameters": parameters,
                          "preprocessing": preprocessing_record(pipeline),
                          "imbalance": imbalance_record(pipeline, y[train], "none"), "cv": folds,
                          "cv_mean_metrics": {k: float(np.mean([r["metrics"][k] for r in folds])) for k in METRICS},
                          "cv_std_metrics": {k: float(np.std([r["metrics"][k] for r in folds], ddof=0)) for k in METRICS},
                          "cv_std_definition": "population standard deviation across five folds; not a confidence interval",
                          "holdout_metrics": evaluate(pipeline, x.iloc[test], y[test])}
                joblib.dump(pipeline, output / (name + ".joblib"))
                result["model_sha256"] = sha256(output / (name + ".joblib"))
                write_json(output / (name + ".metrics.json"), result)
                manifest["algorithms"][name] = result
                manifest["files_created"] += [name + ".joblib", name + ".metrics.json"]
                comparison.append({"algorithm": name, "strategy": "none", "cv_mean_metrics": result["cv_mean_metrics"],
                                   "cv_std_metrics": result["cv_std_metrics"], "holdout_metrics": result["holdout_metrics"]})
        write_json(output / "comparison.json", {"variants": comparison, "selection_policy": manifest["selection_policy"]})
        with (output / "comparison.csv").open("x", encoding="utf-8", newline="") as stream:
            writer = csv.DictWriter(stream, fieldnames=["algorithm", *METRICS, *["cv_mean_"+k for k in METRICS], *["cv_std_"+k for k in METRICS]])
            writer.writeheader()
            for row in comparison:
                writer.writerow({"algorithm": row["algorithm"], **{k: row["holdout_metrics"][k] for k in METRICS},
                                 **{"cv_mean_"+k: row["cv_mean_metrics"][k] for k in METRICS},
                                 **{"cv_std_"+k: row["cv_std_metrics"][k] for k in METRICS}})
        write_json(output / "confusion-matrices.json", {row["algorithm"]: {"order": ["not_at_risk", "at_risk"],
                   "axes": "actual rows, predicted columns", "matrix": row["holdout_metrics"]["confusion_matrix"]} for row in comparison})
        (output / "limitations.txt").write_text("\n\n".join(LIMITATIONS)+"\n", encoding="utf-8")
        manifest["files_created"] += ["comparison.json", "comparison.csv", "confusion-matrices.json", "limitations.txt"]
        if any(sha256(p) != digest for p, digest in bound.items()):
            raise ValueError("Input changed during run; results invalid.")
        manifest["inputs_unchanged"] = True
        manifest["status"] = "completed_candidate_evaluation"
    except Exception:
        manifest["status"] = "failed; partial artifacts are not completed results"
        raise
    finally:
        manifest["runtime_warnings"] = sorted({str(w.message) for w in caught})
        manifest["files_created"].append("manifest.json")
        write_json(output / "manifest.json", manifest)
    return manifest


def main(argv=None):
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("dataset", type=Path)
    parser.add_argument("--schema", type=Path, required=True)
    parser.add_argument("--provenance", type=Path, required=True)
    parser.add_argument("--output", type=Path, required=True)
    parser.add_argument("--authorize-training", action="store_true")
    args = parser.parse_args(argv)
    result = run(args.dataset.resolve(), args.schema.resolve(), args.provenance.resolve(), args.output.resolve(),
                 authorize=args.authorize_training)
    print(json.dumps({"status": result["status"], "train": result["train"], "test": result["test"],
                      "predictors": result["predictors"], "output": str(args.output)}, indent=2))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
