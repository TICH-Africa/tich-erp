<article class="tich-card">
    <div class="tich-dept-panel__head">
        <h2 class="tich-h3">Intakes</h2>
        <p class="tich-text">
            All intakes for {{ $program->program_name }}, including drafts and intakes not yet published.
            Select one as your working intake above, or open its semester units to begin mapping.
        </p>
    </div>

    @if ($periods->isEmpty())
        <p class="tich-text tich-mt-4">
            Save the <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'section' => 'structure'])) }}" class="tich-link">programme structure</a> first to generate semesters.
        </p>
    @else
        @if ($intakes->isEmpty())
            <p class="tich-text tich-mt-4">No intakes yet. Create the first intake below.</p>
        @else
            <div class="tich-card tich-mt-4" style="overflow-x:auto; padding:0;">
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
                        @foreach ($intakes as $intake)
                            @php
                                $intakeMappings = $intake->relationLoaded('items') ? $intake->items : collect();
                                $isSelected = $selectedIntake?->id === $intake->id;
                            @endphp
                            <tr @if ($isSelected) style="background:var(--tich-blue-light,#d6e8f5);" @endif>
                                <td>
                                    <strong>{{ $intake->intakeLabel() }}</strong>
                                    @if ($isSelected)
                                        <br><span class="tich-caption">Working intake</span>
                                    @endif
                                </td>
                                <td>{{ $formats[$intake->curriculum_format] ?? $intake->curriculum_format }}</td>
                                <td>{{ $intakeMappings->count() }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $intake->status)) }}</td>
                                <td class="tich-caption">
                                    @if ($intake->published_at)
                                        Published {{ $intake->published_at->format('d M Y') }}
                                    @elseif ($intake->submitted_at)
                                        Submitted {{ $intake->submitted_at->format('d M Y') }}
                                    @elseif ($intake->created_at)
                                        Created {{ $intake->created_at->format('d M Y') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td style="white-space:nowrap;">
                                    @unless ($isSelected)
                                        <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'intake' => $intake->id, 'section' => 'intakes'])) }}" class="tich-link">Set working</a>
                                        ·
                                    @endunless
                                    <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'intake' => $intake->id, 'section' => 'semesters'])) }}" class="tich-link">Semester units</a>
                                    ·
                                    <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'section' => 'workflow'])) }}" class="tich-link">Workflow</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
                                <option value="{{ $intake->id }}">{{ $intake->intakeLabel() }} ({{ ucwords(str_replace('_', ' ', $intake->status)) }})</option>
                            @endforeach
                            @if ($publishedVersion && ! $intakes->contains('id', $publishedVersion->id))
                                <option value="{{ $publishedVersion->id }}">{{ $publishedVersion->intakeLabel() }} (published)</option>
                            @endif
                        </select>
                    </div>
                    <div class="tich-form-group">
                        <button type="submit" class="tich-btn tich-btn-primary">Create intake</button>
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
    @endif
</article>
