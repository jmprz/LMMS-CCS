<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('learning_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('lab_session_id')->constrained('lab_sessions')->cascadeOnDelete();
            $table->foreignId('quiz_attempt_id')->nullable()->constrained('quiz_attempts')->nullOnDelete();
            $table->foreignId('learning_topic_id')->constrained('learning_topics')->cascadeOnDelete();
            $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->foreignId('learning_resource_id')->nullable()->constrained('learning_resources')->nullOnDelete();
            $table->foreignId('risk_prediction_id')->nullable()->constrained('risk_predictions')->nullOnDelete();
            $table->foreignId('research_experiment_id')->nullable()->constrained('research_experiments')->nullOnDelete();
            $table->decimal('trigger_quiz_percentage', 5, 2)->nullable();
            $table->string('status', 24)->default('recommended');
            $table->timestamp('recommended_at');
            $table->timestamp('displayed_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'lab_session_id', 'recommended_at'], 'recommendation_student_subject_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_recommendations');
    }
};
