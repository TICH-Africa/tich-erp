<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Services\AttendanceVerificationService;
use App\Services\PrintDocumentService;
use App\Services\StaffPortalService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceSheetController extends Controller
{
    public function __construct(
        protected StaffPortalService $portalService,
        protected AttendanceVerificationService $verification,
        protected PrintDocumentService $printDocuments,
    ) {}

    public function show(Request $request, AttendanceSession $session): View
    {
        $staff = $this->portalService->staffForUser($request->user());
        abort_unless((int) $session->recorded_by === (int) $staff->id, 403);

        return view('staff.attendance.sheet', array_merge(
            $this->verification->sheetData($session),
            ['institution' => $this->printDocuments->institution()],
        ));
    }
}
