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

abstract class ModuleDemoSeeder extends Seeder
{
    abstract protected function deptCode(): string;

    abstract protected function moduleKey(): string;

    abstract protected function roleName(): string;

    /**
     * @return array{employee_number: string, first_name: string, surname: string, job_title: string, gross_monthly_salary: float}
     */
    abstract protected function managerProfile(): array;

    public function run(): void
    {
        $departmentId = DB::table('departments')->where('dept_code', $this->deptCode())->value('id');

        if (! $departmentId) {
            return;
        }

        $campusId = DB::table('campuses')->where('is_active', 1)->value('id');
        $profile = $this->managerProfile();
        $emails = $this->staffEmails($profile['first_name'], $profile['surname']);

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
            ['employee_number' => $profile['employee_number']],
            [
                'title' => 'Mr.',
                'first_name' => $profile['first_name'],
                'surname' => $profile['surname'],
                'date_of_birth' => '1988-04-12',
                'gender' => 'female',
                'primary_email' => $emails['primary_email'],
                'organisation_email' => $emails['organisation_email'],
                'phone_number' => '0722000301',
                'department_id' => $departmentId,
                'campus_id' => $campusId,
                'job_title' => $profile['job_title'],
                'employment_category' => 'permanent',
                'employment_start_date' => '2019-03-01',
                'employment_status' => 'active',
                'gross_monthly_salary' => $profile['gross_monthly_salary'],
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

        $roleId = Role::query()->where('role_name', $this->roleName())->value('id');
        if ($roleId) {
            app(RBACService::class)->assignRoleToUser($user, $roleId, null, $departmentId);
        }

        $department = Department::query()->find($departmentId);
        if ($department) {
            app(DepartmentModuleService::class)->syncModules($department, [$this->moduleKey()]);
        }
    }

    /**
     * @return array{primary_email: string, organisation_email: string}
     */
    protected function staffEmails(string $firstName, string $surname): array
    {
        return [
            'primary_email' => strtolower($firstName).'.'.strtolower($surname).'@gmail.com',
            'organisation_email' => Staff::organisationEmailFromName($firstName, $surname),
        ];
    }
}
