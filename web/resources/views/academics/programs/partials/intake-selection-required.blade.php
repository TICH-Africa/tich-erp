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
        <div class="tich-mt-4">
            <a href="{{ route('departments.academics.programs.curriculum', $intakeListParams) }}" class="tich-btn tich-btn-primary tich-ml-2">
                Create intake
            </a>
        </div>
    @else
        <div class="tich-mt-4">
            <p class="tich-text tich-mb-2"><strong>Existing intakes</strong></p>
            <div class="tich-stack tich-mb-4" style="gap:0.5rem;">
                @foreach ($intakes as $intakeOption)
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:0.5rem 0.75rem; border:1px solid var(--tich-border,#e5e7eb); border-radius:4px;">
                        <span>
                            <strong>{{ $intakeOption->intakeLabel() }}</strong>
                            @if ($intakeOption->status !== 'published')
                                <span class="tich-caption">({{ ucfirst($intakeOption->status) }})</span>
                            @endif
                        </span>
                        <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'intake' => $intakeOption->id, 'section' => $section])) }}" class="tich-btn tich-btn-secondary tich-btn-sm">
                            Use this intake
                        </a>
                    </div>
                @endforeach
            </div>
            <div class="tich-mt-4" style="border-top:1px solid var(--tich-border,#e5e7eb); padding-top:1rem;">
                <a href="{{ route('departments.academics.programs.curriculum', $intakeListParams) }}" class="tich-btn tich-btn-ghost">
                    Create a new intake
                </a>
            </div>
        </div>
    @endif
</article>
