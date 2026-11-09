<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\MpesaStkRequest;
use App\Models\ProgramTimetableSession;
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
    public function forStudent(Student $student, array $biodata, ?string $section = null, ?string $tab = null): array
    {
        $student->loadMissing(['applicant', 'program', 'campus']);

        if ($section === 'academics' && $tab === 'units') {
            return $this->forStudentUnitsTab($student, $biodata);
        }

        $academics = $this->academicRecords->withPortalProgress($student, $this->academicRecords->onlyRegisteredUnits(
            $this->academicRecords->forStudent($student, $this->mayViewProvisionalCurriculum($student))
        ));
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
     * @return array<string, mixed>
     */
    private function forStudentUnitsTab(Student $student, array $biodata): array
    {
        $academics = $this->academicRecords->academicsUnitsPageForStudent(
            $student,
            $this->mayViewProvisionalCurriculum($student),
        );
        $academics = $this->academicRecords->onlyRegisteredUnits($academics);
        $finance = $this->finance($student);

        return [
            'overview_stats' => $this->overviewStats($student, $biodata, $academics, $finance),
            'academics' => $academics,
            'timetable' => [
                'timetables' => collect(),
                'timetable' => null,
                'sessions' => collect(),
                'teaching_period' => null,
                'period_label' => null,
                'is_provisional' => false,
            ],
            'transcript' => ['available' => false, 'units_completed' => 0],
            'finance' => $finance,
        ];
    }

    /**
     * @param  array<string, mixed>  $biodata
     * @return array<string, mixed>
     */
    public function overviewForMobile(Student $student, array $biodata): array
    {
        $summaries = $this->academicRecords->rosterSummaries([$student->id])->get($student->id, [
            'registered_units' => 0,
            'grades' => 0,
            'cat_scores' => 0,
            'exam_results' => 0,
        ]);
        $financeSummary = $this->financeSummaryForMobile($student);

        return [
            'overview_stats' => [
                'enrollment_status' => ucfirst($student->enrollment_status ?? 'unknown'),
                'application_status' => ucwords(str_replace('_', ' ', (string) ($biodata['application']['status'] ?? ''))),
                'fee_clearance' => ucfirst($student->fee_clearance_status ?? 'pending'),
                'outstanding_balance' => (float) ($financeSummary['outstanding_balance'] ?? $student->overall_balance ?? 0),
                'document_count' => (int) ($biodata['document_count'] ?? $biodata['documents']->count()),
                'registered_unit_count' => (int) ($summaries['registered_units'] ?? 0),
                'grade_count' => (int) ($summaries['grades'] ?? 0),
                'curriculum_unit_count' => (int) ($summaries['registered_units'] ?? 0),
            ],
            'finance_summary' => $financeSummary,
            'transcript' => $this->transcript($student),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function academicsForMobile(Student $student): array
    {
        return [
            'academics' => $this->academicRecords->academicsHubForMobile(
                $student,
                $this->mayViewProvisionalCurriculum($student),
            ),
            'transcript' => $this->transcript($student),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function academicsUnitsForMobile(Student $student): array
    {
        return $this->academicRecords->mobileUnitsForStudent(
            $student,
            $this->mayViewProvisionalCurriculum($student),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function academicsContentForMobile(Student $student): array
    {
        return $this->academicRecords->mobileContentForStudent(
            $student,
            $this->mayViewProvisionalCurriculum($student),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function academicsAssessmentsForMobile(Student $student): array
    {
        return $this->academicRecords->mobileAssessmentsForStudent(
            $student,
            $this->mayViewProvisionalCurriculum($student),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function timetableForMobile(Student $student): array
    {
        $academics = $this->academicRecords->timetableContextForStudent(
            $student,
            $this->mayViewProvisionalCurriculum($student),
        );

        return [
            'timetable' => $this->timetable($student, $academics, mobile: true),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function financeForMobile(Student $student): array
    {
        return [
            'finance' => $this->finance($student, mobile: true),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function financeSummaryForMobile(Student $student): array
    {
        $student->loadMissing('applicant');

        $accountBalance = (float) DB::table('student_accounts')
            ->where('student_id', $student->id)
            ->sum('outstanding_balance');

        $invoiceBalance = (float) DB::table('invoices')
            ->where('student_id', $student->id)
            ->sum('balance');

        $studentBalance = (float) ($student->overall_balance ?? 0);

        $isCleared = DB::table('student_accounts')
            ->where('student_id', $student->id)
            ->where('is_cleared', 1)
            ->exists()
            || ($student->fee_clearance_status === 'cleared');

        $payableCount = (int) DB::table('invoices')
            ->where('student_id', $student->id)
            ->where('balance', '>', 0)
            ->whereIn('status', ['issued', 'partial', 'overdue'])
            ->count();

        return [
            'outstanding_balance' => $accountBalance > 0 ? $accountBalance : ($invoiceBalance > 0 ? $invoiceBalance : $studentBalance),
            'fee_clearance_status' => $student->fee_clearance_status ?? 'pending',
            'is_cleared' => $isCleared,
            'payable_invoice_count' => $payableCount,
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
            'curriculum_unit_count' => $academics['programme_unit_count'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function finance(Student $student, bool $mobile = false): array
    {
        $student->loadMissing('applicant');

        $rowLimit = $mobile ? 40 : 50;

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
            ->limit($rowLimit)
            ->get();

        $payments = DB::table('payments')
            ->where('student_id', $student->id)
            ->orderByDesc('payment_date')
            ->limit($rowLimit)
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
            'statement' => $this->buildFinanceStatement($invoices, $payments, $mobile ? 40 : null),
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
    private function buildFinanceStatement(Collection $invoices, Collection $payments, ?int $limit = null): array
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

        $statement = $entries
            ->sortBy('sort_at')
            ->values()
            ->map(function (array $entry) use (&$running) {
                $running = round($running + $entry['debit'] - $entry['credit'], 2);
                $entry['running_balance'] = $running;

                return $entry;
            })
            ->reverse()
            ->values();

        if ($limit !== null) {
            $statement = $statement->take($limit);
        }

        return $statement->all();
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
    private function timetable(Student $student, array $academics, bool $mobile = false): array
    {
        $curriculum = $academics['curriculum'] ?? null;
        $period = $academics['current_period'] ?? null;
        $currentSemester = $academics['current_semester'] ?? null;
        $registrations = collect($academics['registrations'] ?? []);
        $program = $student->program;

        // The year of study decides which semesters belong to this student, so a second-year is
        // never handed the first-year schedule that shares the same intake.
        $yearOfStudy = $program
            ? app(StudentUnitRegistrationService::class)->yearOfStudy($student, $program, $curriculum)
            : null;
        $yearPeriods = $this->yearPeriodRange($program, $yearOfStudy['year'] ?? null);

        $candidatePeriods = collect([
            $yearOfStudy['period'] ?? null,
            $period?->semester,
            $currentSemester?->semester_number ?? null,
            $registrations->first()?->semester_number ?? null,
        ])->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        if ($candidatePeriods->isEmpty()) {
            $candidatePeriods = collect([1]);
        }

        $timetables = collect();
        $teachingPeriod = (int) $candidatePeriods->first();
        $isProvisional = false;

        if ($student->program_id) {
            $programId = (int) $student->program_id;
            $curriculumId = $curriculum?->id ? (int) $curriculum->id : null;
            $campusId = $student->campus_id ? (int) $student->campus_id : null;

            foreach ($candidatePeriods as $candidatePeriod) {
                $found = $this->resolvePublishedTimetables($programId, $curriculumId, (int) $candidatePeriod, $campusId, $mobile);
                if ($found->isNotEmpty()) {
                    $timetables = $found;
                    $teachingPeriod = (int) $candidatePeriod;
                    break;
                }
            }

            if ($timetables->isEmpty()) {
                $found = $this->resolveLatestPublishedTimetables($programId, $curriculumId, $campusId, $yearPeriods, $mobile);
                if ($found->isNotEmpty()) {
                    $timetables = $found;
                    $teachingPeriod = (int) ($timetables->first()->teaching_period ?: $teachingPeriod);
                }
            }

            if ($timetables->isEmpty()) {
                $drafts = $this->resolveDraftTimetables($programId, $curriculumId, $teachingPeriod, $campusId, $mobile);
                if ($drafts->isNotEmpty()) {
                    $timetables = $drafts;
                    $isProvisional = true;
                }
            }
        }

        // The campus class and the remote class are posted on separate timetables for the same year
        // and semester, or on one posting that serves both cohorts, so each student is shown what is
        // addressed to them. A school that keeps a single posting is unaffected - nothing of theirs
        // gets filtered away.
        $wantedMode = $student->isOnlineStudent() ? 'online' : 'physical';
        $modeMatched = $timetables->filter(
            fn ($timetable) => $timetable->servesMode($wantedMode)
        );

        if ($modeMatched->isNotEmpty()) {
            $timetables = $modeMatched->values();
        }

        $primary = $this->currentPosting($timetables, $period, $teachingPeriod);
        $template = $mobile ? null : $primary?->template?->load(['days']);
        $lessonReminderSessions = collect();

        if ($mobile) {
            $lessonTimetable = $timetables
                ->filter(fn ($timetable) => $timetable->timetable_kind === 'lesson')
                ->sortByDesc(fn ($timetable) => (int) ($primary?->id === $timetable->id))
                ->first();

            if ($lessonTimetable) {
                $lessonReminderSessions = ProgramTimetableSession::query()
                    ->where('program_timetable_id', $lessonTimetable->id)
                    ->whereNotNull('day_of_week')
                    ->whereNotNull('start_time')
                    ->with(['unit:id,unit_code,unit_name', 'staff', 'room'])
                    ->orderBy('day_of_week')
                    ->orderBy('start_time')
                    ->limit(48)
                    ->get();

                $lessonReminderSessions = $this->sessionsForMode($lessonReminderSessions, $wantedMode);
            }
        }

        // Only classes for units they hold stay on the grid; an unregistered student keeps the whole posting.
        $heldUnitIds = collect($academics['registered_units'] ?? [])
            ->pluck('unit_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        foreach ($timetables as $posted) {
            $posted->setRelation('sessions', $this->sessionsForMode($posted->sessions, $wantedMode));
        }

        if ($heldUnitIds->isNotEmpty()) {
            foreach ($timetables as $posted) {
                $posted->setRelation('sessions', $posted->sessions->filter(
                    fn ($session) => ! $session->unit_id || $heldUnitIds->contains((int) $session->unit_id)
                )->values());
            }
        }

        return [
            'timetables' => $timetables,
            'timetable' => $primary,
            'sessions' => $primary?->sessions ?? collect(),
            'lesson_reminder_sessions' => $lessonReminderSessions,
            'template' => $template,
            'day_labels' => TimetableTemplateService::dayLabels(),
            'segment_types' => TimetableTemplateService::segmentTypes(),
            'active_days' => $template?->activeDayNumbers() ?? [1, 2, 3, 4, 5],
            'teaching_period' => $teachingPeriod,
            'period_label' => $program ? $program->periodLabel($teachingPeriod) : 'Semester '.$teachingPeriod,
            'year_of_study' => $yearOfStudy['year'] ?? null,
            'is_provisional' => $isProvisional,
        ];
    }

    /**
     * Keep the classes addressed to this student's cohort, unless that would empty the posting.
     *
     * @param  \Illuminate\Support\Collection<int, ProgramTimetableSession>  $sessions
     * @return \Illuminate\Support\Collection<int, ProgramTimetableSession>
     */
    private function sessionsForMode($sessions, string $mode)
    {
        $addressed = $sessions->filter(
            fn ($session) => $session->servesMode($mode)
        )->values();

        return $addressed->isEmpty() ? $sessions : $addressed;
    }

    /**
     * The teaching periods that belong to one year of study.
     *
     * @return array{first: int, last: int}|null
     */
    private function yearPeriodRange(?AcademicProgram $program, ?int $year = null): ?array
    {
        if (! $program || ! $year) {
            return null;
        }

        $terms = max(1, (int) $program->termsPerYear());
        $first = (($year - 1) * $terms) + 1;

        // The year, not the programme's configured duration, bounds the range - a cohort pushed on
        // to Year 2 of a one-year programme record still has that year's semesters.
        return [
            'first' => $first,
            'last' => $first + $terms - 1,
        ];
    }

    /**
     * The posting that applies today: this week's timetable when the semester runs one per week,
     * otherwise this month's, otherwise the timetable that covers the whole semester.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\ProgramTimetable>  $timetables
     */
    private function currentPosting(Collection $timetables, mixed $period, int $teachingPeriod): ?\App\Models\ProgramTimetable
    {
        $lessons = $timetables->filter(fn ($timetable) => $timetable->timetable_kind === 'lesson')->values();
        $candidate = $lessons->isNotEmpty() ? $lessons : $timetables->values();

        $start = $period && (int) $period->semester === $teachingPeriod && method_exists($period, 'effectiveLearningStart')
            ? $period->effectiveLearningStart()
            : null;

        if ($start) {
            $elapsedDays = max(0, (int) floor($start->diffInDays(now(), false)));
            $week = intdiv($elapsedDays, 7) + 1;
            $month = $start->diffInMonths(now()) + 1;

            $byWeek = $candidate->first(fn ($timetable) => $timetable->cycle_type === 'weekly' && (int) $timetable->cycle_no === $week);

            if ($byWeek) {
                return $byWeek;
            }

            $byMonth = $candidate->first(fn ($timetable) => $timetable->cycle_type === 'monthly' && (int) $timetable->cycle_no === $month);

            if ($byMonth) {
                return $byMonth;
            }
        }

        $documents = app(ProgramTimetableDocumentService::class);

        return $candidate->first(fn ($timetable) => in_array($timetable->cycle_type, [null, '', 'semester'], true))
            ?? $candidate->first(fn ($timetable) => $documents->hasViewableDocument($timetable))
            ?? $candidate->first();
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\ProgramTimetable>
     */
    private function resolvePublishedTimetables(int $programId, ?int $curriculumId, int $teachingPeriod, ?int $campusId, bool $mobile = false): Collection
    {
        $withSessions = ! $mobile;
        $found = $this->timetableScheduling->publishedTimetablesForPeriod($programId, $curriculumId, $teachingPeriod, $withSessions);

        if ($found->isEmpty() && $curriculumId) {
            $found = $this->timetableScheduling->publishedTimetablesForPeriod($programId, null, $teachingPeriod, $withSessions);
        }

        if ($campusId && $found->isNotEmpty()) {
            $campusMatched = $found->filter(fn ($timetable) => (int) ($timetable->campus_id ?? 0) === $campusId);
            if ($campusMatched->isNotEmpty()) {
                return $campusMatched->values();
            }
        }

        return $found->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\ProgramTimetable>
     */
    private function resolveLatestPublishedTimetables(int $programId, ?int $curriculumId, ?int $campusId, ?array $yearPeriods = null, bool $mobile = false): Collection
    {
        $query = \App\Models\ProgramTimetable::query()
            ->when(
                $mobile,
                fn ($q) => $q->withCount('sessions'),
                fn ($q) => $q->with(['sessions.unit', 'sessions.staff', 'sessions.room', 'template.days']),
            )
            ->where('program_id', $programId)
            ->where('status', 'published')
            ->when($yearPeriods, fn ($q) => $q->whereBetween('teaching_period', [$yearPeriods['first'], $yearPeriods['last']]))
            ->orderByDesc('teaching_period')
            ->orderByDesc('published_at');

        if ($curriculumId) {
            $query->where(function ($inner) use ($curriculumId) {
                $inner->where('curriculum_version_id', $curriculumId)
                    ->orWhereNull('curriculum_version_id');
            });
        }

        if ($campusId) {
            $query->orderByRaw('CASE WHEN campus_id = ? THEN 0 ELSE 1 END', [$campusId]);
        }

        // Only the newest semester's postings carry over - all of them, so each week or month the
        // office published stays visible.
        $all = $query->get();
        $newestPeriod = $all->max('teaching_period');

        return $all
            ->filter(fn ($timetable) => (int) $timetable->teaching_period === (int) $newestPeriod)
            ->sortBy([['timetable_kind', 'asc'], ['cycle_no', 'asc']])
            ->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\ProgramTimetable>
     */
    private function resolveDraftTimetables(int $programId, ?int $curriculumId, int $teachingPeriod, ?int $campusId, bool $mobile = false): Collection
    {
        $query = \App\Models\ProgramTimetable::query()
            ->when(
                $mobile,
                fn ($q) => $q->withCount('sessions'),
                fn ($q) => $q->with(['sessions.unit', 'sessions.staff', 'sessions.room', 'template.days']),
            )
            ->where('program_id', $programId)
            ->whereIn('status', ['draft'])
            ->where('teaching_period', $teachingPeriod)
            ->orderBy('timetable_kind')
            ->orderByRaw('cycle_no is null')
            ->orderBy('cycle_no')
            ->orderByDesc('updated_at');

        if ($curriculumId) {
            $query->where(function ($inner) use ($curriculumId) {
                $inner->where('curriculum_version_id', $curriculumId)
                    ->orWhereNull('curriculum_version_id');
            });
        }

        if ($campusId) {
            $query->orderByRaw('CASE WHEN campus_id = ? THEN 0 ELSE 1 END', [$campusId]);
        }

        return $query->get()->values();
    }

    public function mayViewProvisionalCurriculum(Student $student): bool
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
