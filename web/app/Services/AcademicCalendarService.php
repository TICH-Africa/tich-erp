<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AcademicCalendarService
{
    public function __construct(protected AuditService $auditService) {}

    /**
     * @return Collection<int, AcademicYear>
     */
    public function listYears(): Collection
    {
        return AcademicYear::query()
            ->with('semesters')
            ->orderByDesc('start_date')
            ->get();
    }

    public function createYear(User $user, array $data, ?Request $request = null): AcademicYear
    {
        return DB::transaction(function () use ($user, $data, $request) {
            if (! empty($data['is_current'])) {
                AcademicYear::query()->update(['is_current' => 0]);
            }

            $year = AcademicYear::create([
                'year_label' => $data['year_label'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_current' => ! empty($data['is_current']) ? 1 : 0,
                'created_by' => $user->id,
                'created_at' => now(),
            ]);

            $this->seedTrimesters($user, $year, $data);

            $this->auditService->log(
                'academics.calendar.year_created',
                'academic_years',
                $year->id,
                null,
                $year->only(['year_label', 'start_date', 'end_date']),
                'Academic year created',
                'success',
                $user->id,
                $request
            );

            return $year->fresh('semesters');
        });
    }

    public function updateSemester(User $user, Semester $semester, array $data, ?Request $request = null): Semester
    {
        if (! empty($data['is_current'])) {
            Semester::query()->update(['is_current' => 0]);
        }

        $semester->update([
            'semester_label' => Semester::normalizeLabel(
                $data['semester_label'],
                (int) ($data['semester_number'] ?? $semester->semester_number),
            ),
            'semester_number' => $data['semester_number'],
            'intake_month' => $data['intake_month'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'registration_open_date' => $data['registration_open_date'] ?? null,
            'registration_close_date' => $data['registration_close_date'] ?? null,
            'is_current' => ! empty($data['is_current']) ? 1 : 0,
        ]);

        $this->auditService->log(
            'academics.calendar.semester_updated',
            'semesters',
            $semester->id,
            null,
            $semester->only(['semester_label', 'start_date', 'end_date']),
            'Semester updated',
            'success',
            $user->id,
            $request
        );

        return $semester->fresh();
    }

    private function seedTrimesters(User $user, AcademicYear $year, array $data): void
    {
        $termCount = (int) ($data['term_count'] ?? 3);
        $intakes = $data['intake_months'] ?? ['January', 'May', 'September'];
        $start = \Carbon\Carbon::parse($data['start_date']);
        $end = \Carbon\Carbon::parse($data['end_date']);
        $spanDays = max(1, $start->diffInDays($end));
        $termDays = (int) floor($spanDays / max(1, $termCount));

        for ($i = 1; $i <= $termCount; $i++) {
            $termStart = $start->copy()->addDays(($i - 1) * $termDays);
            $termEnd = $i === $termCount ? $end : $start->copy()->addDays($i * $termDays)->subDay();

            Semester::create([
                'academic_year_id' => $year->id,
                'semester_label' => Semester::normalizeLabel(
                    $data['term_labels'][$i - 1] ?? "Semester {$i}",
                    $i,
                ),
                'semester_number' => $i,
                'intake_month' => $intakes[$i - 1] ?? null,
                'start_date' => $termStart->toDateString(),
                'end_date' => $termEnd->toDateString(),
                'is_current' => $i === 1 && ! empty($data['is_current']) ? 1 : 0,
                'created_by' => $user->id,
                'created_at' => now(),
            ]);
        }
    }
}
