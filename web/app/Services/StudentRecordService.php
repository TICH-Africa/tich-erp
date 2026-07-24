<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class StudentRecordService
{
    /**
     * @return LengthAwarePaginator<int, Student>
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Student::query()
            ->with(['applicant', 'program.department', 'campus', 'user:id,username,email'])
            ->orderByDesc('created_at');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $scoped) use ($search) {
                $scoped->where('registration_number', 'like', "%{$search}%")
                    ->orWhereHas('applicant', function (Builder $applicantQuery) use ($search) {
                        $applicantQuery->where('application_number', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('surname', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['status'])) {
            $query->where('enrollment_status', $filters['status']);
        }

        if (! empty($filters['program_id'])) {
            $query->where('program_id', (int) $filters['program_id']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function findForHub(int $id): Student
    {
        return Student::query()
            ->with([
                'applicant.documents',
                'program.department',
                'campus',
                'user:id,username,email,last_login_at,mfa_enabled',
            ])
            ->findOrFail($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function biodata360(Student $student): array
    {
        $student->loadMissing(['applicant.documents', 'applicant.preferredCampus', 'program.department', 'campus', 'user']);
        $applicant = $student->applicant;

        return [
            'identity' => [
                'full_name' => $applicant?->fullName() ?? '—',
                'date_of_birth' => $applicant?->date_of_birth?->format('Y-m-d'),
                'gender' => $applicant?->gender,
                'national_id_number' => $applicant?->national_id_number,
                'passport_number' => $applicant?->passport_number,
            ],
            'contact' => [
                'email' => $applicant?->email,
                'phone_number' => $applicant?->phone_number,
                'home_county' => $applicant?->home_county,
            ],
            'academic' => [
                'registration_number' => $student->registration_number,
                'program' => $student->program?->program_name,
                'department' => $student->program?->department?->dept_name,
                'cohort_intake' => $student->cohort_intake,
                'entry_qualification' => $applicant?->entry_qualification,
                'entry_pathway' => $student->entry_pathway,
            ],
            'application' => [
                'application_number' => $applicant?->application_number,
                'status' => $applicant?->status,
                'academic_review_status' => $applicant?->academic_review_status,
                'submitted_at' => $applicant?->created_at?->format('Y-m-d H:i'),
                'reviewed_at' => $applicant?->reviewed_at?->format('Y-m-d H:i'),
                'review_notes' => $applicant?->review_notes,
                'rejection_reason' => $applicant?->rejection_reason,
                'intake_year' => $applicant?->intake_year,
                'intake_month' => $applicant?->intake_month,
                'preferred_campus' => $applicant?->preferredCampus?->campus_name,
                'entry_qualification' => $applicant?->entry_qualification,
                'sponsorship_type' => $applicant?->sponsorship_type,
            ],
            'next_of_kin' => [
                'name' => $applicant?->next_of_kin_name,
                'relationship' => $applicant?->next_of_kin_relationship,
                'phone' => $applicant?->next_of_kin_phone,
                'address' => $applicant?->next_of_kin_address,
            ],
            'emergency' => [
                'name' => $student->emergency_contact_name,
                'phone' => $student->emergency_contact_phone,
                'relationship' => $student->emergency_contact_relationship,
            ],
            'enrollment' => [
                'enrollment_status' => $student->enrollment_status,
                'campus' => $student->campus?->campus_name,
                'date_of_admission' => $student->date_of_admission?->format('Y-m-d'),
                'fee_clearance_status' => $student->fee_clearance_status,
                'overall_balance' => $student->overall_balance,
                'portal_activated_at' => $student->portal_activated_at?->format('Y-m-d H:i'),
            ],
            'portal' => [
                'has_account' => $student->user_id !== null,
                'username' => $student->user?->username,
                'last_login_at' => $student->user?->last_login_at?->format('Y-m-d H:i'),
                'invite_pending' => $student->hasActivePortalInvite(),
            ],
            'documents' => $applicant?->documents ?? collect(),
        ];
    }
}
