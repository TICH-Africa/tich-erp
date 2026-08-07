<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Staff;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class FeedbackController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    public function index(Request $request): View
    {
        $query = Feedback::query()
            ->with(['staff'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->whereHas('staff', function ($sq) use ($request) {
                    $sq->where('first_name', 'like', "%{$request->search}%")
                        ->orWhere('surname', 'like', "%{$request->search}%")
                        ->orWhere('employee_number', 'like', "%{$request->search}%");
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at');

        $feedbacks = $query->paginate(20);

        return view('hr.feedback.index', [
            'feedbacks' => $feedbacks,
        ]);
    }

    public function create(): View
    {
        $staffList = Staff::query()
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'surname', 'employee_number', 'job_title']);

        return view('hr.feedback.create', [
            'staffList' => $staffList,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'feedback_type' => 'nullable|string|max:100',
            'description' => 'required|string|max:5000',
            'response' => 'nullable|string|max:5000',
        ]);

        $feedback = DB::transaction(function () use ($validated) {
            return Feedback::create($validated);
        });

        $this->auditService->log(
            'feedback.created',
            'feedback',
            $feedback->id,
            null,
            $feedback->toArray(),
            'Feedback created',
            'success',
            $request->user()->id
        );

        return redirect()->route('hr.employee-relations.feedback.index')->with('success', 'Feedback created successfully.');
    }

    public function show(Feedback $feedback): View
    {
        $feedback->load('staff');

        return view('hr.feedback.show', [
            'feedback' => $feedback,
        ]);
    }

    public function edit(Feedback $feedback): View
    {
        $feedback->load('staff');
        $staffList = Staff::query()
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'surname', 'employee_number', 'job_title']);

        return view('hr.feedback.edit', [
            'feedback' => $feedback,
            'staffList' => $staffList,
        ]);
    }

    public function update(Request $request, Feedback $feedback)
    {
        $validated = $request->validate([
            'feedback_type' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:5000',
            'status' => 'required|in:open,under_review,resolved,closed',
            'response' => 'nullable|string|max:5000',
            'resolved_at' => 'nullable|date',
            'hr_comments' => 'nullable|string|max:5000',
        ]);

        $oldSnapshot = $feedback->toArray();

        DB::transaction(function () use ($feedback, $validated) {
            $feedback->update($validated);
        });

        $this->auditService->log(
            'feedback.updated',
            'feedback',
            $feedback->id,
            $oldSnapshot,
            $feedback->fresh()->toArray(),
            'Feedback updated',
            'success',
            $request->user()->id
        );

        return redirect()->route('hr.employee-relations.feedback.index')->with('success', 'Feedback updated successfully.');
    }

    public function destroy(Request $request, Feedback $feedback)
    {
        DB::transaction(function () use ($feedback, $request) {
            $this->auditService->log(
                'feedback.deleted',
                'feedback',
                $feedback->id,
                $feedback->toArray(),
                null,
                'Feedback deleted',
                'success',
                $request->user()->id
            );

            $feedback->delete();
        });

        return redirect()->route('hr.employee-relations.feedback.index')->with('success', 'Feedback deleted successfully.');
    }
}
