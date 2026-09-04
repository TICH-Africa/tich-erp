<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\StudentLifecycleRequest;
use App\Services\StudentPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PortalLifecycleRequestController extends Controller
{
    public function __construct(
        protected StudentPortalService $portalService,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $student = $this->portalService->studentForUser($request->user());
        abort_if(! $student, 404);

        $validated = $request->validate([
            'deferment_months' => 'required|integer|min:1|max:36',
            'reason' => 'required|string|max:5000',
            'attachments' => 'required|array|min:1|max:5',
            'attachments.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
        ]);

        $storedPaths = [];
        foreach ($request->file('attachments', []) as $file) {
            if (! $file) {
                continue;
            }
            $original = $file->getClientOriginalName();
            $path = $file->storeAs(
                'student-requests/deferment/'.$student->id,
                Str::uuid()->toString().'_'.Str::slug(pathinfo($original, PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension(),
                'local'
            );
            $storedPaths[] = [
                'path' => $path,
                'original_name' => $original,
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ];
        }

        abort_if($storedPaths === [], 422, 'At least one supporting document is required.');

        StudentLifecycleRequest::query()->create([
            'student_id' => $student->id,
            'requested_by_user_id' => $request->user()->id,
            'request_type' => 'deferment',
            'status' => 'pending',
            'registrar_status' => 'pending',
            'dean_status' => 'pending',
            'deferment_months' => (int) $validated['deferment_months'],
            'reason' => $validated['reason'],
            'attachments' => $storedPaths,
        ]);

        return redirect()
            ->route('portal.dashboard', ['section' => 'requests'])
            ->with('success', 'Your deferment request was submitted for review by the Academic Registrar and Dean of Students.');
    }

    public function downloadAttachment(Request $request, StudentLifecycleRequest $lifecycleRequest, int $index)
    {
        $student = $this->portalService->studentForUser($request->user());
        abort_if(! $student || (int) $lifecycleRequest->student_id !== (int) $student->id, 404);

        $attachments = $lifecycleRequest->attachments ?? [];
        abort_unless(isset($attachments[$index]['path']), 404);
        $path = $attachments[$index]['path'];
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download(
            $path,
            $attachments[$index]['original_name'] ?? basename($path)
        );
    }
}
