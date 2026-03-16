<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class ClassroomController extends Controller
{
    public function index()
    {
      // Fetch all lab sessions, maybe ordered by newest first
    $sessions = \App\Models\LabSession::latest()->get(); 
    
    return view('admin.classroom.index', compact('sessions'));
    }

    // app/Http/Controllers/ClassroomController.php
public function show($id)
{
    $class = \App\Models\LabSession::with('students')->findOrFail($id);

    $session = \App\Models\LabSession::findOrFail($id);

    $activeStudents = \App\Models\User::where('role', 'student')->get();
   
    $tasks = \App\Models\Task::where('subject_id', $id)->get();
    
    return view('admin.classroom.show', compact('session', 'activeStudents', 'class', 'tasks'));
}
}
