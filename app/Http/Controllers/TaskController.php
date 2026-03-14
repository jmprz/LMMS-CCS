<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
class TaskController extends Controller
{
 public function submitTask(Request $request, $taskId) {
    $activity = StudentActivity::where('task_id', $taskId)
                               ->where('user_id', auth()->id())
                               ->first();

    $submittedAt = now();
    $activity->update(['submitted_at' => $submittedAt]);

    // Calculate duration in minutes for your thesis data
    $duration = $activity->started_at->diffInMinutes($submittedAt);

    // Check if late
    $task = Task::find($taskId);
    $isLate = $submittedAt > $task->deadline;

    return redirect()->back()->with('success', "Task submitted! Time taken: $duration minutes.");
}
}