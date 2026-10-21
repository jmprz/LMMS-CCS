"""Grouped evaluation and fold-local preprocessing; no fitting at import time."""
from __future__ import annotations

import warnings

import numpy as np
import pandas as pd
from sklearn.base import BaseEstimator, TransformerMixin
from sklearn.ensemble import GradientBoostingClassifier, RandomForestClassifier
from sklearn.impute import SimpleImputer
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import (accuracy_score, confusion_matrix, f1_score, precision_score,
                             recall_score, roc_auc_score, balanced_accuracy_score)
from sklearn.model_selection import GroupShuffleSplit, StratifiedGroupKFold
from sklearn.neighbors import KNeighborsClassifier
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import StandardScaler
from sklearn.svm import SVC
from sklearn.tree import DecisionTreeClassifier
from sklearn.utils.validation import check_is_fitted

from .dataset import CONTRACT
from .imbalance import AuditedSampler, STRATEGIES
from imblearn.pipeline import Pipeline as SamplingPipeline

MODEL_NAMES = ("logistic_regression", "decision_tree", "random_forest", "gradient_boosting", "svm", "knn")


class TrainingMedianImputer(TransformerMixin, BaseEstimator):
    """Drop only training-all-missing columns; never replace them with invented zeroes."""

    def fit(self, x, y=None):
        if not isinstance(x, pd.DataFrame) or not set(x.columns).issubset(set(CONTRACT["core"] + CONTRACT["optional"])):
            raise ValueError("Preprocessor accepts numeric predictor allowlist only.")
        self.feature_names_in_ = np.asarray(x.columns, dtype=object)
        self.n_features_in_ = x.shape[1]
        self.retained_columns_ = x.columns[x.notna().any()].tolist()
        self.dropped_columns_ = x.columns[x.isna().all()].tolist()
        if not self.retained_columns_:
            raise ValueError("All selected predictors are missing in this training fold.")
        if self.dropped_columns_:
            warnings.warn("Training-all-missing predictors excluded for this fit: " + ", ".join(self.dropped_columns_), UserWarning)
        self.imputer_ = SimpleImputer(strategy="median")
        self.imputer_.fit(x.loc[:, self.retained_columns_])
        return self

    def transform(self, x):
        check_is_fitted(self, "imputer_")
        if not isinstance(x, pd.DataFrame) or list(x.columns) != list(self.feature_names_in_):
            raise ValueError("Predictor names/order changed after fitting.")
        return self.imputer_.transform(x.loc[:, self.retained_columns_])

    def get_feature_names_out(self, input_features=None):
        check_is_fitted(self, "imputer_")
        return np.asarray(self.retained_columns_, dtype=object)


def build_pipeline(name: str, seed: int = 42, strategy: str = "none", smote_k: int = 5) -> Pipeline:
    if strategy not in STRATEGIES:
        raise ValueError("Unknown imbalance strategy.")
    models = {
        "logistic_regression": LogisticRegression(max_iter=2000, random_state=seed),
        "decision_tree": DecisionTreeClassifier(random_state=seed),
        "random_forest": RandomForestClassifier(n_estimators=200, random_state=seed, n_jobs=1),
        "gradient_boosting": GradientBoostingClassifier(random_state=seed),
        "svm": SVC(kernel="rbf", probability=False, random_state=seed),
        "knn": KNeighborsClassifier(n_neighbors=5),
    }
    if name not in models:
        raise ValueError("Unknown algorithm; no silent substitution is permitted.")
    model = models[name]
    if strategy == "class_weight" and "class_weight" in model.get_params():
        model.set_params(class_weight="balanced")
    steps = [("impute", TrainingMedianImputer())]
    # SMOTE uses Euclidean neighbors: scale even for trees to avoid duration-unit dominance.
    if name in ("logistic_regression", "svm", "knn") or strategy == "smote":
        steps.append(("scale", StandardScaler()))
    if strategy in ("random_over", "smote"):
        steps.append(("sampler", AuditedSampler(strategy, seed, smote_k)))
        return SamplingPipeline(steps + [("model", model)])
    return Pipeline(steps + [("model", model)])


def check_partition(y, groups, train, test):
    if not len(train) or not len(test) or set(groups[train]) & set(groups[test]):
        raise ValueError("Empty partition or student leakage detected.")
    if len(set(y[train])) != 2 or len(set(y[test])) != 2:
        raise ValueError("Both labels must occur in each partition; no row-split fallback.")


def prepare_splits(dataset, seed=42, test_size=0.2, folds=5):
    if not 0 < test_size < 1 or folds < 2:
        raise ValueError("test_size must be between zero and one; folds must be at least two.")
    y, groups = dataset.y, dataset.groups
    if len(set(y)) != 2 or len(set(groups)) < 3:
        raise ValueError("Insufficient target/group distribution for grouped evaluation.")
    outer = GroupShuffleSplit(n_splits=1, test_size=test_size, random_state=seed)
    train, test = next(outer.split(np.zeros(len(y)), y, groups))
    check_partition(y, groups, train, test)
    for label in (0, 1):
        if len(set(groups[train][y[train] == label])) < folds:
            raise ValueError("Too few training student groups per target for requested stratified grouped CV.")
    cv = StratifiedGroupKFold(n_splits=folds, shuffle=True, random_state=seed)
    splits = []
    for local_train, local_valid in cv.split(np.zeros(len(train)), y[train], groups[train]):
        fit, valid = train[local_train], train[local_valid]
        check_partition(y, groups, fit, valid)
        if set(groups[valid]) & set(groups[test]):
            raise ValueError("Holdout student appeared in CV.")
        splits.append((fit, valid))
    return train, test, splits


def partition_summary(dataset, positions):
    y = dataset.y[positions]
    counts = {"not_at_risk": int((y == 0).sum()), "at_risk": int((y == 1).sum())}
    return {"rows": len(positions), "unique_students": len(set(dataset.groups[positions])),
            "target_counts": counts,
            "target_percentages": {label: round(100 * count / len(y), 4) for label, count in counts.items()},
            "class_ids": sorted(dataset.frame.iloc[positions].lab_session_id.unique().tolist())}


def metrics(y_true, y_pred, scores=None):
    tn, fp, fn, tp = confusion_matrix(y_true, y_pred, labels=[0, 1]).ravel()
    specificity = float(tn / (tn + fp)) if tn + fp else None
    return {"positive_class": "at_risk", "confusion_matrix_order": ["not_at_risk", "at_risk"],
            "confusion_matrix": [[int(tn), int(fp)], [int(fn), int(tp)]],
            "accuracy": float(accuracy_score(y_true, y_pred)),
            "balanced_accuracy": float(balanced_accuracy_score(y_true, y_pred)) if len(set(y_true)) == 2 else None,
            "precision": float(precision_score(y_true, y_pred, zero_division=0)),
            "recall_sensitivity": float(recall_score(y_true, y_pred, zero_division=0)),
            "specificity": specificity, "f1": float(f1_score(y_true, y_pred, zero_division=0)),
            "roc_auc": float(roc_auc_score(y_true, scores)) if scores is not None and len(set(y_true)) == 2 else None,
            "zero_division_policy": "precision/recall/F1 = 0 when undefined; specificity/AUC/balanced accuracy = null when unavailable"}


def evaluate(pipeline, x, y):
    if hasattr(pipeline, "predict_proba"):
        scores = pipeline.predict_proba(x)[:, list(pipeline.classes_).index(1)]
        score_kind = "probability_of_at_risk"
    elif hasattr(pipeline, "decision_function"):
        scores = pipeline.decision_function(x)
        score_kind = "decision_function_not_calibrated_probability"
    else:
        scores, score_kind = None, "unavailable"
    return {**metrics(y, pipeline.predict(x), scores), "roc_score_kind": score_kind}


def preprocessing_record(pipeline):
    step = pipeline.named_steps["impute"]
    return {"imputation": "training-fold median", "scaling": "training-fold StandardScaler" if "scale" in pipeline.named_steps else "none",
            "missing_indicators": False, "retained_columns": step.retained_columns_,
            "training_all_missing_dropped": step.dropped_columns_,
            "training_medians": dict(zip(step.retained_columns_, map(float, step.imputer_.statistics_)))}
