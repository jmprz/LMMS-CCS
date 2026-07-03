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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\ViolationEnforcementService;

class StudentClassController extends Controller
{
    // 1. For the Dashboard (The list)
    public function index()
    {
        $user = auth()->user();

        $joinedClasses = $user->joinedClasses->map(function ($class) use ($user) {
            $class->total_attended_days = \App\Models\Attendance::where('user_id', $user->id)
                ->where('lab_session_id', $class->id)
                ->count();

            return $class;
        });

        $activeSessions = \App\Models\LabSession::where('is_active', true)->get();

        // 🟢 FIXED: Fetch tasks that the student has submitted but are not yet graded
        $pendingTasks = \App\Models\Task::whereHas('submissions', function ($q) use ($user) {
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

        $enforcement = app(ViolationEnforcementService::class)->getStatus(auth()->id(), $labSession->id);

        return response()->json(array_merge([
            'status' => 'alive',
            'is_active' => (bool) $labSession->is_active,
        ], $enforcement));
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
            $submission = Submission::updateOrCreate( // 🚨 ADD "$submission =" HERE
                ['task_id' => $taskId, 'user_id' => $userId],
                [
                    'file_path' => $fullPath,
                    'original_filename' => $file->getClientOriginalName(),
                    'duration_seconds' => $durationSeconds,
                    'submitted_at' => $submittedAt,
                ]
            );

            // 7. Create Timeline Log row with the actual duration included!
            \App\Models\ActivityLog::create([
                'user_id' => $userId,
                'log_type' => 'submission',
                'content' => "Student submitted activity: \"" . $task->title . "\"",
                'lab_session_id' => $task->subject_id, // Maps to your subject_id column
                'duration_seconds' => $durationSeconds,  // Reflects real stopwatch metrics
            ]);

            // ✅ Auto-grade the submission using Gemini
            try {
                $gradingService = new \App\Services\GeminiGradingService();
                $gradingService->gradeSubmission($submission);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Auto-grading failed: ' . $e->getMessage());
                // Grading failure does NOT block submission — fails silently
            }

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
        $detail = $request->detail ?? 'Unknown activity';
        $logType = $request->type ?? 'navigation';

        if (empty($labSessionId) || $labSessionId === 'null') {
            $labSessionId = DB::table('class_student')
                ->join('lab_sessions', 'class_student.lab_session_id', '=', 'lab_sessions.id')
                ->where('class_student.user_id', $userId)
                ->where('lab_sessions.is_active', true)
                ->orderBy('lab_sessions.created_at', 'desc')
                ->value('lab_sessions.id');
        }

        if ($logType === 'violation' && $labSessionId) {
            $result = app(ViolationEnforcementService::class)->recordViolation(
                $userId,
                (int) $labSessionId,
                $detail,
                $request->input('url')
            );

            return response()->json(array_merge(['status' => 'success'], $result));
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
            }
        }

        DB::table('activity_logs')->insert([
            'user_id' => $userId,
            'log_type' => $logType,
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

        $enrollment = auth()->user()->joinedClasses()->where('lab_session_id', $id)->first();
        $violationStatus = app(ViolationEnforcementService::class)->getStatus(auth()->id(), $id);

        return view('student.subject', [
            'class' => $class,
            'isPresent' => $isPresent,
            'quizzes' => $quizzes,
            'violationStatus' => $violationStatus,
        ]);
    }
    public function refreshClassStatuses()
    {
        $classes = auth()->user()->joinedClasses;
        $statusMap = $classes->mapWithKeys(function ($item) {
            // 🟢 FIX: Return the active status so the dashboard turns "LIVE" instantly
            return [$item->id => (bool) $item->is_active];
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
        $gradedTasks = Task::whereHas('submissions', function ($query) use ($userId) {
            $query->where('user_id', $userId)
                ->whereNotNull('grade');
        })
            ->with([
                'labSession.faculty',
                'submissions' => function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                }
            ])
            ->latest()
            ->take(6) // Only show 6 most recent
            ->get()
            ->map(function ($task) {
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

    // 🌐 Safe integrated browser landing page to prevent iframe loops
    public function browserHome($id)
    {
        return view('student.browser-home', compact('id'));
    }

    public function customSearch(Request $request, $id)
    {
        $query = $request->input('q');

        // Log search event to activity timeline
        try {
            \App\Models\ActivityLog::create([
                'user_id' => auth()->id(),
                'lab_session_id' => $id,
                'log_type' => 'search',
                'content' => 'Searched for: ' . $query,
                'duration_seconds' => 0
            ]);
        } catch (\Exception $e) {
            \Log::error('Search Activity Log error: ' . $e->getMessage());
        }

        $results = [];

        if (empty(trim($query))) {
            return view('student.search', compact('query', 'results', 'id'));
        }

        // 🟢 HIGH-STABILITY ENGINES: Accept natural phrases and do not block local environments
        $engines = [
            'bing' => 'https://www.bing.com/search',
            'mojeek' => 'https://www.mojeek.com/search'
        ];

        // Filter out internal system assets, layout utilities, or search platforms
        $forbiddenDomains = [
            'bing.com',
            'microsoft',
            'msn.com',
            'live.com',
            'mojeek',
            'w3.org',
            'google',
            'duckduckgo',
            'yahoo',
            'ask.com',
            'javascript:',
            'mailto:',
            '#'
        ];

        foreach ($engines as $engineName => $engineUrl) {
            try {
                $response = Http::withoutVerifying()
                    ->timeout(4) // 4-second maximum wait time per engine
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                        'Accept-Language' => 'en-US,en;q=0.9',
                    ])
                    ->get($engineUrl, ['q' => $query]);

                if ($response->successful()) {
                    $html = $response->body();

                    $dom = new \DOMDocument();
                    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
                    $xpath = new \DOMXPath($dom);

                    // Fetch every clickable anchor link on the rendered page
                    $links = $xpath->query("//a");

                    foreach ($links as $link) {
                        $href = trim($link->getAttribute('href'));
                        $title = trim(preg_replace('/\s+/', ' ', $link->nodeValue));

                        // 1. Must be an absolute external web path
                        if (strpos($href, 'http') !== 0) {
                            continue;
                        }

                        // 2. Filter out internal tracking routing
                        $isForbidden = false;
                        foreach ($forbiddenDomains as $forbidden) {
                            if (strpos(strtolower($href), $forbidden) !== false) {
                                $isForbidden = true;
                                break;
                            }
                        }
                        if ($isForbidden) {
                            continue;
                        }

                        // 3. Drop layout artifacts, sidebars, cookie policies, or empty elements
                        if (
                            strlen($title) < 12 ||
                            in_array(strtolower($title), ['cached', 'translate this page', 'privacy policy', 'terms of service']) ||
                            strpos(strtolower($title), 'learn more') !== false ||
                            strpos(strtolower($title), 'see results for') !== false
                        ) {
                            continue;
                        }

                        // 4. De-duplicate matches
                        $alreadyExists = false;
                        foreach ($results as $existing) {
                            if ($existing['FirstURL'] === $href) {
                                $alreadyExists = true;
                                break;
                            }
                        }

                        if (!$alreadyExists) {
                            // Keeps 'FirstURL' array key name intact so search.blade.php doesn't break
                            $results[] = [
                                'FirstURL' => $href,
                                'Text' => $title
                            ];
                        }

                        if (count($results) >= 12) {
                            break;
                        }
                    }
                }

                // If the current engine successfully extracted genuine links, break out of loop
                if (count($results) > 0) {
                    break;
                }

            } catch (\Exception $e) {
                \Log::warning("Engine [{$engineName}] connection bypassed: " . $e->getMessage());
                continue;
            }
        }

        // 🟢 SMART CONTEXTUAL FALLBACK (Emergency failsafe if local computer completely loses internet)
        // If the results array is completely empty, it dynamically reads the keywords in the query 
        // and sends the phrase into the search endpoints of major documentation hubs. This will never 404.
        if (count($results) === 0) {
            $words = explode(' ', strtolower(trim($query)));
            $context = 'programming';

            if (in_array('python', $words)) {
                $context = 'python';
            } elseif (in_array('java', $words)) {
                $context = 'java';
            } elseif (in_array('javascript', $words) || in_array('js', $words)) {
                $context = 'javascript';
            } elseif (in_array('html', $words) || in_array('css', $words)) {
                $context = 'web';
            }

            $displayQuery = htmlspecialchars($query);
            $urlEncodedQuery = urlencode($query);

            if ($context === 'python') {
                $results = [
                    ['FirstURL' => "https://docs.python.org/3/search.html?q={$urlEncodedQuery}", 'Text' => "Official Python Documentation Hub - Results for: \"{$displayQuery}\""],
                    ['FirstURL' => "https://realpython.com/search?q={$urlEncodedQuery}", 'Text' => "Real Python Tutorial Index & Video Guides for: \"{$displayQuery}\""],
                    ['FirstURL' => "https://www.geeksforgeeks.org/search/?q={$urlEncodedQuery}", 'Text' => "GeeksforGeeks Python Learning Portal Reference: \"{$displayQuery}\""]
                ];
            } elseif ($context === 'javascript' || $context === 'web') {
                $results = [
                    ['FirstURL' => "https://developer.mozilla.org/en-US/search?q={$urlEncodedQuery}", 'Text' => "MDN Web Docs Engineering Search Network for: \"{$displayQuery}\""],
                    ['FirstURL' => "https://javascript.info/search?query={$urlEncodedQuery}", 'Text' => "The Modern JavaScript Tutorial Collection: \"{$displayQuery}\""],
                    ['FirstURL' => "https://www.w3schools.com/search/index.php?q={$urlEncodedQuery}", 'Text' => "W3Schools Interactive Reference Index: \"{$displayQuery}\""]
                ];
            } else {
                $results = [
                    ['FirstURL' => "https://stackoverflow.com/search?q={$urlEncodedQuery}", 'Text' => "Stack Overflow Community Code Verification Hub for: \"{$displayQuery}\""],
                    ['FirstURL' => "https://github.com/search?q={$urlEncodedQuery}", 'Text' => "Search GitHub Open Source Code Repositories for: \"{$displayQuery}\""],
                    ['FirstURL' => "https://devdocs.io/#q={$urlEncodedQuery}", 'Text' => "DevDocs Unified Multi-Framework Framework Search for: \"{$displayQuery}\""]
                ];
            }
        }

        return view('student.search', compact('query', 'results', 'id'));
    }
}
