<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AssessmentResult;
use App\Models\StudentActivity;
use App\Models\Task;

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
        $userId = auth()->id();
        $activity = StudentActivity::where('task_id', $taskId)
            ->where('user_id', $userId)
            ->first();

        $submittedAt = now();

        // 1. Calculate duration in seconds for your thesis model
        $durationSeconds = $activity->started_at->diffInSeconds($submittedAt);

        // 2. Update the activity table
        $maxDurationSeconds = 3600; // 1 hour cap
        $finalDuration = min($durationSeconds, $maxDurationSeconds);

        $activity->update([
            'ended_at' => $submittedAt,
            'duration_seconds' => $finalDuration, // Use the capped duration
            'is_completed' => true
        ]);


        // 3. Logic for Assessment Results (Academic Metrics)
        // Here you would calculate score/accuracy based on their submission
        $score = $request->input('score'); // Example: retrieved from request
        $accuracy = ($score / $request->input('total_points')) * 100;

        // Save to your new assessment_results table
        \App\Models\AssessmentResult::create([
            'user_id' => $userId,
            'task_id' => $taskId,
            'score' => $score,
            'answer_accuracy' => $accuracy,
            'response_time_ms' => $durationSeconds * 1000 // Convert to MS for ML model
        ]);

        return redirect()->back()->with('success', "Task submitted!");
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