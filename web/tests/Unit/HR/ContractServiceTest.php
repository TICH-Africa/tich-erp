<?php

namespace Tests\Unit\HR;

use App\Models\Department;
use App\Models\JobVacancy;
use App\Models\RecruitmentApplication;
use App\Models\Staff;
use App\Models\StaffContract;
use App\Services\ContractService;
use App\Services\StaffLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ContractService::class);

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

    public function test_generate_contract_number_creates_unique_number(): void
    {
        $number = $this->service->generateContractNumber();

        $this->assertMatchesRegularExpression('/^CON\/\d{4}\/\d{5}$/', $number);
    }

    public function test_create_contract_creates_record(): void
    {
        $staff = Staff::factory()->create([
            'department_id' => $this->department->id,
            'job_title' => 'Test Staff',
            'date_of_birth' => '1990-01-01',
            'gender' => 'Male',
            'email' => 'test@test.com',
            'phone_number' => '0711000000',
            'employment_category' => 'contract',
            'employment_start_date' => now()->toDateString(),
            'gross_monthly_salary' => 50000,
            'employment_status' => 'active',
        ]);

        $contract = $this->service->createContract($staff->id, [
            'contract_type' => 'contract',
            'job_title' => 'Test Staff',
            'department_id' => $this->department->id,
            'gross_salary' => 50000,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'is_renewable' => 1,
            'probation_end_date' => now()->addMonths(3)->toDateString(),
        ], 1);

        $this->assertDatabaseHas('staff_contracts', [
            'id' => $contract->id,
            'staff_id' => $staff->id,
            'contract_type' => 'contract',
            'renewal_status' => 'pending',
            'is_signed' => 0,
        ]);
    }

    public function test_renew_contract_creates_new_contract(): void
    {
        $staff = Staff::factory()->create([
            'department_id' => $this->department->id,
            'job_title' => 'Test Staff',
            'date_of_birth' => '1990-01-01',
            'gender' => 'Male',
            'email' => 'test2@test.com',
            'phone_number' => '0711000000',
            'employment_category' => 'contract',
            'employment_start_date' => now()->toDateString(),
            'gross_monthly_salary' => 50000,
            'employment_status' => 'active',
        ]);

        $contract = StaffContract::create([
            'contract_number' => 'CON/2026/00001',
            'staff_id' => $staff->id,
            'contract_type' => 'contract',
            'job_title' => 'Test Staff',
            'department_id' => $this->department->id,
            'gross_salary' => 50000,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'is_renewable' => 1,
            'renewal_status' => 'pending',
            'probation_status' => 'not_applicable',
        ]);

        $renewed = $this->service->renewContract($contract->id, [
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->addMonths(13)->toDateString(),
            'gross_salary' => 55000,
        ], 1);

        $this->assertDatabaseHas('staff_contracts', [
            'id' => $renewed->id,
            'staff_id' => $staff->id,
            'contract_type' => 'contract',
        ]);

        $this->assertDatabaseHas('staff_contracts', [
            'id' => $contract->id,
            'renewal_status' => 'renewed',
            'new_contract_id' => $renewed->id,
        ]);
    }

    public function test_convert_to_permanent_creates_permanent_contract(): void
    {
        $staff = Staff::factory()->create([
            'department_id' => $this->department->id,
            'job_title' => 'Test Staff',
            'date_of_birth' => '1990-01-01',
            'gender' => 'Male',
            'email' => 'test3@test.com',
            'phone_number' => '0711000000',
            'employment_category' => 'contract',
            'employment_start_date' => now()->toDateString(),
            'gross_monthly_salary' => 50000,
            'employment_status' => 'active',
        ]);

        $contract = StaffContract::create([
            'contract_number' => 'CON/2026/00002',
            'staff_id' => $staff->id,
            'contract_type' => 'contract',
            'job_title' => 'Test Staff',
            'department_id' => $this->department->id,
            'gross_salary' => 50000,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'is_renewable' => 0,
            'renewal_status' => 'pending',
            'probation_status' => 'not_applicable',
        ]);

        $permanent = $this->service->convertToPermanent($contract->id, 1);

        $this->assertDatabaseHas('staff_contracts', [
            'id' => $permanent->id,
            'contract_type' => 'permanent',
            'end_date' => null,
        ]);

        $this->assertDatabaseHas('staff', [
            'id' => $staff->id,
            'employment_category' => 'permanent',
        ]);
    }

    public function test_get_expiry_alerts_returns_contracts_and_licenses(): void
    {
        $staff = Staff::factory()->create([
            'department_id' => $this->department->id,
            'job_title' => 'Test Staff',
            'date_of_birth' => '1990-01-01',
            'gender' => 'Male',
            'email' => 'test4@test.com',
            'phone_number' => '0711000000',
            'employment_category' => 'contract',
            'employment_start_date' => now()->toDateString(),
            'gross_monthly_salary' => 50000,
            'employment_status' => 'active',
        ]);

        StaffContract::create([
            'contract_number' => 'CON/2026/00003',
            'staff_id' => $staff->id,
            'contract_type' => 'contract',
            'job_title' => 'Test Staff',
            'department_id' => $this->department->id,
            'gross_salary' => 50000,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
            'is_renewable' => 1,
            'renewal_status' => 'pending',
            'probation_status' => 'not_applicable',
        ]);

        $alerts = $this->service->getExpiryAlerts(30);

        $this->assertTrue($alerts['contracts']->isNotEmpty());
        $this->assertTrue($alerts['contracts']->first()->isExpiringSoon(30));
    }
}
