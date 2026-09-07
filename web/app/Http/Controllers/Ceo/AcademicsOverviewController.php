<?php

namespace App\Http\Controllers\Ceo;

use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\CurriculumVersion;
use App\Models\Department;
use App\Models\Unit;
use App\Services\AcademicsAccessService;
use App\Services\AcademicsDashboardService;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AcademicsOverviewController extends Controller
{
    public function __construct(
        protected AcademicsAccessService $access,
        protected AcademicsDashboardService $dashboard,
    ) {}

    public function __invoke(): View
    {
        $hub = Department::findAcademicsHub();
        $stats = [
            'programs' => 0,
            'units' => 0,
            'pending_ceo_versions' => 0,
            'pending_ceo_programs' => 0,
            'learning_departments' => 0,
        ];
        $learningDepartments = collect();

        if ($hub) {
            $base = $this->dashboard->stats(auth()->user(), $hub);
            $stats['programs'] = (int) ($base['programs'] ?? 0);
            $stats['units'] = (int) ($base['units'] ?? 0);
            $stats['learning_departments'] = (int) ($base['learning_departments'] ?? 0);
            $learningDepartments = $this->access->learningDepartmentsInScope(auth()->user(), $hub);
        }

        if (Schema::hasTable('curriculum_versions')) {
            $stats['pending_ceo_versions'] = CurriculumVersion::query()
                ->where('status', 'pending_ceo')
                ->count();
        }

        if (Schema::hasTable('academic_programs')) {
            $stats['pending_ceo_programs'] = AcademicProgram::query()
                ->where('status', 'pending_ceo')
                ->count();
        }

        if (! $hub && Schema::hasTable('units')) {
            $stats['units'] = Unit::query()->count();
        }

        return view('ceo.academics.index', [
            'hub' => $hub,
            'stats' => $stats,
            'learningDepartments' => $learningDepartments,
        ]);
    }
}
