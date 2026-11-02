<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Department;
use App\Models\FeeStructure;
use App\Services\AuditService;
use App\Services\Academics\ProgramTierFeeService;
use App\Services\Academics\ProgramTierService;
use App\Services\ProgramCarouselSyncService;
use App\Services\ProgramFormCoverDraftService;
use App\Services\StoredFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
        protected ProgramCarouselSyncService $programCarousel,
        protected StoredFileService $files,
        protected ProgramTierService $programTiers,
        protected ProgramTierFeeService $programTierFees,
        protected ProgramFormCoverDraftService $coverDrafts,
    ) {}

    public function formCoverDraft(Request $request): Response
    {
        $scope = (string) $request->query('scope', 'create');
        abort_unless(in_array($scope, ['create', 'edit'], true), 404);

        $token = (string) $request->query('token', '');
        $absolute = $this->coverDrafts->previewAbsolutePath($scope, $token);
        abort_unless($absolute && is_readable($absolute), 404);

        return response()->file($absolute, [
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function index(): View
    {
        $programs = AcademicProgram::query()
            ->with(['department:id,dept_name,dept_code', 'tiers'])
            ->orderBy('homepage_display_order')
            ->orderBy('program_name')
            ->get();

        $learningDepartments = Department::query()
            ->validLearningDepartments()
            ->where('is_active', 1)
            ->orderBy('dept_name')
            ->get(['id', 'dept_name', 'dept_code']);

        return view('admin.programs.index', [
            'programs' => $programs,
            'learningDepartments' => $learningDepartments,
            'programTypes' => ['certificate', 'diploma', 'advanced_diploma', 'artisan', 'short_course', 'tiered'],
            'programStatuses' => ['active', 'pending_ceo', 'inactive', 'archived'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->coverDrafts->rememberUpload($request);
        $isTiered = $request->boolean('is_tiered');

        $validated = $request->validate(array_merge(
            $this->programFormRules($request, requireCoverImage: true),
            $this->programTiers->validationRules($isTiered),
        ));

        $department = Department::query()->find($validated['department_id']);
        if (! $department?->isValidLearningDepartment()) {
            return back()->withInput()->withErrors([
                'department_id' => 'Programmes must belong to a learning department under a department with the Academics module.',
            ]);
        }

        $tierPayload = $this->programTiers->tierPayloadFromValidated($validated, $isTiered);

        $program = AcademicProgram::create([
            ...$validated,
            'duration_months' => $validated['duration_months'] ?? 12,
            'homepage_display_order' => $validated['homepage_display_order'] ?? 0,
            'is_featured_on_homepage' => $request->boolean('is_featured_on_homepage'),
            'is_tiered' => $isTiered,
            'tier_semester_months' => $tierPayload['tier_semester_months'],
            'program_type' => $isTiered ? 'tiered' : $validated['program_type'],
            'curriculum_format' => $isTiered ? 'semester' : ($validated['curriculum_format'] ?? 'semester'),
            'cover_image_path' => $this->files->replace(null, $this->coverDrafts->resolveUploadedFile($request), 'programs', 'public', null, true),
            'created_by' => $request->user()->id,
            'created_at' => now(),
        ]);

        try {
            $this->programTiers->syncTiers($program, $tierPayload);
        } catch (\InvalidArgumentException $e) {
            $program->delete();

            return back()->withInput()->withErrors(['is_tiered' => $e->getMessage()]);
        }

        $this->coverDrafts->forget($this->coverDrafts->scopeFromRequest($request));

        // Create fee structure if fee provided
        if (! empty($validated['total_semester_fee'])) {
            $this->createOrUpdateFeeStructure($program, $validated['total_semester_fee']);
        }
        $this->syncTierFeeStructures($program, $request, $isTiered);

        $this->auditService->log(
            'core.program.created',
            'academic_programs',
            $program->id,
            null,
            $program->only(['program_code', 'program_name', 'department_id', 'status']),
            null,
            'success',
            $request->user()->id,
            $request
        );

        $this->programCarousel->sync($program->fresh());

        return back()->with('status', 'Program created successfully.');
    }

    public function update(Request $request, AcademicProgram $program): RedirectResponse
    {
        $this->coverDrafts->rememberUpload($request);
        $isTiered = $request->boolean('is_tiered');

        $validated = $request->validate(array_merge(
            $this->programFormRules($request, requireCoverImage: ! $program->cover_image_path, program: $program),
            $this->programTiers->validationRules($isTiered),
        ));

        $department = Department::query()->find($validated['department_id']);
        if (! $department?->isValidLearningDepartment()) {
            return back()->withInput()->withErrors([
                'department_id' => 'Programmes must belong to a learning department under a department with the Academics module.',
            ]);
        }

        $old = $program->only(['program_code', 'program_name', 'department_id', 'status']);
        $tierPayload = $this->programTiers->tierPayloadFromValidated($validated, $isTiered);

        $updates = [
            ...$validated,
            'duration_months' => $validated['duration_months'] ?? $program->duration_months,
            'homepage_display_order' => $validated['homepage_display_order'] ?? 0,
            'is_featured_on_homepage' => $request->boolean('is_featured_on_homepage'),
            'is_tiered' => $isTiered,
            'tier_semester_months' => $tierPayload['tier_semester_months'],
            'program_type' => $isTiered ? 'tiered' : $validated['program_type'],
        ];

        if ($isTiered) {
            $updates['curriculum_format'] = 'semester';
        }

        if ($request->hasFile('cover_image') || $this->coverDrafts->hasRetainedCover(
            $this->coverDrafts->scopeFromRequest($request),
            $request->input('cover_image_draft'),
        )) {
            $updates['cover_image_path'] = $this->files->replace(
                $program->cover_image_path,
                $this->coverDrafts->resolveUploadedFile($request),
                'programs',
                'public',
                null,
                true,
            );
            $this->coverDrafts->forget($this->coverDrafts->scopeFromRequest($request));
        }

        $program->update($updates);

        try {
            $this->programTiers->syncTiers($program->fresh(), $tierPayload);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['is_tiered' => $e->getMessage()]);
        }

        // Update fee structure if fee provided
        if (! empty($validated['total_semester_fee']) && ! $isTiered) {
            $this->createOrUpdateFeeStructure($program, $validated['total_semester_fee']);
        }
        $this->syncTierFeeStructures($program->fresh(), $request, $isTiered);

        $this->auditService->log(
            'core.program.updated',
            'academic_programs',
            $program->id,
            $old,
            $program->only(['program_code', 'program_name', 'department_id', 'status']),
            null,
            'success',
            $request->user()->id,
            $request
        );

        $this->programCarousel->sync($program->fresh());

        return back()->with('status', 'Program updated successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function programFormRules(Request $request, bool $requireCoverImage, ?AcademicProgram $program = null): array
    {
        $scope = $this->coverDrafts->scopeFromRequest($request);
        $hasRetainedCover = $this->coverDrafts->hasRetainedCover($scope, $request->input('cover_image_draft'));

        $codeRule = ['required', 'string', 'max:30', 'unique:academic_programs,program_code'];
        if ($program) {
            $codeRule[3] = 'unique:academic_programs,program_code,'.$program->id;
        }

        $coverRules = ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp'];
        if ($requireCoverImage && ! $hasRetainedCover) {
            array_unshift($coverRules, 'required');
        }

        return [
            '_form' => ['nullable', 'string', 'in:create,edit'],
            'program_code' => $codeRule,
            'program_name' => ['required', 'string', 'max:300'],
            'program_type' => ['required', 'string', 'max:50'],
            'regulatory_body' => ['nullable', 'string', 'max:100'],
            'department_id' => ['required', 'exists:departments,id'],
            'duration_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'status' => ['required', 'in:active,pending_ceo,inactive,archived'],
            'homepage_tagline' => ['nullable', 'string', 'max:2000'],
            'entry_requirements' => ['nullable', 'string', 'max:2000'],
            'homepage_display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_featured_on_homepage' => ['nullable', 'boolean'],
            'cover_image' => $coverRules,
            'cover_image_draft' => ['nullable', 'string', 'max:80'],
            'total_semester_fee' => ['nullable', 'numeric', 'min:0'],
            'tier_fees' => ['nullable', 'array'],
            'tier_fees.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    private function syncTierFeeStructures(AcademicProgram $program, Request $request, bool $isTiered): void
    {
        if (! $isTiered) {
            return;
        }

        $tierFees = $request->input('tier_fees', []);
        if (! is_array($tierFees)) {
            return;
        }

        foreach ($tierFees as $tierKey => $amount) {
            if ($amount === null || $amount === '') {
                continue;
            }
            $this->createOrUpdateFeeStructure($program, (float) $amount, (string) $tierKey);
        }
    }

    private function createOrUpdateFeeStructure(AcademicProgram $program, float $totalSemesterFee, ?string $programTier = null): void
    {
        $currentYear = AcademicYear::query()->where('is_current', 1)->first();
        if (! $currentYear) {
            return;
        }

        $feeStructure = FeeStructure::query()
            ->where('program_id', $program->id)
            ->where('academic_year_id', $currentYear->id)
            ->when(
                $programTier,
                fn ($q) => $q->where('program_tier', $programTier),
                fn ($q) => $q->whereNull('program_tier'),
            )
            ->first();

        $tuitionFee = round($totalSemesterFee * 0.8, 2); // 80% tuition
        $otherFees = round($totalSemesterFee * 0.2, 2); // 20% other fees

        $data = [
            'program_id' => $program->id,
            'program_tier' => $programTier,
            'academic_year_id' => $currentYear->id,
            'effective_from' => now()->toDateString(),
            'is_active' => true,
            'is_approved' => true,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'tuition_fee' => $tuitionFee,
            'total_semester_fee' => $totalSemesterFee,
            'application_fee' => 1000,
            'qa_annual_fee' => 1000,
            'graduation_fee' => 4000,
        ];

        if ($feeStructure) {
            $feeStructure->update($data);
        } else {
            FeeStructure::create($data);
        }
    }
}
