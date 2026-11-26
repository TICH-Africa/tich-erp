<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\StaffContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContractService
{
    public function __construct(protected AuditService $auditService) {}

    /**
     * Permanent employment has no fixed end date; it ends only on separation.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function applyPermanentContractRules(array $data): array
    {
        if (($data['contract_type'] ?? null) !== 'permanent') {
            return $data;
        }

        $data['end_date'] = null;
        unset($data['duration']);
        $data['is_renewable'] = false;
        $data['probation_status'] = $data['probation_status'] ?? 'not_applicable';

        return $data;
    }

    /**
     * Volunteers are unpaid: zero salary, no payroll deductions.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function applyVolunteerContractRules(array $data): array
    {
        if (($data['contract_type'] ?? null) !== 'volunteer') {
            return $data;
        }

        $data['gross_salary'] = 0;
        $data['probation_status'] = 'not_applicable';
        $data['probation_end_date'] = null;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function normalizeContractPayload(array $data): array
    {
        return $this->applyVolunteerContractRules($this->applyPermanentContractRules($data));
    }

    public function calculateEndDate(string $startDate, ?string $duration): ?string
    {
        if (! $duration) {
            return null;
        }

        $duration = strtolower(trim($duration));
        $start = \Carbon\Carbon::parse($startDate);
        $months = null;

        if (is_numeric($duration)) {
            $months = (int) $duration;
        } elseif (preg_match('/^(\d+)\s*(m|month|months)$/', $duration, $matches)) {
            $months = (int) $matches[1];
        }

        if ($months === null || $months <= 0) {
            return null;
        }

        // Inclusive term: 1 Jan + 12 months ends 31 Dec (day before anniversary).
        return $start->copy()->addMonths($months)->subDay()->toDateString();
    }

    public function createContract(int $staffId, array $data, int $createdBy): StaffContract
    {
        $staff = Staff::findOrFail($staffId);

        if ($staff->isLinkedPlatformOperator()) {
            throw new \InvalidArgumentException(
                'Super Admin / platform administrator accounts cannot be issued employment contracts.'
            );
        }

        $data = $this->normalizeContractPayload($data);

        $contract = DB::transaction(function () use ($staffId, $data, $createdBy) {
            $contractNumber = $this->generateContractNumber();

            $contractType = $data['contract_type'] ?? '';
            $probationStatus = in_array($contractType, ['permanent', 'volunteer'], true)
                ? 'not_applicable'
                : ($data['probation_status'] ?? 'active');

            $contract = StaffContract::create(array_merge($data, [
                'staff_id' => $staffId,
                'contract_number' => $contractNumber,
                'renewal_notice_sent' => 0,
                'renewal_status' => 'pending',
                'is_signed' => 0,
                'probation_status' => $probationStatus,
            ]));

            $this->supersedePriorActiveContracts($staffId, $contract->id, $createdBy);

            $this->auditService->log(
                'staff.contract.created',
                'staff_contracts',
                $contract->id,
                null,
                $contract->toArray(),
                'Contract created',
                'success',
                $createdBy
            );

            return $contract;
        });

        $contract->load('staff');
        $this->syncToStaff($contract);

        if ($contract->staff) {
            $this->refreshLeaveAccrual($contract->staff);
        }

        return $contract;
    }

    public function updateContract(int $contractId, array $data, int $updatedBy): StaffContract
    {
        $contract = StaffContract::findOrFail($contractId);

        if (! $contract->isEditableByHrBeforeSigning()) {
            throw new \InvalidArgumentException(
                $contract->is_signed
                    ? 'This contract is already signed and can no longer be edited.'
                    : 'This contract is terminated and cannot be edited.'
            );
        }

        unset($data['is_signed'], $data['signed_date'], $data['witnessed_by']);

        $mergedType = $data['contract_type'] ?? $contract->contract_type;
        $data = $this->normalizeContractPayload(array_merge(['contract_type' => $mergedType], $data));

        $oldValues = $contract->only(array_keys($data));

        DB::transaction(function () use ($contract, $data, $updatedBy) {
            $contract->update($data);

            $this->auditService->log(
                'staff.contract.updated',
                'staff_contracts',
                $contract->id,
                $oldValues,
                $contract->only(array_keys($data)),
                'Contract updated',
                'success',
                $updatedBy
            );
        });

        $contract->load('staff');
        $this->syncToStaff($contract);

        if ($contract->staff) {
            $this->refreshLeaveAccrual($contract->staff);
        }

        return $contract->fresh();
    }

    public function renewContract(int $contractId, array $renewalData, int $renewedBy): StaffContract
    {
        $contract = StaffContract::findOrFail($contractId);

        if ($contract->renewal_status === 'renewed' || $contract->new_contract_id) {
            throw new \InvalidArgumentException('This contract has already been renewed.');
        }

        if ($contract->renewal_status === 'terminated') {
            throw new \InvalidArgumentException('A terminated contract cannot be renewed.');
        }

        $renewalData = $this->normalizeContractPayload(array_merge(
            ['contract_type' => $renewalData['contract_type'] ?? $contract->contract_type],
            $renewalData,
        ));

        $newContract = DB::transaction(function () use ($contract, $renewalData, $renewedBy) {
            $contractType = $renewalData['contract_type'] ?? $contract->contract_type;
            $isRenewable = array_key_exists('is_renewable', $renewalData)
                ? (bool) $renewalData['is_renewable']
                : (bool) ($contract->is_renewable || $contractType !== 'permanent');

            $newContract = StaffContract::create(array_merge($renewalData, [
                'staff_id' => $contract->staff_id,
                'contract_number' => $this->generateContractNumber(),
                'department_id' => $contract->department_id,
                'renewal_notice_sent' => 0,
                'renewal_status' => 'pending',
                'is_signed' => 0,
                'is_renewable' => $isRenewable ? 1 : 0,
                'contract_type' => $contractType,
                'job_title' => $renewalData['job_title'] ?? $contract->job_title,
                'probation_status' => $contractType === 'permanent' ? 'not_applicable' : 'active',
            ]));

            $contract->update([
                'is_renewable' => 1,
                'renewal_status' => 'renewed',
                'new_contract_id' => $newContract->id,
            ]);

            $this->supersedePriorActiveContracts((int) $contract->staff_id, $newContract->id, $renewedBy);

            $this->auditService->log(
                'staff.contract.renewed',
                'staff_contracts',
                $contract->id,
                ['renewal_status' => 'pending'],
                ['renewal_status' => 'renewed', 'new_contract_id' => $newContract->id],
                'Contract renewed',
                'success',
                $renewedBy
            );

            return $newContract;
        });

        $newContract->load('staff');
        $this->syncToStaff($newContract);

        if ($newContract->staff) {
            $this->refreshLeaveAccrual($newContract->staff);
        }

        return $newContract;
    }

    public function terminateContract(int $contractId, ?string $reason, int $terminatedBy): StaffContract
    {
        $contract = StaffContract::findOrFail($contractId);

        DB::transaction(function () use ($contract, $reason, $terminatedBy) {
            $contract->update([
                'renewal_status' => 'terminated',
            ]);

            $this->auditService->log(
                'staff.contract.terminated',
                'staff_contracts',
                $contract->id,
                ['renewal_status' => 'pending'],
                ['renewal_status' => 'terminated'],
                $reason ?? 'Contract terminated',
                'success',
                $terminatedBy
            );
        });

        $contract->load('staff');
        $this->syncToStaff($contract);

        return $contract->fresh();
    }

    public function getExpiryAlerts(int $days = 30): array
    {
        $contracts = StaffContract::query()
            ->with(['staff', 'staff.department'])
            ->active()
            ->expiringSoon($days)
            ->where('renewal_status', 'pending')
            ->get();

        $licenses = collect();
        if (class_exists(\App\Models\StaffProfessionalLicense::class)) {
            $licenses = \App\Models\StaffProfessionalLicense::query()
                ->with('staff')
                ->where('is_expired', 0)
                ->where('expiry_date', '<=', now()->addDays($days))
                ->where('expiry_date', '>=', now())
                ->get();
        }

        $certificates = collect();
        if (class_exists(\App\Models\StaffDocument::class)) {
            $certificates = \App\Models\StaffDocument::query()
                ->with('staff')
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '<=', now()->addDays($days))
                ->where('expiry_date', '>=', now())
                ->get();
        }

        return [
            'contracts' => $contracts,
            'licenses' => $licenses,
            'certificates' => $certificates,
        ];
    }

    public function markRenewalNoticeSent(int $contractId, int $sentBy): StaffContract
    {
        $contract = StaffContract::findOrFail($contractId);

        $contract->update([
            'renewal_notice_sent' => 1,
            'renewal_notice_date' => now(),
        ]);

        $this->auditService->log(
            'staff.contract.renewal_notice_sent',
            'staff_contracts',
            $contract->id,
            ['renewal_notice_sent' => 0],
            ['renewal_notice_sent' => 1, 'renewal_notice_date' => now()->toDateString()],
            'Renewal notice sent',
            'success',
            $sentBy
        );

        return $contract->fresh();
    }

    public function markContractSigned(int $contractId, ?string $witnessedBy, int $signedBy): StaffContract
    {
        $contract = StaffContract::with('staff.user')->findOrFail($contractId);

        $contract->update([
            'is_signed' => 1,
            'signed_date' => now(),
            'witnessed_by' => $witnessedBy,
        ]);

        $this->auditService->log(
            'staff.contract.signed',
            'staff_contracts',
            $contract->id,
            ['is_signed' => 0],
            ['is_signed' => 1, 'signed_date' => now()->toDateString()],
            'Contract signed',
            'success',
            $signedBy
        );

        $contract->load('staff.user');
        $this->syncToStaff($contract);
        $this->notifyStaffContractSigned($contract->fresh(['staff.user', 'department']));

        return $contract->fresh(['staff', 'department']);
    }

    private function notifyStaffContractSigned(StaffContract $contract): void
    {
        $staff = $contract->staff;
        if (! $staff) {
            return;
        }

        $email = $staff->resolveErpEmail($staff->user?->email);
        if (! $email) {
            return;
        }

        try {
            \Illuminate\Support\Facades\Mail::to($email)
                ->send(new \App\Mail\ContractSignedEmail($staff, $contract));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function convertToPermanent(int $contractId, int $convertedBy): StaffContract
    {
        $contract = StaffContract::findOrFail($contractId);

        $permanentContract = DB::transaction(function () use ($contract, $convertedBy) {
            $permanent = StaffContract::create([
                'staff_id' => $contract->staff_id,
                'contract_number' => $this->generateContractNumber(),
                'contract_type' => 'permanent',
                'job_title' => $contract->job_title,
                'department_id' => $contract->department_id,
                'gross_salary' => $contract->gross_salary,
                'start_date' => now(),
                'end_date' => null,
                'is_renewable' => 0,
                'renewal_notice_sent' => 0,
                'renewal_status' => 'pending',
                'probation_end_date' => null,
                'probation_status' => 'not_applicable',
                'is_signed' => 0,
            ]);

            $contract->update([
                'renewal_status' => 'terminated',
                'new_contract_id' => $permanent->id,
            ]);

            $this->supersedePriorActiveContracts((int) $contract->staff_id, $permanent->id, $convertedBy);

            $contract->staff->update([
                'employment_category' => 'permanent',
                'is_on_probation' => 0,
                'probation_end_date' => null,
                'confirmation_date' => now(),
            ]);

            $this->auditService->log(
                'staff.contract.converted_to_permanent',
                'staff_contracts',
                $contract->id,
                ['renewal_status' => 'pending'],
                ['renewal_status' => 'terminated', 'new_contract_id' => $permanent->id],
                'Contract converted to permanent',
                'success',
                $convertedBy
            );

            return $permanent;
        });

        $permanentContract->load('staff');
        $this->syncToStaff($permanentContract);

        return $permanentContract;
    }

    /**
     * @return list<array{staff_id: int, keep_id: int, keep_number: string, supersede_id: int, supersede_number: string}>
     */
    public function duplicateActiveContractRows(): array
    {
        $grouped = StaffContract::query()
            ->with('staff:id')
            ->where(function ($query) {
                $query->whereNull('renewal_status')
                    ->orWhere('renewal_status', 'pending');
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get()
            ->groupBy('staff_id');

        $rows = [];

        foreach ($grouped as $staffId => $contracts) {
            if ($contracts->count() <= 1) {
                continue;
            }

            $keep = $contracts->first();
            foreach ($contracts->skip(1) as $old) {
                $rows[] = [
                    'staff_id' => (int) $staffId,
                    'keep_id' => (int) $keep->id,
                    'keep_number' => (string) $keep->contract_number,
                    'supersede_id' => (int) $old->id,
                    'supersede_number' => (string) $old->contract_number,
                ];
            }
        }

        return $rows;
    }

    /** When legacy rows left two Active (pending) contracts, keep the newest and supersede the rest. */
    public function ensureSingleActiveContractForStaff(int $staffId, int $actedBy = 0): int
    {
        $keeper = StaffContract::query()
            ->where('staff_id', $staffId)
            ->where(function ($query) {
                $query->whereNull('renewal_status')
                    ->orWhere('renewal_status', 'pending');
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        if (! $keeper) {
            return 0;
        }

        $before = StaffContract::query()
            ->where('staff_id', $staffId)
            ->where('id', '!=', $keeper->id)
            ->where(function ($query) {
                $query->whereNull('renewal_status')
                    ->orWhere('renewal_status', 'pending');
            })
            ->count();

        if ($before === 0) {
            return 0;
        }

        $this->supersedePriorActiveContracts($staffId, (int) $keeper->id, $actedBy);

        return $before;
    }

    /** Fix legacy data where multiple contracts were left on Active (pending). */
    public function reconcileDuplicateActiveContracts(int $actedBy = 0): int
    {
        $fixed = 0;

        foreach ($this->duplicateActiveContractRows() as $row) {
            $old = StaffContract::query()->find($row['supersede_id']);
            if (! $old) {
                continue;
            }

            $before = $old->only(['renewal_status', 'new_contract_id']);
            $old->update([
                'renewal_status' => 'renewed',
                'new_contract_id' => $row['keep_id'],
                'is_renewable' => 1,
            ]);

            if ($actedBy > 0) {
                $this->auditService->log(
                    'staff.contract.superseded',
                    'staff_contracts',
                    $old->id,
                    $before,
                    ['renewal_status' => 'renewed', 'new_contract_id' => $row['keep_id']],
                    'Duplicate active contract reconciled',
                    'success',
                    $actedBy
                );
            }

            $fixed++;
        }

        return $fixed;
    }

    /**
     * Only one contract may stay "Active" (pending) per employee; older rows are linked to the successor.
     */
    private function supersedePriorActiveContracts(int $staffId, int $newContractId, int $actedBy): void
    {
        $prior = StaffContract::query()
            ->where('staff_id', $staffId)
            ->where('id', '!=', $newContractId)
            ->where(function ($query) {
                $query->whereNull('renewal_status')
                    ->orWhere('renewal_status', 'pending');
            })
            ->get();

        foreach ($prior as $old) {
            $before = $old->only(['renewal_status', 'new_contract_id']);

            $old->update([
                'renewal_status' => 'renewed',
                'new_contract_id' => $newContractId,
                'is_renewable' => 1,
            ]);

            $this->auditService->log(
                'staff.contract.superseded',
                'staff_contracts',
                $old->id,
                $before,
                ['renewal_status' => 'renewed', 'new_contract_id' => $newContractId],
                'Prior contract superseded by a newer contract',
                'success',
                $actedBy
            );
        }
    }

    public function generateContractNumber(): string
    {
        $year = now()->year;
        $prefix = "CON/{$year}/";

        $last = StaffContract::where('contract_number', 'like', $prefix . '%')
            ->orderByDesc('contract_number')
            ->value('contract_number');

        if ($last) {
            $num = (int) str_replace($prefix, '', $last);
            $num++;
        } else {
            $num = 1;
        }

        return $prefix . str_pad((string) $num, 5, '0', STR_PAD_LEFT);
    }

    private function syncToStaff(StaffContract $contract): void
    {
        if (! $contract->staff) {
            return;
        }

        // Leave accrual counts from the original employment/contract start.
        // Renewals must not reset employment_start_date mid-year.
        $originalStart = StaffContract::query()
            ->where('staff_id', $contract->staff_id)
            ->whereNotNull('start_date')
            ->orderBy('start_date')
            ->value('start_date');

        $employmentStart = $originalStart
            ?? $contract->staff->employment_start_date
            ?? $contract->start_date;

        $updates = [
            'job_title' => $contract->job_title,
            'department_id' => $contract->department_id,
            'campus_id' => $contract->campus_id,
            'job_grade' => $contract->job_grade,
            'payroll_scheme' => match ($contract->contract_type) {
                'permanent', 'contract' => 'employee',
                'volunteer' => 'unpaid',
                'intern' => 'stipend',
                'consultancy' => 'withholding',
                default => $contract->payroll_scheme ?: $contract->staff->payroll_scheme ?: 'employee',
            },
            'salary_scale' => $contract->salary_scale,
            'line_manager_id' => $contract->line_manager_id,
            'gross_monthly_salary' => $contract->contract_type === 'volunteer' ? 0 : $contract->gross_salary,
            'employment_start_date' => $employmentStart,
            'contract_end_date' => $contract->end_date,
            'employment_category' => $contract->contract_type,
            'probation_end_date' => $contract->probation_end_date,
            'is_on_probation' => $contract->probation_status === 'active',
        ];

        // Organisation email is issued manually by ICT only - never copy from login/user email.
        if (filled($contract->organisation_email)) {
            $updates['organisation_email'] = $contract->organisation_email;
        }

        $contract->staff->update($updates);
    }

    /**
     * Recalculate leave balances from the preserved employment start date.
     */
    private function refreshLeaveAccrual(Staff $staff): void
    {
        try {
            app(LeaveAccrualService::class)->ensureBalancesForStaff($staff->fresh());
        } catch (\Throwable) {
            // Leave accrual must not block contract create/renew.
        }
    }
}
