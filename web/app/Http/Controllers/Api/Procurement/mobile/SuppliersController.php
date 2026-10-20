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
                        ->orWhere('supplier_code', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (Supplier $s) => $this->portal->serializeSupplier($s))
            ->values();

        return response()->json(['items' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_name' => ['required', 'string', 'max:300'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'contact_person' => ['nullable', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'postal_address' => ['nullable', 'string', 'max:300'],
            'physical_address' => ['nullable', 'string', 'max:500'],
            'kra_pin' => ['nullable', 'string', 'max:50'],
            'payment_method' => ['required', 'in:bank,paybill,till,mobile'],
            'bank_name' => ['nullable', 'string', 'max:200'],
            'bank_account_name' => ['nullable', 'string', 'max:300'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_branch' => ['nullable', 'string', 'max:200'],
            'paybill_number' => ['nullable', 'string', 'max:20', 'required_if:payment_method,paybill'],
            'paybill_account_number' => ['nullable', 'string', 'max:50', 'required_if:payment_method,paybill'],
            'till_number' => ['nullable', 'string', 'max:20', 'required_if:payment_method,till'],
            'mobile_number' => ['nullable', 'string', 'max:20', 'required_if:payment_method,mobile'],
            'compliance_doc_path' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
            'pin_certificate_path' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
            'cr12_path' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
            'audited_financial_statements_path' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
        ]);

        if ($request->hasFile('compliance_doc_path')) {
            $validated['compliance_doc_path'] = $request->file('compliance_doc_path')
                ->store('suppliers/compliance', 'public');
        }
        if ($request->hasFile('pin_certificate_path')) {
            $validated['pin_certificate_path'] = $request->file('pin_certificate_path')
                ->store('suppliers/pin', 'public');
        }
        if ($request->hasFile('cr12_path')) {
            $validated['cr12_path'] = $request->file('cr12_path')
                ->store('suppliers/cr12', 'public');
        }
        if ($request->hasFile('audited_financial_statements_path')) {
            $validated['audited_financial_statements_path'] = $request->file('audited_financial_statements_path')
                ->store('suppliers/audited', 'public');
        }

        $supplier = $this->supplierService->registerSupplier($validated);
        $this->broadcastProcurementCounts();

        return response()->json([
            'message' => 'Supplier registered.',
            'item' => $this->portal->serializeSupplier($supplier),
        ], 201);
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
