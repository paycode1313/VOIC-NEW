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
        Schema::create('practice_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('ai_role_id')->nullable()->constrained('ai_roles')->nullOnDelete();
            $table->string('scenario_type');
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->decimal('face_score', 5, 2)->default(0.00);
            $table->decimal('voice_score', 5, 2)->default(0.00);
            $table->decimal('overall_score', 5, 2)->default(0.00);
            $table->text('ai_conclusion')->nullable();
            $table->json('feedback_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_sessions');
    }
};
