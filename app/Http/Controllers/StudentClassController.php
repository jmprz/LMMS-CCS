<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LabSession;
use Illuminate\Support\Facades\Storage;
use App\Models\Submission;
use Carbon\Carbon;
use App\Models\Task;
use App\Models\ActivityLog;
use App\Models\Quiz;
use Illuminate\Support\Facades\DB;


class StudentClassController extends Controller
{
// 1. For the Dashboard (The list)
public function index() {
    $joinedClasses = auth()->user()->joinedClasses;
    // Query your active sessions
    $activeSessions = \App\Models\LabSession::where('is_active', true)->get();
    
    // Combine everything into one view return
    return view('student.dashboard', compact('joinedClasses', 'activeSessions'));
}

public function show($id)
{
    // 1. Fetch the specific session
    $class = LabSession::with('students', 'materials')->findOrFail($id);

    // 2. Fetch the tasks for this session
    $tasks = Task::where('subject_id', $id) // Ensure this column name matches your DB (usually lab_session_id or subject_id)
        ->with(['currentUserSubmission']) 
        ->get();

    // 3. Fetch active sessions for the sidebar/navigation
    $activeSessions = LabSession::where('is_active', true)->get();
    
    // 4. Check if student is in this class and get their presence status
    $sessionStatus = auth()->user()->joinedClasses()->where('lab_session_id', $id)->first();
    $isPresent = $sessionStatus ? $sessionStatus->pivot->is_present : false;

    // 5. Fetch AVAILABLE Quizzes (Scheduled and not Expired)
    // FIX: Changed $sessionId to $id
    $availableQuizzes = Quiz::where('subject_id', $id)
        ->where('published_at', '<=', now()) 
        ->where(function($query) {
            $query->whereNull('expires_at') 
                  ->orWhere('expires_at', '>', now());
        })
        ->get();

    // 6. Pass EVERYTHING to the view
    return view('student.subject', [
        'class' => $class, 
        'activeSessions' => $activeSessions,
        'isPresent' => $isPresent,
        'tasks' => $tasks,
        'quizzes' => $availableQuizzes // Added this!
    ]);
}

public function markPresent(\App\Models\LabSession $labSession)
{
    // Update the 'is_present' column for this specific student and this specific session
    // We use the joinedClasses relationship (belongsToMany)
    auth()->user()->joinedClasses()->updateExistingPivot($labSession->id, [
        'is_present' => true,
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Attendance marked and monitoring active.'
    ]);
}
public function heartbeat(LabSession $labSession)
{
    // Update the timestamp on the pivot table to show the student is still active
    auth()->user()->joinedClasses()->updateExistingPivot($labSession->id, [
        'is_present' => true,
        'updated_at' => now(),
    ]);

    return response()->json(['status' => 'alive']);
}

public function join(Request $request)
{
    $request->validate(['class_code' => 'required']);

    $session = \App\Models\LabSession::where('class_code', $request->class_code)->first();

    if (!$session) {
        return back()->with('error', 'Invalid Class Code.');
    }

    // Attach student to class
    auth()->user()->joinedClasses()->syncWithoutDetaching([$session->id]);

    return redirect()->route('student.dashboard')->with('success', 'Successfully joined ' . $session->subject_name);
}

public function stopPresenting(Request $request, $labSessionId)
{
    // Update the pivot table for this specific session
    auth()->user()->joinedClasses()->updateExistingPivot($labSessionId, [
        'is_present' => false,
    ]);

    return response()->json(['status' => 'success']);
}
public function checkStatus($id)
{
    $class = LabSession::findOrFail($id);

    // Calculate if the student is present
    $isPresent = auth()->user()->joinedClasses()
                    ->where('lab_session_id', $id)
                    ->where('is_present', true)
                    ->exists();

    // Return the full JSON object in one go
    return response()->json([
        'is_active' => (bool) $class->is_active,
        'is_present' => $isPresent
    ]);
}

public function submitTask(Request $request, $taskId)
{
    $task = Task::findOrFail($taskId);

    // 1. Check if deadline has passed
    if ($task->deadline && Carbon::now()->gt(Carbon::parse($task->deadline))) {
        return back()->with('error', 'The deadline for this task has passed. You can no longer submit or update files.');
    }

    $request->validate([
        'submission' => 'required|file|mimes:pdf,zip,doc,docx,png,jpg|max:10240',
    ]);

    if ($request->hasFile('submission')) {
        $file = $request->file('submission');
        $filename = auth()->id() . '_' . time() . '_' . $file->getClientOriginalName();
        
        // Move the file directly to the public/submissions folder (Your fix)
        $file->move(public_path('submissions/task_' . $taskId), $filename);

        $path = 'submissions/task_' . $taskId . '/' . $filename;

        // If replacing an old file, we should delete the old physical file to save space
        $oldSubmission = Submission::where('task_id', $taskId)->where('user_id', auth()->id())->first();
        if ($oldSubmission && file_exists(public_path($oldSubmission->file_path))) {
            unlink(public_path($oldSubmission->file_path));
        }

        $openedAtMs = $request->input('opened_at');
        $nowMs = round(microtime(true) * 1000); 

        $durationSeconds = $openedAtMs ? floor(($nowMs - $openedAtMs) / 1000) : 0;

    // Safety check: if duration is negative (due to clock sync issues) or over 24 hours, set to 0
    if ($durationSeconds < 0 || $durationSeconds > 86400) {
        $durationSeconds = 0;
    }

        Submission::updateOrCreate(
            ['task_id' => $taskId, 'user_id' => auth()->id()],
            [
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'duration_seconds' => $durationSeconds, 
                'submitted_at' => now(),               
            ]
        );

        return back()->with('success', 'Task submitted successfully!');
    }
}

public function deleteTask($taskId)
{
    $task = Task::findOrFail($taskId);

    // 2. Check if deadline has passed before allowing delete
    if ($task->deadline && Carbon::now()->gt(Carbon::parse($task->deadline))) {
        return back()->with('error', 'The deadline has passed. You can no longer delete your submission.');
    }

    $submission = Submission::where('task_id', $taskId)
        ->where('user_id', auth()->id())
        ->first();

    if ($submission) {
        // Delete the physical file from /public/submissions/
        $filePath = public_path($submission->file_path);
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $submission->delete();
        return back()->with('success', 'Submission deleted successfully.');
    }

    return back()->with('error', 'No submission found to delete.');
}

public function logBehavior(Request $request)
{
    $userId = auth()->id() ?? 1;
    $labSessionId = $request->lab_session_id;
    $detail = $request->detail;

    // 1. CLEAN THE URL (Do this first)
    if (str_contains($detail, 'google.com/search?q=')) {
        parse_str(parse_url($detail, PHP_URL_QUERY), $query);
        $detail = "Google Search: " . urldecode($query['q'] ?? 'unknown');
    }

    // 2. RAW UPDATE PREVIOUS LOG
    // We look for the most recent log for this user/session and calculate duration
    $lastLog = DB::table('activity_logs')
                ->where('user_id', $userId)
                ->where('lab_session_id', $labSessionId)
                ->orderBy('id', 'desc')
                ->first();

    if ($lastLog) {
        // We use raw PHP Unix timestamps to get the difference
        $startTime = strtotime($lastLog->created_at);
        $endTime = time(); // Current Unix timestamp (seconds)
        $duration = $endTime - $startTime;

        if ($duration > 0) {
            DB::table('activity_logs')
                ->where('id', $lastLog->id)
                ->update(['duration_seconds' => $duration]);
            
            \Log::info("ID {$lastLog->id} updated. Start: $startTime, End: $endTime, Diff: $duration");
        }
    }

    // 3. INSERT NEW LOG
    DB::table('activity_logs')->insert([
        'user_id' => $userId,
        'log_type' => $request->type,
        'content' => $detail,
        'lab_session_id' => $labSessionId,
        'duration_seconds' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return response()->json(['status' => 'success']);
}
}
