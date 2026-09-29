<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recommendation_outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('research_experiment_id')->constrained('research_experiments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('learning_topic_id')->nullable()->constrained('learning_topics')->nullOnDelete();
            $table->foreignId('pre_quiz_attempt_id')->nullable()->constrained('quiz_attempts')->nullOnDelete();
            $table->foreignId('post_quiz_attempt_id')->nullable()->constrained('quiz_attempts')->nullOnDelete();
            $table->decimal('pre_score_percentage', 5, 2)->nullable();
            $table->decimal('post_score_percentage', 5, 2)->nullable();
            $table->timestamp('assessed_at')->nullable();
            $table->timestamps();
            $table->unique(['research_experiment_id', 'user_id', 'learning_topic_id'], 'recommendation_outcome_topic_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_outcomes');
    }
};
