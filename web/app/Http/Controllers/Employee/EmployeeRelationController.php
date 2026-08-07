<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Grievance;
use App\Models\Feedback;
use App\Models\Staff;
use App\Services\EmployeePortalService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class EmployeeRelationController extends Controller
{
    public function __construct(
        protected EmployeePortalService $employeePortal,
    ) {}

    private function staff(Request $request): Staff
    {
        $staff = $this->employeePortal->staffForUser($request->user());
        abort_if(! $staff, 404);

        return $staff;
    }

    public function grievances(Request $request): View
    {
        $staff = $this->staff($request);

        $grievances = Grievance::query()
            ->where('staff_id', $staff->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('employee.relations.grievances.index', [
            'portalTitle' => 'My Grievances',
            'staff' => $staff,
            'grievances' => $grievances,
        ]);
    }

    public function grievanceCreate(Request $request): View
    {
        $staff = $this->staff($request);

        return view('employee.relations.grievances.create', [
            'portalTitle' => 'New Grievance',
            'staff' => $staff,
        ]);
    }

    public function grievanceStore(Request $request)
    {
        return redirect()->route('employee.concerns.create')
            ->with('info', 'Please use the concerns form to submit issues to HR.');
    }

    public function grievanceShow(Request $request, Grievance $grievance): View
    {
        $staff = $this->staff($request);
        abort_if($grievance->staff_id !== $staff->id, 403);

        $grievance->load('assignedTo');

        return view('employee.relations.grievances.show', [
            'portalTitle' => 'Grievance #' . $grievance->id,
            'staff' => $staff,
            'grievance' => $grievance,
        ]);
    }

    public function feedback(Request $request): View
    {
        $staff = $this->staff($request);

        $feedbacks = Feedback::query()
            ->where('staff_id', $staff->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('employee.relations.feedback.index', [
            'portalTitle' => 'My Feedback',
            'staff' => $staff,
            'feedbacks' => $feedbacks,
        ]);
    }

    public function feedbackCreate(Request $request): View
    {
        $staff = $this->staff($request);

        return view('employee.relations.feedback.create', [
            'portalTitle' => 'New Feedback',
            'staff' => $staff,
        ]);
    }

    public function feedbackStore(Request $request)
    {
        $staff = $this->staff($request);

        $validated = $request->validate([
            'feedback_type' => 'nullable|string|max:100',
            'description' => 'required|string|max:5000',
            'response' => 'nullable|string|max:5000',
        ]);

        $feedback = Feedback::create([
            'staff_id' => $staff->id,
            'feedback_type' => $validated['feedback_type'],
            'description' => $validated['description'],
            'response' => $validated['response'],
            'status' => 'open',
        ]);

        return redirect()->route('employee.relations.feedback.index')->with('success', 'Feedback submitted to HR.');
    }

    public function feedbackShow(Request $request, Feedback $feedback): View
    {
        $staff = $this->staff($request);
        abort_if($feedback->staff_id !== $staff->id, 403);

        return view('employee.relations.feedback.show', [
            'portalTitle' => 'Feedback #' . $feedback->id,
            'staff' => $staff,
            'feedback' => $feedback,
        ]);
    }
}
