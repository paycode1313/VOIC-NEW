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
        Schema::create('ai_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role_type')->index();
            $table->string('avatar')->nullable();
            $table->text('description');
            $table->text('system_prompt');
            $table->json('personality_traits')->nullable();
            $table->string('voice_id')->nullable();
            $table->string('difficulty_level')->default('Sedang');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_roles');
    }
};
