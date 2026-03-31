<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       // CreateMaterialsTable
Schema::create('materials', function (Blueprint $table) {
    $table->id();
    $table->foreignId('lab_session_id')->constrained()->onDelete('cascade');
    $table->string('title');
    $table->enum('type', ['pdf', 'pptx', 'youtube']);
    $table->text('content'); // URL for YouTube, or File Path for PDF/PPTX
    $table->timestamps();
});

// CreateMaterialLogsTable (The Tracker)
Schema::create('material_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('material_id')->constrained()->onDelete('cascade');
    $table->integer('seconds_spent')->default(0);
    $table->timestamp('last_viewed_at')->useCurrent();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
