<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_duration_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('lab_session_id')->constrained()->restrictOnDelete();
            $table->uuid('client_session_key');
            $table->string('collection_version');
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->dateTime('last_heartbeat_at');
            $table->unsignedInteger('last_sequence')->default(0);
            $table->unsignedInteger('reported_active_seconds')->default(0);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'client_session_key'], 'activity_duration_user_key_unique');
            $table->index(['user_id', 'lab_session_id', 'opened_at'], 'activity_duration_user_class_open_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_duration_sessions');
    }
};
