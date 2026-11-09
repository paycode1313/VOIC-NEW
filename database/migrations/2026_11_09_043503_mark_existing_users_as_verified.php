<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Email verification is now required. Accounts created before this change
     * never received a verification email, so they are marked as verified to
     * avoid locking existing users out of their dashboard.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    /**
     * Nothing to undo: the original verification state is not recorded.
     */
    public function down(): void
    {
        //
    }
};
