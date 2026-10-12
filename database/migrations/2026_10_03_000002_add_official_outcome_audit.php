<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_grades', fn (Blueprint $table) => $table->string('source')->nullable());
        Schema::create('academic_outcome_imports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('lab_session_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->string('source');
            $table->string('filename');
            $table->char('sha256', 64);
            $table->json('preview');
            $table->dateTime('expires_at');
            $table->dateTime('committed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('subject_grade_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_grade_id')->constrained()->restrictOnDelete();
            $table->decimal('grade', 6, 2)->nullable();
            $table->dateTime('recorded_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('source')->nullable();
            $table->uuid('import_id')->nullable();
            $table->foreign('import_id')->references('id')->on('academic_outcome_imports')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_grade_revisions');
        Schema::dropIfExists('academic_outcome_imports');
        Schema::table('subject_grades', fn (Blueprint $table) => $table->dropColumn('source'));
    }
};
