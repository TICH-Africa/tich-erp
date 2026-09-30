<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use App\Services\RBACService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditVerifyController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
        protected RBACService $rbac,
    ) {}

    public function __invoke(Request $request): RedirectResponse|View
    {
        $viewer = $request->user();
        if (! $viewer || ! $this->rbac->canViewUnrestrictedAuditLogs($viewer)) {
            abort(403, 'Only ICT, CEO, and Chief Institution Administrator may verify the audit chain.');
        }

        $result = $this->auditService->verifyChain();

        return redirect()
            ->route('admin.audit-logs.index')
            ->with('status', $result['message'].' ('.$result['checked'].' records checked)');
    }
}
