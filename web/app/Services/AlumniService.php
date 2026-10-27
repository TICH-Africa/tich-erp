<?php

namespace App\Services;

use App\Models\AcademicRecord;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admissions owns the alumni register: a past-year graduate is the student record the school already
 * has, marked graduated with the year, date and award. Academics only reads the result.
 */
class AlumniService
{
    public const STATUSES = ['graduated', 'alumni'];

    public function __construct(
        protected AuditService $auditService,
        protected AcademicRecordService $records,
    ) {}

    public function isAlumnus(Student $student): bool
    {
        return in_array((string) $student->enrollment_status, self::STATUSES, true);
    }

    /**
     * The alumni register, filtered the same way for Admissions and for the Academics read-only view.
     *
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters, int $perPage = 25): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Student::query()
            ->alumni()
            ->with(['program:id,program_name,program_code'])
            ->when(($filters['search'] ?? '') !== '', function ($q) use ($filters) {
                $like = '%'.trim((string) $filters['search']).'%';
                $q->where(function ($inner) use ($like) {
                    $inner->where('registration_number', 'like', $like)
                        ->orWhere('first_name', 'like', $like)
                        ->orWhere('middle_name', 'like', $like)
                        ->orWhere('surname', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->when(($filters['graduation_year'] ?? '') !== '', fn ($q) => $q->where('graduation_year', (int) $filters['graduation_year']))
            ->when(($filters['program_id'] ?? '') !== '', fn ($q) => $q->where('program_id', (int) $filters['program_id']))
            ->orderByDesc('graduation_year')
            ->orderByDesc('graduation_date')
            ->orderBy('surname')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @return \Illuminate\Support\Collection<int, int> */
    public function graduationYears(): \Illuminate\Support\Collection
    {
        return Student::query()
            ->alumni()
            ->whereNotNull('graduation_year')
            ->distinct()
            ->orderByDesc('graduation_year')
            ->pluck('graduation_year')
            ->map(fn ($year) => (int) $year);
    }

    /**
     * @param  array{graduation_year?: int|null, graduation_date?: string|null, graduation_award?: string|null, gpa?: string|null, status?: string}  $data
     */
    public function register(Student $student, array $data, User $user, ?Request $request = null): Student
    {
        $status = $data['status'] ?? 'graduated';

        if (! in_array($status, self::STATUSES, true)) {
            $status = 'graduated';
        }

        return DB::transaction(function () use ($student, $data, $status, $user, $request): Student {
            $before = [
                'enrollment_status' => $student->enrollment_status,
                'graduation_year' => $student->graduation_year,
                'graduation_date' => $student->graduation_date?->toDateString(),
                'graduation_award' => $student->graduation_award,
            ];

            $student->update([
                'enrollment_status' => $status,
                'graduation_year' => $data['graduation_year'] ?? null,
                'graduation_date' => $data['graduation_date'] ?? null,
                'graduation_award' => ($data['graduation_award'] ?? null) !== null && $data['graduation_award'] !== ''
                    ? $data['graduation_award']
                    : null,
            ]);

            // academic_records.completion_date has been on the table since it was created and never
            // filled; a registered graduation is the first thing that can.
            $recordUpdate = [];

            if (! empty($data['graduation_date'])) {
                $recordUpdate['completion_date'] = $data['graduation_date'];
            }

            if (($data['gpa'] ?? null) !== null && $data['gpa'] !== '') {
                $recordUpdate['gpa'] = $data['gpa'];
            }

            if ($recordUpdate !== []) {
                AcademicRecord::query()
                    ->where('student_id', $student->id)
                    ->orderByDesc('id')
                    ->limit(1)
                    ->update($recordUpdate);
            }

            $this->auditService->log(
                'administration.alumni.registered',
                'students',
                (string) $student->id,
                $before,
                [
                    'enrollment_status' => $status,
                    'graduation_year' => $data['graduation_year'] ?? null,
                    'graduation_date' => $data['graduation_date'] ?? null,
                    'graduation_award' => $data['graduation_award'] ?? null,
                    'gpa' => $data['gpa'] ?? null,
                ],
                'Graduate registered by Admissions',
                'success',
                $user->id,
                $request
            );

            return $student->fresh();
        });
    }

    /**
     * A graduate the ERP never captured. The record is created already finished, so the school keeps
     * their course, campus and result and they stay off every current-student list.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user, ?Request $request = null): Student
    {
        $status = $data['status'] ?? 'graduated';

        if (! in_array($status, self::STATUSES, true)) {
            $status = 'graduated';
        }

        $joined = (int) $data['year_joined'];
        $ended = (int) $data['graduation_year'];
        $admittedOn = sprintf('%04d-01-01', $joined);
        $finalYear = (int) ($data['current_year'] ?? max(1, min(4, $ended - $joined + 1)));
        $gpa = ($data['gpa'] ?? null) === null || $data['gpa'] === '' ? null : $data['gpa'];

        return DB::transaction(function () use ($data, $user, $request, $status, $joined, $ended, $admittedOn, $finalYear, $gpa): Student {
            $student = Student::create([
                'registration_number' => $data['registration_number'],
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'surname' => $data['surname'],
                'program_id' => $data['program_id'],
                'cohort_intake' => (string) $joined,
                'current_year' => $finalYear,
                'enrollment_campus_id' => $data['campus_id'],
                'enrollment_status' => $status,
                'date_of_admission' => $admittedOn,
                'email' => $data['email'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
                'graduation_year' => $ended,
                'graduation_date' => $data['graduation_date'] ?? null,
                'graduation_award' => ($data['graduation_award'] ?? '') !== '' ? $data['graduation_award'] : null,
                // They are not on campus, so every current-student roster keyed on is_active skips them.
                'is_active' => 0,
                'created_by' => $user->id,
            ]);

            $this->records->create($student, [
                'program_id' => $data['program_id'],
                'enrollment_date' => $admittedOn,
                'completion_date' => $data['graduation_date'] ?? sprintf('%04d-12-31', $ended),
                'status' => 'completed',
                'gpa' => $gpa,
                'notes' => 'Added to the alumni register by Admissions; the school kept no ERP record for this cohort.',
            ]);

            $this->auditService->log(
                'administration.alumni.created',
                'students',
                (string) $student->id,
                null,
                [
                    'registration_number' => $student->registration_number,
                    'enrollment_status' => $status,
                    'program_id' => $student->program_id,
                    'campus_id' => $student->enrollment_campus_id,
                    'year_joined' => $joined,
                    'graduation_year' => $ended,
                    'graduation_date' => $student->graduation_date?->toDateString(),
                    'graduation_award' => $student->graduation_award,
                    'gpa' => $gpa,
                ],
                'Graduate added with no prior student record',
                'success',
                $user->id,
                $request
            );

            return $student->fresh();
        });
    }

    /**
     * A mis-clicked graduation has to be liftable, or the person stays off every current-student
     * list with no way back short of SQL.
     */
    public function undo(Student $student, User $user, ?Request $request = null): Student
    {
        return DB::transaction(function () use ($student, $user, $request): Student {
            $before = [
                'enrollment_status' => $student->enrollment_status,
                'graduation_year' => $student->graduation_year,
                'graduation_date' => $student->graduation_date?->toDateString(),
                'graduation_award' => $student->graduation_award,
            ];

            $student->update([
                'enrollment_status' => 'active',
                'graduation_year' => null,
                'graduation_date' => null,
                'graduation_award' => null,
                // Back on the current student list means visible to it, including for a record that
                // was created already graduated because the school had no ERP entry for them.
                'is_active' => 1,
            ]);

            $records = AcademicRecord::query()->where('student_id', $student->id)->orderByDesc('id')->get();
            $newest = $records->first();

            // register() stamps the newest record, so lifting a graduation clears that same one.
            if ($newest !== null) {
                $newest->completion_date = null;

                // Only a record Admissions created along with the graduation goes back to being a live
                // enrolment; a real study history keeps its own status and result.
                if ($records->count() === 1 && $newest->status === 'completed') {
                    $newest->status = 'active';
                    $newest->gpa = null;
                }

                $newest->save();
            }

            $this->auditService->log(
                'administration.alumni.undone',
                'students',
                (string) $student->id,
                $before,
                ['enrollment_status' => 'active'],
                'Graduation removed by Admissions',
                'success',
                $user->id,
                $request
            );

            return $student->fresh();
        });
    }

    /**
     * @param  list<array<string, mixed>>  $rows  rows the import marked as 'mark'
     * @return array{marked: int, skipped: int, problems: list<string>}
     */
    public function applyImport(array $rows, User $user, ?Request $request = null): array
    {
        $marked = 0;
        $skipped = 0;
        $problems = [];

        DB::transaction(function () use ($rows, $user, $request, &$marked, &$skipped, &$problems) {
            foreach ($rows as $row) {
                if (($row['status'] ?? null) !== 'mark' || empty($row['student_id'])) {
                    $skipped++;
                    continue;
                }

                $student = Student::query()->find((int) $row['student_id']);

                if (! $student || $this->isAlumnus($student)) {
                    $skipped++;
                    $problems[] = 'Row '.$row['line'].': '.$row['name'].' is no longer a current student.';
                    continue;
                }

                $this->register($student, [
                    'graduation_year' => $row['graduation_year'],
                    'graduation_date' => $row['graduation_date'],
                    'graduation_award' => $row['graduation_award'],
                ], $user, $request);

                $marked++;
            }
        });

        return ['marked' => $marked, 'skipped' => $skipped, 'problems' => $problems];
    }
}
