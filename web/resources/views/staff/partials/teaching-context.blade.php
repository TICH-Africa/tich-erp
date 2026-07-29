@if (! empty($portalData['teaching_context']['items']))
    <article class="tich-card" style="margin-bottom:1.5rem; border-left:4px solid var(--tich-blue, #1669a6); padding:1rem 1.25rem;">
        <p class="tich-caption" style="margin:0 0 0.5rem;">Teaching intake &amp; cohort context</p>
        @if (count($portalData['teaching_context']['items']) === 1)
            <p class="tich-text" style="margin:0;">
                You are teaching <strong>{{ $portalData['teaching_context']['summary'] }}</strong>.
                Student lists, attendance, and grades apply to this intake only.
            </p>
        @else
            <p class="tich-text" style="margin:0 0 0.75rem;">You have teaching assignments across the following intakes. Check the intake before taking attendance or entering grades.</p>
            <ul class="tich-text" style="margin:0; padding-left:1.25rem;">
                @foreach ($portalData['teaching_context']['items'] as $context)
                    <li style="margin-bottom:0.35rem;">
                        <strong>{{ $context['program_code'] ?? $context['unit_code'] ?? 'Unit' }}</strong>
                        @if (! empty($context['program_name']) && ! empty($context['program_code']))
                            <span class="tich-caption">({{ $context['program_name'] }})</span>
                        @endif
                        · {{ $context['intake_label'] ?? 'Intake not set' }}
                        · {{ $context['semester_label'] ?? 'Semester not set' }}
                        @if (! empty($context['campus']))
                            · {{ $context['campus'] }}
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </article>
@endif
