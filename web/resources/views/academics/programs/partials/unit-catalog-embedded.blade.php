@php
    $catalogHub = $hub;
    $returnFields = [
        'return_program' => $program->id,
        'return_learning_department' => $learningDepartment?->id,
        'return_intake' => $selectedIntake?->id,
        'return_section' => $section ?? 'catalog',
    ];
    $defaultCampusId = $allocationCampuses->first()?->id;
    $intakeYearLabel = $selectedIntake?->academicYear?->year_label
        ?? ($selectedIntake?->intake_year ? (string) $selectedIntake->intake_year : null);

    $unitScheduleMap = [];
    foreach ($mappings as $mapping) {
        $unitScheduleMap[$mapping->unit_id][] = [
            'period' => $program->periodLabel((int) $mapping->semester),
            'year' => $intakeYearLabel,
            'source' => 'intake',
        ];
    }

    foreach ($catalogAllocations as $unitId => $unitAllocations) {
        foreach ($unitAllocations as $allocation) {
            $period = $allocation->semester?->displayLabel() ?? 'Semester';
            $year = $allocation->semester?->academicYear?->year_label ?? $intakeYearLabel;
            $entry = ['period' => $period, 'year' => $year, 'source' => 'allocation'];
            $existing = collect($unitScheduleMap[$unitId] ?? []);
            $duplicate = $existing->contains(fn ($row) => $row['period'] === $entry['period'] && $row['year'] === $entry['year']);
            if (! $duplicate) {
                $unitScheduleMap[$unitId][] = $entry;
            }
        }
    }

    $mappedCount = count($assignedUnitIds);
    $allocatedCount = $catalogAllocations->filter(fn ($rows) => $rows->isNotEmpty())->count();
    $activeCount = $catalogUnits->where('status', 'active')->count();
@endphp

<style>
    .tich-catalog-panel { padding: 0; overflow: hidden; }
    .tich-catalog-panel__head {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--tich-border);
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 1rem;
        align-items: flex-start;
        background: var(--tich-surface);
    }
    .tich-catalog-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-top: 1rem;
    }
    .tich-catalog-stat {
        padding: 0.5rem 0.85rem;
        border-radius: 999px;
        border: 1px solid var(--tich-border);
        background: var(--tich-surface-muted);
        color: var(--tich-text);
        font-size: 0.8125rem;
    }
    .tich-catalog-stat strong { font-weight: 600; color: var(--tich-text); }
    .tich-catalog-table-wrap { overflow-x: auto; }
    .tich-catalog-table tbody tr:nth-child(even) { background: var(--tich-surface-muted); }
    .tich-catalog-table td { vertical-align: top; color: var(--tich-text); }
    .tich-catalog-unit-code {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--tich-blue);
    }
    .tich-catalog-unit-name { font-weight: 500; margin-top: 0.15rem; color: var(--tich-text); }
    .tich-catalog-hours { white-space: nowrap; font-variant-numeric: tabular-nums; color: var(--tich-text); }
    .tich-catalog-status {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: capitalize;
    }
    .tich-catalog-status--active { background: #d1fae5; color: #065f46; }
    .tich-catalog-status--draft { background: var(--tich-surface-muted); color: var(--tich-neutral-muted); border: 1px solid var(--tich-border); }
    .tich-catalog-status--pending_registry { background: #fef3c7; color: #92400e; }
    .tich-catalog-status--archived { background: #fee2e2; color: #991b1b; }
    .tich-catalog-schedule-list { display: grid; gap: 0.45rem; margin: 0; padding: 0; list-style: none; }
    .tich-catalog-schedule-chip {
        display: inline-flex;
        flex-direction: column;
        gap: 0.1rem;
        padding: 0.45rem 0.65rem;
        border-radius: 0.5rem;
        border: 1px solid var(--tich-border);
        background: var(--tich-surface);
        min-width: 9rem;
    }
    .tich-catalog-schedule-chip__period { font-size: 0.8125rem; font-weight: 600; color: var(--tich-text); }
    .tich-catalog-schedule-chip__year { font-size: 0.75rem; color: var(--tich-neutral-muted); }
    .tich-catalog-schedule-chip--muted {
        border-style: dashed;
        background: transparent;
        color: var(--tich-neutral-muted);
    }
    .tich-catalog-alloc-list { display: grid; gap: 0.5rem; margin: 0; padding: 0; list-style: none; }
    .tich-catalog-alloc-card {
        padding: 0.65rem 0.75rem;
        border-radius: 0.5rem;
        border: 1px solid var(--tich-border);
        background: var(--tich-surface);
    }
    .tich-catalog-alloc-card__name { font-weight: 600; font-size: 0.875rem; color: var(--tich-text); }
    .tich-catalog-alloc-card__meta { font-size: 0.75rem; color: var(--tich-neutral-muted); margin-top: 0.15rem; }
    .tich-catalog-alloc-empty { font-size: 0.8125rem; color: var(--tich-neutral-muted); font-style: italic; }
    .tich-catalog-assign {
        margin-top: 0.65rem;
        border: 1px solid var(--tich-border);
        border-radius: 0.5rem;
        background: var(--tich-surface-muted);
        overflow: hidden;
    }
    .tich-catalog-assign summary {
        cursor: pointer;
        padding: 0.55rem 0.75rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--tich-blue);
        list-style: none;
    }
    .tich-catalog-assign summary::-webkit-details-marker { display: none; }
    .tich-catalog-assign__body {
        padding: 0.75rem;
        border-top: 1px solid var(--tich-border);
        display: grid;
        gap: 0.65rem;
        background: var(--tich-surface);
    }
    .tich-catalog-actions { display: flex; flex-wrap: wrap; gap: 0.35rem 0.65rem; align-items: center; }
    .tich-catalog-actions__group { display: flex; flex-wrap: wrap; gap: 0.35rem; align-items: center; }
    .tich-catalog-actions__label { font-size: 0.75rem; color: var(--tich-neutral-muted); }
    .tich-catalog-empty {
        padding: 3rem 1.5rem;
        text-align: center;
        color: var(--tich-text);
    }
    .tich-catalog-hint {
        margin: 0 1.5rem 1rem;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        border: 1px solid var(--tich-notice-info-border);
        background: var(--tich-notice-info-bg);
        color: var(--tich-text);
        font-size: 0.875rem;
    }
    .tich-catalog-hint--success {
        border-color: var(--tich-green);
        background: var(--tich-green-light);
        color: var(--tich-text);
    }

    [data-theme="dark"] .tich-catalog-status--active {
        background: rgba(16, 185, 129, 0.2);
        color: #6ee7b7;
    }
    [data-theme="dark"] .tich-catalog-status--draft {
        background: var(--tich-neutral);
        color: var(--tich-neutral-muted);
        border-color: var(--tich-border);
    }
    [data-theme="dark"] .tich-catalog-status--pending_registry {
        background: rgba(251, 191, 36, 0.2);
        color: #fcd34d;
    }
    [data-theme="dark"] .tich-catalog-status--archived {
        background: rgba(239, 68, 68, 0.2);
        color: #fca5a5;
    }
    [data-theme="dark"] .tich-catalog-hint--success {
        border-color: var(--tich-green);
        background: var(--tich-green-light);
        color: var(--tich-grey);
    }
    [data-theme="dark"] .tich-catalog-unit-code {
        color: var(--tich-blue);
    }
    [data-theme="dark"] .tich-catalog-assign summary {
        color: var(--tich-blue);
    }
    [data-theme="dark"] .tich-catalog-table thead th {
        color: var(--tich-grey);
        border-color: var(--tich-border);
    }
</style>

<article class="tich-card tich-catalog-panel tich-mt-8">
    <div class="tich-catalog-panel__head">
        <div>
            <h2 class="tich-h3">Unit catalog</h2>
            <p class="tich-text" style="margin:0.35rem 0 0; max-width:42rem;">
                Manage department units, map them to the selected intake, and assign lecturers for each teaching period.
            </p>
            <div class="tich-catalog-stats">
                <span class="tich-catalog-stat"><strong>{{ $catalogUnits->count() }}</strong> catalog units</span>
                <span class="tich-catalog-stat"><strong>{{ $activeCount }}</strong> active</span>
                @if ($selectedIntake)
                    <span class="tich-catalog-stat"><strong>{{ $mappedCount }}</strong> mapped to {{ $selectedIntake->intakeLabel() }}</span>
                    <span class="tich-catalog-stat"><strong>{{ $allocatedCount }}</strong> with lecturer</span>
                @endif
            </div>
        </div>
        @can('academics.write')
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="unit-create">Add unit</button>
        @endcan
    </div>

    @unless ($selectedIntake)
        <p class="tich-catalog-hint">Select a working intake above to see semester/year scheduling and assign lecturers.</p>
    @else
        <p class="tich-catalog-hint tich-catalog-hint--success">
            Working intake: <strong>{{ $selectedIntake->intakeLabel() }}</strong>
            @if ($intakeYearLabel)
                · Academic year <strong>{{ $intakeYearLabel }}</strong>
            @endif
        </p>
    @endunless

    <div class="tich-catalog-table-wrap">
        <table class="tich-admin-table tich-catalog-table">
            <thead>
                <tr>
                    <th>Unit</th>
                    <th>Hours</th>
                    <th>Status</th>
                    @if ($selectedIntake)
                        <th>Semester &amp; year</th>
                        <th>Lecturer</th>
                    @endif
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($catalogUnits as $unit)
                    @php
                        $unitAllocations = ($catalogAllocations[$unit->id] ?? collect());
                        $schedules = collect($unitScheduleMap[$unit->id] ?? []);
                        $statusClass = 'tich-catalog-status--'.str_replace('-', '_', $unit->status);
                        if ($schedules->isEmpty() && $unit->semester) {
                            $schedules = collect([[
                                'period' => $program->periodLabel((int) $unit->semester),
                                'year' => $intakeYearLabel,
                                'source' => 'catalog',
                            ]]);
                        }
                    @endphp
                    <tr>
                        <td style="min-width:14rem;">
                            <div class="tich-catalog-unit-code">{{ $unit->unit_code }}</div>
                            <div class="tich-catalog-unit-name">{{ $unit->unit_name }}</div>
                        </td>
                        <td class="tich-catalog-hours">
                            <div>{{ $unit->contact_hours ?? '—' }} contact</div>
                            <div class="tich-caption">{{ $unit->total_learning_hours ?? '—' }} learning</div>
                        </td>
                        <td>
                            <span class="tich-catalog-status {{ $statusClass }}">
                                {{ $statusLabels[$unit->status] ?? ucfirst($unit->status) }}
                            </span>
                        </td>
                        @if ($selectedIntake)
                            <td style="min-width:11rem;">
                                @if ($schedules->isNotEmpty())
                                    <ul class="tich-catalog-schedule-list">
                                        @foreach ($schedules as $schedule)
                                            <li>
                                                <span class="tich-catalog-schedule-chip">
                                                    <span class="tich-catalog-schedule-chip__period">{{ $schedule['period'] }}</span>
                                                    @if ($schedule['year'])
                                                        <span class="tich-catalog-schedule-chip__year">{{ $schedule['year'] }}</span>
                                                    @endif
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="tich-catalog-schedule-chip tich-catalog-schedule-chip--muted">
                                        <span class="tich-catalog-schedule-chip__period">Not scheduled</span>
                                        <span class="tich-catalog-schedule-chip__year">Map unit to intake first</span>
                                    </span>
                                @endif
                            </td>
                            <td style="min-width:15rem;">
                                @if ($unitAllocations->isNotEmpty())
                                    <ul class="tich-catalog-alloc-list">
                                        @foreach ($unitAllocations as $allocation)
                                            <li class="tich-catalog-alloc-card">
                                                <div class="tich-catalog-alloc-card__name">{{ $allocation->staff?->fullName() }}</div>
                                                <div class="tich-catalog-alloc-card__meta">
                                                    {{ $allocation->semester?->displayLabel() }}
                                                    @if ($allocation->semester?->academicYear?->year_label)
                                                        · {{ $allocation->semester->academicYear->year_label }}
                                                    @endif
                                                    · {{ $allocation->campus?->campus_name }}
                                                    @if ($allocation->is_coordinator)
                                                        · <strong>Coordinator</strong>
                                                    @endif
                                                </div>
                                                @can('academics.write')
                                                    <form method="POST" action="{{ route('departments.academics.programs.allocations.destroy', array_merge($catalogHub, ['program' => $program->id, 'allocation' => $allocation->id])) }}" class="tich-mt-2" onsubmit="return confirm('Remove this lecturer allocation?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="intake" value="{{ $selectedIntake->id }}">
                                                        @if ($learningDepartment)
                                                            <input type="hidden" name="learning_department" value="{{ $learningDepartment->id }}">
                                                        @endif
                                                        <button type="submit" class="tich-link" style="font-size:0.8125rem;">Remove</button>
                                                    </form>
                                                @endcan
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="tich-catalog-alloc-empty">No lecturer assigned</p>
                                @endif

                                @can('academics.write')
                                    <details class="tich-catalog-assign">
                                        <summary>+ Assign lecturer</summary>
                                        <form method="POST" action="{{ route('departments.academics.programs.allocations.store', array_merge($catalogHub, ['program' => $program->id])) }}" class="tich-catalog-assign__body">
                                            @csrf
                                            <input type="hidden" name="intake" value="{{ $selectedIntake->id }}">
                                            <input type="hidden" name="unit_id" value="{{ $unit->id }}">
                                            @if ($learningDepartment)
                                                <input type="hidden" name="learning_department" value="{{ $learningDepartment->id }}">
                                            @endif
                                            <div class="tich-form-group" style="margin:0;">
                                                <label class="tich-label">Teaching period</label>
                                                <select name="teaching_period" class="tich-input" required>
                                                    @foreach ($periods as $period)
                                                        <option value="{{ $period['semester'] }}">{{ $period['label'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="tich-form-group" style="margin:0;">
                                                <label class="tich-label">Lecturer</label>
                                                <select name="staff_id" class="tich-input" required>
                                                    @forelse ($allocationStaffList as $member)
                                                        <option value="{{ $member->id }}">{{ $member->fullName() }}</option>
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
                                                <input type="number" name="contact_hours_assigned" class="tich-input" value="{{ $unit->contact_hours ?: 4 }}" min="0">
                                            </div>
                                            <label class="tich-label"><input type="checkbox" name="is_coordinator" value="1"> Unit coordinator</label>
                                            <button type="submit" class="tich-btn tich-btn-primary" @disabled($allocationStaffList->isEmpty())>Save allocation</button>
                                        </form>
                                    </details>
                                @endcan
                            </td>
                        @endif
                        <td>
                            <div class="tich-catalog-actions">
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
                                        <div class="tich-catalog-actions__group">
                                            <span class="tich-catalog-actions__label">Map to intake:</span>
                                            @foreach ($periods as $period)
                                                <form method="POST" action="{{ route('departments.academics.programs.intakes.add-unit', array_merge($catalogHub, ['program' => $program->id, 'version' => $selectedIntake->id, 'semester' => $period['semester'] ?? 1])) }}" style="display:inline;">
                                                    @csrf
                                                    <input type="hidden" name="unit_id" value="{{ $unit->id }}">
                                                    @if (! empty($period['block_id']))
                                                        <input type="hidden" name="block_id" value="{{ $period['block_id'] }}">
                                                    @endif
                                                    <button type="submit" class="tich-link" title="{{ $period['label'] }}">{{ $program->usesBlocks() ? $period['label'] : 'S'.$period['semester'] }}</button>
                                                </form>
                                            @endforeach
                                        </div>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $selectedIntake ? 6 : 4 }}" class="tich-catalog-empty">
                            <p class="tich-text">No units yet.</p>
                            @can('academics.write')
                                <button type="button" class="tich-btn tich-btn-primary tich-mt-4" data-open-modal="unit-create">Add first unit</button>
                            @endcan
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
