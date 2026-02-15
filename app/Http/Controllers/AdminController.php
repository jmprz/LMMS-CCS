<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\LabSession;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Display the Admin Dashboard with the live student grid and active sessions.
     */
    public function index()
    {
        // 1. Fetch students and their presence status
        $activeStudents = User::where('role', 'student')
            ->with('joinedClasses')
            ->get();

        // 2. Fetch only the laboratory sessions that are currently ACTIVE
        $activeSessions = LabSession::where('is_active', true)
            ->where('faculty_id', auth()->id())
            ->latest()
            ->get();

        return view('admin.dashboard', compact('activeStudents', 'activeSessions')); 
    }

    /**
     * API Endpoint for JavaScript Polling: Returns IDs of students currently marked 'present'.
     */
    public function getActiveStatus()
{
    // A student is only "Active" if is_present is true 
    // AND they have sent a heartbeat in the last 60 seconds
    $presentIds = DB::table('class_student')
        ->where('is_present', true)
        ->where('updated_at', '>=', now()->subSeconds(60)) 
        ->pluck('user_id');
        
    return response()->json(['present_ids' => $presentIds]);
}

    /**
     * End a Laboratory Session manually.
     */
    public function endSession(LabSession $session)
    {
        // Set the session to inactive
        $session->update(['is_active' => false]);

        // Reset attendance status for all students in this session
        DB::table('class_student')
            ->where('lab_session_id', $session->id)
            ->update(['is_present' => false]);

        return back()->with('success', 'Laboratory session ended and students disconnected.');
    }

    /**
     * Show the form to manually register a student (Option A).
     */
    public function createStudent()
    {
        return view('admin.students.create');
    }

    /**
     * Save the new student to the MySQL database.
     */
    public function storeStudent(Request $request) 
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|min:8',
            'year_level' => 'required'
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'year_level' => $request->year_level,
            'role' => 'student',
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'Student added successfully!');
    }

    /**
     * Generate the random 6-digit Class Code.
     */
    public function generateCode(Request $request) 
    {
        $request->validate([
            'subject_name' => 'required|string|max:255',
            'schedule_day' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        $formattedTime = date("g:i A", strtotime($request->start_time)) . ' - ' . date("g:i A", strtotime($request->end_time));
        $code = strtoupper(Str::random(6));

        LabSession::create([
            'class_code' => $code,
            'subject_name' => $request->subject_name,
            'schedule_day' => $request->schedule_day,
            'schedule_time' => $formattedTime,
            'faculty_id' => auth()->id(),
            'is_active' => true,
        ]);

        return back()->with('class_code', $code)->with('success', 'Session started!');
    }
}