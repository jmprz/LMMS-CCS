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

        // Fetch tasks that the student has submitted but are not yet graded
        $pendingTasks = \App\Models\Task::whereHas('submissions', function ($q) use ($user) {
            $q->where('user_id', $user->id)->whereNull('grade');
        })->with('labSession.faculty')->latest()->get();

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
            return response()->json(['status' => 'error', 'message' => 'The deadline has passed. Submission closed.'], 403);
        }

        $request->validate([
            'submission' => 'required|file|mimes:pdf,zip,doc,docx,png,jpg,php,py,dart,js,java,cpp,c,css,html,txt|max:10240',
        ]);

        // 3. LIVE SESSION DURATION CALCULATION
        $submittedAt = now();
        $taskCreatedAt = \Carbon\Carbon::parse($task->created_at);

        $durationSeconds = max(0, $submittedAt->getTimestamp() - $taskCreatedAt->getTimestamp());

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

            // Move file to public directory
            $file->move(public_path($folderPath), $filename);
            $fullPath = $folderPath . '/' . $filename;

            // 5. Cleanup Obsolete/Prior Uploads
            $oldSubmission = Submission::where('task_id', $taskId)->where('user_id', $userId)->first();
            if ($oldSubmission && file_exists(public_path($oldSubmission->file_path))) {
                @unlink(public_path($oldSubmission->file_path));
            }

            // 6. Save or Update Submission data
            $submission = Submission::updateOrCreate(
                ['task_id' => $taskId, 'user_id' => $userId],
                [
                    'file_path' => $fullPath,
                    'original_filename' => $file->getClientOriginalName(),
                    'duration_seconds' => $durationSeconds,
                    'submitted_at' => $submittedAt,
                ]
            );

            // 7. Activity Log row
            \App\Models\ActivityLog::create([
                'user_id' => $userId,
                'log_type' => 'submission',
                'content' => "Student submitted activity: \"" . $task->title . "\"",
                'lab_session_id' => $task->subject_id ?? $task->lab_session_id,
                'duration_seconds' => $durationSeconds,
            ]);

            // Auto-grade submission using Gemini if configured
            try {
                $gradingService = new \App\Services\GeminiGradingService();
                $gradingService->gradeSubmission($submission);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Auto-grading failed: ' . $e->getMessage());
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Uploaded successfully to ' . $formattedName,
                'submission' => $submission
            ]);
        }
    }

    public function deleteTask(Request $request, $taskId)
    {
        $task = Task::findOrFail($taskId);

        if ($task->deadline && Carbon::now()->gt(Carbon::parse($task->deadline))) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => 'The deadline has passed. You can no longer delete your submission.'], 403);
            }
            return back()->with('error', 'The deadline has passed. You can no longer delete your submission.');
        }

        $submission = Submission::where('task_id', $taskId)
            ->where('user_id', auth()->id())
            ->first();

        if ($submission) {
            $filePath = public_path($submission->file_path);
            if (file_exists($filePath)) {
                @unlink($filePath);
            }

            $submission->delete();

            // Log activity
            ActivityLog::create([
                'user_id' => auth()->id(),
                'log_type' => 'submission_removed',
                'content' => "Student unsubmitted activity: \"" . $task->title . "\"",
                'lab_session_id' => $task->subject_id ?? $task->lab_session_id,
                'duration_seconds' => 0,
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => 'success', 'message' => 'Submission deleted successfully.']);
            }

            return back()->with('success', 'Submission deleted successfully.');
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => 'error', 'message' => 'No submission found to delete.'], 404);
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
        $class = LabSession::with(['students', 'materials', 'faculty'])->findOrFail($id);

        $quizzes = \App\Models\Quiz::where('subject_id', $id)
            ->where('published_at', '<=', now())
            ->get();

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
            return [$item->id => (bool) $item->is_active];
        });

        return response()->json($statusMap);
    }

    public function getGradedTasks()
    {
        $userId = auth()->id();

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
            ->take(6)
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

    public function browserHome($id)
    {
        return view('student.browser-home', compact('id'));
    }

    public function customSearch(Request $request, $id)
    {
        $query = trim($request->input('q', ''));

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

        if (empty($query)) {
            return view('student.search', compact('query', 'results', 'id'));
        }

        $forbiddenDomains = [
            'duckduckgo.com',
            'bing.com',
            'microsoft.com',
            'msn.com',
            'google.com',
            'yahoo.com',
            'mojeek.com',
            'ask.com',
            'doubleclick.net',
            'javascript:',
            'mailto:',
            '#'
        ];

        try {
            $response = Http::withoutVerifying()
                ->timeout(5)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                    'Accept-Language' => 'en-US,en;q=0.9',
                ])
                ->asForm()
                ->post('https://html.duckduckgo.com/html/', [
                    'q' => $query
                ]);

            if ($response->successful()) {
                $dom = new \DOMDocument();
                @$dom->loadHTML(mb_convert_encoding($response->body(), 'HTML-ENTITIES', 'UTF-8'));
                $xpath = new \DOMXPath($dom);

                $nodeList = $xpath->query("//a[contains(@class, 'result__a')]");

                foreach ($nodeList as $node) {
                    $rawHref = trim($node->getAttribute('href'));
                    $title = trim(preg_replace('/\s+/', ' ', $node->nodeValue));

                    $actualUrl = $rawHref;
                    if (strpos($rawHref, 'uddg=') !== false) {
                        parse_str(parse_url($rawHref, PHP_URL_QUERY), $queryParameters);
                        if (!empty($queryParameters['uddg'])) {
                            $actualUrl = urldecode($queryParameters['uddg']);
                        }
                    }

                    if (strpos($actualUrl, 'http') !== 0)
                        continue;

                    $isForbidden = false;
                    foreach ($forbiddenDomains as $forbidden) {
                        if (strpos(strtolower($actualUrl), $forbidden) !== false) {
                            $isForbidden = true;
                            break;
                        }
                    }
                    if ($isForbidden)
                        continue;

                    $alreadyExists = false;
                    foreach ($results as $existing) {
                        if ($existing['FirstURL'] === $actualUrl) {
                            $alreadyExists = true;
                            break;
                        }
                    }

                    if (!$alreadyExists && strlen($title) > 3) {
                        $results[] = [
                            'FirstURL' => $actualUrl,
                            'Text' => $title
                        ];
                    }

                    if (count($results) >= 15)
                        break;
                }
            }
        } catch (\Exception $e) {
            \Log::warning("DuckDuckGo HTML search failed: " . $e->getMessage());
        }

        if (count($results) === 0) {
            $urlEncodedQuery = urlencode($query);
            $displayQuery = htmlspecialchars($query);

            $results = [
                ['FirstURL' => "https://stackoverflow.com/search?q={$urlEncodedQuery}", 'Text' => "Stack Overflow: \"{$displayQuery}\""],
                ['FirstURL' => "https://github.com/search?q={$urlEncodedQuery}", 'Text' => "GitHub Code Repositories: \"{$displayQuery}\""],
                ['FirstURL' => "https://www.geeksforgeeks.org/search/?q={$urlEncodedQuery}", 'Text' => "GeeksforGeeks Articles: \"{$displayQuery}\""],
                ['FirstURL' => "https://developer.mozilla.org/en-US/search?q={$urlEncodedQuery}", 'Text' => "MDN Web Docs: \"{$displayQuery}\""],
                ['FirstURL' => "https://dev.to/search?q={$urlEncodedQuery}", 'Text' => "DEV Community Articles: \"{$displayQuery}\""],
                ['FirstURL' => "https://medium.com/search?q={$urlEncodedQuery}", 'Text' => "Medium Tech Articles: \"{$displayQuery}\""],
                ['FirstURL' => "https://www.freecodecamp.org/news/search/?query={$urlEncodedQuery}", 'Text' => "freeCodeCamp News: \"{$displayQuery}\""]
            ];
        }

        return view('student.search', compact('query', 'results', 'id'));
    }
}