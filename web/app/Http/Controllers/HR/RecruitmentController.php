<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\JobVacancy;
use App\Models\RecruitmentApplication;
use App\Models\Staff;
use App\Models\StaffOnboarding;
use App\Models\User;
use App\Services\StaffLifecycleService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Support\ModuleMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Mail\OnboardingInvitationEmail;

class RecruitmentController extends Controller
{
    public function __construct(
        protected StaffLifecycleService $staffLifecycle,
    ) {}

    public function index(Request $request): View
    {
        $query = RecruitmentApplication::query()
            ->with(['vacancy', 'vacancy.department'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->decision, fn ($q, $decision) => $q->where('decision', $decision))
            ->when($request->vacancy_id, fn ($q, $id) => $q->where('vacancy_id', $id))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('application_number', 'like', "%{$search}%")
                        ->orWhere('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at');

        $perPage = (int) ($request->per_page ?? 25);
        $applications = $query->paginate($perPage)->appends($request->query());

        $vacancies = JobVacancy::orderBy('job_title')->get(['id', 'job_title']);

        return view('hr.recruitment.index', [
            'applications' => $applications,
            'vacancies' => $vacancies,
        ]);
    }

    public function show(int $id): View
    {
        $application = RecruitmentApplication::with(['vacancy', 'vacancy.department'])->findOrFail($id);

        return view('hr.recruitment.show', ['application' => $application]);
    }

    public function update(Request $request, int $id)
    {
        $application = RecruitmentApplication::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:submitted,under_review,shortlisted,rejected,offered',
            'decision' => 'nullable|string|in:approved,rejected,shortlisted,pending',
            'decision_notes' => 'nullable|string|max:2000',
            'interview_date' => 'nullable|date',
            'interview_panel_ids' => 'nullable|array',
            'interview_score' => 'nullable|numeric|min:0|max:100',
            'interview_notes' => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($application, $validated, $request) {
            $updateData = [
                'status' => $validated['status'],
                'decision' => $validated['decision'] ?? $application->decision,
                'decision_notes' => $validated['decision_notes'] ?? $application->decision_notes,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'is_viewed' => 1,
            ];

            if (isset($validated['interview_date'])) {
                $updateData['interview_date'] = $validated['interview_date'];
                $updateData['interview_panel_ids'] = $validated['interview_panel_ids'] ?? null;
                $updateData['interview_score'] = $validated['interview_score'] ?? null;
                $updateData['interview_notes'] = $validated['interview_notes'] ?? null;
            }

            $application->update($updateData);
        });

        return redirect()->route('hr.recruitment.show', $application)->with('success', 'Application updated successfully.');
    }

    public function shortlist(Request $request, int $id)
    {
        $application = RecruitmentApplication::findOrFail($id);

        $application->update([
            'status' => 'shortlisted',
            'decision' => 'shortlisted',
            'shortlist_status' => 'shortlisted',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'is_viewed' => 1,
        ]);

        return redirect()->route('hr.recruitment.show', $application)->with('success', 'Candidate shortlisted successfully.');
    }

    public function reject(Request $request, int $id)
    {
        $application = RecruitmentApplication::findOrFail($id);

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:2000',
        ]);

        $application->update([
            'status' => 'rejected',
            'decision' => 'rejected',
            'shortlist_status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'is_viewed' => 1,
        ]);

        return redirect()->route('hr.recruitment.show', $application)->with('success', 'Application rejected.');
    }

    public function approve(Request $request, int $id)
    {
        $application = RecruitmentApplication::with('vacancy')->findOrFail($id);

        if ($application->new_staff_id) {
            return redirect()->route('hr.recruitment.show', $application)->with('success', 'Offer already approved. Applicant converted to staff.');
        }

        DB::transaction(function () use ($application, $request) {
            $employeeNumber = $this->staffLifecycle->generateEmployeeNumber();

            $nameParts = $this->splitFullName($application->full_name);

            $token = Str::random(64);
            $expiresAt = now()->addDays(14);

            $staff = Staff::create([
                'employee_number' => $employeeNumber,
                'title' => '',
                'first_name' => $nameParts['first'],
                'middle_name' => $nameParts['middle'],
                'surname' => $nameParts['last'],
                'date_of_birth' => $application->date_of_birth ?? now()->toDateString(),
                'gender' => $application->gender ?? '',
                'marital_status' => $application->marital_status,
                'national_id_number' => $application->id_number,
                'nationality' => 'Kenyan',
                'primary_email' => $application->email,
                'organisation_email' => $this->generateOrganisationEmail($nameParts['first'], $nameParts['last']),
                'phone_number' => $application->phone_number,
                'postal_address' => $application->postal_address,
                'physical_address' => $application->physical_address,
                'department_id' => $application->vacancy->department_id,
                'job_title' => $application->vacancy->job_title,
                'employment_category' => $application->vacancy->employment_type,
                'employment_start_date' => now()->toDateString(),
                'gross_monthly_salary' => 0,
                'employment_status' => 'onboarding',
                'is_profile_locked' => 0,
                'onboarding_token' => $token,
                'onboarding_token_expires_at' => $expiresAt,
                'created_by' => $request->user()->id,
            ]);

            $user = User::query()->updateOrCreate(
                ['email' => $application->email],
                [
                    'password_hash' => Hash::make(Str::random(16)),
                    'user_type' => 'staff',
                    'staff_id' => $staff->id,
                    'is_active' => 1,
                    'mfa_enabled' => 0,
                    'mfa_verified' => 1,
                ]
            );

            $staff->update(['user_id' => $user->id]);

            StaffOnboarding::create([
                'staff_id' => $staff->id,
                'applicant_id' => $application->id,
                'onboarding_number' => 'ONB-' . strtoupper(Str::random(8)),
                'current_step' => 'biodata',
                'status' => 'pending_hr_review',
                'completed_steps' => ['biodata'],
            ]);

            $application->update([
                'status' => 'offered',
                'decision' => 'approved',
                'offer_made' => 1,
                'new_staff_id' => $staff->id,
                'is_onboarded' => 0,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'is_viewed' => 1,
            ]);

            try {
                ModuleMail::send(ModuleMail::HR, $staff->primary_email, new OnboardingInvitationEmail($staff));
            } catch (\Throwable $e) {
                \Log::error('Failed to send onboarding invitation: ' . $e->getMessage());
            }
        });

        return redirect()->route('hr.recruitment.show', $application)->with('success', 'Offer approved. Applicant converted to staff and onboarding invitation sent.');
    }

    private function splitFullName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), -1, PREG_SPLIT_NO_EMPTY);

        if ($parts === []) {
            return ['first' => '', 'middle' => '', 'last' => ''];
        }

        if (count($parts) === 1) {
            return ['first' => $parts[0], 'middle' => '', 'last' => $parts[0]];
        }

        if (count($parts) === 2) {
            return ['first' => $parts[0], 'middle' => '', 'last' => $parts[1]];
        }

        $first = array_shift($parts);
        $last = array_pop($parts);

        return [
            'first' => $first,
            'middle' => implode(' ', $parts),
            'last' => $last,
        ];
    }

    private function generateOrganisationEmail(string $firstName, string $surname): string
    {
        $first = strtolower(preg_replace('/[^a-z0-9]/', '', $firstName));
        $last = strtolower(preg_replace('/[^a-z0-9]/', '', $surname));

        return substr($first, 0, 1) . $last . '@tich.africa';
    }

    public function sendQualifiedEmail(Request $request, int $id)
    {
        $application = RecruitmentApplication::with('vacancy')->findOrFail($id);

        try {
            ModuleMail::send(ModuleMail::HR, $application->email, new \App\Mail\VacancyQualifiedEmail($application, $application->vacancy));
        } catch (\Exception $e) {
            return redirect()->route('hr.recruitment.show', $application)->with('error', 'Failed to send email: ' . $e->getMessage());
        }

        return redirect()->route('hr.recruitment.show', $application)->with('success', 'Qualification email sent to applicant successfully.');
    }
}
