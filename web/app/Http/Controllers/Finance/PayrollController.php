<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\KenyaPayrollTaxService;
use App\Services\PrintDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollController extends \App\Http\Controllers\HR\PayrollController
{
    protected function viewPrefix(): string
    {
        return 'finance.employee.payroll';
    }

    protected function routePrefix(): string
    {
        return 'finance.employee.payroll';
    }
}
