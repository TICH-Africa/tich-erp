@if (! $selectedIntake)
    <article class="tich-card">
        <h2 class="tich-h3">Semester units</h2>
        <p class="tich-text">Select an existing intake to view and manage its semester units, or <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'section' => 'intakes'])) }}" class="tich-link">create a new intake</a>.</p>

        @if ($intakes->isNotEmpty())
            <div class="tich-table-panel tich-mt-6">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Intake</th>
                            <th>Format</th>
                            <th>Units</th>
                            <th>Status</th>
                            <th>Timeline</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($intakes as $intakeOption)
                            @php
                                $intakeMappings = $intakeOption->relationLoaded('items') ? $intakeOption->items : collect();
                                $unitCount = $intakeMappings->count();
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $intakeOption->intakeLabel() }}</strong>
                                </td>
                                <td>{{ $formats[$intakeOption->curriculum_format] ?? $intakeOption->curriculum_format }}</td>
                                <td>{{ $unitCount }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $intakeOption->status)) }}</td>
                                <td class="tich-caption">
                                    @if ($intakeOption->published_at)
                                        Published {{ $intakeOption->published_at->format('d M Y') }}
                                    @elseif ($intakeOption->submitted_at)
                                        Submitted {{ $intakeOption->submitted_at->format('d M Y') }}
                                    @elseif ($intakeOption->created_at)
                                        Created {{ $intakeOption->created_at->format('d M Y') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'intake' => $intakeOption->id, 'section' => 'semesters'])) }}" class="tich-btn tich-btn-secondary" style="font-size:0.875rem; padding:0.35rem 0.75rem;">
                                        Open units
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="tich-text tich-mt-4">No intakes created yet. Use the intakes tab to create one.</p>
        @endif
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
        $expandedSemester = request()->integer('semester') ?: null;
        $expandedBlockId = request()->has('block_id') ? request()->integer('block_id') : null;
        $defaultCampusId = $allocationCampuses->first()?->id;
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
                <h2 class="tich-h3">{{ $selectedIntake->intakeLabel() }} - units by {{ $program->usesBlocks() ? 'block' : 'semester' }}</h2>
                <p class="tich-text">
                    {{ $totalTeachingPeriods }} {{ $program->usesBlocks() ? 'blocks' : 'semesters' }} for this intake.
                    @if ($intakeEditable)
                        Expand a {{ $program->usesBlocks() ? 'block' : 'semester' }} to set dates and manage units, then save.
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

        @if ($intakeEditable && $availableUnits->isNotEmpty())
            @php $inactiveCatalogUnits = $availableUnits->where('status', '!=', 'active')->count(); @endphp
            @if ($inactiveCatalogUnits > 0)
                <p class="tich-caption tich-mt-4">
                    {{ $inactiveCatalogUnits }} catalog unit(s) are still draft or pending - you can map them now; approve them in the
                    <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'intake' => $selectedIntake->id, 'section' => 'catalog'])) }}" class="tich-link">unit catalog</a> before submitting the intake.
                </p>
            @endif
        @elseif ($intakeEditable && $catalogUnits->isEmpty())
            <p class="tich-text tich-mt-4">
                No units in the catalog yet.
                @can('academics.write')
                    Click <strong>Create unit</strong> above to add the first one, then assign it to a semester.
                @else
                    Create units in the <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'section' => 'catalog'])) }}" class="tich-link">unit catalog</a> first.
                @endcan
            </p>
        @endif

        <div class="tich-semester-accordion tich-mt-6" data-semester-accordion>
            @php $periodIndex = 0; @endphp
            @foreach ($periods as $period)
                @php
                    $periodMappings = $program->usesBlocks()
                        ? $mappingsByBlock->get($period['block_id'], collect())
                        : $mappingsBySemester->get($period['semester'], collect());
                    $periodAvailableUnits = $availableUnits->reject(fn ($unit) => in_array($unit->id, $assignedUnitIds, true));
                    $periodKey = $period['semester'].':'.($period['block_id'] ?? '');
                    $periodDate = $periodDates->get($periodKey);
                    $periodIndex++;
                    $semesterStart = old('periods.'.$periodIndex.'.start_date', $periodDate?->start_date?->format('Y-m-d'));
                    $semesterEnd = old('periods.'.$periodIndex.'.end_date', $periodDate?->end_date?->format('Y-m-d'));
                    $learningStart = old('periods.'.$periodIndex.'.learning_start_date', $periodDate?->learning_start_date?->format('Y-m-d') ?: $semesterStart);
                    $learningEnd = old('periods.'.$periodIndex.'.learning_end_date', $periodDate?->learning_end_date?->format('Y-m-d'));
                    $examStart = old('periods.'.$periodIndex.'.exam_start_date', $periodDate?->exam_start_date?->format('Y-m-d'));
                    $examEnd = old('periods.'.$periodIndex.'.exam_end_date', $periodDate?->exam_end_date?->format('Y-m-d') ?: $semesterEnd);
                    $isExpanded = $expandedSemester
                        ? ((int) $period['semester'] === $expandedSemester && (int) ($period['block_id'] ?? 0) === (int) ($expandedBlockId ?? 0))
                        : $loop->first;
                @endphp

                <section class="tich-semester-accordion__item" data-semester-accordion-item>
                    <button
                        type="button"
                        class="tich-semester-accordion__trigger"
                        data-semester-accordion-trigger
                        aria-expanded="{{ $isExpanded ? 'true' : 'false' }}"
                    >
                        <span class="tich-semester-accordion__heading">
                            <span class="tich-semester-accordion__title">{{ $period['label'] }}</span>
                            @if (! $program->usesBlocks())
                                <span class="tich-semester-accordion__meta">Semester {{ $period['semester'] }} of {{ $totalTeachingPeriods }}</span>
                            @endif
                        </span>
                        <span class="tich-semester-accordion__summary">
                            @if ($periodDate?->scheduleLabel())
                                <span class="tich-semester-accordion__meta">{{ $periodDate->scheduleLabel() }}</span>
                            @else
                                <span class="tich-semester-accordion__meta">Dates not set</span>
                            @endif
                            <span class="tich-semester-accordion__meta">{{ $periodMappings->count() }} {{ str('unit')->plural($periodMappings->count()) }}</span>
                            <span class="tich-semester-accordion__chevron" aria-hidden="true"></span>
                        </span>
                    </button>

                    <div class="tich-semester-accordion__panel" data-semester-accordion-panel @unless($isExpanded) hidden @endunless>
                        @can('academics.write')
                            @if ($selectedIntake->status !== 'superseded')
                                <form method="POST" action="{{ route('departments.academics.programs.intakes.sync-periods', array_merge($hub, ['program' => $program->id, 'version' => $selectedIntake->id])) }}" class="tich-inset-panel tich-mb-6">
                                    @csrf
                                    <input type="hidden" name="learning_department" value="{{ $learningDepartment?->id }}">
                                    <p class="tich-text" style="margin:0 0 1rem;">Semester, learning, and exam dates. Learning start defaults to semester start; exam end defaults to semester end.</p>
                                    <input type="hidden" name="periods[1][semester]" value="{{ $period['semester'] }}">
                                    @if ($period['block_id'])
                                        <input type="hidden" name="periods[1][block_id]" value="{{ $period['block_id'] }}">
                                    @endif
                                    <div class="tich-period-dates-block" data-period-dates-block>
                                        <div class="tich-period-dates-grid">
                                            <div class="tich-form-group">
                                                <label class="tich-caption">Semester start</label>
                                                <input type="date" name="periods[1][start_date]" class="tich-input" data-semester-start value="{{ $semesterStart }}">
                                            </div>
                                            <div class="tich-form-group">
                                                <label class="tich-caption">Semester end</label>
                                                <input type="date" name="periods[1][end_date]" class="tich-input" data-semester-end value="{{ $semesterEnd }}">
                                            </div>
                                        </div>
                                        <p class="tich-caption tich-mt-2" style="margin-bottom:0.35rem;">Learning period</p>
                                        <div class="tich-period-dates-grid">
                                            <div class="tich-form-group">
                                                <label class="tich-caption">Learning start</label>
                                                <input type="date" name="periods[1][learning_start_date]" class="tich-input" data-learning-start value="{{ $learningStart }}">
                                            </div>
                                            <div class="tich-form-group">
                                                <label class="tich-caption">Learning end</label>
                                                <input type="date" name="periods[1][learning_end_date]" class="tich-input" data-learning-end value="{{ $learningEnd }}">
                                            </div>
                                        </div>
                                        <p class="tich-caption tich-mt-2" style="margin-bottom:0.35rem;">Exam period</p>
                                        <div class="tich-period-dates-grid">
                                            <div class="tich-form-group">
                                                <label class="tich-caption">Exam start</label>
                                                <input type="date" name="periods[1][exam_start_date]" class="tich-input" data-exam-start @if ($learningEnd) min="{{ $learningEnd }}" @endif value="{{ $examStart }}">
                                            </div>
                                            <div class="tich-form-group">
                                                <label class="tich-caption">Exam end</label>
                                                <input type="date" name="periods[1][exam_end_date]" class="tich-input" data-exam-end value="{{ $examEnd }}">
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="tich-btn tich-btn-secondary">Save dates for this {{ $program->usesBlocks() ? 'block' : 'semester' }}</button>
                                </form>
                            @endif
                        @else
                            @if ($periodDate?->scheduleLabel())
                                <p class="tich-caption tich-mb-4">{{ $periodDate->scheduleLabel() }}</p>
                            @endif
                        @endcan

                        @if ($periodMappings->isEmpty())
                            <p class="tich-caption tich-mb-4">No units assigned yet.</p>
                        @else
                            <div class="tich-table-wrap">
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
                                            @can('academics.write')
                                                <th>Lecturer</th>
                                            @endcan
                                        </tr>
                                    </thead>
                                    <tbody @if ($intakeEditable) data-unit-sortable @endif>
                                        @php $mappingIndex = -1; @endphp
                                        @foreach ($periodMappings as $map)
                                            @php $mappingIndex++; @endphp
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
                                                    {{ $map->unit?->unit_code }} - {{ $map->unit?->unit_name }}
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
                                                @can('academics.write')
                                                    @php
                                                        $unitAllocations = ($catalogAllocations[$map->unit_id] ?? collect());
                                                        $currentAllocation = $unitAllocations->first();
                                                    @endphp
                                                    <td>
                                                        @if ($currentAllocation)
                                                            <div class="tich-catalog-alloc-card">
                                                                <div class="tich-catalog-alloc-card__name">{{ $currentAllocation->staff?->fullName() }}</div>
                                                                <div class="tich-catalog-alloc-card__meta">
                                                                    {{ $currentAllocation->semester?->displayLabel() }}
                                                                    · {{ $currentAllocation->campus?->campus_name }}
                                                                    @if ($currentAllocation->is_coordinator)
                                                                        · <strong>Coordinator</strong>
                                                                    @endif
                                                                </div>
                                                                <form method="POST" action="{{ route('departments.academics.programs.allocations.destroy', array_merge($hub, ['program' => $program->id, 'allocation' => $currentAllocation->id])) }}" class="tich-mt-2" onsubmit="return confirm('Remove this lecturer allocation?')">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <input type="hidden" name="intake" value="{{ $selectedIntake->id }}">
                                                                    <input type="hidden" name="section" value="semesters">
                                                                    <input type="hidden" name="learning_department" value="{{ $learningDepartment?->id }}">
                                                                    <button type="submit" class="tich-link" style="font-size:0.8125rem;">Remove</button>
                                                                </form>
                                                            </div>
                                                        @else
                                                            <span class="tich-caption">No lecturer assigned</span>
                                                        @endif
                                                        <details class="tich-catalog-assign">
                                                            <summary>{{ $currentAllocation ? 'Change lecturer' : '+ Assign lecturer' }}</summary>
                                                            <form method="POST" action="{{ route('departments.academics.programs.allocations.store', array_merge($hub, ['program' => $program->id])) }}" class="tich-catalog-assign__body">
                                                                @csrf
                                                                <input type="hidden" name="intake" value="{{ $selectedIntake->id }}">
                                                                <input type="hidden" name="unit_id" value="{{ $map->unit_id }}">
                                                                <input type="hidden" name="teaching_period" value="{{ $period['semester'] ?? $map->semester }}">
                                                                <input type="hidden" name="section" value="semesters">
                                                                @if ($learningDepartment)
                                                                    <input type="hidden" name="learning_department" value="{{ $learningDepartment->id }}">
                                                                @endif
                                                                <div class="tich-form-group" style="margin:0;">
                                                                    <label class="tich-label">Lecturer</label>
                                                                    <select name="staff_id" class="tich-input" required>
                                                                        @forelse ($allocationStaffList as $member)
                                                                            <option value="{{ $member->id }}" @selected($currentAllocation?->staff_id === $member->id)>{{ $member->fullName() }}</option>
                                                                        @empty
                                                                            <option value="" disabled>No teaching staff found</option>
                                                                        @endforelse
                                                                    </select>
                                                                </div>
                                                                <div class="tich-form-group" style="margin:0;">
                                                                    <label class="tich-label">Campus</label>
                                                                    <select name="campus_id" class="tich-input" required>
                                                                        @foreach ($allocationCampuses as $campus)
                                                                            <option value="{{ $campus->id }}" @selected($defaultCampusId == $campus->id)>{{ $campus->campus_name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="tich-form-group" style="margin:0;">
                                                                    <label class="tich-label">Contact hours</label>
                                                                    <input type="number" name="contact_hours_assigned" class="tich-input" value="{{ $map->contact_hours ?: 4 }}" min="0">
                                                                </div>
                                                                <label class="tich-label"><input type="checkbox" name="is_coordinator" value="1" @checked($currentAllocation?->is_coordinator)> Unit coordinator</label>
                                                                <button type="submit" class="tich-btn tich-btn-primary" @disabled($allocationStaffList->isEmpty())>Save allocation</button>
                                                            </form>
                                                        </details>
                                                    </td>
                                                @endcan
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
                                        <input type="hidden" name="learning_department" value="{{ $learningDepartment?->id }}">
                                        @if ($period['block_id'])
                                            <input type="hidden" name="block_id" value="{{ $period['block_id'] }}">
                                        @endif
                                        <p class="tich-label">Assign units to this {{ $program->usesBlocks() ? 'block' : 'semester' }}</p>
                                        <div class="tich-unit-pick-list">
                                            @foreach ($periodAvailableUnits as $unit)
                                                <label>
                                                    <input type="checkbox" name="unit_ids[]" value="{{ $unit->id }}">
                                                    <span>
                                                        {{ $unit->unit_code }} - {{ $unit->unit_name }}
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
                    </div>
                </section>
            @endforeach
        </div>

        @if ($intakeEditable)
            @can('academics.write')
                <button type="submit" form="intake-sync-form" class="tich-btn tich-btn-primary tich-mt-6">Save intake mapping</button>
            @endcan
            <script src="{{ asset('js/tich-unit-sort.js') }}" defer></script>
        @endif

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-period-dates-block]').forEach(function (block) {
                var semesterStart = block.querySelector('[data-semester-start]');
                var semesterEnd = block.querySelector('[data-semester-end]');
                var learningStart = block.querySelector('[data-learning-start]');
                var learningEnd = block.querySelector('[data-learning-end]');
                var examStart = block.querySelector('[data-exam-start]');
                var examEnd = block.querySelector('[data-exam-end]');

                function syncExamStartMin() {
                    if (!examStart || !learningEnd) return;
                    examStart.min = learningEnd.value || '';
                }

                if (semesterStart) {
                    semesterStart.addEventListener('change', function () {
                        if (learningStart && (!learningStart.value || learningStart.dataset.autoDefault === '1')) {
                            learningStart.value = semesterStart.value;
                            learningStart.dataset.autoDefault = '1';
                        }
                    });
                }

                if (semesterEnd) {
                    semesterEnd.addEventListener('change', function () {
                        if (examEnd && (!examEnd.value || examEnd.dataset.autoDefault === '1')) {
                            examEnd.value = semesterEnd.value;
                            examEnd.dataset.autoDefault = '1';
                        }
                    });
                }

                if (learningStart) {
                    learningStart.addEventListener('input', function () {
                        learningStart.dataset.autoDefault = learningStart.value === (semesterStart?.value || '') ? '1' : '0';
                    });
                }

                if (examEnd) {
                    examEnd.addEventListener('input', function () {
                        examEnd.dataset.autoDefault = examEnd.value === (semesterEnd?.value || '') ? '1' : '0';
                    });
                }

                if (learningEnd) {
                    learningEnd.addEventListener('change', syncExamStartMin);
                    learningEnd.addEventListener('input', syncExamStartMin);
                }

                if (learningStart && semesterStart && semesterStart.value && !learningStart.value) {
                    learningStart.value = semesterStart.value;
                }

                if (examEnd && semesterEnd && semesterEnd.value && !examEnd.value) {
                    examEnd.value = semesterEnd.value;
                }

                syncExamStartMin();
            });

            document.querySelectorAll('[data-semester-accordion]').forEach(function (accordion) {
                accordion.querySelectorAll('[data-semester-accordion-trigger]').forEach(function (trigger) {
                    trigger.addEventListener('click', function () {
                        var item = trigger.closest('[data-semester-accordion-item]');
                        var panel = item.querySelector('[data-semester-accordion-panel]');
                        var isOpen = trigger.getAttribute('aria-expanded') === 'true';

                        accordion.querySelectorAll('[data-semester-accordion-item]').forEach(function (otherItem) {
                            var otherTrigger = otherItem.querySelector('[data-semester-accordion-trigger]');
                            var otherPanel = otherItem.querySelector('[data-semester-accordion-panel]');
                            otherTrigger.setAttribute('aria-expanded', 'false');
                            otherItem.classList.remove('is-open');
                            otherPanel.hidden = true;
                        });

                        if (!isOpen) {
                            trigger.setAttribute('aria-expanded', 'true');
                            item.classList.add('is-open');
                            panel.hidden = false;
                        }
                    });
                });

                var openItem = accordion.querySelector('[data-semester-accordion-trigger][aria-expanded="true"]')?.closest('[data-semester-accordion-item]');
                if (openItem) {
                    openItem.classList.add('is-open');
                }
            });
        });
        </script>
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
