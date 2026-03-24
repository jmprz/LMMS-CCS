<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('quizzes', function (Blueprint $table) {
        $table->id();
       $table->foreignId('subject_id')->constrained('lab_sessions')->onDelete('cascade');
        $table->string('title');
        $table->integer('time_limit')->default(30); // in minutes
        $table->timestamps();
    });

    Schema::create('questions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('quiz_id')->constrained()->onDelete('cascade');
        $table->text('question_text');
        $table->integer('points')->default(1);
        $table->timestamps();
    });

    Schema::create('options', function (Blueprint $table) {
        $table->id();
        $table->foreignId('question_id')->constrained()->onDelete('cascade');
        $table->string('option_text');
        $table->boolean('is_correct')->default(false);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_tables');
    }
};
