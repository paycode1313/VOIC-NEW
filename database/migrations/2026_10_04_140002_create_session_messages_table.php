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
        Schema::create('session_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_session_id')->constrained('practice_sessions')->cascadeOnDelete();
            $table->string('sender'); // 'user', 'ai', 'system'
            $table->text('message');
            $table->string('audio_url')->nullable();
            $table->json('facial_status')->nullable();
            $table->unsignedInteger('timestamp_seconds')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_messages');
    }
};
