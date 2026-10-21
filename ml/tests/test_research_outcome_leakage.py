"""New target-policy fields must remain rejected by the unchanged v5 X contract."""
import pytest

from lmms_ml.dataset import CONTRACT, ValidationError, load_dataset
from test_pipeline import records, write_csv


@pytest.mark.parametrize("field", [
    "outcome_performance", "outcome_origin", "data_origin", "performance_threshold",
    "comparison_direction", "eligible_outcome_opportunities", "completed_outcome_opportunities",
    "no_participation", "outcome_start", "outcome_end", "policy_version", "outcome_event_key",
    "approval_reference", "synthetic_scenario", "synthetic_run_id",
])
def test_target_policy_fields_are_never_x_or_silently_ingested(tmp_path, records, field):
    assert field not in CONTRACT['core'] + CONTRACT['optional']
    for row in records:
        row[field] = "TEST-ONLY"
    with pytest.raises(ValidationError):
        load_dataset(write_csv(tmp_path, records))
