<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up()
{
    // 1. Force delete the table if it already exists
    Schema::dropIfExists('material_logs');

    // 2. Create the new, correct structure
    Schema::create('material_logs', function (Blueprint $table) {
        $table->id();
        // Use cascadeOnDelete so logs are cleaned up if a user or material is removed
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('material_id')->constrained()->cascadeOnDelete();
        
        $table->timestamp('opened_at');
        $table->timestamp('closed_at')->nullable();
        $table->integer('duration_seconds')->nullable(); 
        
        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('material_logs');
}
};
