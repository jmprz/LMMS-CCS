"""Feature-only fixtures in temporary directories; no database or research artifacts."""
import copy
import csv
import hashlib
import json
import socket
from pathlib import Path

import numpy as np
import pandas as pd
import pytest

from lmms_ml import synthetic
from lmms_ml.dataset import CONTRACT, CONTRACT_PATH, Dataset, ValidationError, load_dataset, sha256
from lmms_ml.runner import main


@pytest.fixture
def feature_file(tmp_path, monkeypatch):
    monkeypatch.setattr(synthetic, "OUTPUT_ROOT", tmp_path / "outputs")
    records = []
    for class_id, code in [(5, "FPROG_1C"), (7, "FPROG_1B")]:
        for index in range(40):
            attempts = index % 3 if class_id == 5 else 0
            submitted = index % 5 != 0
            accessed = int(index % 3 != 0)
            sessions = accessed * (1 + index % 3)
            measured = accessed and index % 4 != 0
            row = {name: 0 for name in CONTRACT["core"]}
            row.update(student_group_key=hashlib.sha256(f"FEATURE-TEST-ONLY-{class_id}-{index}".encode()).hexdigest(),
                       lab_session_id=str(class_id), class_code=code,
                       feature_cutoff_at="2026-10-03 12:00:00", feature_version="v5",
                       quiz_avg=65 + index % 33 if attempts else "", quiz_attempt_count=attempts,
                       task_avg=70 + index % 29 if submitted else "", submission_rate=100 if submitted else 0,
                       late_submission_count=int(submitted and index % 7 == 0), materials_accessed=accessed,
                       material_session_count=sessions, reading_avg_seconds=20 + index if measured else "",
                       reading_total_seconds=(20 + index) * sessions if measured else 0,
                       activity_count=index % 9, activity_duration_seconds="", attendance_rate="",
                       assigned_task_count=1, eligible_session_count=0,
                       tagged_quiz_count=0, untagged_quiz_count=2 if class_id == 5 else 0,
                       tagged_task_count=0, untagged_task_count=1,
                       topic_quiz_performance_json="{}", topic_task_performance_json="{}")
            records.append(row)
    path = tmp_path / "FEATURE-ONLY-TEST-FIXTURE.csv"
    write_rows(path, records)
    return path


def write_rows(path, records):
    with path.open("w", encoding="utf-8", newline="") as stream:
        writer = csv.DictWriter(stream, fieldnames=list(records[0]))
        writer.writeheader()
        writer.writerows(records)


def generate(feature_file, name="a", rows=60, seed=20261020, scenario="fprog_1a_v1", **kwargs):
    output = synthetic.OUTPUT_ROOT / (name + ".csv")
    manifest = synthetic.generate(feature_file, CONTRACT_PATH, scenario, rows, seed, output, **kwargs)
    frame = pd.read_csv(output, keep_default_na=True)
    return output, frame, manifest


def test_seed_determinism_exact_rows_and_immutable_input(feature_file):
    before = feature_file.read_bytes()
    a, fa, ma = generate(feature_file)
    b, fb, mb = generate(feature_file, name="b")
    c, fc, mc = generate(feature_file, name="c", seed=123)
    assert a.read_bytes() == b.read_bytes()
    assert ma["synthetic_run_id"] == mb["synthetic_run_id"]
    assert a.read_bytes() != c.read_bytes()
    assert len(fa) == ma["requested_rows"] == ma["generated_rows"] == 60
    assert feature_file.read_bytes() == before
    assert ma["input_sha256"] == sha256(feature_file)
    assert ma["output_sha256"] == sha256(a)
    assert not fa.student_group_key.duplicated().any()
    assert set(fa.data_origin) == {"synthetic"}
    assert fa.student_group_key.str.startswith("SYN-FPROG1A-").all()
    assert ma["source_class_codes"] == ["FPROG_1B", "FPROG_1C"]
    assert ma["real_participants_added"] == 0


def test_percentages_counts_opportunity_consistency(feature_file):
    _, frame, manifest = generate(feature_file, rows=600)
    for name in CONTRACT["percentages"]:
        assert frame[name].dropna().between(0, 100).all()
    for name in CONTRACT["counts"]:
        if name in frame:
            assert (frame[name].dropna() >= 0).all()
            assert (frame[name].dropna() % 1 == 0).all()
    assert frame.quiz_attempt_count.le(2).all()
    assert frame.materials_accessed.le(1).all()
    assert (frame.material_session_count >= frame.materials_accessed).all()
    assert (frame.material_session_count <= frame.materials_accessed * 12).all()
    assert (frame.late_submission_count <= frame.submission_rate / 100).all()
    assert frame.loc[frame.quiz_attempt_count.eq(0), "quiz_avg"].isna().all()
    assert frame.loc[frame.submission_rate.eq(0), "task_avg"].isna().all()
    assert set(frame.submission_rate) <= {0, 100}
    assert manifest["opportunities"]["tasks"] == 1


def test_missingness_zero_and_unsupported_measurements(feature_file):
    _, frame, manifest = generate(feature_file)
    assert frame.quiz_avg.isna().any()
    assert frame.material_session_count.eq(0).any()
    assert frame.submission_rate.eq(0).any()
    assert frame.reading_total_seconds.eq(0).all()
    for name in ("reading_avg_seconds", "activity_duration_seconds", "attendance_rate"):
        assert frame[name].isna().all()
    assert frame.topic_task_performance_json.eq("{}").all()
    assert frame.topic_quiz_performance_json.eq("{}").all()
    assert "activity_duration_seconds" in manifest["missingness_rules"]["globally_all_missing_preserved"]


def test_profile_mixture_and_overlapping_scores(feature_file):
    _, small, manifest = generate(feature_file)
    assert manifest["profile_counts"] == {"strong": 9, "average": 18, "borderline": 15,
                                           "struggling_engaged": 9, "struggling_low_engagement": 6, "irregular_missing": 3}
    _, frame, _ = generate(feature_file, name="larger", rows=1200)
    real = load_dataset(feature_file, labeled=False).frame
    assert frame.quiz_avg.max() >= real.quiz_avg.median()
    assert frame.task_avg.max() >= real.task_avg.median()
    assert frame.task_avg.min() < real.task_avg.median()
    observed = frame[frame.task_avg.notna()]
    strong = observed[observed.synthetic_profile == "strong"].task_avg
    struggling = observed[observed.synthetic_profile == "struggling_engaged"].task_avg
    assert strong.min() < struggling.max()  # profiles overlap; not deterministic risk templates
    assert observed.task_avg.nunique() > 100
    assert frame.quiz_avg.corr(frame.task_avg) < 0.95
    assert ((frame.synthetic_profile == "struggling_engaged") & (frame.materials_accessed > 0)).any()


def test_no_targets_no_outcomes_and_provenance_never_x(feature_file):
    out, frame, _ = generate(feature_file)
    assert CONTRACT["target"] not in frame and CONTRACT["ground_truth"] not in frame
    projected = Dataset(out, frame, {}).x()
    assert list(projected.columns) == CONTRACT["core"]
    assert not set(synthetic.PROVENANCE) & set(projected.columns)
    with pytest.raises(ValidationError):
        load_dataset(out)  # official training loader must reject synthetic file


def test_output_boundary_collision_and_real_input_never_overwritten(feature_file, tmp_path):
    with pytest.raises(ValueError, match="ml/data/synthetic"):
        synthetic.generate(feature_file, CONTRACT_PATH, "fprog_1a_v1", 60, 1, tmp_path / "outside.csv")
    out, _, _ = generate(feature_file)
    before = out.read_bytes()
    with pytest.raises(FileExistsError):
        synthetic.generate(feature_file, CONTRACT_PATH, "fprog_1a_v1", 60, 1, out)
    assert out.read_bytes() == before


def test_explicit_general_class_only_and_opportunity_bounds(feature_file):
    with pytest.raises(ValueError, match="requires --class-code"):
        generate(feature_file, scenario="general_augmentation_v1")
    _, frame, manifest = generate(feature_file, name="general", scenario="general_augmentation_v1",
                                  class_code="FPROG_1B", material_opportunities=1)
    assert manifest["source_class_codes"] == ["FPROG_1B"]
    assert manifest["opportunities"]["quizzes"] == 0
    assert frame.quiz_attempt_count.eq(0).all() and frame.quiz_avg.isna().all()
    assert frame.lab_session_id.eq("SYN-FPROG1B").all()
    with pytest.raises(ValueError, match="below observed"):
        generate(feature_file, name="invalid", scenario="general_augmentation_v1",
                 class_code="FPROG_1B", material_opportunities=0)


def test_distribution_report_and_separation_flags(feature_file):
    out, frame, manifest = generate(feature_file)
    report = json.loads(out.with_suffix(".distributions.json").read_text())
    assert set(report["predictors"]) == set(CONTRACT["core"] + CONTRACT["optional"])
    assert set(report["predictors"]["quiz_avg"]["synthetic"]) >= {"count", "missing_percent", "mean", "median", "standard_deviation", "min", "max"}
    real = pd.DataFrame({f: [90.0] * 10 for f in CONTRACT["core"] + CONTRACT["optional"]})
    low = pd.DataFrame({f: [10.0] * 10 for f in CONTRACT["core"] + CONTRACT["optional"]})
    comparison = synthetic.distributions(real, low)
    assert any("quiz_avg" in flag and "separation" in flag for flag in comparison["flags"])
    assert manifest["distribution_flags"] == report["flags"]


def test_combination_preserves_origin_real_values_and_counts(feature_file):
    before = feature_file.read_bytes()
    out, syn, _ = generate(feature_file)
    combined = synthetic.OUTPUT_ROOT / "combined.csv"
    manifest = synthetic.combine(feature_file, out, combined)
    with combined.open(newline="") as stream:
        rows = list(csv.DictReader(stream))
    assert manifest["real_rows"] == 80 and manifest["synthetic_rows"] == 60 and manifest["total_rows"] == 140
    assert manifest["real_unique_students"] == 80
    assert sum(r["data_origin"] == "real" for r in rows) == 80
    assert sum(r["data_origin"] == "synthetic" for r in rows) == 60
    with feature_file.open(newline="") as stream:
        original = list(csv.DictReader(stream))
    for source, row in zip(original, rows):
        assert all(row[k] == v for k, v in source.items())
    assert feature_file.read_bytes() == before
    assert CONTRACT["target"] not in rows[0] and CONTRACT["ground_truth"] not in rows[0]
    with pytest.raises(ValidationError):
        load_dataset(combined)


def test_combination_rejects_modified_synthetic_file(feature_file):
    out, _, _ = generate(feature_file)
    with out.open("a") as stream:
        stream.write("tampering\n")
    with pytest.raises(ValueError, match="provenance/hash"):
        synthetic.combine(feature_file, out, synthetic.OUTPUT_ROOT / "bad.csv")


def test_no_database_or_network_access(feature_file, monkeypatch):
    def blocked(*args, **kwargs):
        pytest.fail("Network/database access is forbidden")
    monkeypatch.setattr(socket.socket, "connect", blocked)
    monkeypatch.setattr(socket, "create_connection", blocked)
    generate(feature_file)
    source = Path(synthetic.__file__).read_text()
    assert "import pymysql" not in source and "import sqlalchemy" not in source and "import sqlite3" not in source


def test_all_missing_score_is_not_fabricated(feature_file):
    with feature_file.open(newline="") as stream:
        rows = list(csv.DictReader(stream))
    for row in rows:
        row["task_avg"] = ""
    write_rows(feature_file, rows)
    _, frame, _ = generate(feature_file)
    assert frame.task_avg.isna().all()


def test_configuration_controls_profile_mixture(feature_file, monkeypatch):
    original = synthetic.read_scenario
    def scenario(name):
        config, _ = original(name)
        config = copy.deepcopy(config)
        for profile in config["profiles"].values():
            profile["weight"] = 0
        config["profiles"]["average"]["weight"] = 1
        return config, hashlib.sha256(json.dumps(config).encode()).hexdigest()
    monkeypatch.setattr(synthetic, "read_scenario", scenario)
    _, frame, manifest = generate(feature_file)
    assert set(frame.synthetic_profile) == {"average"}
    assert manifest["profile_counts"]["average"] == 60


@pytest.mark.parametrize("column", ["name", "academic_risk_status", "final_outcome"])
def test_unlabeled_loader_blocks_pii_and_label_columns(feature_file, column):
    with feature_file.open(newline="") as stream:
        rows = list(csv.DictReader(stream))
    for row in rows:
        row[column] = "FORBIDDEN-TEST-ONLY"
    write_rows(feature_file, rows)
    with pytest.raises(ValidationError):
        load_dataset(feature_file, labeled=False)


@pytest.mark.parametrize("column,value", [("quiz_avg", 101), ("quiz_attempt_count", 3),
                                         ("material_session_count", -1), ("materials_accessed", 2),
                                         ("attendance_rate", 100), ("activity_duration_seconds", 5),
                                         ("late_submission_count", 2), ("data_origin", "real")])
def test_synthetic_validation_rejects_impossible_or_unverified_values(feature_file, column, value):
    ds = load_dataset(feature_file, labeled=False)
    config, digest = synthetic.read_scenario("fprog_1a_v1")
    frame, details, _ = synthetic.synthesize(ds, config, digest, 60, 20261020)
    frame.loc[0, column] = value
    with pytest.raises(ValueError):
        synthetic.validate_synthetic(frame, details)


def test_synthetic_cli_requires_explicit_arguments_and_no_labels(feature_file, capsys):
    out = synthetic.OUTPUT_ROOT / "cli.csv"
    result = main(["synthetic", str(feature_file), "--schema", str(CONTRACT_PATH), "--scenario", "fprog_1a_v1",
                   "--rows", "60", "--seed", "20261020", "--output", str(out)])
    assert result == 0
    assert '"training_eligible": false' in capsys.readouterr().out
    with pytest.raises(SystemExit):
        main(["synthetic", str(feature_file)])
