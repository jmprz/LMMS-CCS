<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_logs', function (Blueprint $table) {
            $table->uuid('client_session_key')->nullable();
            $table->string('collection_version', 24)->nullable();
            $table->string('measurement_mode', 24)->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->unsignedInteger('last_sequence')->nullable();
            $table->unsignedInteger('reported_active_seconds')->nullable();
            $table->unique(['user_id', 'client_session_key'], 'material_logs_user_client_session_unique');
        });
    }

    public function down(): void
    {
        Schema::table('material_logs', function (Blueprint $table) {
            $table->dropUnique('material_logs_user_client_session_unique');
            $table->dropColumn(['client_session_key', 'collection_version', 'measurement_mode',
                'last_heartbeat_at', 'last_sequence', 'reported_active_seconds']);
        });
    }
};
