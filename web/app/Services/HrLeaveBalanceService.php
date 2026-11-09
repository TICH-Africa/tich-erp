<?php

namespace App\Services;

use App\Models\LeaveType;
use App\Models\Staff;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HrLeaveBalanceService
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    /**
     * @return Collection<int, object{
     *     leave_type_id: int,
     *     leave_code: string,
     *     leave_type_name: string,
     *     year: int,
     *     entitled_days: float,
     *     carried_forward_days: float,
     *     days_taken: float,
     *     days_pending: float,
     *     balance_days: float
     * }>
     */
    public function balancesForStaff(Staff $staff, ?int $year = null): Collection
    {
        if (! Schema::hasTable('leave_balances') || ! Schema::hasTable('leave_types')) {
            return collect();
        }

        $year = $year ?? now()->year;

        $types = LeaveType::query()
            ->where('is_active', true)
            ->orderBy('leave_name')
            ->get(['id', 'leave_code', 'leave_name', 'days_allowed_per_year']);

        $existing = DB::table('leave_balances')
            ->where('staff_id', $staff->id)
            ->where('year', $year)
            ->get()
            ->keyBy('leave_type_id');

        return $types->map(function (LeaveType $type) use ($staff, $year, $existing) {
            $row = $existing->get($type->id);

            if (! $row) {
                $entitled = (float) ($type->days_allowed_per_year ?? 0);

                return (object) [
                    'leave_type_id' => (int) $type->id,
                    'leave_code' => (string) $type->leave_code,
                    'leave_type_name' => (string) $type->leave_name,
                    'year' => $year,
                    'entitled_days' => $entitled,
                    'carried_forward_days' => 0.0,
                    'days_taken' => 0.0,
                    'days_pending' => 0.0,
                    'balance_days' => $entitled,
                    'is_new' => true,
                ];
            }

            return (object) [
                'leave_type_id' => (int) $type->id,
                'leave_code' => (string) $type->leave_code,
                'leave_type_name' => (string) $type->leave_name,
                'year' => $year,
                'entitled_days' => (float) $row->entitled_days,
                'carried_forward_days' => (float) ($row->carried_forward_days ?? 0),
                'days_taken' => (float) $row->days_taken,
                'days_pending' => (float) $row->days_pending,
                'balance_days' => (float) $row->balance_days,
                'is_new' => false,
            ];
        });
    }

    /**
     * @param  array<int, array{entitled_days: float, days_taken: float, days_pending?: float, carried_forward_days?: float}>  $rows
     */
    public function updateManualBalances(
        Staff $staff,
        int $year,
        array $rows,
        Staff $hrStaff,
        ?string $note = null,
    ): void {
        if (! Schema::hasTable('leave_balances')) {
            throw new \InvalidArgumentException('Leave balances are not available.');
        }

        DB::transaction(function () use ($staff, $year, $rows, $hrStaff, $note) {
            foreach ($rows as $leaveTypeId => $fields) {
                $leaveTypeId = (int) $leaveTypeId;
                if ($leaveTypeId <= 0) {
                    continue;
                }

                $entitled = max(0, (float) ($fields['entitled_days'] ?? 0));
                $taken = max(0, (float) ($fields['days_taken'] ?? 0));
                $pending = max(0, (float) ($fields['days_pending'] ?? 0));
                $carried = max(0, (float) ($fields['carried_forward_days'] ?? 0));
                $balance = max(0, $entitled + $carried - $taken - $pending);

                $existing = DB::table('leave_balances')
                    ->where('staff_id', $staff->id)
                    ->where('leave_type_id', $leaveTypeId)
                    ->where('year', $year)
                    ->first();

                $payload = [
                    'entitled_days' => $entitled,
                    'carried_forward_days' => $carried,
                    'days_taken' => $taken,
                    'days_pending' => $pending,
                    'balance_days' => $balance,
                    'last_updated' => now()->toDateString(),
                ];

                if ($existing) {
                    $old = (array) $existing;
                    DB::table('leave_balances')
                        ->where('id', $existing->id)
                        ->update($payload);

                    $this->auditService->log(
                        'hr.leave.balance_adjusted',
                        'leave_balance',
                        (int) $existing->id,
                        $old,
                        array_merge($old, $payload),
                        $note,
                    );
                } else {
                    $id = DB::table('leave_balances')->insertGetId(array_merge($payload, [
                        'staff_id' => $staff->id,
                        'leave_type_id' => $leaveTypeId,
                        'year' => $year,
                        'created_at' => now(),
                    ]));

                    $this->auditService->log(
                        'hr.leave.balance_adjusted',
                        'leave_balance',
                        $id,
                        null,
                        array_merge($payload, [
                            'staff_id' => $staff->id,
                            'leave_type_id' => $leaveTypeId,
                            'year' => $year,
                        ]),
                        $note,
                    );
                }
            }
        });
    }

    public function annualLeaveTypeId(): ?int
    {
        static $id = null;
        if ($id !== null) {
            return $id ?: null;
        }

        $id = (int) (LeaveType::query()->where('leave_code', 'ANNUAL')->value('id') ?? 0);

        return $id > 0 ? $id : null;
    }
}
