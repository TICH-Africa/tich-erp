<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\Campus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProgramsService
{
    public function __construct(protected HomepageService $homepageService) {}

    public function getCatalog(): array
    {
        $usingFallback = false;
        $allPrograms = $this->loadPrograms($usingFallback);
        $featured = $this->resolveFeatured($allPrograms);

        $programs = $featured
            ? $allPrograms->reject(fn ($program) => ($program->program_code ?? null) === ($featured->program_code ?? null))->values()
            : $allPrograms;

        return [
            'featured' => $featured,
            'programs' => $programs,
            'campuses' => $this->getCampuses(),
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

    private function loadPrograms(bool &$usingFallback): Collection
    {
        if ($this->tableExists('academic_programs')) {
            $query = AcademicProgram::query()
                ->with('department:id,dept_name')
                ->where('status', 'active');

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

    private function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
