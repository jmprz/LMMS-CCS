<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OfficialOutcomeResearchService
{
    public function rows(?array $classIds = null, ?CarbonInterface $asOf = null): Collection
    {
        $query = DB::table('subject_grade_revisions as r')->join('subject_grades as g', 'g.id', '=', 'r.subject_grade_id')
            ->whereRaw("LOWER(TRIM(g.grading_period)) = 'final'")->whereNotNull('r.import_id')->whereNotNull('r.grade');
        if ($classIds !== null) {
            $query->whereIn('g.lab_session_id', $classIds);
        }
        if ($asOf !== null) {
            // Select the latest imported revision known at the observation point,
            // rather than selecting today's revision and then discarding it.
            $query->where('r.created_at', '<=', $asOf)
                ->where(fn ($q) => $q->whereNull('r.recorded_at')->orWhere('r.recorded_at', '<=', $asOf));
        }
        $schema = app(RiskFeatureSchema::class);

        return $query->orderByDesc('r.id')->get(['r.id', 'r.subject_grade_id', 'r.grade', 'r.recorded_at', 'g.user_id', 'g.lab_session_id'])
            ->unique('subject_grade_id')->sortBy(fn ($row) => sprintf('%020d:%020d', $row->lab_session_id, $row->user_id))
            ->map(fn ($row) => ['student_group_key' => $schema->groupingKey((int) $row->user_id), 'lab_session_id' => (string) $row->lab_session_id,
                'final_outcome' => $row->grade, 'recorded_at' => $row->recorded_at, 'outcome_revision_id' => $row->id])->values();
    }
}
