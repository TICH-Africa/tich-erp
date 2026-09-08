<?php

namespace App\Services\Me;

use App\Models\Me\MeDepartmentHealthScore;
use App\Models\Me\MeQuarterlyReport;
use App\Models\Me\MeQuarterlyReportLine;
use App\Models\Qa\QaComplianceScore;
use Illuminate\Support\Facades\Schema;

class MeHealthScoreService
{
    public function recalculateForDepartment(int $departmentId, ?string $fiscalYear = null): MeDepartmentHealthScore
    {
        $qaAvg = null;
        if (Schema::hasTable('qa_compliance_scores')) {
            $qaQuery = QaComplianceScore::query()->where('department_id', $departmentId);
            $qaAvg = $qaQuery->avg('weighted_score');
            if ($qaAvg !== null) {
                $qaAvg = round((float) $qaAvg, 2);
            }
        }

        $lines = MeQuarterlyReportLine::query()
            ->whereHas('report', function ($q) use ($departmentId, $fiscalYear) {
                $q->where('department_id', $departmentId)
                    ->whereIn('status', ['me_verified', 'ceo_delivered'])
                    ->when($fiscalYear, function ($q2) use ($fiscalYear) {
                        $q2->whereHas('technicalPlan', fn ($p) => $p->where('fiscal_year', $fiscalYear));
                    });
            })
            ->get();

        $planned = (float) $lines->sum('planned');
        $achieved = (float) $lines->sum('achieved');
        $meAchievement = $planned > 0 ? round(($achieved / $planned) * 100, 2) : null;

        // Interlocked health: 50% QA compliance + 50% M&E achievement when both exist.
        if ($qaAvg !== null && $meAchievement !== null) {
            $health = round(($qaAvg * 0.5) + ($meAchievement * 0.5), 2);
        } elseif ($qaAvg !== null) {
            $health = $qaAvg;
        } elseif ($meAchievement !== null) {
            $health = $meAchievement;
        } else {
            $health = null;
        }

        $rating = $this->ratingFor($health);
        $yearKey = $fiscalYear ?: (string) date('Y');

        return MeDepartmentHealthScore::query()->updateOrCreate(
            [
                'department_id' => $departmentId,
                'fiscal_year' => $yearKey,
            ],
            [
                'qa_compliance_avg' => $qaAvg,
                'me_achievement_avg' => $meAchievement,
                'health_score' => $health,
                'health_rating' => $rating,
                'calculated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function recalculateAll(?string $fiscalYear = null): int
    {
        $deptIds = MeQuarterlyReport::query()
            ->distinct()
            ->pluck('department_id')
            ->all();

        if (Schema::hasTable('qa_compliance_scores')) {
            $deptIds = array_unique(array_merge(
                $deptIds,
                QaComplianceScore::query()->distinct()->pluck('department_id')->all()
            ));
        }

        $count = 0;
        foreach ($deptIds as $deptId) {
            $this->recalculateForDepartment((int) $deptId, $fiscalYear);
            $count++;
        }

        return $count;
    }

    protected function ratingFor(?float $score): ?string
    {
        if ($score === null) {
            return null;
        }
        if ($score >= 85) {
            return 'excellent';
        }
        if ($score >= 70) {
            return 'good';
        }
        if ($score >= 50) {
            return 'watch';
        }

        return 'critical';
    }
}
