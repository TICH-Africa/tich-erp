<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\StaffProfileChangeRequest;
use App\Services\EmployeePortalService;
use App\Services\EmployeeProfileChangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeProfileController extends Controller
{
    public function __construct(
        protected EmployeePortalService $employeePortal,
        protected EmployeeProfileChangeService $profileChanges,
    ) {}

    public function edit(Request $request): View
    {
        $staff = $this->staff($request);

        $pendingRequests = StaffProfileChangeRequest::query()
            ->where('staff_id', $staff->id)
            ->where('status', StaffProfileChangeRequest::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->get();

        return view('employee.profile.edit', [
            'portalTitle' => 'Update my profile',
            'staff' => $staff,
            'pendingRequests' => $pendingRequests,
            'editableFields' => EmployeeProfileChangeService::EDITABLE_FIELDS,
            'qualificationTypes' => [
                'certificate' => 'Certificate',
                'diploma' => 'Diploma',
                'degree' => 'Degree / Bachelors',
                'masters' => 'Masters',
                'phd' => 'PhD',
                'professional_cert' => 'Professional certification',
                'trade_test' => 'Trade test',
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $staff = $this->staff($request);

        $validated = $request->validate([
            'primary_email' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string|max:30',
            'alt_phone_number' => 'nullable|string|max:30',
            'marital_status' => 'nullable|string|in:Single,Married,Divorced,Widowed,Separated',
            'postal_address' => 'nullable|string|max:500',
            'postal_code' => 'nullable|string|max:20',
            'physical_address' => 'nullable|string|max:500',
            'home_county' => 'nullable|string|max:100',
            'emergency_contact_name' => 'nullable|string|max:200',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'emergency_contact_relationship' => 'nullable|string|max:100',
            'employee_notes' => 'nullable|string|max:2000',
            'cropped_photo' => 'nullable|string',
            'qualification_type' => 'nullable|string|in:certificate,diploma,degree,masters,phd,professional_cert,trade_test',
            'qualification_name' => 'nullable|required_with:qualification_type|string|max:300',
            'institution' => 'nullable|string|max:300',
            'country' => 'nullable|string|max:100',
            'year_completed' => 'nullable|integer|min:1950|max:'.(now()->year + 1),
            'grade_or_class' => 'nullable|string|max:50',
            'certificate_number' => 'nullable|string|max:50',
            'certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if (! empty($validated['qualification_type']) && empty($validated['qualification_name'])) {
            return back()->withInput()->withErrors(['qualification_name' => 'Qualification name is required when adding a certificate.']);
        }

        $validated['certificate_file'] = $request->file('certificate_file');

        try {
            $created = $this->profileChanges->submitUpdates($staff, $request->user(), $validated);
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['form' => $exception->getMessage()]);
        }

        $count = count($created);

        return redirect()
            ->route('employee.profile.edit')
            ->with('success', "Submitted {$count} change request(s) for HR review. Your current details remain active until approved.");
    }

    private function staff(Request $request): \App\Models\Staff
    {
        $staff = $request->attributes->get('portal_staff')
            ?? $this->employeePortal->staffForUser($request->user());

        abort_unless($staff, 403);

        return $staff;
    }
}
