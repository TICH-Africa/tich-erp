<?php

namespace App\Services\Academics;

use App\Models\AcademicProgram;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentProgramTierUpgradeService
{
    public function __construct(
        protected ProgramTierFeeService $fees,
        protected AuditService $auditService,
    ) {}

    /**
     * Student or finance requests moving to the next tier (in-system upgrade).
     *
     * @return array<string, mixed>
     */
    public function requestNextTier(Student $student, ?Request $request = null): array
    {
        $student->loadMissing('program');
        $program = $student->program;
        if (! $program?->isTieredProgram()) {
            throw ValidationException::withMessages(['tier' => 'This programme is not tiered.']);
        }

        $keys = $this->fees->tierKeysInOrder();
        $current = $student->program_tier ?? $keys[0] ?? null;
        $currentIndex = $this->fees->tierOrderIndex((string) $current);
        $nextKey = $keys[$currentIndex + 1] ?? null;

        if (! $nextKey) {
            throw ValidationException::withMessages(['tier' => 'Student is already on the highest tier.']);
        }

        $topUp = $this->fees->topUpAmount($program, (string) $current, $nextKey);

        return [
            'from_tier' => $current,
            'to_tier' => $nextKey,
            'top_up_amount' => $topUp,
            'status' => 'pending_academics_approval',
        ];
    }

    /**
     * Academics approves carry-forward of lower-tier results and activates the new tier.
     */
    public function approveUpgrade(User $actor, Student $student, string $toTierKey, ?Request $request = null): Student
    {
        $student->loadMissing('program');
        $program = $student->program;
        if (! $program?->isTieredProgram()) {
            throw ValidationException::withMessages(['tier' => 'This programme is not tiered.']);
        }

        $keys = $this->fees->tierKeysInOrder();
        $current = $student->program_tier ?? $keys[0];
        $expectedNext = $keys[$this->fees->tierOrderIndex((string) $current) + 1] ?? null;

        if ($toTierKey !== $expectedNext) {
            throw ValidationException::withMessages([
                'tier' => 'Students cannot skip tiers. Approve only the next level in sequence.',
            ]);
        }

        return DB::transaction(function () use ($student, $program, $current, $toTierKey, $actor, $request) {
            $history = is_array($student->program_tier_history) ? $student->program_tier_history : [];
            $history[] = [
                'from' => $current,
                'to' => $toTierKey,
                'approved_at' => now()->toIso8601String(),
                'approved_by' => $actor->id,
            ];

            $paid = is_array($student->paid_program_tiers) ? $student->paid_program_tiers : [];
            if (! in_array($toTierKey, $paid, true)) {
                $paid[] = $toTierKey;
            }

            $student->update([
                'program_tier' => $toTierKey,
                'program_tier_history' => $history,
                'paid_program_tiers' => array_values($paid),
                'updated_at' => now(),
            ]);

            $this->auditService->log(
                'academics.student.tier_upgrade_approved',
                'students',
                $student->id,
                ['program_tier' => $current],
                ['program_tier' => $toTierKey],
                'Tier upgrade approved by Academics',
                'success',
                $actor->id,
                $request
            );

            return $student->fresh();
        });
    }

    public function recordInitialTierPayment(Student $student, string $tierKey): void
    {
        $paid = is_array($student->paid_program_tiers) ? $student->paid_program_tiers : [];
        if (! in_array($tierKey, $paid, true)) {
            $paid[] = $tierKey;
        }

        $student->update([
            'program_tier' => $student->program_tier ?? $tierKey,
            'paid_program_tiers' => array_values($paid),
            'updated_at' => now(),
        ]);
    }
}
