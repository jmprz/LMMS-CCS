<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LabSession;

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
    $class = \App\Models\LabSession::with('students')->findOrFail($id);

    // 2. Fetch the tasks for this session (This is the missing piece!)
    $tasks = \App\Models\Task::where('subject_id', $id)->get();

    // 3. Fetch active sessions
    $activeSessions = \App\Models\LabSession::where('is_active', true)->get();
    
    // 4. Define $sessionStatus
    $sessionStatus = auth()->user()->joinedClasses()->where('lab_session_id', $id)->first();
    
    // 5. Safely calculate $isPresent
    $isPresent = $sessionStatus ? $sessionStatus->pivot->is_present : false;

    // 6. Pass $tasks to the view
    return view('student.subject', compact('class', 'activeSessions', 'isPresent', 'tasks'));
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

}
