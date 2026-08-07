<?php

namespace Tests\Unit\HR;

use App\Models\Department;
use App\Models\Grievance;
use App\Models\Staff;
use App\Models\User;
use App\Services\EmployeeConcernService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeConcernServiceTest extends TestCase
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

    public function test_employee_can_submit_conern_to_hr(): void
    {
        $staff = Staff::create([
            'employee_number' => 'EMP/2026/00001',
            'first_name' => 'Jane',
            'surname' => 'Doe',
            'date_of_birth' => '1990-01-01',
            'gender' => 'Female',
            'primary_email' => 'jane@test.com',
            'organisation_email' => 'jane.doe@tich.africa',
            'phone_number' => '0700000000',
            'department_id' => $this->department->id,
            'job_title' => 'Officer',
            'employment_category' => 'permanent',
            'employment_start_date' => '2024-01-01',
            'employment_status' => 'active',
            'gross_monthly_salary' => 50000,
        ]);

        $user = User::factory()->create(['user_type' => 'staff']);
        $staff->update(['user_id' => $user->id]);

        $grievance = app(EmployeeConcernService::class)->submit($staff, $user, [
            'concern_category' => 'working_conditions',
            'subject' => 'Office ventilation issue',
            'description' => 'The office is too hot in the afternoon.',
        ]);

        $this->assertInstanceOf(Grievance::class, $grievance);
        $this->assertStringStartsWith('CON-', $grievance->reference_number);
        $this->assertSame('open', $grievance->status);
        $this->assertDatabaseHas('grievances', [
            'id' => $grievance->id,
            'subject' => 'Office ventilation issue',
        ]);
    }
}
