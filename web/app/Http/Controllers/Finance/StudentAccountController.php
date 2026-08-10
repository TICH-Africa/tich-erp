<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\StudentAccount;
use App\Services\Finance\StudentAccountService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentAccountController extends Controller
{
    public function __construct(
        protected StudentAccountService $accounts,
    ) {}

    public function index(Request $request): View
    {
        return view('finance.student-accounts.index', [
            'accounts' => $this->accounts->listAccounts($request->string('search')->toString() ?: null),
        ]);
    }

    public function show(StudentAccount $studentAccount): View
    {
        $studentAccount->load([
            'student.applicant',
            'student.program',
            'academicYear',
            'invoices' => fn ($query) => $query->orderByDesc('issue_date'),
            'payments' => fn ($query) => $query->orderByDesc('payment_date'),
        ]);

        return view('finance.student-accounts.show', [
            'account' => $studentAccount,
        ]);
    }
}
