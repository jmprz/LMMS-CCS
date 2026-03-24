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
    Schema::create('curriculum', function (Blueprint $table) {
        $table->id();
        $table->string('subject_code'); // e.g., CS211
        $table->string('subject_title');
        $table->integer('year_level');
        $table->integer('semester');
        // Prerequisite links to the ID of another subject in this same table
        $table->unsignedBigInteger('prerequisite_id')->nullable(); 
        $table->text('syllabus_topics')->nullable(); // JSON list of topics
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum');
    }
};
