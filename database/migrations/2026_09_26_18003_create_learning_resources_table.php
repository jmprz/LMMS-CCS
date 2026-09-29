<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('learning_resources', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['pdf', 'pptx', 'youtube', 'url']);
            $table->text('content'); // uploaded file path or URL; validate in app
            $table->string('subject_key', 191); // canonical subject key, e.g. system-administration
            $table->string('program', 32)->nullable(); // BSIT, BSCS; null = shared
            $table->string('status', 24)->default('draft'); // draft, approved, archived
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['subject_key', 'status', 'program'], 'library_scope_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_resources');
    }
};
