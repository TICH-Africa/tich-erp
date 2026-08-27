<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MFA columns on users. Permission catalog tables were removed — roles/permissions
 * are code-owned (config/tich-permissions.php, config/tich-module-roles.php).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'mfa_secret')) {
                $table->string('mfa_secret', 100)->nullable();
            }
            if (! Schema::hasColumn('users', 'mfa_backup_codes')) {
                $table->json('mfa_backup_codes')->nullable();
            }
            if (! Schema::hasColumn('users', 'mfa_enabled_at')) {
                $table->dateTime('mfa_enabled_at')->nullable();
            }
            if (! Schema::hasColumn('users', 'mfa_last_verified_at')) {
                $table->dateTime('mfa_last_verified_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('users', 'mfa_secret') ? 'mfa_secret' : null,
                Schema::hasColumn('users', 'mfa_backup_codes') ? 'mfa_backup_codes' : null,
                Schema::hasColumn('users', 'mfa_enabled_at') ? 'mfa_enabled_at' : null,
                Schema::hasColumn('users', 'mfa_last_verified_at') ? 'mfa_last_verified_at' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
