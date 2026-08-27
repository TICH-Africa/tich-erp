<?php

use App\Services\RbacCatalogService;
use Database\Seeders\SyncDefaultRolesSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Ensures thin role rows from the code catalog after module_key exists.
 * Permissions / categories / nav / role↔permission templates are config-owned.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasColumn('roles', 'module_key')) {
            return;
        }

        app(RbacCatalogService::class)->ensureRolesExist();
        (new SyncDefaultRolesSeeder)->run();
    }

    public function down(): void
    {
        // Data migration - role definitions are not rolled back.
    }
};
