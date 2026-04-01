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
    Schema::create('activity_logs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('log_type'); // e.g., 'violation' or 'search'
        $table->text('content');    // e.g., 'Alt+Tab Attempt' or 'Google: How to cheat'
        
        // If you want to link it to a specific class session
        $table->foreignId('lab_session_id')->nullable()->constrained()->onDelete('set null');
        
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
