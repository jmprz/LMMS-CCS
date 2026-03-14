<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\StudentClassController;
use Illuminate\Support\Facades\Route;

// 1. GUEST ROUTES: Login page (accessible to non-authenticated users)
Route::get('/', function () {
    return view('auth.login');
});

// 2. AUTHENTICATED ROUTES: Everything else must be here
Route::middleware(['auth'])->group(function () {

    // Global Dashboard Router: Determines where the user should go based on role
    Route::get('/dashboard', function () {
        return auth()->user()->role === 'admin' 
            ? redirect()->route('admin.dashboard') 
            : redirect()->route('student.dashboard');
    })->name('dashboard');

    // --- STUDENT ROUTES ---
    Route::get('/student/dashboard', [StudentClassController::class, 'index'])->name('student.dashboard');
    Route::post('/student/join', [StudentClassController::class, 'join'])->name('student.join');
    Route::get('/student/subject/{id}', [StudentClassController::class, 'show'])->name('student.subject');
    
    // Marked routes as POST as they were causing 405 errors
    Route::post('/student/mark-present/{labSession}', [StudentClassController::class, 'markPresent'])->name('student.mark-present');
    Route::post('/student/heartbeat/{labSession}', [StudentClassController::class, 'heartbeat'])->name('student.heartbeat');
    Route::post('/student/stop-presenting', [StudentClassController::class, 'stopPresenting'])->name('student.stop-presenting');

    // --- ADMIN ROUTES ---
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/students/create', [AdminController::class, 'createStudent'])->name('admin.students.create');
    Route::post('/admin/students/store', [AdminController::class, 'storeStudent'])->name('admin.students.store');
    Route::post('/admin/generate-code', [AdminController::class, 'generateCode'])->name('admin.generate-code');
    Route::post('/admin/sessions/{session}/end', [AdminController::class, 'endSession'])->name('admin.sessions.end');
    Route::get('/admin/status-check', [AdminController::class, 'getActiveStatus'])->name('admin.status-check');

    // --- PROFILE ROUTES ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';