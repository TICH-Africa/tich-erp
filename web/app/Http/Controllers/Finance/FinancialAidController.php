<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinancialAidApplication;
use App\Models\Donation;
use App\Models\SponsorshipInquiry;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialAidController extends Controller
{
    public function index(): View
    {
        return redirect()->route('finance.financial-aid.applications');
    }

    public function applications(): View
    {
        $applications = FinancialAidApplication::query()
            ->where('status', 'approved')
            ->with(['opportunity', 'student'])
            ->orderByDesc('approved_at')
            ->paginate(20);

        return view('finance.financial-aid.applications', compact('applications'));
    }

    public function showApplication(FinancialAidApplication $application): View
    {
        $application->load(['opportunity', 'student']);
        $student = $application->student;

        // Get student's fee account if exists
        $feeAccount = null;
        if ($student) {
            $feeAccount = \App\Models\StudentAccount::query()
                ->where('student_id', $student->id)
                ->first();
        }

        return view('finance.financial-aid.show-application', compact('application', 'student', 'feeAccount'));
    }

    public function allocateToStudent(Request $request, FinancialAidApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'allocation_amount' => ['required', 'numeric', 'min:0', 'lte:' . $application->approved_amount],
            'allocation_notes' => ['nullable', 'string'],
            'fee_account_id' => ['nullable', 'exists:student_fee_accounts,id'],
        ]);

        $application->update([
            'allocation_status' => 'allocated',
            'approved_amount' => $validated['allocation_amount'],
            'allocated_by' => auth()->id(),
            'allocated_at' => now(),
            'status' => 'allocated',
        ]);

        // TODO: Create fee credit entry in student's fee account
        // This would integrate with the Finance module's fee management

        return back()->with('status', 'Financial aid allocated to student successfully.');
    }

    public function donations(): View
    {
        $donations = Donation::query()
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('finance.financial-aid.donations', compact('donations'));
    }

    public function showDonation(Donation $donation): View
    {
        return view('finance.financial-aid.show-donation', compact('donation'));
    }

    public function confirmDonation(Request $request, Donation $donation): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:confirmed,completed,cancelled'],
            'confirmation_notes' => ['nullable', 'string'],
            'transaction_reference' => ['nullable', 'string', 'max:100'],
        ]);

        $donation->update([
            'status' => $validated['status'],
            'confirmed_by' => auth()->id(),
            'confirmed_at' => now(),
        ]);

        return back()->with('status', 'Donation status updated successfully.');
    }

    public function sponsorships(): View
    {
        $sponsorships = SponsorshipInquiry::query()
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('finance.financial-aid.sponsorships', compact('sponsorships'));
    }

    public function showSponsorship(SponsorshipInquiry $inquiry): View
    {
        return view('finance.financial-aid.show-sponsorship', compact('inquiry'));
    }

    public function updateSponsorship(Request $request, SponsorshipInquiry $inquiry): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,contacted,in_progress,completed,declined'],
            'followup_notes' => ['nullable', 'string'],
        ]);

        $inquiry->update([
            'status' => $validated['status'],
        ]);

        return back()->with('status', 'Sponsorship inquiry status updated successfully.');
    }
}