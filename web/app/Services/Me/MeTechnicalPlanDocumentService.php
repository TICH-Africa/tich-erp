<?php

namespace App\Services\Me;

use App\Models\Me\MeQuarter;
use App\Models\Me\MeTechnicalPlan;
use App\Services\PrintDocumentService;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Turns a department technical plan into the signed paper document M&E files, either
 * one quarter at a time or the whole year.
 */
class MeTechnicalPlanDocumentService
{
    public const VIEW = 'monitoring-evaluation.documents.technical-plan';

    public function __construct(
        protected PrintDocumentService $printDocuments,
    ) {}

    /**
     * @param  int|string  $quarter  1-4 or 'all'
     */
    public function page(MeTechnicalPlan $plan, int|string $quarter = 'all'): View
    {
        return $this->printDocuments->render(self::VIEW, $this->data($plan, $quarter));
    }

    public function download(MeTechnicalPlan $plan, int|string $quarter = 'all'): StreamedResponse
    {
        $data = $this->data($plan, $quarter);

        return $this->printDocuments->downloadPdf(self::VIEW, $data, $data['filename']);
    }

    /**
     * @return array<string, mixed>
     */
    public function data(MeTechnicalPlan $plan, int|string $quarter): array
    {
        $plan->loadMissing(['department', 'outputs', 'quarters', 'submitter', 'meReviewer']);

        $quarterRows = $plan->quartersByNumber();
        $selected = MeQuarter::resolveQuarterFilter($quarter);
        $tagged = $plan->usesQuarterTags();

        $sections = [];
        $totalPlanned = 0.0;

        if ($tagged) {
            foreach ($selected as $number) {
                $rows = $plan->outputs->filter(fn ($output) => (int) $output->quarter === $number)->values();
                $row = $quarterRows[$number] ?? null;

                $sections[] = [
                    'quarter' => $number,
                    'title' => MeQuarter::titleForQuarter($number),
                    'period' => $row?->periodLabel() ?? '',
                    'state' => $row?->statusLabel() ?? 'Not filed',
                    'reviewer' => $row?->reviewer?->printedName() ?? '',
                    'reviewed_at' => $row?->reviewed_at?->format('d M Y') ?? '',
                    'review_notes' => $row?->review_notes ?? '',
                    'rows' => $rows,
                    'planned_total' => round((float) $rows->sum('planned'), 2),
                ];

                $totalPlanned += (float) $rows->sum('planned');
            }
        } else {
            $rows = $plan->outputs;

            $sections[] = [
                'quarter' => 0,
                'title' => 'Plan outputs',
                'period' => '',
                'state' => \App\Support\StatusTone::label($plan->status),
                'reviewer' => $plan->meReviewer?->printedName() ?? '',
                'reviewed_at' => $plan->me_reviewed_at?->format('d M Y') ?? '',
                'review_notes' => (string) $plan->me_notes,
                'rows' => $rows,
                'planned_total' => round((float) $rows->sum('planned'), 2),
            ];

            $totalPlanned = (float) $rows->sum('planned');
        }

        return [
            'plan' => $plan,
            'sections' => $sections,
            'totalPlanned' => round($totalPlanned, 2),
            'quarterScope' => $tagged ? implode(' / ', array_map(fn ($n) => 'Q'.$n, $selected)) : 'Full year',
            'documentTitle' => 'Department Technical Plan',
            'documentSubtitle' => $plan->department?->dept_name.' · '.($plan->fiscal_year ?? '-'),
            'documentRef' => $this->printDocuments->documentRef('TP', $plan->department?->dept_code ?? 'DEPT', $plan->id),
            'filename' => 'technical-plan-'.Str::slug(collect([
                $plan->department?->dept_code,
                $plan->fiscal_year,
                $selected === [1, 2, 3, 4] ? 'all' : implode('-', $selected),
            ])->filter()->implode('-')).'.pdf',
            'showSignatures' => true,
            'signatureLabels' => [
                'Head of Department - signature & stamp',
                'Monitoring and Evaluation Officer - signature & date',
            ],
        ];
    }
}
