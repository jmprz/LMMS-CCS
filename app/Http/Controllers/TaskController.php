<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Submission;
use App\Models\SubmissionGrade;
use App\Models\Task;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Models\ActivityLog;

class TaskController extends Controller
{
    public function show($taskId)
    {
        $task = Task::with(['labSession.faculty', 'rubric.criteria'])->findOrFail($taskId);

        $submission = Submission::where('task_id', $taskId)
            ->where('user_id', auth()->id())
            ->first();

        $submissionGrade = null;
        if ($submission) {
            $submissionGrade = SubmissionGrade::where('submission_id', $submission->id)
                ->with(['criterionScores.criterion'])
                ->first();
        }

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'task' => $task,
                'submission' => $submission,
                'submissionGrade' => $submissionGrade
            ]);
        }

        return view('student.tasks.show', compact('task', 'submission', 'submissionGrade'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'required|date',
            'points' => 'required|integer|min:0',
            'rubric' => 'required|array',
            'rubric.name' => 'required|string',
            'rubric.criteria_json' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $task = Task::create([
                'subject_id' => $request->subject_id,
                'title' => $request->title,
                'description' => $request->description,
                'deadline' => $request->deadline,
                'points' => $request->points,
            ]);

            $criteriaData = json_decode($request->rubric['criteria_json'], true) ?? [];

            $totalPoints = 0;
            foreach ($criteriaData as $c) {
                $totalPoints += collect($c['levels'] ?? [])->max(fn($l) => (int) ($l['points'] ?? 0)) ?? 0;
            }

            $rubric = Rubric::create([
                'task_id' => $task->id,
                'name' => $request->rubric['name'],
                'total_points' => $totalPoints,
                'auto_grade_enabled' => 1,
                'created_by' => auth()->id(),
            ]);

            foreach ($criteriaData as $index => $c) {
                $levels = $c['levels'] ?? [];
                usort($levels, fn($a, $b) => (int) ($b['points'] ?? 0) - (int) ($a['points'] ?? 0));

                $cleanLevels = array_map(fn($l) => [
                    'label' => trim($l['label'] ?? ''),
                    'points' => (int) ($l['points'] ?? 0),
                    'description' => trim($l['description'] ?? ''),
                ], $levels);

                $maxPoints = !empty($cleanLevels) ? $cleanLevels[0]['points'] : 0;

                RubricCriterion::create([
                    'rubric_id' => $rubric->id,
                    'criterion_name' => trim($c['name'] ?? 'Unnamed Criterion'),
                    'description' => trim($c['description'] ?? ''),
                    'max_points' => $maxPoints,
                    'checking_type' => 'ai',
                    'checking_rules' => ['levels' => $cleanLevels],
                    'weight' => 1.0,
                    'order_index' => $index,
                ]);
            }

            $task->load('rubric.criteria');

            $students = User::whereHas('joinedClasses', function ($query) use ($request) {
                $query->where('lab_sessions.id', $request->subject_id);
            })->get();

            foreach ($students as $student) {
                Mail::send('emails.new_task_notification', ['student' => $student, 'task' => $task], function ($message) use ($student, $task) {
                    $message->to($student->email)
                        ->subject('LMMS - New Task Assigned: ' . $task->title);
                });
            }

            $this->logProfessorActivity(
                $request->subject_id,
                'Posted a task: "' . $task->title . '"'
            );

            DB::commit();
            return response()->json(['success' => true, 'task' => $task]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Task Creation Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to save.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'required|date',
            'points' => 'required|integer|min:0',
            'rubric' => 'required|array',
            'rubric.name' => 'required|string',
            'rubric.criteria_json' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $task = Task::findOrFail($id);

            $task->update([
                'title' => $request->title,
                'description' => $request->description,
                'deadline' => $request->deadline,
                'points' => $request->points,
            ]);

            if ($task->rubric) {
                $task->rubric->criteria()->delete();
                $task->rubric()->delete();
            }

            $criteriaData = json_decode($request->rubric['criteria_json'], true) ?? [];
            $totalPoints = 0;
            foreach ($criteriaData as $c) {
                $totalPoints += collect($c['levels'] ?? [])->max(fn($l) => (int) ($l['points'] ?? 0)) ?? 0;
            }

            $rubric = Rubric::create([
                'task_id' => $task->id,
                'name' => $request->rubric['name'],
                'total_points' => $totalPoints,
                'auto_grade_enabled' => 1,
                'created_by' => auth()->id(),
            ]);

            foreach ($criteriaData as $index => $c) {
                $levels = $c['levels'] ?? [];
                usort($levels, fn($a, $b) => (int) ($b['points'] ?? 0) - (int) ($a['points'] ?? 0));

                $cleanLevels = array_map(fn($l) => [
                    'label' => trim($l['label'] ?? ''),
                    'points' => (int) ($l['points'] ?? 0),
                    'description' => trim($l['description'] ?? ''),
                ], $levels);

                $maxPoints = !empty($cleanLevels) ? $cleanLevels[0]['points'] : 0;

                RubricCriterion::create([
                    'rubric_id' => $rubric->id,
                    'criterion_name' => trim($c['name'] ?? 'Unnamed Criterion'),
                    'description' => trim($c['description'] ?? ''),
                    'max_points' => $maxPoints,
                    'checking_type' => 'ai',
                    'checking_rules' => ['levels' => $cleanLevels],
                    'weight' => 1.0,
                    'order_index' => $index,
                ]);
            }

            $task->load('rubric.criteria');

            $this->logProfessorActivity(
                $task->subject_id,
                'Updated the task: "' . $task->title . '"'
            );

            DB::commit();
            return response()->json(['success' => true, 'task' => $task]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Task Update Failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update.'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $task = Task::findOrFail($id);

            if ($task->rubric) {
                $task->rubric->criteria()->delete();
                $task->rubric->delete();
            }

            $task->submissions()->delete();

            $taskTitle = $task->title;
            $labSessionId = $task->subject_id;

            $task->delete();

            $this->logProfessorActivity(
                $labSessionId,
                'Deleted task: "' . $taskTitle . '"'
            );

            DB::commit();
            return redirect()->back()->with('success', 'Task and all related student submissions were successfully deleted.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to delete task: ' . $e->getMessage());
        }
    }

    private function logProfessorActivity($labSessionId, $content)
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'lab_session_id' => $labSessionId,
            'log_type' => 'professor_activity',
            'content' => $content,
        ]);
    }
}