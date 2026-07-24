@extends('layouts.academics')

@section('academics-content')
    @php
        $hub = ['department' => $department->id];
        if (! empty($learningDepartment)) {
            $hub['learning_department'] = $learningDepartment->id;
        }
        $intakeQuery = $hub;
        if ($selectedIntake) {
            $intakeQuery['intake'] = $selectedIntake->id;
        }
        $mappingIndex = 0;
        $assignedUnitIds = $mappings->pluck('unit_id')->all();
    @endphp

    @include('academics.partials.learning-department-context')

    <a href="{{ route('departments.academics.programs.index', $hub) }}" class="tich-link">&larr; All programmes</a>

    <div class="tich-mt-4">
        <h1 class="tich-h1" style="font-size: 2rem;">{{ $program->program_name }}</h1>
        <p class="tich-text">{{ $program->program_code }} · {{ $program->department?->dept_name }}</p>
    </div>

    @if (session('status'))
        <p class="tich-text tich-mt-4" style="color: var(--tich-success, #15803d);">{{ session('status') }}</p>
    @endif
    @error('intake')
        <p class="tich-text tich-mt-4" style="color: var(--tich-danger, #b91c1c);">{{ $message }}</p>
    @enderror

    <div class="tich-grid tich-grid--2 tich-mt-8" style="gap:1.5rem; align-items:start;">
        <article class="tich-card">
            <h2 class="tich-h3">Programme structure</h2>
            <p class="tich-text tich-mt-2">Set course length and how many semesters or trimesters run in each academic year. This defines the teaching periods copied for every intake.</p>

            <form method="POST" action="{{ route('departments.academics.programs.update-format', array_merge($hub, ['program' => $program->id])) }}" class="tich-mt-4">
                @csrf
                @method('PUT')
                <div class="tich-form-group">
                    <label class="tich-label">Curriculum format</label>
                    <select name="curriculum_format" class="tich-input" required>
                        @foreach ($formats as $key => $label)
                            <option value="{{ $key }}" @selected(old('curriculum_format', $program->curriculum_format ?? 'trimester') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-grid tich-grid--3 tich-mt-4" style="gap:1rem;">
                    <div class="tich-form-group">
                        <label class="tich-label">Course length (months)</label>
                        <input type="number" name="duration_months" class="tich-input" min="1" max="120" value="{{ old('duration_months', $program->duration_months ?? 12) }}" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Semesters / terms per academic year</label>
                        <input type="number" name="semester_count" class="tich-input" min="1" max="6" value="{{ old('semester_count', $program->semester_count ?: $program->termsPerYear()) }}" required>
                        <p class="tich-caption tich-mt-2">e.g. 2 for semester, 3 for trimester</p>
                    </div>
                    @if ($program->usesBlocks() || in_array(old('curriculum_format', $program->curriculum_format), ['block'], true))
                        <div class="tich-form-group">
                            <label class="tich-label">Nursing blocks</label>
                            <input type="number" name="block_count" class="tich-input" min="1" max="10" value="{{ old('block_count', $program->block_count ?: $blocks->count() ?: 4) }}">
                        </div>
                    @else
                        <div class="tich-form-group">
                            <label class="tich-label">Total teaching periods</label>
                            <input type="text" class="tich-input" value="{{ $totalTeachingPeriods }}" disabled>
                            <p class="tich-caption tich-mt-2">{{ $programYears }} year(s) × {{ $termsPerYear }} term(s)/year</p>
                        </div>
                    @endif
                </div>
                @can('academics.write')
                    <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Save structure</button>
                @endcan
            </form>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Published intake</h2>
            @if ($publishedVersion)
                <p class="tich-text tich-mt-4">
                    <strong>{{ $publishedVersion->intakeLabel() }}</strong>
                    · {{ $publishedVersion->items->count() }} units
                    · {{ $publishedVersion->published_at?->format('d M Y') }}
                </p>
            @else
                <p class="tich-caption tich-mt-4">No published intake curriculum yet.</p>
            @endif

            <p class="tich-caption tich-mt-4">Each intake gets the same {{ $totalTeachingPeriods }} {{ $program->usesBlocks() ? 'blocks' : 'semesters' }} because cohorts overlap — January and May intakes both follow the full programme timeline.</p>
        </article>
    </div>

    <article class="tich-card tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h3">Intakes</h2>
            <p class="tich-text">Create an intake by year and month, then assign catalog units to each semester for that cohort.</p>
        </div>

        @if ($intakes->isNotEmpty())
            <div class="tich-mt-4" style="display:flex; flex-wrap:wrap; gap:0.5rem;">
                @foreach ($intakes as $intake)
                    <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'intake' => $intake->id])) }}"
                       class="tich-btn {{ ($selectedIntake?->id === $intake->id) ? 'tich-btn-primary' : 'tich-btn-secondary' }}"
                       style="font-size:0.875rem;">
                        {{ $intake->intakeLabel() }}
                        <span class="tich-caption">· {{ ucwords(str_replace('_', ' ', $intake->status)) }}</span>
                    </a>
                @endforeach
            </div>
        @endif

        @can('academics.write')
            <form method="POST" action="{{ route('departments.academics.programs.intakes.store', array_merge($hub, ['program' => $program->id])) }}" class="tich-mt-6" style="border-top:1px solid var(--tich-border); padding-top:1.5rem;">
                @csrf
                <h3 class="tich-h3">New intake</h3>
                <div class="tich-grid tich-grid--4 tich-mt-4" style="gap:1rem; align-items:end;">
                    <div class="tich-form-group">
                        <label class="tich-label">Intake year</label>
                        <input type="number" name="intake_year" class="tich-input" min="2000" max="2100" value="{{ old('intake_year', now()->year) }}" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Intake month</label>
                        <select name="intake_month" class="tich-input" required>
                            @foreach ($intakeMonths as $monthNum => $monthName)
                                <option value="{{ $monthNum }}" @selected((int) old('intake_month', now()->month) === $monthNum)>{{ $monthName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Copy units from</label>
                        <select name="copy_from_version_id" class="tich-input">
                            <option value="">Blank intake</option>
                            @foreach ($intakes as $intake)
                                <option value="{{ $intake->id }}">{{ $intake->intakeLabel() }}</option>
                            @endforeach
                            @if ($publishedVersion)
                                <option value="{{ $publishedVersion->id }}">{{ $publishedVersion->intakeLabel() }} (published)</option>
                            @endif
                        </select>
                    </div>
                    <div class="tich-form-group">
                        <button type="submit" class="tich-btn tich-btn-primary" @disabled($periods->isEmpty())>Create intake</button>
                    </div>
                </div>
                @error('intake_year')
                    <p class="tich-caption tich-mt-2" style="color:var(--tich-danger,#b91c1c);">{{ $message }}</p>
                @enderror
                @error('intake_month')
                    <p class="tich-caption tich-mt-2" style="color:var(--tich-danger,#b91c1c);">{{ $message }}</p>
                @enderror
            </form>
        @endcan
    </article>

    @include('academics.programs.partials.unit-catalog-embedded')

    @if ($periods->isEmpty())
        <article class="tich-card tich-mt-8">
            <p class="tich-text">Save the programme structure above to generate {{ $program->usesBlocks() ? 'clinical blocks' : 'semesters' }} before creating intakes.</p>
        </article>
    @elseif (! $selectedIntake)
        <article class="tich-card tich-mt-8">
            <p class="tich-text">Create an intake above to start mapping units to each {{ $program->usesBlocks() ? 'block' : 'semester' }}.</p>
        </article>
    @else
        <article class="tich-card tich-mt-8">
            <div class="tich-dept-panel__head">
                <h2 class="tich-h3">{{ $selectedIntake->intakeLabel() }} — units by {{ $program->usesBlocks() ? 'block' : 'semester' }}</h2>
                <p class="tich-text">
                    {{ $totalTeachingPeriods }} {{ $program->usesBlocks() ? 'blocks' : 'semesters' }} for this intake
                    @if ($selectedIntake->status !== 'draft')
                        · <span class="tich-caption">{{ ucwords(str_replace('_', ' ', $selectedIntake->status)) }} (read-only)</span>
                    @endif
                </p>
            </div>

            @if ($selectedIntake->status === 'draft')
                <form id="intake-sync-form" method="POST" action="{{ route('departments.academics.programs.intakes.sync-units', array_merge($hub, ['program' => $program->id, 'version' => $selectedIntake->id])) }}" style="display:none;">
                    @csrf
                </form>
            @endif

            @if ($availableUnits->isNotEmpty())
                @php
                    $inactiveCatalogUnits = $availableUnits->where('status', '!=', 'active')->count();
                @endphp
                @if ($inactiveCatalogUnits > 0)
                    <p class="tich-caption tich-mt-4">
                        {{ $inactiveCatalogUnits }} catalog unit(s) are still draft or pending — approve them above before submitting this intake.
                    </p>
                @endif
            @elseif ($catalogUnits->isEmpty())
                <p class="tich-text tich-mt-4">Create units in the catalog above, then assign them below or use <strong>Assign</strong> from the catalog table.</p>
            @endif

            @foreach ($periods as $period)
                    @php
                        $periodMappings = $program->usesBlocks()
                            ? $mappingsByBlock->get($period['block_id'], collect())
                            : $mappingsBySemester->get($period['semester'], collect());
                        $periodAssignedIds = $periodMappings->pluck('unit_id')->all();
                        $periodAvailableUnits = $availableUnits->reject(fn ($unit) => in_array($unit->id, $assignedUnitIds, true));
                    @endphp

                    <fieldset class="tich-mt-6" style="border:1px solid var(--tich-border); border-radius:0.5rem; padding:1rem;">
                        <legend class="tich-h3" style="padding:0 0.5rem;">
                            {{ $period['label'] }}
                            @if (! $program->usesBlocks())
                                <span class="tich-caption">· Semester {{ $period['semester'] }} of {{ $totalTeachingPeriods }}</span>
                            @endif
                        </legend>

                        @if ($periodMappings->isEmpty())
                            <p class="tich-caption tich-mb-4">No units assigned yet.</p>
                        @else
                            <div style="overflow-x:auto;">
                                <table class="tich-admin-table">
                                    <thead>
                                        <tr>
                                            @if ($selectedIntake->status === 'draft')
                                                <th>Keep</th>
                                            @endif
                                            <th>Unit</th>
                                            <th>Priority</th>
                                            <th>Contact hrs</th>
                                            <th>Total learning hrs</th>
                                            <th>Core</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($periodMappings as $map)
                                            @php($mappingIndex++)
                                            <tr>
                                                @if ($selectedIntake->status === 'draft')
                                                    <td>
                                                        <input form="intake-sync-form" type="hidden" name="mappings[{{ $mappingIndex }}][unit_id]" value="{{ $map->unit_id }}">
                                                        <input form="intake-sync-form" type="hidden" name="mappings[{{ $mappingIndex }}][semester]" value="{{ $period['semester'] ?? $map->semester }}">
                                                        <input form="intake-sync-form" type="hidden" name="mappings[{{ $mappingIndex }}][block_id]" value="{{ $period['block_id'] }}">
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
                                                    @if ($selectedIntake->status === 'draft')
                                                        <input form="intake-sync-form" type="number" name="mappings[{{ $mappingIndex }}][priority]" class="tich-input" style="width:5rem;" min="0" value="{{ $map->priority ?? 1 }}">
                                                    @else
                                                        {{ $map->priority ?? 1 }}
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($selectedIntake->status === 'draft')
                                                        <input form="intake-sync-form" type="number" name="mappings[{{ $mappingIndex }}][contact_hours]" class="tich-input" style="width:5rem;" min="0" value="{{ $map->contact_hours ?? 0 }}">
                                                    @else
                                                        {{ $map->contact_hours ?? 0 }}
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($selectedIntake->status === 'draft')
                                                        <input form="intake-sync-form" type="number" name="mappings[{{ $mappingIndex }}][total_learning_hours]" class="tich-input" style="width:5rem;" min="0" value="{{ $map->total_learning_hours ?? 0 }}">
                                                    @else
                                                        {{ $map->total_learning_hours ?? 0 }}
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($selectedIntake->status === 'draft')
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

                        @if ($selectedIntake->status === 'draft')
                            @can('academics.write')
                                @if ($periodAvailableUnits->isNotEmpty())
                                    <form method="POST" action="{{ route('departments.academics.programs.intakes.add-unit', array_merge($hub, ['program' => $program->id, 'version' => $selectedIntake->id, 'semester' => $period['semester'] ?? 1])) }}" class="tich-mt-4" style="display:flex; gap:0.75rem; align-items:end; flex-wrap:wrap;">
                                        @csrf
                                        @if ($period['block_id'])
                                            <input type="hidden" name="block_id" value="{{ $period['block_id'] }}">
                                        @endif
                                        <div class="tich-form-group" style="flex:1; min-width:16rem;">
                                            <label class="tich-label">Add unit to this {{ $program->usesBlocks() ? 'block' : 'semester' }}</label>
                                            <select name="unit_id" class="tich-input" required>
                                                <option value="">Select unit…</option>
                                                @foreach ($periodAvailableUnits as $unit)
                                                    <option value="{{ $unit->id }}">
                                                        {{ $unit->unit_code }} — {{ $unit->unit_name }}
                                                        @if ($unit->status !== 'active')
                                                            ({{ ucwords(str_replace('_', ' ', $unit->status)) }})
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="submit" class="tich-btn tich-btn-secondary">Add unit</button>
                                    </form>
                                @else
                                    <p class="tich-caption tich-mt-4">All available catalog units are already assigned in this intake.</p>
                                @endif
                            @endcan
                        @endif
                    </fieldset>
                @endforeach

            @if ($selectedIntake->status === 'draft')
                @can('academics.write')
                    <button type="submit" form="intake-sync-form" class="tich-btn tich-btn-primary tich-mt-6">Save intake mapping</button>
                @endcan
            @endif
        </article>

        @if ($selectedIntake)
            <article class="tich-card tich-mt-8">
                <h2 class="tich-h3">Intake workflow</h2>
                <table class="tich-admin-table tich-mt-4">
                    <thead>
                        <tr>
                            <th>Intake</th>
                            <th>Format</th>
                            <th>Units</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $selectedIntake->intakeLabel() }}</td>
                            <td>{{ $formats[$selectedIntake->curriculum_format] ?? $selectedIntake->curriculum_format }}</td>
                            <td>{{ $mappings->count() }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $selectedIntake->status)) }}</td>
                            <td style="white-space:nowrap;">
                                @if ($selectedIntake->status === 'draft')
                                    @can('academics.write')
                                        @if ($mappings->isEmpty())
                                            <span class="tich-caption">Add units to semesters above first</span>
                                        @elseif ($mappings->contains(fn ($map) => ($map->unit?->status ?? '') !== 'active'))
                                            <span class="tich-caption">Approve all mapped units before submitting</span>
                                        @else
                                            <form method="POST" action="{{ route('departments.academics.versions.submit', array_merge($hub, ['version' => $selectedIntake->id])) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="tich-link">Submit for approval</button>
                                            </form>
                                        @endif
                                    @endcan
                                @endif
                                @if ($selectedIntake->status === 'pending_registry' && $canApproveRegistry)
                                    <form method="POST" action="{{ route('departments.academics.versions.approve-registry', array_merge($hub, ['version' => $selectedIntake->id])) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="tich-link">Registrar approve</button>
                                    </form>
                                @endif
                                @if ($selectedIntake->status === 'pending_ceo' && $canApproveCeo)
                                    <form method="POST" action="{{ route('departments.academics.versions.approve-ceo', array_merge($hub, ['version' => $selectedIntake->id])) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="tich-link">CEO publish</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </article>
        @endif
    @endif
@endsection
