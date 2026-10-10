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
            $table->boolean('has_completed_onboarding')->default(false)->after('password');
            $table->string('onboarding_goal', 50)->nullable()->after('has_completed_onboarding');
            $table->string('onboarding_experience', 50)->nullable()->after('onboarding_goal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['has_completed_onboarding', 'onboarding_goal', 'onboarding_experience']);
        });
    }
};
