<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\ProfessionalDevelopment;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class TrainingController extends Controller
{
    public function index(): View
    {
        $trainings = ProfessionalDevelopment::with(['staff', 'approvedBy'])
            ->orderByDesc('start_date')
            ->paginate(25);

        return view('hr.training.index', ['trainings' => $trainings]);
    }

    public function create(): View
    {
        $staff = Staff::orderBy('first_name')->get(['id', 'first_name', 'surname', 'employee_number']);

        return view('hr.training.create', ['staff' => $staff]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'staff_ids' => 'nullable|array',
            'staff_ids.*' => 'exists:staff,id',
            'assign_all' => 'boolean',
            'activity_type' => 'required|string|in:training,workshop,conference,seminar,cpd,study_leave,attachment,mentorship',
            'activity_name' => 'required|string|max:300',
            'organizer' => 'nullable|string|max:300',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'hours_or_days' => 'nullable|numeric|min:0',
            'cpd_credits_earned' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:300',
            'is_external' => 'boolean',
            'cost' => 'nullable|numeric|min:0',
            'funded_by' => 'nullable|string|in:institution,self,donor,sponsor',
            'appraisal_relevance' => 'nullable|string|max:500',
            'is_completed' => 'boolean',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $trainingData = array_merge($validated, [
                'approved_by' => $request->user()->staff_id ?? Staff::first()?->id,
                'approved_at' => now(),
            ]);

            if (!empty($validated['assign_all'])) {
                unset($trainingData['staff_ids']);
                unset($trainingData['staff_id']);
            } elseif (!empty($validated['staff_ids'])) {
                $trainingData['staff_ids'] = $validated['staff_ids'];
                unset($trainingData['staff_id']);
            } else {
                unset($trainingData['staff_ids']);
            }

            $training = ProfessionalDevelopment::create($trainingData);

            $assignedStaffIds = $training->assigned_staff_ids;
            if (empty($assignedStaffIds)) {
                $assignedStaffIds = Staff::pluck('id')->toArray();
            }

            foreach ($assignedStaffIds as $staffId) {
                $staff = Staff::find($staffId);
                if ($staff && $staff->user_id) {
                    \App\Services\PlatformNotificationService::notifyUser(
                        $staff->user_id,
                        'New Training Assigned',
                        "You have been assigned to '{$training->activity_name}' starting {$training->start_date->format('Y-m-d')}.",
                        'professional_development',
                        $training->id,
                        'normal'
                    );
                }
            }
        });

        return redirect()->route('hr.training.index')->with('success', 'Training record created and staff notified.');
    }

    public function edit(int $id): View
    {
        $training = ProfessionalDevelopment::findOrFail($id);
        $staff = Staff::orderBy('first_name')->get(['id', 'first_name', 'surname', 'employee_number']);

        return view('hr.training.edit', ['training' => $training, 'staff' => $staff]);
    }

    public function update(Request $request, int $id)
    {
        $training = ProfessionalDevelopment::findOrFail($id);

        $validated = $request->validate([
            'staff_ids' => 'nullable|array',
            'staff_ids.*' => 'exists:staff,id',
            'assign_all' => 'boolean',
            'activity_type' => 'required|string|in:training,workshop,conference,seminar,cpd,study_leave,attachment,mentorship',
            'activity_name' => 'required|string|max:300',
            'organizer' => 'nullable|string|max:300',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'hours_or_days' => 'nullable|numeric|min:0',
            'cpd_credits_earned' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:300',
            'is_external' => 'boolean',
            'cost' => 'nullable|numeric|min:0',
            'funded_by' => 'nullable|string|in:institution,self,donor,sponsor',
            'appraisal_relevance' => 'nullable|string|max:500',
            'is_completed' => 'boolean',
        ]);

        $trainingData = $validated;

        if (!empty($validated['assign_all'])) {
            unset($trainingData['staff_ids']);
            unset($trainingData['staff_id']);
        } elseif (!empty($validated['staff_ids'])) {
            $trainingData['staff_ids'] = $validated['staff_ids'];
            unset($trainingData['staff_id']);
        } else {
            unset($trainingData['staff_ids']);
        }

        $training->update($trainingData);

        return redirect()->route('hr.training.index')->with('success', 'Training record updated successfully.');
    }

    public function destroy(Request $request, int $id)
    {
        $training = ProfessionalDevelopment::findOrFail($id);
        $training->delete();

        return redirect()->route('hr.training.index')->with('success', 'Training record deleted successfully.');
    }
}