<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    Schema::table('quiz_attempts', function (Blueprint $table) {
        $table->integer('total_points')->default(0)->after('total_questions');
    });

    Schema::table('quiz_attempt_details', function (Blueprint $table) {
        $table->integer('points_earned')->default(0)->after('is_correct');
        $table->integer('points_possible')->default(0)->after('points_earned');
    });
}

public function down()
{
    Schema::table('quiz_attempts', function (Blueprint $table) {
        $table->dropColumn('total_points');
    });
    Schema::table('quiz_attempt_details', function (Blueprint $table) {
        $table->dropColumn(['points_earned', 'points_possible']);
    });
}
};