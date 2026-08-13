<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\PayrollRunService;
use App\Services\PrintDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollRunController extends \App\Http\Controllers\HR\PayrollRunController
{
    protected function viewPrefix(): string
    {
        return 'finance.employee.payroll.runs';
    }

    protected function routePrefix(): string
    {
        return 'finance.employee.payroll.runs';
    }

    protected function printView(): string
    {
        return 'finance.employee.payroll.print';
    }
}
