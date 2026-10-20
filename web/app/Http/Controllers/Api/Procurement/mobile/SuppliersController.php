<?php

namespace App\Http\Controllers\Api\Procurement\mobile;

use App\Http\Controllers\Api\Procurement\mobile\Concerns\BroadcastsProcurementCounts;
use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\Procurement\ProcurementMobilePortalService;
use App\Services\Procurement\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuppliersController extends Controller
{
    use BroadcastsProcurementCounts;

    public function __construct(
        protected ProcurementMobilePortalService $portal,
        protected SupplierService $supplierService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();

        $items = Supplier::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('supplier_name', 'like', "%{$search}%")
                        ->orWhere('supplier_code', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (Supplier $s) => $this->portal->serializeSupplier($s))
            ->values();

        return response()->json(['items' => $items]);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return response()->json($this->portal->serializeSupplier($supplier));
    }

    public function verify(Request $request, Supplier $supplier): JsonResponse
    {
        $validated = $request->validate([
            'compliance_status' => ['required', 'in:approved,rejected,under_review,pending'],
            'risk_rating' => ['nullable', 'in:low,medium,high'],
            'supplier_category' => ['nullable', 'in:goods,services,works'],
        ]);

        $this->supplierService->verifyCompliance($supplier, $validated);
        $this->broadcastProcurementCounts();

        return response()->json([
            'message' => 'Supplier verification updated.',
            'item' => $this->portal->serializeSupplier($supplier->fresh()),
        ]);
    }
}
