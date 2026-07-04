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
use App\Models\ActivityLog;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Task;

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

        $sessions = LabSession::where('faculty_id', $user->id)
            ->latest()
            ->get();

        $selectedClassId = $request->query('session_id') ?? $activeSessions->first()?->id;
        
        $class = $selectedClassId ? LabSession::with('students')->find($selectedClassId) : null;
        $activeStudents = $class ? $class->students : collect();

        // Get all session IDs belonging to this professor
        $professorSessionIds = $sessions->pluck('id')->toArray();

        // Fetch active student count assigned specifically to this professor's classes
        $totalStudentsCount = User::where('role', 'student')
            ->whereHas('joinedClasses', function($query) use ($user) {
                $query->where('faculty_id', $user->id); 
            })
            ->count();

        // Fetch recent logs scoped strictly to this professor's sections
        $logs = ActivityLog::whereIn('lab_session_id', $professorSessionIds)
            ->with(['user', 'labSession'])
            ->latest()
            ->take(15)
            ->get();

        // 📊 DYNAMIC CHART DATA 1: Count activity log types for this professor's classes
        $navigationLogsCount = ActivityLog::whereIn('lab_session_id', $professorSessionIds)->where('log_type', 'navigation')->count();
        $quizLogsCount = ActivityLog::whereIn('lab_session_id', $professorSessionIds)->where('log_type', 'quiz')->count();
        $submissionLogsCount = ActivityLog::whereIn('lab_session_id', $professorSessionIds)->where('log_type', 'submission')->count();

        // 🟢 FIX: Automatically detect whatever pivot table name is defined in your relationship configuration
        $pivotTable = (new \App\Models\LabSession)->students()->getTable();

        // 📊 DYNAMIC CHART DATA 2: Count attendance distribution using the auto-detected table
        $presentCount = \DB::table($pivotTable)
            ->whereIn('lab_session_id', $professorSessionIds)
            ->where('is_present', true)
            ->count();

        $absentCount = \DB::table($pivotTable)
            ->whereIn('lab_session_id', $professorSessionIds)
            ->where('is_present', false)
            ->count();

        $chartConfigs = $this->buildProfessorChartConfigs(
            $professorSessionIds,
            $sessions,
            $navigationLogsCount,
            $quizLogsCount,
            $submissionLogsCount,
            $presentCount,
            $absentCount
        );

        return view('professor.dashboard', compact(
            'activeStudents', 
            'activeSessions', 
            'class', 
            'sessions', 
            'totalStudentsCount', 
            'logs',
            'navigationLogsCount',
            'quizLogsCount',
            'submissionLogsCount',
            'presentCount',
            'absentCount',
            'chartConfigs'
        ));
    }

    // --- ADMIN VIEW ---
    if ($user->role === 'admin') {
        $allSessions = LabSession::with('faculty')->latest()->get();
        
        $totalUsers = User::count();
        $totalStudents = User::where('role', 'student')->count();
        $totalProfessors = User::where('role', 'professor')->count();
        
        $activeClassesCount = LabSession::where('is_active', true)->count();
        
        // 🟢 SMART CALENDAR TIMELINE: Sorts and grabs upcoming events relative to today
        $todayDayOfWeek = now()->dayOfWeekIso; // 1 (Monday) to 7 (Sunday)
        $daysOfWeekMap = [
            'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 
            'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7
        ];

        $upcomingClasses = LabSession::all()->map(function ($session) use ($todayDayOfWeek, $daysOfWeekMap) {
            $sessionDay = ucfirst(strtolower(trim($session->schedule_day)));
            $sessionDayNumber = $daysOfWeekMap[$sessionDay] ?? null;
            
            if (!$sessionDayNumber) return null;

            // Extract the start time out of the range text (e.g. "9:00 AM" from "9:00 AM - 12:00 PM")
            $timeParts = explode(' - ', $session->schedule_time);
            $startTimeStr = $timeParts[0] ?? null;

            if (!$startTimeStr) return null;

            try {
                // Calculate the difference in calendar days to place it accurately this week
                $daysDifference = $sessionDayNumber - $todayDayOfWeek;
                $classDateTime = now()->addDays($daysDifference);
                
                $parsedTime = \Carbon\Carbon::parse($startTimeStr);
                $classDateTime->setTime($parsedTime->hour, $parsedTime->minute, 0);

                // If the class occurrence has already passed for today/this week, move it to next week's sequence
                if ($classDateTime->isPast()) {
                    $classDateTime->addWeeks(1);
                }

                $session->minutes_until = now()->diffInMinutes($classDateTime, false);
                return $session;
            } catch (\Exception $e) {
                return null;
            }
        })
        ->filter() // Clear out invalid entries
        ->sortBy('minutes_until') // Prioritize closest upcoming schedule first
        ->take(5)
        ->values();

     $logs = \App\Models\ActivityLog::with(['user', 'labSession'])->latest()->take(10)->get();

        $totalAdmins = max(0, $totalUsers - ($totalStudents + $totalProfessors));
        $chartConfigs = $this->buildAdminChartConfigs(
            $allSessions,
            $activeClassesCount,
            $totalStudents,
            $totalProfessors,
            $totalAdmins
        );

        return view('admin.dashboard', compact(
            'allSessions', 
            'totalStudents', 
            'totalProfessors', 
            'totalUsers', 
            'activeClassesCount', 
            'upcomingClasses', 
            'logs',
            'chartConfigs'
        ));
    }

    return redirect('/');
}

public function toggleSession($id)
{
    $session = LabSession::findOrFail($id);
    $session->is_active = !$session->is_active;

    if (!$session->is_active) {
        $session->is_broadcasting = false;
    }

    $presentToday = \App\Models\Attendance::where('lab_session_id', $id)
        ->where('attendance_date', now()->toDateString())
        ->with('student')
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
        'class_code'    => 'required|string|max:20|unique:lab_sessions,class_code',
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
        'class_code'    => strtoupper(trim($request->class_code)),
        'subject_name'  => $request->subject_name,
        'semester'      => $request->semester,
        'school_year'   => $request->school_year,
        'schedule_day'  => $request->schedule_day,
        'schedule_time' => $formattedTime,
        'program'       => $request->program,
        'year_level'    => $request->year_level, 
        'section'       => $request->section,    
        'faculty_id'    => $request->faculty_id,
        'is_active'     => false, // 🟢 FIXED: Classes now start inactive until toggled live
    ]);

    return back()->with('success', 'Academic session created successfully with code: ' . strtoupper($request->class_code));
}

public function statusCheck(Request $request)
{
    $sessionId = $request->query('session_id');

    if (!$sessionId) {
        return response()->json(['present_ids' => []]);
    }

    $presentIds = DB::table('class_student')
        ->where('lab_session_id', $sessionId)
        ->where('is_present', true)
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

public function userIndex()
{
    $user = auth()->user();

    if ($user->role === 'admin') {
        $users = User::with('attendances.labSession')
            ->orderBy('role', 'desc')
            ->orderBy('last_name', 'asc')
            ->get();

        return view('admin.users.index', compact('users'));
    }

    if ($user->role === 'professor') {
        $users = User::with('attendances')
            ->whereHas('joinedClasses', function($query) use ($user) {
                $query->where('faculty_id', $user->id); 
            })
            ->where('role', 'student')
            ->distinct()
            ->orderBy('last_name', 'asc')
            ->get();

        return view('professor.classroom.show', compact('users'));
    }

    return redirect()->route('dashboard');
}

public function getUserLogs(User $user)
{
    try {
        $viewer = auth()->user();
        $query = ActivityLog::where('user_id', $user->id);

        if ($viewer->role === 'professor') {
            $professorSessionIds = \App\Models\LabSession::where('faculty_id', $viewer->id)
                ->pluck('id')
                ->toArray();

            $query->whereIn('lab_session_id', $professorSessionIds);
        }

        $logs = $query->orderBy('created_at', 'desc')->limit(50)->get();

        $formattedLogs = $logs->map(function ($log) {
            $sessionName = 'General Session';
            
            if ($log->lab_session_id) {
                $session = \App\Models\LabSession::find($log->lab_session_id);
                if ($session) {
                    $sessionName = $session->subject_name;
                }
            }

            return [
                'id'               => $log->id,
                'log_type'         => $log->log_type ?? 'navigation',
                'content'          => $log->content ?? 'Interacted with workspace',
                'class_name'       => $sessionName,
                'duration_seconds' => $log->duration_seconds ?? 0,
                'created_at'       => $log->created_at ? $log->created_at->toIso8601String() : now()->toIso8601String()
            ];
        });

        return response()->json($formattedLogs);

    } catch (\Exception $e) {
        \Log::error('Log fetch crashed: ' . $e->getMessage());
        return response()->json([
            [
                'id'               => 0,
                'log_type'         => 'navigation',
                'content'          => 'Error loading activity timeline securely.',
                'class_name'       => 'System',
                'duration_seconds' => 0,
                'created_at'       => now()->toIso8601String()
            ]
        ]);
    }
}

public function updateUser(Request $request, User $user)
{
    $validated = $request->validate([
        'first_name' => 'required|string|max:255',
        'middle_name' => 'nullable|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,' . $user->id,
        'role' => 'required',
        'program' => 'nullable',
        'year_level' => 'nullable',
        'section' => 'nullable',
    ]);

    $validated['name'] = $validated['first_name'] . ' ' . $validated['last_name'];
    $user->update($validated);

    return back()->with('success', 'User updated successfully!');
}

public function destroyUser(User $user)
{
    if (auth()->id() === $user->id) {
        return back()->with('error', 'You cannot delete your own account.');
    }

    $user->delete();
    return back()->with('success', 'User deleted successfully');
}

private function buildWeeklyActivitySeries(array $sessionIds, bool $systemWide = false): array
{
    $labels = [];
    $data = [];

    for ($i = 6; $i >= 0; $i--) {
        $date = now()->subDays($i);
        $labels[] = $date->format('D, M j');

        $query = ActivityLog::whereDate('created_at', $date->toDateString());
        if (!$systemWide) {
            $query->whereIn('lab_session_id', $sessionIds);
        }

        $data[] = $query->count();
    }

    return compact('labels', 'data');
}

private function buildProfessorChartConfigs(
    array $sessionIds,
    $sessions,
    int $navigationLogsCount,
    int $quizLogsCount,
    int $submissionLogsCount,
    int $presentCount,
    int $absentCount
): array {
    $materialLogsCount = ActivityLog::whereIn('lab_session_id', $sessionIds)->where('log_type', 'material')->count();
    $violationLogsCount = ActivityLog::whereIn('lab_session_id', $sessionIds)->where('log_type', 'violation')->count();
    $weekly = $this->buildWeeklyActivitySeries($sessionIds);

    $quizScoreLabels = [];
    $quizScoreData = [];
    $quizParticipationLabels = [];
    $quizParticipationData = [];
    $taskSubmissionLabels = [];
    $taskSubmissionData = [];

    foreach ($sessions as $session) {
        $session->loadCount('students');
        $enrolled = $session->students_count;
        $quizIds = Quiz::where('subject_id', $session->id)->pluck('id');
        $taskIds = Task::where('subject_id', $session->id)->pluck('id');

        if ($quizIds->isNotEmpty()) {
            $attempts = QuizAttempt::whereIn('quiz_id', $quizIds)->get();

            if ($attempts->isNotEmpty()) {
                $avg = $attempts->avg(fn ($attempt) => $attempt->total_questions > 0
                    ? ($attempt->score / $attempt->total_questions) * 100
                    : 0);

                $quizScoreLabels[] = $session->class_code;
                $quizScoreData[] = round($avg, 1);
            }

            if ($enrolled > 0) {
                $participants = QuizAttempt::whereIn('quiz_id', $quizIds)->distinct()->count('user_id');
                $quizParticipationLabels[] = $session->class_code;
                $quizParticipationData[] = round(min(100, ($participants / $enrolled) * 100), 1);
            }
        }

        if ($taskIds->isNotEmpty() && $enrolled > 0) {
            $expected = $enrolled * $taskIds->count();
            $submitted = Submission::whereIn('task_id', $taskIds)->count();
            $taskSubmissionLabels[] = $session->class_code;
            $taskSubmissionData[] = round(min(100, ($submitted / $expected) * 100), 1);
        }
    }

    return [
        'activity_breakdown' => [
            'title' => 'Student Activity Breakdown',
            'description' => 'See which activities your students engage with most across all your classes.',
            'type' => 'bar',
            'labels' => ['Navigation', 'Quizzes', 'Submissions', 'Materials', 'Violations'],
            'datasets' => [[
                'label' => 'Activity Count',
                'data' => [$navigationLogsCount, $quizLogsCount, $submissionLogsCount, $materialLogsCount, $violationLogsCount],
                'backgroundColor' => ['rgba(99, 102, 241, 0.85)', 'rgba(245, 158, 11, 0.85)', 'rgba(59, 130, 246, 0.85)', 'rgba(168, 85, 247, 0.85)', 'rgba(239, 68, 68, 0.85)'],
            ]],
            'yLabel' => 'Total Events',
        ],
        'weekly_trend' => [
            'title' => 'Weekly Activity Trend',
            'description' => 'Track daily student engagement over the past 7 days to spot drops early.',
            'type' => 'line',
            'labels' => $weekly['labels'],
            'datasets' => [[
                'label' => 'Daily Activity',
                'data' => $weekly['data'],
                'borderColor' => '#4f46e5',
                'backgroundColor' => 'rgba(99, 102, 241, 0.15)',
                'fill' => true,
                'tension' => 0.35,
            ]],
            'yLabel' => 'Events',
        ],
        'quiz_scores' => [
            'title' => 'Average Quiz Score by Class',
            'description' => 'Compare class performance as a percentage to identify sections that may need support.',
            'type' => 'bar',
            'labels' => $quizScoreLabels ?: ['No quiz data yet'],
            'datasets' => [[
                'label' => 'Average Score (%)',
                'data' => $quizScoreData ?: [0],
                'backgroundColor' => 'rgba(34, 197, 94, 0.85)',
            ]],
            'yLabel' => 'Score (%)',
            'yMax' => 100,
        ],
        'quiz_participation' => [
            'title' => 'Quiz Participation by Class',
            'description' => 'Percentage of enrolled students who have attempted at least one quiz in each class.',
            'type' => 'bar',
            'labels' => $quizParticipationLabels ?: ['No classes with quizzes'],
            'datasets' => [[
                'label' => 'Participation (%)',
                'data' => $quizParticipationData ?: [0],
                'backgroundColor' => 'rgba(245, 158, 11, 0.85)',
            ]],
            'yLabel' => 'Participation (%)',
            'yMax' => 100,
        ],
        'task_submissions' => [
            'title' => 'Task Submission Rate by Class',
            'description' => 'Shows how many expected task submissions have been received per class.',
            'type' => 'bar',
            'labels' => $taskSubmissionLabels ?: ['No tasks assigned yet'],
            'datasets' => [[
                'label' => 'Submission Rate (%)',
                'data' => $taskSubmissionData ?: [0],
                'backgroundColor' => 'rgba(59, 130, 246, 0.85)',
            ]],
            'yLabel' => 'Completion (%)',
            'yMax' => 100,
        ],
        'attendance' => [
            'title' => 'Attendance Snapshot',
            'description' => 'Current present vs absent status across all enrolled students in your classes.',
            'type' => 'doughnut',
            'labels' => ['Present', 'Absent / Inactive'],
            'datasets' => [[
                'label' => 'Students',
                'data' => [$presentCount, $absentCount],
                'backgroundColor' => ['#22c55e', '#ef4444'],
            ]],
        ],
    ];
}

private function buildAdminChartConfigs(
    $allSessions,
    int $activeClassesCount,
    int $totalStudents,
    int $totalProfessors,
    int $totalAdmins
): array {
    $totalClasses = $allSessions->count();
    $inactiveClasses = max(0, $totalClasses - $activeClassesCount);
    $weekly = $this->buildWeeklyActivitySeries([], true);

    $studentsByProgram = User::where('role', 'student')
        ->whereNotNull('program')
        ->where('program', '!=', '')
        ->selectRaw('program, COUNT(*) as total')
        ->groupBy('program')
        ->orderByDesc('total')
        ->get();

    $classesByProgram = LabSession::selectRaw('program, COUNT(*) as total')
        ->whereNotNull('program')
        ->where('program', '!=', '')
        ->groupBy('program')
        ->orderByDesc('total')
        ->get();

    $professorLoad = User::where('role', 'professor')
        ->withCount('managedSessions')
        ->orderByDesc('managed_sessions_count')
        ->get()
        ->filter(fn ($professor) => $professor->managed_sessions_count > 0);

    return [
        'classroom_status' => [
            'title' => 'Active vs Inactive Classes',
            'description' => 'Monitor how many lab sessions are currently live versus scheduled or inactive.',
            'type' => 'bar',
            'labels' => ['Active Live Sessions', 'Inactive / Scheduled'],
            'datasets' => [[
                'label' => 'Classes',
                'data' => [$activeClassesCount, $inactiveClasses],
                'backgroundColor' => ['rgba(99, 102, 241, 0.85)', 'rgba(168, 85, 247, 0.35)'],
            ]],
            'yLabel' => 'Class Count',
        ],
        'user_roles' => [
            'title' => 'User Account Distribution',
            'description' => 'Overview of students, professors, and administrators registered in the system.',
            'type' => 'doughnut',
            'labels' => ['Students', 'Professors', 'Admins'],
            'datasets' => [[
                'label' => 'Accounts',
                'data' => [$totalStudents, $totalProfessors, $totalAdmins],
                'backgroundColor' => ['#22c55e', '#3b82f6', '#ef4444'],
            ]],
        ],
        'weekly_activity' => [
            'title' => 'System Activity (Last 7 Days)',
            'description' => 'Total platform activity per day to gauge overall system usage and peak days.',
            'type' => 'line',
            'labels' => $weekly['labels'],
            'datasets' => [[
                'label' => 'Daily Events',
                'data' => $weekly['data'],
                'borderColor' => '#7c3aed',
                'backgroundColor' => 'rgba(124, 58, 237, 0.15)',
                'fill' => true,
                'tension' => 0.35,
            ]],
            'yLabel' => 'Events',
        ],
        'enrollment_program' => [
            'title' => 'Students by Program',
            'description' => 'Enrollment distribution across degree programs to support resource planning.',
            'type' => 'bar',
            'labels' => $studentsByProgram->pluck('program')->all() ?: ['No program data'],
            'datasets' => [[
                'label' => 'Students',
                'data' => $studentsByProgram->pluck('total')->all() ?: [0],
                'backgroundColor' => 'rgba(34, 197, 94, 0.85)',
            ]],
            'yLabel' => 'Student Count',
        ],
        'classes_program' => [
            'title' => 'Classes by Program',
            'description' => 'Number of lab sessions offered per program across the institution.',
            'type' => 'bar',
            'labels' => $classesByProgram->pluck('program')->all() ?: ['No program data'],
            'datasets' => [[
                'label' => 'Classes',
                'data' => $classesByProgram->pluck('total')->all() ?: [0],
                'backgroundColor' => 'rgba(59, 130, 246, 0.85)',
            ]],
            'yLabel' => 'Class Count',
        ],
        'professor_load' => [
            'title' => 'Professor Teaching Load',
            'description' => 'Number of classes assigned to each professor to balance workloads.',
            'type' => 'bar',
            'labels' => $professorLoad->map(fn ($professor) => Str::limit($professor->name, 18))->values()->all() ?: ['No assignments'],
            'datasets' => [[
                'label' => 'Classes Assigned',
                'data' => $professorLoad->pluck('managed_sessions_count')->values()->all() ?: [0],
                'backgroundColor' => 'rgba(245, 158, 11, 0.85)',
            ]],
            'yLabel' => 'Class Count',
        ],
    ];
}
}