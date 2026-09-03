<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Staff;
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
                    'user_type' => 'super_admin',
                    'password' => 'Password123!',
                    'role' => 'Super Admin',
                    'mfa_enabled' => true,
                    'mfa_method' => 'email',
                ],
                [
                    'email' => 'osumbaevans21@gmail.com',
                    'user_type' => 'super_admin',
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
                    'mfa_verified' => true,
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

                if (! $roleId) {
                    continue;
                }

                $departmentId = $this->resolveDepartmentForRole($data['role']);

                if ($data['user_type'] === 'staff') {
                    $staff = Staff::query()->where('user_id', $user->id)->first();

                    if (! $staff) {
                        $name = $this->staffNameForRole($data['role']);
                        $staff = Staff::create([
                            'employee_number' => 'EMP-'.$data['role'].'-'.strtoupper(substr($user->email, 0, 3)).'-'.$user->id,
                            'title' => 'Mr.',
                            'first_name' => $name['first'],
                            'surname' => $name['surname'],
                            'job_title' => $data['role'],
                            'date_of_birth' => '1985-01-15',
                            'gender' => 'male',
                            'marital_status' => 'single',
                            'nationality' => 'Kenyan',
                            'home_county' => 'Nairobi',
                            'primary_email' => $user->email,
                            'organisation_email' => $user->email,
                            'phone_number' => '0712345678',
                            'physical_address' => 'Nairobi, Kenya',
                            'emergency_contact_name' => 'Emergency Contact',
                            'emergency_contact_phone' => '0700000000',
                            'emergency_contact_relationship' => 'Spouse',
                            'department_id' => $departmentId,
                            'employment_category' => 'permanent',
                            'employment_start_date' => now()->toDateString(),
                            'employment_status' => 'active',
                            'is_teaching_staff' => in_array($data['role'], ['HOD', 'Lecturer/Tutor'], true),
                            'user_id' => $user->id,
                        ]);
                    } elseif ($departmentId && ! $staff->department_id) {
                        $staff->update(['department_id' => $departmentId]);
                    }

                    $user->update(['staff_id' => $staff->id]);
                }

                $hasDefaultRole = DB::table('user_roles')
                    ->where('user_id', $user->id)
                    ->where('role_id', $roleId)
                    ->exists();

                if (! $hasDefaultRole) {
                    $rbac->assignRoleToUser($user, $roleId, null, $departmentId);
                }
            }

            $this->call(EnsureSuperAdminSeeder::class);
        }
    }

    private function staffNameForRole(string $roleName): array
    {
        return match ($roleName) {
            'HOD' => ['first' => 'John', 'surname' => 'Ochieng'],
            'Academic Registrar' => ['first' => 'Sarah', 'surname' => 'Wanjiru'],
            'Admissions Officer' => ['first' => 'Michael', 'surname' => 'Kariuki'],
            'Lecturer/Tutor' => ['first' => 'Grace', 'surname' => 'Mutiso'],
            default => ['first' => 'Demo', 'surname' => 'Staff'],
        };
    }

    private function resolveDepartmentForRole(string $roleName): ?int
    {
        $isAcademicRole = in_array($roleName, ['HOD', 'Lecturer/Tutor', 'Academic Registrar'], true);
        $isAdmissionsRole = $roleName === 'Admissions Officer';

        if ($isAcademicRole) {
            return DB::table('departments')
                ->where('dept_code', 'CHS')
                ->orWhere(function ($query) {
                    $query->where('dept_category', 'academic')
                        ->whereNotNull('parent_dept_id')
                        ->where('is_active', 1);
                })
                ->orderBy('dept_name')
                ->value('id');
        }

        if ($isAdmissionsRole) {
            return DB::table('departments')
                ->whereIn('dept_code', ['ADM', 'ADMISSIONS'])
                ->orWhere(function ($query) {
                    $query->where('dept_category', 'administrative')
                        ->whereNull('parent_dept_id')
                        ->where('is_active', 1);
                })
                ->orderBy('dept_name')
                ->value('id');
        }

        return null;
    }
}
