@php
    $label = $member->fullName();
    $photoUrl = $member->photoUrl();
@endphp
@if ($photoUrl)
    <button
        type="button"
        class="tich-staff-table-avatar tich-staff-table-avatar--clickable"
        data-photo-lightbox
        data-photo-src="{{ $photoUrl }}"
        data-photo-alt="{{ $label }}"
        title="View photo"
        aria-label="View photo of {{ $label }}"
    >
        <img src="{{ $photoUrl }}" alt="">
    </button>
@else
    <div class="tich-staff-table-avatar" aria-hidden="true">
        <span>{{ $member->initials() }}</span>
    </div>
@endif
@include('partials.photo-lightbox')
