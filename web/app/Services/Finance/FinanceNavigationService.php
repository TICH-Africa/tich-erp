<?php

namespace App\Services\Finance;

use App\Models\Department;
use Illuminate\Support\Facades\Auth;

class FinanceNavigationService
{
    public function financeDepartment(): ?Department
    {
        return Department::query()
            ->where('dept_code', 'FIN')
            ->whereNull('parent_dept_id')
            ->first();
    }

    /**
     * @return array{department?: int}
     */
    public function departmentParams(): array
    {
        $department = request()->route('department');
        $departmentId = is_object($department) ? ($department->id ?? null) : $department;

        if ($departmentId) {
            return ['department' => (int) $departmentId];
        }

        $financeDepartment = $this->financeDepartment();

        return $financeDepartment ? ['department' => $financeDepartment->id] : [];
    }

    /**
     * @return list<array{label: string, icon: string, open: bool, active: bool, items: list<array<string, mixed>>}>
     */
    public function sidebarGroups(): array
    {
        $dept = $this->departmentParams();
        $groups = [];

        $studentItems = $this->studentFinanceItems($dept);
        if ($studentItems !== []) {
            $groups[] = [
                'label' => 'Student Finance',
                'icon' => 'users',
                'open' => request()->routeIs('finance.student-finance.*') || request()->routeIs('finance.student-accounts.*', 'finance.fee-structures.*', 'finance.invoices.*', 'finance.payments.*'),
                'active' => request()->routeIs('finance.student-finance.*') || request()->routeIs('finance.student-accounts.*', 'finance.fee-structures.*', 'finance.invoices.*', 'finance.payments.*'),
                'items' => $studentItems,
            ];
        }

        $groups[] = [
            'label' => 'Finance Records',
            'icon' => 'book-open',
            'open' => request()->routeIs('finance.records.*', 'finance.ledger.*', 'finance.reports.*', 'finance.ar.*', 'finance.ap.*', 'finance.gl.*', 'finance.budgeting.*', 'finance.projects-donors.*', 'finance.mpesa.*'),
            'active' => request()->routeIs('finance.records.*', 'finance.ledger.*', 'finance.reports.*', 'finance.ar.*', 'finance.ap.*', 'finance.gl.*', 'finance.budgeting.*', 'finance.projects-donors.*', 'finance.mpesa.*'),
            'items' => $this->financeRecordsItems($dept),
        ];

        if ($this->canAccessEmployeeFinance()) {
            $groups[] = [
                'label' => 'Employee Finance',
                'icon' => 'briefcase',
                'open' => request()->routeIs('finance.employee.*', 'finance.payroll-integration.*', 'hr.payroll.*'),
                'active' => request()->routeIs('finance.employee.*', 'finance.payroll-integration.*', 'hr.payroll.*'),
                'items' => $this->employeeFinanceItems($dept),
            ];
        }

        return $groups;
    }

    /**
     * @param  array{department?: int}  $dept
     * @return list<array<string, mixed>>
     */
    public function studentFinanceItems(array $dept): array
    {
        if ($dept === []) {
            return [];
        }

        return [
            $this->item('Overview', 'layout-grid', route('finance.student-finance.index', $dept), request()->routeIs('finance.student-finance.index')),
            $this->item('Student accounts', 'users', route('finance.student-finance.accounts.index', $dept), request()->routeIs('finance.student-finance.accounts.*')),
            $this->item('Fee structures', 'layers', route('finance.student-finance.fee-structures.index', $dept), request()->routeIs('finance.student-finance.fee-structures.*')),
            $this->item('Invoices', 'file-text', route('finance.student-finance.invoices.index', $dept), request()->routeIs('finance.student-finance.invoices.*')),
            $this->item('Payments', 'wallet', route('finance.student-finance.payments.index', $dept), request()->routeIs('finance.student-finance.payments.*')),
            $this->item('Receipts', 'receipt', route('finance.student-finance.receipts.index', $dept), request()->routeIs('finance.student-finance.receipts.*')),
            $this->item('Adjustments', 'percent', route('finance.student-finance.adjustments.index', $dept), request()->routeIs('finance.student-finance.adjustments.*')),
            $this->item('Installment plans', 'calendar', route('finance.student-finance.installment-plans.index', $dept), request()->routeIs('finance.student-finance.installment-plans.*')),
            $this->item('Refunds', 'refresh-cw', route('finance.student-finance.refunds.index', $dept), request()->routeIs('finance.student-finance.refunds.*')),
            $this->item('Clearance', 'check-circle', route('finance.student-finance.clearance.index', $dept), request()->routeIs('finance.student-finance.clearance.*')),
            $this->item('Milestones', 'flag', route('finance.student-finance.milestones.index', $dept), request()->routeIs('finance.student-finance.milestones.*')),
        ];
    }

    /**
     * @param  array{department?: int}  $dept
     * @return list<array<string, mixed>>
     */
    public function financeRecordsItems(array $dept): array
    {
        $items = [
            $this->item('Overview', 'layout-grid', route('finance.records.index'), request()->routeIs('finance.records.index')),
            $this->item('General ledger', 'book-open', route('finance.ledger.index'), request()->routeIs('finance.ledger.*')),
            $this->item('Financial reports', 'bar-chart', route('finance.reports.index'), request()->routeIs('finance.reports.*')),
        ];

        if ($dept !== []) {
            $items[] = $this->item('Accounts receivable', 'trending-up', route('finance.ar.index', $dept), request()->routeIs('finance.ar.*'));
            $items[] = $this->item('Credit memos', 'file-minus', route('finance.ar.credit-memos.index', $dept), request()->routeIs('finance.ar.credit-memos.*'));
            $items[] = $this->item('Accounts payable', 'trending-down', route('finance.ap.index', $dept), request()->routeIs('finance.ap.*'));
            $items[] = $this->item('Chart of accounts / GL', 'grid', route('finance.gl.index', $dept), request()->routeIs('finance.gl.*'));
            $items[] = $this->item('Budgeting', 'pie-chart', route('finance.budgeting.index', $dept), request()->routeIs('finance.budgeting.*'));
            $items[] = $this->item('Projects & donors', 'globe', route('finance.projects-donors.index', $dept), request()->routeIs('finance.projects-donors.*'));
        }

        if (Auth::user()?->can('finance.payments.manage')) {
            $items[] = $this->item('M-Pesa / treasury', 'smartphone', route('finance.mpesa.settings'), request()->routeIs('finance.mpesa.*'));
        }

        return $items;
    }

    /**
     * @param  array{department?: int}  $dept
     * @return list<array<string, mixed>>
     */
    public function employeeFinanceItems(array $dept): array
    {
        $items = [
            $this->item('Overview', 'layout-grid', route('finance.employee.index'), request()->routeIs('finance.employee.index')),
        ];

        if (Auth::user()?->can('hr.staff.view')) {
            $items[] = $this->item('Payroll runs', 'wallet', route('hr.payroll.index'), request()->routeIs('hr.payroll.index'));
            $items[] = $this->item('Payroll reports', 'bar-chart', route('hr.payroll.report'), request()->routeIs('hr.payroll.report', 'hr.payroll.report.pdf'));
        }

        if (Auth::user()?->can('hr.manage_contracts')) {
            $items[] = $this->item('Payroll settings', 'settings', route('hr.payroll.settings'), request()->routeIs('hr.payroll.settings*'));
        }

        if ($dept !== []) {
            $items[] = $this->item('Payroll → GL integration', 'link', route('finance.payroll-integration.index', $dept), request()->routeIs('finance.payroll-integration.*'));
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function item(string $label, string $icon, string $href, bool $active): array
    {
        return [
            'label' => $label,
            'icon' => $icon,
            'href' => $href,
            'active' => $active,
        ];
    }

    private function canAccessEmployeeFinance(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->can('finance.read')
            || $user->can('hr.staff.view');
    }
}
