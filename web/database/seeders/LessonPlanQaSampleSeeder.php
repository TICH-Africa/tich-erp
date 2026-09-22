<?php

namespace Database\Seeders;

use App\Models\LessonPlan;
use App\Models\Staff;
use App\Models\UnitAllocation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sample lesson plans for QA acknowledgement testing.
 * Anchored to evans777999@gmail.com (HOD / Lecturer in Health and Social Sciences).
 */
class LessonPlanQaSampleSeeder extends Seeder
{
    private const EMAIL = 'evans777999@gmail.com';

    public function run(): void
    {
        $evans = User::query()->where('email', self::EMAIL)->first();
        if (! $evans || ! $evans->staff_id) {
            $this->command?->warn('User '.self::EMAIL.' not found with a staff profile; skipped.');

            return;
        }

        $hodStaff = Staff::query()->find($evans->staff_id);
        if (! $hodStaff) {
            $this->command?->warn('Staff profile for '.self::EMAIL.' missing; skipped.');

            return;
        }

        $departmentId = (int) (DB::table('departments')->where('hod_id', $hodStaff->id)->value('id')
            ?: $hodStaff->department_id);
        if ($departmentId < 1) {
            $this->command?->warn('No department found for '.self::EMAIL.'; skipped.');

            return;
        }

        $allocations = UnitAllocation::query()
            ->whereHas('unit', fn ($q) => $q->where('department_id', $departmentId))
            ->orderBy('id')
            ->limit(12)
            ->get();

        if ($allocations->isEmpty()) {
            $this->command?->warn('No unit allocations in department '.$departmentId.'; skipped.');

            return;
        }

        $tutorStaffId = (int) ($allocations->first(fn ($a) => (int) $a->staff_id !== (int) $hodStaff->id)?->staff_id
            ?: $hodStaff->id);
        $tutorStaff = Staff::query()->find($tutorStaffId) ?: $hodStaff;

        $allocations = $allocations
            ->filter(fn ($a) => in_array((int) $a->staff_id, [(int) $tutorStaff->id, (int) $hodStaff->id], true))
            ->values();

        if ($allocations->isEmpty()) {
            $this->command?->warn('No matching tutor allocations for sample lesson plans; skipped.');

            return;
        }

        $samples = [
            [
                'plan_number' => 'LP-SAMPLE-QA-SUBMITTED',
                'status' => 'submitted',
                'prepared_by' => $tutorStaff->id,
                'allocation' => $allocations[0] ?? null,
                'hod_id' => null,
                'hod_action_at' => null,
                'hod_comments' => null,
                'qa_acknowledged_by' => null,
                'qa_acknowledged_at' => null,
                'qa_comments' => null,
                'title' => 'Community health needs assessment briefing',
                'week' => 3,
            ],
            [
                'plan_number' => 'LP-SAMPLE-QA-PENDING-ACK',
                'status' => 'approved',
                'prepared_by' => $tutorStaff->id,
                'allocation' => $allocations[1] ?? $allocations[0],
                'hod_id' => $hodStaff->id,
                'hod_action_at' => now()->subDays(2),
                'hod_comments' => 'Approved for delivery. Ensure participatory methods are documented.',
                'qa_acknowledged_by' => null,
                'qa_acknowledged_at' => null,
                'qa_comments' => null,
                'title' => 'Maternal health promotion session plan',
                'week' => 4,
            ],
            [
                'plan_number' => 'LP-SAMPLE-QA-ACKED',
                'status' => 'approved',
                'prepared_by' => $tutorStaff->id,
                'allocation' => $allocations[2] ?? $allocations[0],
                'hod_id' => $hodStaff->id,
                'hod_action_at' => now()->subDays(5),
                'hod_comments' => 'Approved.',
                'qa_acknowledged_by' => $this->resolveQaStaffId(),
                'qa_acknowledged_at' => now()->subDay(),
                'qa_comments' => 'Acknowledged. Learning outcomes align with programme standards.',
                'title' => 'Nutrition counselling practical',
                'week' => 5,
            ],
            [
                'plan_number' => 'LP-SAMPLE-QA-HOD-TUTOR',
                'status' => 'submitted',
                'prepared_by' => $hodStaff->id,
                'allocation' => $allocations->first(fn ($a) => (int) $a->staff_id === (int) $hodStaff->id) ?: $allocations[0],
                'hod_id' => null,
                'hod_action_at' => null,
                'hod_comments' => null,
                'qa_acknowledged_by' => null,
                'qa_acknowledged_at' => null,
                'qa_comments' => null,
                'title' => 'Field practicum orientation (HOD as tutor)',
                'week' => 6,
            ],
            [
                'plan_number' => 'LP-SAMPLE-QA-REJECTED',
                'status' => 'rejected',
                'prepared_by' => $tutorStaff->id,
                'allocation' => $allocations[3] ?? $allocations[0],
                'hod_id' => $hodStaff->id,
                'hod_action_at' => now()->subDays(1),
                'hod_comments' => 'Revise objectives to be measurable and add assessment criteria.',
                'qa_acknowledged_by' => null,
                'qa_acknowledged_at' => null,
                'qa_comments' => null,
                'title' => 'Environmental health walk-through draft',
                'week' => 2,
            ],
        ];

        $created = 0;
        $updated = 0;

        foreach ($samples as $sample) {
            /** @var UnitAllocation|null $allocation */
            $allocation = $sample['allocation'];
            if (! $allocation) {
                continue;
            }

            // Ensure prepared_by matches allocation tutor where possible.
            $preparedBy = (int) $sample['prepared_by'];
            if ((int) $allocation->staff_id !== $preparedBy) {
                $match = $allocations->first(fn ($a) => (int) $a->staff_id === $preparedBy);
                if ($match) {
                    $allocation = $match;
                } else {
                    $preparedBy = (int) $allocation->staff_id;
                }
            }

            $payload = [
                'unit_allocation_id' => $allocation->id,
                'prepared_by' => $preparedBy,
                'source_type' => 'form',
                'lesson_title' => $sample['title'],
                'lesson_objectives' => "By the end of this session, learners will be able to:\n1. Explain key concepts for {$sample['title']}.\n2. Apply community-based approaches in practice.\n3. Reflect on quality and safety considerations.",
                'topics_covered' => $sample['title'].'; — core concepts, demonstration, and guided discussion.',
                'competencies_targeted' => 'Professional practice; community engagement; quality assurance awareness.',
                'contact_hours' => 2,
                'week_number' => $sample['week'],
                'planned_date' => now()->addWeeks(max(1, (int) $sample['week'] - 2))->toDateString(),
                'teaching_methods' => 'Interactive lecture, group work, case discussion.',
                'resources_required' => 'Whiteboard, handouts, community case scenarios.',
                'form_payload' => [
                    'sample' => true,
                    'anchor_email' => self::EMAIL,
                ],
                'tutor_verified_at' => now()->subDays(3),
                'status' => $sample['status'],
                'hod_comments' => $sample['hod_comments'],
                'hod_id' => $sample['hod_id'],
                'hod_action_at' => $sample['hod_action_at'],
                'registrar_visible' => 1,
                'qa_acknowledged_by' => $sample['qa_acknowledged_by'],
                'qa_acknowledged_at' => $sample['qa_acknowledged_at'],
                'qa_comments' => $sample['qa_comments'],
                'updated_at' => now(),
            ];

            $existing = LessonPlan::query()->where('plan_number', $sample['plan_number'])->first();
            if ($existing) {
                $existing->fill($payload)->save();
                $updated++;
            } else {
                LessonPlan::query()->create(array_merge($payload, [
                    'plan_number' => $sample['plan_number'],
                    'created_at' => now()->subDays(4),
                ]));
                $created++;
            }
        }

        $this->command?->info("Lesson plan QA samples for ".self::EMAIL.": created={$created}, updated={$updated}.");
    }

    private function resolveQaStaffId(): ?int
    {
        $userId = DB::table('user_roles as ur')
            ->join('roles as r', 'r.id', '=', 'ur.role_id')
            ->where('r.role_name', 'QA Officer')
            ->value('ur.user_id');

        if (! $userId) {
            return null;
        }

        return User::query()->where('id', $userId)->value('staff_id');
    }
}
