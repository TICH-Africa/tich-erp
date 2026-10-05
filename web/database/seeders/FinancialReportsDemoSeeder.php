<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\ChartOfAccount;
use App\Models\Student;
use App\Services\AuditService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Builds a full trading history so every financial statement has something to report:
 * a parent/child chart of accounts, twelve months of balanced journal entries, student
 * receivables across every ageing bucket, supplier payables, posted payroll runs and a
 * finance audit trail.
 *
 * Re-running is a no-op: the guard looks for the marker narration on the ledger.
 */
class FinancialReportsDemoSeeder extends Seeder
{
    private const MARKER = 'Demo financial history';

    private const CHART = [
        ['1030', 'Petty Cash', 'asset', '1000'],
        ['1100.01', 'Tuition Receivables', 'asset', '1100'],
        ['1100.02', 'Hostel and Accommodation Receivables', 'asset', '1100'],
        ['1100.03', 'Sponsor and Bursary Receivables', 'asset', '1100'],
        ['1200', 'Prepayments and Deposits', 'asset', null],
        ['1200.01', 'Supplier Prepayments', 'asset', '1200'],
        ['1300', 'Staff Advances and Prepayments', 'asset', null],
        ['1300.01', 'Staff Advances', 'asset', '1300'],
        ['1400', 'Inventory', 'asset', null],
        ['1400.01', 'Books and Stationery Stock', 'asset', '1400'],
        ['1400.02', 'Laboratory Supplies Stock', 'asset', '1400'],
        ['1500', 'Property, Plant and Equipment', 'asset', null],
        ['1500.01', 'Computers and IT Equipment', 'asset', '1500'],
        ['1500.02', 'Furniture and Fittings', 'asset', '1500'],
        ['1500.03', 'Motor Vehicles', 'asset', '1500'],
        ['1510', 'Land and Buildings', 'asset', null],
        ['1510.01', 'Main Campus Building', 'asset', '1510'],
        ['1590', 'Accumulated Depreciation', 'asset', null],
        ['1590.01', 'Accumulated Depreciation - Computers', 'asset', '1590'],
        ['1590.02', 'Accumulated Depreciation - Furniture', 'asset', '1590'],
        ['2000.01', 'Trade Payables', 'liability', '2000'],
        ['2100', 'Statutory Deductions Payable', 'liability', null],
        ['2110', 'PAYE Payable', 'liability', '2100'],
        ['2120', 'NSSF Payable', 'liability', '2100'],
        ['2130', 'SHA Payable', 'liability', '2100'],
        ['2140', 'AHL Payable', 'liability', '2100'],
        ['2200', 'Accrued Expenses', 'liability', null],
        ['2200.01', 'Accrued Rent', 'liability', '2200'],
        ['2200.02', 'Accrued Utilities', 'liability', '2200'],
        ['2300', 'Deferred Income', 'liability', null],
        ['2300.01', 'Fees Received in Advance', 'liability', '2300'],
        ['3000.01', 'Opening Balance Equity', 'equity', '3000'],
        ['3000.02', 'Prior Year Surplus', 'equity', '3000'],
        ['4000.01', 'Day School Tuition', 'revenue', '4000'],
        ['4000.02', 'Weekend Programme Tuition', 'revenue', '4000'],
        ['4000.03', 'Distance Learning Tuition', 'revenue', '4000'],
        ['4050', 'Grants and Donations', 'revenue', null],
        ['4050.01', 'Donor Restricted Grants', 'revenue', '4050'],
        ['4050.02', 'Government Capitation', 'revenue', '4050'],
        ['4090.01', 'Hostel and Accommodation Income', 'revenue', '4090'],
        ['4090.02', 'Transport and Other Fees', 'revenue', '4090'],
        ['5100', 'Utilities', 'expense', '5000'],
        ['5100.01', 'Electricity', 'expense', '5100'],
        ['5100.02', 'Water and Sewerage', 'expense', '5100'],
        ['5200', 'Rent and Rates', 'expense', '5000'],
        ['5200.01', 'Campus Rent', 'expense', '5200'],
        ['5200.02', 'Ground Rates and Levies', 'expense', '5200'],
        ['5300', 'Supplies and Consumables', 'expense', '5000'],
        ['5300.01', 'Office Stationery', 'expense', '5300'],
        ['5300.02', 'Laboratory Consumables', 'expense', '5300'],
        ['5300.03', 'Cleaning and Sanitation', 'expense', '5300'],
        ['5400', 'Repairs and Maintenance', 'expense', '5000'],
        ['5400.01', 'Building Repairs', 'expense', '5400'],
        ['5400.02', 'Equipment Repairs', 'expense', '5400'],
        ['5500', 'Transport and Fuel', 'expense', '5000'],
        ['5600', 'Printing and Publicity', 'expense', '5000'],
        ['5700', 'Bank Charges', 'expense', '5000'],
        ['5800', 'Depreciation and Amortisation', 'expense', '5000'],
        ['5800.01', 'Depreciation - Computers', 'expense', '5800'],
        ['5800.02', 'Depreciation - Furniture', 'expense', '5800'],
        ['5900', 'Insurance', 'expense', '5000'],
        ['5910', 'Internet and Connectivity', 'expense', '5000'],
        ['5920', 'Security Services', 'expense', '5000'],
    ];

    private const SUPPLIERS = [
        ['SUP-DEMO-001', 'Nairobi Stationery Traders', '0722111001'],
        ['SUP-DEMO-002', 'Kenya Power (EPRA) Billing', '0722111002'],
        ['SUP-DEMO-003', 'Campus Catering Services', '0722111003'],
        ['SUP-DEMO-004', 'Bright Security Guards', '0722111004'],
        ['SUP-DEMO-005', 'Medi Supplies Limited', '0722111005'],
    ];

    private ?int $staffId = null;

    /**
     * @var \Illuminate\Support\Collection<int, int>
     */
    private $staffIds;

    public function run(): void
    {
        if (DB::table('account_ledger')->where('narration', 'like', self::MARKER.'%')->exists()) {
            $this->command?->info('Financial report demo history already present, skipping.');

            return;
        }

        mt_srand(20261002);

        $this->seedChartHierarchy();
        $this->staffId = $this->resolveStaff();

        $payrollRuns = $this->seedPayrollRuns();
        $this->seedLedgerHistory($payrollRuns);
        $this->seedStudentReceivables();
        $this->seedSupplierPayables();
        $this->seedAuditTrail();

        $this->command?->info('Financial report demo data seeded.');
    }

    private function seedChartHierarchy(): void
    {
        foreach (self::CHART as [$code, $name, $type, $parent]) {
            if (ChartOfAccount::query()->where('account_code', $code)->exists()) {
                continue;
            }

            ChartOfAccount::query()->create([
                'account_code' => $code,
                'account_name' => $name,
                'account_type' => $type,
                'currency' => 'KES',
                'parent_account_code' => $parent,
                'is_active' => 1,
                'is_system_account' => 0,
            ]);
        }
    }

    private function resolveStaff(): int
    {
        $financeDepartmentId = DB::table('departments')->where('dept_code', 'FIN')->value('id');

        $staffIds = DB::table('staff')
            ->when($financeDepartmentId, fn ($query) => $query->where('department_id', $financeDepartmentId))
            ->orderBy('id')
            ->limit(6)
            ->pluck('id');

        if ($staffIds->count() < 6) {
            $staffIds = $staffIds->merge(
                DB::table('staff')->whereNotIn('id', $staffIds->all())->orderBy('id')->limit(6 - $staffIds->count())->pluck('id')
            );
        }

        if ($staffIds->isEmpty()) {
            $staffIds = collect([$this->createFallbackStaff()]);
        }

        $this->staffIds = $staffIds->values();

        return (int) $this->staffIds->first();
    }

    private function createFallbackStaff(): int
    {
        $departmentId = (int) (DB::table('departments')->where('dept_code', 'FIN')->value('id')
            ?? DB::table('departments')->orderBy('id')->value('id'));

        return (int) DB::table('staff')->insertGetId([
            'employee_number' => 'EMP-FIN-900',
            'title' => 'Mr.',
            'first_name' => 'Peter',
            'surname' => 'Kamau',
            'date_of_birth' => '1985-08-20',
            'gender' => 'male',
            'email' => 'peter.kamau@tich.ac.ke',
            'primary_email' => 'peter.kamau@tich.ac.ke',
            'organisation_email' => 'peter.kamau@tich.ac.ke',
            'phone_number' => '0722000201',
            'department_id' => $departmentId,
            'job_title' => 'Finance Manager',
            'employment_category' => 'permanent',
            'employment_start_date' => now()->subYears(4)->toDateString(),
            'employment_status' => 'active',
            'gross_monthly_salary' => 105000,
            'is_teaching_staff' => 0,
            'created_at' => now()->subYears(4),
        ]);
    }

    /**
     * Posted payroll runs for the last twelve months, so the payroll summary and the
     * salary expense lines have matching figures.
     *
     * @return list<array<string, mixed>>
     */
    private function seedPayrollRuns(): array
    {
        $staffIds = $this->staffIds->take(6);

        $runs = [];
        $month = now()->subMonths(11)->startOfMonth();

        for ($index = 0; $index < 12; $index++, $month = $month->copy()->addMonth()) {
            $gross = round(620000 + ($index % 4) * 35000 + mt_rand(0, 18000), 2);
            $paye = round($gross * 0.24, 2);
            $nssf = round($gross * 0.10, 2);
            $sha = round($gross * 0.05, 2);
            $ahl = round($gross * 0.03, 2);
            $employer = round($gross * 0.12, 2);
            $net = round($gross - $paye - $nssf - $sha - $ahl, 2);
            $runNumber = sprintf('PR-%s-0001', $month->format('Ym'));

            if (DB::table('payroll_runs')->where('run_number', $runNumber)->exists()) {
                continue;
            }

            $staffCount = max(1, $staffIds->count());

            $runId = DB::table('payroll_runs')->insertGetId([
                'run_number' => $runNumber,
                'pay_period_year' => $month->year,
                'pay_period_month' => $month->month,
                'status' => 'posted',
                'staff_count' => $staffCount,
                'total_gross' => $gross,
                'total_deductions' => round($paye + $nssf + $sha + $ahl, 2),
                'total_net' => $net,
                'total_paye' => $paye,
                'total_nssf' => $nssf,
                'total_sha' => $sha,
                'total_ahl' => $ahl,
                'total_employer_cost' => $gross + $employer,
                'notes' => 'Demo payroll run for financial statement reporting',
                'created_by' => $this->staffId,
                'approved_by' => $this->staffId,
                'approved_at' => $month->copy()->endOfMonth()->toDateTimeString(),
                'posted_by' => $this->staffId,
                'posted_at' => $month->copy()->endOfMonth()->toDateTimeString(),
                'gl_reference' => $runNumber,
            ]);

            $perStaff = round($gross / $staffCount, 2);

            foreach ($staffIds->values() as $position => $staffId) {
                $this->seedPayrollItem((int) $runId, (int) $staffId, $month, $position, $perStaff);
            }

            $runs[] = [
                'month_key' => $month->format('Y-m'),
                'gross' => $gross,
                'paye' => $paye,
                'nssf' => $nssf,
                'sha' => $sha,
                'ahl' => $ahl,
                'employer' => $employer,
                'net' => $net,
                'staff_count' => $staffCount,
            ];
        }

        return $runs;
    }

    private function seedPayrollItem(int $runId, int $staffId, Carbon $month, int $position, float $gross): void
    {
        $payslip = sprintf('PS-%s-%03d', $month->format('Ym'), $position + 1);

        if (DB::table('payroll_items')->where('payslip_number', $payslip)->exists()) {
            return;
        }

        $paye = round($gross * 0.24, 2);
        $nssf = round($gross * 0.10, 2);
        $sha = round($gross * 0.05, 2);
        $ahl = round($gross * 0.03, 2);

        $itemId = DB::table('payroll_items')->insertGetId([
            'payroll_run_id' => $runId,
            'payslip_number' => $payslip,
            'staff_id' => $staffId,
            'pay_period_year' => $month->year,
            'pay_period_month' => $month->month,
            'basic_salary' => round($gross * 0.7, 2),
            'gross_salary' => $gross,
            'total_allowances' => round($gross * 0.3, 2),
            'total_deductions' => round($paye + $nssf + $sha + $ahl, 2),
            'net_salary' => round($gross - $paye - $nssf - $sha - $ahl, 2),
            'calculation_snapshot' => json_encode([
                'basic' => round($gross * 0.7, 2),
                'allowances' => round($gross * 0.3, 2),
                'paye' => $paye,
                'nssf' => $nssf,
                'sha' => $sha,
                'ahl' => $ahl,
            ]),
            'is_processed' => 1,
            'processed_at' => $month->copy()->endOfMonth()->toDateTimeString(),
            'is_approved' => 1,
            'approved_by' => $this->staffId,
            'approved_at' => $month->copy()->endOfMonth()->toDateTimeString(),
            'is_disbursed' => 1,
            'disbursement_date' => $month->copy()->endOfMonth()->toDateString(),
            'created_at' => $month->copy()->endOfMonth()->toDateTimeString(),
        ]);

        foreach ([
            ['paye', $paye, 24.0],
            ['nssf', $nssf, 10.0],
            ['sha', $sha, 5.0],
            ['ahl', $ahl, 3.0],
        ] as [$type, $amount, $rate]) {
            DB::table('statutory_deductions')->insert([
                'payroll_item_id' => $itemId,
                'staff_id' => $staffId,
                'deduction_type' => $type,
                'gross_salary_for_deduction' => $gross,
                'deduction_rate' => $rate,
                'employee_amount' => $amount,
                'employer_amount' => $type === 'paye' ? 0 : round($amount * 0.4, 2),
                'is_remitted' => 1,
                'remittance_date' => $month->copy()->addMonth()->startOfMonth()->addDays(11)->toDateString(),
                'remittance_reference' => 'REM-'.strtoupper($type).'-'.$month->format('Ym'),
                'created_at' => $month->copy()->endOfMonth()->toDateTimeString(),
            ]);
        }
    }

    /**
     * Twelve months of balanced journal entries plus the opening balances that make the
     * balance sheet close.
     *
     * @param  list<array<string, mixed>>  $payrollRuns
     */
    private function seedLedgerHistory(array $payrollRuns): void
    {
        $first = now()->subMonths(11)->startOfMonth();

        $this->seedOpeningBalances($first->copy()->subDay());

        $revenueLines = [
            ['1100.01', '4000.01', 'Semester charges - day school', 68000, 98000],
            ['1100.01', '4000.02', 'Semester charges - weekend programme', 52000, 76000],
            ['1100.01', '4000.03', 'Semester charges - distance learning', 41000, 61000],
            ['1100.01', '4010', 'Application fee', 1000, 2500],
            ['1100.01', '4020', 'Examination fee', 2800, 6500],
            ['1100.01', '4030', 'Graduation fee', 3500, 4500],
            ['1100.02', '4090.01', 'Hostel and accommodation', 15000, 32000],
            ['1100.03', '4090.02', 'Transport and other fees', 1200, 5400],
        ];

        $month = $first->copy();

        for ($index = 0; $index < 12; $index++, $month = $month->copy()->addMonth()) {
            $this->seedFeePostings($month, $index, $revenueLines);
            $this->seedOperatingExpenses($month, $index);
            $this->seedPayrollPostings($month, $payrollRuns);
            $this->seedCapitalAndAccruals($month, $index);
            $this->seedGrants($month, $index);
        }

        $this->seedMispostReversal();
    }

    private function seedOpeningBalances(string $date): void
    {
        $debits = [
            '1030' => 250000.00,
            '1020' => 4500000.00,
            '1500.01' => 3200000.00,
            '1500.02' => 2100000.00,
            '1510.01' => 45000000.00,
        ];

        $credits = [
            '1590.01' => 1400000.00,
            '1590.02' => 700000.00,
            '2100' => 850000.00,
            '2200.01' => 600000.00,
            '2200.02' => 180000.00,
            '2300.01' => 750000.00,
        ];

        $credits['3000.01'] = round(array_sum($debits) - array_sum($credits), 2);

        foreach ($debits as $code => $amount) {
            $this->post($date, 'journal_entry', $code, '3000.01', $amount, self::MARKER.': opening balance '.$code, 'other');
        }

        foreach ($credits as $code => $amount) {
            $this->post($date, 'journal_entry', '3000.01', $code, $amount, self::MARKER.': opening balance '.$code, 'other');
        }
    }

    /**
     * @param  list<array<int, mixed>>  $revenueLines
     */
    private function seedFeePostings(Carbon $month, int $index, array $revenueLines): void
    {
        $invoices = min(6 + ($index % 4), 12);

        for ($i = 0; $i < $invoices; $i++) {
            [$receivable, $revenue, $description, $min, $max] = $revenueLines[($index + $i) % count($revenueLines)];
            $amount = round(mt_rand($min, $max), -1);
            $issueDay = mt_rand(1, 24);
            $date = $month->copy()->addDays($issueDay)->toDateString();

            $this->post($date, 'invoice_raised', $receivable, $revenue, $amount, self::MARKER.': invoice raised - '.$description, 'student_fees');

            if ($i % 3 === 0) {
                continue;
            }

            $settlement = $i % 4 === 0 ? round($amount * (mt_rand(35, 70) / 100), -1) : $amount;
            $paymentDate = $month->copy()->addDays(min($issueDay + mt_rand(2, 12), $month->daysInMonth))->toDateString();
            $cash = ['1010', '1020', '1030'][(($index + $i) % 3)];

            $this->post($paymentDate, 'student_payment', $cash, $receivable, $settlement, self::MARKER.': fee payment received', 'student_fees');
        }

        if ($index % 3 === 0) {
            $this->post(
                $month->copy()->addDays(24)->toDateString(),
                'credit_memo',
                '4000.01',
                '1100.01',
                round(mt_rand(6000, 22000), -2),
                self::MARKER.': fee waiver and credit note',
                'student_fees'
            );
        }
    }

    private function seedOperatingExpenses(Carbon $month, int $index): void
    {
        $operating = [
            ['5100.01', 120000, 260000, 'Electricity bill'],
            ['5100.02', 45000, 95000, 'Water and sewerage'],
            ['5300.01', 35000, 78000, 'Office stationery and printing'],
            ['5300.02', 60000, 140000, 'Laboratory consumables'],
            ['5300.03', 40000, 90000, 'Cleaning and sanitation'],
            ['5400.01', $index % 4 === 1 ? 180000 : 0, 320000, 'Building repairs'],
            ['5400.02', $index % 5 === 2 ? 90000 : 0, 190000, 'Equipment repairs'],
            ['5500', 85000, 165000, 'Transport and fuel'],
            ['5600', 25000, 70000, 'Printing and publicity'],
            ['5700', 18000, 46000, 'Bank charges and commissions'],
            ['5910', 55000, 95000, 'Internet and connectivity'],
            ['5920', 120000, 180000, 'Security services'],
        ];

        foreach ($operating as [$code, $min, $max, $description]) {
            if ($max <= 0) {
                continue;
            }

            $amount = round(mt_rand($min, $max), -2);

            $this->post(
                $month->copy()->addDays(mt_rand(3, 26))->toDateString(),
                'invoice_raised',
                $code,
                '2000.01',
                $amount,
                self::MARKER.': '.$description,
                'procurement'
            );

            $paidDate = $month->copy()->addDays(mt_rand(8, 27))->toDateString();

            $this->post(
                $paidDate,
                'supplier_payment',
                '2000.01',
                '1020',
                $amount,
                self::MARKER.': settlement of '.$description,
                'procurement'
            );
        }

        if ($index % 12 === 0) {
            $this->post(
                $month->copy()->addDays(5)->toDateString(),
                'invoice_raised',
                '5900',
                '2000.01',
                420000,
                self::MARKER.': annual insurance premium',
                'procurement'
            );

            $this->post(
                $month->copy()->addDays(20)->toDateString(),
                'supplier_payment',
                '2000.01',
                '1020',
                420000,
                self::MARKER.': insurance premium paid',
                'procurement'
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $payrollRuns
     */
    private function seedPayrollPostings(Carbon $month, array $payrollRuns): void
    {
        $run = collect($payrollRuns)->firstWhere('month_key', $month->format('Y-m'));

        if (! $run) {
            return;
        }

        $endOfMonth = $month->copy()->endOfMonth();

        $this->post($endOfMonth->toDateString(), 'payroll_disbursement', '5000', '2100', $run['gross'], self::MARKER.': payroll gross - '.$endOfMonth->format('M Y'), 'payroll');
        $this->post($endOfMonth->toDateString(), 'payroll_disbursement', '5000', '2100', $run['employer'], self::MARKER.': employer statutory cost - '.$endOfMonth->format('M Y'), 'payroll');

        $remittance = $month->copy()->addMonth()->startOfMonth()->addDays(11);

        foreach ([['2110', 'paye', $run['paye']], ['2120', 'nssf', $run['nssf']], ['2130', 'sha', $run['sha']], ['2140', 'ahl', $run['ahl']]] as [$code, $label, $amount]) {
            $this->post($endOfMonth->toDateString(), 'payroll_disbursement', '2100', $code, $amount, self::MARKER.': '.$label.' withheld', 'payroll');
            $this->post($remittance->toDateString(), 'statutory_remittance', $code, '1020', $amount, self::MARKER.': '.$label.' remitted', 'payroll');
        }

        $this->post($endOfMonth->copy()->subDays(1)->toDateString(), 'payroll_disbursement', '2100', '1020', $run['net'], self::MARKER.': net pay disbursed', 'payroll');
    }

    private function seedCapitalAndAccruals(Carbon $month, int $index): void
    {
        $capital = [
            0 => [['1500.01', 1250000, 'Laptops and projectors'], ['1500.02', 640000, 'Classroom furniture']],
            3 => [['1500.01', 780000, 'Laboratory computers']],
            6 => [['1500.02', 420000, 'Library shelving and desks']],
            8 => [['1500.01', 560000, 'Networking equipment']],
            10 => [['1500.03', 3200000, 'Institution bus']],
        ];

        foreach ($capital[$index] ?? [] as [$code, $amount, $description]) {
            $this->post($month->copy()->addDays(14)->toDateString(), 'invoice_raised', $code, '2000.01', $amount, self::MARKER.': capital purchase - '.$description, 'procurement');
            $this->post($month->copy()->addDays(25)->toDateString(), 'supplier_payment', '2000.01', '1020', $amount, self::MARKER.': payment for '.$description, 'procurement');
        }

        $this->post($month->copy()->addDays(28)->toDateString(), 'journal_entry', '5800.01', '1590.01', round(mt_rand(68000, 84000), -2), self::MARKER.': depreciation charge - computers', 'other');
        $this->post($month->copy()->addDays(28)->toDateString(), 'journal_entry', '5800.02', '1590.02', round(mt_rand(32000, 41000), -2), self::MARKER.': depreciation charge - furniture', 'other');

        $rent = round(mt_rand(850000, 1200000), -3);
        $utilities = round(mt_rand(165000, 340000), -2);

        $this->post($month->copy()->addDays(27)->toDateString(), 'journal_entry', '5200.01', '2200.01', $rent, self::MARKER.': rent accrued', 'other');
        $this->post($month->copy()->addDays(27)->toDateString(), 'journal_entry', '5100.01', '2200.02', $utilities, self::MARKER.': utilities accrued', 'other');

        $this->post($month->copy()->addDays(18)->toDateString(), 'journal_entry', '1300.01', '1020', round(mt_rand(90000, 240000), -2), self::MARKER.': staff advance issued', 'other');
        $this->post($month->copy()->addDays(26)->toDateString(), 'journal_entry', '5500', '1300.01', round(mt_rand(60000, 150000), -2), self::MARKER.': staff advance recovered', 'other');

        if ($index % 2 === 0) {
            $this->post($month->copy()->addDays(22)->toDateString(), 'journal_entry', '1200.01', '1020', round(mt_rand(150000, 420000), -2), self::MARKER.': supplier deposit paid', 'procurement');
        }

        if ($index % 4 === 2) {
            $this->post($month->copy()->addDays(12)->toDateString(), 'student_payment', '1020', '2300.01', round(mt_rand(400000, 1200000), -2), self::MARKER.': fees received in advance', 'student_fees');
        }
    }

    private function seedGrants(Carbon $month, int $index): void
    {
        if ($index % 3 !== 0) {
            return;
        }

        $this->post(
            $month->copy()->addDays(9)->toDateString(),
            'donor_disbursement',
            '1020',
            '4050.01',
            round(mt_rand(2500000, 8500000), -3),
            self::MARKER.': donor grant received',
            'donor'
        );

        $this->post(
            $month->copy()->addDays(19)->toDateString(),
            'donor_disbursement',
            '1020',
            '4050.02',
            round(mt_rand(1800000, 4200000), -3),
            self::MARKER.': government capitation received',
            'donor'
        );

        $this->post(
            $month->copy()->addDays(21)->toDateString(),
            'donor_disbursement',
            '5300.02',
            '4050.01',
            round(mt_rand(400000, 1200000), -3),
            self::MARKER.': donor funded laboratory supplies',
            'donor'
        );
    }

    private function seedMispostReversal(): void
    {
        $date = now()->subMonths(2)->copy()->day(21)->toDateString();

        $originalId = $this->post($date, 'student_payment', '1010', '1100.01', 145000, self::MARKER.': duplicate receipt posted in error', 'student_fees');
        $this->post($date, 'contra', '1100.01', '1010', 145000, self::MARKER.': reversal of duplicate receipt', 'student_fees');
        DB::table('account_ledger')->where('id', $originalId)->update(['is_reversed' => 1]);
    }

    /**
     * Open student invoices spread across the ageing buckets so the receivable report,
     * the trial balance and the general ledger all agree.
     */
    private function seedStudentReceivables(): void
    {
        $students = Student::query()->orderBy('id')->limit(60)->pluck('id');

        if ($students->isEmpty()) {
            $this->command?->warn('No students found: run the finance bulk demo seeder first to populate receivables.');

            return;
        }

        $academicYearId = (int) (AcademicYear::query()->orderByDesc('start_date')->value('id')
            ?? AcademicYear::query()->orderByDesc('id')->value('id'));

        if ($academicYearId === 0) {
            $this->command?->warn('No academic year found: skipping demo student receivables.');

            return;
        }

        $profiles = [
            ['1100.01', '4000.01', 'Semester charges - day school', 68000, 98000, 8, 'tuition'],
            ['1100.01', '4000.02', 'Semester charges - weekend programme', 52000, 76000, 22, 'tuition'],
            ['1100.02', '4090.01', 'Hostel and accommodation', 15000, 32000, 45, 'hostel'],
            ['1100.03', '4090.02', 'Transport and other fees', 1200, 5400, 75, 'other'],
            ['1100.01', '4020', 'Examination fee', 2800, 6500, 105, 'examination'],
        ];

        $studentAccounts = [];

        foreach ($students as $position => $studentId) {
            $accountId = $studentAccounts[$studentId] ??= (int) $this->resolveStudentAccount((int) $studentId, $academicYearId);

            foreach ([0, 1] as $slot) {
                [$receivable, $revenue, $description, $min, $max, $daysAfterDue, $invoiceType] = $profiles[($position + $slot) % count($profiles)];
                $amount = round(mt_rand($min, $max), -1);
                $dueDate = now()->subDays($daysAfterDue + mt_rand(0, 9));
                $issueDate = $dueDate->copy()->subDays(30);
                $invoiceNumber = 'INV-DEMO-'.$issueDate->format('Ym').'-'.str_pad((string) ($position * 2 + $slot + 1), 4, '0', STR_PAD_LEFT);

                if (DB::table('invoices')->where('invoice_number', $invoiceNumber)->exists()) {
                    continue;
                }

                $settlementRoll = ($position + $slot) % 10;
                [$paid, $status] = match (true) {
                    $settlementRoll < 4 => [$amount, 'paid'],
                    $settlementRoll < 6 => [round($amount * 0.5, -1), 'partial'],
                    $settlementRoll < 8 => [0.0, $dueDate->isPast() ? 'overdue' : 'issued'],
                    default => [round($amount * 0.25, -1), $dueDate->isPast() ? 'overdue' : 'partial'],
                };

                $balance = round($amount - $paid, 2);

                $invoiceId = DB::table('invoices')->insertGetId([
                    'invoice_number' => $invoiceNumber,
                    'student_account_id' => $accountId,
                    'student_id' => (int) $studentId,
                    'invoice_type' => $invoiceType,
                    'description' => $description,
                    'amount' => $amount,
                    'amount_paid' => $paid,
                    'balance' => $balance,
                    'issue_date' => $issueDate->toDateString(),
                    'due_date' => $dueDate->toDateString(),
                    'status' => $status,
                    'is_sent_to_portal' => 1,
                    'created_at' => $issueDate->copy()->toDateTimeString(),
                    'updated_at' => $issueDate->copy()->toDateTimeString(),
                ]);

                $this->post($issueDate->toDateString(), 'invoice_raised', $receivable, $revenue, $amount, self::MARKER.': invoice '.$invoiceNumber, 'student_fees');

                if ($paid <= 0) {
                    continue;
                }

                $paymentDate = $issueDate->copy()->addDays(mt_rand(3, 20));
                $paymentNumber = 'PAY-DEMO-'.str_pad((string) ($invoiceId), 5, '0', STR_PAD_LEFT);

                DB::table('payments')->insert([
                    'payment_number' => $paymentNumber,
                    'invoice_id' => $invoiceId,
                    'student_account_id' => $accountId,
                    'student_id' => (int) $studentId,
                    'payment_date' => $paymentDate->toDateString(),
                    'amount' => $paid,
                    'payment_method' => ['mpesa', 'bank_transfer', 'cash', 'helb'][$position % 4],
                    'payment_reference' => 'RCPT-'.strtoupper($paymentNumber),
                    'transaction_channel_ref' => 'FLMPESA'.mt_rand(100000, 999999),
                    'status' => 'SUCCESS',
                    'is_reconciled' => $position % 2,
                    'recorded_by' => $this->staffId,
                    'created_at' => $paymentDate->copy()->toDateTimeString(),
                ]);

                $this->post($paymentDate->toDateString(), 'student_payment', ['1010', '1020', '1030'][$position % 3], $receivable, $paid, self::MARKER.': payment '.$paymentNumber, 'student_fees');
            }
        }

        $this->refreshStudentAccountTotals();
    }

    private function resolveStudentAccount(int $studentId, int $academicYearId): int
    {
        $existing = DB::table('student_accounts')
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->value('id');

        if ($existing) {
            return (int) $existing;
        }

        return (int) DB::table('student_accounts')->insertGetId([
            'student_id' => $studentId,
            'academic_year_id' => $academicYearId,
            'total_chargeable' => 0,
            'total_paid' => 0,
            'outstanding_balance' => 0,
            'work_study_credit' => 0,
            'scholarship_amount' => 0,
            'helb_amount' => 0,
            'sponsor_amount' => 0,
            'credit_balance' => 0,
            'is_cleared' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function refreshStudentAccountTotals(): void
    {
        DB::statement(
            'UPDATE student_accounts sa
             SET sa.total_chargeable = (
                 SELECT COALESCE(SUM(i.amount), 0) FROM invoices i WHERE i.student_account_id = sa.id
             ),
             sa.total_paid = (
                 SELECT COALESCE(SUM(i.amount_paid), 0) FROM invoices i WHERE i.student_account_id = sa.id
             ),
             sa.outstanding_balance = (
                 SELECT COALESCE(SUM(i.balance), 0) FROM invoices i WHERE i.student_account_id = sa.id
             )'
        );
    }

    private function seedSupplierPayables(): void
    {
        $supplierIds = [];

        foreach (self::SUPPLIERS as $index => [$code, $name, $phone]) {
            $supplierId = DB::table('suppliers')->where('supplier_code', $code)->value('id');

            if (! $supplierId) {
                $supplierId = DB::table('suppliers')->insertGetId([
                    'supplier_code' => $code,
                    'supplier_name' => $name,
                    'contact_person' => $name.' Accounts Office',
                    'email' => 'accounts@'.str_replace(' ', '', strtolower($name)).'.co.ke',
                    'phone' => $phone,
                    'postal_address' => 'P.O. Box '.(40200 + $index),
                    'tax_compliance_status' => 'compliant',
                    'bank_name' => 'Co-operative Bank',
                    'bank_account_name' => $name.' Ltd',
                    'bank_account_number' => '100'.(200000 + $index),
                    'bank_branch' => 'Nairobi Main',
                    'bank_code' => '11000',
                    'is_active' => 1,
                    'created_at' => now()->subYear(),
                ]);
            }

            $supplierIds[] = (int) $supplierId;
        }

        $ages = [5, 5, 20, 20, 45, 45, 70, 105, 105];
        $descriptions = ['Stationery supply', 'Fuel and lubricants', 'Catering services', 'Guarding services', 'Medical supplies'];

        foreach ($ages as $index => $daysOld) {
            $invoiceNumber = 'AP-DEMO-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);

            if (DB::table('accounts_payable')->where('invoice_number', $invoiceNumber)->exists()) {
                continue;
            }

            $invoiceDate = now()->subDays($daysOld + 30);
            $dueDate = now()->subDays($daysOld);
            $amount = round(mt_rand(85000, 1450000), -2);
            $paid = $index % 3 === 0 ? 0.0 : round($amount * (mt_rand(40, 90) / 100), -2);
            $balance = round($amount - $paid, 2);
            $paymentStatus = $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');

            DB::table('accounts_payable')->insert([
                'invoice_number' => $invoiceNumber,
                'supplier_id' => $supplierIds[$index % count($supplierIds)],
                'invoice_date' => $invoiceDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'invoice_amount' => $amount,
                'tax_amount' => 0,
                'total_amount' => $amount,
                'amount_paid' => $paid,
                'balance' => $balance,
                'three_way_match_status' => $index % 3 === 2 ? 'pending' : 'approved',
                'finance_approval_status' => $index % 4 === 3 ? 'pending' : 'approved',
                'payment_status' => $paymentStatus,
                'payment_date' => $paid > 0 ? $dueDate->copy()->subDays(5)->toDateString() : null,
                'payment_reference' => $paid > 0 ? 'PMT-'.$invoiceNumber : null,
                'payment_method' => $paid > 0 ? 'bank_transfer' : null,
                'created_at' => $invoiceDate->copy()->toDateTimeString(),
                'updated_at' => now(),
            ]);

            if ($balance <= 0) {
                continue;
            }

            $this->post(
                $dueDate->copy()->subDays(25)->toDateString(),
                'invoice_raised',
                '2000.01',
                '5200.01',
                $amount,
                self::MARKER.': supplier invoice '.$invoiceNumber.' - '.$descriptions[$index % count($descriptions)],
                'procurement'
            );
        }
    }

    private function seedAuditTrail(): void
    {
        $audit = app(AuditService::class);

        $actions = [
            ['finance.invoice.created', 'invoices', 'Invoice raised from semester fees'],
            ['finance.invoice.sent', 'invoices', 'Invoice pushed to the student portal'],
            ['finance.payment.received', 'payments', 'Fee payment recorded via M-Pesa'],
            ['finance.payment.reconciled', 'payments', 'Bank reconciliation completed'],
            ['finance.ledger.posted', 'account_ledger', 'Journal entry posted to the general ledger'],
            ['finance.ledger.reversed', 'account_ledger', 'Misposted entry reversed'],
            ['finance.payroll.posted', 'payroll_runs', 'Payroll run posted to the general ledger'],
            ['finance.payroll.approved', 'payroll_runs', 'Payroll run approved for disbursement'],
            ['finance.credit_memo.issued', 'credit_memos', 'Credit note issued against an invoice'],
            ['finance.chart_of_accounts.updated', 'chart_of_accounts', 'Child account created under a parent account'],
            ['finance.chart_of_accounts.imported', 'chart_of_accounts', 'Chart of accounts imported from spreadsheet'],
            ['finance.budget.approved', 'finance_budgets', 'Department budget approved'],
            ['finance.disbursement.approved', 'donor_disbursements', 'Donor disbursement approved'],
            ['finance.purchase_order.approved', 'purchase_orders', 'Purchase order approved for payment'],
            ['finance.report.exported', 'financial_reports', 'Financial statement exported'],
        ];

        $created = 0;

        foreach ($actions as $round => [$action, $entityType, $reason]) {
            $repeats = 2 + ($round % 3);

            for ($i = 0; $i < $repeats; $i++) {
                $audit->log(
                    $action,
                    $entityType,
                    (string) (1000 + $created),
                    null,
                    ['demo' => true, 'round' => $round],
                    $reason
                );
                $created++;
            }
        }
    }

    private function post(
        string $date,
        string $transactionType,
        string $debitCode,
        string $creditCode,
        float $amount,
        string $narration,
        string $sourceModule,
        ?int $reversesId = null,
        bool $isReversal = false,
    ): int {
        return (int) DB::table('account_ledger')->insertGetId([
            'ledger_date' => $date,
            'transaction_type' => $transactionType,
            'debit_account_code' => $debitCode,
            'credit_account_code' => $creditCode,
            'debit_amount' => round($amount, 2),
            'credit_amount' => round($amount, 2),
            'narration' => $narration,
            'reference_table' => null,
            'reference_id' => null,
            'source_module' => $sourceModule,
            'is_reversed' => $reversesId !== null ? 1 : 0,
            'reversal_ledger_id' => $isReversal ? $reversesId : null,
            'recorded_by' => $this->staffId,
            'created_at' => now()->parse($date)->setTime(9, 0)->toDateTimeString(),
        ]);
    }
}
