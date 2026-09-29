<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subject_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('lab_session_id')->constrained('lab_sessions')->cascadeOnDelete();
            $table->string('grading_period', 32); // midterm, final
            $table->decimal('grade', 6, 2)->nullable(); // define scale before use
            $table->timestamp('recorded_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'lab_session_id', 'grading_period'], 'subject_grades_unique_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_grades');
    }
};
