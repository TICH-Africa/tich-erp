<div class="tich-staff-table-avatar" aria-hidden="true">
    @if ($member->photoUrl())
        <img src="{{ $member->photoUrl() }}" alt="">
    @else
        <span>{{ $member->initials() }}</span>
    @endif
</div>
