<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\Campus;
use App\Models\Department;
use App\Services\AuditService;
use App\Services\ProgramCarouselSyncService;
use App\Services\StoredFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
        protected ProgramCarouselSyncService $programCarousel,
        protected StoredFileService $files,
    ) {}

    public function index(): View
    {
        $programs = AcademicProgram::query()
            ->with('department:id,dept_name,dept_code')
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
            'programTypes' => ['certificate', 'diploma', 'degree', 'artisan', 'short_course'],
            'programStatuses' => ['active', 'pending_ceo', 'inactive', 'archived'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'program_code' => ['required', 'string', 'max:30', 'unique:academic_programs,program_code'],
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
            'cover_image' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp'],
        ]);

        $department = Department::query()->find($validated['department_id']);
        if (! $department?->isValidLearningDepartment()) {
            return back()->withInput()->withErrors([
                'department_id' => 'Programmes must belong to a learning department under a department with the Academics module.',
            ]);
        }

        $program = AcademicProgram::create([
            ...$validated,
            'duration_months' => $validated['duration_months'] ?? 12,
            'homepage_display_order' => $validated['homepage_display_order'] ?? 0,
            'is_featured_on_homepage' => $request->boolean('is_featured_on_homepage'),
            'cover_image_path' => $this->files->replace(null, $request->file('cover_image'), 'programs', 'public', null, true),
            'created_by' => $request->user()->id,
            'created_at' => now(),
        ]);

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
        $validated = $request->validate([
            'program_code' => ['required', 'string', 'max:30', 'unique:academic_programs,program_code,'.$program->id],
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
            'cover_image' => [
                Rule::requiredIf(fn () => ! $program->cover_image_path),
                'nullable',
                'file',
                'image',
                'mimes:jpeg,jpg,png,gif,webp',
            ],
        ]);

        $department = Department::query()->find($validated['department_id']);
        if (! $department?->isValidLearningDepartment()) {
            return back()->withInput()->withErrors([
                'department_id' => 'Programmes must belong to a learning department under a department with the Academics module.',
            ]);
        }

        $old = $program->only(['program_code', 'program_name', 'department_id', 'status']);
        $updates = [
            ...$validated,
            'duration_months' => $validated['duration_months'] ?? $program->duration_months,
            'homepage_display_order' => $validated['homepage_display_order'] ?? 0,
            'is_featured_on_homepage' => $request->boolean('is_featured_on_homepage'),
        ];

        if ($request->hasFile('cover_image')) {
            $updates['cover_image_path'] = $this->files->replace(
                $program->cover_image_path,
                $request->file('cover_image'),
                'programs',
                'public',
                null,
                true,
            );
        }

        $program->update($updates);

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
}
