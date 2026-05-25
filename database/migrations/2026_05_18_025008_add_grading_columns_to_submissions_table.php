<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('submissions', 'grade')) {
                $table->integer('grade')->nullable()->after('submitted_at');
            }
            
            if (!Schema::hasColumn('submissions', 'feedback')) {
                $table->text('feedback')->nullable()->after('grade');
            }
            
            if (!Schema::hasColumn('submissions', 'auto_graded')) {
                $table->boolean('auto_graded')->default(false)->after('feedback');
            }
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            if (Schema::hasColumn('submissions', 'grade')) {
                $table->dropColumn('grade');
            }
            if (Schema::hasColumn('submissions', 'feedback')) {
                $table->dropColumn('feedback');
            }
            if (Schema::hasColumn('submissions', 'auto_graded')) {
                $table->dropColumn('auto_graded');
            }
        });
    }
};