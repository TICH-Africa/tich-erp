<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\JobVacancy;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CareerController extends Controller
{
    public function index(Request $request): View
    {
        $query = JobVacancy::query()
            ->with(['department', 'createdBy'])
            ->where('is_published', 1)
            ->where(function ($q) {
                $q->where('is_closed', 0)
                    ->orWhere('closing_date', '>=', now()->toDateString());
            })
            ->orderByDesc('created_at');

        $vacancies = $query->get();

        $departments = \App\Models\Department::query()
            ->where('is_active', 1)
            ->orderBy('dept_name')
            ->get(['id', 'dept_name']);

        $employmentTypes = collect(['permanent', 'contract', 'intern', 'visiting', 'casual'])->map(fn ($type) => [
            'value' => $type,
            'label' => ucfirst($type),
        ]);

        return view('careers', [
            'vacancies' => $vacancies,
            'departments' => $departments,
            'employmentTypes' => $employmentTypes,
            'filters' => [
                'search' => $request->search,
                'department_id' => $request->department_id,
                'employment_type' => $request->employment_type,
            ],
        ]);
    }

    public function show(int $id): View
    {
        $vacancy = JobVacancy::query()
            ->with(['department', 'createdBy'])
            ->where('is_published', 1)
            ->findOrFail($id);

        return view('careers.show', ['vacancy' => $vacancy]);
    }
}
