"""Validation-only OOP research target contract. Never substitutes an official grade."""
import csv
import json
import math
from datetime import datetime
from pathlib import Path

import pandas as pd

from .dataset import CONTRACT, Dataset, load_dataset, sha256

CONTRACT_NAME = "oop-temporal-v1"
POLICY_VERSION = "research-later-academic-v1"
CUTOFF = "2026-10-15 17:59:59"
OUTCOME_METADATA = [
    "dataset_contract", "target_policy_version", "outcome_quiz_id", "outcome_completion_state",
    "outcome_score", "outcome_max_score", "outcome_percentage", "outcome_available_at", "outcome_closed_at",
    "outcome_event_at", "outcome_recorded_at", "outcome_attempt_key", "risk_reason", "data_origin", "outcome_origin",
    "experiment_group", "experiment_id", "experiment_assigned_at", "consented_at", "posttest_mapped_at",
    "eligibility_basis", "required_opportunity_verified", "exemption_status",
]
CLASS_ASSESSMENTS = {
    "4": {"class_code": "OOPRG_2B", "quiz": "11", "group": "control", "open": "2026-10-15 18:00:00", "close": "2026-10-16 01:00:00"},
    "8": {"class_code": "OOPRG_2A", "quiz": "9", "group": "experimental", "open": "2026-10-16 20:00:00", "close": "2026-10-17 07:59:00"},
}


def _date(value):
    try:
        parsed = datetime.strptime(value, "%Y-%m-%d %H:%M:%S")
        if parsed.strftime("%Y-%m-%d %H:%M:%S") != value:
            raise ValueError
        return parsed
    except (ValueError, TypeError):
        raise ValueError("Invalid temporal-cohort timestamp.") from None


def _number(value):
    try:
        parsed = float(value)
        if not math.isfinite(parsed):
            raise ValueError
        return parsed
    except (ValueError, TypeError):
        raise ValueError("Invalid temporal outcome numeric value.") from None


def load_temporal_dataset(path, schema_path):
    """Reuse the unchanged v5 feature validator, then independently validate Y/provenance."""
    path, schema_path = Path(path).resolve(strict=True), Path(schema_path).resolve(strict=True)
    dataset_hash, schema_hash = sha256(path), sha256(schema_path)
    schema = json.loads(schema_path.read_text(encoding="utf-8"))
    if (schema.get("dataset_contract") != CONTRACT_NAME or schema.get("target_policy_version") != POLICY_VERSION or
        schema.get("dataset_sha256") != dataset_hash or schema.get("training_authorized") is not False):
        raise ValueError("Explicit hash-bound, validation-only temporal schema required.")
    with path.open(encoding="utf-8-sig", newline="") as stream:
        reader = csv.reader(stream, strict=True)
        header = next(reader, [])
        if not header or len(header) != len(set(header)):
            raise ValueError("Invalid temporal CSV header.")
        values = [r for r in reader if r]
    if any(len(r) != len(header) for r in values) or not values:
        raise ValueError("Empty or malformed temporal CSV.")
    feature_allowed = CONTRACT["core"] + CONTRACT["optional"] + CONTRACT["structured"] + CONTRACT["metadata"]
    required = CONTRACT["core"] + ["student_group_key", "lab_session_id", "class_code", "feature_cutoff_at", "feature_version"] + OUTCOME_METADATA + [CONTRACT["target"]]
    if set(header) - set(feature_allowed + OUTCOME_METADATA + [CONTRACT["target"]]) or set(required) - set(header):
        raise ValueError("Unapproved or missing temporal dataset columns; official outcomes and PII are forbidden.")
    feature_header = [h for h in header if h in feature_allowed]
    categories = {"model_x_core": CONTRACT["core"], "model_x_optional": CONTRACT["optional"],
                  "structured_topic_encoding_pending": CONTRACT["structured"], "target_y": [CONTRACT["target"]],
                  "grouping_metadata": [h for h in feature_header if h in CONTRACT["metadata"]],
                  "outcome_metadata_never_x": OUTCOME_METADATA, "excluded_predictors": CONTRACT["excluded"]}
    policy = schema.get("policy", {})
    if (schema.get("columns") != header or schema.get("categories") != categories or
        policy.get("performance_threshold") != 70 or policy.get("comparison_direction") != "gte" or
        policy.get("prediction_cutoff") != CUTOFF or policy.get("nonparticipation_score") != "NULL" or
        policy.get("eligibility_basis") != "mapped_consented_posttest_roster" or
        policy.get("nonparticipation_label") != "at_risk" or not policy.get("approval_reference")):
        raise ValueError("Temporal schema/policy disagrees with the approved cohort contract.")
    records = [dict(zip(header, row)) for row in values]
    source_name = schema.get("source_feature_filename")
    if not isinstance(source_name, str) or Path(source_name).name != source_name or "/" in source_name or "\\" in source_name:
        raise ValueError("A local feature companion basename is required.")
    source = schema_path.parent / source_name
    feature_hash = sha256(source)
    if feature_hash != schema.get("feature_sha256"):
        raise ValueError("Reconstructed feature companion hash mismatch.")
    base = load_dataset(source, labeled=False)
    with source.open(encoding="utf-8-sig", newline="") as stream:
        reader = csv.DictReader(stream)
        source_records = [r for r in reader if any(r.values())]
        if reader.fieldnames != feature_header or source_records != [{k: r[k] for k in feature_header} for r in records]:
            raise ValueError("Labeled rows differ from their frozen-formula feature reconstruction.")
    if base.report["feature_cutoffs"] != [CUTOFF]:
        raise ValueError("Temporal cohort must use the approved shared prediction cutoff.")
    reasons = {"nonparticipation": 0, "below_threshold": 0, "meets_threshold": 0}
    events = set()
    generated_at = _date(schema.get("generated_at"))
    for row in records:
        class_config = CLASS_ASSESSMENTS.get(row["lab_session_id"])
        if (not class_config or row["class_code"] != class_config["class_code"] or
            row["outcome_quiz_id"] != class_config["quiz"] or row["experiment_group"] != class_config["group"] or
            row["outcome_available_at"] != class_config["open"] or row["outcome_closed_at"] != class_config["close"] or
            row["dataset_contract"] != CONTRACT_NAME or row["target_policy_version"] != POLICY_VERSION or
            row["data_origin"] != "real" or row["outcome_origin"] != "research_observed" or
            row["experiment_id"] != "1" or row["eligibility_basis"] != "mapped_consented_posttest_roster" or
            row["required_opportunity_verified"] != "true" or row["exemption_status"] != "none_documented"):
            raise ValueError("Invalid temporal cohort eligibility, origin or assessment mapping.")
        cutoff, opened, closed = _date(row["feature_cutoff_at"]), _date(row["outcome_available_at"]), _date(row["outcome_closed_at"])
        if not cutoff < opened <= closed <= generated_at:
            raise ValueError("Outcome availability overlaps the predictor period.")
        if any(_date(row[k]) > cutoff for k in ["experiment_assigned_at", "consented_at", "posttest_mapped_at"]):
            raise ValueError("Assignment/consent/mapping did not precede the cutoff.")
        maximum = _number(row["outcome_max_score"])
        if maximum != 20:
            raise ValueError("Unexpected outcome denominator; review source assessment changes.")
        reason = row["risk_reason"]
        if row["outcome_completion_state"] == "not_completed":
            if any(row[k] != "" for k in ["outcome_score", "outcome_percentage", "outcome_event_at", "outcome_recorded_at", "outcome_attempt_key"]):
                raise ValueError("Nonparticipation must retain NULL score, percentage and event fields.")
            expected_label, expected_reason = "at_risk", "nonparticipation"
        elif row["outcome_completion_state"] == "completed":
            score, percent = _number(row["outcome_score"]), _number(row["outcome_percentage"])
            if not 0 <= score <= maximum or not math.isclose(percent, 100 * score / maximum, abs_tol=1e-8):
                raise ValueError("Outcome percentage disagrees with the recorded score and denominator.")
            event, recorded = _date(row["outcome_event_at"]), _date(row["outcome_recorded_at"])
            key = row["outcome_attempt_key"]
            if not (cutoff < event and opened <= event <= closed and event <= recorded <= generated_at):
                raise ValueError("Outcome event violates temporal separation.")
            if not key.startswith("quiz_attempt:") or not key.removeprefix("quiz_attempt:").isdigit() or key in events:
                raise ValueError("Invalid or reused outcome attempt key.")
            events.add(key)
            expected_label, expected_reason = ("at_risk", "below_threshold") if percent < 70 else ("not_at_risk", "meets_threshold")
        else:
            raise ValueError("Invalid outcome completion state.")
        if row[CONTRACT["target"]] != expected_label or reason != expected_reason:
            raise ValueError("Canonical target/reason disagrees with approved temporal outcome policy.")
        reasons[reason] += 1
    if schema.get("row_count") != len(records) or schema.get("reason_counts") != reasons:
        raise ValueError("Temporal export counts disagree with its schema.")
    frame = base.frame.copy()
    for name in OUTCOME_METADATA + [CONTRACT["target"]]:
        frame[name] = [r[name] for r in records]
    for name in ["outcome_score", "outcome_max_score", "outcome_percentage"]:
        frame[name] = pd.to_numeric(frame[name].replace("", None), errors="raise")
    target_counts = {label: int((frame[CONTRACT["target"]] == label).sum()) for label in CONTRACT["labels"]}
    report = {**base.report, "dataset_filename": path.name, "dataset_sha256": dataset_hash,
              "schema_sha256": schema_hash, "feature_sha256": feature_hash, "dataset_contract": CONTRACT_NAME, "target_policy_version": POLICY_VERSION,
              "target_counts": target_counts, "target_percentages": {k: round(100 * v / len(frame), 4) for k, v in target_counts.items()},
              "risk_reason_counts": reasons, "null_nonparticipation_outcomes": reasons["nonparticipation"],
              "excluded_predictors": CONTRACT["excluded"], "metadata_never_x": OUTCOME_METADATA + CONTRACT["metadata"] + [CONTRACT["target"]],
              "training_authorized": False, "validation_only": True}
    if sha256(path) != dataset_hash or sha256(schema_path) != schema_hash or sha256(source) != feature_hash:
        raise ValueError("Temporal input changed during validation.")
    return Dataset(path, frame.loc[:, header], report)
