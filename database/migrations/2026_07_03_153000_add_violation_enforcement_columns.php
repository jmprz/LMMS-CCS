<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_sessions', function (Blueprint $table) {
            $table->unsignedTinyInteger('violation_warning_threshold')
                ->default(3)
                ->after('is_active');
        });

        Schema::table('class_student', function (Blueprint $table) {
            $table->unsignedTinyInteger('violation_count')->default(0)->after('is_present');
            $table->boolean('is_screen_blocked')->default(false)->after('violation_count');
            $table->timestamp('screen_blocked_at')->nullable()->after('is_screen_blocked');
        });
    }

    public function down(): void
    {
        Schema::table('lab_sessions', function (Blueprint $table) {
            $table->dropColumn('violation_warning_threshold');
        });

        Schema::table('class_student', function (Blueprint $table) {
            $table->dropColumn(['violation_count', 'is_screen_blocked', 'screen_blocked_at']);
        });
    }
};
