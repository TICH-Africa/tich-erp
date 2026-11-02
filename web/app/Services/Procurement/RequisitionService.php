<?php

namespace App\Services\Procurement;

use App\Models\Administration\BudgetRequest;
use App\Models\FinanceBudget;
use App\Models\ProcurementRequisition;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
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

        if (! filled($requisition->budget_code)) {
            return [
                'passed' => true,
                'message' => 'No budget linked; requisition uses manually entered line items.',
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
        // Amount informs intermediate routing hints only; CEO is always the final approver.
        if ($amount <= 500000) {
            return 'hod';
        }

        if ($amount <= 2000000) {
            return 'finance';
        }

        return 'ceo';
    }

    /**
     * Final approval stage is always CEO (no amount gate).
     */
    public function requiresCeoApproval(ProcurementRequisition $requisition): bool
    {
        return $requisition->status === 'finance_approved'
            && ($requisition->ceo_approval_status === null || $requisition->ceo_approval_status === 'pending');
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
                    $update['ceo_approval_status'] = 'pending';
                    break;

                case 'ceo':
                    if ($requisition->status !== 'finance_approved') {
                        throw new InvalidArgumentException('Only finance-approved requisitions can be approved by the CEO.');
                    }
                    $update['ceo_approval_status'] = 'approved';
                    $update['ceo_approved_by'] = $approver->id;
                    $update['ceo_approved_at'] = $now;
                    $update['status'] = 'ceo_approved';
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

        return DB::transaction(function () use ($requisition, $level, $rejector, $now, $auditEntry) {
            $update = [];

            switch ($level) {
                case 'hod':
                    $update['hod_approval_status'] = 'rejected';
                    $update['hod_approved_by'] = $rejector->id;
                    $update['hod_approved_at'] = $now;
                    break;

                case 'finance':
                    $update['finance_approval_status'] = 'rejected';
                    $update['finance_approved_by'] = $rejector->id;
                    $update['finance_approved_at'] = $now;
                    break;

                case 'ceo':
                    $update['ceo_approval_status'] = 'rejected';
                    $update['ceo_approved_by'] = $rejector->id;
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

    /**
     * Only Procurement puts a price on a request. Every department that can raise a requisition
     * can also read the module, so the shared procurement permission is not enough here.
     */
    public function mayPrice(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (app(\App\Services\RBACService::class)->isPlatformAdministrator($user)) {
            return true;
        }

        return $user->hasAnyRole(['Procurement Manager', 'Assistant Procurement Officer']);
    }

    /**
     * A requisition as the requesting department files it: what they need, how much of it, and the
     * price they estimate it will cost. Procurement may still change those prices when they take the
     * request on, but the department's estimate is what the budget check and the approval level read.
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @return list<array<string, mixed>>
     */
    public function estimatedLines(array $lines): array
    {
        return collect($lines)->map(function (array $line) {
            $raw = $line['unit_price'] ?? null;
            $quantity = round((float) ($line['quantity'] ?? 0), 4);
            $priced = $raw !== null && is_numeric($raw) ? round((float) $raw, 2) : null;

            return [
                'item' => trim((string) $line['item']),
                'quantity' => $quantity,
                'description' => trim((string) ($line['description'] ?? '')),
                'unit_price' => $priced,
                'unit_of_measure' => trim((string) ($line['unit_of_measure'] ?? '')) ?: null,
                'total' => $priced === null ? null : round($quantity * $priced, 2),
            ];
        })->values()->all();
    }

    /**
     * The department's own estimate for the whole requisition. Lines left without a price are simply
     * not counted, so a half-priced request cannot look cheaper than it is by accident.
     *
     * @param  list<array<string, mixed>>  $lines
     */
    public function estimatedCost(array $lines): float
    {
        return round((float) collect($lines)->sum('total'), 2);
    }

    /**
     * Procurement's estimated prices for a received requisition. The line totals and the header
     * cost are derived here so the requisition page, the budget check and the approval level all
     * read the same number.
     *
     * @param  array<int, mixed>  $unitPrices  keyed by line index
     */
    public function price(ProcurementRequisition $requisition, array $unitPrices): ProcurementRequisition
    {
        $lines = $requisition->lines();

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages([
                'lines' => 'This requisition has no line items to price.',
            ]);
        }

        $priced = $lines->map(function (array $line, int $index) use ($unitPrices) {
            $raw = $unitPrices[$index] ?? null;

            if ($raw === null || trim((string) $raw) === '') {
                throw ValidationException::withMessages([
                    "lines.{$index}" => 'Enter an estimated unit price for '.($line['item'] ?: 'this line').', or 0 if it is free.',
                ]);
            }

            if (! is_numeric($raw) || (float) $raw < 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}" => 'The unit price for '.($line['item'] ?: 'this line').' must be zero or more.',
                ]);
            }

            $unitPrice = round((float) $raw, 2);
            $quantity = round((float) ($line['quantity'] ?? 0), 4);

            return array_merge($line, [
                'unit_price' => $unitPrice,
                'total' => round($quantity * $unitPrice, 2),
            ]);
        })->values();

        $grandTotal = round((float) $priced->sum('total'), 2);

        $trail = $requisition->audit_trail ?? [];
        $trail[] = [
            'action' => $requisition->isPriced() ? 'repriced' : 'priced',
            'level' => 'procurement',
            'performed_by' => Auth::id(),
            'performed_at' => now()->toDateTimeString(),
            'comments' => 'Estimated total KES '.number_format($grandTotal, 2),
        ];

        return DB::transaction(function () use ($requisition, $priced, $grandTotal, $trail) {
            $requisition->update([
                'line_items' => $priced->all(),
                'estimated_cost' => $grandTotal,
                'estimated_unit_cost' => $priced->first()['unit_price'] ?? null,
                'quantity' => round((float) $priced->sum('quantity'), 4),
                'audit_trail' => $trail,
            ]);

            return $requisition->fresh();
        });
    }
}
