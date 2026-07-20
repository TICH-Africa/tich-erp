<?php

namespace App\Http\Controllers\Week4;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function showDashboard(): View
    {
        return view('week4.dashboard');
    }

    public function listApplications(): View
    {
        return view('week4.applications.index', ['applications' => collect()]);
    }

    public function reviewApplication(int $id): View
    {
        return view('week4.applications.show', ['applicationId' => $id]);
    }

    public function shortlistApplication(Request $request, int $id): RedirectResponse
    {
        return back()->with('status', 'Application shortlisted (stub).');
    }
}
