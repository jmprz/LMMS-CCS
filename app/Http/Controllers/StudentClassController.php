<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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

}
