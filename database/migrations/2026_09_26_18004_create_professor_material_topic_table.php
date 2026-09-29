<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('professor_material_topic', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->foreignId('learning_topic_id')->constrained('learning_topics')->cascadeOnDelete();
            $table->foreignId('tagged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['material_id', 'learning_topic_id'], 'prof_material_topic_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professor_material_topic');
    }
};
