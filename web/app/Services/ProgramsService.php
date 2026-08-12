<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\Campus;
use App\Models\Department;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProgramsService
{
    public function __construct(protected HomepageService $homepageService) {}

    public function getCatalog(?string $search = null, ?string $departmentCode = null): array
    {
        $usingFallback = false;
        $allPrograms = $this->loadPrograms($usingFallback, $search, $departmentCode);
        $featured = $this->resolveFeatured($allPrograms);

        $programs = $featured
            ? $allPrograms->reject(fn ($program) => ($program->program_code ?? null) === ($featured->program_code ?? null))->values()
            : $allPrograms;

        return [
            'featured' => $featured,
            'programs' => $programs,
            'campuses' => $this->getCampuses(),
            'departments' => $this->getDepartments(),
            'usingFallback' => $usingFallback,
        ];
    }

    public function getProgramOptions(): Collection
    {
        $usingFallback = false;

        return $this->loadPrograms($usingFallback);
    }

    public function findProgramByCode(?string $code): ?object
    {
        if (! $code) {
            return null;
        }

        return $this->getProgramOptions()->first(fn ($program) => strtoupper($program->program_code ?? '') === strtoupper($code));
    }

    public function findProgramById(?int $id): ?object
    {
        if (! $id) {
            return null;
        }

        return $this->getProgramOptions()->first(fn ($program) => ($program->id ?? null) == $id);
    }

    private function loadPrograms(bool &$usingFallback, ?string $search = null, ?string $departmentCode = null): Collection
    {
        if ($this->tableExists('academic_programs')) {
            $query = AcademicProgram::query()
                ->with('department:id,dept_name')
                ->where('status', 'active');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('program_name', 'like', "%{$search}%")
                      ->orWhere('program_code', 'like', "%{$search}%")
                      ->orWhere('homepage_tagline', 'like', "%{$search}%");
                });
            }

            if ($departmentCode && $this->tableExists('departments')) {
                $deptId = DB::table('departments')->where('dept_code', $departmentCode)->value('id');
                if ($deptId) {
                    $query->where('department_id', $deptId);
                }
            }

            $records = $query->orderBy('homepage_display_order')
                ->orderBy('program_name')
                ->get();

            if ($records->isNotEmpty()) {
                return $records->map(fn (AcademicProgram $program) => $this->mapProgram($program));
            }
        }

        $usingFallback = true;

        return $this->homepageService->getFeaturedPrograms();
    }

    private function resolveFeatured(Collection $programs): ?object
    {
        $featured = $programs->first(fn ($program) => ! empty($program->is_featured_on_homepage));

        return $featured ?? $programs->first();
    }

    private function mapProgram(AcademicProgram $program): object
    {
        $feeDisplay = $this->resolveProgramFee($program->id);

        return (object) [
            'id' => $program->id,
            'program_code' => $program->program_code,
            'program_name' => $program->program_name,
            'program_type' => $program->program_type,
            'regulatory_body' => $program->regulatory_body,
            'department_name' => $program->department?->dept_name,
            'duration_months' => $program->duration_months,
            'homepage_tagline' => $program->homepage_tagline,
            'entry_requirements' => $program->entry_requirements ?? 'See admissions guide for entry requirements.',
            'fee_display' => $feeDisplay,
            'is_featured_on_homepage' => (bool) $program->is_featured_on_homepage,
            'apply_url' => route('apply.index', ['program' => $program->program_code]),
        ];
    }

    private function resolveProgramFee(int $programId): string
    {
        if (! $this->tableExists('fee_structures')) {
            return 'Contact admissions for current fee structure';
        }

        $fee = DB::table('fee_structures')
            ->where('program_id', $programId)
            ->where('is_active', 1)
            ->orderByDesc('effective_from')
            ->value('total_semester_fee');

        if ($fee) {
            return 'KES '.number_format((float) $fee, 0).' per semester';
        }

        return 'Contact admissions for current fee structure';
    }

    private function getCampuses(): Collection
    {
        if ($this->tableExists('campuses')) {
            return Campus::query()
                ->where('is_active', 1)
                ->orderBy('campus_name')
                ->get(['id', 'campus_name', 'campus_code']);
        }

        return collect([
            (object) ['id' => null, 'campus_name' => 'Main Campus, Kisumu', 'campus_code' => 'MAIN'],
        ]);
    }

    private function getDepartments(): Collection
    {
        if ($this->tableExists('departments')) {
            return Department::query()
                ->validLearningDepartments()
                ->where('is_active', 1)
                ->orderBy('display_order')
                ->orderBy('dept_name')
                ->get(['dept_code', 'dept_name']);
        }

        return collect([
            (object) ['dept_code' => 'CHS', 'dept_name' => 'Health and Social Sciences'],
            (object) ['dept_code' => 'HOS', 'dept_name' => 'Catering and Hospitality'],
            (object) ['dept_code' => 'BUS', 'dept_name' => 'Business and Accounting'],
            (object) ['dept_code' => 'ICT', 'dept_name' => 'Information Communication Technology'],
            (object) ['dept_code' => 'TEC', 'dept_name' => 'Technical Department'],
        ]);
    }

    private function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
