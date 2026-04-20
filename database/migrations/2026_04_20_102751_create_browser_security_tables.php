<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allowed_sites', function (Blueprint $table) {
            $table->id();
            $table->string('domain');
            $table->string('name');
            $table->enum('scope', ['global', 'task'])->default('global');
            $table->foreignId('task_id')->nullable()->constrained('tasks')->onDelete('cascade');
            $table->foreignId('lab_session_id')->nullable()->constrained('lab_sessions')->onDelete('cascade');
            $table->boolean('is_pre_approved')->default(false);
            $table->text('description')->nullable();
            $table->foreignId('added_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('domain');
            $table->index('scope');
            $table->index('task_id');
            $table->index('lab_session_id');
        });

        Schema::create('blocked_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('lab_session_id')->constrained('lab_sessions')->onDelete('cascade');
            $table->string('blocked_url', 500);
            $table->string('blocked_domain');
            $table->string('reason')->default('not_whitelisted');
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('attempted_at');
            
            $table->index('user_id');
            $table->index('lab_session_id');
            $table->index('attempted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_attempts');
        Schema::dropIfExists('allowed_sites');
    }
};