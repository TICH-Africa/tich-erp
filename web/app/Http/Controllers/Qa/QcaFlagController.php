<?php

namespace App\Http\Controllers\Qa;

use App\Http\Controllers\Controller;
use App\Models\Qa\QcaFlag;
use App\Models\Staff;
use App\Services\AuditService;
use App\Services\PlatformNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QcaFlagController extends Controller
{
    public function __construct(
        protected AuditService $audit,
        protected PlatformNotificationService $notifications,
    ) {}

    public function index(Request $request): View
    {
        $query = QcaFlag::query()
            ->with(['assignedTo', 'raisedBy', 'resolvedBy'])
            ->orderByDesc('created_at');

        if (! empty($request->get('category'))) {
            $query->where('category', $request->get('category'));
        }
        if (! empty($request->get('severity'))) {
            $query->where('severity', $request->get('severity'));
        }
        if (! empty($request->get('status'))) {
            $query->where('status', $request->get('status'));
        }
        if (! empty($request->get('assigned_to'))) {
            $query->where('assigned_to', (int) $request->get('assigned_to'));
        }
        if (! empty($request->get('from'))) {
            $query->where('created_at', '>=', $request->get('from').(strlen($request->get('from')) <= 10 ? ' 00:00:00' : ''));
        }
        if (! empty($request->get('to'))) {
            $query->where('created_at', '<=', $request->get('to').(strlen($request->get('to')) <= 10 ? ' 23:59:59' : ''));
        }

        $flags = $query->paginate(25);
        $staff = Staff::query()->orderBy('first_name')->orderBy('surname')->get();

        return view('qa.qca-flags.index', compact('flags', 'staff'));
    }

    public function create(): View
    {
        $staff = Staff::query()->orderBy('first_name')->orderBy('surname')->get();

        return view('qa.qca-flags.create', [
            'categories' => QcaFlag::CATEGORIES,
            'severities' => QcaFlag::SEVERITIES,
            'staff' => $staff,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'in:'.implode(',', QcaFlag::CATEGORIES)],
            'severity' => ['required', 'in:'.implode(',', QcaFlag::SEVERITIES)],
            'description' => ['required', 'string', 'max:2000'],
            'assigned_to' => ['nullable', 'exists:staff,id'],
            'target_entity_type' => ['nullable', 'string', 'max:100'],
            'target_entity_id' => ['nullable', 'integer'],
            'resolution_deadline' => ['nullable', 'date'],
            'source_module' => ['nullable', 'string', 'max:100'],
            'source_entity_id' => ['nullable', 'integer'],
        ]);

        $flag = QcaFlag::query()->create([
            'flag_number' => $this->generateFlagNumber(),
            'category' => $validated['category'],
            'severity' => $validated['severity'],
            'description' => $validated['description'],
            'assigned_to' => $validated['assigned_to'] ?? null,
            'target_entity_type' => $validated['target_entity_type'] ?? null,
            'target_entity_id' => $validated['target_entity_id'] ?? null,
            'resolution_deadline' => $validated['resolution_deadline'] ?? null,
            'raised_by' => $request->user()?->staff_id,
            'source_module' => $validated['source_module'] ?? null,
            'source_entity_id' => $validated['source_entity_id'] ?? null,
        ]);

        if ($flag->isHighOrCritical()) {
            $flag->update(['downstream_locks' => ['modules' => $flag->lockedModules()]]);
        }

        $this->audit->log(
            'qca.flag.raised',
            'qca_flags',
            $flag->id,
            null,
            $flag->only(['category', 'severity', 'status', 'description', 'assigned_to']),
            null,
            'success',
            $request->user()?->id,
        );

        $this->notifyAssignee($flag);

        return redirect()
            ->route('qa.qca-flags.show', $flag)
            ->with('status', 'QCA flag raised. '.($flag->isHighOrCritical() ? 'Downstream locks applied.' : ''));
    }

    public function show(QcaFlag $flag): View
    {
        $flag->load(['assignedTo', 'raisedBy', 'resolvedBy', 'milestones.recordedBy']);

        return view('qa.qca-flags.show', compact('flag'));
    }

    public function updateStatus(Request $request, QcaFlag $flag): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', QcaFlag::STATUSES)],
        ]);

        $oldStatus = $flag->status;
        $flag->update(['status' => $validated['status']]);

        $this->audit->log(
            'qca.flag.status_changed',
            'qca_flags',
            $flag->id,
            ['status' => $oldStatus],
            ['status' => $validated['status']],
            null,
            'success',
            $request->user()?->id,
        );

        return back()->with('status', "QCA flag status updated from {$oldStatus} to {$validated['status']}.");
    }

    public function addMilestone(Request $request, QcaFlag $flag): RedirectResponse
    {
        $validated = $request->validate([
            'milestone_type' => ['required', 'in:'.implode(',', QcaFlag::MILESTONE_TYPES)],
            'description' => ['required', 'string', 'max:2000'],
            'evidence' => ['nullable', 'string', 'max:5000'],
        ]);

        $flag->milestones()->create([
            'milestone_type' => $validated['milestone_type'],
            'description' => $validated['description'],
            'recorded_by' => $request->user()?->staff_id,
            'evidence' => $validated['evidence'] ?? null,
        ]);

        $this->audit->log(
            'qca.flag.milestone_added',
            'qca_flags',
            $flag->id,
            null,
            ['milestone_type' => $validated['milestone_type'], 'description' => $validated['description']],
            null,
            'success',
            $request->user()?->id,
        );

        return back()->with('status', 'Milestone recorded: '.$validated['milestone_type']);
    }

    public function resolve(Request $request, QcaFlag $flag): RedirectResponse
    {
        $validated = $request->validate([
            'resolution_description' => ['required', 'string', 'max:5000'],
            'root_cause' => ['required', 'string', 'max:5000'],
            'evidence_of_correction' => ['required', 'string', 'max:5000'],
        ]);

        $flag->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $request->user()?->staff_id,
            'resolution_description' => $validated['resolution_description'],
            'root_cause' => $validated['root_cause'],
            'evidence_of_correction' => $validated['evidence_of_correction'],
        ]);

        $this->audit->log(
            'qca.flag.resolved',
            'qca_flags',
            $flag->id,
            ['status' => 'resolved'],
            $flag->only(['resolution_description', 'root_cause', 'resolved_by']),
            null,
            'success',
            $request->user()?->id,
        );

        return redirect()
            ->route('qa.qca-flags.show', $flag)
            ->with('status', 'QCA flag marked as resolved. Downstream locks released.');
    }

    public function close(QcaFlag $flag): RedirectResponse
    {
        $flag->update(['status' => 'closed']);

        $this->audit->log(
            'qca.flag.closed',
            'qca_flags',
            $flag->id,
            ['status' => 'resolved'],
            ['status' => 'closed'],
            null,
            'success',
            request()->user()?->id,
        );

        return redirect()
            ->route('qa.qca-flags.show', $flag)
            ->with('status', 'QCA flag closed.');
    }

    public function ceoOverride(Request $request, QcaFlag $flag): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $flag->update([
            'status' => 'open',
            'ceo_override_reason' => $validated['reason'],
            'ceo_overridden_by' => $request->user()?->staff_id,
            'ceo_overridden_at' => now(),
        ]);

        $this->audit->log(
            'qca.flag.ceo_override',
            'qca_flags',
            $flag->id,
            ['status' => $flag->status, 'locked_modules' => $flag->lockedModules()],
            ['ceo_override_reason' => $validated['reason'], 'restored_status' => 'open'],
            'CEO override: '.$validated['reason'],
            'success',
            $request->user()?->id,
        );

        return redirect()
            ->route('qa.qca-flags.show', $flag)
            ->with('status', 'CEO override applied. Lock released with documented reason.');
    }

    private function generateFlagNumber(): string
    {
        $latest = QcaFlag::query()->orderByDesc('id')->value('id');
        $nextId = ($latest ?? 0) + 1;

        return 'QCA-'.now()->format('Y').'-'.str_pad($nextId, 5, '0', STR_PAD_LEFT);
    }

    private function notifyAssignee(QcaFlag $flag): void
    {
        if (! $flag->assigned_to) {
            return;
        }

        $staff = Staff::query()->find($flag->assigned_to);
        if (! $staff?->user_id) {
            return;
        }

        $this->notifications->notifyUser(
            $staff->user_id,
            'QCA Flag assigned to you',
            "QCA Flag #{$flag->flag_number} ({$flag->category}/{$flag->severity}) has been assigned to you. Review the flag at: ".route('qa.qca-flags.show', $flag),
            'qca_flag',
            (string) $flag->id,
            $flag->severity === 'Critical' ? 'high' : 'normal',
            route('qa.qca-flags.show', $flag),
        );
    }
}
