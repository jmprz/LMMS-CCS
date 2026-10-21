"""Explicitly authorized, file-only future runs. No database drivers or connections."""
from __future__ import annotations

import argparse
import hashlib
import importlib.metadata
import json
import platform
import subprocess
from datetime import datetime, timezone
from pathlib import Path

import joblib
import numpy as np

from .dataset import CONTRACT, CONTRACT_PATH, ValidationError, load_dataset, sha256, validate_schema
from .modeling import (MODEL_NAMES, build_pipeline, evaluate, partition_summary,
                       prepare_splits, preprocessing_record)
from .imbalance import STRATEGIES, check_sampling_feasibility, imbalance_record

ROOT = Path(__file__).resolve().parents[2]


def git_value(*args):
    try:
        return subprocess.check_output(["git", "-c", f"safe.directory={ROOT.as_posix()}", *args], cwd=ROOT, stderr=subprocess.DEVNULL, text=True).strip()
    except (OSError, subprocess.CalledProcessError):
        return None


def make_manifest(dataset, seed, train, test, cv, predictors):
    files = sorted(Path(__file__).parent.glob("*.py")) + [CONTRACT_PATH, ROOT / "ml/requirements.txt", ROOT / "ml/requirements-lock.txt"]
    worktree = git_value("status", "--porcelain")
    return {"status": "prepared", "run_timestamp_utc": datetime.now(timezone.utc).isoformat(),
            "dataset_filename": dataset.path.name, "dataset_sha256": dataset.report["dataset_sha256"],
            "feature_version": "v5", "git_commit": git_value("rev-parse", "HEAD"),
            "git_worktree_dirty": None if worktree is None else bool(worktree),
            "implementation_sha256": {p.name: sha256(p) for p in files},
            "contract_sha256": sha256(CONTRACT_PATH), "random_seed": seed,
            "predictors": predictors, "target_mapping": {"not_at_risk": 0, "at_risk": 1},
            "target_counts": dataset.report["target_counts"],
            "feature_cutoffs": dataset.report["feature_cutoffs"],
            "split_strategy": "GroupShuffleSplit holdout; StratifiedGroupKFold within training partition only",
            "train": partition_summary(dataset, train), "test": partition_summary(dataset, test),
            "student_group_overlap": 0,
            "partition_index_sha256": hashlib.sha256(json.dumps([train.tolist(), test.tolist(), [[a.tolist(), b.tolist()] for a, b in cv]]).encode()).hexdigest(),
            "cv_partitions": [{"train": partition_summary(dataset, a), "validation": partition_summary(dataset, b)} for a, b in cv],
            "topic_encoding": "pending; structured JSON not used as X",
            "software": {"python": platform.python_version(), **{name: importlib.metadata.version(name) for name in ("numpy", "pandas", "scipy", "scikit-learn", "imbalanced-learn", "joblib", "threadpoolctl")}},
            "real_total_rows": len(dataset.frame), "real_unique_students": len(set(dataset.groups)),
            "real_train_rows": len(train), "real_test_rows": len(test),
            "real_train_at_risk": int((dataset.y[train] == 1).sum()),
            "real_train_not_at_risk": int((dataset.y[train] == 0).sum()),
            "real_test_at_risk": int((dataset.y[test] == 1).sum()),
            "real_test_not_at_risk": int((dataset.y[test] == 0).sum()),
            "test_synthetic_rows": 0, "evaluation_resampled_rows": 0,
            "participant_count_policy": "Unique real student groups only; effective training rows are not participants.",
            "algorithms": {}, "files_created": []}


def write_json(path, payload):
    with Path(path).open("x", encoding="utf-8") as stream:
        json.dump(payload, stream, indent=2, allow_nan=False)
        stream.write("\n")


def require_authorization(args, dataset):
    if not args.authorize_training or not args.review:
        raise ValueError("Training blocked: explicit authorization and a dataset-specific provenance review are required.")
    review = json.loads(args.review.read_text(encoding="utf-8"))
    if (review.get("dataset_sha256") != dataset.report["dataset_sha256"] or
        review.get("data_origin") != "official_research" or
        any(review.get(k) is not True for k in ("official_outcomes_verified", "passing_criterion_approved", "temporal_eligibility_reviewed")) or
        not isinstance(review.get("reviewed_by"), str) or not review["reviewed_by"].strip()):
        raise ValueError("Training blocked: missing/mismatched official data, criterion or timing review.")
    return review


def train(args, dataset):
    require_authorization(args, dataset)
    schema_path = args.schema or dataset.path.with_suffix(".schema.json")
    schema = validate_schema(schema_path, dataset)
    x, y = dataset.x(args.include_attendance), dataset.y
    strategies = getattr(args, "strategies", None) or [getattr(args, "imbalance", "none")]
    smote_k = getattr(args, "smote_k_neighbors", 5)
    if len(set(strategies)) != len(strategies) or len(set(args.models)) != len(args.models):
        raise ValueError("Choose each model and imbalance strategy only once.")
    train_idx, test_idx, cv = prepare_splits(dataset, args.seed, args.test_size, args.folds)
    for fit, _ in [(train_idx, test_idx), *cv]:
        for strategy in strategies:
            check_sampling_feasibility(y[fit], strategy, smote_k)
        if not x.iloc[fit].notna().any().any():
            raise ValueError("All selected predictors missing in a training partition.")
        if "knn" in args.models and len(fit) < 5:
            raise ValueError("KNN requires at least five training observations in every fold.")
    if sha256(dataset.path) != dataset.report["dataset_sha256"]:
        raise ValueError("Dataset changed after validation.")
    manifest = make_manifest(dataset, args.seed, train_idx, test_idx, cv, list(x.columns))
    manifest.update(test_size=args.test_size, cv_folds=args.folds, export_schema_sha256=sha256(schema_path),
                    exported_criterion=schema["criterion"],
                    provenance_review_sha256=sha256(args.review), authorization="explicit CLI flag and human provenance review",
                    imbalance_strategies=strategies, smote_k_neighbors=smote_k if "smote" in strategies else None,
                    resampling_strategy=strategies[0] if len(strategies) == 1 else "comparison; see each algorithm variant",
                    selection_policy="fixed candidate parameters; no tuning, winner selection or full-data refit")
    # All readiness gates precede exclusive output-directory creation or fitting.
    out = args.output.resolve()
    out.mkdir(parents=True, exist_ok=False)
    try:
        write_json(out / "validation.json", dataset.report)
        manifest["files_created"].append("validation.json")
        comparison = []
        for name in args.models:
            for strategy in strategies:
                fold_results = []
                for fit, valid in cv:
                    pipeline = build_pipeline(name, args.seed, strategy, smote_k)
                    pipeline.fit(x.iloc[fit], y[fit])
                    fold_results.append({"metrics": evaluate(pipeline, x.iloc[valid], y[valid]),
                                         "preprocessing": preprocessing_record(pipeline),
                                         "imbalance": imbalance_record(pipeline, y[fit], strategy),
                                         "real_validation_rows": len(valid), "validation_synthetic_rows": 0})
                pipeline = build_pipeline(name, args.seed, strategy, smote_k)
                pipeline.fit(x.iloc[train_idx], y[train_idx])
                balancing = imbalance_record(pipeline, y[train_idx], strategy)
                result = {"estimator": type(pipeline.named_steps["model"]).__name__,
                          "hyperparameters": pipeline.named_steps["model"].get_params(),
                          "preprocessing": preprocessing_record(pipeline), "cv": fold_results,
                          "imbalance": balancing,
                          "cv_mean_metrics": {k: float(np.mean([r["metrics"][k] for r in fold_results])) for k in ("accuracy", "balanced_accuracy", "precision", "recall_sensitivity", "specificity", "f1", "roc_auc")},
                          "holdout_metrics": evaluate(pipeline, x.iloc[test_idx], y[test_idx])}
                key = name if len(strategies) == 1 else name + "__" + strategy
                artifact = key + ".joblib"
                joblib.dump(pipeline, out / artifact)
                result["model_sha256"] = sha256(out / artifact)
                manifest["algorithms"][key] = result
                manifest["files_created"].append(artifact)
                comparison.append({"algorithm": name, "strategy": strategy,
                                   "class_weight_status": balancing["class_weight_status"],
                                   "real_train_rows": len(train_idx), "real_test_rows": len(test_idx),
                                   "effective_training_rows_after_resampling": balancing["effective_training_rows_after_resampling"],
                                   "added_training_rows": balancing["added_training_rows"], "test_synthetic_rows": 0,
                                   "cv_mean_metrics": result["cv_mean_metrics"], "holdout_metrics": result["holdout_metrics"]})
        write_json(out / "comparison.json", {"partition_index_sha256": manifest["partition_index_sha256"],
                                             "selection_policy": manifest["selection_policy"], "variants": comparison})
        manifest["files_created"].append("comparison.json")
        manifest["status"] = "completed_candidate_evaluation"
    except Exception:
        manifest["status"] = "failed; partial artifacts are not valid completed runs"
        raise
    finally:
        manifest["files_created"].append("manifest.json")
        write_json(out / "manifest.json", manifest)
    return manifest


def main(argv=None):
    parser = argparse.ArgumentParser(description="LMMS v5 file-only infrastructure. Final training is blocked pending official data and approval.")
    sub = parser.add_subparsers(dest="command", required=True)
    validate = sub.add_parser("validate", help="Read-only validation; no training")
    validate.add_argument("dataset", type=Path)
    validate.add_argument("--schema", type=Path)
    validate.add_argument("--contract", choices=("official", "oop-temporal-v1"), default="official",
                          help="Explicit validation-only temporal contract; default official-grade behavior is unchanged")
    validate.add_argument("--report", type=Path, help="Optional new aggregate JSON file; never overwritten")
    training = sub.add_parser("train", help="Future authorized candidate evaluation")
    training.add_argument("dataset", type=Path)
    training.add_argument("--schema", type=Path)
    training.add_argument("--review", type=Path)
    training.add_argument("--authorize-training", action="store_true")
    training.add_argument("--output", type=Path, required=True)
    training.add_argument("--models", nargs="+", choices=MODEL_NAMES, default=list(MODEL_NAMES))
    training.add_argument("--seed", type=int, default=42)
    training.add_argument("--test-size", type=float, default=0.2)
    training.add_argument("--folds", type=int, default=5)
    training.add_argument("--include-attendance", action="store_true", help="Explicit optional attendance analysis")
    balancing = training.add_mutually_exclusive_group()
    balancing.add_argument("--imbalance", choices=STRATEGIES, default="none", help="Explicit training-only strategy; default none")
    balancing.add_argument("--strategies", nargs="+", choices=STRATEGIES, help="Compare explicit strategies on identical real partitions")
    training.add_argument("--smote-k-neighbors", type=int, default=5, help="Explicit SMOTE neighbor count; never auto-lowered")
    synthetic = sub.add_parser("synthetic", help="Explicit unlabeled feature-only simulation; never changes real data")
    synthetic.add_argument("dataset", type=Path, help="Immutable real unlabeled ML-ready feature export")
    synthetic.add_argument("--schema", type=Path, required=True, help="Explicit lmms_ml/contract.json")
    synthetic.add_argument("--scenario", choices=("fprog_1a_v1", "fprog_1a_v2", "general_augmentation_v1"), required=True)
    synthetic.add_argument("--rows", type=int, required=True)
    synthetic.add_argument("--seed", type=int, required=True)
    synthetic.add_argument("--output", type=Path, required=True)
    synthetic.add_argument("--class-code", help="Explicit source class for general augmentation only")
    synthetic.add_argument("--material-opportunities", type=int, help="Explicit material availability bound for general scenario")
    combine = sub.add_parser("combine-features", help="New unlabeled development file retaining real/synthetic origin")
    combine.add_argument("dataset", type=Path, help="Same immutable real unlabeled input used for generation")
    combine.add_argument("--synthetic", type=Path, required=True)
    combine.add_argument("--output", type=Path, required=True)
    args = parser.parse_args(argv)
    try:
        if args.command in ("synthetic", "combine-features"):
            from .synthetic import generate, combine as combine_features
            if args.command == "synthetic":
                result = generate(args.dataset, args.schema, args.scenario, args.rows, args.seed,
                                  args.output, args.class_code, args.material_opportunities)
            else:
                result = combine_features(args.dataset, args.synthetic, args.output)
            print(json.dumps({"artifact_kind": result["artifact_kind"], "output": str(args.output),
                              "output_sha256": result["output_sha256"], "training_eligible": False,
                              "generated_rows": result.get("generated_rows"), "real_rows": result.get("real_rows"),
                              "synthetic_rows": result.get("synthetic_rows"), "distribution_flags": result.get("distribution_flags", [])}, indent=2))
            return 0
        if args.command == "validate" and args.contract == "oop-temporal-v1":
            from .temporal_dataset import load_temporal_dataset
            if not args.schema:
                raise ValueError("Temporal validation requires its explicit schema companion.")
            dataset = load_temporal_dataset(args.dataset, args.schema)
            if args.report:
                write_json(args.report, dataset.report)
            print(json.dumps(dataset.report, indent=2, allow_nan=False))
            return 0
        dataset = load_dataset(args.dataset)
        if args.command == "validate":
            if args.schema:
                validate_schema(args.schema, dataset)
            if args.report:
                write_json(args.report, dataset.report)
            print(json.dumps(dataset.report, indent=2, allow_nan=False))
        else:
            manifest = train(args, dataset)
            print(json.dumps({"status": manifest["status"], "output": str(args.output)}, indent=2))
        return 0
    except ValidationError as error:
        if args.command == "validate" and args.report:
            write_json(args.report, error.report)
        print(json.dumps(error.report, indent=2, allow_nan=False))
        return 2
    except ValueError as error:
        # ValidationError is handled above; these are readiness/split messages,
        # never an echo of rejected CSV cells or group identifiers.
        print(json.dumps({"error": str(error)}))
        return 2
    except (OSError, KeyError):
        # Do not echo raw cell values, group keys, JSON payloads or OS credential paths.
        print(json.dumps({"error": "Operation blocked or failed. Check the documented contract, authorization, split feasibility and output paths."}))
        return 2
