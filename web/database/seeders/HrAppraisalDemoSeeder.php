<?php

namespace Database\Seeders;

use App\Models\HrAppraisal;
use App\Models\HrAppraisalCycle;
use App\Models\HrCorporateGoal;
use App\Models\Staff;
use App\Models\User;
use App\Services\HrPerformanceAppraisalService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds an open quarterly appraisal for osumbaevans21@gmail.com (demo walkthrough).
 * Idempotent for the current fiscal quarter.
 */
class HrAppraisalDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('hr_appraisal_cycles') || ! Schema::hasTable('hr_appraisals')) {
            $this->command?->warn('Appraisal tables missing — run migrations first.');

            return;
        }

        $user = User::query()->where('email', 'osumbaevans21@gmail.com')->first();
        if (! $user?->staff) {
            $this->command?->warn('User osumbaevans21@gmail.com (with staff) not found — skipped.');

            return;
        }

        $staff = $user->staff;
        $svc = app(HrPerformanceAppraisalService::class);

        $manager = Staff::query()
            ->excludePlatformOperators()
            ->where('id', '!=', $staff->id)
            ->whereIn('employment_status', ['active', 'on_leave', 'onboarding'])
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->first() ?? $staff;

        DB::transaction(function () use ($svc, $staff, $manager, $user) {
            if (blank($staff->job_description)) {
                $staff->job_description = 'Deliver community health tutorials and mentoring; prepare lesson plans; mark assessments; support students; participate in departmental meetings; uphold TICH academic and safeguarding standards.';
            }
            $staff->line_manager_id = $manager->id;
            $staff->save();

            $year = (int) now()->year;
            $quarter = (int) ceil(now()->month / 3);

            $cycle = HrAppraisalCycle::query()->firstOrCreate(
                ['fiscal_year' => $year, 'quarter' => $quarter],
                [
                    'name' => sprintf('Q%d %d Performance Appraisal (demo)', $quarter, $year),
                    'period_start' => now()->startOfQuarter()->toDateString(),
                    'period_end' => now()->endOfQuarter()->toDateString(),
                    'status' => 'draft',
                    'initiated_by' => $staff->id,
                    'instructions' => 'Demo cycle for platform walkthrough. Set SMART goals, self-assess, then manager review.',
                ]
            );

            if ($cycle->status === 'draft') {
                $cycle->update(['status' => 'open', 'opened_at' => now()]);
            }

            HrCorporateGoal::query()->updateOrCreate(
                ['code' => 'KPI-TICH-TEACH-01', 'cycle_id' => $cycle->id],
                [
                    'title' => 'Improve teaching and learner support quality',
                    'description' => 'Institutional teaching KPI: timely lesson plans, learner feedback, and assessment turnaround.',
                    'role_scope' => 'all',
                    'weight_hint' => 20,
                    'is_active' => true,
                    'created_by' => $staff->id,
                ]
            );

            HrCorporateGoal::query()->updateOrCreate(
                ['code' => 'KPI-TICH-COMM-01', 'cycle_id' => $cycle->id],
                [
                    'title' => 'Strengthen community health outreach contribution',
                    'description' => 'Support at least one outreach or community engagement activity linked to programme outcomes.',
                    'role_scope' => 'job_title',
                    'scope_values' => ['Community Health Tutor', 'Lecturer', 'Tutor'],
                    'weight_hint' => 15,
                    'is_active' => true,
                    'created_by' => $staff->id,
                ]
            );

            $appraisal = $svc->createAppraisalShell($cycle->fresh(), $staff->fresh());
            $appraisal->update([
                'line_manager_id' => $manager->id,
                'job_title_snapshot' => $staff->job_title,
                'job_description_snapshot' => $staff->job_description,
                'department_id' => $staff->department_id,
                'status' => 'draft_goals',
            ]);

            $corpTeach = HrCorporateGoal::query()->where('code', 'KPI-TICH-TEACH-01')->where('cycle_id', $cycle->id)->first();
            $corpComm = HrCorporateGoal::query()->where('code', 'KPI-TICH-COMM-01')->where('cycle_id', $cycle->id)->first();

            $appraisal->goals()->where('goal_type', 'jd_duties')->first()?->update([
                'weight' => 40,
                'description' => $staff->job_description,
            ]);
            $appraisal->goals()->where('goal_type', '!=', 'jd_duties')->delete();

            $svc->upsertGoal($appraisal->fresh(), [
                'title' => 'Deliver quality teaching for assigned community health units',
                'description' => 'Plan, deliver, and assess learning sessions to the required academic standard.',
                'smart_specific' => 'Complete lesson plans and deliver all scheduled sessions for assigned units this quarter.',
                'smart_measurable' => '100% of scheduled sessions delivered; lesson plans submitted before each session week.',
                'smart_achievable' => 'Within current teaching load and department timetable.',
                'smart_relevant' => 'Supports TICH teaching quality KPI and Community Health Tutor JD.',
                'smart_timebound' => 'By end of this appraisal quarter.',
                'target_date' => $cycle->period_end->toDateString(),
                'weight' => 25,
                'corporate_goal_id' => $corpTeach?->id,
            ]);

            $svc->upsertGoal($appraisal->fresh(), [
                'title' => 'Support one community health outreach activity',
                'description' => 'Contribute professionally to outreach linked to programme outcomes.',
                'smart_specific' => 'Participate in or coordinate one outreach/community engagement activity.',
                'smart_measurable' => 'One documented outreach with attendance/brief report filed.',
                'smart_achievable' => 'Using existing programme calendar and department support.',
                'smart_relevant' => 'Aligns with community health mandate and cascading KPI.',
                'smart_timebound' => 'Complete before quarter end.',
                'target_date' => $cycle->period_end->toDateString(),
                'weight' => 20,
                'corporate_goal_id' => $corpComm?->id,
            ]);

            $svc->upsertGoal($appraisal->fresh(), [
                'title' => 'Improve assessment turnaround and learner feedback',
                'description' => 'Return marked work with constructive feedback within agreed timelines.',
                'smart_specific' => 'Mark and return CAT/assignment scripts with written feedback.',
                'smart_measurable' => 'At least 90% of marked items returned within 14 days of submission.',
                'smart_achievable' => 'Using current marking load and academic calendar.',
                'smart_relevant' => 'Supports learner success and academic quality standards.',
                'smart_timebound' => 'Throughout the quarter; review at quarter end.',
                'target_date' => $cycle->period_end->toDateString(),
                'weight' => 15,
            ]);

            $appraisal->refresh();
            $this->command?->info(sprintf(
                'Appraisal demo ready: %s for %s (manager %s) — /employee/appraisals/%d',
                $appraisal->appraisal_number,
                $user->email,
                $manager->fullName(),
                $appraisal->id
            ));
        });
    }
}
