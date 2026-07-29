<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Services\RBACService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionsSeeder::class);
        $this->call(EnsureSystemRolesSeeder::class);
        $this->call(NavigationSeeder::class);
        $this->call(HomepageContentSeeder::class);
        $this->call(ProgramsSeeder::class);
        $this->call(HmdCcUnitsSeeder::class);
        $this->call(LecturerSeeder::class);
        $this->call(ExamResultsDemoSeeder::class);
        $this->call(StaffMarksDemoSeeder::class);

        $rbac = app(RBACService::class);

        $users = [
            [
                'username' => 'superadmin',
                'email' => 'admin@tich.ac.ke',
                'user_type' => 'admin',
                'password' => 'Password123!',
                'role' => 'Super Admin',
                'mfa_enabled' => true,
                'mfa_method' => 'email',
            ],
            [
                'username' => 'admin2',
                'email' => 'osumbaevans21@gmail.com',
                'user_type' => 'admin',
                'password' => 'Password123!',
                'role' => 'Super Admin',
                'mfa_enabled' => true,
                'mfa_method' => 'email',
            ],
            [
                'username' => 'registrar',
                'email' => 'registrar@tich.ac.ke',
                'user_type' => 'staff',
                'password' => 'Password123!',
                'role' => 'Academic Registrar',
                'mfa_enabled' => false,
                'mfa_method' => null,
                'mfa_verified' => true,
            ],
            [
                'username' => 'admissions',
                'email' => 'admissions@tich.ac.ke',
                'user_type' => 'staff',
                'password' => 'Password123!',
                'role' => 'Admissions Officer',
                'mfa_enabled' => true,
                'mfa_method' => 'email',
            ],
            [
                'username' => 'student.demo',
                'email' => 'student@tich.ac.ke',
                'user_type' => 'student',
                'password' => 'Password123!',
                'role' => 'Student',
                'mfa_enabled' => false,
                'mfa_method' => null,
            ],
        ];

        foreach ($users as $data) {
            $user = User::query()->firstOrCreate(
                ['email' => $data['email']],
                [
                    'username' => $data['username'],
                    'user_type' => $data['user_type'],
                    'password_hash' => Hash::make($data['password']),
                    'is_active' => 1,
                    'mfa_enabled' => $data['mfa_enabled'] ?? false,
                    'mfa_method' => $data['mfa_method'] ?? null,
                    'mfa_verified' => $data['mfa_verified'] ?? false,
                ]
            );

            $roleId = Role::query()->where('role_name', $data['role'])->value('id');

            if ($roleId) {
                $hasDefaultRole = DB::table('user_roles')
                    ->where('user_id', $user->id)
                    ->where('role_id', $roleId)
                    ->exists();

                if (! $hasDefaultRole) {
                    $rbac->assignRoleToUser($user, $roleId);
                }
            }
        }
    }
}
