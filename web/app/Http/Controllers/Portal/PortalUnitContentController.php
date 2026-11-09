<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\UnitContent;
use App\Services\StudentPortalService;
use App\Services\StudentUnitContentAccessService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalUnitContentController extends Controller
{
    public function __construct(
        protected StudentPortalService $portalService,
        protected StudentUnitContentAccessService $contentAccess,
    ) {}

    public function view(Request $request, UnitContent $content): StreamedResponse
    {
        $student = $this->portalService->studentForUser($request->user());
        abort_unless($student, 403);
        $content = $this->contentAccess->resolveForStudent($student, $content);

        return $this->contentAccess->inlineFileResponse($content);
    }

    public function download(Request $request, UnitContent $content): StreamedResponse
    {
        $student = $this->portalService->studentForUser($request->user());
        abort_unless($student, 403);
        $content = $this->contentAccess->resolveForStudent($student, $content);

        return $this->contentAccess->downloadFileResponse($content);
    }
}
