<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LabSession;
use App\Models\ActivityLog;
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

        $allStudents = \App\Models\User::where('role', 'student')
            ->select('id', 'first_name', 'last_name', 'middle_name', 'school_id', 'program', 'year_level', 'section')
            ->orderBy('last_name', 'asc') // Changed from 'name' to 'last_name'
            ->get();

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
        // 1. Fetch the lab session and sort students by last_name alphabetically
        $session = LabSession::with([
            'students' => function ($query) {
                $query->orderBy('last_name', 'asc'); // This handles the alphabetical sorting
            },
            'tasks.submissions.user',
            'quizzes.attempts.user',
            'materials',
            'faculty'
        ])->findOrFail($id);

        // 2. SECURITY CHECK
        if ($session->faculty_id !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized access to this classroom.');
        }

        // 3. Get the sorted students
        $activeStudents = $session->students;

        $tasks = $session->tasks;

        // 4. Determine view
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
            'faculty_id' => 'required|exists:users,id',
            'subject_name' => 'required|string|max:255',
            'class_code' => 'required|string|max:50|unique:lab_sessions,class_code,' . $id, // Ignore current ID during unique check
            'start_time' => 'required',
            'end_time' => 'required',
            'schedule_day' => 'required',
            'program' => 'required',
            'year_level' => 'required',
            'section' => 'required',
        ]);

        $session = LabSession::findOrFail($id);

        // Format the time (AM/PM)
        $formattedTime = date("g:i A", strtotime($request->start_time)) . ' - ' . date("g:i A", strtotime($request->end_time));

        $session->update([
            'faculty_id' => $request->faculty_id,
            'subject_name' => $request->subject_name,
            'class_code' => strtoupper($request->class_code), // Ensure it stays uppercase
            'schedule_time' => $formattedTime,
            'semester' => $request->semester,
            'school_year' => $request->school_year,
            'schedule_day' => $request->schedule_day,
            'program' => $request->program,
            'year_level' => $request->year_level,
            'section' => $request->section,
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
    public function getStudentsStatus($id)
    {
        $session = LabSession::with('students')->findOrFail($id);
        if ($session->faculty_id !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403);
        }
        $students = $session->students()->get()->map(function ($student) {
            return [
                'id' => $student->id,
                'name' => $student->last_name . ', ' . $student->first_name,
                'is_present' => (bool) $student->pivot->is_present,
            ];
        });
        return response()->json($students);
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

    /**
     * Return JSON list of enrolled students for the live monitor.
     */
    public function getStudents($classId)
    {
        $session = LabSession::findOrFail($classId);

        // Security: only the assigned professor or admin can view
        if ($session->faculty_id !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized');
        }

        $students = $session->students()
            ->orderBy('last_name')
            ->get(['users.id', 'users.first_name', 'users.last_name'])
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->last_name . ', ' . $student->first_name,
                    'status' => 'offline', // will be updated client‑side when streaming
                ];
            });

        return response()->json($students);
    }

    public function getTasks($id)
    {
        $tasks = \App\Models\Task::where('subject_id', $id)
            ->latest()
            ->get(['id', 'title', 'description', 'deadline', 'points']);

        return response()->json($tasks);
    }

    public function getStudentLogs($userId, $classId)
    {
        // Change 'class_id' to 'lab_session_id' to match your Model and Database
        $logs = ActivityLog::where('user_id', $userId)
            ->where('lab_session_id', $classId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($logs);
    }
    public function getStudentDriveFiles($userId)
    {
        // Security check to guarantee only Authorized Administrators can index assets
        if (Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        // 1. Grab all student submissions along with task descriptions and class structures
        $submissions = \App\Models\Submission::where('user_id', $userId)
            ->with(['task.labSession'])
            ->get();

        // 2. Map and parse rows into the precise metadata objects expected by the folder UI
        $formattedFiles = $submissions->map(function ($submission) {
            $task = $submission->task;
            $session = $task ? $task->labSession : null;

            // Extract directory levels safely with fallbacks if reference mappings are missing
            $yearLevel = $session ? $session->year_level : 1;
            $semester = $session ? $session->semester : 1;
            $classCode = $session ? $session->class_code : 'GENERAL';
            $subjectName = $session ? $session->subject_name : ($task ? $task->title : 'Unassigned Activities');

            // Formats folder text label to: "CS311 - Machine Learning"
            $subjectFolder = strtoupper(trim($classCode));

            return [
                'id' => $submission->id,
                'year_level' => intval($yearLevel),
                'semester' => intval($semester),
                'subject_code' => $subjectFolder,
                'file_name' => $submission->original_filename ?? basename($submission->file_path),
                // Maps the asset link directly to the public disk storage folder path
                'file_url' => asset($submission->file_path),
                'created_at' => $submission->submitted_at ?? $submission->created_at,
            ];
        });

        return response()->json($formattedFiles);
    }
    // Add this method to your ClassroomController
    public function getStudentFilesForClass($userId, $classId)
    {
        $submissions = \App\Models\Submission::where('user_id', $userId)
            ->whereHas('task', function ($q) use ($classId) {
                // Correctly matches the task to the current classroom
                $q->where('id', $classId)
                    ->orWhere('subject_id', $classId);
            })
            ->with('task')
            ->latest('submitted_at') // Sorts by newest first in the database
            ->get();

        $formattedFiles = $submissions->map(function ($submission) {
            return [
                'id' => $submission->id,
                'file_name' => $submission->original_filename ?? basename($submission->file_path) ?? 'Submission File',
                'task_title' => $submission->task->title ?? 'Untitled Task',
                // Formats with Date AND precise Time
                'submitted_at' => $submission->submitted_at
                    ? \Carbon\Carbon::parse($submission->submitted_at)->format('M d, Y • h:i A')
                    : (\Carbon\Carbon::parse($submission->created_at)->format('M d, Y • h:i A')),
                'file_url' => $submission->file_path ? asset($submission->file_path) : '#'
            ];
        });

        return response()->json($formattedFiles);
    }
}
