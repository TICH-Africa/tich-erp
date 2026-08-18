<?php

namespace App\Services\Administration;

use App\Models\Administration\BudgetRequest;
use App\Models\Administration\FundAllocation;
use App\Models\Administration\InspectionCheck;
use App\Models\Administration\PlanningCycle;
use App\Models\Administration\QuickbooksSyncLog;
use App\Models\Administration\StatutoryCertification;
use App\Models\Department;
use App\Models\AccountsPayable;
use App\Models\Finance\Payment;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdministrationService
{
    public function nextCode(string $prefix): string
    {
        return strtoupper($prefix).'-'.now()->format('ymd').'-'.Str::upper(Str::random(4));
    }

    public function createPlanningCycle(array $data, ?int $userId = null): PlanningCycle
    {
        return PlanningCycle::query()->create([
            'cycle_code' => $this->nextCode('PLC'),
            'title' => $data['title'],
            'plan_tier' => $data['plan_tier'],
            'fiscal_year' => (int) $data['fiscal_year'],
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'requisition_deadline' => $data['requisition_deadline'],
            'status' => $data['status'] ?? 'open',
            'notes' => $data['notes'] ?? null,
            'created_by' => $userId,
        ]);
    }

    public function createBudgetRequest(array $data, ?int $userId = null): BudgetRequest
    {
        $cycle = isset($data['planning_cycle_id'])
            ? PlanningCycle::query()->find($data['planning_cycle_id'])
            : null;

        $isLate = $cycle?->isPastDeadline() ?? false;

        return BudgetRequest::query()->create([
            'request_code' => $this->nextCode('BQR'),
            'planning_cycle_id' => $data['planning_cycle_id'] ?? null,
            'department_id' => $data['department_id'],
            'title' => $data['title'],
            'framework' => $data['framework'] ?? 'standard',
            'standard_line_items' => $data['standard_line_items'] ?? null,
            'cbe_details' => $data['cbe_details'] ?? null,
            'requested_amount' => $data['requested_amount'],
            'status' => 'submitted',
            'justification' => $data['justification'] ?? null,
            'submitted_by' => $userId,
            'submitted_at' => now(),
            'is_late' => $isLate,
            'deadline_at' => $cycle?->requisition_deadline,
        ]);
    }

    public function routeBudgetToFinance(BudgetRequest $request, ?int $userId = null): BudgetRequest
    {
        if (! in_array($request->status, ['submitted', 'draft'], true)) {
            throw new \RuntimeException('Only submitted requests can be sent to Finance.');
        }

        $request->update([
            'status' => 'finance_review',
            'workflow_notes' => trim(($request->workflow_notes ? $request->workflow_notes."\n" : '').'Routed to Finance for verification.'),
        ]);

        return $request->fresh();
    }

    public function verifyBudgetByFinance(BudgetRequest $request, float $verifiedAmount, ?int $userId = null, ?string $notes = null): BudgetRequest
    {
        if ($request->status !== 'finance_review') {
            throw new \RuntimeException('Request is not awaiting Finance verification.');
        }

        $request->update([
            'status' => 'executive_review',
            'verified_amount' => $verifiedAmount,
            'finance_verified_by' => $userId,
            'finance_verified_at' => now(),
            'workflow_notes' => trim(($request->workflow_notes ? $request->workflow_notes."\n" : '').($notes ?: 'Verified by Finance. Awaiting Executive/CEO authorization.')),
        ]);

        return $request->fresh();
    }

    public function authorizeBudgetByExecutive(BudgetRequest $request, float $approvedAmount, ?int $userId = null, ?string $notes = null): BudgetRequest
    {
        if ($request->status !== 'executive_review') {
            throw new \RuntimeException('Request is not awaiting Executive authorization.');
        }

        $request->update([
            'status' => 'approved',
            'approved_amount' => $approvedAmount,
            'executive_approved_by' => $userId,
            'executive_approved_at' => now(),
            'workflow_notes' => trim(($request->workflow_notes ? $request->workflow_notes."\n" : '').($notes ?: 'Authorized by Executive/CEO.')),
        ]);

        return $request->fresh();
    }

    public function rejectBudget(BudgetRequest $request, ?int $userId = null, ?string $notes = null): BudgetRequest
    {
        $request->update([
            'status' => 'rejected',
            'workflow_notes' => trim(($request->workflow_notes ? $request->workflow_notes."\n" : '').($notes ?: 'Rejected in approval workflow.')),
        ]);

        return $request->fresh();
    }

    public function releaseFundAllocation(array $data, ?int $userId = null): FundAllocation
    {
        if (! empty($data['budget_request_id'])) {
            $request = BudgetRequest::query()->findOrFail($data['budget_request_id']);
            if ($request->status !== 'approved') {
                throw new \RuntimeException('Funds can only be released for an approved budget request.');
            }

            $alreadyReleased = (float) FundAllocation::query()
                ->where('budget_request_id', $request->id)
                ->whereIn('status', ['pending', 'released'])
                ->sum('amount');
            if ($alreadyReleased + (float) $data['amount'] > (float) $request->approved_amount) {
                throw new \RuntimeException('The release amount exceeds the remaining approved budget.');
            }
        }

        return FundAllocation::query()->create([
            'allocation_code' => $this->nextCode('FDA'),
            'budget_request_id' => $data['budget_request_id'] ?? null,
            'department_id' => $data['department_id'],
            'fiscal_year' => (int) $data['fiscal_year'],
            'month' => isset($data['month']) ? (int) $data['month'] : null,
            'amount' => $data['amount'],
            'status' => 'released',
            'released_by' => $userId,
            'released_at' => now(),
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function aggregatedBudgets(?int $fiscalYear = null): array
    {
        $query = BudgetRequest::query()->with('department:id,dept_name,dept_code');

        if ($fiscalYear) {
            $query->whereHas('planningCycle', fn ($q) => $q->where('fiscal_year', $fiscalYear));
        }

        $rows = $query->get();

        $byDepartment = $rows->groupBy('department_id')->map(function ($items) {
            $department = $items->first()->department;

            return [
                'department' => $department?->dept_name ?? 'Unknown',
                'dept_code' => $department?->dept_code,
                'requests' => $items->count(),
                'requested' => (float) $items->sum('requested_amount'),
                'verified' => (float) $items->sum('verified_amount'),
                'approved' => (float) $items->sum('approved_amount'),
                'cbe_count' => $items->where('framework', 'cbe')->count(),
            ];
        })->values()->all();

        return [
            'by_department' => $byDepartment,
            'totals' => [
                'requested' => (float) $rows->sum('requested_amount'),
                'verified' => (float) $rows->sum('verified_amount'),
                'approved' => (float) $rows->sum('approved_amount'),
                'cbe_share' => $rows->count() > 0
                    ? round(($rows->where('framework', 'cbe')->count() / $rows->count()) * 100, 1)
                    : 0,
            ],
        ];
    }

    public function inspectionReadiness(): array
    {
        $checks = Schema::hasTable('admin_inspection_checks')
            ? InspectionCheck::query()->get()
            : collect();

        $certs = Schema::hasTable('admin_statutory_certifications')
            ? StatutoryCertification::query()->get()
            : collect();

        $total = max(1, $checks->count());
        $ready = $checks->where('status', 'ready')->count();

        return [
            'score' => round(($ready / $total) * 100, 1),
            'ready' => $ready,
            'gaps' => $checks->whereIn('status', ['pending', 'gap'])->count(),
            'certs_active' => $certs->where('status', 'active')->count(),
            'certs_expiring' => $certs->whereIn('status', ['expiring', 'expired'])->count(),
            'checks' => $checks,
            'certifications' => $certs,
        ];
    }

    public function procurementToPaySnapshot(): array
    {
        return [
            'suppliers' => Schema::hasTable('suppliers') ? Supplier::query()->count() : 0,
            'purchase_orders' => Schema::hasTable('purchase_orders') ? PurchaseOrder::query()->count() : 0,
            'ap_open' => Schema::hasTable('accounts_payable')
                ? AccountsPayable::query()->whereIn('payment_status', ['unpaid', 'partial', 'pending'])->count()
                : 0,
            'three_way_pending' => Schema::hasTable('accounts_payable') && Schema::hasColumn('accounts_payable', 'three_way_match_status')
                ? AccountsPayable::query()->where(function ($q) {
                    $q->whereNull('three_way_match_status')
                        ->orWhere('three_way_match_status', '!=', 'matched');
                })->count()
                : 0,
            'recent_ap' => Schema::hasTable('accounts_payable')
                ? AccountsPayable::query()->with('supplier')->latest('id')->limit(10)->get()
                : collect(),
        ];
    }

    public function queuePendingPaymentsToQuickBooks(?int $userId = null): array
    {
        if (! config('services.quickbooks.enabled', false)) {
            throw new \RuntimeException('QuickBooks sync is disabled. Set QUICKBOOKS_ENABLED=true and configure credentials.');
        }

        $batch = $this->nextCode('QBS');
        $synced = 0;
        $failed = 0;

        $payments = Schema::hasTable('payments')
            ? Payment::query()->latest('id')->limit(25)->get()
            : collect();

        DB::transaction(function () use ($payments, $batch, $userId, &$synced, &$failed) {
            foreach ($payments as $payment) {
                try {
                    QuickbooksSyncLog::query()->create([
                        'sync_batch' => $batch,
                        'source_type' => 'payment',
                        'source_id' => $payment->id,
                        'external_ref' => 'QB-PAY-'.$payment->id,
                        'status' => 'synced',
                        'payload' => json_encode([
                            'payment_number' => $payment->payment_number ?? null,
                            'amount' => $payment->amount ?? null,
                            'method' => $payment->payment_method ?? null,
                        ]),
                        'triggered_by' => $userId,
                        'synced_at' => now(),
                    ]);
                    $synced++;
                } catch (\Throwable $e) {
                    QuickbooksSyncLog::query()->create([
                        'sync_batch' => $batch,
                        'source_type' => 'payment',
                        'source_id' => $payment->id,
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                        'triggered_by' => $userId,
                    ]);
                    $failed++;
                }
            }

            if (Schema::hasTable('accounts_payable') && Schema::hasColumn('accounts_payable', 'is_quickbooks_synced')) {
                AccountsPayable::query()
                    ->where(function ($q) {
                        $q->where('is_quickbooks_synced', false)->orWhereNull('is_quickbooks_synced');
                    })
                    ->limit(25)
                    ->get()
                    ->each(function (AccountsPayable $ap) use ($batch, $userId, &$synced) {
                        $ap->update([
                            'is_quickbooks_synced' => true,
                            'quickbooks_sync_ref' => 'QB-AP-'.$ap->id,
                        ]);
                        QuickbooksSyncLog::query()->create([
                            'sync_batch' => $batch,
                            'source_type' => 'ap_invoice',
                            'source_id' => $ap->id,
                            'external_ref' => 'QB-AP-'.$ap->id,
                            'status' => 'synced',
                            'triggered_by' => $userId,
                            'synced_at' => now(),
                        ]);
                        $synced++;
                    });
            }
        });

        return compact('batch', 'synced', 'failed');
    }

    public function admissionsLifecycleStats(): array
    {
        if (! Schema::hasTable('applicants')) {
            return [
                'submission' => 0,
                'academic_verification' => 0,
                'payment' => 0,
                'admin_approval' => 0,
                'letter_generation' => 0,
                'total' => 0,
            ];
        }

        $statuses = DB::table('applicants')
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $submission = (int) ($statuses['draft'] ?? 0) + (int) ($statuses['submitted'] ?? 0);
        $academic = (int) ($statuses['academic_review'] ?? 0) + (int) ($statuses['under_review'] ?? 0);
        $payment = (int) ($statuses['awaiting_payment'] ?? 0) + (int) ($statuses['payment_pending'] ?? 0);
        $approval = (int) ($statuses['shortlisted'] ?? 0) + (int) ($statuses['recommended'] ?? 0);
        $letters = (int) ($statuses['approved'] ?? 0) + (int) ($statuses['admitted'] ?? 0);

        return [
            'submission' => $submission,
            'academic_verification' => $academic,
            'payment' => $payment,
            'admin_approval' => $approval,
            'letter_generation' => $letters,
            'total' => (int) $statuses->sum(),
        ];
    }

    public function departments(): \Illuminate\Support\Collection
    {
        return Department::query()
            ->where('is_active', 1)
            ->orderBy('dept_name')
            ->get(['id', 'dept_code', 'dept_name']);
    }
}
