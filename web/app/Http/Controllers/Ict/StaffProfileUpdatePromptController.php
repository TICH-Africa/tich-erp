<?php

namespace App\Http\Controllers\Ict;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Services\EmployeeProfileUpdatePromptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StaffProfileUpdatePromptController extends Controller
{
    public function __construct(
        protected EmployeeProfileUpdatePromptService $prompts,
    ) {}

    public function store(Request $request, Staff $staff): RedirectResponse
    {
        $validated = $request->validate([
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => ['string', 'in:'.implode(',', \App\Services\EmployeeProfileCompletenessService::requestableFieldKeys())],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->prompts->send(
                $staff,
                $request->user(),
                $validated['fields'],
                $validated['notes'] ?? null,
                'ict',
            );
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Profile update request sent to '.$staff->fullName().'.');
    }
}
