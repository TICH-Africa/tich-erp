@if (! $selectedIntake)
    <article class="tich-card">
        <p class="tich-text">Select or <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'section' => 'intakes'])) }}" class="tich-link">create an intake</a> first.</p>
    </article>
@elseif ($periods->isEmpty())
    <article class="tich-card">
        <p class="tich-text">Save the <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'section' => 'structure'])) }}" class="tich-link">programme structure</a> to generate semesters.</p>
    </article>
@else
    @php
        $intakeEditable = $selectedIntake->isDraft();
        $returnFields = [
            'return_program' => $program->id,
            'return_learning_department' => $learningDepartment?->id,
            'return_intake' => $selectedIntake->id,
            'return_section' => 'semesters',
        ];
    @endphp

    <style>
        .tich-unit-sortable tbody tr[data-sortable-row] { cursor: default; }
        .tich-unit-sortable tbody tr.is-dragging { opacity: 0.45; cursor: grabbing; }
        .tich-drag-handle {
            color: var(--tich-muted, #64748b);
            user-select: none;
            width: 2rem;
            text-align: center;
            font-size: 1.1rem;
            line-height: 1;
            cursor: grab;
        }
        .tich-drag-handle:active { cursor: grabbing; }
        .tich-unit-pick-list {
            display: grid;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }
        .tich-unit-pick-list label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9375rem;
        }
    </style>

    <article class="tich-card">
        <div class="tich-dept-panel__head" style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:1rem; align-items:start;">
            <div>
                <h2 class="tich-h3">{{ $selectedIntake->intakeLabel() }} — units by {{ $program->usesBlocks() ? 'block' : 'semester' }}</h2>
                <p class="tich-text">
                    {{ $totalTeachingPeriods }} {{ $program->usesBlocks() ? 'blocks' : 'semesters' }} for this intake.
                    @if ($intakeEditable)
                        Select units below for each {{ $program->usesBlocks() ? 'block' : 'semester' }}, drag to reorder, then save.
                    @else
                        <span class="tich-caption">· {{ ucwords(str_replace('_', ' ', $selectedIntake->status)) }} (read-only)</span>
                    @endif
                </p>
            </div>
            @if ($intakeEditable)
                @can('academics.write')
                    <button type="button" class="tich-btn tich-btn-secondary" data-open-modal="unit-create">Create unit</button>
                @endcan
            @endif
        </div>

        @error('unit_ids')
            <p class="tich-text tich-mt-4" style="color: var(--tich-danger, #b91c1c);">{{ $message }}</p>
        @enderror

        @if (! $intakeEditable)
            @can('academics.write')
                <div class="tich-inset-panel tich-mt-4">
                    <p class="tich-text" style="margin:0 0 0.75rem;">
                        This intake is {{ str_replace('_', ' ', $selectedIntake->status) }} and cannot be edited.
                        Return it to draft to assign or reorder units.
                    </p>
                    <form method="POST" action="{{ route('departments.academics.versions.reopen', array_merge($hub, ['version' => $selectedIntake->id])) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="tich-btn tich-btn-primary">Return to draft</button>
                    </form>
                </div>
            @else
                <p class="tich-caption tich-mt-4">This intake is locked. Contact an academic officer to make changes.</p>
            @endcan
        @endif

        @if ($intakeEditable)
            <form id="intake-sync-form" method="POST" action="{{ route('departments.academics.programs.intakes.sync-units', array_merge($hub, ['program' => $program->id, 'version' => $selectedIntake->id])) }}" style="display:none;">
                @csrf
            </form>
        @endif

        @can('academics.write')
            @if ($selectedIntake->status !== 'superseded')
                <form id="intake-periods-form" method="POST" action="{{ route('departments.academics.programs.intakes.sync-periods', array_merge($hub, ['program' => $program->id, 'version' => $selectedIntake->id])) }}" class="tich-inset-panel tich-mt-4">
                    @csrf
                    <p class="tich-text" style="margin:0 0 1rem;">Set the start and end date for each {{ $program->usesBlocks() ? 'block' : 'semester' }} in this intake. Students see these dates in the portal.</p>
                    @php $periodIndex = 0; @endphp
                    @foreach ($periods as $period)
                        @php
                            $periodKey = $period['semester'].':'.($period['block_id'] ?? '');
                            $periodDate = $periodDates->get($periodKey);
                            $periodIndex++;
                        @endphp
                        <input type="hidden" name="periods[{{ $periodIndex }}][semester]" value="{{ $period['semester'] }}">
                        @if ($period['block_id'])
                            <input type="hidden" name="periods[{{ $periodIndex }}][block_id]" value="{{ $period['block_id'] }}">
                        @endif
                        <div class="tich-period-dates-grid">
                            <div>
                                <span class="tich-label">{{ $period['label'] }}</span>
                            </div>
                            <div class="tich-form-group">
                                <label class="tich-caption">Start date</label>
                                <input type="date" name="periods[{{ $periodIndex }}][start_date]" class="tich-input" value="{{ old('periods.'.$periodIndex.'.start_date', $periodDate?->start_date?->format('Y-m-d')) }}">
                            </div>
                            <div class="tich-form-group">
                                <label class="tich-caption">End date</label>
                                <input type="date" name="periods[{{ $periodIndex }}][end_date]" class="tich-input" value="{{ old('periods.'.$periodIndex.'.end_date', $periodDate?->end_date?->format('Y-m-d')) }}">
                            </div>
                        </div>
                    @endforeach
                    <button type="submit" class="tich-btn tich-btn-secondary">Save semester dates</button>
                </form>
            @endif
        @endcan

        @if ($intakeEditable && $availableUnits->isNotEmpty())
            @php $inactiveCatalogUnits = $availableUnits->where('status', '!=', 'active')->count(); @endphp
            @if ($inactiveCatalogUnits > 0)
                <p class="tich-caption tich-mt-4">
                    {{ $inactiveCatalogUnits }} catalog unit(s) are still draft or pending — you can map them now; approve them in the
                    <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'intake' => $selectedIntake->id, 'section' => 'catalog'])) }}" class="tich-link">unit catalog</a> before submitting the intake.
                </p>
            @endif
        @elseif ($intakeEditable && $catalogUnits->isEmpty())
            <p class="tich-text tich-mt-4">
                No units in the catalog yet.
                @can('academics.write')
                    Click <strong>Create unit</strong> above to add the first one, then assign it to a semester below.
                @else
                    Create units in the <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'section' => 'catalog'])) }}" class="tich-link">unit catalog</a> first.
                @endcan
            </p>
        @endif

        @foreach ($periods as $period)
            @php
                $periodMappings = $program->usesBlocks()
                    ? $mappingsByBlock->get($period['block_id'], collect())
                    : $mappingsBySemester->get($period['semester'], collect());
                $periodAvailableUnits = $availableUnits->reject(fn ($unit) => in_array($unit->id, $assignedUnitIds, true));
                $periodKey = $period['semester'].':'.($period['block_id'] ?? '');
                $periodDate = $periodDates->get($periodKey);
            @endphp

            <fieldset class="tich-mt-6" style="border:1px solid var(--tich-border); border-radius:0.5rem; padding:1rem;">
                <legend class="tich-h3" style="padding:0 0.5rem;">
                    {{ $period['label'] }}
                    @if (! $program->usesBlocks())
                        <span class="tich-caption">· Semester {{ $period['semester'] }} of {{ $totalTeachingPeriods }}</span>
                    @endif
                    @if ($periodDate?->scheduleLabel())
                        <span class="tich-caption">· {{ $periodDate->scheduleLabel() }}</span>
                    @endif
                </legend>

                @if ($periodMappings->isEmpty())
                    <p class="tich-caption tich-mb-4">No units assigned yet.</p>
                @else
                    <div style="overflow-x:auto;">
                        <table class="tich-admin-table tich-unit-sortable">
                            <thead>
                                <tr>
                                    @if ($intakeEditable)
                                        <th aria-label="Reorder"></th>
                                        <th>Keep</th>
                                    @endif
                                    <th>Unit</th>
                                    <th>Contact hrs</th>
                                    <th>Total learning hrs</th>
                                    <th>Core</th>
                                </tr>
                            </thead>
                            <tbody @if ($intakeEditable) data-unit-sortable @endif>
                                @foreach ($periodMappings as $map)
                                    @php($mappingIndex++)
                                    <tr @if ($intakeEditable) data-sortable-row @endif>
                                        @if ($intakeEditable)
                                            <td class="tich-drag-handle" title="Drag to reorder">⋮⋮</td>
                                            <td>
                                                <input form="intake-sync-form" type="hidden" name="mappings[{{ $mappingIndex }}][unit_id]" value="{{ $map->unit_id }}">
                                                <input form="intake-sync-form" type="hidden" name="mappings[{{ $mappingIndex }}][semester]" value="{{ $period['semester'] ?? $map->semester }}">
                                                <input form="intake-sync-form" type="hidden" name="mappings[{{ $mappingIndex }}][block_id]" value="{{ $period['block_id'] }}">
                                                <input form="intake-sync-form" type="hidden" name="mappings[{{ $mappingIndex }}][display_order]" value="{{ $map->display_order ?? $loop->iteration }}" data-sort-order-field>
                                                <input form="intake-sync-form" type="hidden" name="mappings[{{ $mappingIndex }}][priority]" value="{{ $map->priority ?? $loop->iteration }}" data-sort-order-field>
                                                <input form="intake-sync-form" type="checkbox" name="mappings[{{ $mappingIndex }}][include]" value="1" checked>
                                            </td>
                                        @endif
                                        <td>
                                            {{ $map->unit?->unit_code }} — {{ $map->unit?->unit_name }}
                                            @if (($map->unit?->status ?? '') !== 'active')
                                                <span class="tich-caption">· {{ ucwords(str_replace('_', ' ', $map->unit->status)) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($intakeEditable)
                                                <input form="intake-sync-form" type="number" name="mappings[{{ $mappingIndex }}][contact_hours]" class="tich-input" style="width:5rem;" min="0" value="{{ $map->contact_hours ?? 0 }}">
                                            @else
                                                {{ $map->contact_hours ?? 0 }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($intakeEditable)
                                                <input form="intake-sync-form" type="number" name="mappings[{{ $mappingIndex }}][total_learning_hours]" class="tich-input" style="width:5rem;" min="0" value="{{ $map->total_learning_hours ?? 0 }}">
                                            @else
                                                {{ $map->total_learning_hours ?? 0 }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($intakeEditable)
                                                <input form="intake-sync-form" type="checkbox" name="mappings[{{ $mappingIndex }}][is_compulsory]" value="1" @checked($map->is_compulsory)>
                                            @else
                                                {{ $map->is_compulsory ? 'Yes' : 'No' }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($intakeEditable)
                    @can('academics.write')
                        @if ($periodAvailableUnits->isNotEmpty())
                            <form method="POST" action="{{ route('departments.academics.programs.intakes.add-unit', array_merge($hub, ['program' => $program->id, 'version' => $selectedIntake->id, 'semester' => $period['semester'] ?? 1])) }}" class="tich-mt-4">
                                @csrf
                                @if ($period['block_id'])
                                    <input type="hidden" name="block_id" value="{{ $period['block_id'] }}">
                                @endif
                                <p class="tich-label">Assign units to this {{ $program->usesBlocks() ? 'block' : 'semester' }}</p>
                                <div class="tich-unit-pick-list">
                                    @foreach ($periodAvailableUnits as $unit)
                                        <label>
                                            <input type="checkbox" name="unit_ids[]" value="{{ $unit->id }}">
                                            <span>
                                                {{ $unit->unit_code }} — {{ $unit->unit_name }}
                                                @if ($unit->status !== 'active')
                                                    <span class="tich-caption">({{ ucwords(str_replace('_', ' ', $unit->status)) }})</span>
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <button type="submit" class="tich-btn tich-btn-secondary tich-mt-4">Assign selected units</button>
                            </form>
                        @elseif ($catalogUnits->isNotEmpty())
                            <p class="tich-caption tich-mt-4">All catalog units are already assigned in this intake.</p>
                        @endif
                    @endcan
                @endif
            </fieldset>
        @endforeach

        @if ($intakeEditable)
            @can('academics.write')
                <button type="submit" form="intake-sync-form" class="tich-btn tich-btn-primary tich-mt-6">Save intake mapping</button>
            @endcan
            <script src="{{ asset('js/tich-unit-sort.js') }}" defer></script>
        @endif
    </article>

    @if ($intakeEditable)
        @can('academics.write')
            @include('academics.units.partials.modal', [
                'modalId' => 'unit-create',
                'unit' => null,
                'departments' => collect([$program->department])->filter(),
                'hub' => $hub,
                'curriculumReturn' => $returnFields,
                'defaultDepartmentId' => $program->department_id,
                'periods' => $periods,
                'selectedIntake' => $selectedIntake,
            ])
            @include('admin.partials.tich-modal-assets')
        @endcan
    @endif
@endif
