<?php

namespace Database\Seeders;

use App\Models\AcademicWorkplan;
use App\Models\AcademicWorkplanActivity;
use App\Models\Semester;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sample HOD semester workplans for dual Registrar + QA approval testing.
 * Anchored to evans777999@gmail.com (HOD, Health and Social Sciences).
 */
class AcademicWorkplanSampleSeeder extends Seeder
{
    private const EMAIL = 'evans777999@gmail.com';

    public function run(): void
    {
        if (! Schema::hasTable('academic_workplans')) {
            $this->command?->warn('academic_workplans table missing; run migrations first.');

            return;
        }

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

        $semester = Semester::query()->orderByDesc('id')->first();
        if (! $semester) {
            $this->command?->warn('No semester found; skipped.');

            return;
        }

        $registrarStaffId = $this->staffIdForRole('Academic Registrar');
        $qaStaffId = $this->staffIdForRole('QA Officer');

        $samples = [
            [
                'workplan_number' => 'AWP-SAMPLE-DRAFT',
                'title' => 'Department teaching & practicum workplan (draft)',
                'status' => AcademicWorkplan::STATUS_DRAFT,
                'submitted_at' => null,
                'registrar_status' => AcademicWorkplan::REVIEW_PENDING,
                'registrar_staff_id' => null,
                'registrar_acted_at' => null,
                'registrar_comments' => null,
                'qa_status' => AcademicWorkplan::REVIEW_PENDING,
                'qa_staff_id' => null,
                'qa_acted_at' => null,
                'qa_comments' => null,
            ],
            [
                'workplan_number' => 'AWP-SAMPLE-PENDING',
                'title' => 'Semester quality & delivery workplan (pending dual review)',
                'status' => AcademicWorkplan::STATUS_PENDING,
                'submitted_at' => now()->subDays(1),
                'registrar_status' => AcademicWorkplan::REVIEW_PENDING,
                'registrar_staff_id' => null,
                'registrar_acted_at' => null,
                'registrar_comments' => null,
                'qa_status' => AcademicWorkplan::REVIEW_PENDING,
                'qa_staff_id' => null,
                'qa_acted_at' => null,
                'qa_comments' => null,
            ],
            [
                'workplan_number' => 'AWP-SAMPLE-REG-OK',
                'title' => 'Community outreach calendar (Registrar approved, QA pending)',
                'status' => AcademicWorkplan::STATUS_PENDING,
                'submitted_at' => now()->subDays(3),
                'registrar_status' => AcademicWorkplan::REVIEW_APPROVED,
                'registrar_staff_id' => $registrarStaffId,
                'registrar_acted_at' => now()->subDays(2),
                'registrar_comments' => 'Aligned with academic calendar. Proceed for QA review.',
                'qa_status' => AcademicWorkplan::REVIEW_PENDING,
                'qa_staff_id' => null,
                'qa_acted_at' => null,
                'qa_comments' => null,
            ],
            [
                'workplan_number' => 'AWP-SAMPLE-APPROVED',
                'title' => 'Clinical skills lab reinforcement plan (fully approved)',
                'status' => AcademicWorkplan::STATUS_APPROVED,
                'submitted_at' => now()->subDays(10),
                'registrar_status' => AcademicWorkplan::REVIEW_APPROVED,
                'registrar_staff_id' => $registrarStaffId,
                'registrar_acted_at' => now()->subDays(8),
                'registrar_comments' => 'Approved.',
                'qa_status' => AcademicWorkplan::REVIEW_APPROVED,
                'qa_staff_id' => $qaStaffId,
                'qa_acted_at' => now()->subDays(7),
                'qa_comments' => 'Quality indicators are clear and measurable.',
            ],
            [
                'workplan_number' => 'AWP-SAMPLE-CHANGES',
                'title' => 'Student support & remediation schedule (changes requested)',
                'status' => AcademicWorkplan::STATUS_CHANGES_REQUESTED,
                'submitted_at' => now()->subDays(6),
                'registrar_status' => AcademicWorkplan::REVIEW_CHANGES_REQUESTED,
                'registrar_staff_id' => $registrarStaffId,
                'registrar_acted_at' => now()->subDays(4),
                'registrar_comments' => 'Add mid-semester checkpoint dates and responsible officers.',
                'qa_status' => AcademicWorkplan::REVIEW_PENDING,
                'qa_staff_id' => null,
                'qa_acted_at' => null,
                'qa_comments' => null,
            ],
        ];

        $created = 0;
        $updated = 0;

        foreach ($samples as $sample) {
            $attributes = [
                'department_id' => $departmentId,
                'semester_id' => $semester->id,
                'title' => $sample['title'],
                'objectives' => "Strengthen teaching quality, practicum supervision, and continuous improvement for Health and Social Sciences in {$semester->semester_label}.",
                'resources' => 'Department tutors, skills lab, community partners, QA checklists, timetable slots.',
                'kpis' => "≥90% lesson plans submitted on time; ≥2 peer observations per tutor; practicum feedback logged within 7 days.",
                'status' => $sample['status'],
                'prepared_by_staff_id' => $hodStaff->id,
                'submitted_at' => $sample['submitted_at'],
                'registrar_status' => $sample['registrar_status'],
                'registrar_staff_id' => $sample['registrar_staff_id'],
                'registrar_acted_at' => $sample['registrar_acted_at'],
                'registrar_comments' => $sample['registrar_comments'],
                'qa_status' => $sample['qa_status'],
                'qa_staff_id' => $sample['qa_staff_id'],
                'qa_acted_at' => $sample['qa_acted_at'],
                'qa_comments' => $sample['qa_comments'],
            ];

            $workplan = AcademicWorkplan::query()
                ->where('workplan_number', $sample['workplan_number'])
                ->first();

            if ($workplan) {
                $workplan->fill($attributes)->save();
                $updated++;
            } else {
                $workplan = AcademicWorkplan::query()->create(array_merge($attributes, [
                    'workplan_number' => $sample['workplan_number'],
                ]));
                $created++;
            }

            $this->syncSampleActivities($workplan);
        }

        $this->command?->info("Academic workplan samples for ".self::EMAIL.": created={$created}, updated={$updated} (semester #{$semester->id}).");
    }

    private function syncSampleActivities(AcademicWorkplan $workplan): void
    {
        AcademicWorkplanActivity::query()->where('workplan_id', $workplan->id)->delete();

        $rows = [
            [
                'activity' => 'Kick-off departmental briefing and agree quality priorities',
                'timeline_start' => now()->toDateString(),
                'timeline_end' => now()->addWeeks(1)->toDateString(),
                'kpi' => 'Attendance register signed by ≥80% of tutors',
                'resources' => 'Meeting room, agenda pack',
                'sort_order' => 1,
            ],
            [
                'activity' => 'Schedule peer lesson observations and feedback loops',
                'timeline_start' => now()->addWeeks(2)->toDateString(),
                'timeline_end' => now()->addWeeks(8)->toDateString(),
                'kpi' => 'At least 2 completed observation forms per tutor',
                'resources' => 'Observation checklist, QA template',
                'sort_order' => 2,
            ],
            [
                'activity' => 'Practicum site quality visits and student debriefs',
                'timeline_start' => now()->addWeeks(3)->toDateString(),
                'timeline_end' => now()->addWeeks(12)->toDateString(),
                'kpi' => 'Visit reports filed within 7 days of each site visit',
                'resources' => 'Transport, site MOUs, student logs',
                'sort_order' => 3,
            ],
            [
                'activity' => 'Mid-semester workplan review with Registrar and QA inputs',
                'timeline_start' => now()->addWeeks(7)->toDateString(),
                'timeline_end' => now()->addWeeks(8)->toDateString(),
                'kpi' => 'Action tracker updated with owners and due dates',
                'resources' => 'Shared tracker, prior IQA findings',
                'sort_order' => 4,
            ],
        ];

        foreach ($rows as $row) {
            AcademicWorkplanActivity::query()->create(array_merge($row, [
                'workplan_id' => $workplan->id,
            ]));
        }
    }

    private function staffIdForRole(string $roleName): ?int
    {
        $userId = DB::table('user_roles as ur')
            ->join('roles as r', 'r.id', '=', 'ur.role_id')
            ->where('r.role_name', $roleName)
            ->orderBy('ur.user_id')
            ->value('ur.user_id');

        if (! $userId) {
            return null;
        }

        $staffId = User::query()->where('id', $userId)->value('staff_id');

        return $staffId ? (int) $staffId : null;
    }
}
