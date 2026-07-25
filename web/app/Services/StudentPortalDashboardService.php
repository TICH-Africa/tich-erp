<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Facades\DB;

class StudentPortalDashboardService
{
    public function __construct(
        protected StudentAcademicRecordService $academicRecords,
        protected TimetableSchedulingService $timetableScheduling,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forStudent(Student $student, array $biodata): array
    {
        $student->loadMissing(['applicant', 'program', 'campus']);

        $academics = $this->academicRecords->forStudent($student, $this->mayViewProvisionalCurriculum($student));
        $finance = $this->finance($student);

        return [
            'overview_stats' => $this->overviewStats($student, $biodata, $academics, $finance),
            'academics' => $academics,
            'timetable' => $this->timetable($student, $academics),
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

    /**
     * @param  array<string, mixed>  $academics
     * @return array<string, mixed>
     */
    private function timetable(Student $student, array $academics): array
    {
        $curriculum = $academics['curriculum'] ?? null;
        $period = $academics['current_period'] ?? null;
        $teachingPeriod = $period?->semester ?? 1;
        $canViewProvisional = $student->portal_activated_at || $student->enrollment_status === 'active';

        $timetables = collect();

        if ($curriculum && $student->program_id) {
            foreach (array_keys(TimetableSchedulingService::timetableKinds()) as $kind) {
                $published = $this->timetableScheduling->publishedTimetable(
                    (int) $student->program_id,
                    $curriculum->id,
                    (int) $teachingPeriod,
                    $kind
                );

                if (! $published && $canViewProvisional) {
                    $published = $this->timetableScheduling->latestTimetable(
                        (int) $student->program_id,
                        $curriculum->id,
                        (int) $teachingPeriod,
                        $kind
                    );
                }

                if ($published) {
                    $timetables->push($published);
                }
            }
        }

        $primary = $timetables->firstWhere('timetable_kind', 'lesson') ?? $timetables->first();
        $template = $primary?->template?->load(['segments', 'days']);

        return [
            'timetables' => $timetables,
            'timetable' => $primary,
            'sessions' => $primary?->sessions ?? collect(),
            'template' => $template,
            'day_labels' => TimetableTemplateService::dayLabels(),
            'segment_types' => TimetableTemplateService::segmentTypes(),
            'active_days' => $template?->activeDayNumbers() ?? [1, 2, 3, 4, 5],
            'teaching_period' => $teachingPeriod,
            'is_provisional' => $primary && ! $primary->isPublished(),
        ];
    }

    private function mayViewProvisionalCurriculum(Student $student): bool
    {
        if ($student->portal_activated_at) {
            return true;
        }

        return in_array($student->enrollment_status, ['active', 'enrolled', 'registered'], true);
    }
}
