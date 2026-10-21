"""Fabricated temporary software fixtures only; never reads the genuine research cohort."""
import csv
import hashlib
import json

import pytest

from lmms_ml.dataset import CONTRACT, ValidationError, load_dataset, sha256
from lmms_ml.runner import main
from lmms_ml.temporal_dataset import CLASS_ASSESSMENTS, CONTRACT_NAME, CUTOFF, OUTCOME_METADATA, POLICY_VERSION, load_temporal_dataset


def write_csv(path, rows):
    with path.open("w", encoding="utf-8", newline="") as stream:
        writer = csv.DictWriter(stream, fieldnames=list(rows[0]))
        writer.writeheader()
        writer.writerows(rows)


@pytest.fixture
def temporal(tmp_path):
    features, rows = [], []
    for i, (class_id, score) in enumerate([("4", 14), ("4", None), ("8", 13), ("8", 0)]):
        mapping = CLASS_ASSESSMENTS[class_id]
        feature = {"student_group_key": hashlib.sha256(f"SOFTWARE-TEST-ONLY-{i}".encode()).hexdigest(),
                   "lab_session_id": class_id, "class_code": mapping["class_code"], "feature_cutoff_at": CUTOFF,
                   "feature_version": "v5", **{c: 0 for c in CONTRACT["core"]}, "attendance_rate": "",
                   **{c: "{}" for c in CONTRACT["structured"]}}
        feature.update(quiz_avg=50+i, task_avg="", reading_avg_seconds="", activity_duration_seconds="")
        features.append(feature)
        row = {**feature, **{c: "" for c in OUTCOME_METADATA}, "academic_risk_status": ""}
        completed = score is not None
        row.update(dataset_contract=CONTRACT_NAME, target_policy_version=POLICY_VERSION,
                   outcome_quiz_id=mapping["quiz"], outcome_completion_state="completed" if completed else "not_completed",
                   outcome_score=score if completed else "", outcome_max_score=20, outcome_percentage=5*score if completed else "",
                   outcome_available_at=mapping["open"], outcome_closed_at=mapping["close"],
                   outcome_event_at=mapping["close"] if completed else "", outcome_recorded_at=mapping["close"] if completed else "",
                   outcome_attempt_key=f"quiz_attempt:{i+1}" if completed else "",
                   risk_reason="nonparticipation" if not completed else ("below_threshold" if score<14 else "meets_threshold"),
                   data_origin="real", outcome_origin="research_observed", experiment_group=mapping["group"], experiment_id=1,
                   experiment_assigned_at="2026-10-13 17:34:00", consented_at="2026-10-13 17:35:00", posttest_mapped_at="2026-10-13 19:10:00",
                   eligibility_basis="mapped_consented_posttest_roster", required_opportunity_verified="true", exemption_status="none_documented",
                   academic_risk_status="not_at_risk" if completed and score>=14 else "at_risk")
        rows.append(row)
    feature_path, dataset_path, schema_path = tmp_path/"test.features.csv", tmp_path/"test.csv", tmp_path/"test.schema.json"
    write_csv(feature_path, features)
    write_csv(dataset_path, rows)
    schema = {"dataset_contract": CONTRACT_NAME, "target_policy_version": POLICY_VERSION, "dataset_sha256": sha256(dataset_path),
              "feature_sha256": sha256(feature_path), "source_feature_filename": feature_path.name,
              "training_authorized": False, "generated_at": "2026-10-21 00:00:00", "columns": list(rows[0]), "row_count": 4,
              "reason_counts": {"nonparticipation": 1, "below_threshold": 2, "meets_threshold": 1},
              "categories": {"model_x_core": CONTRACT["core"], "model_x_optional": CONTRACT["optional"],
                             "structured_topic_encoding_pending": CONTRACT["structured"], "target_y": [CONTRACT["target"]],
                             "grouping_metadata": [h for h in features[0] if h in CONTRACT["metadata"]],
                             "outcome_metadata_never_x": OUTCOME_METADATA, "excluded_predictors": CONTRACT["excluded"]},
              "policy": {"performance_threshold": 70, "comparison_direction": "gte", "prediction_cutoff": CUTOFF,
                         "nonparticipation_score": "NULL", "nonparticipation_label": "at_risk", "approval_reference": "SOFTWARE-TEST-ONLY",
                         "eligibility_basis": "mapped_consented_posttest_roster"}}
    schema_path.write_text(json.dumps(schema))
    return dataset_path, schema_path, rows, schema


def rewrite(fixture):
    path, sidecar, rows, schema = fixture
    write_csv(path, rows)
    schema['dataset_sha256'] = sha256(path)
    sidecar.write_text(json.dumps(schema))


def test_null_nonparticipation_is_distinct_from_completed_zero_and_equality_passes(temporal):
    path, sidecar, _, _ = temporal
    data = load_temporal_dataset(path, sidecar)
    assert data.report['total_rows'] == data.report['unique_students'] == 4
    assert data.report['class_count'] == 2
    assert data.report['target_counts'] == {'not_at_risk': 1, 'at_risk': 3}
    assert data.frame.iloc[1].outcome_percentage != data.frame.iloc[1].outcome_percentage  # NULL/NaN, never zero.
    assert data.frame.iloc[3].outcome_percentage == 0
    assert data.frame.iloc[0].academic_risk_status == 'not_at_risk'
    assert list(data.x()) == CONTRACT['core']
    assert set(OUTCOME_METADATA).isdisjoint(data.x().columns)
    assert {'student_group_key','lab_session_id','academic_risk_status'}.isdisjoint(data.x().columns)
    assert 'activity_duration_seconds' in data.report['all_missing_predictors']
    assert data.report['training_authorized'] is False


@pytest.mark.parametrize('column,value', [
    ('outcome_percentage', 0), ('outcome_score', 0), ('outcome_attempt_key','quiz_attempt:1'),
    ('outcome_event_at','2026-10-16 00:00:00'), ('risk_reason','below_threshold'), ('academic_risk_status','not_at_risk')])
def test_nonparticipation_cannot_be_filled_or_relabelled(temporal, column, value):
    temporal[2][1][column] = value
    rewrite(temporal)
    with pytest.raises(ValueError):
        load_temporal_dataset(*temporal[:2])


@pytest.mark.parametrize('column,value', [
    ('outcome_percentage', 99), ('academic_risk_status','at_risk'), ('risk_reason','nonparticipation'),
    ('outcome_quiz_id',9), ('experiment_group','experimental'), ('data_origin','synthetic'),
    ('exemption_status','excused'), ('required_opportunity_verified','false'),
    ('consented_at','2026-10-16 00:00:00'), ('outcome_event_at',CUTOFF),
    ('outcome_recorded_at','2026-10-22 00:00:00')])
def test_temporal_integrity_and_policy_checks(temporal, column, value):
    temporal[2][0][column] = value
    rewrite(temporal)
    with pytest.raises(ValueError):
        load_temporal_dataset(*temporal[:2])


def test_frozen_reconstruction_must_match_labeled_predictors(temporal):
    temporal[2][0]['quiz_avg'] = 99
    rewrite(temporal)
    with pytest.raises(ValueError, match='feature reconstruction'):
        load_temporal_dataset(*temporal[:2])


def test_official_loader_still_rejects_temporal_artifacts(temporal):
    with pytest.raises(ValidationError):
        load_dataset(temporal[0])
    assert 'final_outcome' not in temporal[2][0]


def test_cli_requires_explicit_contract_and_schema_and_never_trains(temporal, tmp_path, capsys):
    path, sidecar, _, _ = temporal
    out = tmp_path/'validation.json'
    assert main(['validate',str(path),'--contract',CONTRACT_NAME,'--schema',str(sidecar),'--report',str(out)]) == 0
    assert json.loads(out.read_text())['validation_only'] is True
    assert main(['validate',str(path),'--contract',CONTRACT_NAME]) == 2
    assert main(['validate',str(path)]) == 2
    assert not list(tmp_path.glob('*.joblib'))
    with pytest.raises(SystemExit):
        main(['train',str(path),'--contract',CONTRACT_NAME,'--output',str(tmp_path/'forbidden')])
