@extends('layouts.academics')

@section('academics-content')
    @php
        $hub = ['department' => $department->id];
        if (! empty($learningDepartment)) {
            $hub['learning_department'] = $learningDepartment->id;
        }
        $mappingIndex = 0;
    @endphp

    @include('academics.partials.learning-department-context')

    <a href="{{ route('departments.academics.programs.index', $hub) }}" class="tich-link">&larr; All programmes</a>

    <div class="tich-mt-4">
        <h1 class="tich-h1" style="font-size: 2rem;">{{ $program->program_name }}</h1>
        <p class="tich-text">{{ $program->program_code }} · {{ $program->department?->dept_name }}</p>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-8" style="gap:1.5rem; align-items:start;">
        <article class="tich-card">
            <h2 class="tich-h3">Programme structure</h2>
            <p class="tich-text tich-mt-2">Set course length and how many semesters or trimesters run in each academic year.</p>

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
            <h2 class="tich-h3">Published version</h2>
            @if ($publishedVersion)
                <p class="tich-text tich-mt-4">
                    <strong>{{ $publishedVersion->version_label }}</strong>
                    · {{ $publishedVersion->items->count() }} units
                    · {{ $publishedVersion->published_at?->format('d M Y') }}
                </p>
            @else
                <p class="tich-caption tich-mt-4">No published curriculum version yet.</p>
            @endif

            @can('academics.write')
                <form method="POST" action="{{ route('departments.academics.programs.versions.create', array_merge($hub, ['program' => $program->id])) }}" class="tich-mt-6">
                    @csrf
                    <div class="tich-form-group">
                        <label class="tich-label">Version label</label>
                        <input type="text" name="version_label" class="tich-input" placeholder="e.g. 2026 intake">
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Academic year</label>
                        <select name="academic_year_id" class="tich-input">
                            <option value="">—</option>
                            @foreach ($academicYears as $year)
                                <option value="{{ $year->id }}">{{ $year->year_label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-secondary">Create draft version</button>
                </form>
            @endcan
        </article>
    </div>

    <article class="tich-card tich-mt-8">
        <h2 class="tich-h3">Units by {{ $program->usesBlocks() ? 'clinical block' : 'semester / term' }}</h2>
        <p class="tich-text tich-mb-4">Assign active catalog units to each {{ $program->usesBlocks() ? 'block' : 'teaching period' }}. Each unit can only belong to one period per programme.</p>

        @if ($periods->isEmpty())
            <p class="tich-text">Save the programme structure above to generate teaching periods{{ $program->usesBlocks() ? ' (set block count for nursing programmes)' : '' }}.</p>
        @elseif ($availableUnits->isEmpty())
            <article class="tich-card" style="padding:1.5rem;">
                <h3 class="tich-h3">No active units in the catalog</h3>
                <p class="tich-text tich-mt-2">
                    Add units in the unit catalog for {{ $program->department?->dept_name ?? 'this department' }}, then submit and approve them before mapping to this programme.
                </p>
                @php
                    $unitCatalogParams = ['department' => $department->id];
                    if (! empty($learningDepartment)) {
                        $unitCatalogParams['learning_department'] = $learningDepartment->id;
                    }
                @endphp
                @if (($catalogUnitCounts['draft'] ?? 0) > 0 || ($catalogUnitCounts['pending_registry'] ?? 0) > 0)
                    <p class="tich-caption tich-mt-4">
                        @if (($catalogUnitCounts['draft'] ?? 0) > 0)
                            {{ $catalogUnitCounts['draft'] }} draft unit(s)
                        @endif
                        @if (($catalogUnitCounts['pending_registry'] ?? 0) > 0)
                            · {{ $catalogUnitCounts['pending_registry'] }} pending registry approval
                        @endif
                        — only <strong>active</strong> units can be mapped.
                    </p>
                @endif
                @can('academics.write')
                    <a href="{{ route('departments.academics.units.index', $unitCatalogParams) }}" class="tich-btn tich-btn-primary tich-mt-4">Open unit catalog</a>
                @endcan
            </article>
        @else
            <form method="POST" action="{{ route('departments.academics.programs.sync-units', array_merge($hub, ['program' => $program->id])) }}">
                @csrf

                @foreach ($periods as $period)
                    @php
                        $periodMappings = $program->usesBlocks()
                            ? $mappingsByBlock->get($period['block_id'], collect())
                            : $mappingsBySemester->get($period['semester'], collect());
                        $assignedUnitIds = $periodMappings->pluck('unit_id')->all();
                    @endphp

                    <fieldset class="tich-mt-6" style="border:1px solid var(--tich-border); border-radius:0.5rem; padding:1rem;">
                        <legend class="tich-h3" style="padding:0 0.5rem;">{{ $period['label'] }}</legend>
                        <p class="tich-caption tich-mb-4">{{ $periodMappings->count() }} unit(s) assigned</p>

                        <div style="overflow-x:auto;">
                            <table class="tich-admin-table">
                                <thead>
                                    <tr>
                                        <th>Include</th>
                                        <th>Unit</th>
                                        <th>Priority</th>
                                        <th>Contact hrs</th>
                                        <th>Total learning hrs</th>
                                        <th>Core</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($availableUnits as $unit)
                                        @php
                                            $map = $mappings->firstWhere('unit_id', $unit->id);
                                            $included = $map && (
                                                ($program->usesBlocks() && (int) $map->block_id === (int) $period['block_id'])
                                                || (! $program->usesBlocks() && (int) $map->semester === (int) $period['semester'])
                                            );
                                        @endphp
                                        <tr>
                                            <td>
                                                <input type="hidden" name="mappings[{{ $mappingIndex }}][unit_id]" value="{{ $unit->id }}">
                                                <input type="hidden" name="mappings[{{ $mappingIndex }}][semester]" value="{{ $period['semester'] ?? ($map?->semester ?? 1) }}">
                                                <input type="hidden" name="mappings[{{ $mappingIndex }}][block_id]" value="{{ $period['block_id'] }}">
                                                <input type="checkbox" name="mappings[{{ $mappingIndex }}][include]" value="1" @checked($included)>
                                            </td>
                                            <td>{{ $unit->unit_code }} — {{ $unit->unit_name }}</td>
                                            <td><input type="number" name="mappings[{{ $mappingIndex }}][priority]" class="tich-input" style="width:5rem;" min="0" value="{{ $included ? ($map?->priority ?? $unit->display_priority ?? 1) : ($unit->display_priority ?? 1) }}"></td>
                                            <td><input type="number" name="mappings[{{ $mappingIndex }}][contact_hours]" class="tich-input" style="width:5rem;" min="0" value="{{ $included ? ($map?->contact_hours ?? $unit->contact_hours ?? 0) : ($unit->contact_hours ?? 0) }}"></td>
                                            <td><input type="number" name="mappings[{{ $mappingIndex }}][total_learning_hours]" class="tich-input" style="width:5rem;" min="0" value="{{ $included ? ($map?->total_learning_hours ?? $unit->total_learning_hours ?? 0) : ($unit->total_learning_hours ?? 0) }}"></td>
                                            <td><input type="checkbox" name="mappings[{{ $mappingIndex }}][is_compulsory]" value="1" @checked($included ? ($map?->is_compulsory ?? $unit->is_core ?? true) : ($unit->is_core ?? false))></td>
                                        </tr>
                                        @php($mappingIndex++)
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </fieldset>
                @endforeach

                @can('academics.write')
                    <button type="submit" class="tich-btn tich-btn-primary tich-mt-6">Save unit mapping</button>
                @endcan
            </form>
        @endif
    </article>

    @if ($versions->isNotEmpty())
        <article class="tich-card tich-mt-8">
            <h2 class="tich-h3">Version workflow</h2>
            <table class="tich-admin-table tich-mt-4">
                <thead>
                    <tr>
                        <th>Version</th>
                        <th>Format</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($versions as $version)
                        <tr>
                            <td>{{ $version->version_label }} (v{{ $version->version_number }})</td>
                            <td>{{ $formats[$version->curriculum_format] ?? $version->curriculum_format }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $version->status)) }}</td>
                            <td style="white-space:nowrap;">
                                @if ($version->status === 'draft')
                                    @can('academics.write')
                                        <form method="POST" action="{{ route('departments.academics.versions.submit', array_merge($hub, ['version' => $version->id])) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="tich-link">Submit</button>
                                        </form>
                                    @endcan
                                @endif
                                @if ($version->status === 'pending_registry' && $canApproveRegistry)
                                    <form method="POST" action="{{ route('departments.academics.versions.approve-registry', array_merge($hub, ['version' => $version->id])) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="tich-link">Registrar approve</button>
                                    </form>
                                @endif
                                @if ($version->status === 'pending_ceo' && $canApproveCeo)
                                    <form method="POST" action="{{ route('departments.academics.versions.approve-ceo', array_merge($hub, ['version' => $version->id])) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="tich-link">CEO publish</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </article>
    @endif
@endsection
