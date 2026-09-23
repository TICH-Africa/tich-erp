{{-- Shared photo lightbox; bind with [data-photo-lightbox] on triggers. --}}
@once
    @push('scripts')
        <div id="tich-photo-lightbox" class="tich-photo-lightbox" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-label="Photo preview">
            <button type="button" class="tich-photo-lightbox__backdrop" data-photo-lightbox-close aria-label="Close photo"></button>
            <div class="tich-photo-lightbox__dialog">
                <button type="button" class="tich-photo-lightbox__close" data-photo-lightbox-close aria-label="Close">&times;</button>
                <img src="" alt="" class="tich-photo-lightbox__img" data-photo-lightbox-img>
                <p class="tich-photo-lightbox__caption" data-photo-lightbox-caption hidden></p>
            </div>
        </div>
        <x-asset.script path="js/tich-photo-lightbox.js" />
    @endpush
@endonce
