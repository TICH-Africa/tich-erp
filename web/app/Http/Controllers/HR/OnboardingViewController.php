<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffOnboarding;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingViewController extends Controller
{
    public function index(): View
    {
        $onboardings = StaffOnboarding::with(['staff', 'staff.department', 'staff.campus', 'staff.documents'])
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('hr.onboarding.index', ['onboardings' => $onboardings]);
    }

    public function create(): View
    {
        $staff = Staff::whereIn('employment_status', ['onboarding', 'active'])
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'surname', 'employee_number']);

        return view('hr.onboarding.create', ['staff' => $staff]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'current_step' => 'required|string|in:biodata,employment_terms,banking,documents,contract,orientation,statutory,ess_account,completed',
            'status' => 'required|string|in:in_progress,pending_hr_review,approved,rejected,completed',
        ]);

        $staff = Staff::findOrFail($validated['staff_id']);

        $onboarding = StaffOnboarding::create([
            'staff_id' => $staff->id,
            'onboarding_number' => 'ONB-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'current_step' => $validated['current_step'],
            'status' => $validated['status'],
            'completed_steps' => [$validated['current_step']],
        ]);

        return redirect()->route('hr.onboarding.index')->with('success', 'Onboarding record created successfully.');
    }
}
