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
        Schema::create('submissions', function (Blueprint $col) {
            $col->id();
            $col->foreignId('task_id')->constrained()->onDelete('cascade');
            $col->foreignId('user_id')->constrained()->onDelete('cascade');
            $col->string('file_path');
            $col->string('original_filename');
            $col->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
