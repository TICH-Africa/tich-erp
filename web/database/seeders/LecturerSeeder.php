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
            ->where('dept_code', 'CHS')
            ->value('id');

        if (! $departmentId) {
            $departmentId = DB::table('departments')
                ->where('dept_category', 'academic')
                ->whereNotNull('parent_dept_id')
                ->value('id');
        }

        if (! $departmentId) {
            $departmentId = DB::table('departments')->value('id');
        }

        if (! $departmentId) {
            return;
        }

        $campusId = DB::table('campuses')->where('is_active', 1)->value('id');
        $semesterId = DB::table('semesters')->where('semester_number', 1)->value('id')
            ?? DB::table('semesters')->orderByDesc('id')->value('id');
        $unitId = Unit::query()->where('unit_code', 'HMDCC-01')->value('id')
            ?? Unit::query()->where('department_id', $departmentId)->value('id');

        $user = User::query()->firstOrCreate(
            ['username' => 'lecturer.demo'],
            [
                'email' => 'james.ochieng@tich.africa',
                'user_type' => 'staff',
                'password_hash' => Hash::make('Password123!'),
                'is_active' => 1,
                'mfa_enabled' => false,
                'mfa_verified' => true,
            ]
        );
        $user->update(['email' => 'james.ochieng@tich.africa']);

        $staff = Staff::query()->firstOrCreate(
            ['employee_number' => 'EMP-LECT-001'],
            [
                'title' => 'Mr.',
                'first_name' => 'James',
                'surname' => 'Ochieng',
                'date_of_birth' => '1985-06-15',
                'gender' => 'male',
                'primary_email' => 'james.ochieng@gmail.com',
                'organisation_email' => 'james.ochieng@tich.africa',
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

        $staff->update([
            'user_id' => $user->id,
            'is_teaching_staff' => 1,
            'department_id' => $departmentId,
            'primary_email' => 'james.ochieng@gmail.com',
            'organisation_email' => 'james.ochieng@tich.africa',
        ]);
        $user->update(['staff_id' => $staff->id, 'email' => 'james.ochieng@tich.africa']);

        $roleId = Role::query()->where('role_name', 'Lecturer/Tutor')->value('id');
        if ($roleId) {
            app(RBACService::class)->assignRoleToUser($user, $roleId);
        }

        if ($unitId && $semesterId && $campusId) {
            $existing = UnitAllocation::query()
                ->where('staff_id', $staff->id)
                ->where('semester_id', $semesterId)
                ->orderByDesc('id')
                ->first();

            if ($existing) {
                UnitAllocation::query()
                    ->where('staff_id', $staff->id)
                    ->where('id', '!=', $existing->id)
                    ->update(['is_active' => 0]);

                $existing->update([
                    'unit_id' => $unitId,
                    'campus_id' => $campusId,
                    'contact_hours_assigned' => 4,
                    'is_coordinator' => 1,
                    'is_active' => 1,
                ]);
            } else {
                UnitAllocation::query()
                    ->where('staff_id', $staff->id)
                    ->update(['is_active' => 0]);

                UnitAllocation::query()->create([
                    'unit_id' => $unitId,
                    'staff_id' => $staff->id,
                    'semester_id' => $semesterId,
                    'campus_id' => $campusId,
                    'contact_hours_assigned' => 4,
                    'is_coordinator' => 1,
                    'is_active' => 1,
                ]);
            }

            DB::table('program_timetable_sessions')
                ->where('unit_id', $unitId)
                ->whereNull('staff_id')
                ->update(['staff_id' => $staff->id]);

            $this->ensureDemoEnrolments((int) $unitId, (int) $semesterId);
        }
    }

    private function ensureDemoEnrolments(int $unitId, int $semesterId): void
    {
        $programId = DB::table('program_timetables')
            ->join('program_timetable_sessions', 'program_timetable_sessions.program_timetable_id', '=', 'program_timetables.id')
            ->where('program_timetable_sessions.unit_id', $unitId)
            ->value('program_timetables.program_id');

        $studentQuery = DB::table('students')->where('is_active', 1);
        if ($programId) {
            $studentQuery->where('program_id', $programId);
        }

        foreach ($studentQuery->pluck('id') as $studentId) {
            DB::table('student_semester_registrations')->updateOrInsert(
                ['student_id' => $studentId, 'semester_id' => $semesterId],
                [
                    'registration_date' => now()->toDateString(),
                    'registration_type' => 'admin',
                    'unit_count' => 1,
                    'status' => 'registered',
                    'is_fee_cleared' => 1,
                ]
            );

            $registrationId = DB::table('student_semester_registrations')
                ->where('student_id', $studentId)
                ->where('semester_id', $semesterId)
                ->value('id');

            if ($registrationId) {
                DB::table('registered_units')->updateOrInsert(
                    ['semester_registration_id' => $registrationId, 'unit_id' => $unitId],
                    ['is_additional' => 0, 'created_at' => now()],
                );
            }
        }
    }
}
