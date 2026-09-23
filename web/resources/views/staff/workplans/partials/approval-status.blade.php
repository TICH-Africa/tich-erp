@php
    $registrar = $summary['registrar'] ?? [];
    $qa = $summary['qa'] ?? [];
@endphp
<div class="tich-grid tich-grid--2" style="align-items:start; gap:1rem;">
    <div class="tich-card" style="padding:1rem;">
        <p class="tich-caption">Academic Registrar</p>
        <p class="tich-mt-2">
            @if (! empty($registrar['approved']))
                <span class="tich-badge tich-badge--success">Registrar ✓</span>
            @elseif (($registrar['status'] ?? '') === 'rejected')
                <span class="tich-badge tich-badge--danger">Rejected</span>
            @elseif (($registrar['status'] ?? '') === 'changes_requested')
                <span class="tich-badge tich-badge--warning">Changes requested</span>
            @else
                <span class="tich-badge tich-badge--info">Registrar pending</span>
            @endif
        </p>
        @if (! empty($registrar['actor']))
            <p class="tich-caption tich-mt-2">{{ $registrar['actor'] }}@if (! empty($registrar['acted_at'])) · {{ $registrar['acted_at'] }}@endif</p>
        @endif
        @if (! empty($registrar['comments']))
            <p class="tich-text tich-mt-2">{{ $registrar['comments'] }}</p>
        @endif
    </div>
    <div class="tich-card" style="padding:1rem;">
        <p class="tich-caption">Quality Assurance</p>
        <p class="tich-mt-2">
            @if (! empty($qa['approved']))
                <span class="tich-badge tich-badge--success">QA ✓</span>
            @elseif (($qa['status'] ?? '') === 'rejected')
                <span class="tich-badge tich-badge--danger">Rejected</span>
            @elseif (($qa['status'] ?? '') === 'changes_requested')
                <span class="tich-badge tich-badge--warning">Changes requested</span>
            @else
                <span class="tich-badge tich-badge--info">QA pending</span>
            @endif
        </p>
        @if (! empty($qa['actor']))
            <p class="tich-caption tich-mt-2">{{ $qa['actor'] }}@if (! empty($qa['acted_at'])) · {{ $qa['acted_at'] }}@endif</p>
        @endif
        @if (! empty($qa['comments']))
            <p class="tich-text tich-mt-2">{{ $qa['comments'] }}</p>
        @endif
    </div>
</div>
