<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('research_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('research_experiment_id')->constrained('research_experiments')->cascadeOnDelete();
            $table->foreignId('lab_session_id')->constrained('lab_sessions')->cascadeOnDelete();
            $table->string('group_type', 24); // experimental, control
            $table->boolean('recommendations_enabled')->default(false);
            $table->timestamps();
            $table->unique(['research_experiment_id', 'lab_session_id'], 'research_group_class_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_groups');
    }
};
