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
    $session = \App\Models\LabSession::findOrFail($id);

    $activeStudents = \App\Models\User::where('role', 'student')->get();
    // You can also fetch the students or tasks associated with this session here
    return view('admin.classroom.show', compact('session', 'activeStudents'));
}
}
