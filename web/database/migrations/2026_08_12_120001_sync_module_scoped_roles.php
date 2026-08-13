<?php

use Database\Seeders\EnsureExtendedModulePermissionsSeeder;
use Database\Seeders\EnsureSystemRolesSeeder;
use Database\Seeders\ModuleRolesSeeder;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\RoleCategoriesSeeder;
use Database\Seeders\SyncDefaultRolesSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Applies module-scoped predefined roles and permissions during migrate,
 * so production deploys do not require running db:seed separately.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasColumn('roles', 'module_key')) {
            return;
        }

        (new PermissionsSeeder)->run();
        (new EnsureExtendedModulePermissionsSeeder)->run();
        (new RoleCategoriesSeeder)->run();
        (new EnsureSystemRolesSeeder)->run();
        (new SyncDefaultRolesSeeder)->run();
        (new ModuleRolesSeeder)->run();
    }

    public function down(): void
    {
        // Data migration - role definitions are not rolled back.
    }
};
