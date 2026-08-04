<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffOnboarding;
use App\Models\User;
use App\Services\StaffLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\OnboardingInvitationEmail;
use App\Mail\OnboardingRejectedEmail;
use Illuminate\Support\Facades\Log;

class EssOnboardingController extends Controller
{
    public function __construct(
        protected StaffLifecycleService $lifecycleService,
    ) {}

    public function show(string $token): View|RedirectResponse
    {
        $staff = Staff::where('onboarding_token', $token)->first();

        if (! $staff) {
            abort(404, 'Onboarding link is invalid or has already been used.');
        }

        if ($staff->onboarding_completed_at && $staff->onboarding->status === 'completed') {
            return redirect()
                ->route('login')
                ->with('status', 'Your onboarding is already complete. Sign in with your username and password.');
        }

        if ($staff->onboarding_token_expires_at?->isPast()) {
            abort(410, 'This onboarding link has expired. Contact HR for assistance.');
        }

        $suggestedUsername = $this->suggestUsername($staff->primary_email ?? $staff->organisation_email ?? '');

        return view('ess.onboarding.activate', [
            'staff' => $staff,
            'suggestedUsername' => $suggestedUsername,
        ]);
    }

    public function saveDraft(Request $request, string $token)
    {
        $staff = Staff::where('onboarding_token', $token)->firstOrFail();

        if ($staff->onboarding_token_expires_at?->isPast()) {
            abort(410, 'This onboarding link has expired.');
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:150'],
            'surname' => ['required', 'string', 'max:150'],
            'middle_name' => ['nullable', 'string', 'max:150'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', 'string', 'in:Male,Female,Other'],
            'marital_status' => ['nullable', 'string', 'in:Single,Married,Divorced,Widowed,Separated'],
            'national_id_number' => ['nullable', 'string', 'max:50'],
            'phone_number' => ['required', 'string', 'max:30'],
            'postal_address' => ['nullable', 'string', 'max:300'],
            'physical_address' => ['nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['required', 'string', 'max:200'],
            'emergency_contact_phone' => ['required', 'string', 'max:30'],
            'emergency_contact_relationship' => ['required', 'string', 'max:100'],
            'kra_pin' => ['nullable', 'string', 'max:20'],
            'nssf_number' => ['nullable', 'string', 'max:50'],
            'sha_number' => ['nullable', 'string', 'max:50'],
            'helb_number' => ['nullable', 'string', 'max:50'],
        ]);

        $staff->update($validated);

        return back()->with('success', 'Progress saved successfully.');
    }

    public function store(Request $request, string $token)
    {
        $staff = Staff::where('onboarding_token', $token)->firstOrFail();

        if ($staff->onboarding_token_expires_at?->isPast()) {
            abort(410, 'This onboarding link has expired. Contact HR for assistance.');
        }

        $existingUserId = User::query()
            ->where('email', $staff->primary_email)
            ->value('id');

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($existingUserId)],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
            'first_name' => ['required', 'string', 'max:150'],
            'surname' => ['required', 'string', 'max:150'],
            'middle_name' => ['nullable', 'string', 'max:150'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', 'string', 'in:Male,Female,Other'],
            'marital_status' => ['nullable', 'string', 'in:Single,Married,Divorced,Widowed,Separated'],
            'national_id_number' => ['nullable', 'string', 'max:50'],
            'phone_number' => ['required', 'string', 'max:30'],
            'postal_address' => ['nullable', 'string', 'max:300'],
            'physical_address' => ['nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['required', 'string', 'max:200'],
            'emergency_contact_phone' => ['required', 'string', 'max:30'],
            'emergency_contact_relationship' => ['required', 'string', 'max:100'],
            'kra_pin' => ['nullable', 'string', 'max:20'],
            'nssf_number' => ['nullable', 'string', 'max:50'],
            'sha_number' => ['nullable', 'string', 'max:50'],
            'helb_number' => ['nullable', 'string', 'max:50'],
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($staff, $validated, $existingUserId) {
            $user = User::updateOrCreate(
                ['id' => $existingUserId],
                [
                    'username' => $validated['username'],
                    'email' => $staff->primary_email,
                    'password_hash' => Hash::make($validated['password']),
                    'user_type' => 'staff',
                    'staff_id' => $staff->id,
                    'is_active' => 1,
                    'mfa_enabled' => 0,
                    'mfa_verified' => 1,
                ]
            );

            $staff->update([
                'user_id' => $user->id,
                'first_name' => $validated['first_name'],
                'surname' => $validated['surname'],
                'middle_name' => $validated['middle_name'],
                'date_of_birth' => $validated['date_of_birth'],
                'gender' => $validated['gender'],
                'marital_status' => $validated['marital_status'],
                'national_id_number' => $validated['national_id_number'],
                'phone_number' => $validated['phone_number'],
                'postal_address' => $validated['postal_address'],
                'physical_address' => $validated['physical_address'],
                'emergency_contact_name' => $validated['emergency_contact_name'],
                'emergency_contact_phone' => $validated['emergency_contact_phone'],
                'emergency_contact_relationship' => $validated['emergency_contact_relationship'],
                'kra_pin' => $validated['kra_pin'],
                'nssf_number' => $validated['nssf_number'],
                'sha_number' => $validated['sha_number'],
                'helb_number' => $validated['helb_number'],
            ]);

            $this->lifecycleService->updateOnboardingStep($staff->id, 'biodata');

            if ($staff->onboarding) {
                $staff->onboarding()->update([
                    'status' => 'pending_hr_review',
                    'completed_steps' => array_values(array_unique(array_merge($staff->onboarding->completed_steps ?? [], ['biodata']))),
                ]);
            }
        });

        return redirect()
            ->route('login')
            ->with('status', 'Your onboarding has been submitted for review. HR will contact you once approved.');
    }

    public function review(int $onboarding): View
    {
        $onboarding = StaffOnboarding::with(['staff', 'staff.department', 'staff.documents'])->findOrFail($onboarding);

        return view('hr.onboarding.review', ['onboarding' => $onboarding]);
    }

    public function approve(Request $request, int $onboarding)
    {
        $onboarding = StaffOnboarding::with('staff')->findOrFail($onboarding);

        if ($onboarding->status === 'approved') {
            return redirect()->route('hr.onboarding.show', $onboarding)->with('success', 'Onboarding already approved.');
        }

        DB::transaction(function () use ($onboarding, $request) {
            $onboarding->update([
                'status' => 'approved',
                'is_biodata_locked' => true,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            $onboarding->staff->update([
                'is_profile_locked' => true,
            ]);

            $this->lifecycleService->recordStatusChange(
                $onboarding->staff_id,
                'biodata_approved',
                'Onboarding biodata approved by HR',
                $request->user()->id
            );
        });

        return redirect()->route('hr.staff.show', $onboarding->staff_id)->with('success', 'Onboarding biodata approved successfully.');
    }

    public function reject(Request $request, int $onboarding)
    {
        $onboarding = StaffOnboarding::with('staff')->findOrFail($onboarding);

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:2000',
        ]);

        DB::transaction(function () use ($onboarding, $request, $validated) {
            $onboarding->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            $onboarding->staff->update([
                'is_profile_locked' => false,
                'onboarding_token' => Str::random(64),
                'onboarding_token_expires_at' => now()->addDays(14),
                'onboarding_completed_at' => null,
            ]);

            $this->lifecycleService->recordStatusChange(
                $onboarding->staff_id,
                'biodata_rejected',
                'Onboarding biodata rejected: ' . $validated['rejection_reason'],
                $request->user()->id
            );

            try {
                Mail::to($onboarding->staff->primary_email)->send(new OnboardingRejectedEmail($onboarding->staff, $validated['rejection_reason']));
            } catch (\Throwable $e) {
                Log::error('Failed to send onboarding rejection email: ' . $e->getMessage());
            }
        });

        return redirect()->route('hr.staff.show', $onboarding->staff_id)->with('success', 'Onboarding rejected. Staff member has been notified and can resubmit.');
    }

    private function suggestUsername(string $email): string
    {
        $local = strtolower(strtok($email, '@') ?: 'staff');
        $local = preg_replace('/[^a-z0-9._-]/', '', $local) ?: 'staff';

        return substr($local, 0, 50);
    }
}
