@extends('layouts.employee')

@section('employee-content')
    @php
        $mustCompleteProfile = $mustCompleteProfile ?? false;
        $highlightFields = $highlightFields ?? [];
        $profileFieldHighlighted = fn (string $field): bool => in_array($field, $highlightFields, true);
        $profileHighlightClass = fn (string $field): string => $profileFieldHighlighted($field) ? 'tich-profile-field--highlighted' : '';
    @endphp

    <div class="tich-page-toolbar">
        <div>
            <h1 class="tich-h3">{{ $mustCompleteProfile ? 'Complete your profile' : 'Update my profile' }}</h1>
            <p class="tich-caption tich-mt-2">{{ $mustCompleteProfile ? 'Required before you can use the ERP' : 'Changes are reviewed by HR before they take effect' }}</p>
        </div>
        <div>
            @unless ($mustCompleteProfile)
                <a href="{{ route('employee.dashboard') }}" class="tich-btn tich-btn-ghost">Back to profile</a>
            @endunless
        </div>
    </div>

    @if ($mustCompleteProfile)
        <div class="tich-alert tich-alert--warning tich-mt-4" role="status">
            <strong>Profile confirmation required.</strong>
            Fill in your legal name and the marked contact fields. Invitees often start with a temporary name from their email - replace it with your real name. You cannot open other ERP modules until this is done.
            @if (! empty($missingProfileLabels))
                <p class="tich-mt-2 tich-caption">Still needed: {{ implode(', ', $missingProfileLabels) }}</p>
            @endif
        </div>
    @endif

    @if ($profileUpdatePrompt ?? null)
        <div class="tich-alert tich-alert--warning tich-mt-4" role="status" id="profile-update-prompt-banner">
            <strong>Profile update requested.</strong>
            {{ $profileUpdatePrompt->requested_via_module === 'ict' ? 'ICT' : 'HR' }} has asked you to update:
            {{ implode(', ', $profileUpdatePrompt->fieldLabels()) }}.
            @if ($profileUpdatePrompt->notes)
                <p class="tich-mt-2 tich-caption"><strong>Note:</strong> {{ $profileUpdatePrompt->notes }}</p>
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

        <article class="tich-card {{ $profileHighlightClass('photo') }}" id="profile-field-photo">
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
                <div class="tich-employee-profile-photo__actions">
                    <label for="photo_input" class="tich-btn tich-btn-secondary">Choose photo</label>
                    <input type="file" id="photo_input" accept="image/jpeg,image/png,image/webp" hidden>
                    <input type="file" name="profile_photo" id="profile_photo_file" accept="image/jpeg,image/png,image/webp" hidden>
                    <input type="hidden" name="profile_photo_ready" id="profile_photo_ready" value="">
                    <p class="tich-caption tich-mt-2">JPG or PNG, max 5 MB. Crop to a square before you save.</p>
                    @unless ($mustCompleteProfile)
                        <p class="tich-caption tich-mt-1">After you submit, HR must approve the photo before it appears on your profile.</p>
                    @endunless
                </div>
            </div>
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
                <div class="{{ $profileHighlightClass('first_name') }}" id="profile-field-first_name">
                    <label for="first_name" class="tich-label">First name (as per National ID) @if ($mustCompleteProfile)<span class="tich-caption" style="color:#b45309;">*</span>@endif</label>
                    <input type="text" id="first_name" name="first_name" class="tich-input @error('first_name') tich-input--error @enderror" value="{{ old('first_name', strcasecmp((string) $staff->first_name, 'Pending') === 0 ? '' : $staff->first_name) }}" @required($mustCompleteProfile) autocomplete="given-name" placeholder="Enter your first name">
                    @error('first_name')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="{{ $profileHighlightClass('middle_name') }}" id="profile-field-middle_name">
                    <label for="middle_name" class="tich-label">Middle name</label>
                    <input type="text" id="middle_name" name="middle_name" class="tich-input @error('middle_name') tich-input--error @enderror" value="{{ old('middle_name', $staff->middle_name) }}" autocomplete="additional-name">
                    @error('middle_name')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="{{ $profileHighlightClass('surname') }}" id="profile-field-surname">
                    <label for="surname" class="tich-label">Surname (as per National ID) @if ($mustCompleteProfile)<span class="tich-caption" style="color:#b45309;">*</span>@endif</label>
                    <input type="text" id="surname" name="surname" class="tich-input @error('surname') tich-input--error @enderror" value="{{ old('surname', strcasecmp((string) $staff->surname, 'Invitee') === 0 ? '' : $staff->surname) }}" @required($mustCompleteProfile) autocomplete="family-name" placeholder="Enter your surname">
                    @error('surname')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="{{ $profileHighlightClass('date_of_birth') }}" id="profile-field-date_of_birth">
                    <label for="date_of_birth" class="tich-label">Date of birth @if ($mustCompleteProfile)<span class="tich-caption" style="color:#b45309;">*</span>@endif</label>
                    @php
                        $dobValue = old('date_of_birth');
                        if ($dobValue === null) {
                            $dob = $staff->date_of_birth;
                            $dobValue = ($dob && $dob->format('Y-m-d') !== '1990-01-01') ? $dob->format('Y-m-d') : '';
                        }
                    @endphp
                    <input type="date" id="date_of_birth" name="date_of_birth" class="tich-input @error('date_of_birth') tich-input--error @enderror" value="{{ $dobValue ?? '' }}" @required($mustCompleteProfile)>
                    @error('date_of_birth')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="{{ $profileHighlightClass('gender') }}" id="profile-field-gender">
                    <label for="gender" class="tich-label">Gender (as per National ID) @if ($mustCompleteProfile)<span class="tich-caption" style="color:#b45309;">*</span>@endif</label>
                    @php
                        $genderValue = old('gender', $staff->gender);
                        if (strcasecmp((string) $genderValue, 'Unspecified') === 0 && old('gender') === null) {
                            $genderValue = '';
                        }
                    @endphp
                    <select id="gender" name="gender" class="tich-input @error('gender') tich-input--error @enderror" @required($mustCompleteProfile)>
                        <option value="">-</option>
                        @foreach (['Male', 'Female', 'Other'] as $gender)
                            <option value="{{ $gender }}" @selected($genderValue === $gender)>{{ $gender }}</option>
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
                <div class="{{ $profileHighlightClass('primary_email') }}" id="profile-field-primary_email">
                    <label for="primary_email" class="tich-label">Personal email @if ($mustCompleteProfile)<span class="tich-caption" style="color:#b45309;">*</span>@endif</label>
                    <input type="email" id="primary_email" name="primary_email" class="tich-input @error('primary_email') tich-input--error @enderror" value="{{ old('primary_email', $staff->primary_email) }}" @required($mustCompleteProfile)>
                    @error('primary_email')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="{{ $profileHighlightClass('phone_number') }}" id="profile-field-phone_number">
                    <label for="phone_number" class="tich-label">Phone number @if ($mustCompleteProfile)<span class="tich-caption" style="color:#b45309;">*</span>@endif</label>
                    <input type="text" id="phone_number" name="phone_number" class="tich-input @error('phone_number') tich-input--error @enderror" value="{{ old('phone_number', in_array($staff->phone_number, ['0700000000', '0000000000'], true) ? '' : $staff->phone_number) }}" @required($mustCompleteProfile) placeholder="e.g. 07XXXXXXXX">
                    @error('phone_number')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="{{ $profileHighlightClass('alt_phone_number') }}" id="profile-field-alt_phone_number">
                    <label for="alt_phone_number" class="tich-label">Alternative phone</label>
                    <input type="text" id="alt_phone_number" name="alt_phone_number" class="tich-input" value="{{ old('alt_phone_number', $staff->alt_phone_number) }}">
                </div>
                <div class="{{ $profileHighlightClass('marital_status') }}" id="profile-field-marital_status">
                    <label for="marital_status" class="tich-label">Marital status @if ($mustCompleteProfile)<span class="tich-caption" style="color:#b45309;">*</span>@endif</label>
                    <select id="marital_status" name="marital_status" class="tich-input @error('marital_status') tich-input--error @enderror" @required($mustCompleteProfile)>
                        <option value="">-</option>
                        @foreach (['Single', 'Married', 'Divorced', 'Widowed', 'Separated'] as $status)
                            <option value="{{ $status }}" @selected(old('marital_status', $staff->marital_status) === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                    @error('marital_status')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="{{ $profileHighlightClass('physical_address') }}" id="profile-field-physical_address" style="grid-column:1/-1;">
                    <label for="physical_address" class="tich-label">Physical address @if ($mustCompleteProfile)<span class="tich-caption" style="color:#b45309;">*</span>@endif</label>
                    <textarea id="physical_address" name="physical_address" rows="2" class="tich-input @error('physical_address') tich-input--error @enderror" @required($mustCompleteProfile)>{{ old('physical_address', $staff->physical_address) }}</textarea>
                    @error('physical_address')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="{{ $profileHighlightClass('postal_address') }}" id="profile-field-postal_address">
                    <label for="postal_address" class="tich-label">Postal address</label>
                    <input type="text" id="postal_address" name="postal_address" class="tich-input" value="{{ old('postal_address', $staff->postal_address) }}">
                </div>
                <div class="{{ $profileHighlightClass('postal_code') }}" id="profile-field-postal_code">
                    <label for="postal_code" class="tich-label">Postal code</label>
                    <input type="text" id="postal_code" name="postal_code" class="tich-input" value="{{ old('postal_code', $staff->postal_code) }}">
                </div>
                <div class="{{ $profileHighlightClass('home_county') }}" id="profile-field-home_county">
                    <label for="home_county" class="tich-label">Home county</label>
                    <input type="text" id="home_county" name="home_county" class="tich-input" value="{{ old('home_county', $staff->home_county) }}">
                </div>
                <div class="{{ $profileHighlightClass('emergency_contact_name') }}" id="profile-field-emergency_contact_name">
                    <label for="emergency_contact_name" class="tich-label">Emergency contact name @if ($mustCompleteProfile)<span class="tich-caption" style="color:#b45309;">*</span>@endif</label>
                    <input type="text" id="emergency_contact_name" name="emergency_contact_name" class="tich-input @error('emergency_contact_name') tich-input--error @enderror" value="{{ old('emergency_contact_name', $staff->emergency_contact_name) }}" @required($mustCompleteProfile)>
                    @error('emergency_contact_name')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="{{ $profileHighlightClass('emergency_contact_phone') }}" id="profile-field-emergency_contact_phone">
                    <label for="emergency_contact_phone" class="tich-label">Emergency contact phone @if ($mustCompleteProfile)<span class="tich-caption" style="color:#b45309;">*</span>@endif</label>
                    <input type="text" id="emergency_contact_phone" name="emergency_contact_phone" class="tich-input @error('emergency_contact_phone') tich-input--error @enderror" value="{{ old('emergency_contact_phone', $staff->emergency_contact_phone) }}" @required($mustCompleteProfile)>
                    @error('emergency_contact_phone')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="{{ $profileHighlightClass('emergency_contact_relationship') }}" id="profile-field-emergency_contact_relationship">
                    <label for="emergency_contact_relationship" class="tich-label">Emergency contact relationship @if ($mustCompleteProfile)<span class="tich-caption" style="color:#b45309;">*</span>@endif</label>
                    <input type="text" id="emergency_contact_relationship" name="emergency_contact_relationship" class="tich-input @error('emergency_contact_relationship') tich-input--error @enderror" value="{{ old('emergency_contact_relationship', $staff->emergency_contact_relationship) }}" @required($mustCompleteProfile)>
                    @error('emergency_contact_relationship')<p class="tich-form-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </article>

        @unless ($mustCompleteProfile)
            <article class="tich-card tich-mt-8 {{ $profileHighlightClass('qualification') }}" id="profile-field-qualification">
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

    <div class="tich-modal tich-photo-crop-modal" id="photo-crop-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="photo-crop-title">
        <div class="tich-modal__backdrop" data-close-crop></div>
        <div class="tich-modal__dialog tich-photo-crop-modal__dialog">
            <div class="tich-modal__header tich-photo-crop-modal__header">
                <div>
                    <h2 class="tich-h3" id="photo-crop-title">Crop profile photo</h2>
                    <p class="tich-caption tich-mt-2">Drag to reposition. Your headshot will be saved as a square image.</p>
                </div>
                <button type="button" class="tich-modal__close" data-close-crop aria-label="Close crop dialog">&times;</button>
            </div>
            <div class="tich-modal__body tich-photo-crop-modal__body">
                <div class="tich-photo-crop-modal__stage">
                    <img id="photo-crop-source" alt="Photo to crop">
                </div>
                <p class="tich-caption tich-photo-crop-modal__hint">Tip: centre your face in the frame for the best result on ID cards and your profile.</p>
            </div>
            <div class="tich-modal__footer tich-photo-crop-modal__footer">
                <button type="button" class="tich-btn tich-btn-ghost" data-close-crop>Cancel</button>
                <button type="button" class="tich-btn tich-btn-primary" id="photo-crop-apply">Use this photo</button>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <x-asset.script path="js/tich-employee-profile-photo.js" />
    @if (! empty($highlightFields))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var highlighted = document.querySelector('.tich-profile-field--highlighted');
                if (highlighted) {
                    highlighted.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        </script>
    @endif
@endsection
