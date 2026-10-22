@php
    $desk = $hodAllocationDesk ?? [];
    $program = $desk['program'] ?? null;
    $intake = $desk['intake'] ?? null;
    $mappedByPeriod = $desk['mappedByPeriod'] ?? collect();
    $allocationsByUnitSemester = $desk['allocationsByUnitSemester'] ?? collect();
    $statusLabels = $desk['statusLabels'] ?? [];
@endphp

<x-page-toolbar title="Unit allocations" :meta="($staff->department?->dept_name ?? 'Department').' · Assign lecturers to your programmes'" />

<div class="tich-mt-6">
    @if (! $program)
        <p class="tich-text">No programmes are registered under your department yet.</p>
    @else
        <form method="GET" action="{{ route('staff.dashboard') }}" class="tich-flex-wrap" style="gap:0.75rem; align-items:end;">
            <input type="hidden" name="section" value="hod-unit-allocations">
            <div class="tich-form-group" style="margin:0;">
                <label class="tich-label" for="program">Programme</label>
                <select id="program" name="program" class="tich-select">
                    @foreach ($desk['programs'] as $option)
                        <option value="{{ $option->id }}" @selected((int) $option->id === (int) $program->id)>
                            {{ $option->program_code }} - {{ $option->program_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group" style="margin:0;">
                <label class="tich-label" for="intake">Intake</label>
                <select id="intake" name="intake" class="tich-select">
                    @forelse ($desk['intakes'] as $option)
                        <option value="{{ $option->id }}" @selected($intake && (int) $option->id === (int) $intake->id)>
                            {{ $option->version_label }}
                        </option>
                    @empty
                        <option value="">No intakes yet</option>
                    @endforelse
                </select>
            </div>
            <button type="submit" class="tich-btn tich-btn-secondary">Load programme</button>
        </form>

        @if ($desk['pending']->isNotEmpty())
            <p class="tich-mt-4">
                <span class="tich-badge" style="background:#fef3c7; color:#92400e;">
                    {{ $desk['pending']->count() }} awaiting Academics
                </span>
            </p>
        @endif

        @if (! $intake)
            <p class="tich-text tich-mt-4">This programme has no intake yet. Academics creates intakes on the programme curriculum page.</p>
        @else
            <article class="tich-card tich-mt-4">
                <p class="tich-h4">Add a unit to {{ $program->program_code }}</p>
                <p class="tich-caption">
                    Only units Academics has approved for your department are listed. A unit added here appears on
                    {{ $intake->version_label }} and you can then assign a lecturer to it.
                </p>

                @if ($desk['intakeLocked'])
                    <p class="tich-caption tich-mt-2">
                        {{ $intake->version_label }} is {{ $intake->status }}. Academics must reopen it before units can be added,
                        but you can still assign lecturers to the units already on it.
                    </p>
                @elseif ($desk['availableUnits']->isEmpty())
                    <p class="tich-caption tich-mt-2">Every approved unit in your department is already on this intake.</p>
                @else
                    <form method="POST" action="{{ route('staff.unit-allocations.map-unit') }}" class="tich-flex-wrap tich-mt-3" style="gap:0.75rem; align-items:end;">
                        @csrf
                        <input type="hidden" name="program" value="{{ $program->id }}">
                        <input type="hidden" name="intake" value="{{ $intake->id }}">
                        <div class="tich-form-group" style="margin:0;">
                            <label class="tich-label" for="unit_id">Unit</label>
                            <select id="unit_id" name="unit_id" class="tich-select" required>
                                @foreach ($desk['availableUnits'] as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->unit_code }} - {{ $unit->unit_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="tich-form-group" style="margin:0;">
                            <label class="tich-label" for="period">Teaching period</label>
                            <select id="period" name="period" class="tich-select">
                                @foreach ($desk['periods'] as $period)
                                    <option value="{{ $period['period'] }}">{{ $period['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="tich-btn tich-btn-primary">Add unit</button>
                    </form>
                @endif
            </article>

            <div class="tich-table-panel tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Period</th>
                            <th>Lecturer</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($desk['mapped'] as $mapping)
                            @php
                                $periodSemesters = $desk['semesterIdByPeriod'][(int) $mapping->semester] ?? null;
                                $rows = $periodSemesters ? ($allocationsByUnitSemester->get($mapping->unit_id.'-'.$periodSemesters, collect())) : collect();
                                $live = $rows->firstWhere('is_active', true) ?? $rows->first(fn ($row) => $row->status === \App\Models\UnitAllocation::STATUS_APPROVED);
                                $pending = $rows->first(fn ($row) => $row->status === \App\Models\UnitAllocation::STATUS_PENDING);
                                $rejected = $rows->first(fn ($row) => $row->status === \App\Models\UnitAllocation::STATUS_REJECTED);
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $mapping->unit?->unit_code }}</strong>
                                    <div class="tich-caption">{{ $mapping->unit?->unit_name }}</div>
                                </td>
                                <td>{{ $program->periodLabel((int) $mapping->semester) }}</td>
                                <td>
                                    @if ($live?->staff)
                                        {{ $live->staff->fullName() }}
                                    @elseif ($pending?->staff)
                                        {{ $pending->staff->fullName() }}
                                    @else
                                        <span class="tich-caption">Not assigned</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($live)
                                        <span class="tich-badge">Teaching</span>
                                    @elseif ($pending)
                                        <span class="tich-badge" style="background:#fef3c7; color:#92400e;">Awaiting Academics</span>
                                    @elseif ($rejected)
                                        <span class="tich-badge" style="background:#fee2e2; color:#991b1b;">Rejected</span>
                                        <div class="tich-caption">{{ $rejected->approval_comments }}</div>
                                    @else
                                        <span class="tich-caption">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($pending)
                                        <form method="POST" action="{{ route('staff.unit-allocations.withdraw', $pending->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="program" value="{{ $program->id }}">
                                            <input type="hidden" name="intake" value="{{ $intake->id }}">
                                            <button type="submit" class="tich-btn tich-btn-ghost">Withdraw request</button>
                                        </form>
                                    @elseif (! $live)
                                        <details>
                                            <summary class="tich-link">Assign lecturer</summary>
                                            <form method="POST" action="{{ route('staff.unit-allocations.store') }}" class="tich-mt-2" style="display:flex; flex-direction:column; gap:0.5rem; min-width:16rem;">
                                                @csrf
                                                <input type="hidden" name="program" value="{{ $program->id }}">
                                                <input type="hidden" name="intake" value="{{ $intake->id }}">
                                                <input type="hidden" name="unit_id" value="{{ $mapping->unit_id }}">
                                                <input type="hidden" name="period" value="{{ $mapping->semester }}">
                                                <label class="tich-label" for="lecturer-{{ $mapping->id }}">Lecturer</label>
                                                <select id="lecturer-{{ $mapping->id }}" name="staff_id" class="tich-select" required>
                                                    @foreach ($desk['lecturers'] as $lecturer)
                                                        <option value="{{ $lecturer->id }}">{{ $lecturer->fullName() }} ({{ $lecturer->employee_number }})</option>
                                                    @endforeach
                                                </select>
                                                <label class="tich-label" for="campus-{{ $mapping->id }}">Campus</label>
                                                <select id="campus-{{ $mapping->id }}" name="campus_id" class="tich-select" required>
                                                    @foreach ($desk['campuses'] as $campus)
                                                        <option value="{{ $campus->id }}">{{ $campus->campus_name }}</option>
                                                    @endforeach
                                                </select>
                                                <label class="tich-label" for="hours-{{ $mapping->id }}">Contact hours</label>
                                                <input id="hours-{{ $mapping->id }}" class="tich-input" type="number" min="0" name="contact_hours_assigned" value="{{ $mapping->contact_hours ?: $mapping->unit?->contact_hours }}">
                                                <p class="tich-caption">Fetched from {{ $mapping->unit?->unit_code }}'s contact hours on this intake - edit them here if this lecturer carries a different load.</p>
                                                <label><input type="checkbox" name="is_coordinator" value="1"> Unit coordinator</label>
                                                <button type="submit" class="tich-btn tich-btn-primary">Send for approval</button>
                                            </form>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">No units are on this intake yet. Add one above.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        <article class="tich-card tich-mt-4">
            <p class="tich-h4">Your department's allocations</p>
            @if ($desk['units']->isEmpty())
                <p class="tich-caption">Nothing allocated yet.</p>
            @else
                <div class="tich-table-wrap tich-mt-2">
                    <table class="tich-admin-table">
                        <thead>
                            <tr>
                                <th>Unit</th>
                                <th>Lecturer</th>
                                <th>Programme</th>
                                <th>Semester</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($desk['units'] as $allocation)
                                <tr>
                                    <td>{{ $allocation->unit?->unit_code }} - {{ $allocation->unit?->unit_name }}</td>
                                    <td>{{ $allocation->staff?->fullName() }}</td>
                                    <td>{{ $allocation->program?->program_code ?? '-' }}</td>
                                    <td>{{ $allocation->semester?->semester_label ?? '-' }}</td>
                                    <td>
                                        {{ $statusLabels[$allocation->status] ?? $allocation->status }}
                                        @if ($allocation->awaitingApproval() && $allocation->requestedBy)
                                            <div class="tich-caption">requested by {{ $allocation->requestedBy->name }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>
    @endif
</div>
