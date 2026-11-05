<?php

namespace App\Services\Me;

use App\Models\Me\MeQuarterlyReport;
use App\Models\Me\MeTechnicalPlan;
use Illuminate\Support\Collection;

class MeQuarterlyReportWorkspaceService
{
    /**
     * Rows for the M&E dashboard: one plan per department (latest reportable plan).
     *
     * @return Collection<int, array{
     *     plan: MeTechnicalPlan,
     *     quarters: array<int, array{
     *         quarter: \App\Models\Me\MeQuarter|null,
     *         reportable: bool,
     *         report: MeQuarterlyReport|null
     *     }>
     * }>
     */
    public function rows(): Collection
    {
        $plans = MeTechnicalPlan::query()
            ->with(['department', 'quarters', 'quarterlyReports.quarter'])
            ->where(function ($query) {
                $query->where('status', 'baseline_locked')
                    ->orWhereHas('quarters', fn ($q) => $q->where('status', 'me_approved'));
            })
            ->orderBy('department_id')
            ->orderByDesc('id')
            ->get()
            ->unique('department_id')
            ->values();

        return $plans->map(function (MeTechnicalPlan $plan) {
            $reportsByQuarterId = $plan->quarterlyReports->keyBy('quarter_id');
            $quarters = [];

            foreach ([1, 2, 3, 4] as $number) {
                $quarter = $plan->quarters->firstWhere('quarter_number', $number);
                $reportable = $plan->isQuarterReportable($number);
                $report = $quarter ? $reportsByQuarterId->get($quarter->id) : null;

                $quarters[$number] = [
                    'quarter' => $quarter,
                    'reportable' => $reportable,
                    'report' => $report,
                ];
            }

            return [
                'plan' => $plan,
                'quarters' => $quarters,
            ];
        });
    }
}
