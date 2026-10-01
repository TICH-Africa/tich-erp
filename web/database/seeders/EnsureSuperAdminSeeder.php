<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\RBACService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EnsureSuperAdminSeeder extends Seeder
{
    /**
     * Bootstrap platform operators. Passwords come from env (never hardcode secrets).
     * Existing users keep their password unless SUPER_ADMIN_FORCE_PASSWORD_RESET=true.
     *
     * @var list<array{email: string, env_password: string, mfa_enabled?: bool}>
     */
    protected array $superAdmins = [
        [
            'email' => 'osumbaevans21@gmail.com',
            'env_password' => 'SUPER_ADMIN_PASSWORD',
            'mfa_enabled' => true,
        ],
        [
            'email' => 'admin@tich.ac.ke',
            'env_password' => 'SUPER_ADMIN_PASSWORD_ALT',
            'mfa_enabled' => true,
        ],
    ];

    public function run(): void
    {
        $roleId = app(\App\Services\RbacCatalogService::class)->roleIdByName('Super Admin');

        if (! $roleId) {
            return;
        }

        $rbac = app(RBACService::class);
        $forceReset = filter_var(env('SUPER_ADMIN_FORCE_PASSWORD_RESET', false), FILTER_VALIDATE_BOOLEAN);

        foreach ($this->superAdmins as $data) {
            $plain = env($data['env_password']);
            $existing = User::query()->where('email', $data['email'])->first();

            if ($existing) {
                $updates = [
                    'user_type' => 'super_admin',
                    'is_active' => 1,
                    'mfa_enabled' => $data['mfa_enabled'] ?? true,
                    'mfa_method' => $existing->mfa_method ?: 'email',
                ];

                if ($forceReset && is_string($plain) && $plain !== '') {
                    $updates['password_hash'] = Hash::make($plain);
                }

                $existing->update($updates);
                $user = $existing;
            } else {
                // New bootstrap account: require env password, else generate one-time random.
                $password = (is_string($plain) && $plain !== '') ? $plain : Str::password(20);
                $user = User::query()->create([
                    'email' => $data['email'],
                    'user_type' => 'super_admin',
                    'password_hash' => Hash::make($password),
                    'is_active' => 1,
                    'mfa_enabled' => $data['mfa_enabled'] ?? true,
                    'mfa_method' => 'email',
                    'mfa_verified' => 0,
                ]);

                if (! is_string($plain) || $plain === '') {
                    $this->command?->warn(
                        "Created {$data['email']} with a random password. Set {$data['env_password']} in .env and SUPER_ADMIN_FORCE_PASSWORD_RESET=true to set a known password."
                    );
                }
            }

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
