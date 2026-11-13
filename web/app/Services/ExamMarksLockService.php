<?php

namespace App\Services;

use App\Models\Unit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Academics take the marks over from the lecturer. Locking a unit's marks is that hand-over:
 * the lecturer's grid turns read-only and every writer of those marks refuses, so nothing the
 * school has already stood by can move underneath a transcript.
 */
class ExamMarksLockService
{
    public function __construct(protected AuditService $auditService) {}

    public function state(int $unitId, ?int $semesterId): ?object
    {
        if (! $semesterId) {
            return null;
        }

        $row = DB::table('exam_marks_locks')
            ->where('unit_id', $unitId)
            ->where('semester_id', $semesterId)
            ->whereNull('unlocked_at')
            ->first();

        return $row ?: null;
    }

    /**
     * The one gate every writer of a unit's marks passes through. `$scope` names what the person
     * was doing so the message tells them where to go, not just that they cannot.
     */
    public function assertEditable(int $unitId, ?int $semesterId, string $scope, int $period): void
    {
        $lock = $this->state($unitId, $semesterId);

        if (! $lock) {
            return;
        }

        throw ValidationException::withMessages([
            'marks' => 'Academics submitted this unit\'s marks for Semester '.$period.' on '
                .Carbon::parse($lock->locked_at)->format('d M Y').' and they are locked, so '.$scope.' can no longer be changed here. '
                .'Ask Academics to unlock the unit to correct a mark.',
        ]);
    }

    public function lock(Unit $unit, int $semesterId, int $period, int $entered): void
    {
        $existing = DB::table('exam_marks_locks')
            ->where('unit_id', $unit->id)
            ->where('semester_id', $semesterId)
            ->first();

        $payload = [
            'locked_at' => now(),
            'locked_by' => Auth::id(),
            'unlocked_at' => null,
            'unlocked_by' => null,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('exam_marks_locks')->where('id', $existing->id)->update($payload);
        } else {
            DB::table('exam_marks_locks')->insert([
                'unit_id' => $unit->id,
                'semester_id' => $semesterId,
                ...$payload,
                'created_at' => now(),
            ]);
        }

        $this->audit('academics.marks.locked', $unit, $semesterId, $period, $entered);
    }

    public function unlock(Unit $unit, int $semesterId, int $period, ?string $reason): void
    {
        $lock = $this->state($unit->id, $semesterId);

        if (! $lock) {
            return;
        }

        DB::table('exam_marks_locks')
            ->where('id', $lock->id)
            ->update([
                'unlocked_at' => now(),
                'unlocked_by' => Auth::id(),
                'updated_at' => now(),
            ]);

        $this->audit('academics.marks.unlocked', $unit, $semesterId, $period, 0, $reason);
    }

    /** How many sittings already carry an exam mark - what Academics is signing for. */
    public function marksEntered(int $unitId, int $semesterId): int
    {
        return (int) DB::table('exam_results')
            ->where('unit_id', $unitId)
            ->where('semester_id', $semesterId)
            ->where('is_supplementary', 0)
            ->whereNotNull('final_exam_score')
            ->count();
    }

    private function audit(
        string $action,
        Unit $unit,
        int $semesterId,
        int $period,
        int $entered,
        ?string $reason = null,
    ): void {
        $this->auditService->log(
            $action,
            'units',
            $unit->id,
            null,
            [
                'unit_code' => $unit->unit_code,
                'semester_id' => $semesterId,
                'teaching_period' => $period,
                'marks_entered' => $entered,
                'reason' => $reason,
            ],
            $reason ?: ($action === 'academics.marks.locked'
                ? 'Exam marks submitted to Academics and locked.'
                : 'Exam marks unlocked for the lecturer to correct.'),
            'success',
            Auth::id(),
        );
    }
}
