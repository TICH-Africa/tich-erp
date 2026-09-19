{{-- Shared staff profile photo picker (crop → profile_photo file field). --}}
@php
    /** @var \App\Models\Staff $staff */
    $photoHelp = $photoHelp ?? 'JPG or PNG, max 5 MB. Crop to a square before you save.';
@endphp

<article class="tich-card tich-mb-6">
    <h2 class="tich-h3">Profile photo</h2>
    <p class="tich-caption tich-mt-2">{{ $photoHelp }}</p>

    <div class="tich-employee-profile-photo tich-mt-4">
        <div class="tich-employee-profile-photo__preview{{ $staff->photoUrl() ? ' is-ready' : '' }}" id="photo-preview-wrap">
            @if ($staff->photoUrl())
                <img src="{{ $staff->photoUrl() }}" alt="{{ $staff->fullName() }}" id="photo-preview" class="tich-employee-profile-photo__img">
            @else
                <div class="tich-employee-profile-photo__placeholder" id="photo-preview">{{ $staff->initials() }}</div>
            @endif
        </div>
        <div class="tich-employee-profile-photo__actions">
            <label for="photo_input" class="tich-btn tich-btn-secondary">Choose photo</label>
            <input type="file" id="photo_input" accept="image/jpeg,image/png,image/webp" hidden>
            <input type="file" name="profile_photo" id="profile_photo_file" accept="image/jpeg,image/png,image/webp" hidden>
            <input type="hidden" name="profile_photo_ready" id="profile_photo_ready" value="">
            @error('profile_photo')
                <p class="tich-form-error tich-mt-2">{{ $message }}</p>
            @enderror
        </div>
    </div>
</article>

<div class="tich-modal tich-photo-crop-modal" id="photo-crop-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="photo-crop-title">
    <div class="tich-modal__backdrop" data-close-crop></div>
    <div class="tich-modal__dialog tich-photo-crop-modal__dialog">
        <div class="tich-modal__header tich-photo-crop-modal__header">
            <div>
                <h2 class="tich-h3" id="photo-crop-title">Crop profile photo</h2>
                <p class="tich-caption tich-mt-2">Drag to reposition. The headshot will be saved as a square image.</p>
            </div>
            <button type="button" class="tich-modal__close" data-close-crop aria-label="Close crop dialog">&times;</button>
        </div>
        <div class="tich-modal__body tich-photo-crop-modal__body">
            <div class="tich-photo-crop-modal__stage">
                <img id="photo-crop-source" alt="Photo to crop">
            </div>
            <p class="tich-caption tich-photo-crop-modal__hint">Tip: centre the face in the frame for the best result on ID cards and profiles.</p>
        </div>
        <div class="tich-modal__footer tich-photo-crop-modal__footer">
            <button type="button" class="tich-btn tich-btn-ghost" data-close-crop>Cancel</button>
            <button type="button" class="tich-btn tich-btn-primary" id="photo-crop-apply">Use this photo</button>
        </div>
    </div>
</div>
