<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Employee\Concerns\ResolvesPortalStaff;
use App\Models\StaffNextOfKin;
use App\Services\EmployeeProfileChangeService;
use App\Services\EmployeeProfileCompletenessService;
use App\Services\EmployeeProfileUpdatePromptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use ResolvesPortalStaff;

    public function __construct(
        protected EmployeeProfileCompletenessService $completeness,
        protected EmployeeProfileChangeService $profileChanges,
        protected EmployeeProfileUpdatePromptService $profilePrompts,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $staff->loadMissing(['primaryNextOfKin', 'department', 'campus']);
        $prompt = $this->profilePrompts->findActiveForStaff($staff);

        return response()->json([
            'must_complete' => ! $this->completeness->isComplete($staff),
            'missing_fields' => $this->completeness->missingLabels($staff),
            'profile_update_prompt' => $prompt ? [
                'token' => $prompt->token,
                'fields' => $prompt->requested_fields,
                'labels' => $prompt->fieldLabels(),
                'notes' => $prompt->notes,
                'module' => $prompt->requested_via_module,
            ] : null,
            'profile' => [
                'first_name' => $staff->first_name,
                'middle_name' => $staff->middle_name,
                'surname' => $staff->surname,
                'full_name' => $staff->fullName(),
                'employee_number' => $staff->employee_number,
                'job_title' => $staff->job_title,
                'department' => $staff->department?->dept_name,
                'date_of_birth' => optional($staff->date_of_birth)?->format('Y-m-d'),
                'gender' => $staff->gender,
                'marital_status' => $staff->marital_status,
                'primary_email' => $staff->primary_email,
                'organisation_email' => $staff->organisation_email,
                'preferred_erp_email' => $staff->preferred_erp_email,
                'phone_number' => $staff->phone_number,
                'alt_phone_number' => $staff->alt_phone_number,
                'physical_address' => $staff->physical_address,
                'postal_address' => $staff->postal_address,
                'postal_code' => $staff->postal_code,
                'home_county' => $staff->home_county,
                'national_id_number' => $staff->national_id_number,
                'kra_pin' => $staff->kra_pin,
                'nssf_number' => $staff->nssf_number,
                'sha_number' => $staff->sha_number,
                'emergency_contact_name' => $staff->emergency_contact_name,
                'emergency_contact_phone' => $staff->emergency_contact_phone,
                'emergency_contact_relationship' => $staff->emergency_contact_relationship,
                'photo_url' => $staff->photoUrl(),
                'next_of_kin' => $staff->primaryNextOfKin ? [
                    'full_name' => $staff->primaryNextOfKin->full_name,
                    'relationship' => $staff->primaryNextOfKin->relationship,
                    'phone_number' => $staff->primaryNextOfKin->phone_number,
                    'alt_phone' => $staff->primaryNextOfKin->alt_phone,
                    'email' => $staff->primaryNextOfKin->email,
                    'occupation' => $staff->primaryNextOfKin->occupation,
                    'physical_address' => $staff->primaryNextOfKin->physical_address,
                ] : null,
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $validated = $request->validate([
            'primary_email' => 'nullable|email|max:255',
            'organisation_email' => 'nullable|email|max:255',
            'preferred_erp_email' => 'nullable|string|in:primary,organisation',
            'phone_number' => 'nullable|string|max:30',
            'alt_phone_number' => 'nullable|string|max:30',
            'physical_address' => 'nullable|string|max:500',
            'postal_address' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'home_county' => 'nullable|string|max:100',
            'marital_status' => 'nullable|string|max:50',
            'emergency_contact_name' => 'nullable|string|max:200',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'emergency_contact_relationship' => 'nullable|string|max:100',
            'national_id_number' => 'nullable|string|max:50',
            'kra_pin' => 'nullable|string|max:50',
            'nssf_number' => 'nullable|string|max:50',
            'sha_number' => 'nullable|string|max:50',
            'employee_notes' => 'nullable|string|max:2000',
            'nok_full_name' => 'nullable|string|max:200',
            'nok_relationship' => 'nullable|string|max:100',
            'nok_phone_number' => 'nullable|string|max:30',
            'nok_alt_phone' => 'nullable|string|max:30',
            'nok_email' => 'nullable|email|max:255',
            'nok_occupation' => 'nullable|string|max:200',
            'nok_physical_address' => 'nullable|string|max:500',
        ]);

        $this->profileChanges->applyEmailSettings($staff, $request->user(), $validated);
        $this->saveNextOfKin($staff, $validated);

        try {
            $created = $this->profileChanges->submitUpdates($staff, $request->user(), $validated);
            $this->profilePrompts->fulfillForStaff($staff);

            return response()->json([
                'message' => count($created) > 0
                    ? 'Submitted '.count($created).' change request(s) for HR review.'
                    : 'Profile updated.',
                'change_requests' => count($created),
            ]);
        } catch (\InvalidArgumentException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'no changes')) {
                return response()->json(['message' => 'No profile field changes to submit.']);
            }

            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function saveNextOfKin(\App\Models\Staff $staff, array $validated): void
    {
        $name = trim((string) ($validated['nok_full_name'] ?? ''));
        $relationship = trim((string) ($validated['nok_relationship'] ?? ''));
        $phone = trim((string) ($validated['nok_phone_number'] ?? ''));

        if ($name === '' || $relationship === '' || $phone === '') {
            return;
        }

        $payload = [
            'full_name' => $name,
            'relationship' => $relationship,
            'phone_number' => $phone,
            'alt_phone' => filled($validated['nok_alt_phone'] ?? null) ? trim((string) $validated['nok_alt_phone']) : null,
            'email' => filled($validated['nok_email'] ?? null) ? strtolower(trim((string) $validated['nok_email'])) : null,
            'occupation' => filled($validated['nok_occupation'] ?? null) ? trim((string) $validated['nok_occupation']) : null,
            'physical_address' => filled($validated['nok_physical_address'] ?? null) ? trim((string) $validated['nok_physical_address']) : null,
            'is_primary' => true,
        ];

        $existing = $staff->primaryNextOfKin;
        if ($existing) {
            $existing->update($payload);
        } else {
            StaffNextOfKin::query()->create(array_merge($payload, ['staff_id' => $staff->id]));
        }

        $this->profilePrompts->fulfillFieldsForStaff($staff, ['next_of_kin']);
    }
}
