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
    Schema::table('quiz_attempts', function (Blueprint $table) {
        // This will store the time in total seconds
        $table->integer('time_spent')->nullable()->after('score');
    });
}
    /**
     * Reverse the migrations.
     */
  public function down()
{
    Schema::table('quiz_attempts', function (Blueprint $table) {
        $table->dropColumn('time_spent');
    });
}
};
