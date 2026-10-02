<?php

namespace App\Services;

use App\Mail\WorkFromHomeStatusMail;
use App\Models\Staff;
use App\Models\User;
use App\Models\WorkFromHomeRequest;
use App\Support\ModuleMail;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WorkFromHomeRequestService
{
    public function __construct(
        protected AuditService $auditService,
        protected PlatformNotificationService $notifications,
        protected HrSidebarNotificationService $hrSidebar,
    ) {}

    /**
     * Monthly bank: 1 day per Mon–Fri week in the month, earned only up to the
     * requested week (unused earlier weeks carry forward within the same month).
     * Unused days do not roll into the next month.
     *
     * @return array{
     *     year:int,
     *     month:int,
     *     weeks_in_month:int,
     *     week_of_month:int,
     *     earned:int,
     *     used:int,
     *     available:int,
     *     used_dates:list<string>
     * }
     */
    public function entitlementFor(Staff $staff, CarbonInterface $workDate): array
    {
        $date = Carbon::parse($workDate)->startOfDay();
        if ($date->isWeekend()) {
            throw new \InvalidArgumentException('Work from home is only allowed Monday to Friday.');
        }

        $year = (int) $date->year;
        $month = (int) $date->month;
        $weeks = $this->weekdayWeeksInMonth($year, $month);
        $weekOfMonth = $this->weekOfMonthIndex($date, $weeks);

        if ($weekOfMonth < 1) {
            throw new \InvalidArgumentException('The selected date is not a working day in that month.');
        }

        $earned = $weekOfMonth;
        $usedDates = $this->consumingDatesForMonth($staff->id, $year, $month);
        $used = count($usedDates);
        $available = max(0, $earned - $used);

        return [
            'year' => $year,
            'month' => $month,
            'weeks_in_month' => count($weeks),
            'week_of_month' => $weekOfMonth,
            'earned' => $earned,
            'used' => $used,
            'available' => $available,
            'used_dates' => $usedDates,
        ];
    }

    /**
     * Snapshot for the create form (current month through today, or a chosen date).
     *
     * @return array<string, mixed>
     */
    public function entitlementSnapshot(Staff $staff, ?CarbonInterface $asOf = null): array
    {
        $asOf = Carbon::parse($asOf ?? now())->startOfDay();
        if ($asOf->isWeekend()) {
            $asOf = $asOf->previous(CarbonInterface::FRIDAY);
        }

        return $this->entitlementFor($staff, $asOf);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(Staff $staff, User $user, array $data): WorkFromHomeRequest
    {
        $workDate = Carbon::parse($data['work_date'])->startOfDay();
        if ($workDate->isWeekend()) {
            throw new \RuntimeException('Work from home requests are Monday to Friday only.');
        }

        if (WorkFromHomeRequest::query()
            ->where('staff_id', $staff->id)
            ->whereDate('work_date', $workDate->toDateString())
            ->whereIn('status', WorkFromHomeRequest::CONSUMING_STATUSES)
            ->exists()) {
            throw new \RuntimeException('You already have a work from home request for '.$workDate->toFormattedDateString().'.');
        }

        // Free the date if an older rejected/returned/cancelled row exists (unique staff+date).
        WorkFromHomeRequest::query()
            ->where('staff_id', $staff->id)
            ->whereDate('work_date', $workDate->toDateString())
            ->whereNotIn('status', WorkFromHomeRequest::CONSUMING_STATUSES)
            ->delete();

        $entitlement = $this->entitlementFor($staff, $workDate);
        if ($entitlement['available'] < 1) {
            throw new \RuntimeException(
                'No work from home days available for that week. You earn 1 day per week, and unused days carry only within '
                .$workDate->format('F Y').' ('.$entitlement['used'].' used of '.$entitlement['earned'].' earned so far).'
            );
        }

        $supervisor = $staff->lineManager;
        $tasks = $this->normaliseTasks($data['remote_tasks'] ?? []);

        $request = DB::transaction(function () use ($staff, $data, $workDate, $entitlement, $supervisor, $tasks) {
            return WorkFromHomeRequest::query()->create([
                'request_code' => $this->generateRequestCode(),
                'staff_id' => $staff->id,
                'supervisor_staff_id' => $supervisor?->id,
                'supervisor_name' => $supervisor?->fullName(),
                'department_name' => $staff->department?->dept_name,
                'job_title' => $staff->job_title,
                'arrangement_type' => 'work_from_home',
                'work_date' => $workDate->toDateString(),
                'work_year' => $entitlement['year'],
                'work_month' => $entitlement['month'],
                'week_of_month' => $entitlement['week_of_month'],
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'total_hours' => $data['total_hours'] ?? $this->hoursBetween($data['start_time'] ?? null, $data['end_time'] ?? null),
                'period_start' => $data['period_start'] ?? $workDate->toDateString(),
                'period_end' => $data['period_end'] ?? $workDate->toDateString(),
                'remote_tasks' => $tasks,
                'considerations' => null,
                'status' => WorkFromHomeRequest::STATUS_PENDING_HR,
                'submitted_at' => now(),
            ]);
        });

        $this->auditService->log(
            'employee.wfh.submitted',
            'work_from_home_requests',
            $request->id,
            null,
            [
                'request_code' => $request->request_code,
                'staff_id' => $staff->id,
                'work_date' => $request->work_date?->toDateString(),
            ],
            'Employee submitted a work from home request',
            'success',
            $user->id,
        );

        $this->notifyHr($staff, $request);
        $this->hrSidebar->broadcastCounts();
        $this->emailEmployeeStatus($request->fresh(['staff']), 'submitted');

        if ($staff->user_id) {
            $summary = $this->employeeSummaryText($request);
            $this->notifications->notifyUser(
                $staff->user_id,
                'Work from home request submitted',
                $summary,
                'work_from_home_request',
                (string) $request->id,
                'normal',
                route('employee.wfh.show', $request),
                false, // dedicated WorkFromHomeStatusMail already sent
            );
        }

        return $request;
    }

    public function approve(WorkFromHomeRequest $request, Staff $hr, array $considerations, ?string $notes = null): WorkFromHomeRequest
    {
        if (! $request->isPendingHr()) {
            throw new \RuntimeException('Only requests pending HR can be approved.');
        }

        $request->fill([
            'status' => WorkFromHomeRequest::STATUS_APPROVED,
            'considerations' => $this->normaliseConsiderations($considerations, true),
            'hr_reviewed_by_staff_id' => $hr->id,
            'hr_reviewed_at' => now(),
            'hr_notes' => $notes,
        ])->save();

        $fresh = $request->fresh(['staff']);
        $this->notifyEmployee(
            $fresh,
            'Work from home approved',
            $this->employeeSummaryText($fresh, 'approved'),
            false,
        );
        $this->emailEmployeeStatus($fresh, 'approved');
        $this->hrSidebar->broadcastCounts();

        return $fresh;
    }

    public function reject(WorkFromHomeRequest $request, Staff $hr, string $notes): WorkFromHomeRequest
    {
        if (! $request->isPendingHr()) {
            throw new \RuntimeException('Only requests pending HR can be rejected.');
        }

        $request->fill([
            'status' => WorkFromHomeRequest::STATUS_REJECTED,
            'hr_reviewed_by_staff_id' => $hr->id,
            'hr_reviewed_at' => now(),
            'hr_notes' => $notes,
        ])->save();

        $fresh = $request->fresh(['staff']);
        $this->notifyEmployee(
            $fresh,
            'Work from home rejected',
            $this->employeeSummaryText($fresh, 'rejected'),
            false,
        );
        $this->emailEmployeeStatus($fresh, 'rejected');
        $this->hrSidebar->broadcastCounts();

        return $fresh;
    }

    public function returnToEmployee(WorkFromHomeRequest $request, Staff $hr, string $notes): WorkFromHomeRequest
    {
        if (! $request->isPendingHr()) {
            throw new \RuntimeException('Only requests pending HR can be returned.');
        }

        $request->fill([
            'status' => WorkFromHomeRequest::STATUS_RETURNED,
            'hr_reviewed_by_staff_id' => $hr->id,
            'hr_reviewed_at' => now(),
            'hr_notes' => $notes,
        ])->save();

        $fresh = $request->fresh(['staff']);
        $this->notifyEmployee(
            $fresh,
            'Work from home returned',
            $this->employeeSummaryText($fresh, 'returned'),
            false,
        );
        $this->emailEmployeeStatus($fresh, 'returned');
        $this->hrSidebar->broadcastCounts();

        return $fresh;
    }

    public function cancel(WorkFromHomeRequest $request, Staff $staff, User $user): WorkFromHomeRequest
    {
        if ($request->staff_id !== $staff->id) {
            throw new \RuntimeException('You can only cancel your own requests.');
        }

        if (! in_array($request->status, [WorkFromHomeRequest::STATUS_PENDING_HR, WorkFromHomeRequest::STATUS_RETURNED], true)) {
            throw new \RuntimeException('Only pending or returned requests can be cancelled.');
        }

        $request->fill([
            'status' => WorkFromHomeRequest::STATUS_CANCELLED,
        ])->save();

        $this->auditService->log(
            'employee.wfh.cancelled',
            'work_from_home_requests',
            $request->id,
            null,
            ['request_code' => $request->request_code],
            'Employee cancelled a work from home request',
            'success',
            $user->id,
        );

        $this->hrSidebar->broadcastCounts();

        return $request->fresh();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function hrIndex(array $filters): LengthAwarePaginator
    {
        $query = WorkFromHomeRequest::query()
            ->with(['staff.department', 'supervisor', 'hrReviewer'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id');

        $status = $filters['status'] ?? 'pending_hr';
        if ($status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('request_code', 'like', "%{$search}%")
                    ->orWhere('supervisor_name', 'like', "%{$search}%")
                    ->orWhereHas('staff', function ($staffQuery) use ($search) {
                        $staffQuery->where('employee_number', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('surname', 'like', "%{$search}%")
                            ->orWhere('other_names', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['year'])) {
            $query->where('work_year', (int) $filters['year']);
        }

        if (! empty($filters['month'])) {
            $query->where('work_month', (int) $filters['month']);
        }

        return $query->paginate(20)->withQueryString();
    }

    public function pendingHrCount(): int
    {
        if (! Schema::hasTable('work_from_home_requests')) {
            return 0;
        }

        return WorkFromHomeRequest::query()
            ->where('status', WorkFromHomeRequest::STATUS_PENDING_HR)
            ->count();
    }

    public function generateRequestCode(): string
    {
        $year = now()->year;
        $prefix = "WFH-{$year}-";

        $last = WorkFromHomeRequest::query()
            ->where('request_code', 'like', $prefix.'%')
            ->orderByDesc('request_code')
            ->value('request_code');

        $next = 1;
        if ($last) {
            $next = ((int) substr($last, strlen($prefix))) + 1;
        }

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Ordered list of week-start Mondays that include at least one Mon–Fri day in the month.
     *
     * @return list<string> Y-m-d Mondays
     */
    public function weekdayWeeksInMonth(int $year, int $month): array
    {
        $cursor = Carbon::create($year, $month, 1)->startOfDay();
        $end = $cursor->copy()->endOfMonth()->startOfDay();
        $weeks = [];

        while ($cursor->lte($end)) {
            if (! $cursor->isWeekend()) {
                $monday = $cursor->copy()->startOfWeek(CarbonInterface::MONDAY)->toDateString();
                if (! in_array($monday, $weeks, true)) {
                    $weeks[] = $monday;
                }
            }
            $cursor->addDay();
        }

        return $weeks;
    }

    /**
     * @param  list<string>  $weeks
     */
    public function weekOfMonthIndex(CarbonInterface $date, array $weeks): int
    {
        $monday = Carbon::parse($date)->startOfWeek(CarbonInterface::MONDAY)->toDateString();
        $index = array_search($monday, $weeks, true);

        return $index === false ? 0 : $index + 1;
    }

    /**
     * @return list<string>
     */
    private function consumingDatesForMonth(int $staffId, int $year, int $month): array
    {
        return WorkFromHomeRequest::query()
            ->where('staff_id', $staffId)
            ->where('work_year', $year)
            ->where('work_month', $month)
            ->whereIn('status', WorkFromHomeRequest::CONSUMING_STATUSES)
            ->orderBy('work_date')
            ->pluck('work_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->all();
    }

    /**
     * @param  array<int, mixed>  $tasks
     * @return list<string>
     */
    private function normaliseTasks(array $tasks): array
    {
        $out = [];
        foreach (array_values($tasks) as $task) {
            $text = trim((string) $task);
            if ($text !== '') {
                $out[] = mb_substr($text, 0, 500);
            }
            if (count($out) >= 5) {
                break;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $considerations
     * @return array<string, bool>
     */
    private function normaliseConsiderations(array $considerations, bool $requireAllYes): array
    {
        $out = [];
        foreach (WorkFromHomeRequest::CONSIDERATION_KEYS as $key) {
            $value = filter_var($considerations[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
            $out[$key] = $value;
        }

        if ($requireAllYes && in_array(false, $out, true)) {
            throw new \RuntimeException('All consideration checks must be Yes before approving a work from home request.');
        }

        return $out;
    }

    private function hoursBetween(?string $start, ?string $end): ?float
    {
        if (! $start || ! $end) {
            return null;
        }

        try {
            $from = Carbon::parse($start);
            $to = Carbon::parse($end);
        } catch (\Throwable) {
            return null;
        }

        if ($to->lte($from)) {
            return null;
        }

        return round($from->floatDiffInHours($to), 2);
    }

    private function notifyHr(Staff $staff, WorkFromHomeRequest $request): void
    {
        $rbac = app(RBACService::class);
        // Role-backed only — never fan out via hasPermission (dept membership / no-role users).
        $userIds = $rbac->hrNotifierUserIds(includeExecutives: false);
        $emailUserIds = $rbac->activeUserIdsWithRoles(['HR Manager', 'Assistant HR Manager']);

        if ($userIds === []) {
            return;
        }

        $summary = $staff->fullName().' requested WFH on '
            .$request->work_date?->toFormattedDateString()
            .' ('.$this->daysRequested($request).' day'
            .($this->daysRequested($request) === 1 ? '' : 's')
            .", {$request->request_code}).";

        $emailLookup = array_fill_keys($emailUserIds, true);

        foreach ($userIds as $userId) {
            $this->notifications->notifyUser(
                (int) $userId,
                'Work from home request',
                $summary,
                'work_from_home_request',
                (string) $request->id,
                'normal',
                route('hr.wfh.show', $request),
                isset($emailLookup[(int) $userId]),
            );
        }
    }

    private function notifyEmployee(WorkFromHomeRequest $request, string $title, string $body, bool $sendEmail = true): void
    {
        $userId = $request->staff?->user_id;
        if (! $userId) {
            return;
        }

        $this->notifications->notifyUser(
            $userId,
            $title,
            $body,
            'work_from_home_request',
            (string) $request->id,
            'normal',
            route('employee.wfh.show', $request),
            $sendEmail,
        );
    }

    private function emailEmployeeStatus(WorkFromHomeRequest $request, string $event): void
    {
        $staff = $request->staff;
        if (! $staff) {
            return;
        }

        $email = $staff->resolveErpEmail($staff->primary_email);
        if (! $email) {
            return;
        }

        $result = ModuleMail::trySend(
            ModuleMail::HR,
            $email,
            new WorkFromHomeStatusMail($request, $event, $staff->fullName() ?: 'Colleague'),
        );

        if (! $result['sent'] && $result['error']) {
            Log::warning('WFH status email failed', [
                'request_id' => $request->id,
                'event' => $event,
                'email' => $email,
                'error' => $result['error'],
            ]);
        }
    }

    private function daysRequested(WorkFromHomeRequest $request): int
    {
        $start = $request->period_start ?? $request->work_date;
        $end = $request->period_end ?? $request->work_date;

        if (! $start || ! $end) {
            return 1;
        }

        return max(1, $start->diffInDays($end) + 1);
    }

    private function employeeSummaryText(WorkFromHomeRequest $request, ?string $event = null): string
    {
        $days = $this->daysRequested($request);
        $date = $request->work_date?->toFormattedDateString() ?? 'the selected date';
        $periodStart = $request->period_start ?? $request->work_date;
        $periodEnd = $request->period_end ?? $request->work_date;
        $period = ($periodStart && $periodEnd && ! $periodStart->equalTo($periodEnd))
            ? $periodStart->toFormattedDateString().' to '.$periodEnd->toFormattedDateString()
            : $date;

        $prefix = match ($event) {
            'approved' => "Your request {$request->request_code} was approved.",
            'rejected' => "Your request {$request->request_code} was rejected.",
            'returned' => "Your request {$request->request_code} was returned for changes.",
            default => "Your request {$request->request_code} was sent to HR.",
        };

        $notes = '';
        if (in_array($event, ['rejected', 'returned', 'approved'], true) && filled($request->hr_notes)) {
            $notes = ' HR notes: '.$request->hr_notes;
        }

        return "{$prefix} Work date: {$date}. Period: {$period}. Days: {$days}.{$notes}";
    }
}
