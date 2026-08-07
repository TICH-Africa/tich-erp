<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Grievance;
use App\Models\Staff;
use App\Services\EmployeeConcernService;
use App\Services\EmployeePortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeConcernController extends Controller
{
    public function __construct(
        protected EmployeePortalService $employeePortal,
        protected EmployeeConcernService $concerns,
    ) {}

    public function index(Request $request): View
    {
        $staff = $this->staff($request);

        $concerns = Grievance::query()
            ->where('staff_id', $staff->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        $openCount = Grievance::query()
            ->where('staff_id', $staff->id)
            ->whereIn('status', ['open', 'under_review'])
            ->count();

        return view('employee.concerns.index', [
            'portalTitle' => 'Concerns & issues',
            'staff' => $staff,
            'concerns' => $concerns,
            'openCount' => $openCount,
        ]);
    }

    public function create(Request $request): View
    {
        $staff = $this->staff($request);

        return view('employee.concerns.create', [
            'portalTitle' => 'Raise a concern',
            'staff' => $staff,
            'categories' => EmployeeConcernService::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $staff = $this->staff($request);

        $validated = $request->validate([
            'concern_category' => 'required|string|in:'.implode(',', array_keys(EmployeeConcernService::CATEGORIES)),
            'subject' => 'required|string|max:300',
            'description' => 'required|string|max:5000',
            'incident_date' => 'nullable|date|before_or_equal:today',
            'resolution_notes' => 'nullable|string|max:2000',
        ]);

        $grievance = $this->concerns->submit($staff, $request->user(), $validated);

        return redirect()
            ->route('employee.concerns.show', $grievance)
            ->with('success', "Your concern {$grievance->reference_number} has been submitted to HR.");
    }

    public function show(Request $request, Grievance $grievance): View
    {
        $staff = $this->staff($request);
        abort_if($grievance->staff_id !== $staff->id, 403);

        $grievance->load('assignedTo');

        return view('employee.concerns.show', [
            'portalTitle' => $grievance->reference_number ?? 'Concern #'.$grievance->id,
            'staff' => $staff,
            'concern' => $grievance,
        ]);
    }

    private function staff(Request $request): Staff
    {
        $staff = $request->attributes->get('portal_staff')
            ?? $this->employeePortal->staffForUser($request->user());

        abort_unless($staff, 403);

        return $staff;
    }
}
