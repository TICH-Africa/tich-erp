<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\SponsorshipInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DonationController extends Controller
{
    public function index(): View
    {
        return view('pages.financial-aid.donate');
    }

    public function sponsor(): View
    {
        return view('pages.financial-aid.sponsor');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'donation_type' => ['required', 'in:one_time,monthly,annual'],
            'amount' => ['required', 'numeric', 'min:100'],
            'designation' => ['nullable', 'string', 'max:50'],
            'donor_name' => ['required', 'string', 'max:300'],
            'donor_email' => ['required', 'email', 'max:255'],
            'donor_phone' => ['nullable', 'string', 'max:30'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        Donation::create($validated);

        return back()->with('status', 'Thank you for your donation! Our team will contact you shortly to complete the payment process.');
    }

    public function storeSponsorship(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sponsor_type' => ['required', 'in:full,partial,co_sponsor,named_scholarship'],
            'duration' => ['required', 'in:1,2,3,ongoing'],
            'preferred_field' => ['nullable', 'string', 'max:50'],
            'sponsor_name' => ['required', 'string', 'max:300'],
            'sponsor_email' => ['required', 'email', 'max:255'],
            'sponsor_phone' => ['required', 'string', 'max:30'],
            'sponsor_message' => ['nullable', 'string', 'max:1000'],
        ]);

        SponsorshipInquiry::create($validated);

        return back()->with('status', 'Thank you for your interest in sponsorship! Our team will contact you within 2 business days.');
    }
}