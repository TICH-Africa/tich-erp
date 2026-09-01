<?php

namespace App\Http\Controllers\HR;

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
                'hr',
            );
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('hr.staff.show', $staff)
            ->with('success', 'Profile update request sent to '.$staff->fullName().'.');
    }

    public function create(Staff $staff): \Illuminate\View\View
    {
        $staff->load(['qualifications', 'department']);

        return view('hr.staff.profile-update-prompt', [
            'staff' => $staff,
            'currentValues' => $this->currentFieldValues($staff),
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function currentFieldValues(Staff $staff): array
    {
        $values = [];

        foreach (\App\Services\EmployeeProfileCompletenessService::requestableFieldKeys() as $key) {
            $values[$key] = match ($key) {
                'photo' => $staff->photoUrl() ? 'Photo on file' : 'No photo uploaded',
                'date_of_birth' => $staff->date_of_birth?->format('d M Y') ?? '—',
                'qualification' => $staff->qualifications->isNotEmpty()
                    ? $staff->qualifications->map(fn ($q) => trim(($q->qualification_name ?? 'Qualification').($q->institution ? ' · '.$q->institution : '')))->join('; ')
                    : 'No qualifications on file',
                default => filled($staff->{$key}) ? (string) $staff->{$key} : '—',
            };
        }

        return $values;
    }

    public function storeByEmail(Request $request): RedirectResponse
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
                ->with('error', 'No staff record found for that email.');
        }

        try {
            $this->prompts->send(
                $staff,
                $request->user(),
                $validated['fields'],
                $validated['notes'] ?? null,
                'hr',
            );
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Profile update request sent to '.$staff->fullName().'.');
    }
}
