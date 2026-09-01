<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AdmissionsReviewService
{
    public function __construct(
        protected RBACService $rbacService,
        protected AuditService $auditService,
        protected ApplicationMailService $mailService,
        protected ApplicationFeeService $feeService,
    ) {}

    /**
     * @return list<int>|null Null means all departments (institution-wide access).
     */
    public function visibleDepartmentIds(User $user): ?array
    {
        if ($this->rbacService->hasInstitutionWideAdmissionsAccess($user)) {
            return null;
        }

        $departmentIds = $this->rbacService->getUserDepartmentIds($user);

        return $departmentIds === [] ? [] : $departmentIds;
    }

    public function canAccessAllDepartments(User $user): bool
    {
        return $this->visibleDepartmentIds($user) === null;
    }

    public function dashboardStats(User $user): array
    {
        $this->backfillMissingHandlingDepartments();

        $base = $this->scopedQuery($user);

        return [
            'pending' => (clone $base)->where('status', 'academic_review')
                ->where('academic_review_status', 'under_review')
                ->count(),
            'approved_pending_payment' => (clone $base)->whereIn('status', ['fee_pending', 'paid'])
                ->where('academic_review_status', 'approved')
                ->count(),
            'admitted' => (clone $base)->where('status', 'admitted')->count(),
            'rejected' => (clone $base)->where('status', 'rejected')->count(),
            'total' => (clone $base)->count(),
        ];
    }

    public function departmentBreakdown(User $user): Collection
    {
        $this->backfillMissingHandlingDepartments();

        $query = DB::table('applicants as a')
            ->join('academic_programs as p', 'a.program_id', '=', 'p.id')
            ->join('departments as d', DB::raw('COALESCE(a.handling_department_id, p.department_id)'), '=', 'd.id')
            ->select(
                'd.id',
                'd.dept_name',
                'd.dept_code',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN a.status = 'academic_review' AND a.academic_review_status = 'under_review' THEN 1 ELSE 0 END) as pending_count")
            )
            ->groupBy('d.id', 'd.dept_name', 'd.dept_code')
            ->orderBy('d.dept_name');

        $visibleIds = $this->visibleDepartmentIds($user);

        if ($visibleIds !== null) {
            if ($visibleIds === []) {
                return collect();
            }

            $query->whereIn('d.id', $visibleIds);
        }

        return $query->get();
    }

    public function listForAdministration(?string $status = null): Collection
    {
        $this->backfillMissingHandlingDepartments();

        $query = Applicant::query()
            ->with([
                'program:id,program_code,program_name,department_id',
                'program.department:id,dept_name,dept_code',
                'preferredCampus:id,campus_name',
                'handlingDepartment:id,dept_name,dept_code',
            ])
            ->orderByDesc('created_at');

        $this->applyStatusFilter($query, $status);

        return $query->get();
    }

    public function findForAdministration(int $id): Applicant
    {
        return Applicant::query()
            ->with([
                'program.department',
                'preferredCampus',
                'documents',
                'handlingDepartment',
                'student',
            ])
            ->findOrFail($id);
    }

    public function listApplications(User $user, ?int $departmentId = null, ?string $status = null, ?int $programId = null): Collection
    {
        $this->backfillMissingHandlingDepartments();

        $query = $this->scopedQuery($user)
            ->with([
                'program:id,program_code,program_name,department_id',
                'program.department:id,dept_name,dept_code',
                'preferredCampus:id,campus_name',
                'handlingDepartment:id,dept_name,dept_code',
            ])
            ->orderByDesc('created_at');

        if ($departmentId) {
            $query->where(function (Builder $scoped) use ($departmentId) {
                $scoped->where('handling_department_id', $departmentId)
                    ->orWhereHas('program', fn (Builder $programQuery) => $programQuery->where('department_id', $departmentId));
            });
        }

        if ($programId) {
            $query->where('program_id', $programId);
        }

        $this->applyStatusFilter($query, $status);

        return $query->get();
    }

    public function findForReview(User $user, int $id): Applicant
    {
        $applicant = $this->scopedQuery($user)
            ->with([
                'program.department',
                'preferredCampus',
                'documents',
                'handlingDepartment',
            ])
            ->findOrFail($id);

        return $applicant;
    }

    public function canReview(User $user, Applicant $applicant): bool
    {
        if ($this->canAccessAllDepartments($user)) {
            return true;
        }

        $departmentId = $this->resolveHandlingDepartmentId($applicant);

        return $departmentId !== null
            && in_array($departmentId, $this->visibleDepartmentIds($user) ?? [], true);
    }

    public function handoffFromAdministration(User $user, Applicant $applicant, ?string $notes = null): Applicant
    {
        $this->assertNotFinalized($applicant);

        if (! in_array($applicant->status, ['submitted_admin', 'submitted'], true)) {
            throw ValidationException::withMessages([
                'application' => 'Only newly submitted applications can be forwarded to academics.',
            ]);
        }

        $applicant->update([
            'status' => 'academic_review',
            'academic_review_status' => 'under_review',
            'review_notes' => $notes,
            'reviewed_at' => now(),
        ]);

        $this->logDecision($user, $applicant, 'admissions.application.handed_off_to_academics', 'Application forwarded from Administration to academic review');

        return $applicant->fresh(['program.department', 'handlingDepartment']);
    }

    public function approveAcademically(User $user, Applicant $applicant, ?string $notes = null): Applicant
    {
        $this->assertCanReview($user, $applicant);
        $this->assertNotFinalized($applicant);
        $this->assertAcademicReviewStage($applicant);

        if (! $user->hasPermission('academics.approve') && ! $user->hasRole('Super Admin')) {
            throw ValidationException::withMessages([
                'approve' => 'You do not have permission to approve applications academically.',
            ]);
        }

        $applicant->update([
            'status' => 'fee_pending',
            'academic_review_status' => 'approved',
            'review_notes' => $notes,
            'rejection_reason' => null,
            'academic_reviewer_id' => $user->staff_id,
            'reviewed_at' => now(),
        ]);

        $this->logDecision($user, $applicant, 'admissions.application.academically_approved', 'Application academically approved');

        $applicant = $applicant->fresh(['program.department', 'handlingDepartment']);
        app(StudentEnrollmentService::class)->registerFromAcademicallyApprovedApplicant($applicant, $user->id);

        $instructions = $this->feeService->paymentInstructions($applicant);
        $mailResult = $this->mailService->sendAcademicApprovalPackage($applicant, null, $instructions);

        if (! $mailResult['sent']) {
            session()->flash('application_mail_error', $mailResult['error']);
        }

        return $applicant;
    }

    public function rejectAcademically(User $user, Applicant $applicant, string $reason, ?string $notes = null): Applicant
    {
        $this->assertCanReview($user, $applicant);
        $this->assertNotFinalized($applicant);

        if (! $user->hasPermission('academics.approve') && ! $user->hasRole('Super Admin')) {
            throw ValidationException::withMessages([
                'reject' => 'You do not have permission to reject applications.',
            ]);
        }

        $applicant->update([
            'status' => 'rejected',
            'academic_review_status' => 'rejected',
            'rejection_reason' => $reason,
            'review_notes' => $notes,
            'academic_reviewer_id' => $user->staff_id,
            'reviewed_at' => now(),
        ]);

        $this->logDecision($user, $applicant, 'admissions.application.rejected', 'Application rejected by academics');

        $applicant = $applicant->fresh(['program.department', 'handlingDepartment']);
        $this->mailService->sendStatusUpdate($applicant);

        return $applicant;
    }

    public function confirmApplicationFee(User $user, Applicant $applicant, ?string $notes = null): Applicant
    {
        $this->assertCanReview($user, $applicant);
        $this->assertNotFinalized($applicant);

        return $this->feeService->markPaid(
            $applicant,
            'MANUAL-'.$applicant->application_number,
            'manual',
            null,
            $notes
        );
    }

    public function finalizeAfterPayment(Applicant $applicant, ?int $actorId = null): Applicant
    {
        if ($applicant->status === 'admitted' || $applicant->status === 'rejected') {
            return $applicant;
        }

        if ($applicant->academic_review_status !== 'approved' || ! $applicant->application_fee_paid) {
            return $applicant;
        }

        $applicant->update([
            'status' => 'admitted',
            'reviewed_at' => now(),
        ]);

        $this->auditService->log(
            'admissions.application.admitted',
            'applicants',
            $applicant->id,
            null,
            [
                'application_number' => $applicant->application_number,
                'status' => 'admitted',
            ],
            'Application admitted after fee payment',
            'success',
            $actorId
        );

        $applicant = $applicant->fresh(['program.department', 'handlingDepartment']);
        $student = app(StudentEnrollmentService::class)->enrollFromAdmittedApplicant($applicant, $actorId);
        app(StudentPortalService::class)->ensureStudentAccountForApplicant($applicant, $student);
        $this->mailService->sendStatusUpdate($applicant);

        return $applicant;
    }

    public function resolveHandlingDepartmentId(Applicant $applicant): ?int
    {
        if ($applicant->handling_department_id) {
            return (int) $applicant->handling_department_id;
        }

        return $applicant->program?->department_id
            ? (int) $applicant->program->department_id
            : null;
    }

    public function handlingDepartmentName(Applicant $applicant): string
    {
        $department = $applicant->handlingDepartment
            ?? $applicant->program?->department;

        return $department?->dept_name ?? 'Unassigned';
    }

    public function filterDepartmentsForUser(User $user): Collection
    {
        $query = Department::query()
            ->validLearningDepartments()
            ->where('is_active', 1)
            ->orderBy('dept_name');

        $visibleIds = $this->visibleDepartmentIds($user);

        if ($visibleIds !== null) {
            if ($visibleIds === []) {
                return collect();
            }

            $query->whereIn('id', $visibleIds);
        }

        return $query->get(['id', 'dept_name', 'dept_code']);
    }

    private function scopedQuery(User $user): Builder
    {
        $query = Applicant::query();
        $visibleIds = $this->visibleDepartmentIds($user);

        if ($visibleIds === null) {
            return $query;
        }

        if ($visibleIds === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where(function (Builder $scoped) use ($visibleIds) {
            $scoped->whereIn('handling_department_id', $visibleIds)
                ->orWhereHas('program', fn (Builder $programQuery) => $programQuery->whereIn('department_id', $visibleIds));
        });
    }

    private function assertCanReview(User $user, Applicant $applicant): void
    {
        if (! $this->canReview($user, $applicant)) {
            throw ValidationException::withMessages([
                'application' => 'You cannot review applications outside your assigned department(s).',
            ]);
        }
    }

    private function assertPendingReview(Applicant $applicant): void
    {
        if (in_array($applicant->status, ['admitted', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'application' => 'This application has already been finalized.',
            ]);
        }
    }

    private function assertAcademicReviewStage(Applicant $applicant): void
    {
        if (in_array($applicant->status, ['submitted_admin', 'submitted'], true)) {
            throw ValidationException::withMessages([
                'application' => 'Administration must forward this application to academics before review.',
            ]);
        }

        if ($applicant->status !== 'academic_review') {
            throw ValidationException::withMessages([
                'application' => 'This application is not awaiting academic review.',
            ]);
        }
    }

    private function applyStatusFilter(Builder $query, ?string $status): void
    {
        if ($status === 'new') {
            $query->whereIn('status', ['submitted_admin', 'submitted']);
        } elseif ($status === 'pending') {
            $query->where('status', 'academic_review')
                ->where('academic_review_status', 'under_review');
        } elseif ($status === 'payment_pending') {
            $query->whereIn('status', ['fee_pending', 'paid'])
                ->where('academic_review_status', 'approved');
        } elseif ($status === 'admitted') {
            $query->where('status', 'admitted');
        } elseif ($status === 'rejected') {
            $query->where('status', 'rejected');
        }
    }

    private function assertNotFinalized(Applicant $applicant): void
    {
        if (in_array($applicant->status, ['admitted', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'application' => 'This application has already been accepted or rejected.',
            ]);
        }
    }

    private function logDecision(User $user, Applicant $applicant, string $action, string $description): void
    {
        $this->auditService->log(
            $action,
            'applicants',
            $applicant->id,
            null,
            [
                'application_number' => $applicant->application_number,
                'status' => $applicant->status,
                'academic_review_status' => $applicant->academic_review_status,
                'handling_department_id' => $this->resolveHandlingDepartmentId($applicant),
                'reviewer_user_id' => $user->id,
            ],
            $description,
            'success',
            $user->id
        );
    }

    private function backfillMissingHandlingDepartments(): void
    {
        if (! Schema::hasColumn('applicants', 'handling_department_id')) {
            return;
        }

        DB::statement('
            UPDATE applicants a
            INNER JOIN academic_programs p ON a.program_id = p.id
            SET a.handling_department_id = p.department_id
            WHERE a.handling_department_id IS NULL
        ');
    }
}
