<?php

namespace App\Console\Commands;

use App\Models\CurriculumVersion;
use App\Models\User;
use App\Services\StudentUnitRegistrationService;
use Illuminate\Console\Command;

class AdvanceStudentCohorts extends Command
{
    protected $signature = 'academics:advance-cohorts {--intake= : Only roll this intake}';

    protected $description = 'Move each published cohort onto the semester its intake dates put it on and register the units mapped there';

    public function handle(StudentUnitRegistrationService $registrations): int
    {
        // One intake per programme, the newest published, so a student is never rolled twice in the
        // same night against two curricula.
        $intakes = CurriculumVersion::query()
            ->where('status', 'published')
            ->when($this->option('intake'), fn ($query, $id) => $query->whereKey((int) $id))
            ->with('program')
            ->orderBy('program_id')
            ->orderByDesc('intake_year')
            ->orderByDesc('intake_month')
            ->get()
            ->unique('program_id')
            ->values();

        if ($intakes->isEmpty()) {
            $this->warn('No published intakes to roll.');

            return self::SUCCESS;
        }

        foreach ($intakes as $intake) {
            if (! $intake->program) {
                $this->warn('#'.$intake->id.' has no programme on record.');

                continue;
            }

            $actor = $intake->published_by ? User::query()->find((int) $intake->published_by) : null;

            if (! $actor) {
                $this->warn($intake->version_label.': publish the intake (or set its publisher) before the cohort can be rolled.');

                continue;
            }

            $result = $registrations->advanceCohort($actor, $intake->program, $intake);

            $this->info($intake->program->program_name.' '.$intake->version_label.': '
                .$result['moved'].' '.str('student')->plural($result['moved']).' moved, '
                .$result['units'].' unit '.str('place')->plural($result['units']).' registered, '
                .count($result['held']).' held, '.$result['idle'].' already sitting it.');

            foreach ($result['held'] as $line) {
                $this->line('  held: '.$line);
            }

            foreach ($result['waiting'] as $line) {
                $this->line('  waiting: '.$line);
            }
        }

        return self::SUCCESS;
    }
}
