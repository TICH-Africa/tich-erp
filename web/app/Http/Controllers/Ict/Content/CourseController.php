<?php

namespace App\Http\Controllers\Ict\Content;

use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\Department;
use App\Services\Academics\ProgramTierService;
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
        protected ProgramTierService $programTiers,
    ) {}

    public function index(): View
    {
        return view('ict.content.courses.index', [
            'programs' => AcademicProgram::query()
                ->with(['department:id,dept_name,dept_code', 'tiers'])
                ->orderBy('homepage_display_order')
                ->orderBy('program_name')
                ->get(),
            'learningDepartments' => Department::query()
                ->validLearningDepartments()
                ->where('is_active', 1)
                ->orderBy('dept_name')
                ->get(['id', 'dept_name', 'dept_code']),
            'programTypes' => ['certificate', 'diploma', 'advanced_diploma', 'artisan', 'short_course', 'tiered'],
            'programStatuses' => ['active', 'pending_ceo', 'inactive', 'archived'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return app(ProgramController::class)->store($request);
    }

    public function update(Request $request, AcademicProgram $program): RedirectResponse
    {
        return app(ProgramController::class)->update($request, $program);
    }
}
