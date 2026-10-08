<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Administration\MeetingMinute;
use App\Services\Administration\AdministrationService;
use App\Services\StoredFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MinutesController extends Controller
{
    public function __construct(
        protected AdministrationService $admin,
        protected StoredFileService $files,
    ) {}

    public function index(): View
    {
        $minutes = Schema::hasTable('admin_meeting_minutes')
            ? MeetingMinute::query()
                ->with(['uploader.staff'])
                ->orderByDesc('meeting_date')
                ->orderByDesc('id')
                ->paginate(20)
            : collect();

        return view('administration.minutes.index', compact('minutes'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Schema::hasTable('admin_meeting_minutes'), 503, 'Minutes table is not ready. Run migrations.');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:300'],
            'meeting_date' => ['required', 'date'],
            'meeting_time' => ['nullable', 'date_format:H:i'],
            'venue' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'document' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,jpg,jpeg,png,webp'],
        ]);

        $file = $request->file('document');
        $documentPath = $this->files->store(
            $file,
            'administration/minutes',
            'public',
            time().'_'.$file->getClientOriginalName()
        );

        MeetingMinute::query()->create([
            'minute_code' => $this->admin->nextCode('MIN'),
            'title' => $data['title'],
            'meeting_date' => $data['meeting_date'],
            'meeting_time' => $data['meeting_time'] ?? null,
            'venue' => filled($data['venue'] ?? null) ? trim($data['venue']) : null,
            'document_path' => $documentPath,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'notes' => $data['notes'] ?? null,
            'uploaded_by' => $request->user()?->id,
        ]);

        return back()->with('status', 'Meeting minutes uploaded.');
    }

    public function destroy(MeetingMinute $minute): RedirectResponse
    {
        $minute->delete();

        return back()->with('status', 'Meeting minutes removed.');
    }

    public function view(MeetingMinute $minute): StreamedResponse
    {
        return $this->streamDocument($minute, inline: true);
    }

    public function download(MeetingMinute $minute): StreamedResponse
    {
        return $this->streamDocument($minute, inline: false);
    }

    private function streamDocument(MeetingMinute $minute, bool $inline): StreamedResponse
    {
        abort_unless(filled($minute->document_path), 404);

        $relative = $this->files->relativePath($minute->document_path);
        abort_unless($relative && Storage::disk('public')->exists($relative), 404);

        $filename = $minute->original_filename ?: basename($relative);
        $safeName = preg_replace('/[^\w.\-() ]+/u', '_', $filename) ?: 'minutes';
        $mime = $minute->mime_type
            ?: (Storage::disk('public')->mimeType($relative) ?: 'application/octet-stream');

        if ($inline) {
            return Storage::disk('public')->response(
                $relative,
                $safeName,
                [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="'.$safeName.'"',
                ]
            );
        }

        return Storage::disk('public')->download($relative, $safeName, ['Content-Type' => $mime]);
    }
}
