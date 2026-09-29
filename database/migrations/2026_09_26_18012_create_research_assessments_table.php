<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('research_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('research_experiment_id')->constrained('research_experiments')->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->string('assessment_type', 24); // pretest, posttest
            $table->string('assessment_version', 32)->nullable();
            $table->timestamps();
            $table->unique(['research_experiment_id', 'quiz_id'], 'research_assessment_quiz_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_assessments');
    }
};
