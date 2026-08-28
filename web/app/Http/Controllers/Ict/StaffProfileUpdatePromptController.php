<?php

namespace App\Http\Controllers\Ict;

use App\Http\Controllers\Controller;
use App\Services\EmployeeProfileUpdatePromptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StaffProfileUpdatePromptController extends Controller
{
    public function __construct(
        protected EmployeeProfileUpdatePromptService $prompts,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => ['string', 'in:'.implode(',', \App\Services\EmployeeProfileCompletenessService::requestableFieldKeys())],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $staff = $this->prompts->findStaffByEmail($validated['email']);

        if (! $staff) {
            return back()
                ->withInput()
                ->with('error', 'No staff record found for that email. Use registration invite if they are not yet in the directory.');
        }

        try {
            $this->prompts->send(
                $staff,
                $request->user(),
                $validated['fields'],
                $validated['notes'] ?? null,
                'ict',
            );
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Profile update request sent to '.$staff->fullName().'.');
    }
}
