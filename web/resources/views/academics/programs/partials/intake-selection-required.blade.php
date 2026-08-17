@php
    $intakeListParams = array_filter(array_merge($hub, [
        'program' => $program->id,
        'section' => 'intakes',
    ]));
@endphp

<article class="tich-card">
    <div class="tich-notice tich-notice--warning tich-mb-4">
        <p class="tich-text" style="margin:0;">
            <strong>Select or create an intake before using {{ strtolower($currentSectionLabel ?? 'this tool') }}.</strong>
        </p>
    </div>

    <p class="tich-text">
        This prevents semester units, timetables, applications, and enrolled students from being viewed or edited against the wrong cohort.
    </p>

    @if ($intakes->isEmpty())
        <p class="tich-text tich-mt-4">
            No intakes exist for this programme yet.
        </p>
        <div class="tich-mt-4">
            <a href="{{ route('departments.academics.programs.curriculum', $intakeListParams) }}" class="tich-btn tich-btn-primary tich-ml-2">
                Create intake
            </a>
            <a href="{{ route('departments.academics.programs.curriculum', $intakeListParams) }}" class="tich-btn tich-btn-ghost tich-ml-2">
                View All Intakes
            </a>
        </div>
    @else
        <form method="GET" action="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'section' => $section])) }}" class="tich-mt-4" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end;">
            <div class="tich-form-group" style="margin:0; min-width:16rem;">
                <label for="required-intake-select" class="tich-label">
                    <a href="{{ route('departments.academics.programs.curriculum', $intakeListParams) }}" class="tich-link">Select intake</a>
                </label>
                <select
                    id="required-intake-select"
                    name="intake"
                    class="tich-input"
                    required
                    onchange="if (this.value) this.form.submit()"
                >
                    <option value="" disabled selected>Choose intake…</option>
                    @foreach ($intakes as $intakeOption)
                        <option value="{{ $intakeOption->id }}">
                            {{ $intakeOption->intakeLabel() }}
                            @if ($intakeOption->status !== 'published')
                                ({{ ucfirst($intakeOption->status) }})
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <a href="{{ route('departments.academics.programs.curriculum', $intakeListParams) }}" class="tich-link">Manage intakes</a>
        </form>
    @endif
</article>