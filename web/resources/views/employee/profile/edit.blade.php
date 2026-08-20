@extends('layouts.employee')

@section('employee-content')
    @php($mustCompleteProfile = $mustCompleteProfile ?? false)

    <x-page-toolbar
        title="{{ $mustCompleteProfile ? 'Complete your profile' : 'Update my profile' }}"
        meta="{{ $mustCompleteProfile ? 'Required before you can use the ERP' : 'Changes are reviewed by HR before they take effect' }}"
    >
        <x-slot:actions>
            @unless ($mustCompleteProfile)
                <a href="{{ route('employee.dashboard') }}" class="tich-btn tich-btn-ghost">Back to profile</a>
            @endunless
        </x-slot:actions>
    </x-page-toolbar>

    @if ($mustCompleteProfile)
        <div class="tich-alert tich-alert--warning tich-mt-4" role="status">
            <strong>Profile confirmation required.</strong>
            Fill in the marked fields so we have accurate contact and emergency details on record. You cannot open other ERP modules until this is done.
            @if (! empty($missingProfileLabels))
                <p class="tich-mt-2 tich-caption">Still needed: {{ implode(', ', $missingProfileLabels) }}</p>
            @endif
        </div>
    @endif

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
            <p class="tich-caption tich-mt-2">
                @if ($mustCompleteProfile)
                    Optional for now. You can add a square (1:1) headshot; later photo changes go to HR for approval.
                @else
                    Upload a square (1:1) headshot. HR must approve before it appears on your profile.
                @endif
            </p>

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
            <h2 class="tich-h3">Legal name</h2>
            <p class="tich-caption tich-mt-2">
                @if ($mustCompleteProfile)
                    Invited accounts may show a temporary name from your email address. Enter your full legal name as it should appear on HR records.
                @else
                    Name changes are reviewed by HR before they take effect.
                @endif
            </p>

            <div class="tich-grid tich-grid--2 tich-mt-4" style="gap:1rem;">
                <div>
                    <label for="first_name" class="tich-label">First name @if ($mustCompleteProfile)<span class="tich-caption" style="color:#b45309;">*</span>@endif</label>
                    <input type="text" id="first_name" name="first_name" class="tich-input @error('first_name') tich-input--error @enderror" value="{{ old('first_name', $staff->first_name) }}" @required($mustCompleteProfile) autocomplete="given-name">
                    @error('first_name')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="middle_name" class="tich-label">Middle name</label>
                    <input type="text" id="middle_name" name="middle_name" class="tich-input @error('middle_name') tich-input--error @enderror" value="{{ old('middle_name', $staff->middle_name) }}" autocomplete="additional-name">
                    @error('middle_name')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="surname" class="tich-label">Surname @if ($mustCompleteProfile)<span class="tich-caption" style="color:#b45309;">*</span>@endif</label>
                    <input type="text" id="surname" name="surname" class="tich-input @error('surname') tich-input--error @enderror" value="{{ old('surname', $staff->surname === 'Invitee' ? '' : $staff->surname) }}" @required($mustCompleteProfile) autocomplete="family-name">
                    @error('surname')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="date_of_birth" class="tich-label">Date of birth @if ($mustCompleteProfile)<span class="tich-caption" style="color:#b45309;">*</span>@endif</label>
                    @php
                        $dobValue = old('date_of_birth');
                        if ($dobValue === null) {
                            $dob = $staff->date_of_birth;
                            $dobValue = ($dob && $dob->format('Y-m-d') !== '1990-01-01') ? $dob->format('Y-m-d') : '';
                        }
                    @endphp
                    <input type="date" id="date_of_birth" name="date_of_birth" class="tich-input @error('date_of_birth') tich-input--error @enderror" value="{{ $dobValue }}" @required($mustCompleteProfile)>
                    @error('date_of_birth')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="gender" class="tich-label">Gender @if ($mustCompleteProfile)<span class="tich-caption" style="color:#b45309;">*</span>@endif</label>
                    <select id="gender" name="gender" class="tich-input @error('gender') tich-input--error @enderror" @required($mustCompleteProfile)>
                        <option value="">-</option>
                        @foreach (['Male', 'Female', 'Other'] as $gender)
                            <option value="{{ $gender }}" @selected(old('gender', $staff->gender) === $gender)>{{ $gender }}</option>
                        @endforeach
                    </select>
                    @error('gender')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </article>

        <article class="tich-card tich-mt-8">
            <h2 class="tich-h3">Contact &amp; personal details</h2>
            <p class="tich-caption tich-mt-2">Employment, payroll, and department assignment are managed by HR.</p>

            <div class="tich-grid tich-grid--2 tich-mt-4" style="gap:1rem;">
                <div>
                    <label for="primary_email" class="tich-label">Personal email @if ($mustCompleteProfile)<span class="tich-caption" style="color:#b45309;">*</span>@endif</label>
                    <input type="email" id="primary_email" name="primary_email" class="tich-input @error('primary_email') tich-input--error @enderror" value="{{ old('primary_email', $staff->primary_email) }}" @required($mustCompleteProfile)>
                    @error('primary_email')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="phone_number" class="tich-label">Phone number @if ($mustCompleteProfile)<span class="tich-caption" style="color:#b45309;">*</span>@endif</label>
                    <input type="text" id="phone_number" name="phone_number" class="tich-input @error('phone_number') tich-input--error @enderror" value="{{ old('phone_number', $staff->phone_number) }}" @required($mustCompleteProfile)>
                    @error('phone_number')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="alt_phone_number" class="tich-label">Alternative phone</label>
                    <input type="text" id="alt_phone_number" name="alt_phone_number" class="tich-input" value="{{ old('alt_phone_number', $staff->alt_phone_number) }}">
                </div>
                <div>
                    <label for="marital_status" class="tich-label">Marital status @if ($mustCompleteProfile)<span class="tich-caption" style="color:#b45309;">*</span>@endif</label>
                    <select id="marital_status" name="marital_status" class="tich-input @error('marital_status') tich-input--error @enderror" @required($mustCompleteProfile)>
                        <option value="">-</option>
                        @foreach (['Single', 'Married', 'Divorced', 'Widowed', 'Separated'] as $status)
                            <option value="{{ $status }}" @selected(old('marital_status', $staff->marital_status) === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                    @error('marital_status')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
                <div style="grid-column:1/-1;">
                    <label for="physical_address" class="tich-label">Physical address @if ($mustCompleteProfile)<span class="tich-caption" style="color:#b45309;">*</span>@endif</label>
                    <textarea id="physical_address" name="physical_address" rows="2" class="tich-input @error('physical_address') tich-input--error @enderror" @required($mustCompleteProfile)>{{ old('physical_address', $staff->physical_address) }}</textarea>
                    @error('physical_address')<p class="tich-form-error">{{ $message }}</p>@enderror
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
                    <label for="emergency_contact_name" class="tich-label">Emergency contact name @if ($mustCompleteProfile)<span class="tich-caption" style="color:#b45309;">*</span>@endif</label>
                    <input type="text" id="emergency_contact_name" name="emergency_contact_name" class="tich-input @error('emergency_contact_name') tich-input--error @enderror" value="{{ old('emergency_contact_name', $staff->emergency_contact_name) }}" @required($mustCompleteProfile)>
                    @error('emergency_contact_name')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="emergency_contact_phone" class="tich-label">Emergency contact phone @if ($mustCompleteProfile)<span class="tich-caption" style="color:#b45309;">*</span>@endif</label>
                    <input type="text" id="emergency_contact_phone" name="emergency_contact_phone" class="tich-input @error('emergency_contact_phone') tich-input--error @enderror" value="{{ old('emergency_contact_phone', $staff->emergency_contact_phone) }}" @required($mustCompleteProfile)>
                    @error('emergency_contact_phone')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="emergency_contact_relationship" class="tich-label">Emergency contact relationship @if ($mustCompleteProfile)<span class="tich-caption" style="color:#b45309;">*</span>@endif</label>
                    <input type="text" id="emergency_contact_relationship" name="emergency_contact_relationship" class="tich-input @error('emergency_contact_relationship') tich-input--error @enderror" value="{{ old('emergency_contact_relationship', $staff->emergency_contact_relationship) }}" @required($mustCompleteProfile)>
                    @error('emergency_contact_relationship')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </article>

        @unless ($mustCompleteProfile)
            <article class="tich-card tich-mt-8">
                <h2 class="tich-h3">Add qualification / certificate</h2>
                <p class="tich-caption tich-mt-2">Optional. New qualifications require HR verification before they appear on your record.</p>

                <div class="tich-grid tich-grid--2 tich-mt-4" style="gap:1rem;">
                    <div>
                        <label for="qualification_type" class="tich-label">Qualification type</label>
                        <select id="qualification_type" name="qualification_type" class="tich-input">
                            <option value="">- Skip -</option>
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
        @endunless

        <div class="tich-flex tich-mt-8" style="gap:0.75rem; flex-wrap:wrap;">
            <button type="submit" class="tich-btn tich-btn-primary">
                {{ $mustCompleteProfile ? 'Save and continue' : 'Submit for HR approval' }}
            </button>
            @unless ($mustCompleteProfile)
                <a href="{{ route('employee.dashboard') }}" class="tich-btn tich-btn-ghost">Cancel</a>
            @endunless
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
