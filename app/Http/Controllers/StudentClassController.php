<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LabSession;

class StudentClassController extends Controller
{
// 1. For the Dashboard (The list)
public function index() {
    $joinedClasses = auth()->user()->joinedClasses;
    return view('student.dashboard', compact('joinedClasses'));
}

// 2. For the Subject Page (The button)
public function show($id) {
    $class = \App\Models\LabSession::findOrFail($id);
    return view('student.subject', compact('class')); // This sends the $class variable!
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

public function stopPresenting(Request $request)
{
    $student = auth()->user(); // Or however you identify your student
    
    // Assuming you have a 'is_presenting' column or a presence table
    $student->update(['is_presenting' => false]); 

    return response()->json(['status' => 'success']);
}

}
