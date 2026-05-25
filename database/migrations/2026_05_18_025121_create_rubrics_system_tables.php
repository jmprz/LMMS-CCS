<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Main rubrics table
        Schema::create('rubrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('total_points');
            $table->boolean('auto_grade_enabled')->default(true);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // Rubric criteria (individual grading points)
        Schema::create('rubric_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubric_id')->constrained('rubrics')->onDelete('cascade');
            $table->string('criterion_name');
            $table->text('description')->nullable();
            $table->integer('max_points');
            $table->enum('checking_type', ['code', 'text', 'file', 'keyword', 'ai', 'manual'])->default('manual');
            $table->json('checking_rules')->nullable();
            $table->decimal('weight', 5, 2)->default(1.0);
            $table->integer('order_index')->default(0);
            $table->timestamps();
            
            $table->index(['rubric_id', 'order_index']);
        });

        // Auto-grading results
        Schema::create('submission_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('submissions')->onDelete('cascade');
            $table->foreignId('rubric_id')->constrained('rubrics')->onDelete('cascade');
            $table->decimal('total_score', 8, 2);
            $table->integer('max_score');
            $table->boolean('auto_graded')->default(false);
            $table->json('feedback')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->unique(['submission_id', 'rubric_id']);
        });

        // Individual criterion scores
        Schema::create('criterion_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_grade_id')->constrained('submission_grades')->onDelete('cascade');
            $table->foreignId('criterion_id')->constrained('rubric_criteria')->onDelete('cascade');
            $table->decimal('points_earned', 8, 2);
            $table->integer('max_points');
            $table->text('feedback')->nullable();
            $table->boolean('auto_checked')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('criterion_scores');
        Schema::dropIfExists('submission_grades');
        Schema::dropIfExists('rubric_criteria');
        Schema::dropIfExists('rubrics');
    }
};