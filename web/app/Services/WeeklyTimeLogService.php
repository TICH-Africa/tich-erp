<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\StaffContract;
use App\Models\StaffWeeklyTimeLog;
use App\Models\StaffWeeklyTimeLogDay;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WeeklyTimeLogService
{
    public function __construct(
        protected PlatformNotificationService $notifications,
        protected StaffPortalService $staffPortal,
    ) {}

    /**
     * Week buckets for a calendar month.
     * Week 1 = from the 1st through the first Sunday (may start mid-week).
     * Later weeks = Mon–Sun until the month ends.
     *
     * @return list<array{week_number: int, week_ref: string, period_start: string, period_end: string, label: string, days: list<array{date: string, day_label: string, in_month: bool}>}>
     */
    public function weeksForMonth(int $year, int $month): array
    {
        $first = Carbon::create($year, $month, 1)->startOfDay();
        $last = $first->copy()->endOfMonth()->startOfDay();
        $weeks = [];
        $weekNumber = 1;

        // Week 1: 1st → first Sunday (or month end if sooner)
        $week1End = $first->copy();
        while ($week1End->dayOfWeek !== Carbon::SUNDAY && $week1End->lt($last)) {
            $week1End->addDay();
        }
        if ($week1End->gt($last)) {
            $week1End = $last->copy();
        }
        $weeks[] = $this->buildWeekBucket($year, $month, $weekNumber, $first, $week1End);
        $weekNumber++;

        $cursor = $week1End->copy()->addDay();
        while ($cursor->lte($last)) {
            $weekStart = $cursor->copy();
            $weekEnd = $cursor->copy()->next(Carbon::SUNDAY);
            if ($weekEnd->gt($last)) {
                $weekEnd = $last->copy();
            }
            $weeks[] = $this->buildWeekBucket($year, $month, $weekNumber, $weekStart, $weekEnd);
            $weekNumber++;
            $cursor = $weekEnd->copy()->addDay();
        }

        return $weeks;
    }

    /**
     * @return array{week_number: int, week_ref: string, period_start: string, period_end: string, label: string, days: list<array{date: string, day_label: string, in_month: bool}>}
     */
    private function buildWeekBucket(int $year, int $month, int $weekNumber, Carbon $periodStart, Carbon $periodEnd): array
    {
        // Align display grid to Mon–Sun covering the period
        $gridStart = $periodStart->copy();
        if ($gridStart->dayOfWeek !== Carbon::MONDAY) {
            $gridStart = $gridStart->copy()->previous(Carbon::MONDAY);
        }
        $gridEnd = $periodEnd->copy();
        if ($gridEnd->dayOfWeek !== Carbon::SUNDAY) {
            $gridEnd = $gridEnd->copy()->next(Carbon::SUNDAY);
        }

        $days = [];
        $cursor = $gridStart->copy();
        while ($cursor->lte($gridEnd)) {
            $inMonth = ((int) $cursor->month === $month && (int) $cursor->year === $year)
                && $cursor->gte($periodStart) && $cursor->lte($periodEnd);
            $label = match ((int) $cursor->dayOfWeek) {
                Carbon::MONDAY => 'MON',
                Carbon::TUESDAY => 'TUE',
                Carbon::WEDNESDAY => 'WED',
                Carbon::THURSDAY => 'THUR',
                Carbon::FRIDAY => 'FRI',
                Carbon::SATURDAY => 'SAT',
                default => 'SUN',
            };
            $days[] = [
                'date' => $cursor->toDateString(),
                'day_label' => $label,
                'in_month' => $inMonth,
            ];
            $cursor->addDay();
        }

        $ref = sprintf('%04d-%02d-W%d', $year, $month, $weekNumber);

        return [
            'week_number' => $weekNumber,
            'week_ref' => $ref,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'label' => 'Week '.$weekNumber.' ('.$periodStart->format('d M').' – '.$periodEnd->format('d M').')',
            'days' => $days,
        ];
    }

    public function currentWeekMeta(?Carbon $today = null): array
    {
        $today ??= now();
        $weeks = $this->weeksForMonth((int) $today->year, (int) $today->month);
        foreach ($weeks as $week) {
            if ($today->toDateString() >= $week['period_start'] && $today->toDateString() <= $week['period_end']) {
                return [
                    'year' => (int) $today->year,
                    'month' => (int) $today->month,
                    'week' => $week,
                ];
            }
        }

        return [
            'year' => (int) $today->year,
            'month' => (int) $today->month,
            'week' => $weeks[0] ?? null,
        ];
    }

    /**
     * Departments this staff may log against (home dept + role depts + contract depts).
     *
     * @return Collection<int, Department>
     */
    public function departmentsForStaff(Staff $staff): Collection
    {
        $ids = collect([(int) $staff->department_id])->filter();

        if ($staff->user_id && Schema::hasTable('user_roles') && Schema::hasColumn('user_roles', 'department_id')) {
            $ids = $ids->merge(
                DB::table('user_roles')
                    ->where('user_id', $staff->user_id)
                    ->whereNotNull('department_id')
                    ->pluck('department_id')
            );
        }

        if (Schema::hasTable('staff_contracts')) {
            $ids = $ids->merge(
                StaffContract::query()
                    ->where('staff_id', $staff->id)
                    ->whereNotNull('department_id')
                    ->pluck('department_id')
            );
        }

        $ids = $ids->map(fn ($id) => (int) $id)->unique()->filter()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Department::query()
            ->whereIn('id', $ids->all())
            ->where('is_active', true)
            ->orderBy('dept_name')
            ->get(['id', 'dept_code', 'dept_name']);
    }

    public function canSelfEndorse(Staff $staff): bool
    {
        if (! $staff->line_manager_id) {
            return true;
        }

        $deptId = $staff->department_id;
        if (! $deptId) {
            return true;
        }

        $others = Staff::query()
            ->where('department_id', $deptId)
            ->where('id', '!=', $staff->id)
            ->where(function ($q) {
                $q->whereNull('employment_status')
                    ->orWhereNotIn('employment_status', ['terminated', 'resigned', 'deceased']);
            })
            ->count();

        // Alone in department, or no one else reports under a different manager chain — allow self.
        if ($others === 0) {
            return true;
        }

        // Highest ranking heuristic: nobody in the dept lists this person as subordinate? 
        // If this staff has subordinates in the dept and no line manager in same dept, treat as HOD.
        $hasSubordinates = Staff::query()
            ->where('line_manager_id', $staff->id)
            ->where('department_id', $deptId)
            ->exists();

        $managerInSameDept = $staff->line_manager_id
            ? Staff::query()->where('id', $staff->line_manager_id)->where('department_id', $deptId)->exists()
            : false;

        return $hasSubordinates && ! $managerInSameDept;
    }

    public function findOrCreateDraft(Staff $staff, int $year, int $month, int $weekNumber): StaffWeeklyTimeLog
    {
        $weeks = $this->weeksForMonth($year, $month);
        $meta = collect($weeks)->firstWhere('week_number', $weekNumber);
        if (! $meta) {
            throw new \RuntimeException('That week does not exist for the selected month.');
        }

        $existing = StaffWeeklyTimeLog::query()
            ->where('staff_id', $staff->id)
            ->where('log_year', $year)
            ->where('log_month', $month)
            ->where('week_number', $weekNumber)
            ->first();

        if ($existing) {
            return $existing->load('days');
        }

        return DB::transaction(function () use ($staff, $year, $month, $weekNumber, $meta) {
            $log = StaffWeeklyTimeLog::query()->create([
                'log_code' => $this->nextCode(),
                'staff_id' => $staff->id,
                'log_year' => $year,
                'log_month' => $month,
                'week_number' => $weekNumber,
                'week_ref' => $meta['week_ref'],
                'period_start' => $meta['period_start'],
                'period_end' => $meta['period_end'],
                'status' => StaffWeeklyTimeLog::STATUS_DRAFT,
            ]);

            $this->seedDaysFromAttendance($log, $staff, $meta['days']);

            return $log->fresh('days');
        });
    }

    /**
     * @param  list<array{date: string, day_label: string, in_month: bool}>  $dayMeta
     */
    public function seedDaysFromAttendance(StaffWeeklyTimeLog $log, Staff $staff, array $dayMeta): void
    {
        $dates = collect($dayMeta)->pluck('date');
        $attendance = StaffAttendance::query()
            ->where('staff_id', $staff->id)
            ->whereIn('attendance_date', $dates->all())
            ->get()
            ->keyBy(fn (StaffAttendance $row) => $row->attendance_date->toDateString());

        $initials = $this->initialsFor($staff);
        $defaultDept = $staff->department_id ? [(int) $staff->department_id] : [];

        foreach (array_values($dayMeta) as $i => $day) {
            $att = $attendance->get($day['date']);
            $timeIn = null;
            $timeOut = null;
            $hours = null;

            if ($day['in_month'] && $att) {
                $timeIn = $att->clock_in_time ? Carbon::parse($att->clock_in_time)->format('H:i') : null;
                $timeOut = $att->clock_out_time ? Carbon::parse($att->clock_out_time)->format('H:i') : null;
                $hours = $att->work_hours !== null ? (float) $att->work_hours : $this->hoursBetween($timeIn, $timeOut);
            }

            StaffWeeklyTimeLogDay::query()->create([
                'weekly_time_log_id' => $log->id,
                'work_date' => $day['date'],
                'day_label' => $day['day_label'],
                'in_month' => $day['in_month'],
                'time_in' => $day['in_month'] ? $timeIn : null,
                'time_out' => $day['in_month'] ? $timeOut : null,
                'tasks_accomplished' => null,
                'initials' => $day['in_month'] ? $initials : null,
                'department_ids' => $day['in_month'] ? $defaultDept : [],
                'approval_sign' => null,
                'total_hours' => $day['in_month'] ? $hours : null,
                'total_units' => null,
                'display_order' => $i,
            ]);
        }

        $this->recomputeTotals($log);
    }

    public function hoursBetween(?string $timeIn, ?string $timeOut): ?float
    {
        if (! $timeIn || ! $timeOut) {
            return null;
        }
        try {
            $in = Carbon::createFromFormat('H:i', substr($timeIn, 0, 5));
            $out = Carbon::createFromFormat('H:i', substr($timeOut, 0, 5));
            if ($out->lt($in)) {
                $out->addDay();
            }

            return round($in->diffInMinutes($out) / 60, 2);
        } catch (\Throwable) {
            return null;
        }
    }

    public function initialsFor(Staff $staff): string
    {
        $parts = array_filter([$staff->first_name, $staff->middle_name, $staff->surname]);
        $initials = '';
        foreach ($parts as $part) {
            $initials .= Str::upper(Str::substr(trim((string) $part), 0, 1));
        }

        return $initials !== '' ? $initials : 'XX';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function saveDraft(StaffWeeklyTimeLog $log, Staff $staff, array $payload): StaffWeeklyTimeLog
    {
        if (! $log->isEditableByEmployee()) {
            throw new \RuntimeException('This time log is locked and cannot be edited.');
        }

        return DB::transaction(function () use ($log, $staff, $payload) {
            $daysInput = is_array($payload['days'] ?? null) ? $payload['days'] : [];
            $allowedDeptIds = $this->departmentsForStaff($staff)->pluck('id')->map(fn ($id) => (int) $id)->all();

            foreach ($log->days as $day) {
                $key = $day->work_date->toDateString();
                $row = $daysInput[$key] ?? $daysInput[(string) $day->id] ?? null;
                if (! is_array($row) || ! $day->in_month) {
                    continue;
                }

                $timeIn = $this->normalizeTime($row['time_in'] ?? null);
                $timeOut = $this->normalizeTime($row['time_out'] ?? null);
                $hours = isset($row['total_hours']) && $row['total_hours'] !== ''
                    ? round((float) $row['total_hours'], 2)
                    : $this->hoursBetween($timeIn, $timeOut);

                $deptIds = collect(is_array($row['department_ids'] ?? null) ? $row['department_ids'] : [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => in_array($id, $allowedDeptIds, true))
                    ->unique()
                    ->values()
                    ->all();

                $day->update([
                    'time_in' => $timeIn,
                    'time_out' => $timeOut,
                    'tasks_accomplished' => trim((string) ($row['tasks_accomplished'] ?? '')) ?: null,
                    'initials' => trim((string) ($row['initials'] ?? '')) ?: $this->initialsFor($staff),
                    'department_ids' => $deptIds,
                    'total_hours' => $hours,
                    'total_units' => isset($row['total_units']) && $row['total_units'] !== ''
                        ? round((float) $row['total_units'], 2)
                        : null,
                ]);
            }

            if ($log->status === StaffWeeklyTimeLog::STATUS_RETURNED) {
                $log->update([
                    'status' => StaffWeeklyTimeLog::STATUS_DRAFT,
                    'hr_notes' => null,
                    'hr_reviewed_at' => null,
                    'hr_reviewed_by_staff_id' => null,
                ]);
            }

            $this->recomputeTotals($log);

            return $log->fresh(['days', 'staff']);
        });
    }

    public function submit(StaffWeeklyTimeLog $log, Staff $staff, bool $selfEndorse = false): StaffWeeklyTimeLog
    {
        if ($log->manager_signed_at) {
            throw new \RuntimeException('This time log is locked after line-manager endorsement.');
        }
        if (! in_array($log->status, [
            StaffWeeklyTimeLog::STATUS_DRAFT,
            StaffWeeklyTimeLog::STATUS_RETURNED,
            StaffWeeklyTimeLog::STATUS_PENDING_HR,
        ], true)) {
            throw new \RuntimeException('This time log cannot be submitted.');
        }

        $signedName = trim($staff->fullName());

        return DB::transaction(function () use ($log, $staff, $signedName) {
            $attrs = [
                'status' => StaffWeeklyTimeLog::STATUS_PENDING_HR,
                'employee_signed_name' => $signedName,
                'employee_signed_at' => now(),
                'hr_notes' => null,
                'hr_reviewed_at' => null,
                'hr_reviewed_by_staff_id' => null,
            ];

            // Alone / highest-ranking in dept may digi-sign as HOD on submit (locks the form).
            if ($this->canSelfEndorse($staff)) {
                $attrs = array_merge($attrs, [
                    'manager_staff_id' => $staff->id,
                    'manager_signed_name' => $signedName,
                    'manager_signature' => $signedName,
                    'manager_signed_at' => now(),
                    'manager_self_endorsed' => true,
                ]);
                $this->stampDayApprovals($log, $this->initialsFor($staff));
            }

            $log->update($attrs);
            $fresh = $log->fresh(['days', 'staff.department', 'manager']);
            $this->notifyHr($fresh);
            $this->notifyManagerIfNeeded($fresh);

            try {
                app(HrSidebarNotificationService::class)->broadcastCounts();
            } catch (\Throwable) {
            }

            return $fresh;
        });
    }

    public function managerEndorse(StaffWeeklyTimeLog $log, Staff $manager, string $signature): StaffWeeklyTimeLog
    {
        if ($log->status !== StaffWeeklyTimeLog::STATUS_PENDING_HR) {
            throw new \RuntimeException('Only logs submitted to HR can be endorsed by the line manager.');
        }
        if ($log->manager_signed_at) {
            throw new \RuntimeException('This log is already endorsed.');
        }

        $owner = $log->staff;
        $allowed = (int) $owner->line_manager_id === (int) $manager->id
            || $this->canSelfEndorse($owner) && (int) $owner->id === (int) $manager->id;

        if (! $allowed) {
            throw new \RuntimeException('You are not the line manager for this staff member.');
        }

        return DB::transaction(function () use ($log, $manager, $signature) {
            $name = trim($signature) !== '' ? trim($signature) : $manager->fullName();
            $log->update([
                'status' => StaffWeeklyTimeLog::STATUS_PENDING_HR,
                'manager_staff_id' => $manager->id,
                'manager_signed_name' => $manager->fullName(),
                'manager_signature' => $name,
                'manager_signed_at' => now(),
                'manager_self_endorsed' => (int) $manager->id === (int) $log->staff_id,
            ]);
            $this->stampDayApprovals($log, $this->initialsFor($manager));

            return $log->fresh(['days', 'staff', 'manager']);
        });
    }

    public function hrApprove(StaffWeeklyTimeLog $log, Staff $hr, ?string $notes = null): StaffWeeklyTimeLog
    {
        if ($log->status !== StaffWeeklyTimeLog::STATUS_PENDING_HR) {
            throw new \RuntimeException('Only logs pending HR review can be approved.');
        }

        $log->update([
            'status' => StaffWeeklyTimeLog::STATUS_APPROVED,
            'hr_reviewed_by_staff_id' => $hr->id,
            'hr_reviewed_at' => now(),
            'hr_notes' => $notes,
        ]);

        $this->broadcastHr();

        return $log->fresh(['days', 'staff', 'manager', 'hrReviewer']);
    }

    public function hrReject(StaffWeeklyTimeLog $log, Staff $hr, string $notes): StaffWeeklyTimeLog
    {
        if ($log->status !== StaffWeeklyTimeLog::STATUS_PENDING_HR) {
            throw new \RuntimeException('Only logs pending HR review can be rejected.');
        }

        $log->update([
            'status' => StaffWeeklyTimeLog::STATUS_REJECTED,
            'hr_reviewed_by_staff_id' => $hr->id,
            'hr_reviewed_at' => now(),
            'hr_notes' => $notes,
        ]);

        $this->broadcastHr();

        return $log->fresh(['days', 'staff', 'manager', 'hrReviewer']);
    }

    public function hrReturn(StaffWeeklyTimeLog $log, Staff $hr, string $notes): StaffWeeklyTimeLog
    {
        if ($log->status !== StaffWeeklyTimeLog::STATUS_PENDING_HR) {
            throw new \RuntimeException('Only logs pending HR review can be returned.');
        }

        $log->update([
            'status' => StaffWeeklyTimeLog::STATUS_RETURNED,
            'hr_reviewed_by_staff_id' => $hr->id,
            'hr_reviewed_at' => now(),
            'hr_notes' => $notes,
            'manager_staff_id' => null,
            'manager_signed_name' => null,
            'manager_signature' => null,
            'manager_signed_at' => null,
            'manager_self_endorsed' => false,
            'employee_signed_name' => null,
            'employee_signed_at' => null,
        ]);

        foreach ($log->days as $day) {
            $day->update(['approval_sign' => null]);
        }

        $this->broadcastHr();

        return $log->fresh(['days', 'staff']);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function hrIndex(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $q = StaffWeeklyTimeLog::query()
            ->with(['staff.department', 'manager'])
            ->whereIn('status', [
                StaffWeeklyTimeLog::STATUS_PENDING_HR,
                StaffWeeklyTimeLog::STATUS_APPROVED,
                StaffWeeklyTimeLog::STATUS_REJECTED,
                StaffWeeklyTimeLog::STATUS_RETURNED,
            ])
            ->latest('employee_signed_at');

        if (! empty($filters['status'])) {
            $q->where('status', $filters['status']);
        } else {
            $q->where('status', StaffWeeklyTimeLog::STATUS_PENDING_HR);
        }

        if (! empty($filters['month'])) {
            $q->where('log_month', (int) $filters['month']);
        }
        if (! empty($filters['year'])) {
            $q->where('log_year', (int) $filters['year']);
        }
        if (! empty($filters['week_number'])) {
            $q->where('week_number', (int) $filters['week_number']);
        }
        if (! empty($filters['submitted_from'])) {
            $q->whereDate('employee_signed_at', '>=', $filters['submitted_from']);
        }
        if (! empty($filters['submitted_to'])) {
            $q->whereDate('employee_signed_at', '<=', $filters['submitted_to']);
        }
        if (! empty($filters['search'])) {
            $search = '%'.trim((string) $filters['search']).'%';
            $q->where(function ($inner) use ($search) {
                $inner->where('log_code', 'like', $search)
                    ->orWhere('week_ref', 'like', $search)
                    ->orWhereHas('staff', function ($s) use ($search) {
                        $s->where('first_name', 'like', $search)
                            ->orWhere('surname', 'like', $search)
                            ->orWhere('employee_number', 'like', $search)
                            ->orWhere('organisation_email', 'like', $search);
                    });
            });
        }

        return $q->paginate($perPage)->withQueryString();
    }

    public function pendingHrCount(): int
    {
        if (! Schema::hasTable('staff_weekly_time_logs')) {
            return 0;
        }

        return StaffWeeklyTimeLog::query()
            ->where('status', StaffWeeklyTimeLog::STATUS_PENDING_HR)
            ->count();
    }

    public function logsForStaff(Staff $staff, int $perPage = 20): LengthAwarePaginator
    {
        return StaffWeeklyTimeLog::query()
            ->with('days')
            ->where('staff_id', $staff->id)
            ->latest()
            ->paginate($perPage);
    }

    public function viewerMayAccess(StaffWeeklyTimeLog $log, Staff $viewer, User $user): bool
    {
        if ((int) $log->staff_id === (int) $viewer->id) {
            return true;
        }
        if ((int) $log->staff?->line_manager_id === (int) $viewer->id) {
            return true;
        }
        if ((int) ($log->manager_staff_id ?? 0) === (int) $viewer->id) {
            return true;
        }
        if ($user->hasPermission('hr.read') || $user->hasAnyRole(['Super Admin', 'HR Manager', 'Assistant HR Manager'])) {
            return true;
        }

        return false;
    }

    private function stampDayApprovals(StaffWeeklyTimeLog $log, string $initials): void
    {
        $log->loadMissing('days');
        foreach ($log->days as $day) {
            if ($day->in_month && ($day->time_in || $day->tasks_accomplished)) {
                $day->update(['approval_sign' => $day->approval_sign ?: $initials]);
            }
        }
    }

    private function recomputeTotals(StaffWeeklyTimeLog $log): void
    {
        $log->load('days');
        $hours = 0.0;
        $units = 0.0;
        foreach ($log->days as $day) {
            if (! $day->in_month) {
                continue;
            }
            $hours += (float) ($day->total_hours ?? 0);
            $units += (float) ($day->total_units ?? 0);
        }
        $log->update([
            'total_hours' => round($hours, 2),
            'total_units' => round($units, 2),
        ]);
    }

    private function normalizeTime(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^\d{1,2}:\d{2}/', $value, $m)) {
            return Carbon::createFromFormat('H:i', strlen($m[0]) === 4 ? '0'.$m[0] : substr($m[0], 0, 5))->format('H:i');
        }

        return null;
    }

    private function nextCode(): string
    {
        return 'WTL-'.now()->format('ymd').'-'.Str::upper(Str::random(4));
    }

    private function notifyHr(StaffWeeklyTimeLog $log): void
    {
        try {
            $userIds = DB::table('user_roles as ur')
                ->join('roles as r', 'r.id', '=', 'ur.role_id')
                ->whereIn('r.role_name', ['HR Manager', 'Assistant HR Manager', 'Super Admin'])
                ->pluck('ur.user_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            if ($userIds === []) {
                return;
            }

            $this->notifications->notifyUsers(
                $userIds,
                'Weekly time log submitted',
                ($log->staff?->fullName() ?? 'A staff member').' submitted '.$log->week_ref.' for HR review.',
                'staff_weekly_time_log',
                (string) $log->id,
                'normal',
                route('hr.time-logs.show', $log),
            );
        } catch (\Throwable) {
        }
    }

    private function notifyManagerIfNeeded(StaffWeeklyTimeLog $log): void
    {
        if ($log->manager_signed_at || ! $log->staff?->line_manager_id) {
            return;
        }
        try {
            $manager = Staff::query()->with('user')->find($log->staff->line_manager_id);
            $userId = $manager?->user_id;
            if (! $userId) {
                return;
            }
            $this->notifications->notifyUsers(
                [(int) $userId],
                'Time log awaiting your endorsement',
                ($log->staff->fullName()).' submitted '.$log->week_ref.'. Please open and digitally endorse it.',
                'staff_weekly_time_log',
                (string) $log->id,
                'high',
                route('employee.time-logs.show', $log),
            );
        } catch (\Throwable) {
        }
    }

    private function broadcastHr(): void
    {
        try {
            app(HrSidebarNotificationService::class)->broadcastCounts();
        } catch (\Throwable) {
        }
    }
}
