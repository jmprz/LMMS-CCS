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

    // 1. Initialize ALL variables with defaults at the top
    $sessions = collect();
    $professors = collect();
    $allStudents = collect(); 

    // 2. Fill variables based on role
    if ($user->role === 'admin') {
        $sessions = LabSession::with(['faculty:id,name', 'students:id,name,school_id'])
            ->withCount('students')
            ->latest()
            ->get();
        
        $professors = \App\Models\User::where('role', 'professor')
            ->select('id', 'name')
            ->get();

        // Get the students
        $allStudents = \App\Models\User::where('role', 'student')
            ->select('id', 'name', 'school_id', 'program', 'year_level', 'section')
            ->orderBy('name', 'asc') // Sorts A to Z by name
            ->get();

        return view('admin.classroom.index', compact('sessions', 'professors', 'allStudents'));
    }

    // 3. Logic for Professors (if they use a different view)
    if ($user->role === 'professor') {
        $sessions = LabSession::where('faculty_id', $user->id)
            ->with(['faculty:id,name', 'students:id,name,school_id'])
            ->withCount('students')
            ->latest()
            ->get();

        // Note: We still pass $allStudents (empty) to avoid errors if the 
        // professor view shares the same Alpine layout.

        $allStudents = $allStudents ?? collect(); 
        $professors = $professors ?? collect();

        return view('professor.classroom.index', compact('sessions', 'allStudents'));
    }

    abort(403);
}

public function enroll(Request $request, $id)
{
    $session = LabSession::findOrFail($id);
    $studentIds = $request->input('student_ids', []);

    // Sync without detaching so we don't remove existing students
    $session->students()->syncWithoutDetaching($studentIds);

    // Return the fresh list of students to the frontend
    return response()->json([
        'message' => 'Enrolled successfully',
        'updated_students' => $session->students()->select('users.id', 'users.name', 'users.school_id')->get()
    ]);
}

public function unenroll($sessionId, $studentId)
{
    $session = LabSession::findOrFail($sessionId);
    $session->students()->detach($studentId);

    return response()->json(['message' => 'Unenrolled successfully']);
}
    // app/Http/Controllers/ClassroomController.php
public function show($id)
    {
        // 1. Fetch the lab session with only the necessary data for this specific class
        $session = LabSession::with([
            'students', 
            'tasks.submissions.user', 
            'quizzes.attempts.user', 
            'materials', 
            'faculty'
        ])->findOrFail($id);

        // 2. SECURITY CHECK: Ensure only the assigned Professor or an Admin can enter
        if ($session->faculty_id !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized access to this classroom.');
        }

        // 3. FIXED: Only grab students who are actually enrolled in THIS specific session
        // This stops all students in the database from appearing in your grid.
        $activeStudents = $session->students; 

        $tasks = $session->tasks;

        // 4. Determine which view to show based on the user's role
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
public function destroy(LabSession $classroom)
{
    $classroom->delete();
    return back()->with('success', 'Classroom deleted successfully.');
    }

    // 🟢 1. Handles the "Start / Stop Lab Session" Button
    public function toggleSession(Request $request, $id)
    {
        $class = LabSession::findOrFail($id);

        // Flip the active status
        $class->is_active = !$class->is_active;

        // Safety feature: If a professor stops the class, automatically stop the screen share too
        if (!$class->is_active) {
            $class->is_broadcasting = false;
        }

        $class->save();

        return response()->json([
            'success' => true,
            'is_active' => $class->is_active
        ]);
    }

    // 🟢 2. Handles the "Share My Screen / Stop Broadcasting" Button
    public function broadcast(Request $request, $id)
    {
        $class = LabSession::findOrFail($id);
        
        // Flip the broadcasting status
        $class->is_broadcasting = !$class->is_broadcasting;
        $class->save();

        // Return a clean JSON response so the Javascript doesn't crash!
        return response()->json([
            'success' => true,
            'is_broadcasting' => $class->is_broadcasting
        ]);
    }
}
