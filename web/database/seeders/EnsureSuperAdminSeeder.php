<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\RBACService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EnsureSuperAdminSeeder extends Seeder
{
    /**
     * @var list<array{email: string, password: string, mfa_enabled?: bool, mfa_verified?: bool}>
     */
    protected array $superAdmins = [
        [
            'email' => 'osumbaevans21@gmail.com',
            'password' => 'Password123!',
            'mfa_enabled' => true,
            'mfa_verified' => true,
        ],
        [
            'email' => 'admin@tich.ac.ke',
            'password' => 'Password123!',
            'mfa_enabled' => true,
            'mfa_verified' => true,
        ],
    ];

    public function run(): void
    {
        $roleId = app(\App\Services\RbacCatalogService::class)->roleIdByName('Super Admin');

        if (! $roleId) {
            return;
        }

        $rbac = app(RBACService::class);

        foreach ($this->superAdmins as $data) {
            $user = User::query()->firstOrCreate(
                ['email' => $data['email']],
                [
                    'user_type' => 'admin',
                    'password_hash' => Hash::make($data['password']),
                    'is_active' => 1,
                    'mfa_enabled' => $data['mfa_enabled'] ?? true,
                    'mfa_method' => 'email',
                    'mfa_verified' => $data['mfa_verified'] ?? true,
                ]
            );

            $user->update([
                'user_type' => 'admin',
                'password_hash' => Hash::make($data['password']),
                'is_active' => 1,
                'mfa_enabled' => $data['mfa_enabled'] ?? true,
                'mfa_method' => 'email',
                'mfa_verified' => $data['mfa_verified'] ?? true,
            ]);

            $hasRole = DB::table('user_roles')
                ->where('user_id', $user->id)
                ->where('role_id', $roleId)
                ->exists();

            if (! $hasRole) {
                $rbac->assignRoleToUser($user, $roleId);
            }
        }
    }
}
