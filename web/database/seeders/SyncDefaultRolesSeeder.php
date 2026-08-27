<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * One-time data cleanup (Principal / Lecturer rename). Safe to keep as migration-style seeder.
 */
class SyncDefaultRolesSeeder extends Seeder
{
    public function run(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('roles')) {
            return;
        }

        $principalId = DB::table('roles')->where('role_name', 'Principal')->value('id');

        if ($principalId) {
            DB::table('user_roles')->where('role_id', $principalId)->delete();
            DB::table('role_permissions')->where('role_id', $principalId)->delete();
            DB::table('roles')->where('id', $principalId)->delete();
        }

        DB::table('roles')
            ->where('role_name', 'Lecturer')
            ->update([
                'role_name' => 'Lecturer/Tutor',
                'display_name' => 'Lecturer / Tutor',
            ]);
    }
}
