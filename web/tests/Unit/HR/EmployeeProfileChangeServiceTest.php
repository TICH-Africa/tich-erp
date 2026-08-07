<?php

namespace Tests\Unit\HR;

use App\Models\Department;
use App\Models\Staff;
use App\Models\StaffProfileChangeRequest;
use App\Models\User;
use App\Services\EmployeeProfileChangeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeProfileChangeServiceTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::create([
            'dept_code' => 'TEST-DEPT',
            'dept_name' => 'Test Department',
            'dept_category' => 'administrative',
            'is_active' => 1,
        ]);
    }

    public function test_employee_can_submit_profile_field_change_for_hr_review(): void
    {
        $staff = $this->makeStaff(['phone_number' => '0700000000']);
        $user = User::factory()->create(['user_type' => 'staff']);
        $staff->update(['user_id' => $user->id]);

        $service = app(EmployeeProfileChangeService::class);
        $created = $service->submitUpdates($staff, $user, [
            'phone_number' => '0711222333',
        ]);

        $this->assertCount(1, $created);
        $this->assertDatabaseHas('staff_profile_change_requests', [
            'staff_id' => $staff->id,
            'request_type' => StaffProfileChangeRequest::TYPE_PROFILE_UPDATE,
            'status' => StaffProfileChangeRequest::STATUS_PENDING,
        ]);
        $this->assertSame('0700000000', $staff->fresh()->phone_number);
    }

    public function test_hr_approval_applies_profile_changes(): void
    {
        $reviewer = $this->makeStaff([
            'employee_number' => 'EMP/2026/00002',
            'primary_email' => 'reviewer@test.com',
            'organisation_email' => 'reviewer@tich.africa',
        ]);
        $staff = $this->makeStaff(['phone_number' => '0700000000']);
        $user = User::factory()->create(['user_type' => 'staff']);
        $staff->update(['user_id' => $user->id]);

        $request = StaffProfileChangeRequest::create([
            'staff_id' => $staff->id,
            'requested_by_user_id' => $user->id,
            'request_type' => StaffProfileChangeRequest::TYPE_PROFILE_UPDATE,
            'status' => StaffProfileChangeRequest::STATUS_PENDING,
            'current_snapshot' => ['phone_number' => '0700000000'],
            'proposed_changes' => ['phone_number' => '0711222333'],
        ]);

        app(EmployeeProfileChangeService::class)->approve($request, $reviewer);

        $this->assertSame('0711222333', $staff->fresh()->phone_number);
        $this->assertSame(StaffProfileChangeRequest::STATUS_APPROVED, $request->fresh()->status);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeStaff(array $overrides = []): Staff
    {
        return Staff::create(array_merge([
            'employee_number' => 'EMP/2026/00001',
            'title' => 'Mr.',
            'first_name' => 'Test',
            'surname' => 'Employee',
            'date_of_birth' => '1990-01-01',
            'gender' => 'Male',
            'primary_email' => 'employee@test.com',
            'organisation_email' => 'employee@tich.africa',
            'phone_number' => '0700000000',
            'department_id' => $this->department->id,
            'job_title' => 'Officer',
            'employment_category' => 'permanent',
            'employment_start_date' => '2024-01-01',
            'employment_status' => 'active',
            'gross_monthly_salary' => 50000,
            'is_profile_locked' => 1,
        ], $overrides));
    }
}
