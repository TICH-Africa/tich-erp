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

    public function calculateEndDate(string $startDate, ?string $duration): ?string
    {
        if (! $duration) {
            return null;
        }

        $duration = strtolower(trim($duration));
        $start = \Carbon\Carbon::parse($startDate);

        if (preg_match('/(\d+)\s*(y|year|years)/', $duration, $matches)) {
            return $start->copy()->addYears((int) $matches[1])->toDateString();
        }

        if (preg_match('/(\d+)\s*(m|month|months)/', $duration, $matches)) {
            return $start->copy()->addMonths((int) $matches[1])->toDateString();
        }

        if (is_numeric($duration)) {
            return $start->copy()->addMonths((int) $duration)->toDateString();
        }

        return null;
    }

    public function createContract(int $staffId, array $data, int $createdBy): StaffContract
    {
        $staff = Staff::findOrFail($staffId);

        $contract = DB::transaction(function () use ($staffId, $data, $createdBy) {
            $contractNumber = $this->generateContractNumber();

            $contract = StaffContract::create(array_merge($data, [
                'staff_id' => $staffId,
                'contract_number' => $contractNumber,
                'renewal_notice_sent' => 0,
                'renewal_status' => 'pending',
                'is_signed' => 0,
                'probation_status' => $data['contract_type'] === 'permanent' ? 'not_applicable' : 'active',
            ]));

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

        return $contract;
    }

    public function updateContract(int $contractId, array $data, int $updatedBy): StaffContract
    {
        $contract = StaffContract::findOrFail($contractId);

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

        return $contract->fresh();
    }

    public function renewContract(int $contractId, array $renewalData, int $renewedBy): StaffContract
    {
        $contract = StaffContract::findOrFail($contractId);

        if (! $contract->is_renewable) {
            throw new \InvalidArgumentException('This contract is not marked as renewable');
        }

        $newContract = DB::transaction(function () use ($contract, $renewalData, $renewedBy) {
            $newContract = StaffContract::create(array_merge($renewalData, [
                'staff_id' => $contract->staff_id,
                'contract_number' => $this->generateContractNumber(),
                'department_id' => $contract->department_id,
                'renewal_notice_sent' => 0,
                'renewal_status' => 'pending',
                'is_signed' => 0,
                'contract_type' => $renewalData['contract_type'] ?? $contract->contract_type,
                'job_title' => $renewalData['job_title'] ?? $contract->job_title,
                'probation_status' => ($renewalData['contract_type'] ?? $contract->contract_type) === 'permanent' ? 'not_applicable' : 'active',
            ]));

            $contract->update([
                'renewal_status' => 'renewed',
                'new_contract_id' => $newContract->id,
            ]);

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
        $contract = StaffContract::findOrFail($contractId);

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

        $contract->load('staff');
        $this->syncToStaff($contract);

        return $contract->fresh();
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

        $organisationEmail = $contract->organisation_email
            ?: $contract->staff->organisation_email
            ?: ($contract->staff->user ? $contract->staff->user->email : null);

        $contract->staff->update([
            'job_title' => $contract->job_title,
            'department_id' => $contract->department_id,
            'campus_id' => $contract->campus_id,
            'job_grade' => $contract->job_grade,
            'payroll_scheme' => $contract->payroll_scheme ?: $contract->staff->payroll_scheme ?: 'employee',
            'salary_scale' => $contract->salary_scale,
            'line_manager_id' => $contract->line_manager_id,
            'organisation_email' => $organisationEmail,
            'gross_monthly_salary' => $contract->gross_salary,
            'employment_start_date' => $contract->start_date,
            'contract_end_date' => $contract->end_date,
            'employment_category' => $contract->contract_type,
            'probation_end_date' => $contract->probation_end_date,
            'is_on_probation' => $contract->probation_status === 'active',
        ]);
    }
}
