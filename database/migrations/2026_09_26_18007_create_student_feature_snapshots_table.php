<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_feature_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('lab_session_id')->constrained('lab_sessions')->cascadeOnDelete();
            $table->timestamp('feature_cutoff_at');
            $table->string('feature_version', 32)->default('v1');
            $table->decimal('quiz_avg', 5, 2)->nullable();
            $table->unsignedInteger('quiz_attempt_count')->default(0);
            $table->decimal('reading_avg_seconds', 12, 2)->nullable();
            $table->unsignedBigInteger('reading_total_seconds')->default(0);
            $table->unsignedInteger('material_session_count')->default(0);
            $table->unsignedInteger('materials_accessed')->default(0);
            $table->decimal('attendance_rate', 5, 2)->nullable();
            $table->unsignedInteger('eligible_session_count')->default(0);
            $table->decimal('task_avg', 5, 2)->nullable();
            $table->decimal('submission_rate', 5, 2)->nullable();
            $table->unsignedInteger('assigned_task_count')->default(0);
            $table->json('additional_features')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'lab_session_id', 'feature_cutoff_at', 'feature_version'], 'feature_snapshot_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_feature_snapshots');
    }
};
