<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\JobVacancy;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VacancyController extends Controller
{
    public function __construct(protected AuditService $auditService) {}

    public function index(Request $request)
    {
        $query = JobVacancy::query()
            ->with(['department', 'createdBy'])
            ->when($request->department_id, fn ($q, $id) => $q->where('department_id', $id))
            ->when($request->employment_type, fn ($q, $type) => $q->where('employment_type', $type))
            ->when($request->is_published, fn ($q, $published) => $q->where('is_published', $published))
            ->when($request->is_closed, fn ($q, $closed) => $q->where('is_closed', $closed))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('vacancy_number', 'like', "%{$search}%")
                        ->orWhere('job_title', 'like', "%{$search}%")
                        ->orWhere('job_description', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at');

        $perPage = (int) ($request->per_page ?? 25);
        $vacancies = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => $vacancies->items(),
            'meta' => [
                'total' => $vacancies->total(),
                'per_page' => $vacancies->perPage(),
                'current_page' => $vacancies->currentPage(),
                'last_page' => $vacancies->lastPage(),
            ],
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
            'is_closed' => 'boolean',
            'closes_automatically' => 'boolean',
        ]);

        $vacancy = DB::transaction(function () use ($validated, $request) {
            $vacancy = JobVacancy::create(array_merge($validated, [
                'vacancy_number' => 'VAC-' . strtoupper(uniqid()),
                'is_published' => $validated['is_published'] ?? 0,
                'is_closed' => $validated['is_closed'] ?? 0,
                'closes_automatically' => $validated['closes_automatically'] ?? 1,
                'slots_filled' => 0,
                'created_by' => $request->user()->id,
            ]));

            $this->auditService->log(
                'hr.vacancy.created',
                'job_vacancies',
                $vacancy->id,
                null,
                $vacancy->toArray(),
                'Vacancy created',
                'success',
                $request->user()->id,
                $request
            );

            return $vacancy;
        });

        return response()->json(['data' => $vacancy->load('department', 'createdBy')], 201);
    }

    public function show(int $id)
    {
        $vacancy = JobVacancy::with(['department', 'createdBy', 'applications'])->findOrFail($id);

        return response()->json(['data' => $vacancy]);
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
            'is_closed' => 'sometimes|boolean',
            'closes_automatically' => 'sometimes|boolean',
        ]);

        $oldValues = $vacancy->only(array_keys($validated));

        DB::transaction(function () use ($vacancy, $validated, $request) {
            $vacancy->update($validated);

            $this->auditService->log(
                'hr.vacancy.updated',
                'job_vacancies',
                $vacancy->id,
                $oldValues,
                $vacancy->only(array_keys($validated)),
                'Vacancy updated',
                'success',
                $request->user()->id,
                $request
            );
        });

        return response()->json(['data' => $vacancy->fresh()->load('department', 'createdBy')]);
    }

    public function destroy(Request $request, int $id)
    {
        $vacancy = JobVacancy::findOrFail($id);

        $this->auditService->log(
            'hr.vacancy.deleted',
            'job_vacancies',
            $vacancy->id,
            $vacancy->toArray(),
            null,
            'Vacancy deleted',
            'success',
            $request->user()->id,
            $request
        );

        $vacancy->delete();

        return response()->json(null, 204);
    }

    public function togglePublish(Request $request, int $id)
    {
        $vacancy = JobVacancy::findOrFail($id);

        $vacancy->update([
            'is_published' => $vacancy->is_published ? 0 : 1,
        ]);

        $this->auditService->log(
            'hr.vacancy.publish_toggled',
            'job_vacancies',
            $vacancy->id,
            ['is_published' => $vacancy->getOriginal('is_published')],
            ['is_published' => $vacancy->is_published],
            'Vacancy publish status toggled',
            'success',
            $request->user()->id,
            $request
        );

        return redirect()->route('hr.vacancies.show', $vacancy)->with('success', 'Vacancy publish status updated.');
    }
}
