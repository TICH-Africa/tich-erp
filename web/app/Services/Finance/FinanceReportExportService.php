<?php

namespace App\Services\Finance;

use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceReportExportService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function downloadExcel(string $report, array $data): StreamedResponse
    {
        $filename = str_replace('_', '-', $report).'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($report, $data) {
            $this->writeCsv($report, $data);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function inlineExcel(string $report, array $data): StreamedResponse
    {
        $filename = str_replace('_', '-', $report).'-'.now()->format('Ymd-His').'.csv';

        return response()->stream(function () use ($report, $data) {
            $this->writeCsv($report, $data);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function writeCsv(string $report, array $data): void
    {
        $handle = fopen('php://output', 'w');
        fwrite($handle, "\xEF\xBB\xBF");

        foreach ($this->rowsForReport($report, $data) as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<list<string|float|int|null>>
     */
    private function rowsForReport(string $report, array $data): array
    {
        return match ($report) {
            'trial_balance' => $this->trialBalanceRows($data),
            'balance_sheet' => $this->balanceSheetRows($data),
            'income_statement' => $this->incomeStatementRows($data),
            'cashflow' => $this->cashflowRows($data),
            'general_ledger' => $this->generalLedgerRows($data),
            'ar_aging' => $this->arAgingRows($data),
            'ap_aging' => $this->apAgingRows($data),
            'payroll_summary' => $this->payrollSummaryRows($data),
            'finance_audit' => $this->financeAuditRows($data),
            'reconciliation' => $this->reconciliationRows($data),
            default => $this->trialBalanceRows($data),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<list<string|float|int|null>>
     */
    private function trialBalanceRows(array $data): array
    {
        $rows = [
            ['Trial Balance', 'As at '.$data['as_at']],
            [],
            ['Account Code', 'Account Name', 'Account Type', 'Debit (KES)', 'Credit (KES)'],
        ];

        foreach ($data['rows'] as $row) {
            $rows[] = [
                $row['account_code'],
                $row['account_name'],
                ucfirst($row['account_type']),
                $row['debit'] > 0 ? $row['debit'] : '',
                $row['credit'] > 0 ? $row['credit'] : '',
            ];
        }

        $rows[] = [];
        $rows[] = ['', '', 'Totals', $data['total_debit'], $data['total_credit']];

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<list<string|float|int|null>>
     */
    private function balanceSheetRows(array $data): array
    {
        $rows = [
            ['Balance Sheet', 'As at '.$data['as_at']],
            [],
            ['Section', 'Account Code', 'Account Name', 'Account Only', 'Amount (KES)'],
        ];

        foreach ($data['sections'] as $section) {
            foreach ($section['rows'] as $row) {
                $rows[] = [
                    $section['title'],
                    $row['account_code'],
                    str_repeat('    ', (int) ($row['level'] ?? 0)).$row['account_name'],
                    ($row['level'] ?? 0) > 0 || ($row['is_group'] ?? false) ? $row['own_amount'] : '',
                    $row['amount'],
                ];
            }

            $rows[] = ['', '', 'Total '.$section['title'], '', $section['total']];
            $rows[] = [];
        }

        $rows[] = ['', '', 'Total Assets', '', $data['total_assets']];
        $rows[] = ['', '', 'Total Liabilities + Equity', '', $data['total_liabilities_equity']];

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<list<string|float|int|null>>
     */
    private function incomeStatementRows(array $data): array
    {
        $rows = [
            ['Statement of Comprehensive Income', $data['period_label'] ?? ''],
            [],
            ['Section', 'Account Code', 'Account Name', 'Account Only', 'Amount (KES)'],
        ];

        foreach ($data['revenue']['rows'] as $row) {
            $rows[] = ['Revenue', $row['account_code'], str_repeat('    ', (int) ($row['level'] ?? 0)).$row['account_name'], $this->ownAmount($row), $row['amount']];
        }
        $rows[] = ['', '', 'Total Revenue', '', $data['revenue']['total']];
        $rows[] = [];

        foreach ($data['expenses']['rows'] as $row) {
            $rows[] = ['Expenses', $row['account_code'], str_repeat('    ', (int) ($row['level'] ?? 0)).$row['account_name'], $this->ownAmount($row), $row['amount']];
        }
        $rows[] = ['', '', 'Total Expenses', '', $data['expenses']['total']];
        $rows[] = [];
        if (($data['view_mode'] ?? 'standard') === 'full') {
            foreach (($data['sections'] ?? []) as $section) {
                foreach ($section['rows'] as $row) {
                    $rows[] = [$section['title'], $row['account_code'], str_repeat('    ', (int) ($row['level'] ?? 0)).$row['account_name'], $this->ownAmount($row), $row['amount']];
                }
                $rows[] = ['', '', 'Total '.$section['title'], '', $section['total']];
                $rows[] = [];
            }
        }

        $rows[] = ['', '', 'Net Income', '', $data['net_income']];

        return $rows;
    }

    /**
     * A child account shows its own movement; a parent shows what sits directly on it.
     *
     * @param  array<string, mixed>  $row
     * @return string|float
     */
    private function ownAmount(array $row): string|float
    {
        return ($row['level'] ?? 0) > 0 || ($row['is_group'] ?? false)
            ? round((float) ($row['own_amount'] ?? 0.0), 2)
            : '';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<list<string|float|int|null>>
     */
    private function cashflowRows(array $data): array
    {
        $rows = [
            ['Statement of Cash Flows', $data['period_label']],
            [],
            ['Activity', 'Description', 'Amount (KES)'],
        ];

        foreach ($data['sections'] as $section) {
            foreach ($section['rows'] as $row) {
                $rows[] = [$section['title'], $row['label'], $row['amount']];
            }

            $rows[] = ['', 'Net cash from '.$section['title'], $section['total']];
            $rows[] = [];
        }

        $rows[] = ['', 'Net change in cash', $data['net_change_in_cash']];
        $rows[] = ['', 'Closing cash balance', $data['closing_cash_balance']];

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<list<string|float|int|null>>
     */
    private function generalLedgerRows(array $data): array
    {
        $rows = [
            ['General Ledger', $data['period_label']],
            [],
            ['Date', 'Transaction', 'Debit Account', 'Credit Account', 'Amount (KES)', 'Narration', 'Reference'],
        ];

        foreach ($data['rows'] as $row) {
            $rows[] = [
                $row['ledger_date_display'],
                ucwords($row['transaction_type']),
                $row['debit_account_code'].' '.$row['debit_account_name'],
                $row['credit_account_code'].' '.$row['credit_account_name'],
                $row['amount'],
                $row['narration'],
                $row['reference_id'],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<list<string|float|int|null>>
     */
    private function arAgingRows(array $data): array
    {
        $rows = [
            ['Accounts Receivable Ageing', 'As at '.$data['as_at']],
            ['Total outstanding', $data['total_outstanding'], 'Invoices', $data['invoice_count']],
            [],
            ['Bucket', 'Invoices', 'Outstanding (KES)'],
        ];

        foreach ($data['buckets'] as $bucket) {
            $rows[] = [$bucket['label'], $bucket['count'], $bucket['total']];
        }

        $rows[] = [];
        $rows[] = ['Invoice', 'Student', 'Registration', 'Due', 'Days', 'Bucket', 'Balance (KES)', 'Status'];

        foreach ($data['rows'] as $row) {
            $rows[] = [
                $row['invoice_number'],
                $row['student_name'],
                $row['registration_number'],
                $row['due_date'],
                $row['days_past_due'],
                $row['bucket_label'],
                $row['balance'],
                $row['status'],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<list<string|float|int|null>>
     */
    private function apAgingRows(array $data): array
    {
        $rows = [
            ['Accounts Payable Ageing', 'As at '.$data['as_at']],
            ['Total outstanding', $data['total_outstanding'], 'Vendor invoices', $data['invoice_count'] ?? 0, 'Suppliers', $data['vendor_count']],
            [],
            ['Bucket', 'Invoices', 'Outstanding (KES)'],
        ];

        foreach ($data['buckets'] as $bucket) {
            $rows[] = [$bucket['label'], $bucket['count'], $bucket['total']];
        }

        $rows[] = [];
        $rows[] = ['Invoice', 'Supplier', 'Invoiced', 'Due', 'Days', 'Bucket', 'Total (KES)', 'Balance (KES)', 'Payment', '3-way match'];

        foreach ($data['rows'] as $row) {
            $rows[] = [
                $row['invoice_number'],
                $row['supplier_name'],
                $row['invoice_date'],
                $row['due_date'],
                $row['days_past_due'],
                $row['bucket_label'],
                $row['total_amount'],
                $row['balance'],
                $row['payment_status'],
                $row['three_way_match_status'],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<list<string|float|int|null>>
     */
    private function payrollSummaryRows(array $data): array
    {
        $rows = [
            ['Institutional Payroll Summary', $data['period_label']],
            [],
            ['Run', 'Period', 'Status', 'Staff', 'Gross', 'Net', 'PAYE', 'Posted'],
        ];

        foreach ($data['rows'] as $row) {
            $rows[] = [
                $row['run_number'],
                $row['period'],
                $row['status'],
                $row['staff_count'],
                $row['total_gross'],
                $row['total_net'],
                $row['total_paye'],
                $row['posted_at'],
            ];
        }

        $rows[] = [];
        $rows[] = ['Totals', '', '', $data['totals']['staff'] ?? 0, $data['totals']['gross'] ?? 0, $data['totals']['net'] ?? 0, $data['totals']['paye'] ?? 0, ''];

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<list<string|float|int|null>>
     */
    private function financeAuditRows(array $data): array
    {
        $rows = [
            ['Finance Audit Trail', $data['period_label']],
            [],
            ['Timestamp', 'Action', 'Entity', 'Entity ID', 'User', 'Status', 'Reason'],
        ];

        foreach ($data['rows'] as $row) {
            $rows[] = [
                $row['created_at'],
                $row['action'],
                $row['entity_type'],
                $row['entity_id'],
                $row['user_email'],
                $row['status'],
                $row['reason'],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<list<string|float|int|null>>
     */
    private function reconciliationRows(array $data): array
    {
        $rows = [
            [$data['title'] ?? 'Bank & Cash Reconciliation', $data['period_label']],
            [],
            ['Opening balance', '', '', number_format($data['opening_balance'], 2)],
            ['', 'Money received', '', number_format($data['received']['total'], 2)],
            ['', 'Money paid out', '', number_format($data['paid_out']['total'], 2)],
            ['', 'Net movement', '', number_format($data['net_movement'], 2)],
            ['', 'Closing balance', '', number_format($data['closing_balance'], 2)],
            [],
        ];

        $rows[] = ['BY ACCOUNT'];
        $rows[] = ['Account', 'Received', 'Paid out', 'Transfers in', 'Transfers out', 'Closing'];

        foreach ($data['received']['accounts'] as $account) {
            $rows[] = [
                $account['account_code'].' '.$account['account_name'],
                number_format($account['received'], 2),
                number_format($account['paid_out'], 2),
                number_format($account['transfers_in'], 2),
                number_format($account['transfers_out'], 2),
                number_format($account['closing_balance'], 2),
            ];
        }
        $rows[] = [];

        foreach (['received' => 'RECEIVED BY TYPE', 'paid_out' => 'PAID OUT BY TYPE'] as $key => $heading) {
            $rows[] = [$heading];

            foreach ($data[$key]['sources'] as $type => $summary) {
                $rows[] = ['', $type, 'Count: '.$summary['count'], number_format($summary['total'], 2)];
            }

            $rows[] = ['', 'Total '.$key, '', number_format($data[$key]['total'], 2)];
            $rows[] = [];
        }

        foreach ($data['differences'] ?? [] as $difference) {
            $rows[] = [
                'DIFFERENCE',
                $difference['label'],
                'Recorded: '.number_format($difference['recorded'], 2).' | Posted: '.number_format($difference['posted'], 2),
                number_format($difference['difference'], 2),
            ];
        }

        if (! empty($data['differences'])) {
            $rows[] = [];
        }

        $rows[] = ['BANK AND CASH MOVEMENTS'];
        $rows[] = ['Date', 'Account', 'Movement', 'Type', 'Narration', 'Reference', 'Received (KES)', 'Paid out (KES)', 'Transfer (KES)'];

        foreach ($data['rows'] as $row) {
            $rows[] = [
                $row['date_display'],
                $row['account_code'],
                $row['movement'],
                $row['type'],
                $row['narration'],
                $row['reference'],
                $row['received'] != 0 ? number_format($row['received'], 2) : '',
                $row['paid_out'] != 0 ? number_format($row['paid_out'], 2) : '',
                $row['transfer'] != 0 ? number_format($row['transfer'], 2) : '',
            ];
        }

        return $rows;
    }
}
