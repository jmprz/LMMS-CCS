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
    $user = Auth::user();

    if ($user->role === 'admin') {
        $sessions = LabSession::with([
            'faculty:id,name', 
            'students:id,name,school_id' // <--- Add this line to get the student list
        ])
        ->withCount('students') // Keeps your counter badge working
        ->latest()
        ->get();
        
        $professors = \App\Models\User::where('role', 'professor')
            ->select('id', 'name')
            ->get();

        return view('admin.classroom.index', compact('sessions', 'professors'));
    }

    // Do the same for the professor view if they also need to see student lists
   $sessions = LabSession::with([
    'faculty:id,name', 
    'students' => function($query) {
        $query->select('users.id', 'users.name', 'users.school_id'); // Adjust columns as needed
    }
])
->withCount('students')
->latest()
->get();

    return view('professor.classroom.index', compact('sessions'));
}
    // app/Http/Controllers/ClassroomController.php
public function show($id)
{
    $session = LabSession::with([
        'students', 
        'tasks.submissions.user', 
        'quizzes.attempts.user', 
        'quizzes.questions',
        'materials',
        'faculty' // Add this to see who is teaching
    ])->findOrFail($id);

    // SECURITY CHECK: Allow if user is the Faculty OR if user is an Admin
    if ($session->faculty_id !== Auth::id() && Auth::user()->role !== 'admin') {
        abort(403, 'Unauthorized access to this classroom.');
    }

    $activeStudents = \App\Models\User::where('role', 'student')->get();
    $tasks = $session->tasks; 
    
    // Determine which view to show based on role
    $view = Auth::user()->role === 'admin' ? 'admin.classroom.show' : 'professor.classroom.show';

    return view($view, [
        'session' => $session,
        'class' => $session, 
        'activeStudents' => $activeStudents,
        'tasks' => $tasks,
        'quizzes' => $session->quizzes ?? collect()
    ]);
}

// Edit - Show the form
public function update(Request $request, $id)
{
    $request->validate([
        'faculty_id'   => 'required|exists:users,id',
        'subject_name' => 'required|string|max:255',
        'start_time'   => 'required',
        'end_time'     => 'required',
        // ... add your other validations here
    ]);

    $session = LabSession::findOrFail($id);

    // Format the time exactly how generateCode does it (AM/PM)
    $formattedTime = date("g:i A", strtotime($request->start_time)) . ' - ' . date("g:i A", strtotime($request->end_time));

    $session->update([
        'faculty_id'    => $request->faculty_id,
        'subject_name'  => $request->subject_name,
        'schedule_time' => $formattedTime, // Updates the single column
        'semester'      => $request->semester,
        'school_year'   => $request->school_year,
        'schedule_day'  => $request->schedule_day,
        'program'       => $request->program,
        'year_level'    => $request->year_level,
        'section'       => $request->section,
    ]);

    return back()->with('success', 'Academic session updated successfully!');
}

// Delete - Remove from DB
public function destroy(LabSession $classroom) // This is correct
{
    $classroom->delete();
    return back()->with('success', 'Classroom deleted successfully.');
}

}
