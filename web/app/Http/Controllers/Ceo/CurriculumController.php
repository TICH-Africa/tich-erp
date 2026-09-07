<?php

namespace App\Http\Controllers\Ceo;

use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\CurriculumVersion;
use App\Models\Department;
use App\Services\AcademicsAccessService;
use App\Services\CurriculumVersionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CurriculumController extends Controller
{
    public function __construct(
        protected AcademicsAccessService $access,
        protected CurriculumVersionService $versions,
    ) {}

    public function index(): View
    {
        $versions = Schema::hasTable('curriculum_versions')
            ? CurriculumVersion::query()
                ->with(['program.department', 'academicYear'])
                ->where('status', 'pending_ceo')
                ->orderByDesc('registrar_approved_at')
                ->orderByDesc('id')
                ->paginate(20)
            : collect();

        $programs = Schema::hasTable('academic_programs')
            ? AcademicProgram::query()
                ->with('department')
                ->where('status', 'pending_ceo')
                ->orderBy('program_name')
                ->get()
            : collect();

        return view('ceo.curriculum.index', [
            'versions' => $versions,
            'programs' => $programs,
        ]);
    }

    public function show(CurriculumVersion $version): View
    {
        abort_unless(in_array($version->status, ['pending_ceo', 'published'], true), 404);

        $version->load(['program.department', 'academicYear', 'items.unit', 'periods']);

        return view('ceo.curriculum.show', [
            'version' => $version,
            'canApprove' => $version->status === 'pending_ceo' && $this->access->canApproveCeo(auth()->user()),
        ]);
    }

    public function approve(Request $request, CurriculumVersion $version): RedirectResponse
    {
        $hub = Department::findAcademicsHub();
        abort_unless($hub, 404);

        try {
            $this->versions->approveCeo(auth()->user(), $hub, $version, $request);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->withErrors(['curriculum' => $e->getMessage() ?: 'Unable to approve this curriculum version.']);
        }

        return redirect()
            ->route('ceo.curriculum.index')
            ->with('status', 'Curriculum version published after CEO approval.');
    }
}
