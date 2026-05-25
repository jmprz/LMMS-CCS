<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Models\SubmissionGrade;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    // -------------------------------------------------------------------------
    // STUDENT — View a graded task with detailed per-criterion feedback
    // GET /student/tasks/{taskId}
    // -------------------------------------------------------------------------
    public function show($taskId)
    {
        $task = Task::with('labSession.faculty')->findOrFail($taskId);

        // Get this student's submission
        $submission = Submission::where('task_id', $taskId)
            ->where('user_id', auth()->id())
            ->first();

        // Get the detailed rubric grade (with all criterion scores loaded)
        $submissionGrade = null;
        if ($submission) {
            $submissionGrade = SubmissionGrade::where('submission_id', $submission->id)
                ->with(['criterionScores.criterion'])
                ->first();
        }

        return view('student.tasks.show', compact('task', 'submission', 'submissionGrade'));
    }

    // -------------------------------------------------------------------------
    // PROFESSOR — Create a new task
    // POST /professor/tasks
    // -------------------------------------------------------------------------
    public function store(Request $request)
    {
        $request->validate([
            'subject_id'  => 'required|integer',
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'deadline'    => 'required|date',
            'points'      => 'required|integer|min:1',
        ]);

        $task = Task::create([
            'subject_id'  => $request->subject_id,
            'title'       => $request->title,
            'description' => $request->description,
            'deadline'    => $request->deadline,
            'points'      => $request->points,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'task' => $task]);
        }

        return redirect()->back()->with('success', 'Task created successfully!');
    }
}