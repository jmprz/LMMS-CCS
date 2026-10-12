<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_session_id')->constrained('lab_sessions')->restrictOnDelete();
            $table->date('session_date');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->enum('status', ['active', 'finalized', 'cancelled'])->default('active');
            $table->unsignedSmallInteger('late_after_minutes')->default(10);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['lab_session_id', 'status', 'started_at'], 'attendance_sessions_class_status_start');
            $table->index(['status', 'finalized_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
