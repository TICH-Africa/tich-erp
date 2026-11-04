<?php

namespace App\Services\Me;

use App\Models\Department;
use App\Models\Me\MeQuarter;
use App\Models\Me\MeQuarterlyReport;
use App\Models\Me\MeTechnicalPlan;
use App\Services\PrintDocumentService;
use App\Support\StatusTone;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Turns the standardised quarterly grid into the signed report the department files
 * with M&E, one quarter at a time or the whole year in one document.
 */
class MeQuarterlyReportDocumentService
{
    public const VIEW = 'monitoring-evaluation.documents.quarterly-report';

    public function __construct(
        protected PrintDocumentService $printDocuments,
    ) {}

    public function page(MeQuarterlyReport $report): View
    {
        return $this->printDocuments->render(self::VIEW, $this->single($report));
    }

    public function download(MeQuarterlyReport $report): StreamedResponse
    {
        $data = $this->single($report);

        return $this->printDocuments->downloadPdf(self::VIEW, $data, $data['filename']);
    }

    /**
     * @param  int|string  $quarter  1-4 or 'all'
     */
    public function planPage(MeTechnicalPlan $plan, int|string $quarter = 'all'): View
    {
        return $this->printDocuments->render(self::VIEW, $this->forPlan($plan, $quarter));
    }

    public function planDownload(MeTechnicalPlan $plan, int|string $quarter = 'all'): StreamedResponse
    {
        $data = $this->forPlan($plan, $quarter);

        return $this->printDocuments->downloadPdf(self::VIEW, $data, $data['filename']);
    }

    /**
     * @return array<string, mixed>
     */
    private function single(MeQuarterlyReport $report): array
    {
        $report->loadMissing(['technicalPlan', 'quarter']);

        return $this->payload(
            $report->technicalPlan,
            $report->department,
            [$this->block($report)],
            [$report->quarter?->quarter_number ? 'Q'.$report->quarter->quarter_number : 'Report'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function forPlan(MeTechnicalPlan $plan, int|string $quarter): array
    {
        $plan->loadMissing(['department', 'quarters']);
        $numbers = MeQuarter::resolveQuarterFilter($quarter);

        $blocks = [];
        $scope = [];

        foreach ($numbers as $number) {
            $report = $plan->quarterlyReports()
                ->whereHas('quarter', fn ($q) => $q->where('quarter_number', $number))
                ->with('lines')
                ->first();

            if (! $report) {
                continue;
            }

            $blocks[] = $this->block($report);
            $scope[] = 'Q'.$number;
        }

        if ($blocks === []) {
            throw new \RuntimeException('No quarterly report has been filed for this technical plan yet.');
        }

        return $this->payload($plan, $plan->department, $blocks, $scope);
    }

    /**
     * @return array<string, mixed>
     */
    private function block(MeQuarterlyReport $report): array
    {
        $report->loadMissing(['lines', 'quarter', 'submitter', 'meVerifier', 'ceoReviewer']);

        return [
            'report' => $report,
            'title' => $report->quarter ? MeQuarter::titleForQuarter((int) $report->quarter->quarter_number) : 'Quarterly report',
            'period' => $report->quarter?->periodLabel() ?? '',
            'status' => \App\Support\StatusTone::label($report->status),
            'lines' => $report->lines,
            'planned' => round($report->totalPlanned(), 2),
            'achieved' => round($report->totalAchieved(), 2),
            'deviation' => round($report->totalAchieved() - $report->totalPlanned(), 2),
            'rate' => $report->achievementRate(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @param  list<string>  $scope
     * @return array<string, mixed>
     */
    private function payload(
        ?MeTechnicalPlan $plan,
        ?Department $department,
        array $blocks,
        array $scope,
    ): array {
        $first = $blocks[0]['report'];

        return [
            'report' => $first,
            'reports' => $blocks,
            'plan' => $plan,
            'department' => $department ?? $first->department,
            'quarterScope' => implode(' / ', $scope),
            'documentTitle' => 'Quarterly M&E Report',
            'documentSubtitle' => ($department?->dept_name ?? '-').' · '.($plan?->title ?? '-'),
            'documentRef' => $this->printDocuments->documentRef(
                'QR',
                $department?->dept_code ?? 'DEPT',
                $first->quarter?->label() ?? $first->id,
            ),
            'filename' => 'quarterly-report-'.Str::slug(collect([
                $department?->dept_code,
                $plan?->fiscal_year,
                count($scope) === 4 ? 'all' : implode('-', $scope),
            ])->filter()->implode('-')).'.pdf',
            'showSignatures' => true,
            'signatureLabels' => [
                'Officer preparing this report - signature & date',
                'Head of Department - signature & stamp',
                'Monitoring and Evaluation Officer - signature & date',
            ],
        ];
    }
}
