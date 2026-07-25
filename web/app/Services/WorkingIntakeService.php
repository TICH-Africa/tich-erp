<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\CurriculumVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class WorkingIntakeService
{
    /**
     * @return list<string>
     */
    public static function intakeRequiredSections(): array
    {
        return ['semesters', 'applications', 'enrolled', 'timetable'];
    }

    public function sessionKey(int $programId): string
    {
        return "academics.working_intake.{$programId}";
    }

    public function resolve(AcademicProgram $program, Request $request): ?CurriculumVersion
    {
        $intakes = $this->intakesForProgram($program->id);

        if ($intakes->isEmpty()) {
            return null;
        }

        $requestedId = $request->integer('intake') ?: null;

        if ($requestedId) {
            $intake = $intakes->firstWhere('id', $requestedId);

            if ($intake) {
                session([$this->sessionKey($program->id) => $intake->id]);

                return $intake;
            }
        }

        $sessionId = session($this->sessionKey($program->id));

        if ($sessionId) {
            $intake = $intakes->firstWhere('id', (int) $sessionId);

            if ($intake) {
                return $intake;
            }

            session()->forget($this->sessionKey($program->id));
        }

        return null;
    }

    public function sectionRequiresIntake(string $section): bool
    {
        return in_array($section, self::intakeRequiredSections(), true);
    }

    public function programHasIntakes(int $programId): bool
    {
        return $this->intakesForProgram($programId)->isNotEmpty();
    }

    /**
     * @return Collection<int, CurriculumVersion>
     */
    public function intakesForProgram(int $programId): Collection
    {
        return app(CurriculumVersionService::class)->intakesForProgram($programId);
    }
}
