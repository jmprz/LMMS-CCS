<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_session_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')->constrained('attendance_sessions')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->enum('status', ['pending', 'present', 'late', 'absent', 'excused'])->default('pending');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['attendance_session_id', 'user_id'], 'attendance_roster_session_student_unique');
            $table->index(['user_id', 'attendance_session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_session_students');
    }
};
