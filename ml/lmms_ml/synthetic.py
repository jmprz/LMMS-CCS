"""Controlled feature-space simulations, never participant reconstruction or labeled training data."""
from __future__ import annotations

import csv
import hashlib
import json
import math
import re
from datetime import datetime, timezone
from pathlib import Path

import numpy as np
import pandas as pd

from .dataset import CONTRACT, CONTRACT_PATH, Dataset, load_dataset, sha256

GENERATOR_VERSION = "synthetic_features_v1"
ML_ROOT = Path(__file__).resolve().parents[1]
OUTPUT_ROOT = ML_ROOT / "data" / "synthetic"
SCENARIOS = ("fprog_1a_v1", "fprog_1a_v2", "general_augmentation_v1")
PROVENANCE = ["data_origin", "synthetic_scenario", "synthetic_run_id", "synthetic_profile"]
FEATURE_COLUMNS = CONTRACT["core"] + CONTRACT["optional"] + CONTRACT["structured"]
OUTPUT_COLUMNS = ["student_group_key", "lab_session_id", "class_code", "feature_cutoff_at", "feature_version",
                  "eligible_session_count", "assigned_task_count", "tagged_quiz_count", "untagged_quiz_count",
                  "tagged_task_count", "untagged_task_count"] + FEATURE_COLUMNS + PROVENANCE


def read_scenario(name):
    if name not in SCENARIOS:
        raise ValueError("Choose an explicitly supported synthetic scenario.")
    path = ML_ROOT / "scenarios" / (name + ".json")
    config = json.loads(path.read_text(encoding="utf-8"))
    if config.get("scenario") != name or config.get("verified_measurements") != "disabled" or config.get("structured_topics") != "empty":
        raise ValueError("This generator authorizes no verified measurement or structured-topic fabrication.")
    if type(config.get("minimum_reference_rows")) is not int or config["minimum_reference_rows"] < 2:
        raise ValueError("Scenario reference size must be at least two.")
    if type(config.get("minimum_observed_values")) is not int or config["minimum_observed_values"] < 2:
        raise ValueError("Scenario minimum observed values must be at least two.")
    for weight in ("empirical_score_weight", "empirical_correlation_weight"):
        if not 0 <= config[weight] <= 1:
            raise ValueError("Scenario empirical weights must be within 0–1.")
    profiles = config.get("profiles", {})
    if not profiles or not math.isclose(sum(p["weight"] for p in profiles.values()), 1.0):
        raise ValueError("Profile weights must sum to one.")
    for name, profile in profiles.items():
        if not re.fullmatch(r"[a-z_]+", name):
            raise ValueError("Profile names must be non-identifying scenario labels.")
        if not 0 <= profile["score_mean"] <= 100 or not 0 < profile["score_sd"] <= 100:
            raise ValueError("Invalid scenario score parameters.")
        for key in ("weight", "engagement", "submission", "late", "missing"):
            if not isinstance(profile[key], (int, float)) or not math.isfinite(profile[key]) or not 0 <= profile[key] <= 1:
                raise ValueError("Invalid profile probabilities.")
    return config, sha256(path)


def validate_feature_contract(path):
    """Explicit ML-side feature contract; no labeled schema or passing criterion needed."""
    if json.loads(Path(path).read_text(encoding="utf-8")) != CONTRACT:
        raise ValueError("--schema must be the unchanged ML v5 contract.json, not a labeled export schema.")


def stats(series):
    values = pd.to_numeric(series, errors="coerce")
    available = values.dropna()
    return {"count": len(available), "missing": int(values.isna().sum()),
            "missing_percent": round(100 * float(values.isna().mean()), 4),
            "mean": float(available.mean()) if len(available) else None,
            "median": float(available.median()) if len(available) else None,
            "standard_deviation": float(available.std(ddof=1)) if len(available) > 1 else None,
            "min": float(available.min()) if len(available) else None,
            "max": float(available.max()) if len(available) else None}


def distributions(real, generated):
    report = {"predictors": {}, "flags": [], "interpretation": "Inspection only; no regeneration, calibration or claim of empirical equivalence."}
    for feature in CONTRACT["core"] + CONTRACT["optional"]:
        a = stats(real[feature]) if feature in real else stats(pd.Series([np.nan] * len(real)))
        b = stats(generated[feature])
        smd = None
        nonoverlap = False
        if a["count"] and b["count"]:
            nonoverlap = b["max"] < a["min"] or b["min"] > a["max"]
            variance = ((a["standard_deviation"] or 0) ** 2 + (b["standard_deviation"] or 0) ** 2) / 2
            smd = abs(a["mean"] - b["mean"]) / math.sqrt(variance) if variance > 0 else None
            if nonoverlap or (smd is not None and smd >= 1.5) or (variance == 0 and a["mean"] != b["mean"]):
                report["flags"].append(f"{feature}: severe observed distribution separation.")
        if abs(a["missing_percent"] - b["missing_percent"]) >= 50:
            report["flags"].append(f"{feature}: missingness differs by at least 50 percentage points.")
        report["predictors"][feature] = {"real_reference": a, "synthetic": b,
                                           "absolute_standardized_mean_difference": smd, "nonoverlapping_observed_ranges": nonoverlap}
    report["inspection_rules"] = {"severe_absolute_smd": 1.5, "missingness_difference_percentage_points": 50,
                                  "not_academic_or_target_thresholds": True}
    return report


def profile_allocation(profiles, rows, rng):
    names = list(profiles)
    exact = np.array([profiles[name]["weight"] * rows for name in names])
    counts = np.floor(exact).astype(int)
    for index in np.argsort(-(exact - counts), kind="stable")[:rows - int(counts.sum())]:
        counts[index] += 1
    assignment = np.repeat(names, counts)
    rng.shuffle(assignment)
    return assignment, dict(zip(names, map(int, counts)))


def reference_and_opportunities(dataset, config, class_code, material_opportunities):
    frame = dataset.frame
    if "class_code" not in frame:
        raise ValueError("A combined-class ML-ready export with class_code is required.")
    if config["scenario"] in ("fprog_1a_v1", "fprog_1a_v2"):
        if class_code is not None or material_opportunities is not None:
            raise ValueError("FPROG scenario opportunities/references are explicitly configured in its JSON.")
        codes = config["source_class_codes"]
        if not set(codes).issubset(set(frame.class_code)):
            raise ValueError("Both configured FPROG reference classes must exist in the real export.")
        reference = frame[frame.class_code.isin(codes)]
        opportunities = config["opportunities"].copy()
        key, code = config["synthetic_class_key"], config["class_code"]
    else:
        if not class_code or material_opportunities is None:
            raise ValueError("General augmentation requires --class-code and explicit --material-opportunities; material availability is not present in frozen exports.")
        if not re.fullmatch(r"[A-Za-z0-9_-]{1,80}", class_code):
            raise ValueError("Use a non-identifying class code.")
        reference = frame[frame.class_code == class_code]
        if reference.lab_session_id.nunique() != 1:
            raise ValueError("Selected class must resolve to exactly one exported lab session.")
        for column in ("tagged_quiz_count", "untagged_quiz_count", "assigned_task_count"):
            if column not in reference or reference[column].isna().any() or reference[column].nunique() != 1:
                raise ValueError("General scenario requires consistent opportunity metadata in its reference class.")
        opportunities = {"quizzes": int(reference.tagged_quiz_count.iloc[0] + reference.untagged_quiz_count.iloc[0]),
                         "tasks": int(reference.assigned_task_count.iloc[0]), "materials": material_opportunities,
                         "max_sessions_per_material": config["max_sessions_per_material"],
                         "max_activity_events": config["max_activity_events"]}
        key, code = "SYN-" + re.sub(r"[^A-Za-z0-9]", "", class_code), class_code
    if len(reference) < config["minimum_reference_rows"]:
        raise ValueError("Insufficient empirical reference rows for the chosen scenario/class.")
    for name, value in opportunities.items():
        if type(value) is not int or not 0 <= value <= 10000:
            raise ValueError("Opportunity bounds must be nonnegative integers at most 10000.")
        if name.startswith("max_") and value == 0:
            raise ValueError("Session/event upper bounds must be positive.")
    if opportunities["materials"] < reference.materials_accessed.max():
        raise ValueError("Configured material availability is below observed distinct accesses.")
    return reference, opportunities, key, code


def empirical_correlation(reference, weight):
    columns = ["quiz_avg", "task_avg", "submission_rate", "material_session_count", "activity_count"]
    corr = reference[columns].corr(min_periods=3).fillna(0).to_numpy()
    np.fill_diagonal(corr, 1)
    corr = weight * corr + (1 - weight) * np.eye(len(columns))
    eigenvalues, eigenvectors = np.linalg.eigh(corr)
    corr = (eigenvectors * np.maximum(eigenvalues, 0.05)) @ eigenvectors.T
    scales = np.sqrt(np.diag(corr))
    return corr / np.outer(scales, scales), columns


def synthesize(dataset: Dataset, config, scenario_sha, rows, seed, class_code=None, material_opportunities=None):
    if type(rows) is not int or not 1 <= rows <= 100000 or type(seed) is not int or seed < 0:
        raise ValueError("Explicit rows (1–100000) and nonnegative integer seed are required.")
    reference, opp, key, code = reference_and_opportunities(dataset, config, class_code, material_opportunities)
    version = "synthetic_features_v2" if config["scenario"] == "fprog_1a_v2" else GENERATOR_VERSION
    run_payload = [version, dataset.report["dataset_sha256"], scenario_sha, rows, seed, class_code, material_opportunities]
    run_id = hashlib.sha256(json.dumps(run_payload, separators=(",", ":")).encode()).hexdigest()
    rng = np.random.default_rng(seed)
    assignment, allocation = profile_allocation(config["profiles"], rows, rng)
    corr, correlation_columns = empirical_correlation(reference, config["empirical_correlation_weight"])
    latent = rng.multivariate_normal(np.zeros(5), corr, size=rows)
    globally_empty = [f for f in CONTRACT["core"] + CONTRACT["optional"] if f not in dataset.frame or dataset.frame[f].isna().all()]
    supported_score = {f: bool(reference[f].notna().sum() >= config["minimum_observed_values"] and f not in globally_empty) for f in ("quiz_avg", "task_avg")}
    means = {f: float(reference[f].mean()) if supported_score[f] else 0 for f in supported_score}
    # Published quiz/task opportunities prevent structural no-opportunity rows
    # from being interpreted as individual nonparticipation in the reference.
    quiz_reference = reference
    if {"tagged_quiz_count", "untagged_quiz_count"}.issubset(reference.columns):
        quiz_reference = reference[(reference.tagged_quiz_count + reference.untagged_quiz_count) > 0]
    quiz_coverage = float(quiz_reference.quiz_avg.notna().mean()) if len(quiz_reference) else 0
    task_reference = reference[reference.assigned_task_count > 0] if "assigned_task_count" in reference else reference
    task_grade_coverage = float(task_reference.task_avg.notna().mean()) if len(task_reference) else 0
    material_coverage = float((reference.materials_accessed > 0).mean())
    sessions = reference.loc[reference.materials_accessed > 0, "material_session_count"]
    repeat_mean = max(0.0, float(sessions.mean()) - 1) if len(sessions) else 0
    event_mean = max(0.0, float(reference.activity_count.mean()))
    generated = []
    for index, name in enumerate(assignment):
        profile = config["profiles"][str(name)]
        z = latent[index]
        engagement = float(np.clip(profile["engagement"] + 0.18 * z[3] + rng.normal(0, 0.08), 0.01, 0.99))
        missing = float(np.clip(profile["missing"] + rng.normal(0, 0.04), 0, 0.95))
        q_probability = float(np.clip(quiz_coverage * (0.45 + engagement) * (1 - missing), 0, 1))
        attempts = int(rng.binomial(opp["quizzes"], q_probability)) if supported_score["quiz_avg"] and "quiz_attempt_count" not in globally_empty else 0
        submitted = int(rng.binomial(opp["tasks"], np.clip(profile["submission"] + 0.10 * z[2], 0, 1))) if "submission_rate" not in globally_empty else 0
        def score(feature, component):
            mean = ((1 - config["empirical_score_weight"]) * profile["score_mean"]
                    + config["empirical_score_weight"] * means[feature])
            value = mean + profile["score_sd"] * (0.65 * z[component] + 0.75 * rng.normal())
            return round(float(np.clip(value, 0, 100)), 2)
        quiz = score("quiz_avg", 0) if attempts else None
        task = score("task_avg", 1) if submitted and supported_score["task_avg"] and rng.random() < task_grade_coverage * (1 - missing) else None
        access_probability = float(np.clip(material_coverage * (0.4 + engagement) * (1 - missing), 0, 1))
        accesses = int(rng.binomial(opp["materials"], access_probability))
        session_count = accesses + int(rng.poisson(repeat_mean * accesses * (0.4 + engagement)))
        session_count = min(session_count, opp["max_sessions_per_material"] * accesses)
        events = min(opp["max_activity_events"], int(rng.poisson(min(10000, event_mean * (0.3 + engagement) * (1 - missing)))))
        row = dict(student_group_key=f"{key}-{run_id[:12]}-{index + 1:06d}", lab_session_id=key, class_code=code,
                   feature_cutoff_at=dataset.report["feature_cutoffs"][0], feature_version="v5",
                   eligible_session_count=0, assigned_task_count=opp["tasks"],
                   tagged_quiz_count=0, untagged_quiz_count=opp["quizzes"], tagged_task_count=0, untagged_task_count=opp["tasks"],
                   quiz_avg=quiz, quiz_attempt_count=attempts, task_avg=task,
                   submission_rate=round(100 * submitted / opp["tasks"], 2) if opp["tasks"] else None,
                   late_submission_count=int(rng.binomial(submitted, profile["late"])), materials_accessed=accesses,
                   material_session_count=session_count, reading_total_seconds=0, reading_avg_seconds=None,
                   activity_count=events, activity_duration_seconds=None, attendance_rate=None,
                   topic_quiz_performance_json="{}", topic_task_performance_json="{}",
                   data_origin="synthetic", synthetic_scenario=config["scenario"], synthetic_run_id=run_id, synthetic_profile=str(name))
        for feature in globally_empty:
            row[feature] = None
        generated.append(row)
    frame = pd.DataFrame(generated, columns=OUTPUT_COLUMNS)
    details = {"scenario_config": config, "scenario_sha256": scenario_sha, "synthetic_run_id": run_id,
               "profile_counts": allocation, "opportunities": opp, "source_class_codes": sorted(reference.class_code.unique().tolist()),
               "source_class_ids": sorted(reference.lab_session_id.unique().tolist()), "reference_rows": len(reference),
               "reference_unique_students": reference.student_group_key.nunique(),
               "generation_rules": {"scores": "profile/empirical aggregate-mean blend plus correlated latent and independent residual noise; clipped only at 0/100",
                                    "counts": "bounded binomial opportunities and conditional Poisson sessions/events",
                                    "correlation_columns": correlation_columns, "regularized_correlation": corr.tolist(),
                                    "score_reference_means": means, "score_reference_supported": supported_score,
                                    "duration": "disabled: no verified collection provenance in feature-only input; totals zero and averages missing",
                                    "topics": "empty: no synthetic topic assignment/performance authorized in these scenarios"},
               "missingness_rules": {"globally_all_missing_preserved": globally_empty,
                                     "opportunity_conditioned_quiz_coverage": quiz_coverage, "due_task_grade_coverage": task_grade_coverage,
                                     "material_access_coverage": material_coverage,
                                     "no_quiz_evidence": "attempt_count=0 and quiz_avg=NULL",
                                     "no_due_tasks": "task_avg/submission_rate=NULL; late count=0",
                                     "verified_attendance_activity_material_duration": "not generated"},
               "warnings": ["Virtual opportunities are simulation inputs, not evidence that real FPROG_1A received assessments.",
                            "No real student is copied or assigned synthetic observations; synthetic identifiers do not represent participants.",
                            "Source cutoff retained for comparison; this is not a reconstruction of historical classroom activity."]}
    if config["scenario"] == "fprog_1a_v2":
        from .synthetic_v2 import revise_measurements
        frame, mechanics = revise_measurements(frame, reference, config, seed)
        details["v2_measurement_mechanics"] = mechanics
    validate_synthetic(frame, details)
    return frame, details, distributions(reference, frame)


def validate_synthetic(frame, manifest):
    if manifest["scenario_config"]["scenario"] == "fprog_1a_v2":
        from .synthetic_v2 import validate_measurements
        validate_measurements(frame)
    if list(frame.columns) != OUTPUT_COLUMNS or not len(frame):
        raise ValueError("Invalid synthetic feature schema or empty output.")
    if not frame.student_group_key.str.fullmatch(r"SYN-[A-Za-z0-9]+-[0-9a-f]{12}-[0-9]{6}").all() or frame.student_group_key.duplicated().any():
        raise ValueError("Synthetic grouping identifiers must be unique unmistakable SYN identifiers.")
    if not frame.lab_session_id.str.fullmatch(r"SYN-[A-Za-z0-9]+").all() or set(frame.data_origin) != {"synthetic"}:
        raise ValueError("Synthetic provenance/class namespace is missing.")
    if set(frame.synthetic_scenario) != {manifest["scenario_config"]["scenario"]} or set(frame.synthetic_run_id) != {manifest["synthetic_run_id"]}:
        raise ValueError("Synthetic scenario/run provenance mismatch.")
    if not set(frame.synthetic_profile).issubset(manifest["scenario_config"]["profiles"]):
        raise ValueError("Synthetic profile provenance mismatch.")
    opp = manifest["opportunities"]
    for feature in CONTRACT["core"] + CONTRACT["optional"]:
        values = pd.to_numeric(frame[feature], errors="coerce")
        if (frame[feature].notna() & values.isna()).any() or np.isinf(values).any() or (values.dropna() < 0).any():
            raise ValueError("Invalid synthetic numeric values.")
        if feature in CONTRACT["counts"] and (values.dropna() % 1 != 0).any():
            raise ValueError("Synthetic counts must be integers.")
        if feature in CONTRACT["percentages"] and (values.dropna() > 100).any():
            raise ValueError("Synthetic percentage outside 0–100.")
    if (frame.quiz_attempt_count > opp["quizzes"]).any() or (frame.materials_accessed > opp["materials"]).any():
        raise ValueError("Synthetic opportunity bounds exceeded.")
    if (frame.material_session_count < frame.materials_accessed).any() or (frame.material_session_count > frame.materials_accessed * opp["max_sessions_per_material"]).any():
        raise ValueError("Invalid material session/access relationship.")
    if (frame.activity_count > opp["max_activity_events"]).any() or (frame.late_submission_count > opp["tasks"]).any():
        raise ValueError("Synthetic activity/lateness bounds exceeded.")
    for _, row in frame.iterrows():
        if pd.notna(row.quiz_avg) and not row.quiz_attempt_count > 0:
            raise ValueError("Quiz score without a valid quiz opportunity/attempt.")
        if opp["tasks"]:
            if pd.isna(row.submission_rate):
                if pd.notna(row.task_avg) or (pd.notna(row.late_submission_count) and row.late_submission_count > 0):
                    raise ValueError("Task evidence cannot be supported when submission evidence is unknown.")
                continue
            submitted = row.submission_rate * opp["tasks"] / 100
            if abs(row.submission_rate - round(100 * round(submitted) / opp["tasks"], 2)) > 1e-8 or row.late_submission_count > round(submitted):
                raise ValueError("Submission rate/lateness does not match discrete task opportunities.")
            if pd.notna(row.task_avg) and submitted < 1:
                raise ValueError("Task grade without a submission.")
        elif pd.notna(row.submission_rate) or pd.notna(row.task_avg) or row.late_submission_count != 0:
            raise ValueError("Task evidence without task opportunities.")
    for feature in ("attendance_rate", "activity_duration_seconds", "reading_avg_seconds"):
        if frame[feature].notna().any():
            raise ValueError("Unsupported verified/reading-duration measurement generated.")
    if frame.reading_total_seconds.fillna(0).ne(0).any() or frame.eligible_session_count.ne(0).any():
        raise ValueError("Unsupported duration/attendance evidence generated.")
    if not frame.topic_task_performance_json.eq("{}").all() or not frame.topic_quiz_performance_json.eq("{}").all():
        raise ValueError("Synthetic structured topic generation is disabled.")


def output_paths(output):
    output = Path(output).resolve()
    if OUTPUT_ROOT.resolve() != OUTPUT_ROOT.absolute():
        raise ValueError("Synthetic output root must not redirect through a filesystem link/junction.")
    if not output.is_relative_to(OUTPUT_ROOT.resolve()) or output.suffix.lower() != ".csv":
        raise ValueError("Synthetic/combined outputs must be new CSVs under ml/data/synthetic/.")
    paths = [output, output.with_suffix(".manifest.json"), output.with_suffix(".distributions.json")]
    if any(p.exists() for p in paths):
        raise FileExistsError("Synthetic output collision; no overwrite permitted.")
    return paths


def write_bundle(output, header, rows, manifest, comparison):
    paths = output_paths(output)
    paths[0].parent.mkdir(parents=True, exist_ok=True)
    created = []
    try:
        with paths[0].open("x", newline="", encoding="utf-8") as stream:
            created.append(paths[0])
            writer = csv.DictWriter(stream, fieldnames=header)
            writer.writeheader()
            writer.writerows(rows)
        manifest["output_sha256"] = sha256(paths[0])
        manifest["distribution_report_filename"] = paths[2].name
        for path, payload in [(paths[2], comparison), (paths[1], manifest)]:
            with path.open("x", encoding="utf-8") as stream:
                created.append(path)
                json.dump(payload, stream, indent=2, allow_nan=False)
                stream.write("\n")
        return manifest
    except Exception:
        for path in created:
            path.unlink()
        raise


def csv_records(frame):
    return frame.astype(object).where(pd.notna(frame), "").to_dict("records")


def generate(real_path, schema_path, scenario, rows, seed, output, class_code=None, material_opportunities=None):
    validate_feature_contract(schema_path)
    output_paths(output)  # enforce output boundaries before any work
    dataset = load_dataset(real_path, labeled=False)
    config, scenario_sha = read_scenario(scenario)
    frame, details, comparison = synthesize(dataset, config, scenario_sha, rows, seed, class_code, material_opportunities)
    from .runner import git_value
    manifest = {"artifact_kind": "synthetic_unlabeled_features", "data_origin": "synthetic",
                "generator_version": "synthetic_features_v2" if scenario == "fprog_1a_v2" else GENERATOR_VERSION,
                "generator_sha256": sha256(Path(__file__)),
                "contract_sha256": sha256(CONTRACT_PATH), "input_filename": dataset.path.name,
                "input_sha256": dataset.report["dataset_sha256"], "scenario": scenario, "random_seed": seed,
                "requested_rows": rows, "generated_rows": len(frame), "real_participants_added": 0,
                "feature_version": "v5", "feature_cutoff_at": dataset.report["feature_cutoffs"][0],
                "git_commit": git_value("rev-parse", "HEAD"), "timestamp_utc": datetime.now(timezone.utc).isoformat(),
                "target_generation": "disabled; no target or outcome columns", "training_eligible": False,
                "provenance_fields_never_x": PROVENANCE, "distribution_flags": comparison["flags"], **details}
    if scenario == "fprog_1a_v2":
        manifest["v2_rules_sha256"] = sha256(Path(__file__).with_name("synthetic_v2.py"))
    if sha256(dataset.path) != manifest["input_sha256"]:
        raise ValueError("Real input changed; generation stopped.")
    result = write_bundle(output, OUTPUT_COLUMNS, csv_records(frame), manifest, comparison)
    if sha256(dataset.path) != manifest["input_sha256"]:
        raise ValueError("Real input changed during output writing; artifacts must not be used.")
    return result


def combine(real_path, synthetic_path, output):
    output_paths(output)
    dataset = load_dataset(real_path, labeled=False)
    synthetic_path = Path(synthetic_path).resolve(strict=True)
    manifest_path = synthetic_path.with_suffix(".manifest.json")
    manifest = json.loads(manifest_path.read_text(encoding="utf-8"))
    if (manifest.get("artifact_kind") != "synthetic_unlabeled_features" or manifest.get("data_origin") != "synthetic" or
        manifest.get("input_sha256") != dataset.report["dataset_sha256"] or manifest.get("output_sha256") != sha256(synthetic_path)):
        raise ValueError("Synthetic artifact provenance/hash does not match the immutable real input.")
    with synthetic_path.open(encoding="utf-8", newline="") as stream:
        reader = csv.DictReader(stream)
        syn = pd.DataFrame(list(reader))
        if reader.fieldnames != OUTPUT_COLUMNS:
            raise ValueError("Unexpected synthetic artifact header.")
    for column in CONTRACT["core"] + CONTRACT["optional"] + CONTRACT["counts"]:
        if column in syn:
            syn[column] = pd.to_numeric(syn[column].mask(syn[column].eq("")), errors="raise")
    validate_synthetic(syn, manifest)
    if len(syn) != manifest["generated_rows"] or set(syn.feature_version) != {"v5"} or set(syn.feature_cutoff_at) != set(dataset.report["feature_cutoffs"]):
        raise ValueError("Synthetic row count/version/cutoff mismatch.")
    with dataset.path.open(encoding="utf-8-sig", newline="") as stream:
        reader = csv.DictReader(stream)
        header = list(reader.fieldnames)
        real_records = [{**row, "data_origin": "real", "synthetic_scenario": "", "synthetic_run_id": "", "synthetic_profile": ""} for row in reader if any(row.values())]
    # Union only approved metadata/predictors; preserve real cell values, no fill/imputation.
    header += [column for column in OUTPUT_COLUMNS if column not in header]
    combined_manifest = {"artifact_kind": "mixed_origin_unlabeled_development_features", "training_eligible": False,
                         "real_filename": dataset.path.name, "real_sha256": dataset.report["dataset_sha256"],
                         "synthetic_filename": synthetic_path.name, "synthetic_sha256": sha256(synthetic_path),
                         "synthetic_manifest_sha256": sha256(manifest_path), "real_rows": len(real_records),
                         "real_unique_students": dataset.report["unique_students"], "synthetic_rows": len(syn),
                         "total_rows": len(real_records) + len(syn), "synthetic_rows_are_participants": False,
                         "target_generation": "disabled", "provenance_fields_never_x": PROVENANCE,
                         "timestamp_utc": datetime.now(timezone.utc).isoformat(),
                         "warnings": ["Cannot use synthetic rows for real held-out evaluation or claim a larger participant count.",
                                      "Current labeled-training loader rejects this unlabeled mixed-origin file."]}
    if sha256(dataset.path) != combined_manifest["real_sha256"] or sha256(synthetic_path) != manifest["output_sha256"]:
        raise ValueError("Input changed during combination.")
    result = write_bundle(output, header, real_records + csv_records(syn), combined_manifest, distributions(dataset.frame, syn))
    if sha256(dataset.path) != combined_manifest["real_sha256"] or sha256(synthetic_path) != manifest["output_sha256"]:
        raise ValueError("Input changed during output writing; combined artifacts must not be used.")
    return result
