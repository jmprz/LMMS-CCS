<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Update existing student_activities
Schema::table('student_activities', function (Blueprint $table) {
        $table->integer('duration_seconds')->default(0);
        $table->boolean('is_completed')->default(false);
    });

        // 2. Create assessment_results for performance tracking
        Schema::create('assessment_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->decimal('score', 5, 2);
            $table->decimal('answer_accuracy', 5, 2);
            $table->integer('response_time_ms');
            $table->timestamps();
        });

        // 3. Update class_student for precise attendance
       Schema::table('class_student', function (Blueprint $table) {
        $table->timestamp('check_in_time')->nullable();
        $table->boolean('is_late')->default(false);
    });
    }

    public function down(): void
    {
        // Reverse operations in opposite order
        Schema::table('class_student', function (Blueprint $table) {
            $table->dropColumn(['check_in_time', 'is_late']);
        });

        Schema::dropIfExists('assessment_results');

        Schema::table('student_activities', function (Blueprint $table) {
            $table->dropColumn(['duration_seconds', 'is_completed']);
        });
    }
};