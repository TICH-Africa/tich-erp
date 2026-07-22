@extends('layouts.academics')

@section('academics-content')
    <a href="{{ route('academics.programs.index') }}" class="tich-link">&larr; All programmes</a>

    <div class="tich-mt-4">
        <h1 class="tich-h1" style="font-size: 2rem;">{{ $program->program_name }}</h1>
        <p class="tich-text">{{ $program->program_code }} · {{ $program->department?->dept_name }}</p>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-8" style="gap:1.5rem; align-items:start;">
        <article class="tich-card">
            <h2 class="tich-h3">Curriculum format (Phase B)</h2>
            <form method="POST" action="{{ route('academics.programs.update-format', $program) }}" class="tich-mt-4">
                @csrf
                @method('PUT')
                <div class="tich-form-group">
                    <label class="tich-label">Format</label>
                    <select name="curriculum_format" class="tich-input" required>
                        @foreach ($formats as $key => $label)
                            <option value="{{ $key }}" @selected(old('curriculum_format', $program->curriculum_format ?? 'trimester') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-grid tich-grid--2 tich-mt-4" style="gap:1rem;">
                    <div class="tich-form-group">
                        <label class="tich-label">Semester count</label>
                        <input type="number" name="semester_count" class="tich-input" min="0" value="{{ old('semester_count', $program->semester_count ?? 3) }}">
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Block count</label>
                        <input type="number" name="block_count" class="tich-input" min="0" value="{{ old('block_count', $program->block_count ?? 0) }}">
                    </div>
                </div>
                @can('academics.write')
                    <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Save format</button>
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
                <form method="POST" action="{{ route('academics.programs.versions.create', $program) }}" class="tich-mt-6">
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
        <h2 class="tich-h3">Unit mapping (Phase A)</h2>
        <p class="tich-text tich-mb-4">Map active catalog units to this programme. Set semester/block, priority, and learning hours per mapping.</p>

        <form method="POST" action="{{ route('academics.programs.sync-units', $program) }}">
            @csrf
            <div style="overflow-x:auto;">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Include</th>
                            <th>Unit</th>
                            <th>Semester</th>
                            @if ($program->usesBlocks())
                                <th>Block</th>
                            @endif
                            <th>Priority</th>
                            <th>Contact hrs</th>
                            <th>Total learning hrs</th>
                            <th>Core</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $existing = $mappings->keyBy('unit_id'); @endphp
                        @foreach ($availableUnits as $index => $unit)
                            @php $map = $existing->get($unit->id); @endphp
                            <tr>
                                <td>
                                    <input type="hidden" name="mappings[{{ $index }}][unit_id]" value="{{ $unit->id }}">
                                    <input type="checkbox" name="mappings[{{ $index }}][include]" value="1" @checked($map !== null)>
                                </td>
                                <td>{{ $unit->unit_code }} — {{ $unit->unit_name }}</td>
                                <td><input type="number" name="mappings[{{ $index }}][semester]" class="tich-input" style="width:5rem;" min="1" value="{{ $map?->semester ?? $unit->semester ?? 1 }}"></td>
                                @if ($program->usesBlocks())
                                    <td>
                                        <select name="mappings[{{ $index }}][block_id]" class="tich-input">
                                            <option value="">—</option>
                                            @foreach ($blocks as $block)
                                                <option value="{{ $block->id }}" @selected($map?->block_id == $block->id)>{{ $block->block_label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                @endif
                                <td><input type="number" name="mappings[{{ $index }}][priority]" class="tich-input" style="width:5rem;" min="0" value="{{ $map?->priority ?? $unit->display_priority ?? ($index + 1) }}"></td>
                                <td><input type="number" name="mappings[{{ $index }}][contact_hours]" class="tich-input" style="width:5rem;" min="0" value="{{ $map?->contact_hours ?? $unit->contact_hours ?? 0 }}"></td>
                                <td><input type="number" name="mappings[{{ $index }}][total_learning_hours]" class="tich-input" style="width:5rem;" min="0" value="{{ $map?->total_learning_hours ?? $unit->total_learning_hours ?? 0 }}"></td>
                                <td><input type="checkbox" name="mappings[{{ $index }}][is_compulsory]" value="1" @checked($map?->is_compulsory ?? $unit->is_core ?? true)></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @can('academics.write')
                <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Save unit mapping</button>
            @endcan
        </form>
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
                                        <form method="POST" action="{{ route('academics.versions.submit', $version) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="tich-link">Submit</button>
                                        </form>
                                    @endcan
                                @endif
                                @if ($version->status === 'pending_registry' && $canApproveRegistry)
                                    <form method="POST" action="{{ route('academics.versions.approve-registry', $version) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="tich-link">Registrar approve</button>
                                    </form>
                                @endif
                                @if ($version->status === 'pending_ceo' && $canApproveCeo)
                                    <form method="POST" action="{{ route('academics.versions.approve-ceo', $version) }}" style="display:inline;">
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
