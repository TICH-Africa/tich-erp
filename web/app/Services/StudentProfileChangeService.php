<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Student;
use App\Models\StudentProfileChangeRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class StudentProfileChangeService
{
    /** Fields students may update immediately. */
    public const SELF_SERVICE_FIELDS = [
        'phone_number',
        'email',
        'home_county',
        'nationality',
        'postal_address',
        'next_of_kin_name',
        'next_of_kin_relationship',
        'next_of_kin_phone',
        'next_of_kin_address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
    ];

    /** Fields that require Academic Registrar approval. */
    public const APPROVAL_REQUIRED_FIELDS = [
        'first_name',
        'middle_name',
        'surname',
        'date_of_birth',
        'national_id_number',
        'passport_number',
    ];

    public function __construct(
        protected AuditService $auditService,
        protected StoredFileService $files,
    ) {}

    /**
     * Apply self-service fields immediately; queue restricted fields for registrar approval.
     *
     * @param  array<string, mixed>  $input
     * @return array{applied: list<string>, queued: ?StudentProfileChangeRequest, photo_queued: ?StudentProfileChangeRequest}
     */
    public function submit(Student $student, User $user, array $input): array
    {
        $student->loadMissing('applicant');
        $applicant = $student->applicant;

        if (! $applicant) {
            throw new InvalidArgumentException('Student biodata record is missing.');
        }

        $applied = [];
        $selfUpdates = [];

        foreach (self::SELF_SERVICE_FIELDS as $field) {
            if (! array_key_exists($field, $input)) {
                continue;
            }

            $value = is_string($input[$field]) ? trim($input[$field]) : $input[$field];
            $value = $value === '' ? null : $value;

            if (in_array($field, ['emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship'], true)) {
                if ($student->{$field} != $value) {
                    $student->{$field} = $value;
                    $applied[] = $field;
                }
                continue;
            }

            if ($applicant->{$field} != $value) {
                $selfUpdates[$field] = $value;
                $applied[] = $field;
            }
        }

        if ($selfUpdates !== []) {
            $applicant->fill($selfUpdates);
            if (array_key_exists('email', $selfUpdates) && $selfUpdates['email']) {
                $user->email = $selfUpdates['email'];
                $user->save();
            }
        }

        $student->save();
        $applicant->save();

        $restrictedChanges = [];
        $restrictedSnapshot = [];
        foreach (self::APPROVAL_REQUIRED_FIELDS as $field) {
            if (! array_key_exists($field, $input)) {
                continue;
            }

            $value = is_string($input[$field]) ? trim($input[$field]) : $input[$field];
            $value = $value === '' ? null : $value;

            $current = $applicant->{$field};
            if ($current instanceof \Carbon\CarbonInterface) {
                $current = $current->format('Y-m-d');
            }

            if ((string) $current !== (string) $value) {
                $restrictedSnapshot[$field] = $current;
                $restrictedChanges[$field] = $value;
            }
        }

        $queued = null;
        if ($restrictedChanges !== []) {
            $queued = StudentProfileChangeRequest::query()->create([
                'student_id' => $student->id,
                'requested_by_user_id' => $user->id,
                'request_type' => StudentProfileChangeRequest::TYPE_PROFILE_UPDATE,
                'status' => StudentProfileChangeRequest::STATUS_PENDING,
                'current_snapshot' => $restrictedSnapshot,
                'proposed_changes' => $restrictedChanges,
                'student_notes' => $input['student_notes'] ?? null,
            ]);
        }

        $photoQueued = null;
        if (! empty($input['profile_photo']) && $input['profile_photo'] instanceof UploadedFile) {
            $path = $this->storePhoto($student, $input['profile_photo']);
            $photoQueued = StudentProfileChangeRequest::query()->create([
                'student_id' => $student->id,
                'requested_by_user_id' => $user->id,
                'request_type' => StudentProfileChangeRequest::TYPE_PHOTO,
                'status' => StudentProfileChangeRequest::STATUS_PENDING,
                'current_snapshot' => ['photo_path' => $student->photo_path],
                'proposed_changes' => ['photo_path' => $path],
                'attachment_path' => $path,
                'student_notes' => $input['student_notes'] ?? null,
            ]);
        }

        if ($applied === [] && ! $queued && ! $photoQueued) {
            throw new InvalidArgumentException('No changes were detected.');
        }

        $this->auditService->log(
            'portal.profile.update',
            'students',
            $student->id,
            null,
            [
                'applied' => $applied,
                'queued' => (bool) $queued,
                'photo_queued' => (bool) $photoQueued,
            ],
            'Student profile update submitted',
            'success',
            $user->id
        );

        return [
            'applied' => $applied,
            'queued' => $queued,
            'photo_queued' => $photoQueued,
        ];
    }

    public function approve(StudentProfileChangeRequest $request, User $reviewer, ?string $notes = null): StudentProfileChangeRequest
    {
        if ($request->status !== StudentProfileChangeRequest::STATUS_PENDING) {
            throw new InvalidArgumentException('Only pending requests can be approved.');
        }

        return DB::transaction(function () use ($request, $reviewer, $notes) {
            $student = Student::query()->with('applicant')->findOrFail($request->student_id);
            $changes = $request->proposed_changes ?? [];

            if ($request->request_type === StudentProfileChangeRequest::TYPE_PHOTO) {
                if (! empty($changes['photo_path'])) {
                    $student->photo_path = $changes['photo_path'];
                    $student->save();
                }
            } else {
                $applicant = $student->applicant;
                if ($applicant) {
                    foreach ($changes as $field => $value) {
                        if (in_array($field, self::APPROVAL_REQUIRED_FIELDS, true)) {
                            $applicant->{$field} = $value;
                        }
                    }
                    $applicant->save();
                }
            }

            $request->update([
                'status' => StudentProfileChangeRequest::STATUS_APPROVED,
                'reviewer_notes' => $notes,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            return $request->fresh();
        });
    }

    public function reject(StudentProfileChangeRequest $request, User $reviewer, string $reason, ?string $notes = null): StudentProfileChangeRequest
    {
        if ($request->status !== StudentProfileChangeRequest::STATUS_PENDING) {
            throw new InvalidArgumentException('Only pending requests can be rejected.');
        }

        $request->update([
            'status' => StudentProfileChangeRequest::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'reviewer_notes' => $notes,
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        return $request->fresh();
    }

    private function storePhoto(Student $student, UploadedFile $file): string
    {
        $directory = 'students/'.$student->id.'/photos';
        $path = $file->store($directory, 'public');

        return $path;
    }
}
