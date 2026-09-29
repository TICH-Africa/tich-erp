<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancialAidOpportunity;
use App\Models\FinancialAidApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialAidController extends Controller
{
    public function index(Request $request): View
    {
        $query = FinancialAidOpportunity::query()->withCount('applications');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('slug', 'like', "%{$request->search}%");
            });
        }

        $opportunities = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('admin.financial-aid.index', compact('opportunities'));
    }

    public function create(): View
    {
        return view('admin.financial-aid.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:300'],
            'slug' => ['required', 'string', 'max:300', 'unique:financial_aid_opportunities,slug'],
            'description' => ['required', 'string'],
            'eligibility_criteria' => ['nullable', 'string'],
            'application_process' => ['nullable', 'string'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'funding_type' => ['required', 'in:scholarship,grant,loan,work_study'],
            'application_open_date' => ['nullable', 'date'],
            'application_deadline' => ['nullable', 'date', 'after_or_equal:application_open_date'],
            'status' => ['required', 'in:draft,published,closed,archived'],
        ]);

        if ($validated['status'] === 'published') {
            $validated['published_at'] = now();
        }

        $validated['created_by'] = auth()->id();

        FinancialAidOpportunity::create($validated);

        return redirect()->route('admin.financial-aid.index')->with('status', 'Financial aid opportunity created successfully.');
    }

    public function show(FinancialAidOpportunity $financialAidOpportunity): View
    {
        $financialAidOpportunity->load(['applications' => function ($query) {
            $query->orderByDesc('created_at');
        }]);

        return view('admin.financial-aid.show', compact('financialAidOpportunity'));
    }

    public function edit(FinancialAidOpportunity $financialAidOpportunity): View
    {
        return view('admin.financial-aid.edit', compact('financialAidOpportunity'));
    }

    public function update(Request $request, FinancialAidOpportunity $financialAidOpportunity): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:300'],
            'slug' => ['required', 'string', 'max:300', 'unique:financial_aid_opportunities,slug,' . $financialAidOpportunity->id],
            'description' => ['required', 'string'],
            'eligibility_criteria' => ['nullable', 'string'],
            'application_process' => ['nullable', 'string'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'funding_type' => ['required', 'in:scholarship,grant,loan,work_study'],
            'application_open_date' => ['nullable', 'date'],
            'application_deadline' => ['nullable', 'date', 'after_or_equal:application_open_date'],
            'status' => ['required', 'in:draft,published,closed,archived'],
        ]);

        $wasDraft = $financialAidOpportunity->status === 'draft';
        $nowPublished = $validated['status'] === 'published';

        if ($wasDraft && $nowPublished) {
            $validated['published_at'] = now();
        }

        $validated['updated_by'] = auth()->id();

        $financialAidOpportunity->update($validated);

        return redirect()->route('admin.financial-aid.show', $financialAidOpportunity)->with('status', 'Financial aid opportunity updated successfully.');
    }

    public function destroy(FinancialAidOpportunity $financialAidOpportunity): RedirectResponse
    {
        $financialAidOpportunity->delete();
        return redirect()->route('admin.financial-aid.index')->with('status', 'Financial aid opportunity deleted successfully.');
    }

    // Application management
    public function applications(FinancialAidOpportunity $financialAidOpportunity): View
    {
        $applications = $financialAidOpportunity->applications()
            ->with('student')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.financial-aid.applications', compact('financialAidOpportunity', 'applications'));
    }

    public function reviewApplication(FinancialAidApplication $application): View
    {
        $application->load('opportunity', 'student');
        return view('admin.financial-aid.review', compact('application'));
    }

    public function updateApplicationStatus(Request $request, FinancialAidApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,under_review,approved,rejected,allocated'],
            'admin_notes' => ['nullable', 'string'],
            'approved_amount' => ['nullable', 'numeric', 'min:0', 'required_if:status,approved'],
        ]);

        $update = [
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ];

        if ($validated['status'] === 'approved') {
            $update['approved_amount'] = $validated['approved_amount'] ?? $application->opportunity->amount;
        }

        $application->update($update);

        return back()->with('status', 'Application status updated successfully.');
    }

    public function allocateFunds(Request $request, FinancialAidApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'allocated_amount' => ['required', 'numeric', 'min:0', 'lte:' . ($application->approved_amount ?? $application->opportunity->amount)],
            'allocation_notes' => ['nullable', 'string'],
        ]);

        $application->update([
            'allocation_status' => 'allocated',
            'approved_amount' => $validated['allocated_amount'],
            'allocation_status' => 'allocated',
            'allocated_by' => auth()->id(),
            'allocated_at' => now(),
            'status' => 'allocated',
        ]);

        // TODO: Integrate with Finance module to create allocation
        // This would create a payment/allocation record in the finance module

        return back()->with('status', 'Funds allocated successfully. Finance team can now process the payment.');
    }
}