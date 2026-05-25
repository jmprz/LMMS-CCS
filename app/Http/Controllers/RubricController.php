<?php

namespace App\Http\Controllers;

use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\Submission;
use App\Models\Task;
use App\Services\GeminiGradingService;
use Illuminate\Http\Request;

class RubricController extends Controller
{
    // -------------------------------------------------------------------------
    // PROFESSOR — Show rubric creation / edit form
    // GET /professor/tasks/{taskId}/rubric/create
    // -------------------------------------------------------------------------
    public function create($taskId)
    {
        // 1. Fetch the task and eager-load the lab session
        $task = Task::with('labSession')->findOrFail($taskId);
        
        // 2. Fetch the rubric if it exists, along with its criteria
        $rubric = $task->rubric()->with('criteria')->first();

        // 3. Build existing criteria in the exact format Alpine.js expects
        $existingCriteria = [];
        if ($rubric) {
            $uid      = 1;
            $levelUid = 1000;

            foreach ($rubric->criteria as $c) {
                $levels = $c->checking_rules['levels'] ?? [];

                // Format every level with an explicit interactive uid key for the dynamic frontend
                $levelsWithUids = [];
                foreach ($levels as $lvl) {
                    $levelsWithUids[] = [
                        'uid'         => $levelUid++,
                        'label'       => $lvl['label']   ?? 'Level',
                        'points'      => (int)($lvl['points'] ?? 0),
                        'description' => $lvl['description'] ?? '',
                    ];
                }

                $existingCriteria[] = [
                    'uid'         => $uid++,
                    'name'        => $c->criterion_name,
                    'description' => $c->description ?? '',
                    'levels'      => $levelsWithUids,
                ];
            }
        }

        // 4. Return view with all variables securely bound
        return view('professor.rubrics.create', compact('task', 'rubric', 'existingCriteria'));
    }

    // -------------------------------------------------------------------------
    // PROFESSOR — Save / replace rubric for a task
    // POST /professor/tasks/{taskId}/rubric
    // -------------------------------------------------------------------------
    public function store(Request $request, $taskId)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'criteria_json' => 'required|string',
        ]);

        $task         = Task::findOrFail($taskId);
        $criteriaData = json_decode($request->criteria_json, true);

        if (! is_array($criteriaData) || count($criteriaData) === 0) {
            return back()->withErrors(['criteria_json' => 'Please add at least one criterion.'])->withInput();
        }

        // ── Delete old rubric (cascade deletes criteria + scores) ──
        $task->rubric()->delete();

        // ── Calculate total points (highest level per criterion) ──
        $totalPoints = 0;
        foreach ($criteriaData as $c) {
            $pts = collect($c['levels'] ?? [])->max(fn($l) => (int)($l['points'] ?? 0)) ?? 0;
            $totalPoints += $pts;
        }

        // ── Create rubric ──
        $rubric = Rubric::create([
            'task_id'            => $task->id,
            'name'               => $request->name,
            'description'        => $request->description ?? null,
            'total_points'       => $totalPoints,
            'auto_grade_enabled' => $request->boolean('auto_grade_enabled', true),
            'created_by'         => auth()->id(),
        ]);

        // Keep task points in sync
        $task->update(['points' => $totalPoints]);

        // ── Create criteria ──
        foreach ($criteriaData as $index => $c) {
            $levels = $c['levels'] ?? [];

            // Sort levels by points descending (best first — makes Gemini prompt cleaner)
            usort($levels, fn($a, $b) => (int)($b['points'] ?? 0) - (int)($a['points'] ?? 0));

            // Strip internal uids from stored data
            $cleanLevels = array_map(fn($l) => [
                'label'       => trim($l['label'] ?? ''),
                'points'      => (int)($l['points'] ?? 0),
                'description' => trim($l['description'] ?? ''),
            ], $levels);

            $maxPoints = !empty($cleanLevels) ? $cleanLevels[0]['points'] : 0; // already sorted

            RubricCriterion::create([
                'rubric_id'      => $rubric->id,
                'criterion_name' => trim($c['name'] ?? 'Unnamed Criterion'),
                'description'    => trim($c['description'] ?? ''),
                'max_points'     => $maxPoints,
                'checking_type'  => 'ai',      // all level-based criteria use AI
                'checking_rules' => ['levels' => $cleanLevels],
                'weight'         => 1.0,
                'order_index'    => $index,
            ]);
        }

        return redirect()
            ->route('professor.tasks.rubric.show', $taskId)
            ->with('success', 'Rubric saved! Gemini AI will now auto-grade student submissions.');
    }

    // -------------------------------------------------------------------------
    // PROFESSOR — View rubric + student submissions/grades for a task
    // GET /professor/tasks/{taskId}/rubric
    // -------------------------------------------------------------------------
    public function show($taskId)
    {
        $task = Task::with([
            'labSession.faculty',
            'rubric.criteria',
        ])->findOrFail($taskId);

        $rubric = $task->rubric;

        $submissions = Submission::where('task_id', $taskId)
            ->with([
                'user',
                'submissionGrade.criterionScores.criterion',
            ])
            ->latest()
            ->get();

        // 🟢 FIXED: Changed from 'professor.rubric.show' to 'professor.rubrics.show'
        return view('professor.rubrics.show', compact('task', 'rubric', 'submissions'));
    }

    // -------------------------------------------------------------------------
    // PROFESSOR — Re-run auto-grading on one submission
    // POST /professor/submissions/{submissionId}/regrade
    // -------------------------------------------------------------------------
    public function regrade($submissionId)
    {
        $submission = Submission::with('task')->findOrFail($submissionId);
        $service    = new GeminiGradingService();
        $grade      = $service->gradeSubmission($submission);

        if ($grade) {
            return response()->json([
                'success'     => true,
                'message'     => 'Re-graded successfully.',
                'total_score' => $grade->total_score,
                'max_score'   => $grade->max_score,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No rubric found or auto-grading is disabled.',
        ], 422);
    }

    // -------------------------------------------------------------------------
    // PROFESSOR — Delete a rubric
    // DELETE /professor/tasks/{taskId}/rubric
    // -------------------------------------------------------------------------
    public function destroy($taskId)
    {
        $task = Task::findOrFail($taskId);
        $task->rubric()->delete();

        return redirect()
            ->route('professor.classroom.show', $task->subject_id)
            ->with('success', 'Rubric deleted. Task will require manual grading.');
    }
}