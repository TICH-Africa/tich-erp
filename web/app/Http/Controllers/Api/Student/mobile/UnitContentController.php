<?php

namespace App\Http\Controllers\Api\Student\mobile;

use App\Http\Controllers\Api\Student\mobile\Concerns\ResolvesPortalStudent;
use App\Http\Controllers\Api\Student\mobile\Concerns\SerializesStudentPortal;
use App\Http\Controllers\Controller;
use App\Models\UnitContent;
use App\Services\StudentUnitContentAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UnitContentController extends Controller
{
    use ResolvesPortalStudent;
    use SerializesStudentPortal;

    public function __construct(
        protected StudentUnitContentAccessService $contentAccess,
    ) {}

    public function show(Request $request, UnitContent $content): JsonResponse
    {
        $student = $this->portalStudent($request);
        $content = $this->contentAccess->resolveForStudent($student, $content);

        return response()->json([
            'content' => $this->serializeUnitContentItem($content),
        ]);
    }

    public function file(Request $request, UnitContent $content): Response|StreamedResponse
    {
        $student = $this->portalStudent($request);
        $content = $this->contentAccess->resolveForStudent($student, $content);

        $disposition = $request->query('disposition') === 'download' ? 'download' : 'inline';

        return $disposition === 'download'
            ? $this->contentAccess->downloadFileResponse($content)
            : $this->contentAccess->inlineFileResponse($content);
    }
}
