<?php

namespace App\Services;

use App\Models\CurriculumVersion;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class StudentPortalDashboardService
{
    public function __construct(
        protected CurriculumVersionService $curriculumVersions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forStudent(Student $student, array $biodata): array
    {
        $student->loadMissing(['applicant', 'program', 'campus']);

        $academics = $this->academics($student);
        $finance = $this->finance($student);

        return [
            'overview_stats' => $this->overviewStats($student, $biodata, $academics, $finance),
            'academics' => $academics,
            'finance' => $finance,
        ];
    }

    /**
     * @param  array<string, mixed>  $biodata
     * @param  array<string, mixed>  $academics
     * @param  array<string, mixed>  $finance
     * @return array<string, mixed>
     */
    private function overviewStats(Student $student, array $biodata, array $academics, array $finance): array
    {
        return [
            'enrollment_status' => ucfirst($student->enrollment_status ?? 'unknown'),
            'application_status' => ucwords(str_replace('_', ' ', (string) ($biodata['application']['status'] ?? ''))),
            'fee_clearance' => ucfirst($student->fee_clearance_status ?? 'pending'),
            'outstanding_balance' => (float) ($finance['summary']['outstanding_balance'] ?? $student->overall_balance ?? 0),
            'document_count' => $biodata['documents']->count(),
            'registered_unit_count' => $academics['registered_unit_count'],
            'grade_count' => $academics['grades']->count(),
            'curriculum_unit_count' => $academics['curriculum_units']->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function academics(Student $student): array
    {
        $curriculum = $this->resolveCurriculum($student);
        $curriculumUnits = $curriculum
            ? $curriculum->items->sortBy(['semester', 'display_order', 'priority'])->values()
            : collect();

        $registrations = DB::table('student_semester_registrations as ssr')
            ->join('semesters as s', 's.id', '=', 'ssr.semester_id')
            ->leftJoin('academic_years as ay', 'ay.id', '=', 's.academic_year_id')
            ->where('ssr.student_id', $student->id)
            ->orderByDesc('ssr.registration_date')
            ->select([
                'ssr.*',
                's.semester_label',
                's.semester_number',
                'ay.year_label',
            ])
            ->get();

        $registeredUnits = DB::table('registered_units as ru')
            ->join('student_semester_registrations as ssr', 'ssr.id', '=', 'ru.semester_registration_id')
            ->join('units as u', 'u.id', '=', 'ru.unit_id')
            ->join('semesters as s', 's.id', '=', 'ssr.semester_id')
            ->where('ssr.student_id', $student->id)
            ->orderByDesc('ssr.registration_date')
            ->select([
                'ru.id',
                'ru.is_additional',
                'u.unit_code',
                'u.unit_name',
                's.semester_label',
                's.semester_number',
                'ssr.status as registration_status',
                'ssr.registration_date',
            ])
            ->get();

        $grades = DB::table('grade_records as gr')
            ->join('units as u', 'u.id', '=', 'gr.unit_id')
            ->join('semesters as s', 's.id', '=', 'gr.semester_id')
            ->where('gr.student_id', $student->id)
            ->orderByDesc('gr.recorded_at')
            ->select([
                'gr.final_score',
                'gr.grade_letter',
                'gr.grade_points',
                'gr.recorded_at',
                'u.unit_code',
                'u.unit_name',
                's.semester_label',
            ])
            ->get();

        $attendance = DB::table('attendance_summaries as a')
            ->join('units as u', 'u.id', '=', 'a.unit_id')
            ->join('semesters as s', 's.id', '=', 'a.semester_id')
            ->where('a.student_id', $student->id)
            ->orderByDesc('a.last_calculated_at')
            ->select([
                'a.attendance_percentage',
                'a.total_present',
                'a.total_sessions',
                'a.status_flag',
                'u.unit_code',
                'u.unit_name',
                's.semester_label',
            ])
            ->get();

        $currentSemester = $student->current_semester_id
            ? Semester::query()->with('academicYear')->find($student->current_semester_id)
            : Semester::query()->with('academicYear')->where('is_current', 1)->first();

        return [
            'curriculum' => $curriculum,
            'curriculum_units' => $curriculumUnits,
            'curriculum_by_semester' => $curriculumUnits->groupBy('semester'),
            'registrations' => $registrations,
            'registered_units' => $registeredUnits,
            'registered_unit_count' => $registeredUnits->count(),
            'grades' => $grades,
            'attendance' => $attendance,
            'current_semester' => $currentSemester,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function finance(Student $student): array
    {
        $accounts = DB::table('student_accounts as sa')
            ->join('academic_years as ay', 'ay.id', '=', 'sa.academic_year_id')
            ->where('sa.student_id', $student->id)
            ->orderByDesc('ay.start_date')
            ->select([
                'sa.*',
                'ay.year_label',
            ])
            ->get();

        $invoices = DB::table('invoices')
            ->where('student_id', $student->id)
            ->orderByDesc('issue_date')
            ->limit(50)
            ->get();

        $payments = DB::table('payments')
            ->where('student_id', $student->id)
            ->orderByDesc('payment_date')
            ->limit(50)
            ->get();

        $accountBalance = (float) $accounts->sum('outstanding_balance');
        $invoiceBalance = (float) $invoices->sum('balance');
        $studentBalance = (float) ($student->overall_balance ?? 0);

        return [
            'accounts' => $accounts,
            'invoices' => $invoices,
            'payments' => $payments,
            'summary' => [
                'outstanding_balance' => $accountBalance > 0 ? $accountBalance : ($invoiceBalance > 0 ? $invoiceBalance : $studentBalance),
                'total_paid' => (float) ($accounts->sum('total_paid') > 0
                    ? $accounts->sum('total_paid')
                    : $payments->sum('amount')),
                'total_chargeable' => (float) $accounts->sum('total_chargeable'),
                'fee_clearance_status' => $student->fee_clearance_status ?? 'pending',
                'is_cleared' => $accounts->contains(fn ($account) => (bool) $account->is_cleared)
                    || ($student->fee_clearance_status === 'cleared'),
            ],
        ];
    }

    private function resolveCurriculum(Student $student): ?CurriculumVersion
    {
        if (! $student->program_id) {
            return null;
        }

        $applicant = $student->applicant;

        if ($applicant?->intake_year && $applicant?->intake_month) {
            $intakeMatch = CurriculumVersion::query()
                ->with(['items.unit'])
                ->where('program_id', $student->program_id)
                ->where('status', 'published')
                ->where('intake_year', $applicant->intake_year)
                ->where('intake_month', $applicant->intake_month)
                ->orderByDesc('published_at')
                ->first();

            if ($intakeMatch) {
                return $intakeMatch;
            }
        }

        return $this->curriculumVersions->publishedVersionForProgram((int) $student->program_id);
    }
}
