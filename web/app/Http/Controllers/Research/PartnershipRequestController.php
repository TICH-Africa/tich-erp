<?php

namespace App\Http\Controllers\Research;

use App\Http\Controllers\Controller;
use App\Models\Portal\PartnershipRequest;
use App\Models\Portal\PartnershipRequestDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PartnershipRequestController extends Controller
{
    public function index(Request $request): View
    {
        $query = PartnershipRequest::query()->withCount('documents')->orderByDesc('id');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($type = $request->string('applicant_type')->toString()) {
            $query->where('applicant_type', $type);
        }

        return view('research.partnerships.index', [
            'requests' => $query->paginate(20)->withQueryString(),
            'filters' => [
                'status' => $status ?? '',
                'applicant_type' => $type ?? '',
            ],
        ]);
    }

    public function show(PartnershipRequest $partnership): View
    {
        $partnership->load('documents');

        return view('research.partnerships.show', [
            'request' => $partnership,
        ]);
    }

    public function updateStatus(Request $request, PartnershipRequest $partnership): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending_review,under_evaluation,approved,declined'],
            'review_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $partnership->update([
            'status' => $data['status'],
            'review_notes' => $data['review_notes'] ?? $partnership->review_notes,
            'reviewed_by' => $request->user()?->staff_id,
            'reviewed_at' => now(),
            'updated_at' => now(),
            'updated_by' => $request->user()?->staff_id,
        ]);

        return back()->with('status', 'Partnership request updated.');
    }

    public function downloadDocument(PartnershipRequest $partnership, PartnershipRequestDocument $document): StreamedResponse
    {
        abort_unless((int) $document->partnership_request_id === (int) $partnership->id, 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download(
            $document->file_path,
            $document->original_filename ?: 'attachment'
        );
    }
}
