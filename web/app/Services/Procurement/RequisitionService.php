<?php

namespace App\Services\Procurement;

use App\Models\Administration\BudgetRequest;
use App\Models\FinanceBudget;
use App\Models\ProcurementRequisition;
use App\Models\Staff;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class RequisitionService
{
    public function generateRequisitionNumber(): string
    {
        $year = now()->year;
        $prefix = "REQ-{$year}-";

        $lastNumber = ProcurementRequisition::query()
            ->where('requisition_number', 'like', $prefix . '%')
            ->orderByDesc('requisition_number')
            ->value('requisition_number');

        if ($lastNumber) {
            $parts = explode('-', $lastNumber);
            $lastSeq = (int) end($parts);
        } else {
            $lastSeq = 0;
        }

        $nextSeq = $lastSeq + 1;

        return $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }

    public function verifyBudget(ProcurementRequisition $requisition): array
    {
        if (($requisition->requisition_type ?? null) === 'emergency') {
            return [
                'passed' => true,
                'message' => 'Emergency requisitions bypass standard budget verification.',
                'budget' => null,
                'available' => null,
                'required' => (float) $requisition->estimated_cost,
            ];
        }

        $estimatedCost = (float) $requisition->estimated_cost;

        if ($estimatedCost <= 0) {
            return [
                'passed' => false,
                'message' => 'The estimated cost must be greater than zero.',
                'budget' => null,
                'available' => null,
                'required' => $estimatedCost,
            ];
        }

        $budget = null;

        if ($requisition->budget_code) {
            $budget = FinanceBudget::query()
                ->where('budget_code', $requisition->budget_code)
                ->where('status', 'active')
                ->first();
        }

        if (!$budget && $requisition->requesting_department_id) {
            $budget = FinanceBudget::query()
                ->where('department_id', $requisition->requesting_department_id)
                ->where('status', 'active')
                ->orderByDesc('fiscal_year')
                ->first();
        }

        if (!$budget && $requisition->budget_code && Schema::hasTable('admin_budget_requests')) {
            $budgetRequest = BudgetRequest::query()
                ->where('request_code', $requisition->budget_code)
                ->whereNotIn('status', ['draft', 'rejected', 'returned', 'cancelled'])
                ->first();

            if ($budgetRequest) {
                $available = (float) ($budgetRequest->approved_amount
                    ?: $budgetRequest->verified_amount
                    ?: $budgetRequest->requested_amount);

                if ($available < $estimatedCost) {
                    return [
                        'passed' => false,
                        'message' => "Insufficient budget. Required: {$estimatedCost}, Available: {$available}.",
                        'budget' => null,
                        'available' => $available,
                        'required' => $estimatedCost,
                    ];
                }

                return [
                    'passed' => true,
                    'message' => 'Budget verification passed against budget request '.$budgetRequest->request_code.'.',
                    'budget' => null,
                    'available' => $available,
                    'required' => $estimatedCost,
                ];
            }
        }

        if (!$budget) {
            return [
                'passed' => false,
                'message' => 'No active budget found for this requisition.',
                'budget' => null,
                'available' => null,
                'required' => $estimatedCost,
            ];
        }

        $available = $budget->availableAmount();

        if ($available < $estimatedCost) {
            return [
                'passed' => false,
                'message' => "Insufficient budget. Required: {$estimatedCost}, Available: {$available}.",
                'budget' => $budget,
                'available' => $available,
                'required' => $estimatedCost,
            ];
        }

        return [
            'passed' => true,
            'message' => 'Budget verification passed.',
            'budget' => $budget,
            'available' => $available,
            'required' => $estimatedCost,
        ];
    }

    public function getApprovalLevel(float $amount): string
    {
        if ($amount <= 500000) {
            return 'hod';
        }

        if ($amount <= 2000000) {
            return 'finance';
        }

        return 'ceo';
    }

    public function approve(
        ProcurementRequisition $requisition,
        Staff $approver,
        string $level,
        ?string $comments = null
    ): ProcurementRequisition {
        $validLevels = ['hod', 'finance', 'ceo'];

        if (!in_array($level, $validLevels, true)) {
            throw new InvalidArgumentException("Invalid approval level: {$level}");
        }

        $now = now();
        $auditEntry = [
            'action' => 'approve',
            'level' => $level,
            'performed_by' => $approver->id,
            'performed_at' => $now->toDateTimeString(),
            'comments' => $comments,
        ];

        return DB::transaction(function () use ($requisition, $level, $approver, $now, $auditEntry) {
            $update = [];

            switch ($level) {
                case 'hod':
                    $update['hod_approval_status'] = 'approved';
                    $update['hod_approved_by'] = $approver->id;
                    $update['hod_approved_at'] = $now;
                    $update['status'] = 'hod_approved';
                    break;

                case 'finance':
                    $update['finance_approval_status'] = 'approved';
                    $update['finance_approved_by'] = $approver->id;
                    $update['finance_approved_at'] = $now;
                    $update['status'] = 'finance_approved';
                    break;

                case 'ceo':
                    $update['ceo_approval_status'] = 'approved';
                    $update['ceo_approved_by'] = $approver->id;
                    $update['ceo_approved_at'] = $now;
                    $update['status'] = 'completed';
                    break;
            }

            $trail = $requisition->audit_trail ?? [];
            $trail[] = $auditEntry;

            $update['audit_trail'] = $trail;

            $requisition->update($update);

            return $requisition->fresh();
        });
    }

    public function reject(
        ProcurementRequisition $requisition,
        Staff $rejector,
        string $level,
        ?string $comments = null
    ): ProcurementRequisition {
        $validLevels = ['hod', 'finance', 'ceo'];

        if (!in_array($level, $validLevels, true)) {
            throw new InvalidArgumentException("Invalid approval level: {$level}");
        }

        $now = now();
        $auditEntry = [
            'action' => 'reject',
            'level' => $level,
            'performed_by' => $rejector->id,
            'performed_at' => $now->toDateTimeString(),
            'comments' => $comments,
        ];

        return DB::transaction(function () use ($requisition, $level, $now, $auditEntry) {
            $update = [];

            switch ($level) {
                case 'hod':
                    $update['hod_approval_status'] = 'rejected';
                    $update['hod_approved_by'] = Auth::id();
                    $update['hod_approved_at'] = $now;
                    break;

                case 'finance':
                    $update['finance_approval_status'] = 'rejected';
                    $update['finance_approved_by'] = Auth::id();
                    $update['finance_approved_at'] = $now;
                    break;

                case 'ceo':
                    $update['ceo_approval_status'] = 'rejected';
                    $update['ceo_approved_by'] = Auth::id();
                    $update['ceo_approved_at'] = $now;
                    break;
            }

            $update['status'] = 'rejected';

            $trail = $requisition->audit_trail ?? [];
            $trail[] = $auditEntry;

            $update['audit_trail'] = $trail;

            $requisition->update($update);

            return $requisition->fresh();
        });
    }

    public function addAuditTrail(
        ProcurementRequisition $requisition,
        string $action,
        ?string $comments = null
    ): ProcurementRequisition {
        $now = now();
        $auditEntry = [
            'action' => $action,
            'performed_by' => Auth::id(),
            'performed_at' => $now->toDateTimeString(),
            'comments' => $comments,
        ];

        $trail = $requisition->audit_trail ?? [];
        $trail[] = $auditEntry;

        $requisition->update(['audit_trail' => $trail]);

        return $requisition->fresh();
    }

    public function cancel(ProcurementRequisition $requisition, ?string $reason = null): ProcurementRequisition
    {
        return DB::transaction(function () use ($requisition, $reason) {
            $auditEntry = [
                'action' => 'cancel',
                'performed_by' => Auth::id(),
                'performed_at' => now()->toDateTimeString(),
                'comments' => $reason,
            ];

            $trail = $requisition->audit_trail ?? [];
            $trail[] = $auditEntry;

            $requisition->update([
                'status' => 'cancelled',
                'audit_trail' => $trail,
            ]);

            return $requisition->fresh();
        });
    }
}
