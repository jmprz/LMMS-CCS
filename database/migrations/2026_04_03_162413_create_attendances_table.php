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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('lab_session_id')->constrained()->onDelete('cascade');
        $table->date('attendance_date'); // Stores only the date (YYYY-MM-DD)
        $table->time('joined_at');       // Stores the exact time they clicked "Join"
        $table->string('status')->default('present'); // present, late, etc.
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
