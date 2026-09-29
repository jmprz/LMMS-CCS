<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LabSession;
use App\Models\Quiz;
use App\Models\ResearchAssessment;
use App\Models\ResearchExperiment;
use App\Models\ResearchGroup;
use App\Models\ResearchParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ResearchExperimentController extends Controller
{
    public function index(Request $request)
    {
        $experiments = ResearchExperiment::withCount(['groups', 'assessments'])
            ->latest()->get();
        $chosen = $request->integer('experiment');
        $experiment = $chosen
            ? ResearchExperiment::with(['groups.labSession', 'groups.participants.student', 'assessments.quiz.labSession'])
                ->findOrFail($chosen)
            : ResearchExperiment::with(['groups.labSession', 'groups.participants.student', 'assessments.quiz.labSession'])
                ->latest()->first();
        $sessions = LabSession::orderBy('subject_name')->orderBy('program')->orderBy('section')->get();
        $quizzes = Quiz::with('labSession')->whereNotNull('learning_topic_id')
            ->orderBy('subject_id')->orderBy('title')->get();
        $studentsByClass = collect();

        $engagementSummary = [
            'recommendations' => 0,
            'students_recommended' => 0,
            'students_engaged' => 0,
            'total_views' => 0,
            'active_seconds' => 0,
            'engagement_rate' => 0,
        ];
        $engagementRows = collect();

        $resultSummary = [
            'experimental' => [
                'students' => 0,
                'pretest_avg' => null,
                'posttest_avg' => null,
                'avg_change' => null,
            ],
            'control' => [
                'students' => 0,
                'pretest_avg' => null,
                'posttest_avg' => null,
                'avg_change' => null,
            ],
        ];
        $resultRows = collect();

        if ($experiment) {
            $classIds = $experiment->groups->pluck('lab_session_id');
            $studentsByClass = DB::table('class_student as cs')
                ->join('users as u', 'u.id', '=', 'cs.user_id')
                ->whereIn('cs.lab_session_id', $classIds)
                ->where('u.role', 'student')
                ->select('cs.lab_session_id', 'u.id', 'u.name', 'u.school_id')
                ->orderBy('u.last_name')->orderBy('u.first_name')->get()
                ->unique(fn ($item) => $item->lab_session_id . ':' . $item->id)
                ->groupBy('lab_session_id');

            // Recommendation engagement analytics are research-facing only.
            // A "view" is one row in recommendation_engagements.
            // duration_seconds is active viewer time captured by the roadmap tracker.
            $recommendationBase = DB::table('learning_recommendations as lr')
                ->where('lr.research_experiment_id', $experiment->id);

            $engagementSummary['recommendations'] = (clone $recommendationBase)->count();
            $engagementSummary['students_recommended'] = (clone $recommendationBase)
                ->distinct()
                ->count('lr.user_id');

            $engagementTotals = DB::table('recommendation_engagements as re')
                ->join('learning_recommendations as lr', 'lr.id', '=', 're.learning_recommendation_id')
                ->where('lr.research_experiment_id', $experiment->id)
                ->selectRaw('COUNT(re.id) as total_views')
                ->selectRaw('COUNT(DISTINCT re.user_id) as students_engaged')
                ->selectRaw('COALESCE(SUM(re.duration_seconds), 0) as active_seconds')
                ->first();

            $engagementSummary['total_views'] = (int) ($engagementTotals->total_views ?? 0);
            $engagementSummary['students_engaged'] = (int) ($engagementTotals->students_engaged ?? 0);
            $engagementSummary['active_seconds'] = (int) ($engagementTotals->active_seconds ?? 0);

            if ($engagementSummary['students_recommended'] > 0) {
                $engagementSummary['engagement_rate'] = round(
                    ($engagementSummary['students_engaged'] / $engagementSummary['students_recommended']) * 100,
                    1
                );
            }

            $engagementRows = DB::table('learning_recommendations as lr')
                ->join('users as u', 'u.id', '=', 'lr.user_id')
                ->leftJoin('learning_topics as lt', 'lt.id', '=', 'lr.learning_topic_id')
                ->leftJoin('recommendation_engagements as re', 're.learning_recommendation_id', '=', 'lr.id')
                ->where('lr.research_experiment_id', $experiment->id)
                ->groupBy('u.id', 'u.name', 'u.school_id')
                ->select([
                    'u.id as user_id',
                    'u.name as student_name',
                    'u.school_id',
                ])
                ->selectRaw('COUNT(DISTINCT lr.id) as recommendations_count')
                ->selectRaw('COUNT(DISTINCT CASE WHEN re.id IS NOT NULL THEN lr.id END) as opened_resources_count')
                ->selectRaw('COUNT(re.id) as total_views')
                ->selectRaw('COALESCE(SUM(re.duration_seconds), 0) as active_seconds')
                ->selectRaw('MAX(re.opened_at) as last_viewed_at')
                ->selectRaw("GROUP_CONCAT(DISTINCT lt.name ORDER BY lt.name SEPARATOR ', ') as weak_topics")
                ->orderByDesc('active_seconds')
                ->orderBy('u.name')
                ->get();

            // -------------------------------------------------------------
            // PRETEST / POSTTEST RESULT VIEW
            // -------------------------------------------------------------
            // Use the latest attempt for each student + mapped quiz pair.
            $latestAttemptIds = DB::table('quiz_attempts')
                ->select('user_id', 'quiz_id')
                ->selectRaw('MAX(id) as id')
                ->groupBy('user_id', 'quiz_id');

            $assessmentAttempts = DB::table('research_assessments as ra')
                ->join('quizzes as q', 'q.id', '=', 'ra.quiz_id')
                ->join('research_groups as rg', function ($join) use ($experiment) {
                    $join->on('rg.lab_session_id', '=', 'q.subject_id')
                        ->where('rg.research_experiment_id', '=', $experiment->id);
                })
                ->join('research_participants as rp', function ($join) {
                    $join->on('rp.research_group_id', '=', 'rg.id')
                        ->where('rp.consent_status', '=', 'consented');
                })
                ->join('users as u', 'u.id', '=', 'rp.user_id')
                ->leftJoinSub($latestAttemptIds, 'latest_attempts', function ($join) {
                    $join->on('latest_attempts.user_id', '=', 'u.id')
                        ->on('latest_attempts.quiz_id', '=', 'q.id');
                })
                ->leftJoin('quiz_attempts as qa', 'qa.id', '=', 'latest_attempts.id')
                ->where('ra.research_experiment_id', $experiment->id)
                ->select([
                    'u.id as user_id',
                    'u.name as student_name',
                    'u.school_id',
                    'rg.group_type',
                    'rg.lab_session_id',
                    'ra.assessment_type',
                    'q.id as quiz_id',
                    'q.title as quiz_title',
                    'qa.score',
                    'qa.total_points',
                    'qa.created_at as attempted_at',
                ])
                ->orderBy('u.name')
                ->get();

            $engagementByUser = $engagementRows->keyBy('user_id');

            $resultRows = $assessmentAttempts
                ->groupBy(fn ($row) => $row->user_id . ':' . $row->group_type . ':' . $row->lab_session_id)
                ->map(function ($items) use ($engagementByUser) {
                    $first = $items->first();
                    $pre = $items->firstWhere('assessment_type', 'pretest');
                    $post = $items->firstWhere('assessment_type', 'posttest');

                    $prePct = ($pre && $pre->score !== null && (float) $pre->total_points > 0)
                        ? round(((float) $pre->score / (float) $pre->total_points) * 100, 1)
                        : null;

                    $postPct = ($post && $post->score !== null && (float) $post->total_points > 0)
                        ? round(((float) $post->score / (float) $post->total_points) * 100, 1)
                        : null;

                    $engagement = $engagementByUser->get($first->user_id);

                    return (object) [
                        'user_id' => $first->user_id,
                        'student_name' => $first->student_name,
                        'school_id' => $first->school_id,
                        'group_type' => $first->group_type,
                        'lab_session_id' => $first->lab_session_id,
                        'pretest_percentage' => $prePct,
                        'posttest_percentage' => $postPct,
                        'change_percentage_points' => ($prePct !== null && $postPct !== null)
                            ? round($postPct - $prePct, 1)
                            : null,
                        'pretest_quiz' => $pre->quiz_title ?? null,
                        'posttest_quiz' => $post->quiz_title ?? null,
                        'pretest_attempted_at' => $pre->attempted_at ?? null,
                        'posttest_attempted_at' => $post->attempted_at ?? null,
                        'recommendations_count' => (int) ($engagement->recommendations_count ?? 0),
                        'opened_resources_count' => (int) ($engagement->opened_resources_count ?? 0),
                        'total_views' => (int) ($engagement->total_views ?? 0),
                        'active_seconds' => (int) ($engagement->active_seconds ?? 0),
                    ];
                })
                ->values();

            foreach (['experimental', 'control'] as $groupType) {
                $completed = $resultRows
                    ->where('group_type', $groupType)
                    ->filter(fn ($row) => $row->pretest_percentage !== null && $row->posttest_percentage !== null);

                $resultSummary[$groupType]['students'] = $completed->count();

                if ($completed->isNotEmpty()) {
                    $resultSummary[$groupType]['pretest_avg'] = round($completed->avg('pretest_percentage'), 1);
                    $resultSummary[$groupType]['posttest_avg'] = round($completed->avg('posttest_percentage'), 1);
                    $resultSummary[$groupType]['avg_change'] = round($completed->avg('change_percentage_points'), 1);
                }
            }
        }

        return view('admin.research-experiments.index', compact(
            'experiments',
            'experiment',
            'sessions',
            'quizzes',
            'studentsByClass',
            'engagementSummary',
            'engagementRows',
            'resultSummary',
            'resultRows'
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validateSettings($request);
        $experiment = ResearchExperiment::create(array_merge($data, [
            'status' => 'draft', 'created_by' => $request->user()->id,
        ]));
        return redirect()->route('admin.research-experiments.index', ['experiment' => $experiment->id])
            ->with('success', 'Experiment created as a draft. Assign classes, assessments and participants before activation.');
    }

    public function update(Request $request, ResearchExperiment $experiment)
    {
        $this->requireDraft($experiment);
        $experiment->update($this->validateSettings($request));
        return $this->backTo($experiment, 'Experiment settings saved.');
    }

    private function validateSettings(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'weak_topic_threshold' => ['required', 'numeric', 'between:0,100'],
            'intervention_starts_at' => ['nullable', 'date'],
            'intervention_ends_at' => ['nullable', 'date', 'after:intervention_starts_at'],
        ]);
    }

    public function assignGroup(Request $request, ResearchExperiment $experiment)
    {
        $this->requireDraft($experiment);
        $data = $request->validate([
            'lab_session_id' => ['required', 'integer', 'exists:lab_sessions,id'],
            'group_type' => ['required', Rule::in(['experimental', 'control'])],
        ]);
        // Prevent one real classroom receiving incompatible conditions across active experiments.
        $conflict = ResearchGroup::where('lab_session_id', $data['lab_session_id'])
            ->where('research_experiment_id', '!=', $experiment->id)
            ->whereHas('experiment', fn ($query) => $query->where('status', 'active'))
            ->exists();
        if ($conflict) {
            throw ValidationException::withMessages(['lab_session_id' => 'This class belongs to a different active experiment.']);
        }
        $existing = ResearchGroup::where('research_experiment_id', $experiment->id)
            ->where('lab_session_id', $data['lab_session_id'])->first();
        if ($existing && $existing->group_type !== $data['group_type'] && $existing->participants()->exists()) {
            throw ValidationException::withMessages(['group_type' => 'Remove the existing group and its participant assignments before changing its condition.']);
        }
        ResearchGroup::updateOrCreate([
            'research_experiment_id' => $experiment->id,
            'lab_session_id' => $data['lab_session_id'],
        ], [
            'group_type' => $data['group_type'],
            'recommendations_enabled' => false, // enabled only by a validated activation
        ]);
        return $this->backTo($experiment, 'Class condition assigned. Recommendations remain disabled until activation.');
    }

    public function removeGroup(ResearchExperiment $experiment, ResearchGroup $group)
    {
        $this->requireDraft($experiment);
        $this->belongsTo($experiment, $group->research_experiment_id);
        if ($group->participants()->exists() || $experiment->recommendations()->where('lab_session_id', $group->lab_session_id)->exists()) {
            throw ValidationException::withMessages(['group' => 'Group has participants or recommendation history. Keep it for research integrity.']);
        }
        $group->delete();
        return $this->backTo($experiment, 'Unused class assignment removed.');
    }

    public function assignAssessment(Request $request, ResearchExperiment $experiment)
    {
        $this->requireDraft($experiment);
        $data = $request->validate([
            'quiz_id' => ['required', 'integer', 'exists:quizzes,id'],
            'assessment_type' => ['required', Rule::in(['pretest', 'posttest'])],
            'assessment_version' => ['nullable', 'string', 'max:32'],
        ]);
        $quiz = Quiz::findOrFail($data['quiz_id']);
        if (!$quiz->learning_topic_id) {
            throw ValidationException::withMessages(['quiz_id' => 'Assign a standardized learning topic to this quiz first.']);
        }
        if (!$experiment->groups()->where('lab_session_id', $quiz->subject_id)->exists()) {
            throw ValidationException::withMessages(['quiz_id' => 'Assign the quiz classroom to an experiment group first.']);
        }
        ResearchAssessment::updateOrCreate([
            'research_experiment_id' => $experiment->id, 'quiz_id' => $quiz->id,
        ], [
            'assessment_type' => $data['assessment_type'],
            'assessment_version' => $data['assessment_version'] ?? null,
        ]);
        return $this->backTo($experiment, 'Assessment mapped. Existing quiz questions and results are unchanged.');
    }

    public function removeAssessment(ResearchExperiment $experiment, ResearchAssessment $assessment)
    {
        $this->requireDraft($experiment);
        $this->belongsTo($experiment, $assessment->research_experiment_id);
        if ($experiment->recommendations()->whereHas('quizAttempt', fn ($q) => $q->where('quiz_id', $assessment->quiz_id))->exists()) {
            throw ValidationException::withMessages(['assessment' => 'Recommendation history exists for this quiz.']);
        }
        $assessment->delete();
        return $this->backTo($experiment, 'Assessment mapping removed; quiz retained.');
    }

    public function enroll(Request $request, ResearchExperiment $experiment, ResearchGroup $group)
    {
        $this->requireDraft($experiment);
        $this->belongsTo($experiment, $group->research_experiment_id);
        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);
        $enrolled = DB::table('class_student as cs')->join('users as u', 'u.id', '=', 'cs.user_id')
            ->where('cs.lab_session_id', $group->lab_session_id)
            ->where('cs.user_id', $data['user_id'])->where('u.role', 'student')->exists();
        if (!$enrolled) {
            throw ValidationException::withMessages(['user_id' => 'The selected student must be enrolled in this class.']);
        }
        $elsewhere = ResearchParticipant::where('user_id', $data['user_id'])
            ->whereHas('group', fn ($q) => $q->where('research_experiment_id', $experiment->id)
                ->where('id', '!=', $group->id))->exists();
        if ($elsewhere) {
            throw ValidationException::withMessages(['user_id' => 'This student is already assigned to another group in this experiment.']);
        }
        ResearchParticipant::firstOrCreate([
            'research_group_id' => $group->id, 'user_id' => $data['user_id'],
        ], ['consent_status' => 'pending']);
        return $this->backTo($experiment, 'Participant enrolled with pending consent.');
    }

    /** Enroll several existing classroom students without changing consent. */
    public function enrollBulk(Request $request, ResearchExperiment $experiment, ResearchGroup $group)
    {
        $this->requireDraft($experiment);
        $this->belongsTo($experiment, $group->research_experiment_id);

        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1', 'max:500'],
            'user_ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
        ]);
        $ids = array_map('intval', $data['user_ids']);

        DB::transaction(function () use ($experiment, $group, $ids) {
            // Revalidate server-side: posted IDs cannot enroll nonstudents or outsiders.
            $valid = DB::table('class_student as cs')
                ->join('users as u', 'u.id', '=', 'cs.user_id')
                ->where('cs.lab_session_id', $group->lab_session_id)
                ->where('u.role', 'student')->whereIn('u.id', $ids)
                ->distinct()->pluck('u.id')->map(fn ($id) => (int) $id)->all();
            if (count($valid) !== count($ids)) {
                throw ValidationException::withMessages(['user_ids' => 'Every selected user must be an enrolled student in this class. Refresh the page and try again.']);
            }
            $elsewhere = ResearchParticipant::whereIn('user_id', $ids)
                ->whereHas('group', fn ($q) => $q->where('research_experiment_id', $experiment->id)
                    ->where('id', '!=', $group->id))->exists();
            if ($elsewhere) {
                throw ValidationException::withMessages(['user_ids' => 'One or more selected students are assigned to another group in this experiment.']);
            }
            $now = now();
            $rows = array_map(fn ($id) => [
                'research_group_id' => $group->id,
                'user_id' => $id,
                'consent_status' => 'pending',
                'consented_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ], $ids);
            // Existing participants are preserved, especially declined/withdrawn records.
            ResearchParticipant::insertOrIgnore($rows);
        });
        return $this->backTo($experiment, 'Selected students enrolled. Consent remains pending until individually documented and verified.');
    }

    /** Record verified, already-obtained consent for selected pending participants only. */
    public function consentBulk(Request $request, ResearchExperiment $experiment, ResearchGroup $group)
    {
        $this->requireDraft($experiment);
        $this->belongsTo($experiment, $group->research_experiment_id);
        $data = $request->validate([
            'participant_ids' => ['required', 'array', 'min:1', 'max:500'],
            'participant_ids.*' => ['required', 'integer', 'distinct', 'exists:research_participants,id'],
            'consent_verified' => ['required', 'accepted'],
        ]);
        $ids = array_map('intval', $data['participant_ids']);

        DB::transaction(function () use ($group, $ids) {
            $participants = ResearchParticipant::where('research_group_id', $group->id)
                ->whereIn('id', $ids)->lockForUpdate()->get();
            if ($participants->count() !== count($ids)
                || $participants->contains(fn ($p) => $p->consent_status !== 'pending')) {
                throw ValidationException::withMessages(['participant_ids' => 'Only pending participants in this group can be bulk-confirmed. Declined or withdrawn students must never be included.']);
            }
            ResearchParticipant::where('research_group_id', $group->id)
                ->whereIn('id', $ids)->where('consent_status', 'pending')
                ->update(['consent_status' => 'consented', 'consented_at' => now(), 'updated_at' => now()]);
        });
        return $this->backTo($experiment, 'Verified consent recorded for selected participants. Retain each student’s signed consent evidence securely.');
    }

    public function removeParticipant(ResearchExperiment $experiment, ResearchParticipant $participant)
    {
        $this->requireDraft($experiment);
        $participant->load('group');
        $this->belongsTo($experiment, $participant->group->research_experiment_id);
        if ($participant->consent_status === 'consented'
            || $experiment->recommendations()->where('user_id', $participant->user_id)
                ->where('lab_session_id', $participant->group->lab_session_id)->exists()) {
            throw ValidationException::withMessages(['participant' => 'Cannot remove a consented participant or one with recommendation history. Record withdrawal instead.']);
        }
        $participant->delete();
        return $this->backTo($experiment, 'Unconsented participant removed from the draft.');
    }

    public function consent(Request $request, ResearchExperiment $experiment, ResearchParticipant $participant)
    {
        $participant->load('group');
        $this->belongsTo($experiment, $participant->group->research_experiment_id);
        $data = $request->validate([
            'consent_status' => ['required', Rule::in(['consented', 'declined', 'withdrawn'])],
            'consent_verified' => ['required_if:consent_status,consented', 'accepted'],
        ]);
        // Withdrawal remains available while active or paused; other edits stay draft-only.
        if ($data['consent_status'] !== 'withdrawn') {
            $this->requireDraft($experiment);
        }
        if ($participant->consent_status === 'withdrawn' && $data['consent_status'] === 'consented') {
            throw ValidationException::withMessages(['consent_status' => 'A withdrawn participant requires a separately documented re-consent process.']);
        }
        $participant->update([
            'consent_status' => $data['consent_status'],
            'consented_at' => $data['consent_status'] === 'consented' ? now() : null,
        ]);
        return $this->backTo($experiment, 'Participant consent status recorded. Keep the signed consent evidence securely outside this UI.');
    }

    public function changeStatus(Request $request, ResearchExperiment $experiment)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'paused', 'completed'])]]);
        if ($data['status'] === 'active') {
            if (!in_array($experiment->status, ['draft', 'paused'], true)) abort(409, 'Completed experiments cannot be reactivated.');
            $this->validateForActivation($experiment);
        } elseif ($data['status'] === 'paused') {
            if ($experiment->status !== 'active') abort(409, 'Only active experiments can be paused.');
        } else {
            if (!in_array($experiment->status, ['active', 'paused'], true)) abort(409, 'Activate before completing an experiment.');
        }
        DB::transaction(function () use ($experiment, $data) {
            $experiment->update(['status' => $data['status']]);
            $experiment->groups()->update(['recommendations_enabled' => false]);
            if ($data['status'] === 'active') {
                $experiment->groups()->where('group_type', 'experimental')
                    ->update(['recommendations_enabled' => true]);
            }
        });
        return $this->backTo($experiment, 'Experiment status changed to ' . $data['status'] . '.');
    }

    private function validateForActivation(ResearchExperiment $experiment): void
    {
        $groups = $experiment->groups()->with('participants')->get();
        $errors = [];
        if (!$groups->contains('group_type', 'experimental') || !$groups->contains('group_type', 'control')) {
            $errors[] = 'Assign at least one experimental and one control class.';
        }
        foreach ($groups as $group) {
            $activeConflict = ResearchGroup::where('lab_session_id', $group->lab_session_id)
                ->where('research_experiment_id', '!=', $experiment->id)
                ->whereHas('experiment', fn ($q) => $q->where('status', 'active'))
                ->exists();
            if ($activeConflict) {
                $errors[] = 'A class is already assigned to another active experiment.';
            }
            if (!$group->participants->contains('consent_status', 'consented')) {
                $errors[] = 'Each class needs at least one consented participant.';
            }
            $pretest = $experiment->assessments()->where('assessment_type', 'pretest')
                ->whereHas('quiz', fn ($q) => $q->where('subject_id', $group->lab_session_id)
                    ->whereNotNull('learning_topic_id'))->exists();
            $posttest = $experiment->assessments()->where('assessment_type', 'posttest')
                ->whereHas('quiz', fn ($q) => $q->where('subject_id', $group->lab_session_id)
                    ->whereNotNull('learning_topic_id'))->exists();
            if (!$pretest || !$posttest) {
                $errors[] = 'Each class needs a topic-tagged pretest AND posttest mapping.';
            }
        }
        if ($experiment->intervention_starts_at && $experiment->intervention_ends_at
            && $experiment->intervention_starts_at->gt($experiment->intervention_ends_at)) {
            $errors[] = 'Intervention end must follow intervention start.';
        }
        if ($experiment->intervention_ends_at && now()->gt($experiment->intervention_ends_at)) {
            $errors[] = 'The intervention end date is already past.';
        }
        if ($errors) {
            throw ValidationException::withMessages(['activation' => array_unique($errors)]);
        }
    }

    private function requireDraft(ResearchExperiment $experiment): void
    {
        abort_unless($experiment->status === 'draft', 409, 'Pause or finish the current experiment. For integrity, setup edits are allowed only while in draft.');
    }

    private function belongsTo(ResearchExperiment $experiment, int $experimentId): void
    {
        abort_unless($experiment->id === $experimentId, 404);
    }

    private function backTo(ResearchExperiment $experiment, string $message)
    {
        return redirect()->route('admin.research-experiments.index', ['experiment' => $experiment->id])
            ->with('success', $message);
    }
}
