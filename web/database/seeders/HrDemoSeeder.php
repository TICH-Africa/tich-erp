<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\JobVacancy;
use App\Models\RecruitmentApplication;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffContract;
use App\Models\StaffOnboarding;
use App\Models\User;
use App\Services\DepartmentModuleService;
use App\Services\RBACService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class HrDemoSeeder extends Seeder
{
    public function run(): void
    {
        $hrDepartmentId = DB::table('departments')->where('dept_code', 'HR')->value('id')
            ?? DB::table('departments')->where('id', 9)->value('id')
            ?? DB::table('departments')->whereNull('parent_dept_id')->value('id');

        $financeDepartmentId = DB::table('departments')->where('dept_code', 'FIN')->value('id')
            ?? DB::table('departments')->where('dept_name', 'like', '%Finance%')->value('id')
            ?? $hrDepartmentId;

        $academicDepartmentId = DB::table('departments')->where('dept_code', 'CHS')->value('id')
            ?? DB::table('departments')->where('dept_category', 'academic')->whereNotNull('parent_dept_id')->value('id')
            ?? $hrDepartmentId;

        $campusId = DB::table('campuses')->where('is_active', 1)->value('id');

        if (! $hrDepartmentId) {
            return;
        }

        $hrManagerStaff = $this->ensureHrManager($hrDepartmentId, $campusId);
        $this->ensureDepartmentModules($hrDepartmentId);
        $this->call(KenyaPayrollTaxSeeder::class);
        $this->call(ContractorPayrollDemoSeeder::class);
        $this->seedPensionSchemes();
        $this->seedLeaveTypes();
        $extraStaff = $this->seedStaffMembers($hrDepartmentId, $financeDepartmentId, $academicDepartmentId, $campusId);
        $vacancies = $this->seedVacancies($hrManagerStaff->id, [
            $hrDepartmentId,
            $financeDepartmentId,
            $academicDepartmentId,
        ]);
        $this->seedApplications($vacancies, $hrManagerStaff->id);
        $this->seedContracts($extraStaff, $hrDepartmentId, $financeDepartmentId);
        $this->seedOnboarding($extraStaff);
        $this->seedLeaveRequests($extraStaff);
        $this->seedQualifications($extraStaff);
    }

    private function ensureHrManager(int $departmentId, ?int $campusId): Staff
    {
        $emails = $this->staffEmails('Grace', 'Wanjiku');

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
            ['employee_number' => 'EMP-HR-001'],
            [
                'title' => 'Ms.',
                'first_name' => 'Grace',
                'surname' => 'Wanjiku',
                'date_of_birth' => '1988-03-12',
                'gender' => 'female',
                'primary_email' => $emails['primary_email'],
                'organisation_email' => $emails['organisation_email'],
                'phone_number' => '0722000101',
                'department_id' => $departmentId,
                'campus_id' => $campusId,
                'job_title' => 'HR Manager',
                'employment_category' => 'permanent',
                'employment_start_date' => '2019-04-01',
                'employment_status' => 'active',
                'gross_monthly_salary' => 95000,
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

        $roleId = Role::query()->where('role_name', 'HR Manager')->value('id');
        if ($roleId) {
            app(RBACService::class)->assignRoleToUser($user, $roleId, null, $departmentId);
        }

        return $staff;
    }

    private function ensureDepartmentModules(int $hrDepartmentId): void
    {
        $department = Department::query()->find($hrDepartmentId);

        if (! $department) {
            return;
        }

        app(DepartmentModuleService::class)->syncModules($department, ['hr']);
    }

    private function seedPensionSchemes(): void
    {
        $schemes = [
            ['scheme_code' => 'NSSF-DC', 'scheme_name' => 'NSSF Defined Contribution', 'scheme_type' => 'defined_contribution', 'employer_contribution_pct' => 6, 'employee_contribution_pct' => 6],
            ['scheme_code' => 'TICH-PF', 'scheme_name' => 'TICH Provident Fund', 'scheme_type' => 'provident_fund', 'employer_contribution_pct' => 10, 'employee_contribution_pct' => 5],
        ];

        foreach ($schemes as $scheme) {
            DB::table('pension_schemes')->updateOrInsert(
                ['scheme_code' => $scheme['scheme_code']],
                [...$scheme, 'is_active' => 1]
            );
        }
    }

    private function seedLeaveTypes(): void
    {
        $types = [
            ['leave_code' => 'ANNUAL', 'leave_name' => 'Annual Leave', 'days_allowed_per_year' => 21],
            ['leave_code' => 'SICK', 'leave_name' => 'Sick Leave', 'days_allowed_per_year' => 14, 'requires_medical_certificate' => 1],
            ['leave_code' => 'MAT', 'leave_name' => 'Maternity Leave', 'days_allowed_per_year' => 90, 'gender_restriction' => 'female_only'],
            ['leave_code' => 'COMP', 'leave_name' => 'Compassionate Leave', 'days_allowed_per_year' => 7],
        ];

        foreach ($types as $type) {
            DB::table('leave_types')->updateOrInsert(
                ['leave_code' => $type['leave_code']],
                [
                    'leave_name' => $type['leave_name'],
                    'days_allowed_per_year' => $type['days_allowed_per_year'],
                    'is_payable' => 1,
                    'requires_medical_certificate' => $type['requires_medical_certificate'] ?? 0,
                    'requires_approval_hod' => 1,
                    'requires_approval_hr' => 1,
                    'gender_restriction' => $type['gender_restriction'] ?? 'any',
                    'min_service_months' => 0,
                    'carry_forward_days' => $type['leave_code'] === 'ANNUAL' ? 5 : 0,
                    'is_active' => 1,
                ]
            );
        }
    }

    private function seedStaffMembers(int $hrDeptId, int $financeDeptId, int $academicDeptId, ?int $campusId): array
    {
        $members = [
            ['employee_number' => 'EMP-HR-002', 'first_name' => 'Peter', 'surname' => 'Otieno', 'job_title' => 'HR Officer', 'department_id' => $hrDeptId, 'employment_category' => 'permanent'],
            ['employee_number' => 'EMP-HR-003', 'first_name' => 'Mary', 'surname' => 'Akinyi', 'job_title' => 'Recruitment Assistant', 'department_id' => $hrDeptId, 'employment_category' => 'contract'],
            ['employee_number' => 'EMP-FIN-001', 'first_name' => 'David', 'surname' => 'Kiprop', 'job_title' => 'Finance Officer', 'department_id' => $financeDeptId, 'employment_category' => 'permanent'],
            ['employee_number' => 'EMP-HR-004', 'first_name' => 'Faith', 'surname' => 'Mwangi', 'job_title' => 'Payroll Clerk', 'department_id' => $hrDeptId, 'employment_category' => 'contract'],
        ];

        $created = [];

        foreach ($members as $member) {
            $emails = $this->staffEmails($member['first_name'], $member['surname']);

            $created[] = Staff::query()->firstOrCreate(
                ['employee_number' => $member['employee_number']],
                [
                    'title' => 'Mr.',
                    'first_name' => $member['first_name'],
                    'surname' => $member['surname'],
                    'date_of_birth' => '1990-01-15',
                    'gender' => 'male',
                    'primary_email' => $emails['primary_email'],
                    'organisation_email' => $emails['organisation_email'],
                    'phone_number' => '07'.fake()->numerify('########'),
                    'department_id' => $member['department_id'],
                    'campus_id' => $campusId,
                    'job_title' => $member['job_title'],
                    'employment_category' => $member['employment_category'],
                    'payroll_scheme' => $member['payroll_scheme'] ?? 'employee',
                    'employment_start_date' => '2023-06-01',
                    'employment_status' => 'active',
                    'gross_monthly_salary' => 65000,
                    'is_teaching_staff' => 0,
                ]
            );
        }

        return $created;
    }

    private function seedVacancies(int $createdByStaffId, array $departmentIds): array
    {
        $templates = [
            [
                'vacancy_number' => 'VAC-2026-001',
                'job_title' => 'Human Resource Officer',
                'employment_type' => 'permanent',
                'position_grade' => 'H5',
                'slots_available' => 1,
                'min_qualification' => 'Degree',
                'is_published' => 1,
                'published_on' => now()->subDays(10)->toDateString(),
            ],
            [
                'vacancy_number' => 'VAC-2026-002',
                'job_title' => 'Recruitment & Onboarding Specialist',
                'employment_type' => 'contract',
                'position_grade' => 'H4',
                'slots_available' => 2,
                'min_qualification' => 'Diploma',
                'is_published' => 1,
                'published_on' => now()->subDays(5)->toDateString(),
            ],
            [
                'vacancy_number' => 'VAC-2026-003',
                'job_title' => 'Finance Assistant',
                'employment_type' => 'permanent',
                'position_grade' => 'F4',
                'slots_available' => 1,
                'min_qualification' => 'Degree',
                'is_published' => 1,
                'published_on' => now()->subDays(3)->toDateString(),
            ],
            [
                'vacancy_number' => 'VAC-2026-004',
                'job_title' => 'Community Health Tutor',
                'employment_type' => 'contract',
                'position_grade' => 'T5',
                'slots_available' => 3,
                'min_qualification' => 'Degree',
                'is_published' => 1,
                'published_on' => now()->subDays(7)->toDateString(),
            ],
            [
                'vacancy_number' => 'VAC-2026-005',
                'job_title' => 'HR Intern',
                'employment_type' => 'intern',
                'position_grade' => 'H1',
                'slots_available' => 2,
                'min_qualification' => 'Certificate',
                'is_published' => 0,
                'published_on' => null,
            ],
            [
                'vacancy_number' => 'VAC-2026-006',
                'job_title' => 'Payroll Administrator',
                'employment_type' => 'permanent',
                'position_grade' => 'H4',
                'slots_available' => 1,
                'min_qualification' => 'Diploma',
                'is_published' => 1,
                'published_on' => now()->subDay()->toDateString(),
            ],
        ];

        $vacancies = [];

        foreach ($templates as $index => $template) {
            $departmentId = $departmentIds[$index % count($departmentIds)];

            $vacancies[] = JobVacancy::query()->updateOrCreate(
                ['vacancy_number' => $template['vacancy_number']],
                [
                    ...$template,
                    'department_id' => $departmentId,
                    'job_description' => 'Join our team as '.$template['job_title'].' and contribute to institutional excellence at TICH.',
                    'requirements' => 'Relevant qualification, strong communication skills, and experience in a similar role.',
                    'responsibilities' => 'Execute departmental duties, maintain records, support team objectives, and uphold institutional policies.',
                    'salary_scale' => 'As per institution salary scale',
                    'benefits' => 'Medical cover, pension scheme, professional development support.',
                    'closing_date' => now()->addDays(30 + ($index * 5))->toDateString(),
                    'is_closed' => 0,
                    'closes_automatically' => 1,
                    'slots_filled' => 0,
                    'created_by' => $createdByStaffId,
                ]
            );
        }

        return $vacancies;
    }

    private function seedApplications(array $vacancies, int $reviewerStaffId): void
    {
        $statuses = ['submitted', 'under_review', 'shortlisted', 'rejected', 'offered'];
        $names = [
            ['John', 'Kamau'], ['Sarah', 'Njeri'], ['Brian', 'Ouma'], ['Lucy', 'Chebet'],
            ['Kevin', 'Mutua'], ['Ann', 'Wambui'], ['Collins', 'Mboya'], ['Ruth', 'Achieng'],
            ['Ian', 'Wekesa'], ['Diana', 'Mutiso'], ['Samuel', 'Kiptoo'], ['Grace', 'Adongo'],
        ];

        $counter = 1;

        foreach ($vacancies as $vacancy) {
            $applicationsForVacancy = $vacancy->is_published ? 3 : 1;

            for ($i = 0; $i < $applicationsForVacancy; $i++) {
                $name = $names[($counter - 1) % count($names)];
                $fullName = $name[0].' '.$name[1];
                $status = $statuses[($counter - 1) % count($statuses)];
                $applicationNumber = sprintf('APP/2026/%05d', $counter);

                RecruitmentApplication::query()->updateOrCreate(
                    ['application_number' => $applicationNumber],
                    [
                        'vacancy_id' => $vacancy->id,
                        'full_name' => $fullName,
                        'id_number' => fake()->numerify('#########'),
                        'date_of_birth' => '1995-05-20',
                        'gender' => $counter % 2 === 0 ? 'female' : 'male',
                        'email' => strtolower(str_replace(' ', '.', $fullName)).'@example.com',
                        'phone_number' => '07'.fake()->numerify('########'),
                        'postal_address' => 'P.O. Box '.fake()->numerify('####').', Nairobi',
                        'physical_address' => 'Nairobi, Kenya',
                        'highest_qualification' => fake()->randomElement(['Diploma', 'Degree', 'Masters']),
                        'institution' => fake()->randomElement(['University of Nairobi', 'Kenyatta University', 'Maseno University']),
                        'year_completed' => fake()->numberBetween(2015, 2023),
                        'years_of_experience' => fake()->numberBetween(1, 10),
                        'current_organization' => fake()->company(),
                        'area_of_specialization' => $vacancy->job_title,
                        'cv_file_path' => 'hr/applications/demo-cv.pdf',
                        'cover_letter_file_path' => 'hr/applications/demo-cover.pdf',
                        'is_shortlisted' => in_array($status, ['shortlisted', 'offered'], true) ? 1 : 0,
                        'shortlist_status' => match ($status) {
                            'rejected' => 'rejected',
                            'shortlisted', 'offered' => 'shortlisted',
                            default => 'pending',
                        },
                        'offer_made' => $status === 'offered' ? 1 : 0,
                        'offer_accepted' => $status === 'offered' ? 1 : 0,
                        'application_source' => fake()->randomElement(['portal', 'referral', 'walk_in']),
                        'status' => $status,
                        'reviewed_by' => in_array($status, ['under_review', 'shortlisted', 'rejected', 'offered'], true) ? $reviewerStaffId : null,
                        'reviewed_at' => in_array($status, ['under_review', 'shortlisted', 'rejected', 'offered'], true) ? now()->subDays(2) : null,
                        'decision' => match ($status) {
                            'shortlisted', 'offered' => 'shortlisted',
                            'rejected' => 'rejected',
                            default => 'pending',
                        },
                        'is_viewed' => $status !== 'submitted' ? 1 : 0,
                    ]
                );

                $counter++;
            }
        }
    }

    private function seedContracts(array $staffMembers, int $hrDeptId, int $financeDeptId): void
    {
        $contracts = [
            [
                'contract_number' => 'CON-2024-001',
                'staff_index' => 0,
                'department_id' => $hrDeptId,
                'contract_type' => 'permanent',
                'job_title' => 'HR Officer',
                'gross_salary' => 72000,
                'start_date' => '2023-06-01',
                'end_date' => null,
                'renewal_status' => 'renewed',
                'is_signed' => 1,
            ],
            [
                'contract_number' => 'CON-2024-002',
                'staff_index' => 1,
                'department_id' => $hrDeptId,
                'contract_type' => 'fixed_term',
                'job_title' => 'Recruitment Assistant',
                'gross_salary' => 48000,
                'start_date' => '2024-01-01',
                'end_date' => now()->addDays(20)->toDateString(),
                'renewal_status' => 'pending',
                'is_signed' => 1,
                'is_renewable' => 1,
            ],
            [
                'contract_number' => 'CON-2024-003',
                'staff_index' => 2,
                'department_id' => $financeDeptId,
                'contract_type' => 'permanent',
                'job_title' => 'Finance Officer',
                'gross_salary' => 78000,
                'start_date' => '2022-09-01',
                'end_date' => null,
                'renewal_status' => 'renewed',
                'is_signed' => 1,
            ],
            [
                'contract_number' => 'CON-2025-004',
                'staff_index' => 3,
                'department_id' => $hrDeptId,
                'contract_type' => 'fixed_term',
                'job_title' => 'Payroll Clerk',
                'gross_salary' => 42000,
                'start_date' => '2025-01-01',
                'end_date' => now()->addDays(45)->toDateString(),
                'renewal_status' => 'pending',
                'is_signed' => 0,
                'is_renewable' => 1,
            ],
        ];

        foreach ($contracts as $contract) {
            $staff = $staffMembers[$contract['staff_index']] ?? null;

            if (! $staff) {
                continue;
            }

            StaffContract::query()->updateOrCreate(
                ['contract_number' => $contract['contract_number']],
                [
                    'staff_id' => $staff->id,
                    'department_id' => $contract['department_id'],
                    'contract_type' => $contract['contract_type'],
                    'job_title' => $contract['job_title'],
                    'gross_salary' => $contract['gross_salary'],
                    'start_date' => $contract['start_date'],
                    'end_date' => $contract['end_date'],
                    'is_renewable' => $contract['is_renewable'] ?? 0,
                    'renewal_status' => $contract['renewal_status'],
                    'probation_status' => 'not_applicable',
                    'is_signed' => $contract['is_signed'],
                    'signed_date' => $contract['is_signed'] ? now()->subMonths(2)->toDateString() : null,
                ]
            );
        }
    }

    private function seedOnboarding(array $staffMembers): void
    {
        $records = [
            [
                'onboarding_number' => 'ONB-2026-001',
                'staff_index' => 1,
                'current_step' => 'documents',
                'status' => 'in_progress',
                'completed_steps' => ['biodata', 'employment_terms', 'banking'],
            ],
            [
                'onboarding_number' => 'ONB-2026-002',
                'staff_index' => 3,
                'current_step' => 'orientation',
                'status' => 'pending_hr_review',
                'completed_steps' => ['biodata', 'employment_terms', 'banking', 'documents', 'contract'],
            ],
        ];

        foreach ($records as $record) {
            $staff = $staffMembers[$record['staff_index']] ?? null;

            if (! $staff) {
                continue;
            }

            StaffOnboarding::query()->updateOrCreate(
                ['onboarding_number' => $record['onboarding_number']],
                [
                    'staff_id' => $staff->id,
                    'current_step' => $record['current_step'],
                    'status' => $record['status'],
                    'completed_steps' => $record['completed_steps'],
                    'missing_documents' => ['good_conduct'],
                ]
            );
        }
    }

    private function seedLeaveRequests(array $staffMembers): void
    {
        $annualLeaveId = DB::table('leave_types')->where('leave_code', 'ANNUAL')->value('id');
        $sickLeaveId = DB::table('leave_types')->where('leave_code', 'SICK')->value('id');

        if (! $annualLeaveId || empty($staffMembers)) {
            return;
        }

        $staff = $staffMembers[0];

        DB::table('leave_balances')->updateOrInsert(
            ['staff_id' => $staff->id, 'leave_type_id' => $annualLeaveId, 'year' => now()->year],
            [
                'entitled_days' => 21,
                'days_taken' => 3,
                'days_pending' => 2,
                'balance_days' => 16,
                'last_updated' => now()->toDateString(),
            ]
        );

        DB::table('leave_requests')->updateOrInsert(
            ['leave_number' => 'LV-2026-001'],
            [
                'staff_id' => $staff->id,
                'leave_type_id' => $annualLeaveId,
                'start_date' => now()->addDays(14)->toDateString(),
                'end_date' => now()->addDays(18)->toDateString(),
                'days_requested' => 5,
                'reason' => 'Family commitments',
                'hod_approval_status' => 'approved',
                'hr_approval_status' => 'pending',
                'overall_status' => 'pending_hr',
            ]
        );

        if ($sickLeaveId) {
            DB::table('leave_requests')->updateOrInsert(
                ['leave_number' => 'LV-2026-002'],
                [
                    'staff_id' => $staffMembers[2]->id ?? $staff->id,
                    'leave_type_id' => $sickLeaveId,
                    'start_date' => now()->subDays(1)->toDateString(),
                    'end_date' => now()->addDays(2)->toDateString(),
                    'days_requested' => 4,
                    'reason' => 'Medical rest',
                    'hod_approval_status' => 'approved',
                    'hr_approval_status' => 'approved',
                    'overall_status' => 'approved',
                ]
            );

            $onLeaveStaff = $staffMembers[2] ?? $staff;

            DB::table('leave_balances')->updateOrInsert(
                ['staff_id' => $onLeaveStaff->id, 'leave_type_id' => $sickLeaveId, 'year' => now()->year],
                [
                    'entitled_days' => 14,
                    'days_taken' => 4,
                    'days_pending' => 0,
                    'balance_days' => 10,
                    'last_updated' => now()->toDateString(),
                ]
            );
        }
    }

    private function seedQualifications(array $staffMembers): void
    {
        if (empty($staffMembers)) {
            return;
        }

        $staff = $staffMembers[0];

        DB::table('staff_qualifications')->updateOrInsert(
            ['staff_id' => $staff->id, 'qualification_name' => 'Bachelor of Human Resource Management'],
            [
                'qualification_type' => 'degree',
                'institution' => 'Kenyatta University',
                'country' => 'Kenya',
                'year_completed' => 2014,
                'grade_or_class' => 'Second Class Upper',
                'is_verified' => 1,
            ]
        );

        DB::table('staff_professional_licenses')->updateOrInsert(
            ['staff_id' => $staff->id, 'license_number' => 'IHRM-2018-4521'],
            [
                'license_type' => 'other',
                'issuing_body' => 'Institute of Human Resource Management',
                'issue_date' => '2018-06-01',
                'expiry_date' => now()->addDays(25)->toDateString(),
                'is_expired' => 0,
                'is_verified' => 1,
            ]
        );
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
