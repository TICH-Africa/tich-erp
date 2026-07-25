<?php

use App\Models\Student;
use App\Support\IntakeIdentity;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Student::query()
            ->with('applicant')
            ->whereNotNull('cohort_intake')
            ->chunkById(100, function ($students) {
                foreach ($students as $student) {
                    $applicant = $student->applicant;

                    if (! $applicant || ($applicant->intake_year && $applicant->intake_month)) {
                        continue;
                    }

                    $parsed = IntakeIdentity::parseCohortIntake($student->cohort_intake);

                    if (! $parsed) {
                        continue;
                    }

                    $applicant->update([
                        'intake_year' => $parsed['year'],
                        'intake_month' => $parsed['month'],
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Data backfill only.
    }
};
