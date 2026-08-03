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
            ->when($request->department_id, fn ($q, $id) => $q->where('department_id', $id))
            ->when($request->employment_type, fn ($q, $type) => $q->where('employment_type', $type))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('job_title', 'like', "%{$search}%")
                        ->orWhere('job_description', 'like', "%{$search}%")
                        ->orWhere('requirements', 'like', "%{$search}%")
                        ->orWhere('min_qualification', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at');

        $perPage = (int) ($request->per_page ?? 20);
        $vacancies = $query->paginate($perPage)->appends($request->query());

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
