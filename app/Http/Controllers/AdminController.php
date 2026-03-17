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
    public function index()
    {
        $activeStudents = User::where('role', 'student')
                            ->orderBy('name', 'asc')
                            ->paginate(6);
                            
        $activeSessions = LabSession::where('is_active', true)
            ->where('faculty_id', auth()->id())
            ->latest()
            ->get();

        return view('admin.dashboard', compact('activeStudents', 'activeSessions')); 
    }

    public function toggle(LabSession $session)
    {
        try {
            // 1. Toggle the session status
            $session->is_active = !$session->is_active;
            $session->save();

            // 2. If stopping, reset student attendance in the pivot table
            if (!$session->is_active) {
                DB::table('class_student')
                    ->where('lab_session_id', $session->id)
                    ->update(['is_present' => false]);
            }

            return back()->with('success', 'Session status updated!');
        } catch (\Exception $e) {
            Log::error("Toggle Error: " . $e->getMessage());
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
public function getActiveStatus()
{
    // Only check students present in the current active session
    $activeSessionId = LabSession::where('is_active', true)->value('id');

    $presentIds = DB::table('class_student')
                    ->where('lab_session_id', $activeSessionId)
                    ->where('is_present', true)
                    ->pluck('student_id')
                    ->toArray();

    return response()->json(['present_ids' => $presentIds]);
}
    public function generateCode(Request $request) 
    {
        $request->validate([
            'subject_name' => 'required|string|max:255',
            'schedule_day' => 'required',
            'start_time'   => 'required',
            'end_time'     => 'required',
            'program'      => 'required',
            'year_level'   => 'required',
            'section'      => 'required',
        ]);

        $code = strtoupper(Str::random(6));
        $formattedTime = date("g:i A", strtotime($request->start_time)) . ' - ' . date("g:i A", strtotime($request->end_time));

        LabSession::create([
            'class_code'    => $code,
            'subject_name'  => $request->subject_name,
            'schedule_day'  => $request->schedule_day,
            'schedule_time' => $formattedTime,
            'program'       => $request->program,
            'year_level'    => $request->year_level, 
            'section'       => $request->section,    
            'faculty_id'    => auth()->id(),
            'is_active'     => true,
        ]);

        return back()->with('success', 'Session started!');
    }

public function statusCheck(Request $request)
{
    // 1. Get the session ID from the URL query string (e.g., ?session_id=1)
    $sessionId = $request->query('session_id');

    // 2. Query with both the Session ID and the heartbeat filter
    $presentIds = DB::table('class_student')
        ->where('lab_session_id', $sessionId)
        ->where('is_present', true)
        ->where('updated_at', '>=', now()->subMinutes(1)) 
        ->pluck('student_id'); // Ensure this matches your database column name

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