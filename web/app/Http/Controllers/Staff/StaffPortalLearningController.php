<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\ObjectiveAssessment;
use App\Models\Staff;
use App\Models\Unit;
use App\Models\UnitAllocation;
use App\Models\UnitContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StaffPortalLearningController extends Controller
{
    public function __construct()
    {
        $this->middleware('staff.portal');
    }

    public function index(Request $request): View
    {
        $staff = Auth::user()->staff;
        $activeTab = $request->string('tab')->toString() ?: 'content';

        $allocations = UnitAllocation::query()
            ->with(['unit', 'semester.academicYear'])
            ->where('staff_id', $staff->id)
            ->where('is_active', 1)
            ->get();

        $unitIds = $allocations->pluck('unit_id')->filter()->unique()->values()->all();
        $allocationIds = $allocations->pluck('id')->filter()->unique()->values()->all();

        $contents = UnitContent::query()
            ->with(['unit', 'unitAllocation'])
            ->whereIn('unit_id', $unitIds)
            ->orWhereIn('unit_allocation_id', $allocationIds)
            ->orderByDesc('created_at')
            ->get();

        $assignments = Assignment::query()
            ->with(['unit', 'unitAllocation', 'submissions'])
            ->whereIn('unit_allocation_id', $allocationIds)
            ->orderByDesc('created_at')
            ->get();

        $cats = ObjectiveAssessment::query()
            ->with(['unit', 'allocation', 'questions', 'submissions'])
            ->whereIn('unit_allocation_id', $allocationIds)
            ->orderByDesc('created_at')
            ->get();

        return view('staff.learning.index', [
            'activeTab' => $activeTab,
            'allocations' => $allocations,
            'contents' => $contents,
            'assignments' => $assignments,
            'cats' => $cats,
            'units' => Unit::query()->whereIn('id', $unitIds)->get(),
            'staff' => $staff,
        ]);
    }
}
