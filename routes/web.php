<?php

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

    // 1. Dashboard Switcher
    Route::get('/dashboard', function () {
        if (auth()->user()->role === 'admin') return redirect()->route('admin.dashboard');
        if (auth()->user()->role === 'student') return redirect()->route('student.dashboard');
        return redirect('/');
    })->name('dashboard');

   Route::post('student/log-behavior', [StudentClassController::class, 'logBehavior']);
    // 2. STUDENT ROUTES (Must be named student.x)
    Route::middleware(['auth', 'student'])->prefix('student')->name('student.')->group(function () {
        Route::get('/dashboard', [StudentClassController::class, 'index'])->name('dashboard');
        Route::get('/classroom/{id}', [StudentClassController::class, 'show'])->name('subject');
        Route::post('/mark-present/{labSession}', [StudentClassController::class, 'markPresent'])->name('mark-present');
        Route::post('/heartbeat/{labSession}', [StudentClassController::class, 'heartbeat'])->name('heartbeat');
        Route::post('/stop-presenting/{labSession}', [StudentClassController::class, 'stopPresenting'])->name('stop-presenting');
        Route::post('/join', [StudentClassController::class, 'join'])->name('join');
        Route::get('/check-session-status/{id}', [StudentClassController::class, 'checkStatus'])->name('check-session-status');
        Route::post('/tasks/{task}/submit', [StudentClassController::class, 'submitTask'])->name('tasks.submit');
        Route::post('/tasks/{taskId}/delete', [StudentClassController::class, 'deleteTask'])->name('tasks.delete');
        Route::get('/quizzes/{quiz}/attempt', [QuizController::class, 'attempt'])->name('quizzes.attempt');
        Route::post('/quizzes/{quiz}/submit', [QuizController::class, 'submit'])->name('quizzes.submit');
        });

    // 3. ADMIN ROUTES (Must be named admin.x)
    Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/classroom', [ClassroomController::class, 'index'])->name('classroom');
        Route::get('/classroom/{id}', [ClassroomController::class, 'show'])->name('classroom.show');
        Route::get('/students/create', [AdminController::class, 'createStudent'])->name('students.create');
        Route::post('/students/store', [AdminController::class, 'storeStudent'])->name('students.store');
        Route::post('/generate-code', [AdminController::class, 'generateCode'])->name('generate-code');
        Route::post('/sessions/{session}/end', [AdminController::class, 'endSession'])->name('sessions.end');
        Route::post('/sessions/{session}/toggle', [AdminController::class, 'toggle'])->name('sessions.toggle'); 
        Route::get('/status-check', [AdminController::class, 'getActiveStatus'])->name('status-check');
        Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::post('/grade/{id}', [AdminController::class, 'gradeSubmission'])->name('grade');
        Route::get('/quizzes/create', [QuizController::class, 'create'])->name('quizzes.create');
        Route::post('/quizzes', [QuizController::class, 'store'])->name('quizzes.store');
        Route::delete('/quizzes/{quiz}', [QuizController::class, 'destroy'])->name('quizzes.destroy');
        Route::post('/classroom/{id}/materials', [MaterialController::class, 'store'])->name('materials.store');
        });

    // 4. PROFILE ROUTES
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';