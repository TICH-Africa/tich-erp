@extends('layouts.employee')

@section('employee-content')
    <x-page-toolbar
        title="Update my profile"
        meta="Changes are reviewed by HR before they take effect"
    >
        <x-slot:actions>
            <a href="{{ route('employee.dashboard') }}" class="tich-btn tich-btn-ghost">Back to profile</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if ($errors->has('form'))
        <div class="tich-alert tich-alert--danger tich-mt-4">{{ $errors->first('form') }}</div>
    @endif

    @if ($pendingRequests->isNotEmpty())
        <article class="tich-card tich-mt-6" style="border-left:4px solid #d97706;">
            <h2 class="tich-h3">Pending HR review</h2>
            <ul class="tich-mt-4" style="list-style:none; padding:0;">
                @foreach ($pendingRequests as $pending)
                    <li class="tich-text tich-mt-2" style="padding-bottom:0.5rem; border-bottom:1px solid var(--tich-neutral-border);">
                        <strong>{{ $pending->typeLabel() }}</strong>
                        <span class="tich-caption"> · submitted {{ $pending->created_at->format('d M Y H:i') }}</span>
                    </li>
                @endforeach
            </ul>
        </article>
    @endif

    <form method="POST" action="{{ route('employee.profile.update') }}" enctype="multipart/form-data" class="tich-mt-6" id="employee-profile-form">
        @csrf

        <article class="tich-card">
            <h2 class="tich-h3">Profile photo</h2>
            <p class="tich-caption tich-mt-2">Upload a square (1:1) headshot. HR must approve before it appears on your profile.</p>

            <div class="tich-employee-profile-photo tich-mt-4">
                <div class="tich-employee-profile-photo__preview" id="photo-preview-wrap">
                    @if ($staff->photoUrl())
                        <img src="{{ $staff->photoUrl() }}" alt="{{ $staff->fullName() }}" id="photo-preview" class="tich-employee-profile-photo__img">
                    @else
                        <div class="tich-employee-profile-photo__placeholder" id="photo-preview">{{ $staff->initials() }}</div>
                    @endif
                </div>
                <div>
                    <label for="photo_input" class="tich-btn tich-btn-secondary">Choose photo</label>
                    <input type="file" id="photo_input" accept="image/jpeg,image/png,image/webp" hidden>
                    <p class="tich-caption tich-mt-2">JPG or PNG, max 5 MB. You will crop to a square before submitting.</p>
                </div>
            </div>

            <input type="hidden" name="cropped_photo" id="cropped_photo" value="">
        </article>

        <article class="tich-card tich-mt-8">
            <h2 class="tich-h3">Contact &amp; personal details</h2>
            <p class="tich-caption tich-mt-2">Employment, payroll, and identity fields cannot be changed here.</p>

            <div class="tich-grid tich-grid--2 tich-mt-4" style="gap:1rem;">
                <div>
                    <label for="primary_email" class="tich-label">Personal email</label>
                    <input type="email" id="primary_email" name="primary_email" class="tich-input @error('primary_email') tich-input--error @enderror" value="{{ old('primary_email', $staff->primary_email) }}">
                    @error('primary_email')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="phone_number" class="tich-label">Phone number</label>
                    <input type="text" id="phone_number" name="phone_number" class="tich-input @error('phone_number') tich-input--error @enderror" value="{{ old('phone_number', $staff->phone_number) }}">
                    @error('phone_number')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="alt_phone_number" class="tich-label">Alternative phone</label>
                    <input type="text" id="alt_phone_number" name="alt_phone_number" class="tich-input" value="{{ old('alt_phone_number', $staff->alt_phone_number) }}">
                </div>
                <div>
                    <label for="marital_status" class="tich-label">Marital status</label>
                    <select id="marital_status" name="marital_status" class="tich-input">
                        <option value="">—</option>
                        @foreach (['Single', 'Married', 'Divorced', 'Widowed', 'Separated'] as $status)
                            <option value="{{ $status }}" @selected(old('marital_status', $staff->marital_status) === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="grid-column:1/-1;">
                    <label for="physical_address" class="tich-label">Physical address</label>
                    <textarea id="physical_address" name="physical_address" rows="2" class="tich-input">{{ old('physical_address', $staff->physical_address) }}</textarea>
                </div>
                <div>
                    <label for="postal_address" class="tich-label">Postal address</label>
                    <input type="text" id="postal_address" name="postal_address" class="tich-input" value="{{ old('postal_address', $staff->postal_address) }}">
                </div>
                <div>
                    <label for="postal_code" class="tich-label">Postal code</label>
                    <input type="text" id="postal_code" name="postal_code" class="tich-input" value="{{ old('postal_code', $staff->postal_code) }}">
                </div>
                <div>
                    <label for="home_county" class="tich-label">Home county</label>
                    <input type="text" id="home_county" name="home_county" class="tich-input" value="{{ old('home_county', $staff->home_county) }}">
                </div>
                <div>
                    <label for="emergency_contact_name" class="tich-label">Emergency contact name</label>
                    <input type="text" id="emergency_contact_name" name="emergency_contact_name" class="tich-input" value="{{ old('emergency_contact_name', $staff->emergency_contact_name) }}">
                </div>
                <div>
                    <label for="emergency_contact_phone" class="tich-label">Emergency contact phone</label>
                    <input type="text" id="emergency_contact_phone" name="emergency_contact_phone" class="tich-input" value="{{ old('emergency_contact_phone', $staff->emergency_contact_phone) }}">
                </div>
                <div>
                    <label for="emergency_contact_relationship" class="tich-label">Emergency contact relationship</label>
                    <input type="text" id="emergency_contact_relationship" name="emergency_contact_relationship" class="tich-input" value="{{ old('emergency_contact_relationship', $staff->emergency_contact_relationship) }}">
                </div>
            </div>
        </article>

        <article class="tich-card tich-mt-8">
            <h2 class="tich-h3">Add qualification / certificate</h2>
            <p class="tich-caption tich-mt-2">Optional. New qualifications require HR verification before they appear on your record.</p>

            <div class="tich-grid tich-grid--2 tich-mt-4" style="gap:1rem;">
                <div>
                    <label for="qualification_type" class="tich-label">Qualification type</label>
                    <select id="qualification_type" name="qualification_type" class="tich-input">
                        <option value="">— Skip —</option>
                        @foreach ($qualificationTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('qualification_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="qualification_name" class="tich-label">Qualification name</label>
                    <input type="text" id="qualification_name" name="qualification_name" class="tich-input @error('qualification_name') tich-input--error @enderror" value="{{ old('qualification_name') }}" placeholder="e.g. BSc Community Health">
                    @error('qualification_name')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="institution" class="tich-label">Institution</label>
                    <input type="text" id="institution" name="institution" class="tich-input" value="{{ old('institution') }}">
                </div>
                <div>
                    <label for="year_completed" class="tich-label">Year completed</label>
                    <input type="number" id="year_completed" name="year_completed" class="tich-input" min="1950" max="{{ now()->year + 1 }}" value="{{ old('year_completed') }}">
                </div>
                <div>
                    <label for="grade_or_class" class="tich-label">Grade / class</label>
                    <input type="text" id="grade_or_class" name="grade_or_class" class="tich-input" value="{{ old('grade_or_class') }}">
                </div>
                <div>
                    <label for="certificate_number" class="tich-label">Certificate number</label>
                    <input type="text" id="certificate_number" name="certificate_number" class="tich-input" value="{{ old('certificate_number') }}">
                </div>
                <div style="grid-column:1/-1;">
                    <label for="certificate_file" class="tich-label">Certificate file (PDF or image)</label>
                    <input type="file" id="certificate_file" name="certificate_file" class="tich-input" accept=".pdf,.jpg,.jpeg,.png">
                </div>
            </div>
        </article>

        <article class="tich-card tich-mt-8">
            <label for="employee_notes" class="tich-label">Notes for HR (optional)</label>
            <textarea id="employee_notes" name="employee_notes" rows="3" class="tich-input tich-mt-2" placeholder="Explain any changes if helpful for the reviewer">{{ old('employee_notes') }}</textarea>
        </article>

        <div class="tich-flex tich-mt-8" style="gap:0.75rem; flex-wrap:wrap;">
            <button type="submit" class="tich-btn tich-btn-primary">Submit for HR approval</button>
            <a href="{{ route('employee.dashboard') }}" class="tich-btn tich-btn-ghost">Cancel</a>
        </div>
    </form>

    <div class="tich-modal" id="photo-crop-modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="tich-modal__backdrop" data-close-crop></div>
        <div class="tich-modal__dialog" style="max-width:520px;">
            <h2 class="tich-h3">Crop profile photo</h2>
            <p class="tich-caption tich-mt-2">Drag to reposition. The photo will be saved as a square (1:1).</p>
            <div class="tich-mt-4" style="max-height:360px; overflow:hidden; background:#111;">
                <img id="photo-crop-source" alt="" style="max-width:100%; display:block;">
            </div>
            <div class="tich-flex tich-mt-6" style="gap:0.75rem; justify-content:flex-end;">
                <button type="button" class="tich-btn tich-btn-ghost" data-close-crop>Cancel</button>
                <button type="button" class="tich-btn tich-btn-primary" id="photo-crop-apply">Apply crop</button>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
    <style>
        .tich-employee-profile-photo { display:flex; align-items:center; gap:1.25rem; flex-wrap:wrap; }
        .tich-employee-profile-photo__preview { width:7rem; height:7rem; border-radius:50%; overflow:hidden; flex-shrink:0; border:2px solid var(--tich-neutral-border); background:var(--tich-surface-muted, #f1f5f9); display:flex; align-items:center; justify-content:center; }
        .tich-employee-profile-photo__img { width:100%; height:100%; object-fit:cover; }
        .tich-employee-profile-photo__placeholder { font-family:var(--font-heading); font-size:1.5rem; font-weight:700; color:var(--tich-blue); }
    </style>
    <script>
        (function () {
            var fileInput = document.getElementById('photo_input');
            var cropModal = document.getElementById('photo-crop-modal');
            var cropSource = document.getElementById('photo-crop-source');
            var croppedField = document.getElementById('cropped_photo');
            var previewWrap = document.getElementById('photo-preview-wrap');
            var cropper = null;

            function closeCropModal() {
                cropModal.classList.remove('is-open');
                cropModal.setAttribute('aria-hidden', 'true');
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                fileInput.value = '';
            }

            document.querySelectorAll('[data-close-crop]').forEach(function (el) {
                el.addEventListener('click', closeCropModal);
            });

            fileInput.addEventListener('change', function () {
                var file = fileInput.files && fileInput.files[0];
                if (!file) {
                    return;
                }

                var reader = new FileReader();
                reader.onload = function (event) {
                    cropSource.src = event.target.result;
                    cropModal.classList.add('is-open');
                    cropModal.setAttribute('aria-hidden', 'false');

                    if (cropper) {
                        cropper.destroy();
                    }

                    cropper = new Cropper(cropSource, {
                        aspectRatio: 1,
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 1,
                        responsive: true,
                    });
                };
                reader.readAsDataURL(file);
            });

            document.getElementById('photo-crop-apply').addEventListener('click', function () {
                if (!cropper) {
                    return;
                }

                var canvas = cropper.getCroppedCanvas({ width: 400, height: 400, imageSmoothingQuality: 'high' });
                var dataUrl = canvas.toDataURL('image/jpeg', 0.9);
                croppedField.value = dataUrl;

                previewWrap.innerHTML = '<img src="' + dataUrl + '" alt="Profile preview" class="tich-employee-profile-photo__img" id="photo-preview">';
                closeCropModal();
            });
        })();
    </script>
@endsection
