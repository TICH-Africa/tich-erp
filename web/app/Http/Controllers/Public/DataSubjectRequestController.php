<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\DataSubjectRequest;
use App\Services\DataSubjectRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DataSubjectRequestController extends Controller
{
    public function __construct(
        protected DataSubjectRequestService $requests,
    ) {}

    public function create(): View
    {
        $labels = [
            'access' => 'Access my personal data',
            'rectification' => 'Correct inaccurate data',
            'erasure' => 'Request erasure (where applicable)',
            'objection' => 'Object to processing',
            'complaint' => 'Complaint about use of my data',
        ];

        $requestTypes = [];
        foreach (DataSubjectRequest::requestTypeOptions() as $key => $short) {
            $requestTypes[$key] = $labels[$key] ?? $short;
        }

        return view('pages.data-subject-request', [
            'requestTypes' => $requestTypes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'requester_name' => ['required', 'string', 'max:200'],
            'requester_email' => ['required', 'email', 'max:255'],
            'requester_phone' => ['nullable', 'string', 'max:30'],
            'request_type' => ['required', 'string', 'in:access,rectification,erasure,objection,complaint'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $record = $this->requests->createFromPublic($validated, $request);
        } catch (\Throwable) {
            return back()
                ->withInput()
                ->withErrors(['submit' => 'We could not submit your request. Please email the Data Protection Officer directly.']);
        }

        return redirect()
            ->route('privacy.data-request')
            ->with('success', 'Your request '.$record->request_number.' was received. We will respond within '
                .config('data-protection.data_subject_request_sla_days', 30).' days.');
    }
}
