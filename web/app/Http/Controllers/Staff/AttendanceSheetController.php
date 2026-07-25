<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Services\AttendanceVerificationService;
use App\Services\StaffPortalService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceSheetController extends Controller
{
    public function __construct(
        protected StaffPortalService $portalService,
        protected AttendanceVerificationService $verification,
    ) {}

    public function show(Request $request, AttendanceSession $session): View
    {
        $staff = $this->portalService->staffForUser($request->user());
        abort_unless((int) $session->recorded_by === (int) $staff->id, 403);

        return view('staff.attendance.sheet', $this->verification->sheetData($session));
    }
}
