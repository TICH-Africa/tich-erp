<?php

namespace App\Services\Procurement;

use App\Models\Asset;
use App\Models\AssetAudit;
use App\Models\AssetDisposal;
use App\Models\Discrepancy;
use App\Models\GoodsReceivedNote;
use App\Models\InventoryItem;
use App\Models\ProcurementInvoice;
use App\Models\ProcurementPayment;
use App\Models\ProcurementRequisition;
use App\Models\Rfq;
use App\Models\Staff;
use App\Models\StockAlert;
use App\Models\StockIssue;
use App\Models\Supplier;
use App\Models\User;
use App\Services\RBACService;
use Illuminate\Support\Facades\Schema;

class ProcurementMobilePortalService
{
    public function __construct(
        protected ProcurementSidebarNotificationService $sidebarCounts,
        protected RequisitionService $requisitionService,
        protected RBACService $rbac,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(User $user): array
    {
        $counts = $this->sidebarCounts->counts(true);

        return [
            'metrics' => [
                'pending_requisitions' => $counts['requisitions.pending'] ?? 0,
                'pending_grns' => $counts['grns.pending'] ?? 0,
                'active_stock_alerts' => $counts['stock-alerts.active'] ?? 0,
                'pending_stock_issues' => $counts['stock-issues.pending'] ?? 0,
                'actionable_rfqs' => $counts['rfqs.actionable'] ?? 0,
                'pending_suppliers' => $counts['suppliers.pending'] ?? 0,
            ],
            'badge_counts' => $counts,
            'action_inbox' => $this->actionInbox($user, 15),
            'quick_modules' => $this->moduleDefinitions(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function actionInbox(User $user, int $limit = 20): array
    {
        $items = [];

        if (Schema::hasTable('procurement_requisitions')) {
            ProcurementRequisition::query()
                ->with(['department', 'requester'])
                ->whereIn('status', ['submitted', 'hod_approved', 'finance_approved'])
                ->orderByDesc('created_at')
                ->limit(8)
                ->get()
                ->each(function (ProcurementRequisition $req) use (&$items) {
                    $level = $req->currentApprovalLevel();
                    $items[] = [
                        'kind' => 'requisition',
                        'id' => $req->id,
                        'title' => $req->requisition_number,
                        'subtitle' => $req->requested_item ?? 'Purchase requisition',
                        'meta' => $req->department?->dept_name,
                        'status' => $req->status,
                        'status_label' => $req->statusLabel(),
                        'action_label' => $level !== 'completed' ? 'Review approval ('.strtoupper($level).')' : null,
                        'sort_at' => optional($req->created_at)?->timestamp ?? 0,
                    ];
                });
        }

        if (Schema::hasTable('goods_received_notes')) {
            GoodsReceivedNote::query()
                ->with(['purchaseOrder'])
                ->where('inspection_status', 'pending')
                ->orderByDesc('created_at')
                ->limit(6)
                ->get()
                ->each(function (GoodsReceivedNote $grn) use (&$items) {
                    $items[] = [
                        'kind' => 'grn',
                        'id' => $grn->id,
                        'title' => $grn->grn_number,
                        'subtitle' => 'Goods receipt · inspect',
                        'meta' => $grn->purchaseOrder?->po_number,
                        'status' => $grn->inspection_status,
                        'status_label' => ucfirst($grn->inspection_status ?? 'pending'),
                        'action_label' => 'Complete inspection',
                        'sort_at' => optional($grn->created_at)?->timestamp ?? 0,
                    ];
                });
        }

        if (Schema::hasTable('stock_issues')) {
            StockIssue::query()
                ->with(['item', 'department'])
                ->where(function ($q) {
                    $q->where('approval_status', 'pending')->orWhere('status', 'pending');
                })
                ->orderByDesc('created_at')
                ->limit(6)
                ->get()
                ->each(function (StockIssue $issue) use (&$items) {
                    $items[] = [
                        'kind' => 'stock_issue',
                        'id' => $issue->id,
                        'title' => $issue->item?->item_name ?? 'Stock issue',
                        'subtitle' => 'Qty '.$issue->quantity,
                        'meta' => $issue->department?->dept_name,
                        'status' => $issue->approval_status,
                        'status_label' => ucfirst($issue->approval_status ?? 'pending'),
                        'action_label' => 'Approve issue',
                        'sort_at' => optional($issue->created_at)?->timestamp ?? 0,
                    ];
                });
        }

        if (Schema::hasTable('stock_alerts')) {
            StockAlert::query()
                ->with('inventoryItem')
                ->where('status', 'active')
                ->orderByDesc('created_at')
                ->limit(4)
                ->get()
                ->each(function (StockAlert $alert) use (&$items) {
                    $items[] = [
                        'kind' => 'stock_alert',
                        'id' => $alert->id,
                        'title' => $alert->inventoryItem?->item_name ?? 'Low stock',
                        'subtitle' => 'Stock alert',
                        'meta' => null,
                        'status' => $alert->status,
                        'status_label' => 'Active',
                        'action_label' => 'View alert',
                        'sort_at' => optional($alert->created_at)?->timestamp ?? 0,
                    ];
                });
        }

        usort($items, fn ($a, $b) => ($b['sort_at'] ?? 0) <=> ($a['sort_at'] ?? 0));

        return array_map(function (array $row) {
            unset($row['sort_at']);

            return $row;
        }, array_slice($items, 0, $limit));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function moduleDefinitions(): array
    {
        return [
            ['key' => 'requisitions', 'label' => 'Requisitions', 'mobile' => true, 'web_path' => '/procurement/requisitions'],
            ['key' => 'grns', 'label' => 'GRNs', 'mobile' => true, 'web_path' => '/procurement/grns'],
            ['key' => 'stock_issues', 'label' => 'Stock issues', 'mobile' => true, 'web_path' => '/procurement/stock-issues'],
            ['key' => 'stock_alerts', 'label' => 'Stock alerts', 'mobile' => true, 'web_path' => '/procurement/stock-alerts'],
            ['key' => 'assets', 'label' => 'Assets', 'mobile' => true, 'web_path' => '/procurement/assets'],
            ['key' => 'suppliers', 'label' => 'Suppliers', 'mobile' => true, 'web_path' => '/procurement/suppliers'],
            ['key' => 'rfqs', 'label' => 'RFQs', 'mobile' => true, 'web_path' => '/procurement/rfqs'],
            ['key' => 'inventory', 'label' => 'Inventory', 'mobile' => true, 'web_path' => '/procurement/inventory-items'],
            ['key' => 'disposals', 'label' => 'Disposals', 'mobile' => true, 'web_path' => '/procurement/asset-disposals'],
            ['key' => 'audits', 'label' => 'Audits', 'mobile' => true, 'web_path' => '/procurement/asset-audits'],
            ['key' => 'payments', 'label' => 'Payment & verification', 'mobile' => true, 'web_path' => '/procurement/payment-verification'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeRequisition(ProcurementRequisition $requisition, ?User $user = null): array
    {
        $requisition->loadMissing(['department', 'requester']);

        $level = $requisition->currentApprovalLevel();
        $budget = $this->requisitionService->verifyBudget($requisition);
        $budgetPassed = (bool) ($budget['passed'] ?? false);

        return [
            'id' => $requisition->id,
            'requisition_number' => $requisition->requisition_number,
            'requested_item' => $requisition->requested_item,
            'department' => $requisition->department?->dept_name,
            'estimated_cost' => (float) $requisition->estimated_cost,
            'status' => $requisition->status,
            'status_label' => $requisition->statusLabel(),
            'request_date' => optional($requisition->request_date)?->format('Y-m-d'),
            'justification' => $requisition->justification,
            'line_items' => $requisition->line_items ?? [],
            'approval' => [
                'current_level' => $level,
                'hod' => $requisition->hod_approval_status,
                'finance' => $requisition->finance_approval_status,
                'ceo' => $requisition->ceo_approval_status,
            ],
            'budget_check' => [
                'passed' => $budgetPassed,
                'message' => $budget['message'] ?? '',
            ],
            'is_draft' => $requisition->isDraft(),
            'can_submit' => $user && $requisition->isDraft() && $budgetPassed && (bool) $user->staff_id,
            'can_approve' => $user ? $this->canApproveRequisition($user, $requisition) : false,
            'can_reject' => $user ? $this->canApproveRequisition($user, $requisition) : false,
            'web_path' => '/procurement/requisitions/'.$requisition->id,
        ];
    }

    public function canApproveRequisition(User $user, ProcurementRequisition $requisition): bool
    {
        if ($requisition->isRejected() || $requisition->isCompleted()) {
            return false;
        }

        if (! $user->staff_id) {
            return false;
        }

        $level = $requisition->currentApprovalLevel();
        if (! in_array($level, ['hod', 'finance', 'ceo'], true)) {
            return false;
        }

        if (! $this->rbac->hasPermission($user, 'procurement.read')) {
            return false;
        }

        $budget = $this->requisitionService->verifyBudget($requisition);

        return (bool) ($budget['passed'] ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeStockIssue(StockIssue $issue): array
    {
        $issue->loadMissing(['item', 'department', 'requestedBy']);

        return [
            'id' => $issue->id,
            'item_name' => $issue->item?->item_name,
            'item_code' => $issue->item?->item_code,
            'department' => $issue->department?->dept_name,
            'quantity' => (float) $issue->quantity,
            'unit_cost' => (float) $issue->unit_cost,
            'total_cost' => (float) $issue->total_cost,
            'reason' => $issue->reason,
            'approval_status' => $issue->approval_status,
            'status' => $issue->status,
            'requested_by' => $issue->requestedBy?->fullName(),
            'created_at' => optional($issue->created_at)?->toIso8601String(),
            'can_approve' => $issue->approval_status === 'pending',
            'web_path' => '/procurement/stock-issues/'.$issue->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeGrn(GoodsReceivedNote $grn): array
    {
        $grn->loadMissing(['purchaseOrder.supplier', 'receivedBy', 'items']);

        return [
            'id' => $grn->id,
            'grn_number' => $grn->grn_number,
            'po_number' => $grn->purchaseOrder?->po_number,
            'supplier' => $grn->purchaseOrder?->supplier?->supplier_name,
            'received_date' => optional($grn->received_date)?->format('Y-m-d'),
            'inspection_status' => $grn->inspection_status,
            'inspection_notes' => $grn->inspection_notes,
            'is_complete' => (bool) $grn->is_complete,
            'received_by' => $grn->receivedBy?->fullName(),
            'can_complete_inspection' => $grn->inspection_status === 'pending',
            'items' => $grn->items->map(fn ($item) => [
                'item_name' => $item->item_name,
                'quantity_received' => (float) $item->quantity_received,
                'unit_cost' => (float) $item->unit_cost,
                'total_cost' => (float) $item->total_cost,
                'condition' => $item->condition,
                'classification' => $item->classification,
            ])->values()->all(),
            'web_path' => '/procurement/grns/'.$grn->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeStockAlert(StockAlert $alert): array
    {
        $alert->loadMissing('inventoryItem');

        return [
            'id' => $alert->id,
            'status' => $alert->status,
            'alert_type' => $alert->alert_type,
            'notes' => $alert->notes,
            'item_name' => $alert->inventoryItem?->item_name,
            'item_code' => $alert->inventoryItem?->item_code,
            'current_stock' => $alert->current_stock ?? $alert->inventoryItem?->current_stock,
            'reorder_level' => $alert->reorder_level ?? $alert->inventoryItem?->reorder_level,
            'recommended_quantity' => $alert->recommended_quantity,
            'triggered_at' => optional($alert->triggered_at)?->toIso8601String(),
            'created_at' => optional($alert->created_at)?->toIso8601String(),
            'can_auto_reorder' => $alert->status === 'active',
            'requisition_id' => $alert->requisition_id,
            'web_path' => '/procurement/stock-alerts/'.$alert->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeAsset(Asset $asset): array
    {
        $asset->loadMissing('custodian');

        return [
            'id' => $asset->id,
            'asset_number' => $asset->asset_number,
            'asset_name' => $asset->asset_name,
            'category' => $asset->asset_category,
            'location' => $asset->location_name,
            'custodian' => $asset->custodian?->fullName(),
            'status' => $asset->asset_status,
            'tag_number' => $asset->tag_number,
            'serial_number' => $asset->serial_number,
            'condition' => $asset->condition,
            'web_path' => '/procurement/assets/'.$asset->id,
        ];
    }

    public function staffForUser(User $user): ?Staff
    {
        if (! $user->staff_id) {
            return null;
        }

        return Staff::query()->find($user->staff_id);
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeSupplier(Supplier $supplier): array
    {
        return [
            'id' => $supplier->id,
            'supplier_code' => $supplier->supplier_code,
            'supplier_name' => $supplier->supplier_name,
            'contact_person' => $supplier->contact_person,
            'email' => $supplier->email,
            'phone' => $supplier->phone,
            'compliance_status' => $supplier->compliance_status,
            'supplier_category' => $supplier->supplier_category,
            'risk_rating' => $supplier->risk_rating,
            'is_active' => (bool) $supplier->is_active,
            'blacklist_status' => $supplier->blacklist_status,
            'can_verify' => in_array($supplier->compliance_status, ['pending', 'under_review'], true),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeRfq(Rfq $rfq): array
    {
        $rfq->loadMissing(['requisition', 'awardedSupplier']);

        return [
            'id' => $rfq->id,
            'rfq_number' => $rfq->rfq_number,
            'status' => $rfq->status,
            'item_description' => $rfq->item_description,
            'quantity' => (float) $rfq->quantity,
            'submission_deadline' => optional($rfq->submission_deadline)?->format('Y-m-d'),
            'requisition_number' => $rfq->requisition?->requisition_number,
            'awarded_supplier' => $rfq->awardedSupplier?->supplier_name,
            'awarded_amount' => $rfq->awarded_amount !== null ? (float) $rfq->awarded_amount : null,
            'approval_status' => $rfq->approval_status,
            'can_approve_award' => $rfq->status === 'awarded' && $rfq->approval_status === 'pending',
            'can_reject_award' => $rfq->status === 'awarded' && $rfq->approval_status === 'pending',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeInventoryItem(InventoryItem $item): array
    {
        $item->loadMissing('supplier');
        $lowStock = $item->current_stock <= $item->reorder_level;

        return [
            'id' => $item->id,
            'item_code' => $item->item_code,
            'item_name' => $item->item_name,
            'category' => $item->category,
            'unit_of_measure' => $item->unit_of_measure,
            'current_stock' => (int) $item->current_stock,
            'reorder_level' => (int) $item->reorder_level,
            'unit_cost' => (float) $item->unit_cost,
            'store_location' => $item->store_location,
            'supplier' => $item->supplier?->supplier_name,
            'is_active' => (bool) $item->is_active,
            'low_stock' => $lowStock,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeAssetDisposal(AssetDisposal $record): array
    {
        $record->loadMissing(['asset', 'requestedBy']);

        return [
            'id' => $record->id,
            'disposal_type' => $record->disposal_type,
            'approval_status' => $record->approval_status,
            'status' => $record->status,
            'reason' => $record->reason,
            'disposed_value' => (float) $record->disposed_value,
            'disposal_date' => optional($record->disposal_date)?->format('Y-m-d'),
            'asset_number' => $record->asset?->asset_number,
            'asset_name' => $record->asset?->asset_name,
            'requested_by' => $record->requestedBy?->fullName(),
            'can_approve' => $record->approval_status === 'pending',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeAssetAudit(AssetAudit $record): array
    {
        $record->loadMissing(['asset', 'auditor', 'reviewer']);

        return [
            'id' => $record->id,
            'status' => $record->status,
            'verification_status' => $record->verification_status,
            'condition' => $record->condition,
            'notes' => $record->notes,
            'review_notes' => $record->review_notes,
            'asset_number' => $record->asset?->asset_number,
            'asset_name' => $record->asset?->asset_name,
            'auditor' => $record->auditor?->fullName(),
            'reviewer' => $record->reviewer?->fullName(),
            'can_review' => in_array($record->status, ['submitted', 'pending'], true),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeInvoice(ProcurementInvoice $invoice): array
    {
        $invoice->loadMissing('supplier');

        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'supplier' => $invoice->supplier?->supplier_name,
            'total_amount' => (float) $invoice->total_amount,
            'balance' => (float) $invoice->balance,
            'status' => $invoice->status,
            'invoice_date' => optional($invoice->invoice_date)?->format('Y-m-d'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeDiscrepancy(Discrepancy $row): array
    {
        $row->loadMissing(['invoice', 'supplier']);

        return [
            'id' => $row->id,
            'status' => $row->status,
            'field_name' => $row->field_name,
            'deviation_amount' => $row->deviation_amount !== null ? (float) $row->deviation_amount : null,
            'invoice_number' => $row->invoice?->invoice_number,
            'supplier' => $row->supplier?->supplier_name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializePayment(ProcurementPayment $payment): array
    {
        $payment->loadMissing(['invoice', 'supplier']);

        return [
            'id' => $payment->id,
            'payment_number' => $payment->payment_number,
            'amount' => (float) $payment->amount,
            'status' => $payment->status,
            'payment_method' => $payment->payment_method,
            'invoice_number' => $payment->invoice?->invoice_number,
            'supplier' => $payment->supplier?->supplier_name,
            'payment_date' => optional($payment->payment_date)?->format('Y-m-d'),
        ];
    }
}
