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
    Schema::table('submissions', function (Blueprint $table) {
        // How many seconds elapsed from opening the task to clicking "Submit"
        $table->integer('duration_seconds')->nullable(); 
        
        // Detailed timestamp for speed analysis
        $table->timestamp('submitted_at')->nullable(); 
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            //
        });
    }
};
