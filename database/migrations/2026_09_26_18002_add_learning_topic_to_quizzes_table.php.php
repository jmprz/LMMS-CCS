
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('quizzes', 'learning_topic_id')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->foreignId('learning_topic_id')
                    ->nullable()
                    ->after('topic');
            });
        }

        Schema::table('quizzes', function (Blueprint $table) {
            $table->foreign('learning_topic_id')
                ->references('id')
                ->on('learning_topics')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropForeign(['learning_topic_id']);
            $table->dropColumn('learning_topic_id');
        });
    }
};
