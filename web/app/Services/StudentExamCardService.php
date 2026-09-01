<?php

namespace App\Services;

use App\Models\Semester;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentExamCardService
{
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

        $eligibility = DB::table('exam_eligibility_matrix')
            ->where('student_id', $student->id)
            ->where('semester_id', $semesterId)
            ->whereIn('unit_id', $unitIds)
            ->get()
            ->keyBy('unit_id');

        $schedules = DB::table('exam_schedules')
            ->where('semester_id', $semesterId)
            ->whereIn('unit_id', $unitIds)
            ->get()
            ->keyBy('unit_id');

        return DB::table('units')
            ->whereIn('id', $unitIds)
            ->orderBy('unit_code')
            ->get(['id', 'unit_code', 'unit_name'])
            ->map(function ($unit) use ($eligibility, $schedules) {
                $elig = $eligibility->get($unit->id);
                $schedule = $schedules->get($unit->id);

                if ($elig && ! $elig->eligible_for_exams) {
                    return null;
                }

                return (object) [
                    'unit_code' => $unit->unit_code,
                    'unit_name' => $unit->unit_name,
                    'exam_date' => $schedule?->exam_date,
                    'start_time' => $schedule?->start_time,
                    'end_time' => $schedule?->end_time,
                    'venue' => $schedule?->venue,
                    'exam_type' => $schedule?->exam_type,
                ];
            })
            ->filter()
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

        $id = DB::table('exam_cards')->insertGetId([
            'exam_card_number' => $cardNumber,
            'student_id' => $student->id,
            'semester_id' => $semesterId,
            'examination_number' => $student->registration_number,
            'qr_code_data' => $cardNumber,
            'issued_at' => now(),
            'is_voided' => 0,
        ]);

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
