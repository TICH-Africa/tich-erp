<?php

namespace App\Http\Controllers\Qa;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Qa\QaCapacitySession;
use App\Models\Qa\QaTrainingCredit;
use App\Models\Qa\QaTrainingEventRegistration;
use App\Models\Qa\QaTrainingEnrolment;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingCreditsController extends Controller
{
    public function __construct(protected AuditService $audit) {}

    public function index(Request $request): View
    {
        $credits = QaTrainingCredit::query()
            ->with(['staff', 'event'])
            ->orderByDesc('awarded_at')
            ->paginate(25);

        $sessions = QaCapacitySession::query()
            ->orderByDesc('scheduled_at')
            ->paginate(20);

        return view('qa.training-credits.index', compact('credits', 'sessions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'staff_id' => ['required', 'exists:staff,id'],
            'credit_type' => ['required', 'in:CPD,Mandatory,Certification'],
            'credit_value' => ['required', 'integer', 'min:1'],
            'event_id' => ['nullable', 'exists:qa_capacity_sessions,id'],
            'awarded_at' => ['required', 'date'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $credit = QaTrainingCredit::query()->create($validated);

        $this->audit->log(
            'qa.training_credit.created',
            'qa_training_credits',
            $credit->id,
            null,
            $credit->only(['staff_id', 'credit_type', 'credit_value', 'event_id', 'awarded_at', 'expires_at']),
            null,
            'success',
            $request->user()?->id,
        );

        return redirect()
            ->route('qa.training-credits.index')
            ->with('status', 'Training credit awarded.'.($validated['expires_at'] ? ' Expires: '.$validated['expires_at'] : ''));
    }

    public function export(Request $request): RedirectResponse
    {
        $credits = QaTrainingCredit::query()
            ->with(['staff']);

        if (! empty($request->get('credit_type'))) {
            $credits->where('credit_type', $request->get('credit_type'));
        }

        $credits = $credits->orderByDesc('awarded_at')->get();

        // Generate CSV
        $filename = 'training-credits-'.now()->format('Ymd-His').'.csv';
        $headers = ['Staff', 'Credit Type', 'Credit Value', 'Awarded', 'Expiry', 'Event'];

        return response()->streamDownload(function () use ($credits, $headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($credits as $credit) {
                fputcsv($handle, [
                    $credit->staff?->first_name.' '.$credit->staff?->surname ?? '-',
                    $credit->credit_type,
                    $credit->credit_value,
                    $credit->awarded_at?->format('Y-m-d'),
                    $credit->expires_at?->format('Y-m-d') ?? '-',
                    $credit->event?->title ?? '-',
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
