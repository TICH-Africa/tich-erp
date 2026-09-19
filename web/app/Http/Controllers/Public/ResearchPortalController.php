<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Portal\ResearchProject;
use App\Models\Portal\ResearchProjectDocument;
use App\Services\Research\ResearchActivityService;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResearchPortalController extends Controller
{
    public function __construct(
        protected ResearchActivityService $activities,
    ) {}

    public function show(string $slug): View
    {
        $activity = ResearchProject::query()
            ->published()
            ->where('slug', $slug)
            ->with('documents')
            ->firstOrFail();

        $this->activities->syncLifecycleStatus($activity);

        return view('pages.research-show', [
            'activity' => $activity,
        ]);
    }

    public function documentViewer(ResearchProjectDocument $document): View
    {
        $project = $document->project;
        abort_unless($project && $project->isPublished(), 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return view('pages.research-document-viewer', [
            'document' => $document,
            'activity' => $project,
            'streamUrl' => route('research.documents.stream', $document),
            'viewerIp' => request()->ip(),
            'viewerStamp' => now()->format('d M Y H:i'),
        ]);
    }

    public function documentStream(ResearchProjectDocument $document): StreamedResponse
    {
        $project = $document->project;
        abort_unless($project && $project->isPublished(), 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        $mime = $document->mime_type ?: 'application/octet-stream';
        $filename = preg_replace('/[^\w.\-() ]+/u', '_', (string) ($document->original_filename ?: 'document')) ?: 'document';

        return Storage::disk('local')->response(
            $document->file_path,
            $filename,
            [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'SAMEORIGIN',
            ]
        );
    }
}
