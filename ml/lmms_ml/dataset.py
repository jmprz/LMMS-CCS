"""Strict local CSV validation. Outcomes/identifiers are never projected into X."""
from __future__ import annotations

import csv
import hashlib
import json
import math
import re
from dataclasses import dataclass
from datetime import datetime
from pathlib import Path

import numpy as np
import pandas as pd

CONTRACT_PATH = Path(__file__).with_name("contract.json")
CONTRACT = json.loads(CONTRACT_PATH.read_text(encoding="utf-8"))


class ValidationError(ValueError):
    def __init__(self, report: dict):
        self.report = report
        super().__init__("Dataset validation failed; inspect aggregate report errors.")


def sha256(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as stream:
        for chunk in iter(lambda: stream.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


@dataclass
class Dataset:
    path: Path
    frame: pd.DataFrame
    report: dict

    def predictors(self, include_attendance: bool = False) -> list[str]:
        return CONTRACT["core"] + (["attendance_rate"] if include_attendance else [])

    def x(self, include_attendance: bool = False) -> pd.DataFrame:
        selected = self.predictors(include_attendance)
        if any(name not in self.frame for name in selected):
            raise ValueError("Explicit optional predictor is absent from dataset.")
        # No select_dtypes or blanket drop-target selection: exact, ordered allowlist.
        return self.frame.loc[:, selected].copy()

    @property
    def y(self) -> np.ndarray:
        return self.frame[CONTRACT["target"]].map({"not_at_risk": 0, "at_risk": 1}).to_numpy()

    @property
    def groups(self) -> np.ndarray:
        return self.frame["student_group_key"].to_numpy()


def load_dataset(path: str | Path, *, labeled: bool = True) -> Dataset:
    """Default is the unchanged labeled-training contract; feature-only mode forbids Y/outcomes."""
    path = Path(path).resolve(strict=True)
    if not path.is_file() or path.suffix.lower() != ".csv":
        raise ValueError("A local CSV file is required.")
    digest = sha256(path)
    report = {"dataset_filename": path.name, "dataset_sha256": digest,
              "feature_version": CONTRACT["feature_version"], "errors": [], "warnings": [],
              "missing_required_columns": [], "unexpected_columns": [],
              "duplicate_student_class_rows": 0, "predictors": {}}
    errors, warnings = report["errors"], report["warnings"]
    with path.open(encoding="utf-8-sig", newline="") as stream:
        reader = csv.reader(stream, strict=True)
        try:
            header = next(reader)
            if len(header) != len(set(header)):
                errors.append("Duplicate CSV column names.")
            required = set(CONTRACT["core"] + ["student_group_key", "lab_session_id", "feature_cutoff_at", "feature_version"]
                           + ([CONTRACT["target"], CONTRACT["ground_truth"]] if labeled else []))
            allowed = set(CONTRACT["core"] + CONTRACT["optional"] + CONTRACT["structured"] +
                          CONTRACT["metadata"] + ([CONTRACT["target"], CONTRACT["ground_truth"]] if labeled else []))
            report["missing_required_columns"] = sorted(required - set(header))
            report["unexpected_columns"] = sorted(set(header) - allowed)
            if report["missing_required_columns"]:
                errors.append("Missing required columns.")
            if report["unexpected_columns"]:
                errors.append("Unapproved/leakage columns present.")
            rows = []
            for row in reader:
                if not row:
                    continue
                if len(row) != len(header):
                    errors.append("Malformed CSV row width.")
                    continue
                rows.append([cell.strip() for cell in row])
        except (csv.Error, StopIteration):
            errors.append("Empty or malformed CSV.")
            raise ValidationError(report) from None
    if errors:
        raise ValidationError(report)
    if not rows:
        errors.append("Dataset has no observations.")
        raise ValidationError(report)
    frame = pd.DataFrame(rows, columns=header)
    report.update(total_rows=len(frame), unique_students=frame.student_group_key.nunique(),
                  class_count=frame.lab_session_id.nunique())
    valid_groups = frame.student_group_key.str.fullmatch(r"[0-9a-f]{64}")
    valid_classes = frame.lab_session_id.str.fullmatch(r"[1-9][0-9]*")
    if not valid_groups.all() or not valid_classes.all():
        errors.append("Invalid or missing grouping/class metadata.")
    duplicates = int(frame.duplicated(["student_group_key", "lab_session_id"]).sum())
    report["duplicate_student_class_rows"] = duplicates
    if duplicates:
        errors.append("Duplicate student-class observations; none are silently removed.")
    if labeled:
        if not frame[CONTRACT["target"]].isin(CONTRACT["labels"]).all():
            errors.append("Unexpected or missing canonical target labels.")
        report["target_counts"] = {label: int((frame[CONTRACT["target"]] == label).sum()) for label in CONTRACT["labels"]}
        report["target_percentages"] = {label: round(100 * count / len(frame), 4) for label, count in report["target_counts"].items()}
        if 0 in report["target_counts"].values():
            warnings.append("Only one target class is present; training/splitting is blocked.")
    if set(frame.feature_version) != {"v5"}:
        errors.append("Dataset must contain only frozen feature version v5.")
    cutoffs = set(frame.feature_cutoff_at)
    report["feature_cutoffs"] = sorted(cutoffs)
    if len(cutoffs) != 1:
        errors.append("One shared feature cutoff is required.")
    for cutoff in cutoffs:
        try:
            parsed = datetime.strptime(cutoff, "%Y-%m-%d %H:%M:%S")
            if parsed.strftime("%Y-%m-%d %H:%M:%S") != cutoff:
                raise ValueError
        except ValueError:
            errors.append("Invalid feature cutoff format.")
    numeric = CONTRACT["core"] + CONTRACT["optional"] + CONTRACT["counts"] + [CONTRACT["ground_truth"]]
    for name in dict.fromkeys(numeric):
        if name not in frame:
            continue
        raw = frame[name]
        missing = raw.eq("")
        values = pd.to_numeric(raw.mask(missing), errors="coerce").astype(float)
        invalid = (~missing & (~np.isfinite(values))).sum()
        finite = values[np.isfinite(values)]
        negative = int((finite < 0).sum()) if name != CONTRACT["ground_truth"] else 0
        invalid_pct = int(((finite < 0) | (finite > 100)).sum()) if name in CONTRACT["percentages"] else 0
        fractional = int((finite % 1 != 0).sum()) if name in CONTRACT["counts"] else 0
        if invalid or negative or invalid_pct or fractional:
            errors.append(f"Invalid numeric values in {name}.")
        if name == CONTRACT["ground_truth"] and missing.any():
            errors.append("Official ground-truth source is missing; labels are not inferred.")
        stats = {"non_missing": int((~missing).sum()), "missing": int(missing.sum()),
                 "missing_percent": round(100 * float(missing.mean()), 4),
                 "invalid_numeric": int(invalid), "invalid_percentage": invalid_pct,
                 "negative_values": negative, "fractional_counts": fractional,
                 "min": float(finite.min()) if len(finite) else None,
                 "max": float(finite.max()) if len(finite) else None,
                 "constant": bool(len(finite) and finite.nunique() == 1),
                 "all_missing": bool(missing.all())}
        if name in CONTRACT["core"] + CONTRACT["optional"]:
            report["predictors"][name] = stats
            if stats["missing_percent"] >= 50:
                warnings.append(f"{name}: at least 50% missing (inspection warning, not an academic threshold).")
            if stats["constant"]:
                warnings.append(f"{name}: constant among observed values.")
        frame[name] = values
    for name, count_key in [("topic_quiz_performance_json", "attempt_count"), ("topic_task_performance_json", "graded_task_count")]:
        if name not in frame:
            continue
        covered = 0
        for value in frame[name]:
            try:
                topics = json.loads(value)
                if not isinstance(topics, dict):
                    raise ValueError
                for key, item in topics.items():
                    average = item["average"]
                    count = item[count_key]
                    if (not re.fullmatch(r"[1-9][0-9]*", key) or isinstance(average, bool) or
                        not isinstance(average, (int, float)) or not math.isfinite(average) or not 0 <= average <= 100 or
                        type(count) is not int or count < 1):
                        raise ValueError
                covered += bool(topics)
            except (ValueError, TypeError, KeyError):
                errors.append(f"Invalid structured topic data in {name}.")
                break
        report.setdefault("structured_topics", {})[name] = {"rows_with_evidence": covered, "encoding": "pending; never included in X"}
    report["all_missing_predictors"] = [k for k, v in report["predictors"].items() if v["all_missing"]]
    report["constant_predictors"] = [k for k, v in report["predictors"].items() if v["constant"]]
    report["errors"] = list(dict.fromkeys(errors))
    if sha256(path) != digest:
        report["errors"].append("Dataset changed during validation.")
    if report["errors"]:
        raise ValidationError(report)
    return Dataset(path, frame, report)


def validate_schema(path: Path, dataset: Dataset) -> dict:
    """Validate the existing Laravel companion; its input_sha256 is the UNLABELED input."""
    schema = json.loads(path.read_text(encoding="utf-8"))
    expected = {"model_x_core": CONTRACT["core"], "model_x_optional": CONTRACT["optional"],
                "structured_topic_encoding_pending": CONTRACT["structured"],
                "target_y": [CONTRACT["target"]], "ground_truth_source": [CONTRACT["ground_truth"]],
                "grouping_metadata": CONTRACT["metadata"], "excluded_predictors": CONTRACT["excluded"]}
    if schema.get("categories") != expected or schema.get("columns") != list(dataset.frame.columns):
        raise ValueError("Export schema categories/header disagree with frozen v5 contract.")
    if schema.get("feature_versions") != ["v5"] or set(schema.get("feature_cutoffs", [])) != set(dataset.report["feature_cutoffs"]):
        raise ValueError("Export schema cutoff/version mismatch.")
    criterion = schema.get("criterion", {})
    grade = criterion.get("passing_grade")
    if type(grade) not in (float, int) or not math.isfinite(grade) or criterion.get("passing_operator") not in ("gte", "lte"):
        raise ValueError("Export companion has no valid configured criterion.")
    if set(schema.get("target_values", {})) != set(CONTRACT["labels"]):
        raise ValueError("Export companion target vocabulary mismatch.")
    if schema.get("imputation") != "none" or schema.get("topic_encoding") != "pending":
        raise ValueError("Input must be an unprocessed frozen labeled export.")
    return schema
