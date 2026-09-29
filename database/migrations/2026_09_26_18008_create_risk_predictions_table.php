<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('risk_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_feature_snapshot_id')->constrained('student_feature_snapshots')->cascadeOnDelete();
            $table->string('model_name', 64);
            $table->string('model_version', 64);
            $table->string('risk_level', 32);
            $table->decimal('risk_probability', 6, 5)->nullable();
            $table->timestamp('predicted_at');
            $table->json('explanation')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_predictions');
    }
};
