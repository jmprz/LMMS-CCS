<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('research_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('research_group_id')->constrained('research_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('consent_status', 24)->default('pending'); // pending, consented, declined, withdrawn
            $table->timestamp('consented_at')->nullable();
            $table->timestamps();
            $table->unique(['research_group_id', 'user_id'], 'research_participant_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_participants');
    }
};
