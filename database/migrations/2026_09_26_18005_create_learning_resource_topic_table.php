<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('learning_resource_topic', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_resource_id')->constrained('learning_resources')->cascadeOnDelete();
            $table->foreignId('learning_topic_id')->constrained('learning_topics')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['learning_resource_id', 'learning_topic_id'], 'library_resource_topic_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_resource_topic');
    }
};
