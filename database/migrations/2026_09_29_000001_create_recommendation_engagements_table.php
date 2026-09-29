<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recommendation_engagements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_recommendation_id')
                ->constrained('learning_recommendations')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->timestamps();

            $table->index( ['user_id', 'learning_recommendation_id'], 'rec_eng_user_rec_idx');
            $table->index('opened_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_engagements');
    }
};
