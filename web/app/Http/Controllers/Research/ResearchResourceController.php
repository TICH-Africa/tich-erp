<?php

namespace App\Http\Controllers\Research;

use App\Http\Controllers\Controller;
use App\Models\Portal\ResearchResource;
use App\Services\StoredFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResearchResourceController extends Controller
{
    public function __construct(
        protected StoredFileService $files,
    ) {}

    public function index(): View
    {
        return view('research.resources.index', [
            'resources' => ResearchResource::query()
                ->orderByRaw('year IS NULL, year DESC, id DESC')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('research.resources.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $resource = new ResearchResource;
        $resource->fill($this->validated($request));
        $resource->created_by = $request->user()?->id;
        $resource->save();

        return redirect()
            ->route('research.resources.index')
            ->with('status', 'Resource added.');
    }

    public function edit(ResearchResource $resource): View
    {
        return view('research.resources.edit', [
            'resource' => $resource,
        ]);
    }

    public function update(Request $request, ResearchResource $resource): RedirectResponse
    {
        $resource->fill($this->validated($request, $resource));
        $resource->updated_by = $request->user()?->id;
        $resource->save();

        return redirect()
            ->route('research.resources.index')
            ->with('status', 'Resource updated.');
    }

    public function destroy(ResearchResource $resource): RedirectResponse
    {
        $resource->delete();

        return redirect()
            ->route('research.resources.index')
            ->with('status', 'Resource removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?ResearchResource $resource = null): array
    {
        $request->validate([
            'title' => ['required', 'string', 'max:500'],
            'authors' => ['nullable', 'string', 'max:500'],
            'publisher' => ['nullable', 'string', 'max:300'],
            'year' => ['nullable', 'string', 'max:20'],
            'isbn' => ['nullable', 'string', 'max:150'],
            'doi' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:20000'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,csv,txt,zip,epub', 'max:20480'],
        ]);

        $data = [
            'title' => trim($request->string('title')->toString()),
            'authors' => $this->text($request, 'authors'),
            'publisher' => $this->text($request, 'publisher'),
            'year' => $this->text($request, 'year'),
            'isbn' => $this->text($request, 'isbn'),
            'doi' => $this->text($request, 'doi'),
            'description' => $this->text($request, 'description'),
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->hasFile('file')) {
            $data['file_path'] = $this->files->replace(
                $resource?->file_path,
                $request->file('file'),
                'research/resources',
                'public',
                null,
                true,
            );
            $data['original_name'] = $request->file('file')->getClientOriginalName();
            $data['mime_type'] = $request->file('file')->getClientMimeType();
        } elseif ($resource?->file_path) {
            if ($request->boolean('remove_file')) {
                $this->files->delete($resource->file_path, 'public');
                $data['file_path'] = null;
                $data['original_name'] = null;
                $data['mime_type'] = null;
            } else {
                // Saving the description without picking a file keeps the document already on record.
                $data['file_path'] = $resource->file_path;
                $data['original_name'] = $resource->original_name;
                $data['mime_type'] = $resource->mime_type;
            }
        }

        return $data;
    }

    private function text(Request $request, string $field): ?string
    {
        $value = trim($request->string($field)->toString());

        return $value === '' ? null : $value;
    }
}
