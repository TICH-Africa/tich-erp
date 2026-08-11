<?php

namespace App\Services;

use App\Models\MpesaStkRequest;
use App\Models\Student;
use App\Services\Finance\MpesaSettingsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudentPortalDashboardService
{
    public function __construct(
        protected StudentAcademicRecordService $academicRecords,
        protected TimetableSchedulingService $timetableScheduling,
        protected MpesaSettingsService $mpesaSettings,
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
            'transcript' => $this->transcript($student),
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
        $student->loadMissing('applicant');

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

        $payableInvoices = $invoices->filter(function ($invoice) {
            return (float) $invoice->balance > 0
                && in_array($invoice->status, ['issued', 'partial', 'overdue'], true);
        })->values();

        $pendingStkRequests = MpesaStkRequest::query()
            ->with('invoice:id,invoice_number,invoice_type')
            ->where('student_id', $student->id)
            ->where('status', MpesaStkRequest::STATUS_PENDING)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->orderByDesc('created_at')
            ->get();

        $currentAccount = $accounts->first();
        $accountBalance = (float) $accounts->sum('outstanding_balance');
        $invoiceBalance = (float) $invoices->sum('balance');
        $studentBalance = (float) ($student->overall_balance ?? 0);

        return [
            'accounts' => $accounts,
            'invoices' => $invoices,
            'payments' => $payments,
            'payable_invoices' => $payableInvoices,
            'pending_stk_requests' => $pendingStkRequests,
            'statement' => $this->buildFinanceStatement($invoices, $payments),
            'credits' => $this->accountCredits($currentAccount),
            'default_phone' => (string) ($student->applicant?->phone_number ?? ''),
            'mpesa_enabled' => $this->mpesaSettings->isEnabled(),
            'summary' => [
                'outstanding_balance' => $accountBalance > 0 ? $accountBalance : ($invoiceBalance > 0 ? $invoiceBalance : $studentBalance),
                'total_paid' => (float) ($accounts->sum('total_paid') > 0
                    ? $accounts->sum('total_paid')
                    : $payments->sum('amount')),
                'total_chargeable' => (float) $accounts->sum('total_chargeable'),
                'fee_clearance_status' => $student->fee_clearance_status ?? 'pending',
                'is_cleared' => $accounts->contains(fn ($account) => (bool) $account->is_cleared)
                    || ($student->fee_clearance_status === 'cleared'),
                'payable_invoice_count' => $payableInvoices->count(),
            ],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $invoices
     * @param  \Illuminate\Support\Collection<int, object>  $payments
     * @return list<array<string, mixed>>
     */
    private function buildFinanceStatement(Collection $invoices, Collection $payments): array
    {
        $entries = collect();

        foreach ($invoices as $invoice) {
            $entries->push([
                'date' => $invoice->issue_date,
                'sort_at' => strtotime((string) ($invoice->issue_date ?? $invoice->created_at ?? now())).'-0',
                'type' => 'invoice',
                'reference' => $invoice->invoice_number,
                'description' => $invoice->description ?: ucwords(str_replace('_', ' ', (string) $invoice->invoice_type)),
                'debit' => (float) $invoice->amount,
                'credit' => 0.0,
            ]);
        }

        foreach ($payments as $payment) {
            $entries->push([
                'date' => $payment->payment_date,
                'sort_at' => strtotime((string) ($payment->payment_date ?? $payment->created_at ?? now())).'-1',
                'type' => 'payment',
                'reference' => $payment->payment_number,
                'description' => 'Payment · '.ucwords(str_replace('_', ' ', (string) $payment->payment_method)),
                'debit' => 0.0,
                'credit' => (float) $payment->amount,
            ]);
        }

        $running = 0.0;

        return $entries
            ->sortBy('sort_at')
            ->values()
            ->map(function (array $entry) use (&$running) {
                $running = round($running + $entry['debit'] - $entry['credit'], 2);
                $entry['running_balance'] = $running;

                return $entry;
            })
            ->reverse()
            ->values()
            ->all();
    }

    /**
     * @return array<string, float>
     */
    private function accountCredits(?object $account): array
    {
        if (! $account) {
            return [
                'scholarship' => 0.0,
                'helb' => 0.0,
                'sponsor' => 0.0,
                'work_study' => 0.0,
                'total' => 0.0,
            ];
        }

        $scholarship = (float) ($account->scholarship_amount ?? 0);
        $helb = (float) ($account->helb_amount ?? 0);
        $sponsor = (float) ($account->sponsor_amount ?? 0);
        $workStudy = (float) ($account->work_study_credit ?? 0);

        return [
            'scholarship' => $scholarship,
            'helb' => $helb,
            'sponsor' => $sponsor,
            'work_study' => $workStudy,
            'total' => round($scholarship + $helb + $sponsor + $workStudy, 2),
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

        $timetables = collect();

        if ($curriculum && $student->program_id) {
            foreach (array_keys(TimetableSchedulingService::timetableKinds()) as $kind) {
                $published = $this->timetableScheduling->publishedTimetable(
                    (int) $student->program_id,
                    $curriculum->id,
                    (int) $teachingPeriod,
                    $kind
                );

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
            'is_provisional' => false,
        ];
    }

    private function mayViewProvisionalCurriculum(Student $student): bool
    {
        if ($student->portal_activated_at) {
            return true;
        }

        return in_array($student->enrollment_status, ['active', 'enrolled', 'registered'], true);
    }

    /**
     * @return array<string, mixed>
     */
    private function transcript(Student $student): array
    {
        $unitsCompleted = (int) DB::table('grade_records')
            ->where('student_id', $student->id)
            ->count();

        return [
            'available' => $unitsCompleted > 0,
            'units_completed' => $unitsCompleted,
        ];
    }
}
