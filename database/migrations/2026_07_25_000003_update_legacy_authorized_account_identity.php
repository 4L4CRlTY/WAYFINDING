<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacyAccount = DB::table('users')
            ->where('email', 'staff@gmail.com')
            ->where('role', 'authorized_user')
            ->first();

        $newEmailIsAvailable = ! DB::table('users')
            ->where('email', 'authorized@gmail.com')
            ->exists();

        if ($legacyAccount && $newEmailIsAvailable) {
            DB::table('users')
                ->where('id', $legacyAccount->id)
                ->update([
                    'username' => strtolower((string) $legacyAccount->username) === 'staff'
                        ? 'Authorized User'
                        : $legacyAccount->username,
                    'email' => 'authorized@gmail.com',
                ]);
        }
    }

    public function down(): void
    {
        $authorizedAccount = DB::table('users')
            ->where('email', 'authorized@gmail.com')
            ->where('role', 'authorized_user')
            ->first();

        $legacyEmailIsAvailable = ! DB::table('users')
            ->where('email', 'staff@gmail.com')
            ->exists();

        if ($authorizedAccount && $legacyEmailIsAvailable) {
            DB::table('users')
                ->where('id', $authorizedAccount->id)
                ->update([
                    'username' => $authorizedAccount->username === 'Authorized User'
                        ? 'staff'
                        : $authorizedAccount->username,
                    'email' => 'staff@gmail.com',
                ]);
        }
    }
};
