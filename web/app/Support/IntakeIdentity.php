<?php

namespace App\Support;

use App\Models\Applicant;
use App\Models\CurriculumVersion;
use App\Models\Student;

class IntakeIdentity
{
    public static function cohortLabel(int $year, int $month): string
    {
        $monthName = date('M', mktime(0, 0, 0, $month, 1));

        return $year.'-'.strtoupper($monthName);
    }

    /**
     * @return array{year: int, month: int}|null
     */
    public static function parseCohortIntake(?string $cohort): ?array
    {
        if (! $cohort) {
            return null;
        }

        $cohort = trim($cohort);

        if (preg_match('/^(\d{4})-([A-Z]{3})$/i', $cohort, $matches)) {
            $monthNum = (int) date('n', strtotime('1 '.$matches[2].' 2000'));

            return ['year' => (int) $matches[1], 'month' => $monthNum];
        }

        if (preg_match('/^(\d{4})-(\d{1,2})$/', $cohort, $matches)) {
            return ['year' => (int) $matches[1], 'month' => (int) $matches[2]];
        }

        return null;
    }

    /**
     * @return array{year: int, month: int}|null
     */
    public static function resolveStudentIntake(?Applicant $applicant, ?Student $student = null): ?array
    {
        if ($applicant?->intake_year && $applicant?->intake_month) {
            return [
                'year' => (int) $applicant->intake_year,
                'month' => (int) $applicant->intake_month,
            ];
        }

        return self::parseCohortIntake($student?->cohort_intake);
    }

    public static function studentMatchesIntake(Student $student, CurriculumVersion $intake): bool
    {
        if (! $intake->intake_year || ! $intake->intake_month) {
            return false;
        }

        $targetYear = (int) $intake->intake_year;
        $targetMonth = (int) $intake->intake_month;

        $resolved = self::resolveStudentIntake($student->applicant, $student);

        return $resolved
            && $resolved['year'] === $targetYear
            && $resolved['month'] === $targetMonth;
    }

    public static function applicantMatchesIntake(?Applicant $applicant, CurriculumVersion $intake): bool
    {
        if (! $intake->intake_year || ! $intake->intake_month || ! $applicant) {
            return false;
        }

        if ($applicant->intake_year && $applicant->intake_month) {
            return (int) $applicant->intake_year === (int) $intake->intake_year
                && (int) $applicant->intake_month === (int) $intake->intake_month;
        }

        return false;
    }
}
