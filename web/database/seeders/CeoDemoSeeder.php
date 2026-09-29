<?php

namespace Database\Seeders;

use App\Models\Administration\BudgetRequest;
use App\Models\Administration\PlanningCycle;
use App\Models\AcademicProgram;
use App\Models\CurriculumVersion;
use App\Models\Department;
use App\Models\Finance\FinancePolicy;
use App\Models\Finance\FinancePolicySignoff;
use App\Models\Me\MePolicy;
use App\Models\Me\MePolicySignoff;
use App\Models\Me\MeQuarterlyReport;
use App\Models\ProcurementRequisition;
use App\Models\Qa\IqaAssessment;
use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use App\Services\Administration\AdministrationService;
use App\Services\Qa\IqaAssessmentSchema;
use App\Services\RBACService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Populates CEO portal queues, policy sign-offs, and review samples for local/staging demos.
 */
class CeoDemoSeeder extends Seeder
{
    public const CEO_EMAIL = 'ceo@tich.ac.ke';

    public const CEO_PASSWORD = 'Password123!';

    public const DEMO_BUDGET_TITLE_PREFIX = 'CEO Demo —';

    public const DEMO_REQ_PREFIX = 'REQ-CEO-DEMO-';

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('CeoDemoSeeder skipped in production.');

            return;
        }

        $ceoUser = $this->ensureCeoUser();
        $actorUserId = (int) (User::query()->where('email', 'admin@tich.ac.ke')->value('id') ?? $ceoUser->id);
        $financeStaff = Staff::query()->where('employee_number', 'EMP-FIN-001')->first()
            ?? Staff::query()->whereNotNull('user_id')->first();
        $financeUserId = (int) ($financeStaff?->user_id ?? $actorUserId);
        $financeStaffId = (int) ($financeStaff?->id ?? Staff::query()->value('id'));

        $budgets = $this->seedExecutiveBudgets($actorUserId, $financeUserId);
        $curriculum = $this->seedPendingCurriculum($actorUserId);
        $procurement = $this->seedProcurementQueue($financeStaffId);
        $meReports = $this->seedMeReportsForCeo();
        $this->ensurePoliciesAwaitingCeoSign();
        $quality = $this->seedPublishedIqaAssessments($actorUserId);

        $this->command?->info(sprintf(
            'CEO demo ready: user %s (%s) | budgets executive_review: %d | curriculum pending_ceo: %d | procurement pending CEO: %d | M&E awaiting sign: %d | published IQA (30d): %d',
            self::CEO_EMAIL,
            self::CEO_PASSWORD,
            $budgets,
            $curriculum,
            $procurement,
            $meReports,
            $quality,
        ));
        $this->command?->info('Open /ceo after signing in as the CEO user (Super Admin also works).');
    }

    private function ensureCeoUser(): User
    {
        $roleId = Role::query()->where('role_name', 'CEO')->value('id');
        abort_unless($roleId, 500, 'CEO role missing — run ProductionEssentialSeeder / SyncDefaultRolesSeeder first.');

        $campusId = DB::table('campuses')->where('is_active', 1)->value('id');
        $deptId = Department::query()->where('dept_code', 'ADM')->value('id');

        $user = User::query()->firstOrCreate(
            ['email' => self::CEO_EMAIL],
            [
                'user_type' => 'staff',
                'password_hash' => Hash::make(self::CEO_PASSWORD),
                'is_active' => 1,
                'mfa_enabled' => false,
                'mfa_verified' => true,
            ]
        );

        $user->update([
            'user_type' => 'staff',
            'password_hash' => Hash::make(self::CEO_PASSWORD),
            'is_active' => 1,
            'mfa_enabled' => false,
            'mfa_verified' => true,
        ]);

        $staff = Staff::query()->firstOrCreate(
            ['employee_number' => 'EMP-CEO-001'],
            [
                'title' => 'Dr.',
                'first_name' => 'Grace',
                'surname' => 'Mwangi',
                'date_of_birth' => '1978-06-12',
                'gender' => 'female',
                'primary_email' => 'grace.mwangi@gmail.com',
                'organisation_email' => Staff::organisationEmailFromName('Grace', 'Mwangi'),
                'phone_number' => '0722111000',
                'department_id' => $deptId,
                'campus_id' => $campusId,
                'job_title' => 'Chief Executive Officer',
                'employment_category' => 'permanent',
                'employment_start_date' => '2018-01-15',
                'employment_status' => 'active',
                'gross_monthly_salary' => 420000,
                'is_teaching_staff' => 0,
                'user_id' => $user->id,
            ]
        );

        $staff->update([
            'user_id' => $user->id,
            'department_id' => $deptId,
            'employment_status' => 'active',
            'job_title' => 'Chief Executive Officer',
        ]);

        $user->update(['staff_id' => $staff->id]);

        $hasRole = DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where('role_id', $roleId)
            ->exists();

        if (! $hasRole) {
            app(RBACService::class)->assignRoleToUser($user, (int) $roleId, null, $deptId);
        }

        return $user->fresh();
    }

    private function seedExecutiveBudgets(int $actorUserId, int $financeUserId): int
    {
        if (! Schema::hasTable('admin_budget_requests')) {
            return 0;
        }

        $cycle = PlanningCycle::query()->orderByDesc('id')->first();
        if (! $cycle) {
            return 0;
        }

        $admin = app(AdministrationService::class);
        $samples = [
            [
                'department_code' => 'ICTO',
                'title' => self::DEMO_BUDGET_TITLE_PREFIX.' ICT infrastructure FY'.$cycle->fiscal_year,
                'amount' => 4200000.00,
                'justification' => 'Campus network refresh, LMS capacity, and cybersecurity controls for the academic year.',
            ],
            [
                'department_code' => 'QA',
                'title' => self::DEMO_BUDGET_TITLE_PREFIX.' Quality assurance & audit FY'.$cycle->fiscal_year,
                'amount' => 890000.00,
                'justification' => 'External audit support, compliance tooling, and departmental assessment cycles.',
            ],
            [
                'department_code' => 'MNE',
                'title' => self::DEMO_BUDGET_TITLE_PREFIX.' M&E institutional reporting FY'.$cycle->fiscal_year,
                'amount' => 1250000.00,
                'justification' => 'PIME dashboards, quarterly verification, and results-framework coordination.',
            ],
        ];

        foreach ($samples as $sample) {
            $deptId = Department::query()->where('dept_code', $sample['department_code'])->value('id');
            if (! $deptId) {
                continue;
            }

            $existing = BudgetRequest::query()
                ->where('title', $sample['title'])
                ->where('planning_cycle_id', $cycle->id)
                ->first();

            if (! $existing) {
                $existing = $admin->createBudgetRequest([
                    'planning_cycle_id' => $cycle->id,
                    'department_id' => $deptId,
                    'title' => $sample['title'],
                    'framework' => 'standard',
                    'budget_type' => 'annual',
                    'requested_amount' => $sample['amount'],
                    'justification' => $sample['justification'],
                    'standard_line_items' => [
                        ['item' => 'Operational expenditure (demo line)', 'quantity' => 1, 'unit_price' => $sample['amount'], 'total' => $sample['amount']],
                    ],
                ], $actorUserId);
            }

            if ($existing->status === 'submitted') {
                try {
                    $admin->routeBudgetToFinance($existing, $actorUserId);
                    $existing = $existing->fresh();
                } catch (\Throwable) {
                    $existing->update(['status' => 'finance_review']);
                    $existing = $existing->fresh();
                }
            }

            if ($existing->status === 'finance_review') {
                try {
                    $admin->verifyBudgetByFinance(
                        $existing,
                        round((float) $existing->requested_amount * 0.97, 2),
                        $financeUserId,
                        'Finance verified for CEO demo queue.'
                    );
                } catch (\Throwable) {
                    $existing->update([
                        'status' => 'executive_review',
                        'verified_amount' => round((float) $existing->requested_amount * 0.97, 2),
                        'finance_verified_by' => $financeUserId,
                        'finance_verified_at' => now()->subDays(2),
                    ]);
                }
            }
        }

        BudgetRequest::query()
            ->whereIn('status', ['finance_review'])
            ->where('title', 'like', self::DEMO_BUDGET_TITLE_PREFIX.'%')
            ->update([
                'status' => 'executive_review',
                'verified_amount' => DB::raw('COALESCE(verified_amount, requested_amount * 0.97)'),
                'finance_verified_at' => now()->subDays(1),
            ]);

        return BudgetRequest::query()->where('status', 'executive_review')->count();
    }

    private function seedPendingCurriculum(int $registrarUserId): int
    {
        if (! Schema::hasTable('curriculum_versions') || ! Schema::hasTable('academic_programs')) {
            return 0;
        }

        $yearId = DB::table('academic_years')->where('is_current', 1)->value('id')
            ?? DB::table('academic_years')->orderByDesc('id')->value('id');

        $programRows = [
            ['code' => 'CHD', 'label' => 'CHD Jan 2027 intake', 'intake_year' => 2027, 'intake_month' => 1],
            ['code' => 'HMD', 'label' => 'HMD Sep 2026 intake', 'intake_year' => 2026, 'intake_month' => 9],
        ];

        foreach ($programRows as $row) {
            $program = AcademicProgram::query()->where('program_code', $row['code'])->first();
            if (! $program || ! $yearId) {
                continue;
            }

            $version = CurriculumVersion::query()->firstOrCreate(
                [
                    'program_id' => $program->id,
                    'version_label' => $row['label'],
                ],
                [
                    'academic_year_id' => $yearId,
                    'intake_year' => $row['intake_year'],
                    'intake_month' => $row['intake_month'],
                    'version_number' => (int) CurriculumVersion::query()->where('program_id', $program->id)->max('version_number') + 1,
                    'curriculum_format' => 'semester',
                    'status' => 'pending_ceo',
                    'notes' => 'CEO demo — registrar cleared; awaiting executive publication.',
                    'created_by' => $registrarUserId,
                    'submitted_at' => now()->subDays(5),
                    'submitted_by' => $registrarUserId,
                    'registrar_approved_at' => now()->subDays(3),
                    'registrar_approved_by' => $registrarUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            if ($version->status !== 'pending_ceo') {
                $version->update([
                    'status' => 'pending_ceo',
                    'registrar_approved_at' => $version->registrar_approved_at ?? now()->subDays(3),
                    'registrar_approved_by' => $version->registrar_approved_by ?? $registrarUserId,
                ]);
            }

            if ($program->status !== 'pending_ceo') {
                $program->update(['status' => 'pending_ceo']);
            }

            $this->copyCurriculumUnitsFromReference((int) $version->id, (int) $program->id);
        }

        return CurriculumVersion::query()->where('status', 'pending_ceo')->count();
    }

    private function copyCurriculumUnitsFromReference(int $targetVersionId, int $programId): void
    {
        if (! Schema::hasTable('curriculum_version_units')) {
            return;
        }

        if (DB::table('curriculum_version_units')->where('curriculum_version_id', $targetVersionId)->exists()) {
            return;
        }

        $sourceVersionId = DB::table('curriculum_versions')
            ->where('program_id', $programId)
            ->where('id', '!=', $targetVersionId)
            ->orderByDesc('id')
            ->value('id');

        if (! $sourceVersionId) {
            $sourceVersionId = DB::table('curriculum_versions')->orderByDesc('id')->value('id');
        }

        if (! $sourceVersionId) {
            return;
        }

        $units = DB::table('curriculum_version_units')->where('curriculum_version_id', $sourceVersionId)->get();
        foreach ($units as $unit) {
            DB::table('curriculum_version_units')->insert([
                'curriculum_version_id' => $targetVersionId,
                'unit_id' => $unit->unit_id,
                'semester' => $unit->semester,
                'block_id' => $unit->block_id,
                'is_compulsory' => $unit->is_compulsory,
                'display_order' => $unit->display_order,
                'priority' => $unit->priority,
                'credit_hours' => $unit->credit_hours,
                'contact_hours' => $unit->contact_hours,
                'total_learning_hours' => $unit->total_learning_hours,
            ]);
        }
    }

    private function seedProcurementQueue(int $staffId): int
    {
        if (! Schema::hasTable('procurement_requisitions')) {
            return 0;
        }

        $rows = [
            ['suffix' => '0001', 'dept' => 'ACAD', 'item' => 'Simulation manikins & clinical skills kits', 'cost' => 318000.00],
            ['suffix' => '0002', 'dept' => 'ICTO', 'item' => 'Server rack UPS & network switches', 'cost' => 562000.00],
        ];

        foreach ($rows as $row) {
            $number = self::DEMO_REQ_PREFIX.$row['suffix'];
            $deptId = Department::query()->where('dept_code', $row['dept'])->value('id');
            if (! $deptId) {
                continue;
            }

            ProcurementRequisition::query()->firstOrCreate(
                ['requisition_number' => $number],
                [
                    'requesting_department_id' => $deptId,
                    'requested_by' => $staffId,
                    'request_date' => now()->subDays(12)->toDateString(),
                    'justification' => 'CEO demo — finance cleared; awaiting executive approval before procurement proceeds.',
                    'requested_item' => $row['item'],
                    'estimated_cost' => $row['cost'],
                    'budget_code' => 'CEO-DEMO-'.date('Y'),
                    'status' => 'finance_approved',
                    'hod_approval_status' => 'approved',
                    'hod_approved_by' => $staffId,
                    'hod_approved_at' => now()->subDays(10),
                    'finance_approval_status' => 'approved',
                    'finance_approved_by' => $staffId,
                    'finance_approved_at' => now()->subDays(7),
                    'ceo_approval_status' => 'pending',
                    'created_at' => now()->subDays(12),
                ]
            );
        }

        return ProcurementRequisition::query()->pendingCeoApproval()->count();
    }

    private function seedMeReportsForCeo(): int
    {
        if (! Schema::hasTable('me_quarterly_reports')) {
            return 0;
        }

        $targets = [
            ['dept_code' => 'FIN', 'days_ago' => 4],
            ['dept_code' => 'ACAD', 'days_ago' => 6],
        ];

        foreach ($targets as $target) {
            $deptId = Department::query()->where('dept_code', $target['dept_code'])->value('id');
            if (! $deptId) {
                continue;
            }

            $report = MeQuarterlyReport::query()
                ->where('department_id', $deptId)
                ->whereIn('status', ['submitted', 'me_verified', 'ceo_delivered'])
                ->orderByDesc('id')
                ->first();

            if (! $report) {
                continue;
            }

            $report->update([
                'status' => 'ceo_delivered',
                'ceo_delivered_at' => now()->subDays($target['days_ago']),
                'ceo_reviewed_at' => null,
                'ceo_reviewed_by' => null,
                'ceo_signature' => null,
                'ceo_notes' => null,
            ]);
        }

        MeQuarterlyReport::query()
            ->where('status', 'ceo_delivered')
            ->whereNull('ceo_reviewed_at')
            ->where('ceo_delivered_at', '<', now()->subDays(45))
            ->update(['ceo_delivered_at' => now()->subDays(5)]);

        return MeQuarterlyReport::query()
            ->where('status', 'ceo_delivered')
            ->whereNull('ceo_reviewed_at')
            ->count();
    }

    private function ensurePoliciesAwaitingCeoSign(): void
    {
        $financePolicy = FinancePolicy::query()->published()->orderByDesc('published_at')->orderByDesc('id')->first();
        if ($financePolicy) {
            FinancePolicySignoff::query()
                ->where('policy_id', $financePolicy->id)
                ->where('signed_role', 'CEO')
                ->delete();
        }

        $mePolicy = MePolicy::query()->published()->orderByDesc('published_at')->orderByDesc('id')->first();
        if ($mePolicy) {
            MePolicySignoff::query()
                ->where('policy_id', $mePolicy->id)
                ->where('signed_role', 'CEO')
                ->delete();
        }
    }

    private function seedPublishedIqaAssessments(int $publisherUserId): int
    {
        if (! Schema::hasTable('iqa_assessments')) {
            return 0;
        }

        $published = IqaAssessment::query()
            ->where('status', IqaAssessment::STATUS_PUBLISHED)
            ->orderByDesc('id')
            ->first();

        if ($published) {
            $published->update([
                'published_at' => $published->published_at && $published->published_at->gte(now()->subDays(30))
                    ? $published->published_at
                    : now()->subDays(2),
            ]);
        } else {
            $draft = IqaAssessment::query()
                ->where('status', IqaAssessment::STATUS_DRAFT)
                ->orderByDesc('id')
                ->first();

            $payload = IqaAssessmentSchema::normalizePayload(
                $draft?->payload ?? IqaAssessmentSchema::emptyPayload()
            );
            $payload['walkthrough'] = range(1, 8);
            $publisher = User::query()->find($publisherUserId);

            if ($draft) {
                $draft->update([
                    'title' => IqaAssessmentSchema::TITLE,
                    'status' => IqaAssessment::STATUS_PUBLISHED,
                    'payload' => $payload,
                    'published_by_user_id' => $publisherUserId,
                    'publisher_name' => $publisher?->displayName() ?? 'QA Officer',
                    'published_at' => now()->subDays(2),
                    'updated_by_user_id' => $publisherUserId,
                    'current_section' => 8,
                ]);
            } else {
                IqaAssessment::query()->create([
                    'title' => IqaAssessmentSchema::TITLE,
                    'assessment_year' => (int) now()->format('Y'),
                    'status' => IqaAssessment::STATUS_PUBLISHED,
                    'current_section' => 8,
                    'payload' => $payload,
                    'created_by_user_id' => $publisherUserId,
                    'updated_by_user_id' => $publisherUserId,
                    'published_by_user_id' => $publisherUserId,
                    'publisher_name' => $publisher?->displayName() ?? 'QA Officer',
                    'published_at' => now()->subDays(2),
                ]);
            }
        }

        return IqaAssessment::query()
            ->where('status', IqaAssessment::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '>=', now()->subDays(30))
            ->count();
    }
}
