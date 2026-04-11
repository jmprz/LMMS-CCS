<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\LabSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Submission;

class AdminController extends Controller
{
public function index(Request $request)
{
    $user = auth()->user();

    // --- PROFESSOR VIEW ---
    if ($user->role === 'professor') {
        $activeSessions = LabSession::where('faculty_id', $user->id)
            ->where('is_active', true)
            ->latest()
            ->get();

        $selectedClassId = $request->query('session_id') ?? $activeSessions->first()?->id;
        
        $class = $selectedClassId ? LabSession::with('students')->find($selectedClassId) : null;
        $activeStudents = $class ? $class->students : collect();

        return view('professor.dashboard', compact('activeStudents', 'activeSessions', 'class'));
    }

    // --- ADMIN VIEW ---
    if ($user->role === 'admin') {
        // 1. Get the lists/collections
        $allSessions = LabSession::with('faculty')->latest()->get();
        
        // 2. Get the Specific Stats for your Dashboard cards
        $totalUsers = User::count();
        $totalStudents = User::where('role', 'student')->count();
        $totalProfessors = User::where('role', 'professor')->count();
        
        $activeClassesCount = LabSession::where('is_active', true)->count();
        
        $upcomingClasses = LabSession::where('is_active', false)
            ->orderBy('schedule_time', 'asc')
            ->take(5)
            ->get();

        // 3. Activity Logs (Ensure you have an ActivityLog model/table)
        $logs = \App\Models\ActivityLog::latest()->take(10)->get(); 

        // Return EVERYTHING in one compact or array
        return view('admin.dashboard', compact(
            'allSessions', 
            'totalStudents', 
            'totalProfessors', 
            'totalUsers', 
            'activeClassesCount', 
            'upcomingClasses', 
            'logs'
        ));
    }

    // If neither, go home
    return redirect('/');
}
public function toggleSession($id)
{
    $session = LabSession::findOrFail($id);
    $session->is_active = !$session->is_active;

    if (!$session->is_active) {
        $session->is_broadcasting = false;
    }

    // GET: List of everyone who marked attendance TODAY
    $presentToday = \App\Models\Attendance::where('lab_session_id', $id)
        ->where('attendance_date', now()->toDateString())
        ->with('student') // Loads the User model info
        ->get();
        
    $session->save();
    return back()->with('success', $session->is_active ? 'Session Started' : 'Session Ended');
}

public function toggleBroadcast($id)
{
    $session = LabSession::findOrFail($id);
    $session->is_broadcasting = !$session->is_broadcasting;
    $session->save();
    
    return back()->with('success', 'Broadcast Status Updated');
}
public function getActiveStatus()
{
    // Only check students present in the current active session
    $activeSessionId = LabSession::where('is_active', true)->value('id');

    $presentIds = DB::table('class_student')
                    ->where('lab_session_id', $activeSessionId)
                    ->where('is_present', true)
                    ->pluck('user_id')
                    ->toArray();

    return response()->json(['present_ids' => $presentIds]);
}
 public function generateCode(Request $request) 
{
    $request->validate([
        'class_code'    => 'required|string|max:20|unique:lab_sessions,class_code', // Manual input validation
        'faculty_id'    => 'required|exists:users,id',
        'subject_name'  => 'required|string|max:255',
        'semester'      => 'required|string',
        'school_year'   => 'required|string',
        'schedule_day'  => 'required',
        'start_time'    => 'required',
        'end_time'      => 'required',
        'program'       => 'required',
        'year_level'    => 'required',
        'section'       => 'required',
    ]);
    
    
    $formattedTime = date("g:i A", strtotime($request->start_time)) . ' - ' . date("g:i A", strtotime($request->end_time));

    LabSession::create([
        'class_code'    => strtoupper(trim($request->class_code)), // Use the input, forced to UPPERCASE
        'subject_name'  => $request->subject_name,
        'semester'      => $request->semester,
        'school_year'   => $request->school_year,
        'schedule_day'  => $request->schedule_day,
        'schedule_time' => $formattedTime,
        'program'       => $request->program,
        'year_level'    => $request->year_level, 
        'section'       => $request->section,    
        'faculty_id'    => $request->faculty_id,
        'is_active'     => true,
    ]);

    return back()->with('success', 'Academic session created successfully with code: ' . strtoupper($request->class_code));
}

public function statusCheck(Request $request)
{
    $sessionId = $request->query('session_id');

    // If no session is selected, return empty to avoid errors
    if (!$sessionId) {
        return response()->json(['present_ids' => []]);
    }

    $presentIds = DB::table('class_student')
        ->where('lab_session_id', $sessionId)
        ->where('is_present', true)
        // Students who sent a heartbeat in the last 60 seconds
        ->where('updated_at', '>=', now()->subSeconds(60)) 
        ->pluck('user_id');

    return response()->json(['present_ids' => $presentIds]);
}

public function gradeSubmission(Request $request, $id)
{
    $submission = \App\Models\Submission::findOrFail($id);
    
    $submission->update([
        'grade' => $request->grade,
        'feedback' => $request->feedback
    ]);

    return back()->with('success', 'Grade and Feedback saved!');
}

}