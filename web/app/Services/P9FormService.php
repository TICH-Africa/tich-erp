<?php

namespace App\Services;

use App\Models\PayrollItem;
use App\Models\Staff;
use App\Models\StatutoryDeduction;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class P9FormService
{
    private const MONTHS = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    private const EMPLOYER_NAME = 'Tropical Institute of Community Health Development, (TICH)';
    private const EMPLOYER_PIN = 'P051129554G';
    private const PERSONAL_RELIEF_MONTHLY = 2400;

    public function generate(Staff $staff, int $year): Spreadsheet
    {
        $monthlyData = $this->getMonthlyPayrollData($staff, $year);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("P9A {$year}");

        $this->buildHeader($sheet, $staff, $year);
        $this->buildColumnHeaders($sheet);
        $this->buildMonthlyRows($sheet, $monthlyData);
        $this->buildTotalsRow($sheet, $monthlyData);
        $this->buildFooter($sheet, $monthlyData);
        $this->applyStyles($sheet);

        return $spreadsheet;
    }

    public function download(Staff $staff, int $year): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = $this->generate($staff, $year);
        $filename = "P9A_{$year}_{$staff->employee_number}_{$staff->surname}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function staffWithPayrollData(int $year): \Illuminate\Database\Eloquent\Collection
    {
        $staffIds = PayrollItem::query()
            ->where('pay_period_year', $year)
            ->distinct()
            ->pluck('staff_id');

        return Staff::query()
            ->whereIn('id', $staffIds)
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Prefer the latest year that has payroll items; fall back to the current calendar year.
     */
    public function defaultYear(): int
    {
        $latest = PayrollItem::query()->max('pay_period_year');

        return $latest ? (int) $latest : (int) now()->year;
    }

    /**
     * @return list<int>
     */
    public function availableYears(): array
    {
        $fromPayroll = PayrollItem::query()
            ->distinct()
            ->orderByDesc('pay_period_year')
            ->pluck('pay_period_year')
            ->map(fn ($y) => (int) $y)
            ->all();

        $fallback = range((int) now()->year, (int) now()->year - 5);

        return array_values(array_unique(array_merge($fromPayroll, $fallback)));
    }

    public function getMonthlyData(Staff $staff, int $year): array
    {
        return $this->getMonthlyPayrollData($staff, $year);
    }

    private function getMonthlyPayrollData(Staff $staff, int $year): array
    {
        $items = PayrollItem::query()
            ->where('staff_id', $staff->id)
            ->where('pay_period_year', $year)
            ->with('statutoryDeductions')
            ->orderBy('pay_period_month')
            ->get();

        $data = [];
        foreach (self::MONTHS as $month => $name) {
            $item = $items->firstWhere('pay_period_month', $month);
            if ($item) {
                $snapshot = $item->calculation_snapshot ?? [];
                $paye = $this->getDeductionAmount($item, 'paye');
                $ahl = $this->getDeductionAmount($item, 'ahl');
                $shif = $this->getDeductionAmount($item, 'sha');
                $nssf = $this->getDeductionAmount($item, 'nssf');

                $basicSalary = (float) $item->basic_salary;
                $benefitsNonCash = (float) ($snapshot['benefits_non_cash'] ?? 0);
                $valueOfQuarters = (float) ($snapshot['value_of_quarters'] ?? 0);
                $totalGross = (float) $item->gross_salary;

                $pensionE1 = round($basicSalary * 0.30, 2);
                $pensionE2 = (float) ($snapshot['pension_actual'] ?? $nssf);
                $pensionE3 = min($pensionE1, $pensionE2, 30000);

                $prmf = (float) ($snapshot['prmf'] ?? 0);
                $ownerOccupiedInterest = (float) ($snapshot['owner_occupied_interest'] ?? 0);

                $totalDeductions = $pensionE3 + $ahl + $shif + $prmf + $ownerOccupiedInterest;
                $chargeablePay = max(0, $totalGross - $totalDeductions);
                $taxCharged = $paye + self::PERSONAL_RELIEF_MONTHLY;
                $insuranceRelief = (float) ($snapshot['insurance_relief'] ?? 0);
                $payeNet = $paye;

                $data[$month] = [
                    'basic_salary' => $basicSalary,
                    'benefits_non_cash' => $benefitsNonCash,
                    'value_of_quarters' => $valueOfQuarters,
                    'total_gross' => $totalGross,
                    'pension_e1' => $pensionE1,
                    'pension_e2' => $pensionE2,
                    'pension_e3' => $pensionE3,
                    'ahl' => $ahl,
                    'shif' => $shif,
                    'prmf' => $prmf,
                    'owner_occupied_interest' => $ownerOccupiedInterest,
                    'total_deductions' => $totalDeductions,
                    'chargeable_pay' => $chargeablePay,
                    'tax_charged' => $taxCharged,
                    'personal_relief' => self::PERSONAL_RELIEF_MONTHLY,
                    'insurance_relief' => $insuranceRelief,
                    'paye' => $payeNet,
                ];
            } else {
                $data[$month] = null;
            }
        }

        return $data;
    }

    private function getDeductionAmount(PayrollItem $item, string $type): float
    {
        $deduction = $item->statutoryDeductions->firstWhere('deduction_type', $type);
        return $deduction ? (float) $deduction->employee_amount : 0;
    }

    private function buildHeader(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, Staff $staff, int $year): void
    {
        $sheet->setCellValue('A2', 'ISO 9001:2015 CERTIFIED');
        $sheet->setCellValue('A3', 'APPENDIX P9A');
        $sheet->setCellValue('F3', "KENYA REVENUE AUTHORITY DOMESTIC TAXES DEPARTMENT\nTAX DEDUCTION CARD YEAR {$year}");
        $sheet->getStyle('F3')->getAlignment()->setWrapText(true);
        $sheet->setCellValue('N4', "Employer's PIN");
        $sheet->setCellValue('P4', self::EMPLOYER_PIN);
        $sheet->setCellValue('A5', "Employers Name");
        $sheet->setCellValue('C5', self::EMPLOYER_NAME);
        $sheet->setCellValue('A6', "Employee's Main Name");
        $sheet->setCellValue('C6', $staff->surname . ' ' . $staff->first_name);
        $sheet->setCellValue('N6', "Employee's PIN");
        $sheet->setCellValue('P6', $staff->kra_pin ?? '');
        $sheet->setCellValue('A7', "Employee's Other Names");
        $sheet->setCellValue('C7', $staff->other_names ?? '');
    }

    private function buildColumnHeaders(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        $row = 8;
        $headers = [
            'A' => 'MONTH',
            'B' => 'Basic Salary',
            'C' => "Benefits-\nNonCash",
            'D' => "Value of\nQuarters",
            'E' => "Total Gross\nPay",
            'F' => "Defined Contribution\nRetirement Scheme",
            'I' => "Affordable Housing\nLevy (AHL)",
            'J' => "Social Health\nInsurance Fund (SHIF)",
            'K' => "Post Retirement\nMedical Fund (PRMF)",
            'L' => "Owner-Occupied\nInterest",
            'M' => "Total\nDeductions",
            'N' => "Chargeable\nPay",
            'O' => "Tax\nCharged",
            'P' => "Personal\nRelief",
            'Q' => "Insurance\nRelief",
            'R' => "PAYE Tax",
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}{$row}", $label);
            $sheet->getStyle("{$col}{$row}")->getAlignment()->setWrapText(true);
        }

        $row9 = $row + 1;
        foreach (['B','C','D','E','F','I','J','K','L','M','N','O','P','Q','R'] as $col) {
            $sheet->setCellValue("{$col}{$row9}", 'Kshs.');
        }

        $sheet->setCellValue("B11", "A");
        $sheet->setCellValue("C11", "B");
        $sheet->setCellValue("D11", "C");
        $sheet->setCellValue("E11", "D");
        $sheet->setCellValue("F11", "E");
        $sheet->setCellValue("F12", "E1");
        $sheet->setCellValue("G12", "E2");
        $sheet->setCellValue("H12", "E3");
        $sheet->setCellValue("I11", "F");
        $sheet->setCellValue("J11", "G");
        $sheet->setCellValue("K11", "H");
        $sheet->setCellValue("L11", "I");
        $sheet->setCellValue("M11", "J");
        $sheet->setCellValue("N11", "K");
        $sheet->setCellValue("O11", "L");
        $sheet->setCellValue("P11", "M");
        $sheet->setCellValue("Q11", "N");
        $sheet->setCellValue("R11", "O");

        $sheet->setCellValue("F14", "30% of A");
        $sheet->setCellValue("G14", "Actual");
        $sheet->setCellValue("H13", "Fixed");
        $sheet->setCellValue("H14", "30,000 p.m");
    }

    private function buildMonthlyRows(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $monthlyData): void
    {
        $startRow = 15;
        foreach (self::MONTHS as $month => $name) {
            $row = $startRow + $month - 1;
            $sheet->setCellValue("A{$row}", $name);

            $data = $monthlyData[$month] ?? null;
            if ($data) {
                $sheet->setCellValue("B{$row}", $data['basic_salary']);
                $sheet->setCellValue("C{$row}", $data['benefits_non_cash']);
                $sheet->setCellValue("D{$row}", $data['value_of_quarters']);
                $sheet->setCellValue("E{$row}", $data['total_gross']);
                $sheet->setCellValue("F{$row}", $data['pension_e1']);
                $sheet->setCellValue("G{$row}", $data['pension_e2']);
                $sheet->setCellValue("H{$row}", $data['pension_e3']);
                $sheet->setCellValue("I{$row}", $data['ahl']);
                $sheet->setCellValue("J{$row}", $data['shif']);
                $sheet->setCellValue("K{$row}", $data['prmf']);
                $sheet->setCellValue("L{$row}", $data['owner_occupied_interest']);
                $sheet->setCellValue("M{$row}", $data['total_deductions']);
                $sheet->setCellValue("N{$row}", $data['chargeable_pay']);
                $sheet->setCellValue("O{$row}", $data['tax_charged']);
                $sheet->setCellValue("P{$row}", $data['personal_relief']);
                $sheet->setCellValue("Q{$row}", $data['insurance_relief']);
                $sheet->setCellValue("R{$row}", $data['paye']);
            } else {
                foreach (range('B', 'R') as $col) {
                    if ($col <= 'R') {
                        $sheet->setCellValue("{$col}{$row}", 0);
                    }
                }
            }
        }
    }

    private function buildTotalsRow(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $monthlyData): void
    {
        $totalsRow = 27;
        $sheet->setCellValue("A{$totalsRow}", 'TOTAL');

        $fields = ['basic_salary','benefits_non_cash','value_of_quarters','total_gross',
            'pension_e1','pension_e2','pension_e3','ahl','shif','prmf',
            'owner_occupied_interest','total_deductions','chargeable_pay',
            'tax_charged','personal_relief','insurance_relief','paye'];
        $cols = ['B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R'];

        foreach ($fields as $i => $field) {
            $total = 0;
            foreach ($monthlyData as $data) {
                if ($data) {
                    $total += $data[$field];
                }
            }
            $sheet->setCellValue("{$cols[$i]}{$totalsRow}", $total);
        }

        $sheet->getStyle("A{$totalsRow}:R{$totalsRow}")->getFont()->setBold(true);
    }

    private function buildFooter(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $monthlyData): void
    {
        $totalChargeable = 0;
        $totalPaye = 0;
        foreach ($monthlyData as $data) {
            if ($data) {
                $totalChargeable += $data['chargeable_pay'];
                $totalPaye += $data['paye'];
            }
        }

        $sheet->setCellValue('A29', 'TOTAL CHARGEABLE PAY (COL. K) Kshs.');
        $sheet->setCellValue('D29', $totalChargeable);
        $sheet->setCellValue('N29', 'TOTAL TAX (COL. O)');
        $sheet->setCellValue('O29', $totalPaye);

        $sheet->setCellValue('A31', 'IMPORTANT');
        $sheet->setCellValue('A32', '1. Use P9A');
        $sheet->setCellValue('B32', '(a) For all liable employees and where director/employee received Benefits in addition to cash emoluments');
        $sheet->setCellValue('B33', '(b) Where an employee is eligible to deduction on owner occupier interest.');
        $sheet->setCellValue('A34', '(c) Where an employee contributes to a post retirement medical fund');
        $sheet->setCellValue('A35', '2.  (a) Deductible interest must not exceed Kshs. 30,000/=');
        $sheet->setCellValue('A36', '(b) Deductible pension contribution must not exceed Kshs. 30,000/=');
        $sheet->setCellValue('A37', '(c) Deductible contribution to a post retirement medical fund must not exceed Kshs.15,000/=');
        $sheet->setCellValue('A38', '(d) Deductible Contribution to SHIF and AHL are effective December 2024');
        $sheet->setCellValue('A39', '(e) Personal Relief is Kshs. 2400 per Month or 28,800 per year');
        $sheet->setCellValue('A40', '(f) Insurance Relief is 15% of the Premium up to a Maximum of Kshs. 5,000 per month or Kshs. 60,000 per year');
        $sheet->setCellValue('A41', 'P9A');
    }

    private function applyStyles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        $sheet->getStyle('A8:R8')->getFont()->setBold(true);
        $sheet->getStyle('A8:R8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A8:R8')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(8)->setRowHeight(36);

        $sheet->getStyle('B15:R27')->getNumberFormat()
            ->setFormatCode('#,##0');
        $sheet->getStyle('B15:R27')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle('A3')->getFont()->setBold(true);
        $sheet->getStyle('A5:A7')->getFont()->setBold(true);
        $sheet->getStyle('N4')->getFont()->setBold(true);
        $sheet->getStyle('N6')->getFont()->setBold(true);
        $sheet->getStyle('A31')->getFont()->setBold(true);

        $sheet->getStyle('A8:R27')->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // Fixed widths — autoSize balloons columns from long header/footer text.
        $widths = [
            'A' => 12,
            'B' => 11,
            'C' => 10,
            'D' => 10,
            'E' => 11,
            'F' => 10,
            'G' => 9,
            'H' => 9,
            'I' => 10,
            'J' => 10,
            'K' => 10,
            'L' => 10,
            'M' => 10,
            'N' => 11,
            'O' => 10,
            'P' => 9,
            'Q' => 9,
            'R' => 10,
        ];

        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setAutoSize(false);
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Keep long notes readable without stretching columns.
        $sheet->getStyle('A31:R41')->getAlignment()->setWrapText(true);
        $sheet->mergeCells('F3:M3');
        $sheet->mergeCells('C5:L5');
        $sheet->mergeCells('C6:L6');
        $sheet->mergeCells('C7:L7');
        $sheet->mergeCells('B32:M32');
        $sheet->mergeCells('B33:M33');
        $sheet->mergeCells('A34:M34');
        $sheet->mergeCells('A35:M35');
        $sheet->mergeCells('A36:M36');
        $sheet->mergeCells('A37:M37');
        $sheet->mergeCells('A38:M38');
        $sheet->mergeCells('A39:M39');
        $sheet->mergeCells('A40:M40');
    }
}
