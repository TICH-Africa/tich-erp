<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Campus;
use App\Models\Student;
use App\Support\IntakeIdentity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentEnrollmentService
{
    public function __construct(protected AuditService $auditService) {}

    public function registerFromAcademicallyApprovedApplicant(Applicant $applicant, ?int $createdBy = null): Student
    {
        $existing = Student::query()->where('application_id', $applicant->id)->first();

        if ($existing) {
            return $existing;
        }

        $applicant->loadMissing(['program', 'preferredCampus']);

        return DB::transaction(function () use ($applicant, $createdBy) {
            Applicant::query()->whereKey($applicant->id)->lockForUpdate()->firstOrFail();

            $existing = Student::query()->where('application_id', $applicant->id)->first();
            if ($existing) {
                return $existing;
            }

            $student = Student::create([
                'registration_number' => $this->generateRegistrationNumber($applicant),
                'application_id' => $applicant->id,
                'program_id' => $applicant->program_id,
                'cohort_intake' => $this->cohortIntakeFromApplicant($applicant),
                'enrollment_campus_id' => $this->resolveCampusId($applicant),
                'enrollment_status' => 'pending',
                'entry_pathway' => $this->mapEntryPathway($applicant->entry_qualification),
                'date_of_admission' => now()->toDateString(),
                'fee_clearance_status' => 'pending',
                'overall_balance' => 0,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => $createdBy,
            ]);

            $this->auditService->log(
                'sis.student.registered_from_application',
                'students',
                $student->id,
                null,
                [
                    'application_id' => $applicant->id,
                    'application_number' => $applicant->application_number,
                    'registration_number' => $student->registration_number,
                ],
                'Student record opened after academic approval (finance visibility)',
                'success',
                $createdBy
            );

            return $student;
        }, 3);
    }

    public function enrollFromAdmittedApplicant(Applicant $applicant, ?int $createdBy = null): Student
    {
        $existing = Student::query()->where('application_id', $applicant->id)->first();

        if ($existing) {
            return $this->refreshPortalInvite($existing);
        }

        $applicant->loadMissing(['program', 'preferredCampus']);

        return DB::transaction(function () use ($applicant, $createdBy) {
            // Serialize concurrent enrollments for the same applicant (double-click / race).
            Applicant::query()->whereKey($applicant->id)->lockForUpdate()->firstOrFail();

            $existing = Student::query()->where('application_id', $applicant->id)->first();
            if ($existing) {
                return $this->refreshPortalInvite($existing);
            }

            $token = Str::random(48);
            $expiresAt = now()->addDays((int) config('tich-sis.portal_invite_days', 14));

            $student = Student::create([
                'registration_number' => $this->generateRegistrationNumber($applicant),
                'application_id' => $applicant->id,
                'program_id' => $applicant->program_id,
                'cohort_intake' => $this->cohortIntakeFromApplicant($applicant),
                'enrollment_campus_id' => $this->resolveCampusId($applicant),
                'enrollment_status' => 'pending',
                'entry_pathway' => $this->mapEntryPathway($applicant->entry_qualification),
                'date_of_admission' => now()->toDateString(),
                'fee_clearance_status' => 'pending',
                'overall_balance' => 0,
                'portal_invite_token' => $token,
                'portal_invite_expires_at' => $expiresAt,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => $createdBy,
            ]);

            $this->auditService->log(
                'sis.student.enrolled',
                'students',
                $student->id,
                null,
                [
                    'application_id' => $applicant->id,
                    'application_number' => $applicant->application_number,
                    'registration_number' => $student->registration_number,
                ],
                'Student record created from admitted application',
                'success',
                $createdBy
            );

            return $student;
        }, 3);
    }

    public function refreshPortalInvite(Student $student): Student
    {
        if ($student->portal_activated_at !== null || $student->user_id !== null) {
            return $student;
        }

        $student->update([
            'portal_invite_token' => Str::random(48),
            'portal_invite_expires_at' => now()->addDays((int) config('tich-sis.portal_invite_days', 14)),
            'updated_at' => now(),
        ]);

        return $student->fresh();
    }

    public function findByPortalToken(string $token): ?Student
    {
        return Student::query()
            ->with(['applicant', 'program.department', 'campus'])
            ->where('portal_invite_token', $token)
            ->first();
    }

    private function generateRegistrationNumber(Applicant $applicant): string
    {
        $year = date('Y');
        $month = strtoupper(date('M'));
        $sequence = Student::query()
            ->whereYear('date_of_admission', (int) $year)
            ->count() + 1;

        $campusCode = strtoupper((string) ($applicant->preferredCampus?->campus_code ?? 'THC'));
        $examBodyToken = substr(preg_replace('/[^A-Z0-9]/', '', strtoupper((string) ($applicant->program?->regulatory_body ?? 'N'))) ?: 'N', 0, 1);

        return sprintf(
            '%s/%s/%s%02d/%s',
            $campusCode,
            $examBodyToken,
            $month,
            $sequence,
            $year
        );
    }

    private function cohortIntakeFromApplicant(Applicant $applicant): string
    {
        if ($applicant->intake_year && $applicant->intake_month) {
            return IntakeIdentity::cohortLabel(
                (int) $applicant->intake_year,
                (int) $applicant->intake_month
            );
        }

        return $this->currentCohortIntake();
    }

    private function currentCohortIntake(): string
    {
        return date('Y').'-'.strtoupper(date('M'));
    }

    private function resolveCampusId(Applicant $applicant): int
    {
        if ($applicant->preferred_campus_id) {
            return (int) $applicant->preferred_campus_id;
        }

        $campusId = Campus::query()->where('is_active', 1)->orderBy('id')->value('id');

        if (! $campusId) {
            throw new \RuntimeException('No campus configured for student enrollment.');
        }

        return (int) $campusId;
    }

    private function mapEntryPathway(?string $qualification): string
    {
        return $qualification === 'rpl' ? 'rpl' : 'regular';
    }
}
