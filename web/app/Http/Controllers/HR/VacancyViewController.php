<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\JobVacancy;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VacancyViewController extends Controller
{
    public function index(): View
    {
        $vacancies = JobVacancy::with(['department', 'createdBy'])
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('hr.vacancies.index', ['vacancies' => $vacancies]);
    }

    public function create(): View
    {
        $departments = Department::orderBy('dept_name')->get(['id', 'dept_name']);

        return view('hr.vacancies.create', [
            'departments' => $departments,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'job_title' => 'required|string|max:200',
            'department_id' => 'required|exists:departments,id',
            'employment_type' => 'required|string|in:permanent,contract,intern,visiting,casual',
            'position_grade' => 'nullable|string|max:20',
            'slots_available' => 'required|integer|min:1',
            'job_description' => 'required|string',
            'requirements' => 'required|string',
            'responsibilities' => 'required|string',
            'salary_scale' => 'nullable|string|max:200',
            'benefits' => 'nullable|string',
            'min_qualification' => 'required|string|max:50',
            'closing_date' => 'required|date',
            'is_published' => 'boolean',
            'closes_automatically' => 'boolean',
        ]);

        $vacancy = app(\App\Services\StaffLifecycleService::class); // We'll use the VacancyController API logic here
        // Actually let's just call the VacancyController directly
        $vacancy = \App\Models\JobVacancy::create(array_merge($validated, [
            'vacancy_number' => 'VAC-' . strtoupper(uniqid()),
            'is_published' => $validated['is_published'] ?? 0,
            'is_closed' => 0,
            'closes_automatically' => $validated['closes_automatically'] ?? 1,
            'slots_filled' => 0,
            'created_by' => $request->user()->id,
        ]));

        return redirect()->route('hr.vacancies.show', $vacancy)->with('success', 'Vacancy created successfully.');
    }

    public function show(int $id): View
    {
        $vacancy = JobVacancy::with(['department', 'createdBy', 'applications'])->findOrFail($id);

        return view('hr.vacancies.show', ['vacancy' => $vacancy]);
    }

    public function edit(int $id): View
    {
        $vacancy = JobVacancy::findOrFail($id);
        $departments = Department::orderBy('dept_name')->get(['id', 'dept_name']);

        return view('hr.vacancies.edit', [
            'vacancy' => $vacancy,
            'departments' => $departments,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $vacancy = JobVacancy::findOrFail($id);

        $validated = $request->validate([
            'job_title' => 'sometimes|string|max:200',
            'department_id' => 'sometimes|exists:departments,id',
            'employment_type' => 'sometimes|string|in:permanent,contract,intern,visiting,casual',
            'position_grade' => 'nullable|string|max:20',
            'slots_available' => 'sometimes|integer|min:1',
            'job_description' => 'sometimes|string',
            'requirements' => 'sometimes|string',
            'responsibilities' => 'sometimes|string',
            'salary_scale' => 'nullable|string|max:200',
            'benefits' => 'nullable|string',
            'min_qualification' => 'sometimes|string|max:50',
            'closing_date' => 'sometimes|date',
            'is_published' => 'sometimes|boolean',
            'closes_automatically' => 'sometimes|boolean',
        ]);

        $vacancy->update($validated);

        return redirect()->route('hr.vacancies.show', $vacancy)->with('success', 'Vacancy updated successfully.');
    }

    public function destroy(Request $request, int $id)
    {
        $vacancy = JobVacancy::findOrFail($id);
        $vacancy->delete();

        return redirect()->route('hr.vacancies.index')->with('success', 'Vacancy deleted successfully.');
    }
}
