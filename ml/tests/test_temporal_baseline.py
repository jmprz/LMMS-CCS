"""Baseline adapter guards; fabricated temporary software fixtures only."""
import csv
import hashlib
import json

import numpy as np
import pytest

from lmms_ml import temporal_baseline as baseline
from lmms_ml.dataset import CONTRACT, load_dataset


def test_authorization_and_pinned_hash_fail_before_output(tmp_path):
    path = tmp_path / "fake.csv"
    path.write_text("SOFTWARE TEST ONLY")
    out = tmp_path / "results"
    with pytest.raises(ValueError, match="authorize-training"):
        baseline.run(path, path, path, out)
    with pytest.raises(ValueError, match="hash differs"):
        baseline.run(path, path, path, out, authorize=True)
    assert not out.exists()


def test_fold_local_baseline_artifacts_and_no_holdout_fitting(tmp_path, monkeypatch):
    path = tmp_path / "fabricated-software-test.csv"
    rows = []
    for i in range(40):
        row = {name: i % 3 for name in CONTRACT["core"]}
        row.update(activity_duration_seconds="", attendance_rate="", task_avg="" if i % 4 == 0 else i,
                   student_group_key=hashlib.sha256(f"SOFTWARE-ONLY-{i}".encode()).hexdigest(),
                   lab_session_id=1, feature_cutoff_at="2026-10-15 17:59:59", feature_version="v5",
                   final_outcome=i % 2, academic_risk_status="at_risk" if i % 2 else "not_at_risk")
        rows.append(row)
    with path.open("w", newline="") as f:
        writer = csv.DictWriter(f, fieldnames=list(rows[0])); writer.writeheader(); writer.writerows(rows)
    data = load_dataset(path)
    data.report["risk_reason_counts"] = {"nonparticipation": 0, "below_threshold": 20, "meets_threshold": 20}
    schema = tmp_path / "test.schema.json"
    schema.write_text(json.dumps({"source_feature_filename": path.name}))
    provenance = tmp_path / "test.provenance.json"
    provenance.write_text("{}")
    monkeypatch.setattr(baseline, "verify_inputs", lambda *args: data)
    train, test, cv = baseline.prepare_splits(data)
    original_build = baseline.build_pipeline
    fits = []

    def tracked_build(*args, **kwargs):
        pipe = original_build(*args, **kwargs)
        original_fit = pipe.fit

        def fit(x, y):
            positions = set(x.index)
            assert positions.isdisjoint(test)
            assert list(x.columns) == [c for c in CONTRACT["core"] if c != "activity_duration_seconds"]
            assert "sampler" not in pipe.named_steps
            assert pipe.named_steps["model"].get_params().get("class_weight") is None
            fits.append(positions)
            return original_fit(x, y)

        pipe.fit = fit
        return pipe

    monkeypatch.setattr(baseline, "build_pipeline", tracked_build)
    # Avoid serializing the local tracking wrapper; real runner still writes actual joblib pipelines.
    monkeypatch.setattr(baseline.joblib, "dump", lambda model, p: p.write_bytes(b"TEST-ONLY-NOT-A-MODEL"))
    out = tmp_path / "new-results"
    result = baseline.run(path, schema, provenance, out, authorize=True)
    assert result["status"] == "completed_candidate_evaluation"
    assert result["resampling_strategy"] == "none"
    assert result["student_group_overlap"] == 0
    assert len(fits) == 36
    assert fits == ([set(a) for a, _ in cv] + [set(train)]) * 6
    for model in result["algorithms"].values():
        assert model["imbalance"]["added_training_rows"] == 0
        for k, value in model["cv_std_metrics"].items():
            assert value == pytest.approx(np.std([f["metrics"][k] for f in model["cv"]]))
    assert (out / "split-indices.json").is_file()
    assert (out / "comparison.csv").is_file()
    with pytest.raises(FileExistsError):
        baseline.run(path, schema, provenance, out, authorize=True)


def test_all_missing_exclusion_is_exact(tmp_path):
    class FakeData:
        report = {"all_missing_predictors": ["task_avg"]}
    with pytest.raises(ValueError, match="scope changed"):
        baseline.baseline_x(FakeData())
