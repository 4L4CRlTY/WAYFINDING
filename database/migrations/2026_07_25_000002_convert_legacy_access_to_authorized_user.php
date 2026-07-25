<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Upgrade installations that used the former role and permission names.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE users MODIFY role ENUM('admin', 'staff', 'authorized_user', 'user') NOT NULL DEFAULT 'user'"
            );
        }

        DB::table('users')
            ->where('role', 'staff')
            ->update(['role' => 'authorized_user']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE users MODIFY role ENUM('admin', 'authorized_user', 'user') NOT NULL DEFAULT 'user'"
            );
        }

        if (
            Schema::hasColumn('users', 'staff_permissions')
            && ! Schema::hasColumn('users', 'authorized_permissions')
        ) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('staff_permissions', 'authorized_permissions');
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE users MODIFY role ENUM('admin', 'staff', 'authorized_user', 'user') NOT NULL DEFAULT 'user'"
            );
        }

        DB::table('users')
            ->where('role', 'authorized_user')
            ->update(['role' => 'staff']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE users MODIFY role ENUM('admin', 'staff', 'user') NOT NULL DEFAULT 'user'"
            );
        }

        if (
            Schema::hasColumn('users', 'authorized_permissions')
            && ! Schema::hasColumn('users', 'staff_permissions')
        ) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('authorized_permissions', 'staff_permissions');
            });
        }
    }
};
