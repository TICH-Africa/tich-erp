<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinanceReportExportService;
use App\Services\Finance\FinanceReportService;
use App\Services\Finance\LedgerService;
use App\Services\PrintDocumentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LedgerController extends Controller
{
    private const REPORTS = [
        'trial_balance',
        'balance_sheet',
        'income_statement',
        'cashflow',
        'general_ledger',
    ];

    public function __construct(
        protected LedgerService $ledger,
        protected FinanceReportService $reports,
        protected FinanceReportExportService $exports,
        protected PrintDocumentService $printDocuments,
    ) {}

    public function index(): View
    {
        return view('finance.ledger.index', [
            'entries' => $this->ledger->recentEntries(100),
            'balances' => $this->ledger->accountBalances(),
            'mainAccount' => config('finance.main_treasury_account'),
        ]);
    }

    public function reports(Request $request): View
    {
        $report = $this->resolveReport($request);

        return view('finance.ledger.reports', [
            'report' => $report,
            'reportData' => $this->reports->build($report),
            'reportTitle' => $this->reports->title($report),
        ]);
    }

    public function viewPdf(Request $request): Response
    {
        $report = $this->resolveReport($request);

        return $this->printDocuments->inlinePdf(
            'finance.reports.print',
            $this->printPayload($report),
            str_replace('_', '-', $report).'-'.now()->format('Ymd').'.pdf',
        );
    }

    public function exportPdf(Request $request): StreamedResponse
    {
        $report = $this->resolveReport($request);

        return $this->printDocuments->downloadPdf(
            'finance.reports.print',
            $this->printPayload($report),
            str_replace('_', '-', $report).'-'.now()->format('Ymd').'.pdf',
        );
    }

    public function viewExcel(Request $request): View
    {
        $report = $this->resolveReport($request);
        $reportData = $this->reports->build($report);

        return $this->printDocuments->render('finance.reports.spreadsheet', [
            'report' => $report,
            'reportData' => $reportData,
            'reportTitle' => $this->reports->title($report),
            'documentTitle' => $this->reports->title($report),
            'documentSubtitle' => $this->reportSubtitle($reportData),
            'documentRef' => $this->printDocuments->documentRef('FIN', strtoupper($report)),
            'backUrl' => route('finance.reports.index', ['report' => $report]),
            'pdfViewUrl' => route('finance.reports.view.pdf', ['report' => $report]),
            'pdfDownloadUrl' => route('finance.reports.export.pdf', ['report' => $report]),
            'excelDownloadUrl' => route('finance.reports.export.excel', ['report' => $report]),
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $report = $this->resolveReport($request);

        return $this->exports->downloadExcel($report, $this->reports->build($report));
    }

    private function resolveReport(Request $request): string
    {
        $report = $request->string('report')->toString() ?: 'trial_balance';

        abort_unless(in_array($report, self::REPORTS, true), 404, 'Unknown financial report.');

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    private function printPayload(string $report): array
    {
        $reportData = $this->reports->build($report);

        return [
            'report' => $report,
            'reportData' => $reportData,
            'reportTitle' => $this->reports->title($report),
            'documentTitle' => $this->reports->title($report),
            'documentSubtitle' => $this->reportSubtitle($reportData),
            'documentRef' => $this->printDocuments->documentRef('FIN', strtoupper($report)),
            'paperOrientation' => 'portrait',
        ];
    }

    /**
     * @param  array<string, mixed>  $reportData
     */
    private function reportSubtitle(array $reportData): string
    {
        return $reportData['period_label']
            ?? ('As at '.($reportData['as_at'] ?? now()->format('d M Y')));
    }
}
