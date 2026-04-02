<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LabSession;
use Illuminate\Support\Facades\Auth;

class ClassroomController extends Controller
{
    public function index()
    {
        // Only fetch sessions belonging to the logged-in Professor
        $sessions = LabSession::where('faculty_id', Auth::id())
                              ->latest()
                              ->get(); 
        
        // Change view path from 'admin.classroom.index' to 'professor.classroom'
        return view('professor.classroom.index', compact('sessions'));
    }

    // app/Http/Controllers/ClassroomController.php
public function show($id)
    {
        // 1. Fetch with Eager Loading
        $session = LabSession::with([
            'students', 
            'tasks.submissions.user', 
            'quizzes.attempts.user', 
            'quizzes.questions',
            'materials'
        ])->findOrFail($id);

        // 2. SECURITY CHECK: Ensure this professor actually owns this session
        // If the current user ID doesn't match the faculty_id, block them.
        if ($session->faculty_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this classroom.');
        }

        // 3. Logic for the student dropdown/monitoring
        $activeStudents = \App\Models\User::where('role', 'student')->get();
        $tasks = $session->tasks; 
        
        // 4. Change view path from 'admin.classroom.show' to 'professor.classroom-detail' 
        // (Or whatever you named your detail blade file)
        return view('professor.classroom.show', [
            'session' => $session,
            'class' => $session, 
            'activeStudents' => $activeStudents,
            'tasks' => $tasks,
            'quizzes' => $session->quizzes ?? collect()
        ]);
    }

}
