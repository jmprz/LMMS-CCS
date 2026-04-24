<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AssessmentResult;
use App\Models\StudentActivity;
use App\Models\Task;
use App\Models\Submission;
use Illuminate\Support\Facades\Auth; 

class TaskController extends Controller
{
    public function index()
{
    // Eager load the labSession relationship so you can display session names/titles
    $tasks = \App\Models\Task::with('labSession')->get();
    
    return view('admin.tasks.index', compact('tasks'));
}

   public function submitTask(Request $request, $taskId)
    {
        $userId = Auth::id();
        $task = Task::findOrFail($taskId);

        // 1. Handle the File Upload
        if (!$request->hasFile('submission')) {
            return response()->json(['status' => 'error', 'message' => 'No file uploaded'], 400);
        }

        $file = $request->file('submission');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = 'submissions/task_' . $taskId;
        $file->move(public_path($path), $filename);
        $fullPath = $path . '/' . $filename;

        // 2. Save or Update the Submission record
        $submission = Submission::updateOrCreate(
            ['task_id' => $taskId, 'user_id' => $userId],
            [
                'file_path' => $fullPath,
                'original_filename' => $file->getClientOriginalName(),
                'submitted_at' => now(),
            ]
        );

        // 3. Update Student Activity (Duration Logic)
        $activity = StudentActivity::where('task_id', $taskId)
            ->where('user_id', $userId)
            ->first();

        $durationSeconds = 0;
        $submittedAt = now();

        if ($activity) {
            // Calculate duration in seconds
            $durationSeconds = $activity->created_at->diffInSeconds($submittedAt);
            $maxDurationSeconds = 3600; // 1 hour cap
            $finalDuration = min($durationSeconds, $maxDurationSeconds);

            $activity->update([
                'ended_at' => $submittedAt,
                'duration_seconds' => $finalDuration,
                'is_completed' => true
            ]);
        }

        // 4. Academic Metrics (Assessment Results)
        // Note: For lab tasks, accuracy might be 100% on submission 
        // or calculated later by the professor.
        $totalPoints = $task->points ?? 100;
        
        AssessmentResult::updateOrCreate(
            ['user_id' => $userId, 'task_id' => $taskId],
            [
                'score' => 0, // Placeholder until graded by professor
                'answer_accuracy' => 0, 
                'response_time_ms' => $durationSeconds * 1000 
            ]
        );

        // 5. Return JSON for the Frontend Fetch call
        return response()->json([
            'status' => 'success',
            'message' => 'Task submitted successfully!',
            'submission' => $submission
        ]);
    }

    public function store(Request $request)
    {
        // Validate the incoming data
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'deadline' => 'required|date',
            'subject_id' => 'required|exists:lab_sessions,id',
            'points' => 'required|numeric|min:1',
        ]);


        // Create the task
        \App\Models\Task::create($request->only([
            'title',
            'description',
            'deadline',
            'subject_id',
            'points'
        ]));

        return redirect()->back()->with('success', 'Task created successfully!');
    }
}