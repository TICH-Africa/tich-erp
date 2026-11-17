<?php

namespace App\Http\Controllers\Api\Student\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Student\mobile\Concerns\ResolvesPortalStudent;
use App\Services\Finance\StudentFeeStructureService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FeeStructureController extends Controller
{
    use ResolvesPortalStudent;

    public function __construct(
        protected StudentFeeStructureService $feeStructures,
    ) {}

    public function pdf(Request $request): StreamedResponse
    {
        return $this->feeStructures->downloadPdf($this->portalStudent($request));
    }
}
