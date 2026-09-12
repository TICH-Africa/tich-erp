<?php

namespace App\Http\Controllers\Qa;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Qa\QaCapacitySession;
use App\Models\Qa\QaTrainingEnrolment;
use App\Services\PlatformNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CapacityController extends Controller
{
    public function __construct(
        protected PlatformNotificationService $notifications,
    ) {}

    public function index(): View
    {
        $sessions = QaCapacitySession::query()
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('qa.capacity.index', compact('sessions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:5000'],
            'scheduled_at' => ['nullable', 'date'],
            'audience' => ['nullable', 'string', 'max:300'],
            'location' => ['nullable', 'string', 'max:300'],
            'status' => ['required', 'in:scheduled,completed,cancelled'],
        ]);

        QaCapacitySession::query()->create([
            ...$validated,
            'created_by' => $request->user()?->staff_id,
        ]);

        return back()->with('status', 'Capacity-building session recorded.');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'exists:qa_capacity_sessions,id'],
            'staff_id' => ['required', 'exists:staff,id'],
            'status' => ['required', 'in:pending,accepted'],
            'decline_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $session = QaCapacitySession::query()->findOrFail($validated['session_id']);

        QaTrainingEnrolment::query()->updateOrCreate([
            'qa_capacity_session_id' => $validated['session_id'],
            'staff_id' => $validated['staff_id'],
        ], [
            'status' => $validated['status'],
            'decline_reason' => $validated['decline_reason'] ?? null,
        ]);

        if ($request->user()?->staff_id && (int) $request->user()->staff_id === (int) $validated['staff_id']) {
            $session->increment('enrolled_count');
        }

        return back()->with('status', 'Staff enrolment recorded.');
    }

    public function export(Request $request): RedirectResponse
    {
        $sessions = QaCapacitySession::query()
            ->with(['enrolments.staff'])
            ->orderByDesc('scheduled_at')
            ->get();

        $filename = 'capacity-sessions-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($sessions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Session', 'Date', 'Title', 'Staff', 'Status']);
            foreach ($sessions as $session) {
                foreach ($session->enrolments as $enrolment) {
                    fputcsv($handle, [
                        $session->title,
                        $session->scheduled_at?->format('Y-m-d'),
                        $session->title,
                        $enrolment->staff?->first_name.' '.$enrolment->staff?->surname ?? '-',
                        $enrolment->status,
                    ]);
                }
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
