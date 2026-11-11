<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * User-trial data: anonymous (participant code only, no name, no transcript, no audio),
     * so it survives the automatic deletion of guest sessions.
     */
    public function up(): void
    {
        Schema::create('study_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->index();
            $table->foreignId('practice_session_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('mode', 20);
            $table->string('language', 2);
            $table->decimal('overall_band', 3, 1);
            $table->json('criteria');
            $table->unsignedSmallInteger('answers');
            $table->unsignedInteger('speaking_seconds');
            $table->decimal('fillers_per_minute', 5, 2)->nullable();
            $table->unsignedSmallInteger('average_wpm')->nullable();
            $table->decimal('eye_contact', 5, 1)->nullable();
            $table->timestamps();
        });

        Schema::create('study_surveys', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('practice_session_id')->nullable()->constrained()->nullOnDelete();
            $table->json('answers');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_surveys');
        Schema::dropIfExists('study_attempts');
    }
};
