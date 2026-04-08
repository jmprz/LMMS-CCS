<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        // 1. Drop the old column if updating an existing DB
        if (Schema::hasColumn('users', 'name')) {
            $table->dropColumn('name');
        }

        // 2. Name Fields
        $table->string('first_name')->after('id');
        $table->string('middle_name')->nullable()->after('first_name');
        $table->string('last_name')->after('middle_name');
        $table->string('name')->after('last_name'); // Full name combined

        // 3. School ID & Role
        $table->string('school_id')->unique()->after('email');
        $table->string('role')->default('student')->after('school_id');

        // 4. Academic Info (Grouped & Ordered: Program -> Year -> Section)
        $table->string('program')->nullable()->after('role');
        $table->integer('year_level')->nullable()->after('program');
        $table->string('section')->nullable()->after('year_level');
    });
}

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'middle_name', 'last_name', 'name', 'school_id', 'role', 'year_level', 'section']);
            $table->string('name')->after('id');
        });
    }
};