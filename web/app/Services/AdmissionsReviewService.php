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
    ) {}

    /**
     * @return list<int>|null Null means all departments (institution-wide access).
     */
    public function visibleDepartmentIds(User $user): ?array
    {
        if ($user->hasRole('Super Admin')
            || $user->hasRole('Academic Registrar')
            || $user->hasRole('Admissions Officer')
            || $user->hasRole('CEO')) {
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
            'pending' => (clone $base)->whereIn('status', ['submitted', 'academic_review'])
                ->whereIn('academic_review_status', ['pending', 'under_review'])
                ->count(),
            'shortlisted' => (clone $base)->where('academic_review_status', 'shortlisted')->count(),
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
                DB::raw("SUM(CASE WHEN a.status IN ('submitted','academic_review') AND a.academic_review_status IN ('pending','under_review') THEN 1 ELSE 0 END) as pending_count")
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

    public function listApplications(User $user, ?int $departmentId = null, ?string $status = null): Collection
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

        if ($status === 'pending') {
            $query->whereIn('status', ['submitted', 'academic_review'])
                ->whereIn('academic_review_status', ['pending', 'under_review', 'shortlisted']);
        } elseif ($status === 'admitted') {
            $query->where('status', 'admitted');
        } elseif ($status === 'rejected') {
            $query->where('status', 'rejected');
        }

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

    public function shortlist(User $user, Applicant $applicant, ?string $notes = null): Applicant
    {
        $this->assertCanReview($user, $applicant);
        $this->assertPendingReview($applicant);

        $applicant->update([
            'status' => 'academic_review',
            'academic_review_status' => 'shortlisted',
            'review_notes' => $notes,
            'academic_reviewer_id' => $user->staff_id,
            'reviewed_at' => now(),
        ]);

        $this->logDecision($user, $applicant, 'admissions.application.shortlisted', 'Application shortlisted');

        $applicant = $applicant->fresh(['program.department', 'handlingDepartment']);
        $this->mailService->sendStatusUpdate($applicant);

        return $applicant;
    }

    public function approve(User $user, Applicant $applicant, ?string $notes = null): Applicant
    {
        $this->assertCanReview($user, $applicant);
        $this->assertNotFinalized($applicant);

        if (! $user->hasPermission('admissions.approve') && ! $user->hasRole('Super Admin')) {
            throw ValidationException::withMessages([
                'approve' => 'You do not have permission to approve applications.',
            ]);
        }

        $applicant->update([
            'status' => 'admitted',
            'academic_review_status' => 'approved',
            'review_notes' => $notes,
            'rejection_reason' => null,
            'academic_reviewer_id' => $user->staff_id,
            'reviewed_at' => now(),
        ]);

        $this->logDecision($user, $applicant, 'admissions.application.approved', 'Application approved — student admitted');

        $applicant = $applicant->fresh(['program.department', 'handlingDepartment']);
        $this->mailService->sendStatusUpdate($applicant);

        return $applicant;
    }

    public function reject(User $user, Applicant $applicant, string $reason, ?string $notes = null): Applicant
    {
        $this->assertCanReview($user, $applicant);
        $this->assertNotFinalized($applicant);

        if (! $user->hasPermission('admissions.approve') && ! $user->hasRole('Super Admin')) {
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

        $this->logDecision($user, $applicant, 'admissions.application.rejected', 'Application rejected');

        $applicant = $applicant->fresh(['program.department', 'handlingDepartment']);
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
            ->where('is_active', 1)
            ->where('dept_category', 'academic')
            ->whereNotNull('parent_dept_id')
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
