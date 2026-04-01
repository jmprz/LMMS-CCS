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
       Schema::table('activity_logs', function (Blueprint $table) {
        // This modifies the existing column to support 6 decimal places (microseconds)
        // If the column doesn't exist yet, use ->timestamp('created_at', 6)...
        $table->timestamp('created_at', 6)->nullable()->change();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
