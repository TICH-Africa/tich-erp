<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Services\RBACService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionsSeeder::class);

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
                'username' => 'registrar',
                'email' => 'registrar@tich.ac.ke',
                'user_type' => 'staff',
                'password' => 'Password123!',
                'role' => 'Academic Registrar',
                'mfa_enabled' => true,
                'mfa_method' => 'auth_app',
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
                    'mfa_enabled' => $data['mfa_enabled'],
                    'mfa_method' => $data['mfa_method'],
                ]
            );

            $roleId = Role::query()->where('role_name', $data['role'])->value('id');

            if ($roleId) {
                $rbac->assignRoleToUser($user, $roleId);
            }
        }
    }
}
