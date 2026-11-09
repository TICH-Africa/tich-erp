<?php

namespace App\Services;

use App\Models\Semester;
use App\Models\Student;
use Illuminate\Database\QueryException;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentExamCardService
{
    public function __construct(
        protected PrintDocumentService $printDocuments,
    ) {}

    /**
     * @return array{
     *     semester_id: ?int,
     *     semester_label: ?string,
     *     upcoming_exams: Collection<int, object>,
     *     exam_card: ?object,
     *     can_view_exam_card: bool
     * }
     */
    public function portalData(Student $student): array
    {
        $student->loadMissing(['applicant', 'program', 'campus']);

        $semester = $this->resolveSemester($student);
        $semesterId = $semester?->id;

        $upcomingExams = $semesterId
            ? $this->upcomingExams($student, (int) $semesterId)
            : collect();

        $examCard = $semesterId
            ? $this->findExamCard($student->id, (int) $semesterId)
            : null;

        $canView = $semesterId && (
            $examCard !== null
            || $upcomingExams->contains(fn ($exam) => $exam->eligible_for_exams)
        );

        return [
            'semester_id' => $semesterId,
            'semester_label' => $semester?->semester_label,
            'upcoming_exams' => $upcomingExams,
            'exam_card' => $examCard,
            'can_view_exam_card' => $canView,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildDocument(Student $student, int $semesterId): array
    {
        $student->loadMissing(['applicant', 'program.department', 'campus']);

        $semester = Semester::query()->with('academicYear')->findOrFail($semesterId);
        abort_unless($this->studentRegisteredForSemester($student->id, $semesterId), 404);

        $examCard = $this->findExamCard($student->id, $semesterId)
            ?? $this->issueExamCardIfEligible($student, $semesterId);

        abort_unless($examCard, 404, 'Exam card is not available. Clear fees and meet attendance requirements first.');

        $units = $this->examCardUnits($student, $semesterId);

        return [
            'student' => $student,
            'semester' => $semester,
            'exam_card' => $examCard,
            'units' => $units,
            'program' => $student->program,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function printViewData(array $document, bool $includeActions = true, ?int $semesterId = null): array
    {
        $student = $document['student'];
        $semester = $document['semester'];
        $examCard = $document['exam_card'];
        $semesterId ??= (int) $semester->id;

        return array_merge($document, [
            'documentTitle' => 'Examination card',
            'documentSubtitle' => $semester->semester_label,
            'documentRef' => $this->printDocuments->documentRef('EC', $examCard->exam_card_number),
            'paperOrientation' => 'portrait',
            'metaRows' => [
                ['label' => 'Student name', 'value' => e($student->applicant?->fullName() ?? $student->registration_number)],
                ['label' => 'Registration number', 'value' => e($student->registration_number)],
                ['label' => 'Examination number', 'value' => e($examCard->examination_number ?? $student->registration_number)],
                ['label' => 'Programme', 'value' => e($student->program?->program_name ?? '-')],
                ['label' => 'Semester', 'value' => e($semester->semester_label)],
                ['label' => 'Card number', 'value' => e($examCard->exam_card_number)],
            ],
            'backUrl' => $includeActions
                ? route('portal.dashboard', ['section' => 'academics', 'tab' => 'exams'])
                : null,
            'pdfUrl' => $includeActions
                ? route('portal.exam-card.pdf', ['semester' => $semesterId])
                : null,
        ]);
    }

    public function cachedPdfBinary(Student $student, int $semesterId): string
    {
        $document = $this->buildDocument($student, $semesterId);
        $examCard = $document['exam_card'];
        $scheduleStamp = $this->examScheduleCacheStamp($student->id, $semesterId);
        $issuedStamp = $this->timestampFromDbValue($examCard->issued_at ?? null);

        $cacheKey = sprintf(
            'student.exam_card.pdf.v1.%d.%d.%d.%s.%s',
            $student->id,
            $semesterId,
            (int) ($examCard->id ?? 0),
            $issuedStamp,
            $scheduleStamp,
        );

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($document) {
            $data = $this->printViewData($document, includeActions: false);

            return $this->printDocuments->pdfBinary(
                'portal.exam-card.print',
                $data,
                'portrait',
            );
        });
    }

    public function inlinePdfResponse(Student $student, int $semesterId, string $filename): Response
    {
        $binary = $this->cachedPdfBinary($student, $semesterId);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function downloadPdfResponse(Student $student, int $semesterId, string $filename): StreamedResponse
    {
        $binary = $this->cachedPdfBinary($student, $semesterId);

        return response()->streamDownload(function () use ($binary) {
            echo $binary;
        }, $filename, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    private function timestampFromDbValue(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp();
        }

        $parsed = strtotime((string) $value);

        return $parsed ?: 0;
    }

    private function examScheduleCacheStamp(int $studentId, int $semesterId): string
    {
        $unitIds = $this->registeredUnitIds($studentId, $semesterId);
        if ($unitIds === []) {
            return '0';
        }

        $row = DB::table('exam_schedules')
            ->where('semester_id', $semesterId)
            ->whereIn('unit_id', $unitIds)
            ->selectRaw('COUNT(*) as c, MAX(id) as max_id')
            ->first();

        if (! $row || (int) $row->c === 0) {
            return '0';
        }

        return (int) $row->c.'-'.(int) $row->max_id;
    }

    /**
     * @return Collection<int, object>
     */
    public function upcomingExams(Student $student, int $semesterId): Collection
    {
        $unitIds = $this->registeredUnitIds($student->id, $semesterId);

        if ($unitIds === []) {
            return collect();
        }

        $today = now()->toDateString();

        $eligibility = DB::table('exam_eligibility_matrix')
            ->where('student_id', $student->id)
            ->where('semester_id', $semesterId)
            ->whereIn('unit_id', $unitIds)
            ->get()
            ->keyBy('unit_id');

        return DB::table('exam_schedules as es')
            ->join('units as u', 'u.id', '=', 'es.unit_id')
            ->where('es.semester_id', $semesterId)
            ->whereIn('es.unit_id', $unitIds)
            ->where('es.exam_date', '>=', $today)
            ->whereIn('es.status', ['scheduled', 'in_progress'])
            ->orderBy('es.exam_date')
            ->orderBy('es.start_time')
            ->select([
                'es.id',
                'es.unit_id',
                'es.semester_id',
                'es.exam_date',
                'es.start_time',
                'es.end_time',
                'es.venue',
                'es.exam_type',
                'es.status',
                'u.unit_code',
                'u.unit_name',
            ])
            ->get()
            ->map(function ($row) use ($eligibility) {
                $elig = $eligibility->get($row->unit_id);

                return (object) [
                    'schedule_id' => (int) $row->id,
                    'unit_id' => (int) $row->unit_id,
                    'unit_code' => $row->unit_code,
                    'unit_name' => $row->unit_name,
                    'exam_date' => $row->exam_date,
                    'start_time' => $row->start_time,
                    'end_time' => $row->end_time,
                    'venue' => $row->venue,
                    'exam_type' => $row->exam_type,
                    'status' => $row->status,
                    'eligible_for_exams' => $elig ? (bool) $elig->eligible_for_exams : false,
                    'fee_clearance_check_passed' => $elig ? (bool) $elig->fee_clearance_check_passed : false,
                    'attendance_percentage' => $elig?->attendance_percentage,
                ];
            });
    }

    /**
     * @return list<object>
     */
    public function examCardUnits(Student $student, int $semesterId): array
    {
        $unitIds = $this->registeredUnitIds($student->id, $semesterId);

        if ($unitIds === []) {
            return [];
        }

        return DB::table('units as u')
            ->leftJoin('exam_eligibility_matrix as eem', function ($join) use ($student, $semesterId) {
                $join->on('eem.unit_id', '=', 'u.id')
                    ->where('eem.student_id', '=', $student->id)
                    ->where('eem.semester_id', '=', $semesterId);
            })
            ->leftJoin('exam_schedules as es', function ($join) use ($semesterId) {
                $join->on('es.unit_id', '=', 'u.id')
                    ->where('es.semester_id', '=', $semesterId);
            })
            ->whereIn('u.id', $unitIds)
            ->where(function ($query) {
                $query->whereNull('eem.eligible_for_exams')
                    ->orWhere('eem.eligible_for_exams', 1);
            })
            ->orderBy('u.unit_code')
            ->get([
                'u.unit_code',
                'u.unit_name',
                'es.exam_date',
                'es.start_time',
                'es.end_time',
                'es.venue',
                'es.exam_type',
            ])
            ->map(fn ($row) => (object) [
                'unit_code' => $row->unit_code,
                'unit_name' => $row->unit_name,
                'exam_date' => $row->exam_date,
                'start_time' => $row->start_time,
                'end_time' => $row->end_time,
                'venue' => $row->venue,
                'exam_type' => $row->exam_type,
            ])
            ->values()
            ->all();
    }

    private function resolveSemester(Student $student): ?Semester
    {
        if ($student->current_semester_id) {
            return Semester::query()->with('academicYear')->find($student->current_semester_id);
        }

        return Semester::query()->with('academicYear')->where('is_current', 1)->first();
    }

    /**
     * @return list<int>
     */
    private function registeredUnitIds(int $studentId, int $semesterId): array
    {
        return DB::table('registered_units as ru')
            ->join('student_semester_registrations as ssr', 'ssr.id', '=', 'ru.semester_registration_id')
            ->where('ssr.student_id', $studentId)
            ->where('ssr.semester_id', $semesterId)
            ->pluck('ru.unit_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function studentRegisteredForSemester(int $studentId, int $semesterId): bool
    {
        return DB::table('student_semester_registrations')
            ->where('student_id', $studentId)
            ->where('semester_id', $semesterId)
            ->exists();
    }

    private function findExamCard(int $studentId, int $semesterId): ?object
    {
        return DB::table('exam_cards')
            ->where('student_id', $studentId)
            ->where('semester_id', $semesterId)
            ->where('is_voided', 0)
            ->orderByDesc('issued_at')
            ->first();
    }

    private function issueExamCardIfEligible(Student $student, int $semesterId): ?object
    {
        if (! $this->hasEligibleUnits($student->id, $semesterId)) {
            return null;
        }

        $existing = DB::table('exam_cards')
            ->where('student_id', $student->id)
            ->where('semester_id', $semesterId)
            ->first();

        if ($existing && ! $existing->is_voided) {
            return $existing;
        }

        $cardNumber = 'EC-'.now()->format('Y').'-'.strtoupper(Str::random(6));

        try {
            $id = DB::table('exam_cards')->insertGetId([
                'exam_card_number' => $cardNumber,
                'student_id' => $student->id,
                'semester_id' => $semesterId,
                'examination_number' => $student->registration_number,
                'qr_code_data' => $cardNumber,
                'issued_at' => now(),
                'is_voided' => 0,
            ]);
        } catch (QueryException) {
            return $this->findExamCard($student->id, $semesterId);
        }

        return DB::table('exam_cards')->where('id', $id)->first();
    }

    private function hasEligibleUnits(int $studentId, int $semesterId): bool
    {
        return DB::table('exam_eligibility_matrix')
            ->where('student_id', $studentId)
            ->where('semester_id', $semesterId)
            ->where('eligible_for_exams', 1)
            ->exists();
    }
}
