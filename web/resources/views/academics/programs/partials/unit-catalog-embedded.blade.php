@php
    $catalogHub = $hub;
        $returnFields = [
            'return_program' => $program->id,
            'return_learning_department' => $learningDepartment?->id,
            'return_intake' => $selectedIntake?->id,
            'return_section' => $section ?? 'catalog',
        ];
@endphp

<article class="tich-card tich-mt-8">
    <div class="tich-dept-panel__head" style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:1rem; align-items:start;">
        <div>
            <h2 class="tich-h3">Unit catalog</h2>
            <p class="tich-text">Create, approve, and assign units for {{ $program->department?->dept_name ?? 'this department' }} — all from this programme curriculum page.</p>
        </div>
        @can('academics.write')
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="unit-create">Add unit</button>
        @endcan
    </div>

    <div class="tich-card tich-mt-4" style="overflow-x:auto; padding:0;">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Unit</th>
                    <th>Contact hrs</th>
                    <th>Learning hrs</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($catalogUnits as $unit)
                    <tr>
                        <td>{{ $unit->unit_code }}</td>
                        <td>{{ $unit->unit_name }}</td>
                        <td>{{ $unit->contact_hours }}</td>
                        <td>{{ $unit->total_learning_hours }}</td>
                        <td>{{ $statusLabels[$unit->status] ?? ucfirst($unit->status) }}</td>
                        <td style="white-space:nowrap;">
                            @can('academics.write')
                                @if (in_array($unit->status, ['draft', 'pending_registry']))
                                    <button type="button" class="tich-link" data-open-modal="unit-edit-{{ $unit->id }}">Edit</button>
                                @endif
                                @if ($unit->status === 'draft')
                                    <form method="POST" action="{{ route('departments.academics.units.submit', array_merge($catalogHub, ['unit' => $unit->id])) }}" style="display:inline;">
                                        @csrf
                                        @foreach ($returnFields as $name => $value)
                                            @if ($value)
                                                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                                            @endif
                                        @endforeach
                                        <button type="submit" class="tich-link">Submit</button>
                                    </form>
                                @endif
                            @endcan
                            @if ($canApproveRegistry && $unit->status === 'pending_registry')
                                <form method="POST" action="{{ route('departments.academics.units.approve', array_merge($catalogHub, ['unit' => $unit->id])) }}" style="display:inline;">
                                    @csrf
                                    @foreach ($returnFields as $name => $value)
                                        @if ($value)
                                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                                        @endif
                                    @endforeach
                                    <button type="submit" class="tich-link">Approve</button>
                                </form>
                            @endif
                            @if ($selectedIntake && $selectedIntake->status === 'draft' && ! in_array($unit->id, $assignedUnitIds, true))
                                @can('academics.write')
                                    <span class="tich-caption">Assign:</span>
                                    @foreach ($periods as $period)
                                        <form method="POST" action="{{ route('departments.academics.programs.intakes.add-unit', array_merge($catalogHub, ['program' => $program->id, 'version' => $selectedIntake->id, 'semester' => $period['semester'] ?? 1])) }}" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="unit_id" value="{{ $unit->id }}">
                                            @if (! empty($period['block_id']))
                                                <input type="hidden" name="block_id" value="{{ $period['block_id'] }}">
                                            @endif
                                            <button type="submit" class="tich-link" title="{{ $period['label'] }}">{{ $program->usesBlocks() ? $period['label'] : 'S'.$period['semester'] }}</button>@if (! $loop->last),@endif
                                        </form>
                                    @endforeach
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding:2rem;text-align:center;" class="tich-text">
                            No units yet. Click <strong>Add unit</strong> to create the first one for this department.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</article>

@can('academics.write')
    @include('academics.units.partials.modal', [
        'modalId' => 'unit-create',
        'unit' => null,
        'departments' => collect([$program->department])->filter(),
        'hub' => $catalogHub,
        'curriculumReturn' => $returnFields,
        'defaultDepartmentId' => $program->department_id,
        'periods' => $periods,
        'selectedIntake' => $selectedIntake,
    ])
    @foreach ($catalogUnits as $unit)
        @if (in_array($unit->status, ['draft', 'pending_registry']))
            @include('academics.units.partials.modal', [
                'modalId' => 'unit-edit-'.$unit->id,
                'unit' => $unit,
                'departments' => collect([$program->department])->filter(),
                'hub' => $catalogHub,
                'curriculumReturn' => $returnFields,
                'defaultDepartmentId' => $program->department_id,
                'periods' => $periods,
                'selectedIntake' => $selectedIntake,
            ])
        @endif
    @endforeach
    @include('admin.partials.tich-modal-assets')
@endcan
