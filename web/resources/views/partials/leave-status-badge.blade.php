@php
    $statusKey = $status ?? '';
    $badgeClass = match ($statusKey) {
        'pending_hr' => 'tich-badge--warning',
        'returned' => 'tich-badge--info',
        'approved' => 'tich-badge--success',
        'rejected' => 'tich-badge--danger',
        'cancelled' => 'tich-badge--danger',
        default => '',
    };
    $label = $label ?? ucfirst(str_replace('_', ' ', $statusKey));
@endphp
<span class="tich-badge {{ $badgeClass }}">{{ $label }}</span>
