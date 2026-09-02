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
            ->whereIn('dept_code', ['HMD-CC', 'CHS', 'BUS', 'ICT', 'TEC'])
            ->orWhere(function ($query) {
                $query->where('dept_category', 'academic')
                    ->whereNotNull('parent_dept_id')
                    ->where('is_active', 1);
            })
            ->orderBy('dept_name')
            ->value('id');

        if (! $departmentId) {
            return;
        }

        $campusId = DB::table('campuses')->where('is_active', 1)->value('id');
        $semesterId = DB::table('semesters')->where('semester_number', 1)->value('id')
            ?? DB::table('semesters')->orderByDesc('id')->value('id');
        $unitId = Unit::query()->where('unit_code', 'HMDCC-01')->value('id')
            ?? Unit::query()->where('department_id', $departmentId)->value('id');

        $primaryLecturer = $this->seedLecturer(
            email: 'lecturer@tich.ac.ke',
            loginAliasEmail: 'james.ochieng@tich.africa',
            employeeNumber: 'EMP-LECT-001',
            firstName: 'James',
            surname: 'Ochieng',
            jobTitle: 'Senior Lecturer',
            departmentId: $departmentId,
            campusId: $campusId,
        );

        $this->seedLecturer(
            email: 'academic@tich.com',
            employeeNumber: 'EMP-LECT-002',
            firstName: 'Mary',
            surname: 'Akinyi',
            jobTitle: 'Lecturer',
            departmentId: $departmentId,
            campusId: $campusId,
        );

        if ($unitId && $semesterId && $campusId) {
            $this->ensureUnitAllocation($primaryLecturer, (int) $unitId, (int) $semesterId, (int) $campusId);
            $this->ensureDemoEnrolments((int) $unitId, (int) $semesterId);
        }
    }

    private function seedLecturer(
        string $email,
        string $employeeNumber,
        string $firstName,
        string $surname,
        string $jobTitle,
        int $departmentId,
        ?int $campusId,
        ?string $loginAliasEmail = null,
    ): Staff {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'user_type' => 'staff',
                'password_hash' => Hash::make('Password123!'),
                'is_active' => 1,
                'mfa_enabled' => false,
                'mfa_verified' => true,
            ]
        );

        $user->update([
            'email' => $email,
            'user_type' => 'staff',
            'is_active' => 1,
            'mfa_enabled' => false,
            'mfa_verified' => true,
        ]);

        $staff = Staff::query()->firstOrCreate(
            ['employee_number' => $employeeNumber],
            [
                'title' => 'Mr.',
                'first_name' => $firstName,
                'surname' => $surname,
                'date_of_birth' => '1985-06-15',
                'gender' => 'male',
                'primary_email' => $loginAliasEmail ?: strtolower($firstName).'.'.strtolower($surname).'@gmail.com',
                'organisation_email' => $email,
                'phone_number' => '0712345678',
                'department_id' => $departmentId,
                'campus_id' => $campusId,
                'job_title' => $jobTitle,
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
            'campus_id' => $campusId,
            'organisation_email' => $email,
            'primary_email' => $loginAliasEmail ?: $staff->primary_email,
            'employment_status' => 'active',
        ]);

        $user->update(['staff_id' => $staff->id, 'email' => $email]);

        $roleId = Role::query()->where('role_name', 'Lecturer/Tutor')->value('id');
        if ($roleId) {
            app(RBACService::class)->assignRoleToUser($user, $roleId, null, $departmentId);
        }

        return $staff;
    }

    private function ensureUnitAllocation(Staff $staff, int $unitId, int $semesterId, int $campusId): void
    {
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
