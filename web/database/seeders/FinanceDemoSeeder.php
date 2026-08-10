<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use App\Services\DepartmentModuleService;
use App\Services\RBACService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FinanceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $financeDepartmentId = DB::table('departments')->where('dept_code', 'FIN')->value('id')
            ?? DB::table('departments')->where('dept_name', 'like', '%Finance%')->value('id');

        if (! $financeDepartmentId) {
            return;
        }

        $campusId = DB::table('campuses')->where('is_active', 1)->value('id');

        $this->ensureFinanceManager($financeDepartmentId, $campusId);
        $this->ensureDepartmentModules($financeDepartmentId);
    }

    private function ensureFinanceManager(int $departmentId, ?int $campusId): Staff
    {
        $emails = $this->staffEmails('Peter', 'Kamau');

        $user = User::query()->firstOrCreate(
            ['email' => $emails['organisation_email']],
            [
                'user_type' => 'staff',
                'password_hash' => Hash::make('Password123!'),
                'is_active' => 1,
                'mfa_enabled' => false,
                'mfa_verified' => true,
            ]
        );
        $user->update(['email' => $emails['organisation_email']]);

        $staff = Staff::query()->firstOrCreate(
            ['employee_number' => 'EMP-FIN-001'],
            [
                'title' => 'Mr.',
                'first_name' => 'Peter',
                'surname' => 'Kamau',
                'date_of_birth' => '1985-08-20',
                'gender' => 'male',
                'primary_email' => $emails['primary_email'],
                'organisation_email' => $emails['organisation_email'],
                'phone_number' => '0722000201',
                'department_id' => $departmentId,
                'campus_id' => $campusId,
                'job_title' => 'Finance Manager',
                'employment_category' => 'permanent',
                'employment_start_date' => '2018-01-15',
                'employment_status' => 'active',
                'gross_monthly_salary' => 105000,
                'is_teaching_staff' => 0,
                'user_id' => $user->id,
            ]
        );

        $staff->update([
            'user_id' => $user->id,
            'department_id' => $departmentId,
            'primary_email' => $emails['primary_email'],
            'organisation_email' => $emails['organisation_email'],
            'payroll_scheme' => 'employee',
        ]);
        $user->update(['staff_id' => $staff->id, 'email' => $emails['organisation_email']]);

        $roleId = Role::query()->where('role_name', 'Finance Manager')->value('id');
        if ($roleId) {
            app(RBACService::class)->assignRoleToUser($user, $roleId, null, $departmentId);
        }

        return $staff;
    }

    private function ensureDepartmentModules(int $financeDepartmentId): void
    {
        $department = Department::query()->find($financeDepartmentId);

        if (! $department) {
            return;
        }

        app(DepartmentModuleService::class)->syncModules($department, ['finance']);
    }

    /**
     * @return array{primary_email: string, organisation_email: string}
     */
    private function staffEmails(string $firstName, string $surname): array
    {
        return [
            'primary_email' => strtolower($firstName).'.'.strtolower($surname).'@gmail.com',
            'organisation_email' => Staff::organisationEmailFromName($firstName, $surname),
        ];
    }
}
