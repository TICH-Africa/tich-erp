<?php

namespace App\Http\Controllers\Week4;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function showPortal(): View
    {
        return view('week4.apply.portal');
    }

    public function handleStep(Request $request, int $step): RedirectResponse
    {
        return back();
    }

    public function submitApplication(Request $request, int $applicantId): RedirectResponse
    {
        return back()->with('status', 'Application submitted (stub).');
    }

    public function checkStatus(): View
    {
        return view('week4.apply.status');
    }
}
