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

    public function enrollFromAdmittedApplicant(Applicant $applicant, ?int $createdBy = null): Student
    {
        $existing = Student::query()->where('application_id', $applicant->id)->first();

        if ($existing) {
            return $this->refreshPortalInvite($existing);
        }

        $applicant->loadMissing(['program', 'preferredCampus']);

        return DB::transaction(function () use ($applicant, $createdBy) {
            $token = Str::random(48);
            $expiresAt = now()->addDays((int) config('tich-sis.portal_invite_days', 14));

            $student = Student::create([
                'registration_number' => $this->generateRegistrationNumber(),
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
        });
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

    private function generateRegistrationNumber(): string
    {
        $year = date('Y');
        $latest = Student::query()
            ->where('registration_number', 'like', "REG-{$year}-%")
            ->orderByDesc('id')
            ->value('registration_number');

        $sequence = 1;

        if ($latest && preg_match('/REG-\d{4}-(\d+)/', $latest, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return sprintf('REG-%s-%05d', $year, $sequence);
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
