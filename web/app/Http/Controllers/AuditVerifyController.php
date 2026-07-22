<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AuditVerifyController extends Controller
{
    public function __construct(protected AuditService $auditService) {}

    public function __invoke(): RedirectResponse|View
    {
        $result = $this->auditService->verifyChain();

        return redirect()
            ->route('admin.audit-logs.index')
            ->with('status', $result['message'].' ('.$result['checked'].' records checked)');
    }
}
