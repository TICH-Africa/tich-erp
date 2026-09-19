<?php

namespace App\Http\Controllers\Research;

use App\Http\Controllers\Controller;
use App\Models\Portal\ResearchProject;
use App\Models\Portal\ResearchProjectDocument;
use App\Services\Research\ResearchActivityService;
use App\Services\StoredFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResearchActivityController extends Controller
{
    public function __construct(
        protected ResearchActivityService $activities,
        protected StoredFileService $files,
    ) {}

    public function index(Request $request): View
    {
        $query = ResearchProject::query()->withCount('documents')->orderByDesc('id');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($visibility = $request->string('visibility')->toString()) {
            $query->where('visibility', $visibility);
        }
        if ($q = $request->string('q')->toString()) {
            $query->where(function ($inner) use ($q) {
                $inner->where('title', 'like', "%{$q}%")
                    ->orWhere('summary', 'like', "%{$q}%");
            });
        }

        return view('research.activities.index', [
            'activities' => $query->paginate(20)->withQueryString(),
            'filters' => [
                'status' => $status ?? '',
                'visibility' => $visibility ?? '',
                'q' => $q ?? '',
            ],
        ]);
    }

    public function create(): View
    {
        return view('research.activities.create', $this->formMeta());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $documents = $this->collectDocuments($request);

        $project = $this->activities->create($data, $request->user(), $documents);

        return redirect()
            ->route('research.activities.show', $project)
            ->with('status', 'Research activity created.');
    }

    public function show(ResearchProject $activity): View
    {
        $this->activities->syncLifecycleStatus($activity);
        $activity->load(['documents', 'leadResearcher']);

        return view('research.activities.show', [
            'activity' => $activity,
        ]);
    }

    public function edit(ResearchProject $activity): View
    {
        $activity->load('documents');

        return view('research.activities.edit', array_merge($this->formMeta(), [
            'activity' => $activity,
        ]));
    }

    public function update(Request $request, ResearchProject $activity): RedirectResponse
    {
        $data = $this->validated($request, $activity);
        $documents = $this->collectDocuments($request);

        $this->activities->update($activity, $data, $request->user(), $documents);

        return redirect()
            ->route('research.activities.show', $activity)
            ->with('status', 'Research activity updated.');
    }

    public function destroy(ResearchProject $activity): RedirectResponse
    {
        $this->activities->delete($activity);

        return redirect()
            ->route('research.activities.index')
            ->with('status', 'Research activity archived and removed.');
    }

    public function destroyDocument(ResearchProject $activity, ResearchProjectDocument $document): RedirectResponse
    {
        abort_unless((int) $document->research_project_id === (int) $activity->id, 404);
        $this->activities->deleteDocument($document);

        return back()->with('status', 'Document removed.');
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
        ]);

        $path = $this->files->store($request->file('image'), 'research/inline', 'public');

        return response()->json([
            'url' => $this->files->url($path, 'public'),
            'path' => $path,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formMeta(): array
    {
        return [
            'durationUnits' => ResearchActivityService::DURATION_UNITS,
            'lifecycleStatuses' => ResearchActivityService::LIFECYCLE_STATUSES,
            'visibilities' => ResearchActivityService::VISIBILITIES,
            'uploadUrl' => route('research.activities.upload-image'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?ResearchProject $activity = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:300'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'summary' => ['required', 'string', 'max:5000'],
            'abstract' => ['nullable', 'string', 'max:20000'],
            'body' => ['nullable', 'string', 'max:500000'],
            'start_date' => ['required', 'date'],
            'duration_value' => ['required', 'integer', 'min:1', 'max:3650'],
            'duration_unit' => ['required', 'in:'.implode(',', ResearchActivityService::DURATION_UNITS)],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status_locked' => ['nullable', 'boolean'],
            'status' => ['nullable', 'in:'.implode(',', ResearchActivityService::LIFECYCLE_STATUSES)],
            'visibility' => ['required', 'in:'.implode(',', ResearchActivityService::VISIBILITIES)],
            'is_featured' => ['nullable', 'boolean'],
            'cover_image' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
            'remove_cover' => ['nullable', 'boolean'],
            'doc_titles' => ['nullable', 'array'],
            'doc_titles.*' => ['nullable', 'string', 'max:300'],
            'doc_files' => ['nullable', 'array'],
            'doc_files.*' => ['nullable', 'file', 'max:20480', 'mimes:pdf,doc,docx,png,jpg,jpeg,webp'],
        ]);

        $data['status_locked'] = $request->boolean('status_locked');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['remove_cover'] = $request->boolean('remove_cover');
        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image');
        }

        return $data;
    }

    /**
     * @return list<array{title: string, file: \Illuminate\Http\UploadedFile}>
     */
    private function collectDocuments(Request $request): array
    {
        $titles = $request->input('doc_titles', []);
        $files = $request->file('doc_files', []);
        $out = [];

        foreach ($files as $i => $file) {
            if (! $file) {
                continue;
            }
            $out[] = [
                'title' => (string) ($titles[$i] ?? ''),
                'file' => $file,
            ];
        }

        return $out;
    }
}
