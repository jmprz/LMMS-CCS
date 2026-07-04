<?php

use App\Models\Task;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\StudentClassController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\RubricController;
use App\Http\Controllers\AllowedSiteController;
use App\Http\Controllers\ActivationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

// All routes inside here require the user to be logged in
Route::middleware(['auth', 'verified'])->group(function () {

    // =========================================================================
    // 1. ACCOUNT ACTIVATION ROUTES (Must be OUTSIDE the 'activated' middleware)
    // =========================================================================
    Route::prefix('activate-account')->name('activation.')->group(function () {
        Route::get('/', [ActivationController::class, 'index'])->name('index');
        Route::post('/send-otp', [ActivationController::class, 'sendOtp'])->name('send_otp');
        Route::get('/verify', [ActivationController::class, 'otpView'])->name('otp_view');
        Route::post('/verify', [ActivationController::class, 'verifyOtp'])->name('verify_otp');
        Route::get('/new-password', [ActivationController::class, 'passwordView'])->name('password_view');
        Route::post('/new-password', [ActivationController::class, 'updatePassword'])->name('update_password');
    });

   // =========================================================================
    // 2. PROTECTED SYSTEM ENVIRONMENT (Must be INSIDE the 'activated' middleware)
    // =========================================================================
    Route::middleware(['activated'])->group(function () {

        // DASHBOARD SWITCHER
        Route::get('/dashboard', function () {
            $role = auth()->user()->role;
            if ($role === 'admin')
                return redirect()->route('admin.dashboard');
            if ($role === 'professor')
                return redirect()->route('professor.dashboard');
            if ($role === 'student')
                return redirect()->route('student.dashboard');
            return redirect('/');
        })->name('dashboard');

        Route::post('student/log-behavior', [StudentClassController::class, 'logBehavior']);

    // =========================================================================
    // STUDENT ROUTES
    // =========================================================================
    Route::middleware(['student'])->prefix('student')->name('student.')->group(function () {
        Route::get('/dashboard', [StudentClassController::class, 'index'])->name('dashboard');
        Route::get('/classroom/{id}', [StudentClassController::class, 'enterClassroom'])->name('subject');
        Route::post('/join', [StudentClassController::class, 'join'])->name('join');
        Route::post('/mark-present/{labSession}', [StudentClassController::class, 'markPresent'])->name('mark-present');
        Route::post('/heartbeat/{labSession}', [StudentClassController::class, 'heartbeat'])->name('heartbeat');
        Route::post('/stop-presenting/{labSession}', [StudentClassController::class, 'stopPresenting'])->name('stop-presenting');
        Route::get('/check-session-status/{id}', [StudentClassController::class, 'checkStatus'])->name('check-session-status');

        Route::get('/classroom/{id}/live-tasks', function ($id) {
            return App\Models\Task::where('subject_id', $id)
                ->with([
                    'submissions' => function ($query) {
                        $query->where('user_id', auth()->id());
                    }
                ])
                ->latest()
                ->get()
                ->map(function ($task) {
                    $task->current_user_submission = $task->submissions->first();
                    unset($task->submissions);
                    return $task;
                });
        })->name('live-tasks');

        Route::post('/tasks/{taskId}/submit', [StudentClassController::class, 'submitTask'])->name('tasks.submit');
        Route::post('/tasks/{taskId}/delete', [StudentClassController::class, 'deleteTask'])->name('tasks.delete');

        Route::get('/classroom/{id}/allowed-sites', [AllowedSiteController::class, 'index'])->name('allowed-sites.index');
        Route::get('/classroom/{id}/browser-home', [StudentClassController::class, 'browserHome'])->name('classroom.browser-home');
        Route::get('/classroom/{id}/search', [StudentClassController::class, 'customSearch'])->name('classroom.search');

        // ✅ NEW: Student views detailed grade feedback for a task
        Route::get('/tasks/{taskId}', [TaskController::class, 'show'])->name('tasks.show');

        Route::get('/graded-tasks', [StudentClassController::class, 'getGradedTasks'])->name('graded-tasks');
        Route::get('/quizzes/{quiz}/attempt', [QuizController::class, 'attempt'])->name('quizzes.attempt');
        Route::post('/quizzes/{quiz}/submit', [QuizController::class, 'submit'])->name('quizzes.submit');
        Route::get('/refresh-statuses', [StudentClassController::class, 'refreshClassStatuses'])->name('refresh-class-statuses');
        Route::post('/browser/check-url', [App\Http\Controllers\BrowserProxyController::class, 'checkUrl'])->name('browser.check');
        Route::post('/materials/{material}/log-start', [MaterialController::class, 'logStart'])->name('materials.log-start');
        Route::post('/materials/{material}/log-end', [MaterialController::class, 'logEnd'])->name('materials.log-end');
    });

    Route::get('/student/classroom/{id}/live-quizzes', function ($id) {
        return App\Models\Quiz::where('subject_id', $id)
            ->where('published_at', '<=', now())
            ->with([
                'attempts' => function ($query) {
                    $query->where('user_id', auth()->id());
                }
            ])
            ->latest()
            ->get()
            ->map(function ($quiz) {
                $attempt = $quiz->attempts->first();
                return [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'expires_at' => $quiz->expires_at,
                    'questions_count' => $quiz->questions_count,
                    'total_points' => $quiz->total_points ?? $quiz->questions_count,
                    'has_attempt' => (bool) $attempt,
                    'user_score' => $attempt ? $attempt->score : null,
                ];
            });
    });

    Route::get('/student/classroom/{id}/live-materials', function ($id) {
        $class = \App\Models\LabSession::findOrFail($id);

        return $class->materials()
            ->latest()
            ->get()
            ->map(function ($m) {
                $url = $m->content;
                if ($m->type === 'youtube') {
                    $url = \Illuminate\Support\Str::contains($url, 'embed')
                        ? $url
                        : \Illuminate\Support\Str::replace('watch?v=', 'embed/', $url);
                } else {
                    $url = url('/' . $url);
                }

                return [
                    'id' => $m->id,
                    'title' => $m->title,
                    'type' => $m->type,
                    'url' => $url,
                ];
            });
    });


    // =========================================================================
    // PROFESSOR ROUTES
    // =========================================================================
    Route::middleware(['professor'])->prefix('professor')->name('professor.')->group(function () {
        Route::get('/classroom/{id}/tasks', [ClassroomController::class, 'getTasks'])->name('classroom.tasks');

        // Professor Dashboard
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/classroom/{id}/students-status', [ClassroomController::class, 'getStudentsStatus'])->name('professor.classroom.students-status');

        // Classroom Routes
        Route::get('/classroom', [ClassroomController::class, 'index'])->name('classroom');
        Route::get('/classroom/{id}', [ClassroomController::class, 'show'])->name('classroom.show');
        Route::get('/status-check', [AdminController::class, 'getActiveStatus'])->name('status-check');


        // Fetch student activity logs for the modal
        Route::get('/students/{userId}/activity-logs/{classId}', [ClassroomController::class, 'getStudentLogs']);
        Route::get('/students/{userId}/files/{classId}', [ClassroomController::class, 'getStudentFilesForClass']);

        Route::get('/classroom/{classId}/activity-logs', [ClassroomController::class, 'getClassActivityLogs'])
     ->name('classroom.activity-logs');
        // Live task creation from classroom
        Route::post('/classroom/{id}/live-tasks', function (\Illuminate\Http\Request $request, $id) {
            $request->validate([
                'title' => 'required|string',
                'description' => 'required|string',
                'deadline' => 'required|date',
                'points' => 'required|integer|min:1',
            ]);

            $task = \App\Models\Task::create([
                'subject_id' => $id,
                'title' => $request->title,
                'description' => $request->description,
                'deadline' => $request->deadline,
                'points' => $request->points,
            ]);

            return response()->json(['success' => true, 'task' => $task]);
        });

        // Professor-Specific Academic Actions
        Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::put('/tasks/{id}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('/tasks/{id}', [TaskController::class, 'destroy'])->name('tasks.destroy');


        Route::post('/grade/{id}', [AdminController::class, 'gradeSubmission'])->name('grade');
    
        Route::post('/classroom/{id}/materials', [MaterialController::class, 'store'])->name('materials.store');

        // Rubric Management Routes
        Route::get('/tasks/{taskId}/rubric', [RubricController::class, 'show'])->name('tasks.rubric.show');
        Route::get('/tasks/{taskId}/rubric/create', [RubricController::class, 'create'])->name('tasks.rubric.create');
        Route::post('/tasks/{taskId}/rubric', [RubricController::class, 'store'])->name('tasks.rubric.store');
        Route::delete('/tasks/{taskId}/rubric', [RubricController::class, 'destroy'])->name('tasks.rubric.destroy');

        // ✅ NEW: Re-grade a specific submission
        Route::post('/submissions/{submissionId}/regrade', [RubricController::class, 'regrade'])->name('submissions.regrade');

        // Quiz Routes
        Route::get('/quizzes/create', [QuizController::class, 'create'])->name('quizzes.create');
        Route::post('/quizzes', [QuizController::class, 'store'])->name('quizzes.store');
        Route::get('/quizzes/{quiz}/edit', [QuizController::class, 'edit'])->name('quizzes.edit');
        Route::put('/quizzes/{quiz}', [QuizController::class, 'update'])->name('quizzes.update');
        Route::delete('/quizzes/{quiz}', [QuizController::class, 'destroy'])->name('quizzes.destroy');
        Route::get('/quizzes/{quiz}/export-scores', [QuizController::class, 'exportScores'])->name('quizzes.export-scores');

        // Material Routes
        Route::get('/materials/{id}/viewers', [MaterialController::class, 'getViewers'])->name('materials.viewers');
        Route::put('/materials/{id}', [MaterialController::class, 'update'])->name('materials.update');
        Route::delete('/materials/{id}', [MaterialController::class, 'destroy'])->name('materials.destroy');

        // Session Controls
        Route::post('/sessions/{id}/toggle', [ClassroomController::class, 'toggleSession'])->name('sessions.toggle');
        Route::post('/sessions/{id}/broadcast', [ClassroomController::class, 'broadcast'])->name('sessions.broadcast');
        Route::post('/sessions/{id}/end', [AdminController::class, 'endSession'])->name('sessions.end');

        // Allowed Sites
        Route::get('/classroom/{id}/allowed-sites', [App\Http\Controllers\AllowedSiteController::class, 'index'])->name('allowed-sites.index');
        Route::post('/allowed-sites', [App\Http\Controllers\AllowedSiteController::class, 'store'])->name('allowed-sites.store');
        Route::delete('/allowed-sites/{id}', [App\Http\Controllers\AllowedSiteController::class, 'destroy'])->name('allowed-sites.destroy');
        Route::get('/classroom/{id}/blocked-attempts', [App\Http\Controllers\AllowedSiteController::class, 'getBlockedAttempts'])->name('blocked-attempts.index');
        Route::get('/classroom/{id}/blocked-stats', [App\Http\Controllers\AllowedSiteController::class, 'getBlockedStats'])->name('blocked-attempts.stats');
        Route::put('/classroom/{id}/violation-settings', [ClassroomController::class, 'updateViolationSettings'])->name('classroom.violation-settings');
        Route::post('/classroom/{classId}/students/{studentId}/unblock', [ClassroomController::class, 'unblockStudent'])->name('classroom.students.unblock');

        Route::get('/classroom/{id}/active-students', function ($id) {
            $session = \App\Models\LabSession::findOrFail($id);
            $activeStudents = $session->students()
                ->wherePivot('is_present', true)
                ->wherePivot('updated_at', '>=', now()->subMinutes(2))
                ->pluck('user_id')
                ->toArray();

            return response()->json(['activeStudents' => $activeStudents]);
        })->name('classroom.active-students');

        Route::get('/classroom/{class}/students', [ClassroomController::class, 'getStudents'])->name('classroom.students');
    });

    // =========================================================================
    // ADMIN ROUTES
    // =========================================================================
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/classroom', [ClassroomController::class, 'index'])->name('classroom');
        Route::get('/classroom/{id}', [ClassroomController::class, 'show'])->name('classroom.show');
        Route::put('/classroom/{classroom}/edit', [ClassroomController::class, 'update'])->name('classroom.edit');
        Route::delete('/classroom/{classroom}', [ClassroomController::class, 'destroy'])->name('classroom.destroy');

        Route::get('/students/create', [AdminController::class, 'createStudent'])->name('students.create');
        Route::post('/students/store', [AdminController::class, 'storeStudent'])->name('students.store');
        Route::post('/generate-code', [AdminController::class, 'generateCode'])->name('generate-code');
        Route::post('/classroom/{id}/enroll', [ClassroomController::class, 'enroll'])->name('classroom.enroll');
        Route::delete('/classroom/{session_id}/unenroll/{student_id}', [ClassroomController::class, 'unenroll'])->name('classroom.unenroll');

        Route::get('/users', [AdminController::class, 'userIndex'])->name('users.index');
        Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');
        Route::get('/users/{user}/activity-logs', [AdminController::class, 'getUserLogs']);
        Route::get('/users/{user}/drive-files', [App\Http\Controllers\ClassroomController::class, 'getStudentDriveFiles'])
            ->name('users.drive-files');

    });

    // =========================================================================
    // PROFILE ROUTES
    // =========================================================================
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

});

require __DIR__ . '/auth.php';