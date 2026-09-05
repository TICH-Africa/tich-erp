<?php

namespace App\Http\Controllers\Ict\Content;

use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\Department;
use App\Services\AuditService;
use App\Services\ProgramCarouselSyncService;
use App\Services\StoredFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
        protected ProgramCarouselSyncService $programCarousel,
        protected StoredFileService $files,
    ) {}

    public function index(): View
    {
        return view('ict.content.courses.index', [
            'programs' => AcademicProgram::query()
                ->with('department:id,dept_name,dept_code')
                ->orderBy('homepage_display_order')
                ->orderBy('program_name')
                ->get(),
            'learningDepartments' => Department::query()
                ->validLearningDepartments()
                ->where('is_active', 1)
                ->orderBy('dept_name')
                ->get(['id', 'dept_name', 'dept_code']),
            'programTypes' => ['certificate', 'diploma', 'degree', 'artisan', 'short_course'],
            'programStatuses' => ['active', 'pending_ceo', 'inactive', 'archived'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $department = Department::query()->find($validated['department_id']);
        if (! $department?->isValidLearningDepartment()) {
            return back()->withInput()->withErrors([
                'department_id' => 'Programmes must belong to a learning department under Academics.',
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

        $this->programCarousel->sync($program->fresh());
        $this->auditService->log('core.program.created', 'academic_programs', $program->id, null, $program->only(['program_code', 'program_name']), null, 'success', $request->user()->id, $request);

        return back()->with('status', 'Course / programme created.');
    }

    public function update(Request $request, AcademicProgram $program): RedirectResponse
    {
        $validated = $this->validated($request, $program);

        $department = Department::query()->find($validated['department_id']);
        if (! $department?->isValidLearningDepartment()) {
            return back()->withInput()->withErrors([
                'department_id' => 'Programmes must belong to a learning department under Academics.',
            ]);
        }

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
        $this->programCarousel->sync($program->fresh());

        return back()->with('status', 'Course / programme updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?AcademicProgram $program = null): array
    {
        $codeRule = ['required', 'string', 'max:30', 'unique:academic_programs,program_code'];
        if ($program) {
            $codeRule[3] = 'unique:academic_programs,program_code,'.$program->id;
        }

        return $request->validate([
            'program_code' => $codeRule,
            'program_name' => ['required', 'string', 'max:300'],
            'program_type' => ['required', 'string', 'max:50'],
            'regulatory_body' => ['nullable', 'string', 'max:100'],
            'department_id' => ['required', 'exists:departments,id'],
            'duration_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'status' => ['required', 'in:active,pending_ceo,inactive,archived'],
            'homepage_tagline' => ['nullable', 'string', 'max:50000'],
            'entry_requirements' => ['nullable', 'string', 'max:50000'],
            'homepage_display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_featured_on_homepage' => ['nullable', 'boolean'],
            'cover_image' => [$program ? 'nullable' : 'required', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp'],
        ]);
    }
}
