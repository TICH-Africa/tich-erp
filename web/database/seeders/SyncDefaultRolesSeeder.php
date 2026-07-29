<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncDefaultRolesSeeder extends Seeder
{
    public function run(): void
    {
        $this->removePrincipalRole();
        $this->renameLecturerRole();
        $this->ensureLecturerTutorPermissions();
    }

    private function removePrincipalRole(): void
    {
        $principalId = DB::table('roles')->where('role_name', 'Principal')->value('id');

        if (! $principalId) {
            return;
        }

        DB::table('user_roles')->where('role_id', $principalId)->delete();
        DB::table('role_permissions')->where('role_id', $principalId)->delete();
        DB::table('roles')->where('id', $principalId)->delete();
    }

    private function renameLecturerRole(): void
    {
        $exists = DB::table('roles')->where('role_name', 'Lecturer')->exists();

        if (! $exists) {
            return;
        }

        $payload = [
            'role_name' => 'Lecturer/Tutor',
            'description' => 'Teaching staff with academic delivery functions',
        ];

        if (Schema::hasColumn('roles', 'display_name')) {
            $payload['display_name'] = 'Lecturer/Tutor';
        }

        DB::table('roles')->where('role_name', 'Lecturer')->update($payload);
    }

    private function ensureLecturerTutorPermissions(): void
    {
        $roleId = DB::table('roles')->where('role_name', 'Lecturer/Tutor')->value('id');

        if (! $roleId || ! DB::table('permissions')->exists()) {
            return;
        }

        $query = DB::table('permissions')
            ->where('module', 'academics')
            ->whereIn('category', ['view', 'create', 'edit', 'manage']);

        foreach ($query->pluck('id') as $permissionId) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permissionId],
                ['granted_at' => now()]
            );
        }
    }
}
