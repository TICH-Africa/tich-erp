<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\StaffProfileChangeRequest;
use App\Services\AuthService;
use App\Services\EmployeePortalService;
use App\Services\EmployeeProfileCompletenessService;
use App\Services\EmployeeProfileChangeService;
use App\Services\EmployeeProfileUpdatePromptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeProfileController extends Controller
{
    public function __construct(
        protected EmployeePortalService $employeePortal,
        protected EmployeeProfileChangeService $profileChanges,
        protected EmployeeProfileCompletenessService $completeness,
        protected EmployeeProfileUpdatePromptService $profilePrompts,
        protected AuthService $authService,
    ) {}

    public function edit(Request $request): View
    {
        $staff = $this->staff($request);
        $mustComplete = ! $this->completeness->isComplete($staff);

        $pendingRequests = StaffProfileChangeRequest::query()
            ->where('staff_id', $staff->id)
            ->where('status', StaffProfileChangeRequest::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->get();

        $profileUpdatePrompt = $this->profilePrompts->findActiveForStaff($staff, $request->string('prompt')->toString() ?: null);
        $highlightFields = $profileUpdatePrompt?->requested_fields ?? [];

        return view('employee.profile.edit', [
            'portalTitle' => $mustComplete ? 'Complete your profile' : 'Update my profile',
            'staff' => $staff,
            'pendingRequests' => $pendingRequests,
            'mustCompleteProfile' => $mustComplete,
            'missingProfileLabels' => $this->completeness->missingLabels($staff),
            'requiredProfileFields' => EmployeeProfileCompletenessService::REQUIRED_FIELDS,
            'editableFields' => EmployeeProfileChangeService::EDITABLE_FIELDS,
            'profileUpdatePrompt' => $profileUpdatePrompt,
            'highlightFields' => $highlightFields,
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
        $mustComplete = ! $this->completeness->isComplete($staff);

        $rules = [
            'first_name' => ($mustComplete ? 'required' : 'nullable').'|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'surname' => ($mustComplete ? 'required' : 'nullable').'|string|max:100',
            'date_of_birth' => ($mustComplete ? 'required' : 'nullable').'|date|before:today',
            'gender' => ($mustComplete ? 'required' : 'nullable').'|string|in:Male,Female',
            'primary_email' => ($mustComplete ? 'required' : 'nullable').'|email|max:255',
            'phone_number' => ($mustComplete ? 'required' : 'nullable').'|string|max:30',
            'alt_phone_number' => 'nullable|string|max:30',
            'marital_status' => ($mustComplete ? 'required' : 'nullable').'|string|in:Single,Married,Divorced,Widowed,Separated',
            'postal_address' => 'nullable|string|max:500',
            'postal_code' => 'nullable|string|max:20',
            'physical_address' => ($mustComplete ? 'required' : 'nullable').'|string|max:500',
            'home_county' => 'nullable|string|max:100',
            'emergency_contact_name' => ($mustComplete ? 'required' : 'nullable').'|string|max:200',
            'emergency_contact_phone' => ($mustComplete ? 'required' : 'nullable').'|string|max:30',
            'emergency_contact_relationship' => ($mustComplete ? 'required' : 'nullable').'|string|max:100',
            'employee_notes' => 'nullable|string|max:2000',
            'profile_photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:6144',
            'cropped_photo' => 'nullable|string',
            'qualification_type' => 'nullable|string|in:certificate,diploma,degree,masters,phd,professional_cert,trade_test',
            'qualification_name' => 'nullable|required_with:qualification_type|string|max:300',
            'institution' => 'nullable|string|max:300',
            'country' => 'nullable|string|max:100',
            'year_completed' => 'nullable|integer|min:1950|max:'.(now()->year + 1),
            'grade_or_class' => 'nullable|string|max:50',
            'certificate_number' => 'nullable|string|max:50',
            'certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];

        $validated = $request->validate($rules);

        if (! empty($validated['qualification_type']) && empty($validated['qualification_name'])) {
            return back()->withInput()->withErrors(['qualification_name' => 'Qualification name is required when adding a certificate.']);
        }

        $validated['certificate_file'] = $request->file('certificate_file');
        $validated['profile_photo'] = $request->file('profile_photo');

        if ($request->hasFile('profile_photo') || ! empty($validated['cropped_photo'])) {
            $photoPath = $this->profileChanges->resolvePhotoPathFromInput($staff, $validated);
            if ($photoPath) {
                $staff->update(['photo_path' => $photoPath]);
                app(\App\Services\StaffLifecycleService::class)->ensureEmployeeIdentity($staff->fresh(), $request->user());
            }
        }

        try {
            if ($mustComplete) {
                $staff = $this->profileChanges->applySelfServiceCompletion($staff, $request->user(), $validated);
                $this->profilePrompts->fulfillForStaff($staff);

                if (! $this->completeness->isComplete($staff)) {
                    return back()
                        ->withInput()
                        ->withErrors([
                            'form' => 'Still missing: '.implode(', ', $this->completeness->missingLabels($staff)).'.',
                        ]);
                }

                // Optional qualification still goes through HR after the gate opens.
                if (! empty($validated['qualification_type']) && ! empty($validated['qualification_name'])) {
                    $this->profileChanges->submitUpdates($staff, $request->user(), [
                        'qualification_type' => $validated['qualification_type'],
                        'qualification_name' => $validated['qualification_name'],
                        'institution' => $validated['institution'] ?? null,
                        'country' => $validated['country'] ?? null,
                        'year_completed' => $validated['year_completed'] ?? null,
                        'grade_or_class' => $validated['grade_or_class'] ?? null,
                        'certificate_number' => $validated['certificate_number'] ?? null,
                        'certificate_file' => $validated['certificate_file'],
                        'employee_notes' => $validated['employee_notes'] ?? null,
                    ]);
                }

                return redirect()
                    ->intended($this->authService->authenticatedHome($request->user()))
                    ->with('success', $this->mustCompleteSuccessMessage($validated));
            }

            $created = $this->profileChanges->submitUpdates($staff, $request->user(), $validated);
            $this->profilePrompts->fulfillForStaff($staff);
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['form' => $exception->getMessage()]);
        }

        $count = count($created);
        $hasPhotoRequest = collect($created)->contains(
            fn (StaffProfileChangeRequest $request) => $request->request_type === StaffProfileChangeRequest::TYPE_PHOTO
        );

        $message = "Submitted {$count} change request(s) for HR review. Your current details remain active until approved.";
        if ($hasPhotoRequest) {
            $message .= ' Your new profile photo will appear after HR approves the photo update.';
        }

        return redirect()
            ->route('employee.profile.edit')
            ->with('success', $message);
    }

    private function staff(Request $request): \App\Models\Staff
    {
        $staff = $request->attributes->get('portal_staff')
            ?? $this->employeePortal->staffForUser($request->user());

        abort_unless($staff, 403);

        return $staff;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function mustCompleteSuccessMessage(array $validated): string
    {
        $message = 'Profile saved. You can now use the ERP. Later changes will be reviewed by HR.';

        if ($validated['profile_photo'] ?? null) {
            $message .= ' Your profile photo has been saved.';
        }

        return $message;
    }
}
