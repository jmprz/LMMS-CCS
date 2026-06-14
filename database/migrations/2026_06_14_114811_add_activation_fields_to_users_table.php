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
        Schema::table('users', function (Blueprint $table) {
            // Adds tracking markers right after the role field
            $table->boolean('is_activated')->default(false)->after('role');
            $table->string('otp_code')->nullable()->after('is_activated');
            $table->dateTime('otp_expires_at')->nullable()->after('otp_code');
            $table->string('temp_email')->nullable()->after('otp_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_activated', 'otp_code', 'otp_expires_at', 'temp_email']);
        });
    }
};
