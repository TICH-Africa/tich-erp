<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\Procurement\SupplierService;
use Illuminate\Http\RedirectResponse;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(protected SupplierService $supplierService) {}

    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $category = $request->string('category')->toString();

        $suppliers = Supplier::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('supplier_name', 'like', "%{$search}%")
                        ->orWhere('supplier_code', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('compliance_status', $status))
            ->when($category !== '', fn ($query) => $query->where('supplier_category', $category))
            ->orderByDesc('created_at')
            ->paginate(Pagination::perPage($request))
            ->withQueryString();

        $stats = [
            'total' => Supplier::query()->count(),
            'active' => Supplier::query()->active()->count(),
            'blacklisted' => Supplier::query()->blacklisted()->count(),
            'pending_verification' => Supplier::query()->where('compliance_status', 'pending')->count(),
            'compliant' => Supplier::query()->compliant()->count(),
        ];

        return view('procurement.suppliers.index', compact('suppliers', 'stats', 'search', 'status', 'category'));
    }

    public function create(): View
    {
        return view('procurement.suppliers.create');
    }

    public function store(Request $request): RedirectResponse
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
            'bank_name' => ['nullable', 'string', 'max:200'],
            'bank_account_name' => ['nullable', 'string', 'max:300'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_branch' => ['nullable', 'string', 'max:200'],
            'bank_code' => ['nullable', 'string', 'max:20'],
            'compliance_doc_path' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
            'pin_certificate_path' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
            'cr12_path' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
            'audited_financial_statements_path' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
        ]);

        if ($request->hasFile('compliance_doc_path')) {
            $validated['compliance_doc_path'] = $request->file('compliance_doc_path')->store('suppliers/compliance', 'public');
        }
        if ($request->hasFile('pin_certificate_path')) {
            $validated['pin_certificate_path'] = $request->file('pin_certificate_path')->store('suppliers/pin', 'public');
        }
        if ($request->hasFile('cr12_path')) {
            $validated['cr12_path'] = $request->file('cr12_path')->store('suppliers/cr12', 'public');
        }
        if ($request->hasFile('audited_financial_statements_path')) {
            $validated['audited_financial_statements_path'] = $request->file('audited_financial_statements_path')->store('suppliers/audited', 'public');
        }

        $supplier = $this->supplierService->registerSupplier($validated);

        return redirect()
            ->route('procurement.suppliers.show', $supplier)
            ->with('status', 'Supplier registered successfully.');
    }

    public function show(Supplier $supplier): View
    {
        $supplier->load(['rfqs', 'evaluations.evaluator', 'rfqInvitations.rfq']);

        return view('procurement.suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier): View
    {
        return view('procurement.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
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
            'bank_name' => ['nullable', 'string', 'max:200'],
            'bank_account_name' => ['nullable', 'string', 'max:300'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_branch' => ['nullable', 'string', 'max:200'],
            'bank_code' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $supplier->update($validated);

        return redirect()
            ->route('procurement.suppliers.show', $supplier)
            ->with('status', 'Supplier updated successfully.');
    }

    public function verify(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $request->validate([
            'compliance_status' => ['required', 'in:pending,approved,rejected,under_review'],
            'risk_rating' => ['nullable', 'in:low,medium,high'],
            'supplier_category' => ['nullable', 'in:goods,services,works'],
            'sub_category' => ['nullable', 'string', 'max:100'],
        ]);

        $this->supplierService->verifyCompliance($supplier, $validated);

        return back()->with('status', 'Supplier verification updated.');
    }

    public function blacklist(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->supplierService->blacklist($supplier, $validated['reason']);

        return back()->with('status', 'Supplier has been blacklisted.');
    }

    public function removeBlacklist(Supplier $supplier): RedirectResponse
    {
        $this->supplierService->removeBlacklist($supplier);

        return back()->with('status', 'Supplier blacklist has been removed.');
    }
}
