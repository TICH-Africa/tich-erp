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
     * @param  array{graduation_year?: int|null, graduation_date?: string|null, graduation_award?: string|null, status?: string}  $data
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
            if (! empty($data['graduation_date'])) {
                AcademicRecord::query()
                    ->where('student_id', $student->id)
                    ->orderByDesc('id')
                    ->limit(1)
                    ->update(['completion_date' => $data['graduation_date']]);
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
            ]);

            AcademicRecord::query()
                ->where('student_id', $student->id)
                ->where('completion_date', $before['graduation_date'])
                ->update(['completion_date' => null]);

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
