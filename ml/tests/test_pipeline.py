"""Tiny fabricated observations exist only in pytest temporary directories.

No research CSV, database, labels or outcomes are used or modified by these tests.
"""
import argparse
import csv
import hashlib
import json
import re
from pathlib import Path

import numpy as np
import pandas as pd
import pytest

from lmms_ml.dataset import CONTRACT, ValidationError, load_dataset, sha256, validate_schema
from lmms_ml.modeling import (MODEL_NAMES, TrainingMedianImputer, build_pipeline,
                              evaluate, metrics, prepare_splits)
from lmms_ml.runner import ROOT, main, make_manifest, train, write_json
from lmms_ml.imbalance import AuditedSampler, imbalance_record
from imblearn.pipeline import Pipeline as SamplingPipeline


@pytest.fixture
def records():
    rows = []
    for group in range(40):
        for class_id in (1, 2):
            row = {name: str((group + class_id) % 4) for name in CONTRACT["core"]}
            row.update(student_group_key=hashlib.sha256(f"SYNTHETIC-TEST-ONLY-{group}".encode()).hexdigest(),
                       lab_session_id=str(class_id), feature_cutoff_at="2026-10-03 12:00:00", feature_version="v5",
                       quiz_avg=str(30 + group), task_avg=str(40 + group), submission_rate="100",
                       attendance_rate="", topic_quiz_performance_json="{}", topic_task_performance_json="{}",
                       final_outcome="1" if group % 2 else "0",
                       academic_risk_status="at_risk" if group % 2 else "not_at_risk")
            rows.append(row)
    return rows


def write_csv(tmp_path, rows):
    path = tmp_path / "synthetic-test-only.csv"
    with path.open("w", encoding="utf-8", newline="") as stream:
        writer = csv.DictWriter(stream, fieldnames=list(rows[0]))
        writer.writeheader()
        writer.writerows(rows)
    return path


def write_schema(path, dataset):
    schema = {"columns": list(dataset.frame.columns), "categories": {
        "model_x_core": CONTRACT["core"], "model_x_optional": CONTRACT["optional"],
        "structured_topic_encoding_pending": CONTRACT["structured"], "target_y": [CONTRACT["target"]],
        "ground_truth_source": [CONTRACT["ground_truth"]], "grouping_metadata": CONTRACT["metadata"],
        "excluded_predictors": CONTRACT["excluded"]},
        "criterion": {"passing_grade": 0.5, "passing_operator": "gte"},
        "feature_versions": ["v5"], "feature_cutoffs": dataset.report["feature_cutoffs"],
        "target_values": {"at_risk": "At-Risk", "not_at_risk": "Not At-Risk"},
        "imputation": "none", "topic_encoding": "pending"}
    write_json(path, schema)


def test_ml_contract_matches_frozen_php_and_scope():
    php = (ROOT / "config/risk_features.php").read_text()
    def lists(section, end):
        part = php.split(f"'{section}' =>", 1)[1].split(f"'{end}' =>", 1)[0]
        return [value for body in re.findall(r"=>\s*\[([^\]]*)\]", part) for value in re.findall(r"'([^']+)'", body)]
    assert lists("core_groups", "optional_groups") == CONTRACT["core"]
    assert lists("optional_groups", "structured_groups") == CONTRACT["optional"]
    assert lists("structured_groups", "excluded") == CONTRACT["structured"]
    assert "FEATURE_VERSION = 'v5'" in (ROOT / "app/Services/StudentRiskFeatureService.php").read_text()
    for forbidden in [*CONTRACT["metadata"], *CONTRACT["excluded"], CONTRACT["target"], CONTRACT["ground_truth"]]:
        assert forbidden not in CONTRACT["core"] + CONTRACT["optional"]


def test_valid_counts_missing_and_zero_are_preserved(tmp_path, records):
    records[0]["quiz_avg"] = ""
    records[1]["quiz_avg"] = "0"
    ds = load_dataset(write_csv(tmp_path, records))
    assert ds.report["total_rows"] == 80
    assert ds.report["unique_students"] == 40
    assert ds.report["class_count"] == 2
    assert ds.report["predictors"]["quiz_avg"]["missing_percent"] == 1.25
    assert np.isnan(ds.x().iloc[0].quiz_avg)
    assert ds.x().iloc[1].quiz_avg == 0
    assert ds.report["all_missing_predictors"] == ["attendance_rate"]
    assert "submission_rate" in ds.report["constant_predictors"]
    assert ds.report["target_counts"] == {"not_at_risk": 40, "at_risk": 40}
    assert ds.report["target_percentages"] == {"not_at_risk": 50, "at_risk": 50}
    assert list(ds.x().columns) == CONTRACT["core"]
    assert list(ds.x(True).columns) == CONTRACT["core"] + CONTRACT["optional"]


@pytest.mark.parametrize("column", ["quiz_avg", "student_group_key", "academic_risk_status", "final_outcome"])
def test_missing_required_columns(tmp_path, records, column):
    for row in records:
        del row[column]
    with pytest.raises(ValidationError) as caught:
        load_dataset(write_csv(tmp_path, records))
    assert column in caught.value.report["missing_required_columns"]


@pytest.mark.parametrize("column", ["user_id", "student_id", "name", "email", "risk_prediction", "recommendation_result", "current_academic_grade", "extra"])
def test_leakage_and_unexpected_columns_rejected(tmp_path, records, column):
    for row in records:
        row[column] = "TEST-ONLY"
    with pytest.raises(ValidationError) as caught:
        load_dataset(write_csv(tmp_path, records))
    assert column in caught.value.report["unexpected_columns"]


@pytest.mark.parametrize("label", ["", "At-Risk", "1", "PASS", "unknown"])
def test_invalid_labels_not_inferred(tmp_path, records, label):
    records[0]["academic_risk_status"] = label
    with pytest.raises(ValidationError):
        load_dataset(write_csv(tmp_path, records))


def test_duplicates_reported_and_rejected(tmp_path, records):
    records.append(records[0].copy())
    with pytest.raises(ValidationError) as caught:
        load_dataset(write_csv(tmp_path, records))
    assert caught.value.report["duplicate_student_class_rows"] == 1


@pytest.mark.parametrize("column,value", [("quiz_avg", "101"), ("task_avg", "-1"), ("quiz_attempt_count", "-2"),
                                          ("quiz_attempt_count", "1.5"), ("reading_avg_seconds", "inf"),
                                          ("reading_total_seconds", "bad"), ("final_outcome", "")])
def test_invalid_numeric_ranges(tmp_path, records, column, value):
    records[0][column] = value
    with pytest.raises(ValidationError):
        load_dataset(write_csv(tmp_path, records))


@pytest.mark.parametrize("field,value", [("feature_version", "v6"), ("feature_cutoff_at", "tomorrow"),
                                        ("student_group_key", "123"), ("lab_session_id", "0"),
                                        ("topic_quiz_performance_json", "[]")])
def test_invalid_metadata_and_topics(tmp_path, records, field, value):
    records[0][field] = value
    with pytest.raises(ValidationError):
        load_dataset(write_csv(tmp_path, records))


def test_train_only_median_and_all_missing_handling():
    fit = pd.DataFrame({"quiz_avg": [0.0, 10.0, np.nan], "attendance_rate": [np.nan] * 3})
    valid = pd.DataFrame({"quiz_avg": [1000.0, np.nan], "attendance_rate": [100.0, 100.0]})
    with pytest.warns(UserWarning):
        transformer = TrainingMedianImputer().fit(fit)
    assert transformer.dropped_columns_ == ["attendance_rate"]
    assert transformer.transform(valid).tolist() == [[1000.0], [5.0]]
    assert transformer.transform(fit)[0, 0] == 0
    assert list(transformer.get_feature_names_out()) == ["quiz_avg"]
    with pytest.raises(ValueError):
        TrainingMedianImputer().fit(pd.DataFrame({"quiz_avg": [np.nan]}))
    with pytest.raises(ValueError):
        TrainingMedianImputer().fit(pd.DataFrame({"lab_session_id": [1]}))


def test_group_separation_cv_and_reproducibility(tmp_path, records):
    ds = load_dataset(write_csv(tmp_path, records))
    train_idx, test_idx, folds = prepare_splits(ds, seed=42, folds=3)
    again = prepare_splits(ds, seed=42, folds=3)
    assert np.array_equal(train_idx, again[0]) and np.array_equal(test_idx, again[1])
    assert not set(ds.groups[train_idx]) & set(ds.groups[test_idx])
    validation_indices = []
    for (fit, valid), (fit2, valid2) in zip(folds, again[2]):
        assert np.array_equal(fit, fit2) and np.array_equal(valid, valid2)
        assert not set(ds.groups[fit]) & set(ds.groups[valid])
        assert not set(ds.groups[valid]) & set(ds.groups[test_idx])
        validation_indices.extend(valid)
    assert sorted(validation_indices) == sorted(train_idx)
    manifest = make_manifest(ds, 42, train_idx, test_idx, folds, ds.predictors())
    json.dumps(manifest, allow_nan=False)
    assert manifest["dataset_sha256"] == sha256(ds.path)
    assert manifest["student_group_overlap"] == 0
    assert manifest["train"]["unique_students"] + manifest["test"]["unique_students"] == 40
    assert "scikit-learn" in manifest["software"]


def test_impossible_grouped_cv_blocks(tmp_path, records):
    ds = load_dataset(write_csv(tmp_path, records[:8]))
    with pytest.raises(ValueError):
        prepare_splits(ds, folds=5)


def test_one_target_can_be_reported_but_cannot_split(tmp_path, records):
    for row in records:
        row["academic_risk_status"] = "at_risk"
    ds = load_dataset(write_csv(tmp_path, records))
    assert ds.report["target_counts"]["not_at_risk"] == 0
    with pytest.raises(ValueError, match="Insufficient"):
        prepare_splits(ds)


@pytest.mark.parametrize("name", MODEL_NAMES)
def test_six_pipelines_on_tiny_test_only_data(tmp_path, records, name):
    ds = load_dataset(write_csv(tmp_path, records))
    fit, test, _ = prepare_splits(ds, folds=3)
    pipeline = build_pipeline(name)
    assert ("scale" in pipeline.named_steps) == (name in ("logistic_regression", "svm", "knn"))
    pipeline.fit(ds.x().iloc[fit], ds.y[fit])
    result = evaluate(pipeline, ds.x().iloc[test], ds.y[test])
    assert result["positive_class"] == "at_risk"
    assert result["roc_auc"] is not None


def test_metric_calculations():
    result = metrics([0, 0, 0, 1, 1], [0, 0, 1, 0, 1], [0.1, 0.2, 0.7, 0.4, 0.9])
    assert result["confusion_matrix"] == [[2, 1], [1, 1]]
    assert result["accuracy"] == 0.6
    assert result["precision"] == result["recall_sensitivity"] == result["f1"] == 0.5
    assert result["specificity"] == pytest.approx(2 / 3)
    assert result["roc_auc"] == pytest.approx(5 / 6)
    assert metrics([1], [1])["specificity"] is None


def test_validation_cli_is_read_only_and_no_training_default(tmp_path, records, capsys):
    path = write_csv(tmp_path, records)
    assert main(["validate", str(path)]) == 0
    assert '"total_rows": 80' in capsys.readouterr().out
    assert list(tmp_path.iterdir()) == [path]
    out = tmp_path / "blocked-run"
    assert main(["train", str(path), "--output", str(out)]) == 2
    assert not out.exists()


def test_schema_and_review_gate_and_run_manifest(tmp_path, records):
    ds = load_dataset(write_csv(tmp_path, records))
    schema = ds.path.with_suffix(".schema.json")
    write_schema(schema, ds)
    validate_schema(schema, ds)
    review = tmp_path / "test-only-review.json"
    write_json(review, {"dataset_sha256": ds.report["dataset_sha256"], "data_origin": "official_research",
                        "official_outcomes_verified": True, "passing_criterion_approved": True,
                        "temporal_eligibility_reviewed": True, "reviewed_by": "PYTEST FIXTURE ONLY"})
    args = argparse.Namespace(authorize_training=True, review=review, schema=schema, include_attendance=False,
                              seed=42, test_size=0.2, folds=3, models=["decision_tree"], output=tmp_path / "test-only-run")
    manifest = train(args, ds)
    assert manifest["status"] == "completed_candidate_evaluation"
    assert manifest["algorithms"]["decision_tree"]["estimator"] == "DecisionTreeClassifier"
    assert manifest["provenance_review_sha256"] == sha256(review)
    assert (args.output / "manifest.json").exists()
    assert set(manifest["predictors"]) == set(CONTRACT["core"])
    assert ds.groups[0] not in (args.output / "manifest.json").read_text()
    with pytest.raises(FileExistsError):
        train(args, ds)
    args.authorize_training = False
    with pytest.raises(ValueError):
        train(args, ds)
    altered = json.loads(schema.read_text())
    altered["categories"]["model_x_core"].append("final_outcome")
    schema.write_text(json.dumps(altered))
    with pytest.raises(ValueError):
        validate_schema(schema, ds)


@pytest.mark.parametrize("change", [{"dataset_sha256": "wrong"}, {"data_origin": "synthetic"},
                                   {"official_outcomes_verified": False}, {"passing_criterion_approved": False},
                                   {"temporal_eligibility_reviewed": False}])
def test_incomplete_review_never_creates_run(tmp_path, records, change):
    ds = load_dataset(write_csv(tmp_path, records))
    review = tmp_path / "test-only-review.json"
    write_json(review, {"dataset_sha256": ds.report["dataset_sha256"], "data_origin": "official_research",
                        "official_outcomes_verified": True, "passing_criterion_approved": True,
                        "temporal_eligibility_reviewed": True, "reviewed_by": "PYTEST ONLY", **change})
    out = tmp_path / "blocked"
    args = argparse.Namespace(authorize_training=True, review=review, output=out)
    with pytest.raises(ValueError, match="Training blocked"):
        train(args, ds)
    assert not out.exists()


def test_structural_cli_report_and_no_values_in_error(tmp_path, records, capsys):
    records[0]["academic_risk_status"] = "SECRET-INVALID-CELL"
    path = write_csv(tmp_path, records)
    assert main(["validate", str(path)]) == 2
    output = capsys.readouterr().out
    assert "SECRET-INVALID-CELL" not in output
    assert records[0]["student_group_key"] not in output


def test_schema_drift_and_malformed_csv(tmp_path, records):
    path = write_csv(tmp_path, records)
    with path.open("a") as stream:
        stream.write("one,two\n")
    with pytest.raises(ValidationError):
        load_dataset(path)


@pytest.mark.parametrize("name", MODEL_NAMES)
def test_class_weight_supported_only_where_native(name):
    pipeline = build_pipeline(name, strategy="class_weight")
    params = pipeline.named_steps["model"].get_params()
    if name in ("logistic_regression", "decision_tree", "random_forest", "svm"):
        assert params["class_weight"] == "balanced"
    else:
        assert "class_weight" not in params
    assert "sampler" not in pipeline.named_steps
    assert build_pipeline(name).named_steps["model"].get_params().get("class_weight") is None


@pytest.mark.parametrize("strategy", ["random_over", "smote"])
def test_sampler_is_deterministic_and_counts_real_separately(strategy):
    x = np.arange(48, dtype=float).reshape(24, 2)
    y = np.array([0] * 16 + [1] * 8)
    original_x, original_y = x.copy(), y.copy()
    a = AuditedSampler(strategy, random_state=17, k_neighbors=3)
    b = AuditedSampler(strategy, random_state=17, k_neighbors=3)
    xa, ya = a.fit_resample(x, y)
    xb, yb = b.fit_resample(x, y)
    np.testing.assert_array_equal(xa, xb)
    np.testing.assert_array_equal(ya, yb)
    np.testing.assert_array_equal(x, original_x)
    np.testing.assert_array_equal(y, original_y)
    assert a.record_["real_train_rows"] == 24
    assert a.record_["effective_training_rows_after_resampling"] == 32
    assert a.record_["original_class_counts"] == {"not_at_risk": 16, "at_risk": 8}
    assert a.record_["resampled_class_counts"] == {"not_at_risk": 16, "at_risk": 16}
    assert a.record_["added_training_rows"] == 8
    assert a.record_["interpolated_training_rows"] == (8 if strategy == "smote" else 0)
    assert a.record_["duplicated_training_rows"] == (8 if strategy == "random_over" else 0)


def test_smote_rejects_nan_and_too_few_minority_without_fallback():
    with pytest.raises(ValueError, match="more than k_neighbors=5"):
        AuditedSampler("smote", k_neighbors=5).fit_resample(np.ones((8, 2)), [0] * 6 + [1] * 2)
    with pytest.raises(ValueError, match="already-imputed"):
        AuditedSampler("smote", k_neighbors=1).fit_resample(np.full((8, 2), np.nan), [0] * 5 + [1] * 3)


@pytest.mark.parametrize("strategy", ["random_over", "smote"])
def test_prediction_never_samples_and_evaluation_support_unchanged(strategy, monkeypatch):
    x = pd.DataFrame({"quiz_avg": [float(n) if n != 2 else np.nan for n in range(24)]})
    y = np.array([0] * 16 + [1] * 8)
    pipeline = build_pipeline("decision_tree", strategy=strategy, smote_k=3)
    assert isinstance(pipeline, SamplingPipeline)
    assert list(pipeline.named_steps).index("impute") < list(pipeline.named_steps).index("sampler")
    if strategy == "smote":
        assert list(pipeline.named_steps).index("scale") < list(pipeline.named_steps).index("sampler")
    pipeline.fit(x, y)
    evaluation_x = pd.DataFrame({"quiz_avg": [np.nan, 19.0, 300.0, 0.0]})
    evaluation_y = np.array([0, 1, 0, 1])
    before = evaluation_x.copy(deep=True)
    def forbidden(*args, **kwargs):
        pytest.fail("Sampler was called during evaluation")
    monkeypatch.setattr(pipeline.named_steps["sampler"], "fit_resample", forbidden)
    result = evaluate(pipeline, evaluation_x, evaluation_y)
    pd.testing.assert_frame_equal(before, evaluation_x)
    assert sum(map(sum, result["confusion_matrix"])) == 4
    assert len(pipeline.predict(evaluation_x)) == 4
    record = imbalance_record(pipeline, y, strategy)
    assert record["test_synthetic_rows"] == record["evaluation_resampled_rows"] == 0


def test_balanced_accuracy():
    result = metrics([0, 0, 0, 1], [0, 0, 0, 0], [0.1, 0.2, 0.3, 0.4])
    assert result["accuracy"] == 0.75
    assert result["balanced_accuracy"] == 0.5


def imbalanced_test_dataset(tmp_path, records):
    # Subselect existing fabricated test rows; never infer/create labels from X.
    rows = [row for index, row in enumerate(records)
            if row["academic_risk_status"] == "not_at_risk" or index // 2 < 16]
    return load_dataset(write_csv(tmp_path, rows))


def authorized_test_args(tmp_path, ds, strategies, k=2):
    schema = ds.path.with_suffix(".schema.json")
    write_schema(schema, ds)
    review = tmp_path / "test-only-review.json"
    write_json(review, {"dataset_sha256": ds.report["dataset_sha256"], "data_origin": "official_research",
                        "official_outcomes_verified": True, "passing_criterion_approved": True,
                        "temporal_eligibility_reviewed": True, "reviewed_by": "PYTEST FIXTURE ONLY"})
    return argparse.Namespace(authorize_training=True, review=review, schema=schema, include_attendance=False,
                              seed=42, test_size=0.2, folds=2, models=["decision_tree"],
                              output=tmp_path / "test-only-comparison", strategies=strategies, smote_k_neighbors=k)


def test_comparison_splits_before_sampling_and_cv_is_fold_local(tmp_path, records, monkeypatch):
    ds = imbalanced_test_dataset(tmp_path, records)
    train_idx, test_idx, cv = prepare_splits(ds, seed=42, folds=2)
    assert not set(ds.groups[train_idx]) & set(ds.groups[test_idx])
    original_frame = ds.frame.copy(deep=True)
    fit_indices, sampling_labels = [], []
    original_fit, original_sample = TrainingMedianImputer.fit, AuditedSampler.fit_resample
    def tracked_fit(self, x, y=None):
        fit_indices.append(x.index.tolist())
        assert not set(ds.groups[x.index]) & set(ds.groups[test_idx])
        return original_fit(self, x, y)
    def tracked_sample(self, x, y):
        sampling_labels.append(np.asarray(y).copy())
        assert np.isfinite(x).all()  # imputation (and SMOTE scaling) already happened
        return original_sample(self, x, y)
    monkeypatch.setattr(TrainingMedianImputer, "fit", tracked_fit)
    monkeypatch.setattr(AuditedSampler, "fit_resample", tracked_sample)
    args = authorized_test_args(tmp_path, ds, ["none", "class_weight", "random_over", "smote"], k=1)
    manifest = train(args, ds)
    expected_fit_indices = [a.tolist() for a, _ in cv] + [train_idx.tolist()]
    assert fit_indices == expected_fit_indices * 4
    expected_labels = [ds.y[a] for a, _ in cv] + [ds.y[train_idx]]
    assert len(sampling_labels) == 6  # two samplers, each fit twice in CV + once in train
    for actual, expected in zip(sampling_labels, expected_labels * 2):
        np.testing.assert_array_equal(actual, expected)
    pd.testing.assert_frame_equal(ds.frame, original_frame)
    assert manifest["real_total_rows"] == len(ds.frame)
    assert manifest["real_train_rows"] == len(train_idx)
    assert manifest["real_test_rows"] == len(test_idx)
    assert manifest["real_unique_students"] == 28
    assert manifest["test_synthetic_rows"] == 0
    for result in manifest["algorithms"].values():
        assert sum(map(sum, result["holdout_metrics"]["confusion_matrix"])) == len(test_idx)
        assert result["imbalance"]["real_train_rows"] == len(train_idx)
        for fold, (_, valid) in zip(result["cv"], cv):
            assert fold["real_validation_rows"] == len(valid)
            assert fold["validation_synthetic_rows"] == 0
            assert sum(map(sum, fold["metrics"]["confusion_matrix"])) == len(valid)
    assert manifest["algorithms"]["decision_tree__none"]["imbalance"]["added_training_rows"] == 0
    augmented = manifest["algorithms"]["decision_tree__smote"]["imbalance"]
    assert augmented["effective_training_rows_after_resampling"] > manifest["real_train_rows"]
    comparison = json.loads((args.output / "comparison.json").read_text())
    assert len(comparison["variants"]) == 4
    assert comparison["partition_index_sha256"] == manifest["partition_index_sha256"]


def test_smote_failure_preflight_creates_no_run(tmp_path, records):
    ds = imbalanced_test_dataset(tmp_path, records)
    args = authorized_test_args(tmp_path, ds, ["smote"], k=1000)
    with pytest.raises(ValueError, match="No automatic fallback"):
        train(args, ds)
    assert not args.output.exists()

