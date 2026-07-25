<?php

namespace App\Services;

/**
 * Phase C integration hooks - downstream modules consume published curriculum data.
 */
class AcademicsIntegrationRegistry
{
    public function __construct(protected CurriculumVersionService $versions) {}

    /**
     * Hook: unit_allocations / workload balancer (not yet implemented).
     *
     * @return array{ready: bool, published_version_id: ?int, unit_count: int}
     */
    public function workloadContext(int $programId): array
    {
        $version = $this->versions->publishedVersionForProgram($programId);

        return [
            'ready' => $version !== null,
            'published_version_id' => $version?->id,
            'unit_count' => $version?->items()->count() ?? 0,
        ];
    }

    /**
     * Hook: timetable builder validates contact hours vs published curriculum.
     */
    public function timetableUnitHours(int $programId, int $unitId): ?array
    {
        $version = $this->versions->publishedVersionForProgram($programId);

        if (! $version) {
            return null;
        }

        $item = $version->items()->where('unit_id', $unitId)->first();

        if (! $item) {
            return null;
        }

        return [
            'contact_hours' => $item->contact_hours,
            'total_learning_hours' => $item->total_learning_hours,
            'semester' => $item->semester,
            'block_id' => $item->block_id,
        ];
    }

    /**
     * Hook: exam eligibility uses program min_attendance_pct from published curriculum context.
     */
    public function examEligibilityThreshold(int $programId): float
    {
        $program = \App\Models\AcademicProgram::query()->find($programId);

        return (float) ($program?->min_attendance_pct ?? 90.0);
    }

    /**
     * Hook: lesson plans reference unit allocations once staffing is assigned.
     */
    public function lessonPlanReady(int $programId): bool
    {
        return $this->workloadContext($programId)['ready'];
    }
}
