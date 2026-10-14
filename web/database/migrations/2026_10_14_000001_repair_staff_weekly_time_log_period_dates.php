<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = 'staff_weekly_time_logs';
        if (! Schema::hasTable($table)) {
            return;
        }

        $hasPeriodStart = Schema::hasColumn($table, 'period_start');
        $hasPeriodEnd = Schema::hasColumn($table, 'period_end');

        if (! $hasPeriodStart || ! $hasPeriodEnd) {
            Schema::table($table, function (Blueprint $blueprint) use ($hasPeriodStart, $hasPeriodEnd) {
                if (! $hasPeriodStart) {
                    $blueprint->date('period_start')->nullable();
                }
                if (! $hasPeriodEnd) {
                    $blueprint->date('period_end')->nullable();
                }
            });
        }

        $columns = Schema::getColumnListing($table);
        $startSource = $this->firstExistingColumn($columns, ['week_start', 'start_date']);
        $endSource = $this->firstExistingColumn($columns, ['week_end', 'end_date']);

        if ($startSource !== null) {
            foreach (DB::table($table)->whereNull('period_start')->whereNotNull($startSource)->get(['id', $startSource]) as $row) {
                DB::table($table)->where('id', $row->id)->update([
                    'period_start' => $row->{$startSource},
                ]);
            }
        }

        if ($endSource !== null) {
            foreach (DB::table($table)->whereNull('period_end')->whereNotNull($endSource)->get(['id', $endSource]) as $row) {
                DB::table($table)->where('id', $row->id)->update([
                    'period_end' => $row->{$endSource},
                ]);
            }
        }

        $daysTable = 'staff_weekly_time_log_days';
        if (! Schema::hasTable($daysTable) || ! Schema::hasColumn($daysTable, 'in_month')) {
            return;
        }

        foreach (DB::table($table)
            ->whereNull('period_start')
            ->orWhereNull('period_end')
            ->get(['id']) as $row) {
            $bounds = DB::table($daysTable)
                ->where('weekly_time_log_id', $row->id)
                ->where('in_month', true)
                ->selectRaw('MIN(work_date) as period_start, MAX(work_date) as period_end')
                ->first();

            $updates = [];
            if ($bounds?->period_start !== null) {
                $updates['period_start'] = $bounds->period_start;
            }
            if ($bounds?->period_end !== null) {
                $updates['period_end'] = $bounds->period_end;
            }
            if ($updates !== []) {
                DB::table($table)->where('id', $row->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        // Columns may have existed before this conditional repair; dropping them is unsafe.
    }

    /**
     * @param  list<string>  $columns
     * @param  list<string>  $candidates
     */
    private function firstExistingColumn(array $columns, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }

        return null;
    }
};
