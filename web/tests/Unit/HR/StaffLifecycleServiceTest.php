<?php

namespace Tests\Unit\HR;

use App\Models\Department;
use App\Models\JobVacancy;
use App\Models\RecruitmentApplication;
use App\Models\Staff;
use App\Services\StaffLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffLifecycleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(StaffLifecycleService::class);

        $this->department = Department::create([
            'dept_code' => 'TEST-DEPT',
            'dept_name' => 'Test Department',
            'dept_category' => 'administrative',
            'is_active' => 1,
        ]);

        $creator = Staff::create([
            'employee_number' => 'EMP/2026/00001',
            'title' => 'Mr.',
            'first_name' => 'Test',
            'middle_name' => 'Staff',
            'surname' => 'Creator',
            'date_of_birth' => '1990-01-01',
            'gender' => 'Male',
            'email' => 'creator@test.com',
            'phone_number' => '0711000000',
            'department_id' => $this->department->id,
            'job_title' => 'HR Officer',
            'employment_category' => 'permanent',
            'employment_start_date' => now()->toDateString(),
            'gross_monthly_salary' => 50000,
            'employment_status' => 'active',
        ]);

        $this->vacancy = JobVacancy::create([
            'vacancy_number' => 'VAC-001',
            'job_title' => 'Test Position',
            'department_id' => $this->department->id,
            'employment_type' => 'permanent',
            'slots_available' => 1,
            'job_description' => 'Test job',
            'requirements' => 'Test requirements',
            'responsibilities' => 'Test responsibilities',
            'min_qualification' => 'Degree',
            'closing_date' => now()->addMonth(),
            'is_published' => 1,
            'created_by' => $creator->id,
        ]);
    }

    public function test_generate_employee_number_creates_unique_number(): void
    {
        $number = $this->service->generateEmployeeNumber();

        $this->assertMatchesRegularExpression('/^EMP\/\d{4}\/\d{5}$/', $number);
    }

    public function test_convert_applicant_to_employee_creates_staff_and_onboarding(): void
    {
        $applicant = RecruitmentApplication::factory()->create([
            'vacancy_id' => $this->vacancy->id,
            'offer_accepted' => 1,
            'is_onboarded' => 0,
        ]);

        $employmentDetails = [
            'first_name' => 'John',
            'middle_name' => 'Doe',
            'surname' => 'Smith',
            'date_of_birth' => '1990-01-01',
            'gender' => 'Male',
            'email' => 'john.smith@tich.ac.ke',
            'phone_number' => '0711000000',
            'department_id' => $this->department->id,
            'job_title' => 'Administrative Officer',
            'employment_category' => 'permanent',
            'employment_start_date' => now()->toDateString(),
            'gross_monthly_salary' => 50000,
        ];

        $staff = $this->service->convertApplicantToEmployee($applicant, $employmentDetails, 1);

        $this->assertDatabaseHas('staff', ['id' => $staff->id, 'employment_status' => 'onboarding']);
        $this->assertDatabaseHas('staff_onboarding', ['staff_id' => $staff->id, 'status' => 'in_progress']);
        $this->assertDatabaseHas('recruitment_applications', ['id' => $applicant->id, 'is_onboarded' => 1, 'new_staff_id' => $staff->id]);
    }

    public function test_convert_applicant_fails_if_offer_not_accepted(): void
    {
        $applicant = RecruitmentApplication::factory()->create([
            'vacancy_id' => $this->vacancy->id,
            'offer_accepted' => 0,
            'is_onboarded' => 0,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->convertApplicantToEmployee($applicant, [], 1);
    }

    public function test_complete_onboarding_requires_all_steps(): void
    {
        $staff = Staff::factory()->onboarding()->create([
            'department_id' => $this->department->id,
        ]);
        \App\Models\StaffOnboarding::create([
            'staff_id' => $staff->id,
            'onboarding_number' => 'ONB-TEST123',
            'current_step' => 'biodata',
            'status' => 'in_progress',
            'completed_steps' => ['biodata'],
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->completeOnboarding($staff->id, 1);
    }

    public function test_lock_profile_marks_staff_as_locked(): void
    {
        $staff = Staff::factory()->create([
            'department_id' => $this->department->id,
            'is_profile_locked' => 0,
        ]);

        $locked = $this->service->lockProfile($staff->id, 1);

        $this->assertTrue($locked->is_profile_locked);
        $this->assertDatabaseHas('staff', ['id' => $staff->id, 'is_profile_locked' => 1]);
    }

    public function test_request_profile_change_audits_request(): void
    {
        $staff = Staff::factory()->create([
            'department_id' => $this->department->id,
            'is_profile_locked' => 1,
        ]);

        $this->service->requestProfileChange($staff->id, ['phone_number' => '0711000000'], 'Changed number', 1);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'staff',
            'entity_id' => (string) $staff->id,
            'action' => 'staff.profile.change_requested',
        ]);
    }
}
