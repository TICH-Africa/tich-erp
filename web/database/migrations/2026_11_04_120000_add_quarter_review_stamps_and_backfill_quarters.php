<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('me_quarters')) {
            return;
        }

        Schema::table('me_quarters', function (Blueprint $table) {
            if (! Schema::hasColumn('me_quarters', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('status');
            }
            if (! Schema::hasColumn('me_quarters', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
            if (! Schema::hasColumn('me_quarters', 'review_notes')) {
                $table->text('review_notes')->nullable()->after('reviewed_at');
            }
        });

        $this->backfillQuarterRows();
    }

    public function down(): void
    {
        if (! Schema::hasTable('me_quarters')) {
            return;
        }

        Schema::table('me_quarters', function (Blueprint $table) {
            $columns = array_filter(
                ['review_notes', 'reviewed_at', 'reviewed_by'],
                fn ($c) => Schema::hasColumn('me_quarters', $c)
            );
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    /**
     * Plans that already carry quarter-tagged outputs need their quarter rows so M&E
     * can approve them one quarter at a time instead of waiting for the whole year.
     */
    private function backfillQuarterRows(): void
    {
        $startOfYear = (string) now()->startOfYear()->toDateString();

        $plans = DB::table('me_technical_plans')
            ->select('id', 'status', 'fiscal_year')
            ->whereIn('status', ['me_review', 'returned', 'me_approved', 'baseline_locked'])
            ->get();

        foreach ($plans as $plan) {
            $year = preg_match('/(\d{4})/', (string) $plan->fiscal_year, $m) ? (int) $m[1] : (int) now()->year;

            for ($quarter = 1; $quarter <= 4; $quarter++) {
                $hasOutputs = DB::table('me_plan_outputs')
                    ->where('technical_plan_id', $plan->id)
                    ->where('quarter', $quarter)
                    ->exists();

                $status = match (true) {
                    in_array($plan->status, ['baseline_locked', 'me_approved'], true) => 'me_approved',
                    $hasOutputs => 'me_review',
                    default => 'open',
                };

                $exists = DB::table('me_quarters')
                    ->where('technical_plan_id', $plan->id)
                    ->where('quarter_number', $quarter)
                    ->exists();

                if ($exists) {
                    if ($status === 'me_approved') {
                        DB::table('me_quarters')
                            ->where('technical_plan_id', $plan->id)
                            ->where('quarter_number', $quarter)
                            ->update(['status' => 'me_approved']);
                    }

                    continue;
                }

                DB::table('me_quarters')->insert([
                    'technical_plan_id' => $plan->id,
                    'quarter_number' => $quarter,
                    'period_start' => date('Y-m-d', strtotime("{$year}-".(($quarter - 1) * 3 + 1).'-01')),
                    'period_end' => date('Y-m-d', strtotime("{$year}-".($quarter * 3).'-01 +1 month -1 day')),
                    'status' => $status,
                    'created_at' => $startOfYear,
                ]);
            }
        }
    }
};
