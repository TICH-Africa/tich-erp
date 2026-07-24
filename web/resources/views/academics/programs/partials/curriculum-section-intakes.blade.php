<article class="tich-card">
    <div class="tich-dept-panel__head">
        <h2 class="tich-h3">Intakes</h2>
        <p class="tich-text">Create an intake by year and month. Each intake shares the programme structure and can have its own unit mapping and applications.</p>
    </div>

    @if ($periods->isEmpty())
        <p class="tich-text tich-mt-4">Save the <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'section' => 'structure'])) }}" class="tich-link">programme structure</a> first to generate semesters.</p>
    @else
        @if ($intakes->isNotEmpty())
            <div class="tich-mt-4" style="display:flex; flex-wrap:wrap; gap:0.5rem;">
                @foreach ($intakes as $intake)
                    <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'intake' => $intake->id, 'section' => 'semesters'])) }}"
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
