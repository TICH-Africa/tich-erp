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
        // Essential platform bootstrap: thin role rows from code catalog.
        // Permissions, categories, module roles, nav = config (not seeded).
        $this->call(ProductionEssentialSeeder::class);
        $this->call(SyncDefaultRolesSeeder::class);

        // Site / academic catalogue (usually wanted on a live campus).
        $this->call(SiteSettingsSeeder::class);
        $this->call(HomepageContentSeeder::class);
        $this->call(ProgramsSeeder::class);
        $this->call(DepartmentModulesSeeder::class);
        $this->call(HmdCcUnitsSeeder::class);
        $this->call(AcademicCalendarDemoSeeder::class);

        // Demo / sample data - local and staging only.
        if (! app()->environment('production')) {
            $this->call(LecturerSeeder::class);
            $this->call(HrDemoSeeder::class);
            $this->call(FinanceDemoSeeder::class);
            $this->call(AdministrationDemoSeeder::class);
            $this->call(QaDemoSeeder::class);
            $this->call(ProcurementDemoSeeder::class);
            $this->call(ResearchDemoSeeder::class);
            $this->call(IctDemoSeeder::class);
            $this->call(MonitoringEvaluationDemoSeeder::class);
            $this->call(FinanceAccountsDemoSeeder::class);
            $this->call(FinanceBulkDemoSeeder::class);
            $this->call(ExamResultsDemoSeeder::class);
            $this->call(JamesOchiengAcademicCycleSeeder::class);
            $this->call(FinanceDemoSeeder::class);
        }

        // Demo users - local and staging only.
        if (! app()->environment('production')) {
            $rbac = app(RBACService::class);

            $users = [
                [
                    'email' => 'admin@tich.ac.ke',
                    'user_type' => 'admin',
                    'password' => 'Password123!',
                    'role' => 'Super Admin',
                    'mfa_enabled' => true,
                    'mfa_method' => 'email',
                ],
                [
                    'email' => 'osumbaevans21@gmail.com',
                    'user_type' => 'admin',
                    'password' => 'Password123!',
                    'role' => 'Super Admin',
                    'mfa_enabled' => true,
                    'mfa_method' => 'email',
                ],
                [
                    'email' => 'registrar@tich.ac.ke',
                    'user_type' => 'staff',
                    'password' => 'Password123!',
                    'role' => 'Academic Registrar',
                    'mfa_enabled' => false,
                    'mfa_method' => null,
                    'mfa_verified' => true,
                ],
                [
                    'email' => 'admissions@tich.ac.ke',
                    'user_type' => 'staff',
                    'password' => 'Password123!',
                    'role' => 'Admissions Officer',
                    'mfa_enabled' => true,
                    'mfa_method' => 'email',
                ],
                [
                    'email' => 'hod@tich.ac.ke',
                    'user_type' => 'staff',
                    'password' => 'Password123!',
                    'role' => 'HOD',
                    'mfa_enabled' => false,
                    'mfa_method' => null,
                    'mfa_verified' => true,
                ],
                [
                    'email' => 'student@tich.ac.ke',
                    'user_type' => 'student',
                    'password' => 'Password123!',
                    'role' => 'Student',
                    'mfa_enabled' => false,
                    'mfa_method' => null,
                ],
            ];

            foreach ($users as $data) {
                $attributes = [
                    'email' => $data['email'],
                    'user_type' => $data['user_type'],
                    'is_active' => 1,
                    'mfa_enabled' => $data['mfa_enabled'] ?? false,
                    'mfa_method' => $data['mfa_method'] ?? null,
                    'mfa_verified' => $data['mfa_verified'] ?? false,
                ];

                $user = User::query()
                    ->where('email', $data['email'])
                    ->first();

                if ($user) {
                    $user->update($attributes);
                } else {
                    $user = User::create(array_merge($attributes, [
                        'password_hash' => Hash::make($data['password']),
                    ]));
                }

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

            $this->call(EnsureSuperAdminSeeder::class);
        }
    }
}
