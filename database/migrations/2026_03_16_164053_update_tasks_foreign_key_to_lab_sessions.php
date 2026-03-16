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
       Schema::table('tasks', function (Blueprint $table) {
        // Drop the old constraint
        $table->dropForeign(['subject_id']);
        
        // Add the new constraint
        $table->foreign('subject_id')
              ->references('id')
              ->on('lab_sessions')
              ->onDelete('cascade'); // Deletes tasks if the lab session is deleted
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lab_sessions', function (Blueprint $table) {
            //
        });
    }
};
