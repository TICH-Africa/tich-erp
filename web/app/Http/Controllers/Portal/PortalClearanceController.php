<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\StudentClearanceService;
use App\Services\StudentPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PortalClearanceController extends Controller
{
    public function __construct(
        protected StudentPortalService $portalService,
        protected StudentClearanceService $clearance,
    ) {}

    public function ensure(Request $request): RedirectResponse
    {
        $student = $this->portalService->studentForUser($request->user());
        abort_if(! $student, 404);

        $this->clearance->ensureDefaults($student);

        return redirect()
            ->route('portal.dashboard', ['section' => 'clearance'])
            ->with('success', 'Clearance checklist refreshed.');
    }
}
