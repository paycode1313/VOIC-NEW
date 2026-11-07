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
        Schema::table('practice_sessions', function (Blueprint $table) {
            $table->string('exam_mode')->nullable()->index()->after('scenario_type');
            $table->json('exam_state')->nullable()->after('feedback_notes');
        });

        Schema::table('session_messages', function (Blueprint $table) {
            $table->unsignedTinyInteger('exam_part')->nullable()->after('sender');
            $table->json('meta')->nullable()->after('facial_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_messages', function (Blueprint $table) {
            $table->dropColumn(['exam_part', 'meta']);
        });

        Schema::table('practice_sessions', function (Blueprint $table) {
            $table->dropIndex(['exam_mode']);
            $table->dropColumn(['exam_mode', 'exam_state']);
        });
    }
};
