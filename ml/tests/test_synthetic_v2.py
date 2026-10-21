"""Temporary simulation fixtures only; production data and database are never test inputs."""
import csv
import hashlib
import json
import socket

import numpy as np
import pandas as pd
import pytest

from lmms_ml.dataset import CONTRACT, CONTRACT_PATH, load_dataset
from lmms_ml import synthetic
from lmms_ml.synthetic_audit import audit, origin_diagnostic


@pytest.fixture
def reference(tmp_path, monkeypatch):
    monkeypatch.setattr(synthetic, "OUTPUT_ROOT", tmp_path / "synthetic")
    import lmms_ml.synthetic_audit as audit_module
    monkeypatch.setattr(audit_module, "OUTPUT_ROOT", tmp_path / "synthetic")
    rows = []
    for code, class_id in [("FPROG_1B", 1), ("FPROG_1C", 2)]:
        for i in range(60):
            submitted = i % 20 != 0
            quiz = code == "FPROG_1C" and i % 10 != 0
            row = {c: 0 for c in CONTRACT["core"]}
            row.update(student_group_key=hashlib.sha256(f"TEST-ONLY-{i}".encode()).hexdigest(),
                       lab_session_id=class_id, class_code=code, feature_cutoff_at="2026-10-20 12:00:00",
                       feature_version="v5", quiz_attempt_count=int(quiz), quiz_avg=(50 + 5 * (i % 11)) if quiz else "",
                       task_avg=(0 if i % 15 == 0 else 100) if submitted else "", submission_rate=100 if submitted else 0,
                       late_submission_count=int(submitted and code == "FPROG_1C"),
                       materials_accessed=int(i % 3 != 0), material_session_count=(1 + i % 3) * int(i % 3 != 0),
                       activity_count=i % 7, reading_avg_seconds="", activity_duration_seconds="", attendance_rate="",
                       assigned_task_count=1, tagged_quiz_count=0, untagged_quiz_count=2 if quiz else 0,
                       tagged_task_count=0, untagged_task_count=1, eligible_session_count=0,
                       topic_quiz_performance_json="{}", topic_task_performance_json="{}")
            rows.append(row)
    path = tmp_path / "test-only-reference.csv"
    with path.open("w", newline="") as stream:
        writer = csv.DictWriter(stream, fieldnames=list(rows[0]))
        writer.writeheader()
        writer.writerows(rows)
    return path


def candidate(reference, scenario="fprog_1a_v2", name="v2"):
    path = synthetic.OUTPUT_ROOT / f"{name}.csv"
    manifest = synthetic.generate(reference, CONTRACT_PATH, scenario, 60, 20261020, path)
    return path, pd.read_csv(path), manifest


def test_v2_measurement_states_and_completeness(reference):
    _, frame, manifest = candidate(reference)
    assert len(frame) == 60 and frame.quiz_attempt_count.isin([0, 1]).all()
    assert (frame.quiz_avg.dropna() % 5 == 0).all()
    assert frame.task_avg.dropna().isin([0, 100]).all()
    assert frame.task_avg.isna().sum() == 4
    assert frame.loc[frame.task_avg.isna(), "submission_rate"].eq(0).all()
    assert frame.loc[frame.task_avg.notna(), "submission_rate"].eq(100).all()
    assert frame.late_submission_count.le(frame.submission_rate / 100).all()
    assert frame.synthetic_profile.value_counts().to_dict() == {"average": 18, "borderline": 15, "strong": 9,
                                                              "struggling_engaged": 9, "struggling_low_engagement": 6, "irregular_missing": 3}
    assert manifest["generator_version"] == "synthetic_features_v2"
    assert manifest["feature_version"] == "v5" and not manifest["training_eligible"]
    assert frame[["attendance_rate", "activity_duration_seconds", "reading_avg_seconds"]].isna().all().all()
    assert frame.reading_total_seconds.eq(0).all()
    assert frame.topic_task_performance_json.eq("{}").all()
    assert CONTRACT["target"] not in frame and CONTRACT["ground_truth"] not in frame


def test_v1_draws_preserved_and_v2_deterministic_without_network(reference, monkeypatch):
    def denied(*args, **kwargs):
        pytest.fail("Simulation must never access network/database")
    monkeypatch.setattr(socket.socket, "connect", denied)
    before = reference.read_bytes()
    path1, v1, _ = candidate(reference, "fprog_1a_v1", "v1")
    original = path1.read_bytes()
    path2, v2, _ = candidate(reference)
    path3, _, _ = candidate(reference, name="repeat")
    assert path2.read_bytes() == path3.read_bytes()
    for c in ("materials_accessed", "material_session_count", "activity_count"):
        pd.testing.assert_series_equal(v1[c], v2[c])
    assert path1.read_bytes() == original and reference.read_bytes() == before
    with pytest.raises(FileExistsError):
        candidate(reference)


@pytest.mark.parametrize("column,value", [("quiz_avg", 72.5), ("quiz_attempt_count", 2), ("task_avg", 50)])
def test_v2_validator_rejects_wrong_measurement_support(reference, column, value):
    _, frame, manifest = candidate(reference)
    frame.loc[0, column] = value
    with pytest.raises(ValueError):
        synthetic.validate_synthetic(frame, manifest)


def test_v2_rejects_incompatible_reference(reference):
    frame = pd.read_csv(reference)
    frame.loc[1, "task_avg"] = 75
    frame.to_csv(reference, index=False)
    with pytest.raises(ValueError, match="Reference does not support"):
        candidate(reference)
    assert not (synthetic.OUTPUT_ROOT / "v2.csv").exists()


def test_origin_diagnostic_groups_students_and_excludes_provenance(reference):
    _, generated, _ = candidate(reference)
    real = load_dataset(reference, labeled=False).frame
    report = origin_diagnostic(real, generated)
    assert report["purpose"] == "synthetic realism diagnostic only"
    assert report["real_rows"] == 120 and report["real_unique_students"] == 60
    assert not report["provenance_metadata_or_academic_labels_in_x"]
    assert set(report["scalar_predictors"]).issubset(CONTRACT["core"] + CONTRACT["optional"])
    assert "student_group_key" not in report["scalar_predictors"]
    for model in report["models"].values():
        assert len(model["folds"]) == 5
        assert sum(f["validation_rows"] for f in model["folds"]) == 180
        assert all(f["group_overlap"] == 0 for f in model["folds"])
    assert report == origin_diagnostic(real, generated)


def test_origin_diagnostic_flags_obvious_separation(reference):
    _, generated, _ = candidate(reference)
    real = load_dataset(reference, labeled=False).frame.copy()
    real["activity_count"] = 0
    generated["activity_count"] = 1000
    report = origin_diagnostic(real, generated)
    assert report["extremely_easy_origin_flag"]


def test_audit_writes_new_reports_no_identifiers_or_labels(reference):
    v1, _, _ = candidate(reference, "fprog_1a_v1", "v1")
    v2, _, _ = candidate(reference)
    original = {p: p.read_bytes() for p in (reference, v1, v2)}
    report = audit(reference, v1, v2)
    assert report["mechanics"] == {"quiz_off_grid": 0, "attempts_above_observed_max": 0, "invalid_task_support": 0,
                                   "missing_task_with_submission": 0, "zero_grade_without_submission": 0}
    text = v2.with_suffix(".audit.json").read_text()
    for group in load_dataset(reference, labeled=False).frame.student_group_key:
        assert group not in text
    assert report["review"]["training_eligible"] is False
    for suffix in (".audit.json", ".audit.csv", ".review.json"):
        assert v2.with_suffix(suffix).exists()
    assert all(p.read_bytes() == content for p, content in original.items())
    with pytest.raises(FileExistsError):
        audit(reference, v1, v2)
