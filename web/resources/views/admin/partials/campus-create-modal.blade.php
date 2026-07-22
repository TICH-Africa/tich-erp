@props([
    'parentCampuses',
    'campusTypes',
    'open' => false,
])

<div
    id="campus-create-modal"
    class="tich-modal{{ $open ? ' is-open' : '' }}"
    aria-hidden="{{ $open ? 'false' : 'true' }}"
    role="dialog"
    aria-modal="true"
    aria-labelledby="campus-create-modal-title"
>
    <div class="tich-modal__backdrop" data-close-modal="campus-create-modal"></div>

    <div class="tich-modal__dialog">
        <header class="tich-modal__header">
            <h2 id="campus-create-modal-title" class="tich-h3" style="margin: 0;">Add campus</h2>
            <button type="button" class="tich-modal__close" data-close-modal="campus-create-modal" aria-label="Close">&times;</button>
        </header>

        <form method="POST" action="{{ route('admin.campuses.store') }}" class="tich-modal__body">
            @csrf

            @if ($errors->any() && old('_method') !== 'PUT')
                <div class="tich-modal__errors">
                    <ul style="margin: 0; padding-left: 1.25rem;">
                        @foreach ($errors->all() as $error)
                            <li class="tich-text">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @include('admin.partials.campus-form-fields', [
                'parentCampuses' => $parentCampuses,
                'campusTypes' => $campusTypes,
            ])

            <footer class="tich-modal__footer">
                <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="campus-create-modal">Cancel</button>
                <button type="submit" class="tich-btn tich-btn-primary">Create campus</button>
            </footer>
        </form>
    </div>
</div>
