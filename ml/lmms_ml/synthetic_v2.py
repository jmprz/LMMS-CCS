"""Versioned FPROG simulation mechanics; no changes to production v5 measurements."""
import numpy as np


def validate_measurements(frame):
    if not frame.quiz_attempt_count.isin([0, 1]).all():
        raise ValueError("FPROG v2 represented quiz count must be 0 or 1.")
    if (frame.quiz_avg.dropna() % 5 != 0).any():
        raise ValueError("FPROG v2 quiz scores must use the five-point grid.")
    if not frame.task_avg.dropna().isin([0, 100]).all():
        raise ValueError("FPROG v2 task grades must be missing, 0 or 100.")
    if not frame.quiz_avg.notna().eq(frame.quiz_attempt_count.eq(1)).all():
        raise ValueError("FPROG v2 quiz grade/evidence count mismatch.")


def revise_measurements(frame, reference, config, seed):
    """Retain v1 material/activity draws; independently revise only assessment states."""
    mechanics = config["measurement_mechanics"]
    if mechanics != {"quiz_score_step": 5, "represented_quiz_count_max": 1,
                     "task_grade_support": [0, 100], "irregular_extra_missing_fraction": 0.2,
                     "empirical_lateness_weight": 0.8}:
        raise ValueError("Unsupported v2 measurement mechanics; create a reviewed scenario revision.")
    observed_tasks = reference.task_avg.dropna()
    observed_quiz = reference.quiz_avg.dropna()
    if (len(observed_tasks) < config["minimum_observed_values"] or
        len(observed_quiz) < config["minimum_observed_values"] or
        not observed_tasks.isin([0, 100]).all() or (observed_quiz % 5 != 0).any() or
        not reference.quiz_attempt_count.isin([0, 1]).all() or
        not reference.assigned_task_count.eq(1).all() or
        not reference.loc[reference.task_avg.notna(), "submission_rate"].eq(100).all()):
        raise ValueError("Reference does not support the reviewed FPROG v2 measurement mechanics.")
    rng = np.random.default_rng(np.random.SeedSequence([seed, 2]))
    frame = frame.copy()
    profiles = [config["profiles"][name] for name in frame.synthetic_profile]
    base_missing = float(reference.task_avg.isna().mean())
    missing_count = min(len(frame), int(np.floor(len(frame) * base_missing + 0.5)))
    # An explicit modest increment for irregular profiles, not a large drop in all submissions.
    irregular = np.flatnonzero(frame.synthetic_profile.eq("irregular_missing"))
    extra = min(len(irregular), int(np.floor(len(irregular) * mechanics["irregular_extra_missing_fraction"] + 0.5)))
    missing = set(map(int, rng.choice(irregular, extra, replace=False))) if extra else set()
    candidates = np.array([i for i in range(len(frame)) if i not in missing])
    weights = np.array([0.05 + 1 - profiles[i]["submission"] for i in candidates])
    missing.update(map(int, rng.choice(candidates, min(missing_count, len(candidates)), replace=False, p=weights / weights.sum())))
    quiz_coverage = float(reference.quiz_avg.notna().mean())
    zero_fraction = float(observed_tasks.eq(0).mean())
    submitted_reference = reference[reference.submission_rate.eq(100)]
    late_fraction = float(submitted_reference.late_submission_count.mean())
    if not 0 <= late_fraction <= 1:
        raise ValueError("Reference lateness incompatible with one virtual task.")
    for i, profile in enumerate(profiles):
        has_quiz = rng.random() < np.clip(quiz_coverage * (0.8 + 0.4 * profile["engagement"]), 0, 1)
        mean = (1 - config["empirical_score_weight"]) * profile["score_mean"] + config["empirical_score_weight"] * float(observed_quiz.mean())
        score = np.clip(mean + profile["score_sd"] * rng.normal(), 0, 100)
        frame.at[i, "quiz_attempt_count"] = int(has_quiz)
        frame.at[i, "quiz_avg"] = float(5 * np.rint(score / 5)) if has_quiz else np.nan
        submitted = i not in missing
        frame.at[i, "submission_rate"] = 100 if submitted else 0
        # A zero is an actual submitted/graded state, never an inferred missing grade or risk label.
        zero_probability = zero_fraction * (0.5 + 0.5 * (100 - profile["score_mean"]) / 30)
        frame.at[i, "task_avg"] = (0 if rng.random() < zero_probability else 100) if submitted else np.nan
        late_probability = mechanics["empirical_lateness_weight"] * late_fraction + (1 - mechanics["empirical_lateness_weight"]) * profile["late"]
        frame.at[i, "late_submission_count"] = int(submitted and rng.random() < late_probability)
    validate_measurements(frame)
    return frame, {
        "virtual_quiz_opportunities": config["opportunities"]["quizzes"],
        "represented_quiz_count_max": 1, "quiz_score_step": 5,
        "quiz_presence_reference": "pooled FPROG rows, including structural no-quiz rows; a simulation assumption, not estimated student propensity",
        "pooled_quiz_evidence_fraction": quiz_coverage,
        "reference_task_missing_fraction": base_missing, "synthetic_task_missing_count": len(missing),
        "irregular_extra_missing_count": extra, "reference_submitted_zero_grade_fraction": zero_fraction,
        "reference_conditional_late_fraction": late_fraction,
        "task_rule": "one task: missing/no submission or submitted/graded 0 or 100; no arbitrary intermediate grades",
        "submission_tradeoff": "near-reference grade completeness constrains submission completeness; missing slots weighted toward lower-submission profiles",
        "preserved_v1_draws": ["materials_accessed", "material_session_count", "activity_count"],
        "not_a_production_feature_change": True,
    }
