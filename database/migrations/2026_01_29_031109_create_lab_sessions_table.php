<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::create('lab_sessions', function (Blueprint $table) {
        $table->id();
        $table->string('class_code')->unique();
        $table->string('subject_name');
        $table->string('schedule_day')->nullable();   // Added
        $table->string('schedule_time')->nullable();  // Added
        $table->foreignId('faculty_id')->constrained('users')->onDelete('cascade');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_sessions');
    }
};
