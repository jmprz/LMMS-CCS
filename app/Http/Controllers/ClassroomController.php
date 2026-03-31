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
    // 1. Fetch the session with EVERYTHING needed for the view at once (Eager Loading)
    $session = \App\Models\LabSession::with([
        'students', 
        'tasks.submissions.user', 
        'quizzes.attempts.user', // This is for viewing student scores
        'quizzes.questions',
        'materials'      // This is for the quiz overview
    ])->findOrFail($id);

    // 2. Keep your existing logic for the student dropdown
    $activeStudents = \App\Models\User::where('role', 'student')->get();
   
    // 3. Keep $tasks separate if your view specifically uses the $tasks variable
    $tasks = $session->tasks; 
    
    // Note: We use $session for 'class' too since they are the same record
    return view('admin.classroom.show', [
        'session' => $session,
        'class' => $session, 
        'activeStudents' => $activeStudents,
        'tasks' => $tasks,
        'quizzes' => $session->quizzes ?? collect() // Fallback to empty collection
    ]);
}

}
