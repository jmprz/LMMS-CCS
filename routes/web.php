<?php

use App\Models\Task;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\StudentClassController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\MaterialController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Route::middleware(['auth', 'verified'])->group(function () {

    // 1. DASHBOARD SWITCHER
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

    // 2. STUDENT ROUTES
    Route::middleware(['student'])->prefix('student')->name('student.')->group(function () {
        Route::get('/dashboard', [StudentClassController::class, 'index'])->name('dashboard');
        Route::get('/classroom/{id}', [StudentClassController::class, 'enterClassroom'])->name('subject');
        Route::post('/join', [StudentClassController::class, 'join'])->name('join');
        Route::post('/mark-present/{labSession}', [StudentClassController::class, 'markPresent'])->name('mark-present');
        Route::post('/heartbeat/{labSession}', [StudentClassController::class, 'heartbeat'])->name('heartbeat');
        Route::post('/stop-presenting/{labSession}', [StudentClassController::class, 'stopPresenting'])->name('stop-presenting');
        Route::get('/check-session-status/{id}', [StudentClassController::class, 'checkStatus'])->name('check-session-status');
        // 🟢 FIXED: Removed duplicate and matched 'user_id' to your controller
        Route::get('/classroom/{id}/live-tasks', function ($id) {
            return App\Models\Task::where('subject_id', $id)
                ->with(['submissions' => function($query) {
                    $query->where('user_id', auth()->id()); // Matches your controller!
                }])
                ->latest()
                ->get()
                ->map(function($task) {
                    $task->current_user_submission = $task->submissions->first();
                    unset($task->submissions); 
                    return $task;
                });
        })->name('live-tasks');
        Route::post('/tasks/{taskId}/submit', [StudentClassController::class, 'submitTask'])->name('tasks.submit');
        Route::post('/tasks/{taskId}/delete', [StudentClassController::class, 'deleteTask'])->name('tasks.delete');
        Route::get('/graded-tasks', [StudentClassController::class, 'getGradedTasks'])->name('graded-tasks');
        Route::get('/quizzes/{quiz}/attempt', [QuizController::class, 'attempt'])->name('quizzes.attempt');
        Route::post('/quizzes/{quiz}/submit', [QuizController::class, 'submit'])->name('quizzes.submit');
        Route::get('/refresh-statuses', [StudentClassController::class, 'refreshClassStatuses'])->name('refresh-class-statuses');
    });

    // 3. PROFESSOR ROUTES
Route::middleware(['professor'])->prefix('professor')->name('professor.')->group(function () {
    
    // Professor Dashboard
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');

    // Classroom Routes
    Route::get('/classroom', [ClassroomController::class, 'index'])->name('classroom');
    Route::get('/classroom/{id}', [ClassroomController::class, 'show'])->name('classroom.show');
    Route::get('/status-check', [AdminController::class, 'getActiveStatus'])->name('status-check');
    
    // Catch the POST request from the "Give Task" modal
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
    Route::post('/grade/{id}', [AdminController::class, 'gradeSubmission'])->name('grade');
    Route::post('/classroom/{id}/materials', [MaterialController::class, 'store'])->name('materials.store');

    // ADD THESE QUIZ ROUTES ⬇️
    Route::get('/quizzes/create', [QuizController::class, 'create'])->name('quizzes.create');
    Route::post('/quizzes', [QuizController::class, 'store'])->name('quizzes.store');
    Route::get('/quizzes/{quiz}/edit', [QuizController::class, 'edit'])->name('quizzes.edit');
    Route::put('/quizzes/{quiz}', [QuizController::class, 'update'])->name('quizzes.update');
    Route::delete('/quizzes/{quiz}', [QuizController::class, 'destroy'])->name('quizzes.destroy');

    // Session Controls
    Route::post('/sessions/{id}/toggle', [ClassroomController::class, 'toggleSession'])->name('sessions.toggle');
    Route::post('/sessions/{id}/broadcast', [ClassroomController::class, 'broadcast'])->name('sessions.broadcast');
    Route::post('/sessions/{id}/end', [AdminController::class, 'endSession'])->name('sessions.end');
});

    // 4. ADMIN ROUTES
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/classroom', [ClassroomController::class, 'index'])->name('classroom');
        Route::get('/classroom/{id}', [ClassroomController::class, 'show'])->name('classroom.show');
        Route::put('/classroom/{classroom}/edit', [ClassroomController::class, 'update'])->name('classroom.edit');
        Route::delete('/classroom/{classroom}', [ClassroomController::class, 'destroy'])->name('classroom.destroy');
        
        Route::get('/students/create', [AdminController::class, 'createStudent'])->name('students.create');
        Route::post('/students/store', [AdminController::class, 'storeStudent'])->name('students.store');
        Route::post('/generate-code', [AdminController::class, 'generateCode'])->name('generate-code');
    });

    // 5. PROFILE ROUTES
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';