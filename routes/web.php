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

    // 1. DASHBOARD SWITCHER
    Route::get('/dashboard', function () {
        $role = auth()->user()->role;
        if ($role === 'admin') return redirect()->route('admin.dashboard');
        if ($role === 'professor') return redirect()->route('professor.dashboard');
        if ($role === 'student') return redirect()->route('student.dashboard');
        return redirect('/');
    })->name('dashboard');

    // 2. STUDENT ROUTES
    Route::middleware(['student'])->prefix('student')->name('student.')->group(function () {
        Route::get('/dashboard', [StudentClassController::class, 'index'])->name('dashboard');
        Route::get('/classroom/{id}', [StudentClassController::class, 'show'])->name('subject');
        Route::post('/join', [StudentClassController::class, 'join'])->name('join');
        Route::get('/check-session-status/{id}', [StudentClassController::class, 'checkStatus'])->name('check-session-status');
        Route::post('/tasks/{task}/submit', [StudentClassController::class, 'submitTask'])->name('tasks.submit');
        Route::get('/quizzes/{quiz}/attempt', [QuizController::class, 'attempt'])->name('quizzes.attempt');
        Route::post('/quizzes/{quiz}/submit', [QuizController::class, 'submit'])->name('quizzes.submit');
        Route::post('/mark-present/{labSession}', [StudentClassController::class, 'markPresent'])->name('mark-present');
        Route::post('/heartbeat/{labSession}', [StudentClassController::class, 'heartbeat'])->name('heartbeat');
    });

    // 3. PROFESSOR ROUTES (Points to AdminController)
    Route::middleware(['professor'])->prefix('professor')->name('professor.')->group(function () {
        // Professor Dashboard
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        
        // Use the same show/monitor logic
        Route::get('/classroom', [ClassroomController::class, 'index'])->name('classroom');
        Route::get('/classroom/{id}', [ClassroomController::class, 'show'])->name('classroom.show');
          Route::get('/status-check', [AdminController::class, 'getActiveStatus'])->name('status-check');
        // Professor-Specific Academic Actions
        Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::post('/grade/{id}', [AdminController::class, 'gradeSubmission'])->name('grade');
        Route::post('/classroom/{id}/materials', [MaterialController::class, 'store'])->name('materials.store');
        
        // Session Controls
        Route::post('/sessions/{session}/toggle', [AdminController::class, 'toggle'])->name('sessions.toggle'); 
        Route::post('/sessions/{session}/end', [AdminController::class, 'endSession'])->name('sessions.end');

        // Quiz Management
        Route::get('/quizzes/create', [QuizController::class, 'create'])->name('quizzes.create');
        Route::post('/quizzes', [QuizController::class, 'store'])->name('quizzes.store');
    });

    // 4. ADMIN ROUTES (Points to AdminController)
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/classroom', [ClassroomController::class, 'index'])->name('classroom');
        Route::get('/classroom/{id}', [ClassroomController::class, 'show'])->name('classroom.show');
        
        // Admin-Specific User Management
        Route::get('/students/create', [AdminController::class, 'createStudent'])->name('students.create');
        Route::post('/students/store', [AdminController::class, 'storeStudent'])->name('students.store');
        Route::post('/generate-code', [AdminController::class, 'generateCode'])->name('generate-code');
    });

    // 4. PROFILE ROUTES
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';