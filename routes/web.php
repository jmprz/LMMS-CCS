<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\StudentClassController;


Route::get('/', function () {
    return view('welcome');
});


Route::middleware(['auth'])->group(function () {
    // Student Dashboard (List of joined subjects)
    Route::get('/student/dashboard', [StudentClassController::class, 'index'])->name('student.dashboard');

    // Join Class Logic
    Route::post('/student/join', [StudentClassController::class, 'join'])->name('student.join');

    // Specific Subject Page (With the Attendance/Monitor button)
    Route::get('/student/subject/{id}', [StudentClassController::class, 'show'])->name('student.subject');
});


Route::middleware(['auth'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');

    Route::get('/admin/students/create', [AdminController::class, 'createStudent'])->name('admin.students.create');
    Route::post('/admin/students/store', [AdminController::class, 'storeStudent'])->name('admin.students.store');

    Route::post('/admin/generate-code', [AdminController::class, 'generateCode'])->name('admin.generate-code');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
