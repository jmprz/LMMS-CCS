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
    // Use now() helper to avoid Carbon namespace issues
    $task = Task::with('labSession')->findOrFail($taskId);
    $user = auth()->user();

    if ($task->deadline && now()->gt(Carbon::parse($task->deadline))) {
        return response()->json(['status' => 'error', 'message' => 'The deadline has passed.'], 403);
    }

    $request->validate([
        'submission' => 'required|file|mimes:pdf,zip,doc,docx,png,jpg,php,py,dart,js,java,cpp,c,css,html,txt|max:10240',
    ]);

    if ($request->hasFile('submission')) {
        $file = $request->file('submission');

        // 1. Format Subject - Added default fallback
        $subjectCode = strtoupper($task->labSession->class_code ?? 'GENERAL');

        // 2. Format Section
        $section = strtoupper(($user->year_level ?? '') . ($user->section ?? 'NA'));

        // 3. Format Student Name (LASTNAME_FIRSTNAME)
        // Trim removes accidental spaces at the start or end
        $nameParts = explode(' ', trim($user->name));
        if (count($nameParts) > 1) {
            $lastName = array_pop($nameParts); 
            $firstName = implode('_', $nameParts); 
            $formattedName = strtoupper($lastName . '_' . $firstName);
        } else {
            $formattedName = strtoupper($user->name);
        }

        // 4. Build the Path
        $folderPath = "submissions/{$subjectCode}/{$section}/{$formattedName}";
        $filename = time() . '_' . $file->getClientOriginalName();
        
        // Laravel's move() automatically creates folders if they don't exist
        $file->move(public_path($folderPath), $filename);
        $fullPath = $folderPath . '/' . $filename;

        // 5. Cleanup Old Files
        $oldSubmission = Submission::where('task_id', $taskId)->where('user_id', $user->id)->first();
        if ($oldSubmission && file_exists(public_path($oldSubmission->file_path))) {
            @unlink(public_path($oldSubmission->file_path)); // @ suppresses errors if file is missing
        }

        // 6. Save to Database
        Submission::updateOrCreate(
            ['task_id' => $taskId, 'user_id' => $user->id],
            [
                'file_path' => $fullPath,
                'original_filename' => $file->getClientOriginalName(),
                'duration_seconds' => abs(now()->diffInSeconds($task->created_at)),
                'submitted_at' => now(),
            ]
        );

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
            'log_type' => $request->type,
            'content' => $detail,
            'lab_session_id' => $labSessionId,
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