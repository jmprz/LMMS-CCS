<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\LabSession;

class AdminController extends Controller
{
    /**
     * Display the Admin Dashboard with the live student grid.
     */
    public function index()
    {
        // Fetch only users with the role 'student' 
        // Later, we can filter this by who is 'present'
        $activeStudents = User::where('role', 'student')->get();

        return view('admin.dashboard', compact('activeStudents')); 
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

    // Format the time for better display (e.g., 08:00 AM - 10:00 AM)
    $formattedTime = date("g:i A", strtotime($request->start_time)) . ' - ' . date("g:i A", strtotime($request->end_time));

    $code = strtoupper(\Illuminate\Support\Str::random(6));

    \App\Models\LabSession::create([
        'class_code' => $code,
        'subject_name' => $request->subject_name,
        'schedule_day' => $request->schedule_day,
        'schedule_time' => $formattedTime, // Combine them into the existing column
        'faculty_id' => auth()->id(),
        'is_active' => true,
    ]);

    return back()->with('class_code', $code);
}
}