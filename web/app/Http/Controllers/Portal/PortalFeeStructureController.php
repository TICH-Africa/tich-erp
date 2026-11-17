<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Finance\StudentFeeStructureService;
use App\Services\StudentPortalService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalFeeStructureController extends Controller
{
    public function __construct(
        protected StudentPortalService $portalService,
        protected StudentFeeStructureService $feeStructures,
    ) {}

    public function pdf(Request $request): StreamedResponse
    {
        $student = $this->portalService->studentForUser($request->user());
        abort_unless($student, 404);

        return $this->feeStructures->downloadPdf($student);
    }
}
