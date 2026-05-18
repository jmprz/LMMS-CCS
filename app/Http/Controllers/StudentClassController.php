<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LabSession;
use Illuminate\Support\Facades\Storage;
use App\Models\Submission;
use Carbon\Carbon;
use App\Models\Task;
use App\Models\ActivityLog;
use App\Models\Quiz;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentClassController extends Controller
{
    // 1. For the Dashboard (The list)
    public function index() {
        $user = auth()->user();
        
        $joinedClasses = $user->joinedClasses->map(function($class) use ($user) {
            $class->total_attended_days = \App\Models\Attendance::where('user_id', $user->id)
                ->where('lab_session_id', $class->id)
                ->count();
                
            return $class;
        });

        $activeSessions = \App\Models\LabSession::where('is_active', true)->get();

        // 🟢 FIXED: Fetch tasks that the student has submitted but are not yet graded
        $pendingTasks = \App\Models\Task::whereHas('submissions', function($q) use ($user) {
            $q->where('user_id', $user->id)->whereNull('grade');
        })->with('labSession.faculty')->latest()->get();
        
        // Pass 'pendingTasks' to the view
        return view('student.dashboard', compact('joinedClasses', 'activeSessions', 'pendingTasks'));
    }

    public function markPresent(\App\Models\LabSession $labSession)
    {
        $userId = auth()->id();
        $today = now()->toDateString();
        
        \App\Models\Attendance::updateOrCreate(
            [
                'user_id' => $userId,
                'lab_session_id' => $labSession->id,
                'attendance_date' => $today
            ],
            [
                'joined_at' => now()->toTimeString(),
                'status' => 'present'
            ]
        );

        auth()->user()->joinedClasses()->updateExistingPivot($labSession->id, [
            'is_present' => true,
        ]);

        return response()->json(['status' => 'success']);
    }

    public function heartbeat(LabSession $labSession)
    {
        auth()->user()->joinedClasses()->updateExistingPivot($labSession->id, [
            'is_present' => true,
            'updated_at' => now(),
        ]);

        return response()->json(['status' => 'alive']);
    }

    public function join(Request $request)
    {
        $request->validate(['class_code' => 'required']);

        $session = \App\Models\LabSession::where('class_code', $request->class_code)->first();

        if (!$session) {
            return back()->with('error', 'Invalid Class Code.');
        }

        auth()->user()->joinedClasses()->syncWithoutDetaching([$session->id]);

        return redirect()->route('student.dashboard')->with('success', 'Successfully joined ' . $session->subject_name);
    }

    public function stopPresenting(Request $request, $labSessionId)
    {
        auth()->user()->joinedClasses()->updateExistingPivot($labSessionId, [
            'is_present' => false,
        ]);

        return response()->json(['status' => 'success']);
    }

    public function checkStatus($id)
    {
        $class = LabSession::findOrFail($id);

        $isPresent = auth()->user()->joinedClasses()
                        ->where('lab_session_id', $id)
                        ->where('is_present', true)
                        ->exists();

        return response()->json([
            'is_active' => (bool) $class->is_active,
            'is_broadcasting' => (bool) $class->is_broadcasting,
            'is_present' => $isPresent
        ]);
    }

public function submitTask(Request $request, $taskId)
{
    // 1. Fetch task along with its associated session details
    $task = Task::with('labSession')->findOrFail($taskId);
    $user = auth()->user();
    $userId = $user->id;

    // 2. Deadline Check
    if ($task->deadline && now()->gt(Carbon::parse($task->deadline))) {
        return response()->json(['status' => 'error', 'message' => 'The deadline has passed.'], 403);
    }

    $request->validate([
        'submission' => 'required|file|mimes:pdf,zip,doc,docx,png,jpg,php,py,dart,js,java,cpp,c,css,html,txt|max:10240',
    ]);

    // 3. STOPWATCH DURATION CALCULATION
    $durationSeconds = 0;
    $submittedAt = now();

    // Check if an entry exists for when they first opened this specific task workspace
    $activity = \App\Models\StudentActivity::where('task_id', $taskId)
        ->where('user_id', $userId)
        ->first();

    if ($activity) {
        // 1. Convert BOTH times to UNIX timestamps
        $startTimestamp = \Carbon\Carbon::parse($activity->created_at)->getTimestamp();
        $endTimestamp = \Carbon\Carbon::parse($submittedAt)->getTimestamp();

        // 2. Wrap with abs() to guarantee it's positive
        $calculatedDuration = abs($endTimestamp - $startTimestamp);
        
        $maxDurationSeconds = 3600; // 1-hour session cap
        $durationSeconds = min($calculatedDuration, $maxDurationSeconds);

        // Mark the individual workspace activity tracking block as completed
        $activity->update([
            'ended_at' => $submittedAt,
            'duration_seconds' => $durationSeconds,
            'is_completed' => true
        ]);
    } else {
        // Fallback: If no workspace log was caught, look at the elapsed time since their last overall activity heartbeat
        $lastLog = \App\Models\ActivityLog::where('user_id', $userId)
            ->where('lab_session_id', $task->subject_id)
            ->latest()
            ->first();
            
        if ($lastLog) {
            // 🟢 FIXED: Convert to raw timestamps here too to avoid negative calculations
            $startTimestamp = \Carbon\Carbon::parse($lastLog->created_at)->getTimestamp();
            $endTimestamp = \Carbon\Carbon::parse($submittedAt)->getTimestamp();
            
            // 🟢 FIXED: Wrapped in abs() so it can NEVER be negative
            $durationSeconds = min(abs($endTimestamp - $startTimestamp), 3600);
        }
    }

    // 4. File Handling & Folder Path Building
    if ($request->hasFile('submission')) {
        $file = $request->file('submission');

        // Format Subject Code
        $subjectCode = strtoupper($task->labSession->class_code ?? 'GENERAL');

        // Format Section string
        $section = strtoupper(($user->year_level ?? '') . ($user->section ?? 'NA'));

        // Format Student Folder Identity (LASTNAME_FIRSTNAME)
        $nameParts = explode(' ', trim($user->name));
        if (count($nameParts) > 1) {
            $lastName = array_pop($nameParts); 
            $firstName = implode('_', $nameParts); 
            $formattedName = strtoupper($lastName . '_' . $firstName);
        } else {
            $formattedName = strtoupper($user->name);
        }

        // Build Public Storage Destination Path
        $folderPath = "submissions/{$subjectCode}/{$section}/{$formattedName}";
        $filename = time() . '_' . $file->getClientOriginalName();
        
        // Move the file into the public uploads directory
        $file->move(public_path($folderPath), $filename);
        $fullPath = $folderPath . '/' . $filename;

        // 5. Cleanup Obsolete/Prior Uploads
        $oldSubmission = Submission::where('task_id', $taskId)->where('user_id', $userId)->first();
        if ($oldSubmission && file_exists(public_path($oldSubmission->file_path))) {
            @unlink(public_path($oldSubmission->file_path));
        }

        // 6. Save or Update Submission data with the correct dynamic duration
        Submission::updateOrCreate(
            ['task_id' => $taskId, 'user_id' => $userId],
            [
                'file_path' => $fullPath,
                'original_filename' => $file->getClientOriginalName(),
                'duration_seconds' => $durationSeconds, // Stores active duration
                'submitted_at' => $submittedAt,
            ]
        );

        // 7. Create Timeline Log row with the actual duration included!
        \App\Models\ActivityLog::create([
            'user_id'          => $userId,
            'log_type'         => 'submission',
            'content'          => "Student submitted activity: \"" . $task->title . "\"",
            'lab_session_id'   => $task->subject_id, // Maps to your subject_id column
            'duration_seconds' => $durationSeconds,  // Reflects real stopwatch metrics
        ]);

        return response()->json([
            'status' => 'success', 
            'message' => 'Uploaded to ' . $formattedName
        ]);
    }
}

    public function deleteTask($taskId)
    {
        $task = Task::findOrFail($taskId);

        if ($task->deadline && Carbon::now()->gt(Carbon::parse($task->deadline))) {
            return back()->with('error', 'The deadline has passed. You can no longer delete your submission.');
        }

        $submission = Submission::where('task_id', $taskId)
            ->where('user_id', auth()->id())
            ->first();

        if ($submission) {
            $filePath = public_path($submission->file_path);
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $submission->delete();
            return back()->with('success', 'Submission deleted successfully.');
        }

        return back()->with('error', 'No submission found to delete.');
    }

   public function logBehavior(Request $request)
{
    $userId = auth()->id() ?? 1;
    $labSessionId = $request->lab_session_id;
    $detail = $request->detail;

    // 🟢 BACKEND AUTO-RESOLVE GUARD: If the frontend sent null, find their active lab session
    if (empty($labSessionId) || $labSessionId === 'null') {
        $labSessionId = \DB::table('lab_session_student') // or your pivot table name
            ->join('lab_sessions', 'lab_session_student.lab_session_id', '=', 'lab_sessions.id')
            ->where('lab_session_student.user_id', $userId)
            ->where('lab_sessions.is_active', true)
            ->orderBy('lab_sessions.created_at', 'desc')
            ->value('lab_sessions.id');
    }

    if (str_contains($detail, 'google.com/search?q=')) {
        parse_str(parse_url($detail, PHP_URL_QUERY), $query);
        $detail = "Google Search: " . urldecode($query['q'] ?? 'unknown');
    }

    $lastLog = DB::table('activity_logs')
                ->where('user_id', $userId)
                ->where('lab_session_id', $labSessionId)
                ->orderBy('id', 'desc')
                ->first();

    if ($lastLog) {
        $startTime = strtotime($lastLog->created_at);
        $endTime = time();
        $duration = $endTime - $startTime;

        if ($duration > 0) {
            DB::table('activity_logs')
                ->where('id', $lastLog->id)
                ->update(['duration_seconds' => $duration]);
            
            \Log::info("ID {$lastLog->id} updated. Start: $startTime, End: $endTime, Diff: $duration");
        }
    }

    DB::table('activity_logs')->insert([
        'user_id' => $userId,
        'log_type' => $request->type ?? 'navigation',
        'content' => $detail,
        'lab_session_id' => $labSessionId, // Safely assigned!
        'duration_seconds' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return response()->json(['status' => 'success']);
}

    public function enterClassroom($id)
    {
        // 1. Fetch class with students and materials
        $class = LabSession::with(['students', 'materials', 'faculty'])->findOrFail($id);

        // 2. Fetch quizzes for this specific subject
        $quizzes = \App\Models\Quiz::where('subject_id', $id)
            ->where('published_at', '<=', now()) 
            ->get();

        // 3. Check if THIS student is marked as present
        $isPresent = $class->students()
                           ->where('user_id', auth()->id())
                           ->first()?->pivot->is_present ?? false;

        // 🟢 FIXED: We send exactly what subject.blade.php needs
        return view('student.subject', [
            'class' => $class,
            'isPresent' => $isPresent,
            'quizzes' => $quizzes
        ]);
    }
    public function refreshClassStatuses()
    {
        $classes = auth()->user()->joinedClasses;
        $statusMap = $classes->mapWithKeys(function ($item) {
            // 🟢 FIX: Return the active status so the dashboard turns "LIVE" instantly
            return [$item->id => (bool)$item->is_active];
        });

        return response()->json($statusMap);
    }
        /**
     * Get tasks that have been graded for the student
     */
    public function getGradedTasks()
    {
        $userId = auth()->id();
        
        // Get all tasks where user has a submission that's been graded
        $gradedTasks = Task::whereHas('submissions', function($query) use ($userId) {
            $query->where('user_id', $userId)
                ->whereNotNull('grade');
        })
        ->with([
            'labSession.faculty',
            'submissions' => function($query) use ($userId) {
                $query->where('user_id', $userId);
            }
        ])
        ->latest()
        ->take(6) // Only show 6 most recent
        ->get()
        ->map(function($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'points' => $task->points,
                'lab_session' => [
                    'subject_name' => $task->labSession->subject_name,
                    'faculty' => [
                        'name' => $task->labSession->faculty->name ?? 'Unknown'
                    ]
                ],
                'submission' => [
                    'grade' => $task->submissions->first()->grade,
                    'feedback' => $task->submissions->first()->feedback,
                    'updated_at' => $task->submissions->first()->updated_at
                ]
            ];
        });
        
        return response()->json($gradedTasks);
    }
}