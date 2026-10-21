"""Fit-only sampling with aggregate accounting; never represents new participants."""
import numpy as np
from imblearn.over_sampling import RandomOverSampler, SMOTE
from sklearn.base import BaseEstimator

STRATEGIES = ("none", "class_weight", "random_over", "smote")


def label_counts(y):
    y = np.asarray(y)
    return {"not_at_risk": int((y == 0).sum()), "at_risk": int((y == 1).sum())}


def check_sampling_feasibility(y, strategy, k_neighbors=5):
    if strategy not in STRATEGIES:
        raise ValueError("Unknown imbalance strategy.")
    if type(k_neighbors) is not int or k_neighbors < 1:
        raise ValueError("SMOTE k_neighbors must be a positive integer.")
    counts = list(label_counts(y).values())
    if min(counts) == 0:
        raise ValueError("Training partition must contain both target classes.")
    if strategy == "smote" and min(counts) != max(counts) and min(counts) <= k_neighbors:
        raise ValueError(f"SMOTE requires more than k_neighbors={k_neighbors} minority rows in each training fold; minority rows={min(counts)}. Choose a lower value explicitly or another strategy. No automatic fallback.")


class AuditedSampler(BaseEstimator):
    """imblearn intermediate sampler: Pipeline calls fit_resample only during fit."""

    def __init__(self, strategy="random_over", random_state=42, k_neighbors=5):
        self.strategy = strategy
        self.random_state = random_state
        self.k_neighbors = k_neighbors

    def fit_resample(self, x, y):
        if self.strategy not in ("random_over", "smote"):
            raise ValueError("AuditedSampler requires an explicit resampling strategy.")
        check_sampling_feasibility(y, self.strategy, self.k_neighbors)
        if not np.isfinite(np.asarray(x)).all():
            raise ValueError("Sampling requires finite, already-imputed numeric predictors.")
        self.sampler_ = (RandomOverSampler(random_state=self.random_state) if self.strategy == "random_over"
                         else SMOTE(random_state=self.random_state, k_neighbors=self.k_neighbors))
        self.real_class_counts_ = label_counts(y)
        sampled_x, sampled_y = self.sampler_.fit_resample(x, y)
        self.effective_class_counts_ = label_counts(sampled_y)
        self.added_training_rows_ = len(sampled_y) - len(y)
        self.record_ = {"resampling_strategy": self.strategy,
                        "original_class_counts": self.real_class_counts_,
                        "resampled_class_counts": self.effective_class_counts_,
                        "real_train_rows": len(y),
                        "effective_training_rows_after_resampling": len(sampled_y),
                        "effective_training_at_risk": self.effective_class_counts_["at_risk"],
                        "effective_training_not_at_risk": self.effective_class_counts_["not_at_risk"],
                        "added_training_rows": self.added_training_rows_,
                        "duplicated_training_rows": self.added_training_rows_ if self.strategy == "random_over" else 0,
                        "interpolated_training_rows": self.added_training_rows_ if self.strategy == "smote" else 0,
                        "synthetic_interpolated_rows_generated": self.strategy == "smote" and self.added_training_rows_ > 0,
                        "sampler_parameters": self.sampler_.get_params(),
                        "random_state": self.random_state,
                        "k_neighbors": self.k_neighbors if self.strategy == "smote" else None,
                        "test_synthetic_rows": 0, "evaluation_resampled_rows": 0}
        return sampled_x, sampled_y


def imbalance_record(pipeline, y, strategy):
    counts = label_counts(y)
    model = pipeline.named_steps["model"]
    weight = model.get_params().get("class_weight")
    record = {"resampling_strategy": strategy, "real_train_rows": len(y),
              "real_train_at_risk": counts["at_risk"], "real_train_not_at_risk": counts["not_at_risk"],
              "original_class_counts": counts, "resampled_class_counts": counts.copy(),
              "effective_training_rows_after_resampling": len(y),
              "effective_training_at_risk": counts["at_risk"], "effective_training_not_at_risk": counts["not_at_risk"],
              "added_training_rows": 0, "duplicated_training_rows": 0, "interpolated_training_rows": 0,
              "synthetic_interpolated_rows_generated": False,
              "test_synthetic_rows": 0, "evaluation_resampled_rows": 0,
              "class_weight_supported": "class_weight" in model.get_params(),
              "class_weight_applied": weight,
              "class_weight_status": "applied" if weight else ("unsupported; estimator unchanged" if strategy == "class_weight" else "not requested")}
    if "sampler" in pipeline.named_steps:
        record.update(pipeline.named_steps["sampler"].record_)
    if weight == "balanced":
        record["effective_class_weights"] = {label: len(y) / (2 * n) for label, n in counts.items()}
    return record
