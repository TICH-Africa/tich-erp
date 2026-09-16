<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Staff;
use App\Models\StockIssue;
use App\Services\Procurement\InventoryService;
use Illuminate\Http\RedirectResponse;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockIssueController extends Controller
{
    public function index(Request $request): View
    {
        $query = StockIssue::query()->with(['item', 'department', 'requestedBy', 'approvedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $issues = $query->orderByDesc('created_at')->paginate(Pagination::perPage($request))->withQueryString();

        return view('procurement.stock-issues.index', compact('issues'));
    }

    public function create(): View
    {
        return view('procurement.stock-issues.create', [
            'items' => InventoryItem::query()->where('current_stock', '>', 0)->orderBy('item_name')->get(['id', 'item_code', 'item_name', 'current_stock', 'unit_of_measure']),
            'departments' => Department::query()->where('is_active', 1)->orderBy('dept_name')->get(['id', 'dept_name']),
            'staff' => Staff::query()->orderBy('first_name')->get(['id', 'first_name', 'surname']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'requested_by' => ['required', 'exists:staff,id'],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $validated['total_cost'] = round((float) $validated['quantity'] * (float) $validated['unit_cost'], 2);
        $validated['approval_status'] = 'pending';
        $validated['status'] = 'pending';

        $issue = StockIssue::query()->create($validated);

        InventoryTransaction::query()->create([
            'inventory_item_id' => $validated['inventory_item_id'],
            'transaction_type' => 'issue_pending',
            'quantity' => (int) $validated['quantity'],
            'unit_cost' => $validated['unit_cost'],
            'total_cost' => $validated['total_cost'],
            'reference_table' => 'stock_issues',
            'reference_id' => $issue->id,
            'department_id' => $validated['department_id'],
            'recorded_by' => $validated['requested_by'],
            'transaction_date' => now()->format('Y-m-d'),
            'notes' => "Pending stock issue: {$issue->item->item_name}",
        ]);

        return redirect()
            ->route('procurement.stock-issues.show', $issue)
            ->with('status', 'Stock issue request created.');
    }

    public function show(StockIssue $issue): View
    {
        $issue->load(['item', 'department', 'requestedBy', 'approvedBy']);

        return view('procurement.stock-issues.show', compact('issue'));
    }

    public function approve(StockIssue $issue, InventoryService $inventoryService): RedirectResponse
    {
        if ($issue->approval_status === 'approved') {
            return back()->withErrors(['status' => 'Already approved.']);
        }

        $item = $issue->item;
        $qty = (int) $issue->quantity;

        if ($item->current_stock < $qty) {
            return back()->withErrors(['quantity' => 'Insufficient stock to approve this issue.']);
        }

        $issue->update([
            'approval_status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'status' => 'completed',
            'notes' => 'Approved by ' . auth()->user()->staff?->full_name ?? auth()->user()->name,
        ]);

        $inventoryService->updateStock(
            $item->id,
            $qty,
            'issue',
            auth()->id(),
            [
                'department_id' => $issue->department_id,
                'reference_table' => 'stock_issues',
                'reference_id' => $issue->id,
            ],
            ['notes' => "Stock issue: {$item->item_name}"]
        );

        // Remove pending transaction created at request time
        InventoryTransaction::query()
            ->where('reference_table', 'stock_issues')
            ->where('reference_id', $issue->id)
            ->where('transaction_type', 'issue_pending')
            ->delete();

        return back()->with('status', 'Stock issue approved. Stock deducted.');
    }
}
