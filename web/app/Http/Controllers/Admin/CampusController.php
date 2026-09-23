<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class CampusController extends Controller
{
    public function __construct(protected AuditService $auditService) {}

    public function index(): View
    {
        $campuses = Campus::query()
            ->with('parentCampus:id,campus_name')
            ->orderBy('campus_name')
            ->get();

        return view('admin.campuses.index', [
            'campuses' => $campuses,
            'parentCampuses' => Campus::query()->orderBy('campus_name')->get(['id', 'campus_name']),
            'campusTypes' => Campus::typeOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'campus_name' => ['required', 'string', 'max:200'],
            'campus_type' => ['required', 'in:'.implode(',', Campus::typeKeys())],
            'parent_campus_id' => ['nullable', 'exists:campuses,id'],
            'county' => ['nullable', 'string', 'max:100'],
            'sub_county' => ['nullable', 'string', 'max:100'],
            'physical_address' => ['nullable', 'string', 'max:500'],
        ];

        if ($request->input('campus_type') === 'community_college') {
            $rules['county'] = ['required', 'string', 'max:100'];
        }

        $validated = $request->validate($rules);
        $attributes = Arr::only($validated, [
            'campus_name',
            'campus_type',
            'parent_campus_id',
            'county',
            'sub_county',
            'physical_address',
        ]);

        if ($request->input('campus_type') === 'community_college') {
            $attributes['parent_campus_id'] = null;
            $attributes['sub_county'] = null;
            $attributes['physical_address'] = null;
        }

        $campus = Campus::create([
            ...$attributes,
            'is_active' => 1,
            'created_by' => $request->user()->id,
        ]);

        $this->auditService->log(
            'core.campus.created',
            'campuses',
            $campus->id,
            null,
            $campus->only([
                'campus_name',
                'campus_type',
                'parent_campus_id',
                'county',
                'sub_county',
                'physical_address',
            ]),
            null,
            'success',
            $request->user()->id,
            $request
        );

        return back()->with('status', 'Campus created successfully.');
    }

    public function update(Request $request, Campus $campus): RedirectResponse
    {
        $rules = [
            'campus_name' => ['required', 'string', 'max:200'],
            'campus_type' => ['required', 'in:'.implode(',', Campus::typeKeys())],
            'parent_campus_id' => ['nullable', 'exists:campuses,id'],
            'county' => ['nullable', 'string', 'max:100'],
            'sub_county' => ['nullable', 'string', 'max:100'],
            'physical_address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if ($request->input('campus_type') === 'community_college') {
            $rules['county'] = ['required', 'string', 'max:100'];
        }

        $validated = $request->validate($rules);
        $old = $campus->only([
            'campus_name',
            'campus_type',
            'parent_campus_id',
            'county',
            'sub_county',
            'physical_address',
            'is_active',
        ]);
        $attributes = Arr::only($validated, [
            'campus_name',
            'campus_type',
            'parent_campus_id',
            'county',
            'sub_county',
            'physical_address',
        ]);

        if ($request->input('campus_type') === 'community_college') {
            $attributes['parent_campus_id'] = null;
            $attributes['sub_county'] = null;
            $attributes['physical_address'] = null;
        }

        $campus->update([
            ...$attributes,
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->auditService->log(
            'core.campus.updated',
            'campuses',
            $campus->id,
            $old,
            $campus->only([
                'campus_name',
                'campus_type',
                'parent_campus_id',
                'county',
                'sub_county',
                'physical_address',
                'is_active',
            ]),
            null,
            'success',
            $request->user()->id,
            $request
        );

        return back()->with('status', 'Campus updated successfully.');
    }
}
