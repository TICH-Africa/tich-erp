<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $plans = DB::table('me_technical_plans')
            ->where('status', 'baseline_locked')
            ->get(['id', 'department_id']);

        $quarters = DB::table('me_quarters')
            ->whereIn('quarter_number', [2, 3, 4])
            ->get(['id', 'technical_plan_id', 'quarter_number']);

        $existing = DB::table('me_quarterly_reports')
            ->pluck('id', 'quarter_id');

        $created = 0;
        foreach ($quarters as $quarter) {
            if (isset($existing[$quarter->id])) {
                continue;
            }

            $planId = $quarter->technical_plan_id;

            $outputs = DB::table('me_plan_outputs')
                ->where('technical_plan_id', $planId)
                ->get(['id', 'output', 'activity', 'costable_item', 'planned', 'planned_unit']);

            if ($outputs->isEmpty()) {
                continue;
            }

            $reportId = DB::table('me_quarterly_reports')->insertGetId([
                'technical_plan_id' => $planId,
                'quarter_id' => $quarter->id,
                'department_id' => DB::table('me_technical_plans')->where('id', $planId)->value('department_id'),
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($outputs as $i => $output) {
                $planned = round(((float) $output->planned) / 4, 2);
                DB::table('me_quarterly_report_lines')->insert([
                    'quarterly_report_id' => $reportId,
                    'plan_output_id' => $output->id,
                    'output' => $output->output,
                    'activity' => $output->activity,
                    'costable_item' => $output->costable_item,
                    'planned' => $planned,
                    'achieved' => 0,
                    'deviation' => 0 - $planned,
                    'display_order' => $i,
                ]);
            }

            $created++;
        }

        DB::table('migrations')->insert([
            ['migration' => '2026_09_14_000002_seed_q2_q4_draft_reports', 'batch' => 1],
        ]);
    }

    public function down(): void
    {
        DB::table('migrations')
            ->where('migration', '2026_09_14_000002_seed_q2_q4_draft_reports')
            ->delete();

        $reportIds = DB::table('me_quarterly_reports')
            ->join('me_quarters', 'me_quarterly_reports.quarter_id', '=', 'me_quarters.id')
            ->whereIn('me_quarters.quarter_number', [2, 3, 4])
            ->pluck('me_quarterly_reports.id');

        DB::table('me_quarterly_report_lines')
            ->whereIn('quarterly_report_id', $reportIds)
            ->delete();

        DB::table('me_quarterly_reports')
            ->whereIn('id', $reportIds)
            ->delete();
    }
};