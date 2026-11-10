<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataBreachIncident;
use App\Models\DataProcessingRegister;
use App\Models\DataSubjectRequest;
use App\Models\PersonalDataConsentRecord;
use App\Services\DataSubjectRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DataProtectionController extends Controller
{
    public function __construct(
        protected DataSubjectRequestService $dsrService,
    ) {}

    public function index(Request $request): View
    {
        abort_unless(
            $request->user()?->hasPermission('audit_logs.read')
            || $request->user()?->hasPermission('admin.access'),
            403
        );

        $openBreaches = collect();
        if (Schema::hasTable('data_breach_incidents')) {
            $openBreaches = DataBreachIncident::query()
                ->where('status', 'open')
                ->orderByDesc('discovered_at')
                ->limit(20)
                ->get();
        }

        return view('admin.data-protection.index', [
            'register' => DataProcessingRegister::query()->orderBy('name')->get(),
            'recentConsents' => PersonalDataConsentRecord::query()
                ->orderByDesc('consented_at')
                ->limit(50)
                ->get(),
            'openRequests' => DataSubjectRequest::query()
                ->whereIn('status', ['received', 'in_progress'])
                ->orderBy('due_at')
                ->with('handler')
                ->get(),
            'openBreaches' => $openBreaches,
        ]);
    }

    public function completeDsr(Request $request, DataSubjectRequest $dataSubjectRequest): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('audit_logs.read'), 403);

        $validated = $request->validate([
            'outcome_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->dsrService->complete(
            $dataSubjectRequest,
            (int) $request->user()->id,
            $validated['outcome_notes'] ?? null,
            $request,
        );

        return back()->with('success', 'Request '.$dataSubjectRequest->request_number.' marked complete.');
    }

    public function progressDsr(Request $request, DataSubjectRequest $dataSubjectRequest): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('audit_logs.read'), 403);

        $this->dsrService->markInProgress($dataSubjectRequest, (int) $request->user()->id, $request);

        return back()->with('success', 'Request '.$dataSubjectRequest->request_number.' is in progress.');
    }
}
