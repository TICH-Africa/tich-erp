<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Staff;
use App\Models\Unit;
use App\Models\UnitAllocation;
use App\Models\User;
use App\Services\RBACService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LecturerSeeder extends Seeder
{
    public function run(): void
    {
        $departmentId = DB::table('departments')
            ->where('dept_category', 'academic')
            ->whereNotNull('parent_dept_id')
            ->value('id');

        if (! $departmentId) {
            $departmentId = DB::table('departments')->value('id');
        }

        if (! $departmentId) {
            return;
        }

        $campusId = DB::table('campuses')->where('is_active', 1)->value('id');
        $semesterId = DB::table('semesters')->orderByDesc('id')->value('id');
        $unitId = Unit::query()->where('department_id', $departmentId)->value('id');

        $user = User::query()->firstOrCreate(
            ['email' => 'lecturer@tich.ac.ke'],
            [
                'username' => 'lecturer.demo',
                'user_type' => 'staff',
                'password_hash' => Hash::make('Password123!'),
                'is_active' => 1,
                'mfa_enabled' => false,
                'mfa_verified' => true,
            ]
        );

        $staff = Staff::query()->firstOrCreate(
            ['employee_number' => 'EMP-LECT-001'],
            [
                'title' => 'Mr.',
                'first_name' => 'James',
                'surname' => 'Ochieng',
                'date_of_birth' => '1985-06-15',
                'gender' => 'male',
                'email' => 'lecturer@tich.ac.ke',
                'phone_number' => '0712345678',
                'department_id' => $departmentId,
                'job_title' => 'Senior Lecturer',
                'employment_category' => 'permanent',
                'employment_start_date' => '2020-01-01',
                'employment_status' => 'active',
                'is_teaching_staff' => 1,
                'user_id' => $user->id,
            ]
        );

        $staff->update(['user_id' => $user->id, 'is_teaching_staff' => 1]);
        $user->update(['staff_id' => $staff->id]);

        $roleId = Role::query()->where('role_name', 'Lecturer')->value('id');
        if ($roleId) {
            app(RBACService::class)->assignRoleToUser($user, $roleId);
        }

        if ($unitId && $semesterId && $campusId) {
            UnitAllocation::query()->firstOrCreate(
                [
                    'unit_id' => $unitId,
                    'staff_id' => $staff->id,
                    'semester_id' => $semesterId,
                ],
                [
                    'campus_id' => $campusId,
                    'contact_hours_assigned' => 4,
                    'is_coordinator' => 1,
                    'is_active' => 1,
                ]
            );

            DB::table('program_timetable_sessions')
                ->where('unit_id', $unitId)
                ->whereNull('staff_id')
                ->update(['staff_id' => $staff->id]);
        }
    }
}
