<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\UnitContent;
use App\Services\StudentPortalService;
use App\Services\StudentUnitContentAccessService;
use App\Services\UnitContentPreviewService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalUnitContentController extends Controller
{
    public function __construct(
        protected StudentPortalService $portalService,
        protected StudentUnitContentAccessService $contentAccess,
        protected UnitContentPreviewService $preview,
    ) {}

    /**
     * The lesson reader: notes, the file itself, and the next lesson in the lecturer's order.
     */
    public function read(Request $request, UnitContent $content): View
    {
        $student = $this->portalService->studentForUser($request->user());
        abort_unless($student, 403);
        $content = $this->contentAccess->resolveForStudent($student, $content);

        $lessons = UnitContent::arrange(UnitContent::query()
            ->where('unit_id', $content->unit_id)
            ->available()
            ->inLessonOrder()
            ->get(['id', 'title', 'section_title', 'lesson_number', 'file_path']));

        $position = $lessons->search(fn (UnitContent $row): bool => $row->id === $content->id);
        $position = $position === false ? 0 : (int) $position;

        return view('portal.learning-content.read', [
            'content' => $content,
            'unit' => $content->unit,
            'lessons' => $lessons,
            'position' => $position,
            'previous' => $position > 0 ? $lessons[$position - 1] : null,
            'next' => $position < $lessons->count() - 1 ? $lessons[$position + 1] : null,
            'previewKind' => $this->preview->kind($content),
            'previewHtml' => $this->preview->kind($content) === 'office' ? $this->preview->html($content) : null,
            'previewReason' => $this->preview->fallbackReason($content),
            'fileUrl' => $content->file_path ? route('portal.learning-content.view', $content) : null,
            'downloadUrl' => $content->file_path ? route('portal.learning-content.download', $content) : null,
        ]);
    }

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
