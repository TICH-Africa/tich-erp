@extends('layouts.employee')

@section('employee-content')
    @php
        $mustCompleteProfile = $mustCompleteProfile ?? false;
        $highlightFields = $highlightFields ?? [];
        $profileFieldHighlighted = fn (string $field): bool => in_array($field, $highlightFields, true);
        $profileHighlightClass = fn (string $field): string => $profileFieldHighlighted($field) ? 'tich-profile-field--highlighted' : '';
        $reqMark = '<span class="uf-req">*</span>';
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

    <div class="uf-form">
        <form method="POST" action="{{ route('employee.profile.update') }}" enctype="multipart/form-data" id="employee-profile-form" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">
                        {{ $staff->staff_number ?? 'EMP' }} · {{ $mustCompleteProfile ? 'Profile setup' : 'Profile update' }}
                    </div>
                    <div class="uf-amount-bar__sum">{{ $staff->fullName() }}</div>
                </div>
                <span class="uf-badge">
                    {{ $mustCompleteProfile ? 'Action required' : 'HR review' }}
                </span>
            </div>

            <div class="uf-form-section {{ $profileHighlightClass('photo') }}" id="profile-field-photo">
                <div class="uf-section-head">Profile Photo</div>
                <div class="uf-section-body">
                    <p class="uf-hint">
                        @if ($mustCompleteProfile)
                            Optional for now. You can add a square (1:1) headshot; later photo changes go to HR for approval.
                        @else
                            Upload a square (1:1) headshot. HR must approve before it appears on your profile.
                        @endif
                    </p>
                    <div class="tich-employee-profile-photo">
                        <div class="tich-employee-profile-photo__preview" id="photo-preview-wrap">
                            @if ($staff->photoUrl())
                                <img src="{{ $staff->photoUrl() }}" alt="{{ $staff->fullName() }}" id="photo-preview" class="tich-employee-profile-photo__img">
                            @else
                                <div class="tich-employee-profile-photo__placeholder" id="photo-preview">{{ $staff->initials() }}</div>
                            @endif
                        </div>
                        <div class="tich-employee-profile-photo__actions">
                            <label for="photo_input" class="uf-btn uf-btn-secondary">Choose photo</label>
                            <input type="file" id="photo_input" accept="image/jpeg,image/png,image/webp" hidden>
                            <input type="file" name="profile_photo" id="profile_photo_file" accept="image/jpeg,image/png,image/webp" hidden>
                            <input type="hidden" name="profile_photo_ready" id="profile_photo_ready" value="">
                            <span class="uf-hint">JPG or PNG, max 5 MB. Crop to a square before you save.</span>
                            @unless ($mustCompleteProfile)
                                <span class="uf-hint">After you submit, HR must approve the photo before it appears on your profile.</span>
                            @endunless
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Legal Name</div>
                <div class="uf-section-body">
                    <p class="uf-hint">
                        @if ($mustCompleteProfile)
                            Invited accounts may show a temporary name from your email address. Enter your full legal name as it should appear on HR records.
                        @else
                            Name changes are reviewed by HR before they take effect.
                        @endif
                    </p>
                    <div class="uf-form-grid-2">
                        <div class="uf-field {{ $profileHighlightClass('first_name') }}" id="profile-field-first_name">
                            <label for="first_name">First name (as per National ID) @if ($mustCompleteProfile){!! $reqMark !!}@endif</label>
                            <input type="text" id="first_name" name="first_name" class="{{ $errors->has('first_name') ? 'is-invalid' : '' }}" value="{{ old('first_name', strcasecmp((string) $staff->first_name, 'Pending') === 0 ? '' : $staff->first_name) }}" @required($mustCompleteProfile) autocomplete="given-name" placeholder="Enter your first name">
                            @error('first_name')<span class="uf-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="uf-field {{ $profileHighlightClass('middle_name') }}" id="profile-field-middle_name">
                            <label for="middle_name">Middle name</label>
                            <input type="text" id="middle_name" name="middle_name" class="{{ $errors->has('middle_name') ? 'is-invalid' : '' }}" value="{{ old('middle_name', $staff->middle_name) }}" autocomplete="additional-name">
                            @error('middle_name')<span class="uf-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="uf-field {{ $profileHighlightClass('surname') }}" id="profile-field-surname">
                            <label for="surname">Surname (as per National ID) @if ($mustCompleteProfile){!! $reqMark !!}@endif</label>
                            <input type="text" id="surname" name="surname" class="{{ $errors->has('surname') ? 'is-invalid' : '' }}" value="{{ old('surname', strcasecmp((string) $staff->surname, 'Invitee') === 0 ? '' : $staff->surname) }}" @required($mustCompleteProfile) autocomplete="family-name" placeholder="Enter your surname">
                            @error('surname')<span class="uf-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="uf-field {{ $profileHighlightClass('date_of_birth') }}" id="profile-field-date_of_birth">
                            <label for="date_of_birth">Date of birth @if ($mustCompleteProfile){!! $reqMark !!}@endif</label>
                            @php
                                $dobValue = old('date_of_birth');
                                if ($dobValue === null) {
                                    $dob = $staff->date_of_birth;
                                    $dobValue = ($dob && $dob->format('Y-m-d') !== '1990-01-01') ? $dob->format('Y-m-d') : '';
                                }
                            @endphp
                            <input type="date" id="date_of_birth" name="date_of_birth" class="{{ $errors->has('date_of_birth') ? 'is-invalid' : '' }}" value="{{ $dobValue ?? '' }}" @required($mustCompleteProfile)>
                            @error('date_of_birth')<span class="uf-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="uf-field {{ $profileHighlightClass('gender') }}" id="profile-field-gender">
                            <label for="gender">Gender at birth @if ($mustCompleteProfile){!! $reqMark !!}@endif</label>
                            @php
                                $genderValue = old('gender', $staff->gender);
                                if (old('gender') === null && in_array(strtolower((string) $genderValue), ['unspecified', 'other', ''], true)) {
                                    $genderValue = '';
                                }
                            @endphp
                            <select id="gender" name="gender" class="{{ $errors->has('gender') ? 'is-invalid' : '' }}" @required($mustCompleteProfile)>
                                <option value="">-</option>
                                @foreach (['Male', 'Female'] as $gender)
                                    <option value="{{ $gender }}" @selected($genderValue === $gender)>{{ $gender }}</option>
                                @endforeach
                            </select>
                            @error('gender')<span class="uf-error">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Contact &amp; Personal Details</div>
                <div class="uf-section-body">
                    <p class="uf-hint">Employment, payroll, and department assignment are managed by HR.</p>
                    <div class="uf-form-grid-2">
                        <div class="uf-field {{ $profileHighlightClass('primary_email') }}" id="profile-field-primary_email">
                            <label for="primary_email">Personal email @if ($mustCompleteProfile){!! $reqMark !!}@endif</label>
                            <input type="email" id="primary_email" name="primary_email" class="{{ $errors->has('primary_email') ? 'is-invalid' : '' }}" value="{{ old('primary_email', $staff->primary_email) }}" @required($mustCompleteProfile)>
                            @error('primary_email')<span class="uf-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="uf-field {{ $profileHighlightClass('phone_number') }}" id="profile-field-phone_number">
                            <label for="phone_number">Phone number @if ($mustCompleteProfile){!! $reqMark !!}@endif</label>
                            <input type="text" id="phone_number" name="phone_number" class="{{ $errors->has('phone_number') ? 'is-invalid' : '' }}" value="{{ old('phone_number', in_array($staff->phone_number, ['0700000000', '0000000000'], true) ? '' : $staff->phone_number) }}" @required($mustCompleteProfile) placeholder="e.g. 07XXXXXXXX">
                            @error('phone_number')<span class="uf-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="uf-field {{ $profileHighlightClass('alt_phone_number') }}" id="profile-field-alt_phone_number">
                            <label for="alt_phone_number">Alternative phone</label>
                            <input type="text" id="alt_phone_number" name="alt_phone_number" value="{{ old('alt_phone_number', $staff->alt_phone_number) }}">
                        </div>
                        <div class="uf-field {{ $profileHighlightClass('marital_status') }}" id="profile-field-marital_status">
                            <label for="marital_status">Marital status @if ($mustCompleteProfile){!! $reqMark !!}@endif</label>
                            <select id="marital_status" name="marital_status" class="{{ $errors->has('marital_status') ? 'is-invalid' : '' }}" @required($mustCompleteProfile)>
                                <option value="">-</option>
                                @foreach (['Single', 'Married', 'Divorced', 'Widowed', 'Separated'] as $status)
                                    <option value="{{ $status }}" @selected(old('marital_status', $staff->marital_status) === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                            @error('marital_status')<span class="uf-error">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="uf-field {{ $profileHighlightClass('physical_address') }}" id="profile-field-physical_address">
                        <label for="physical_address">Physical address @if ($mustCompleteProfile){!! $reqMark !!}@endif</label>
                        <textarea id="physical_address" name="physical_address" class="{{ $errors->has('physical_address') ? 'is-invalid' : '' }}" @required($mustCompleteProfile)>{{ old('physical_address', $staff->physical_address) }}</textarea>
                        @error('physical_address')<span class="uf-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="uf-form-grid-2">
                        <div class="uf-field {{ $profileHighlightClass('postal_address') }}" id="profile-field-postal_address">
                            <label for="postal_address">Postal address</label>
                            <input type="text" id="postal_address" name="postal_address" value="{{ old('postal_address', $staff->postal_address) }}">
                        </div>
                        <div class="uf-field {{ $profileHighlightClass('postal_code') }}" id="profile-field-postal_code">
                            <label for="postal_code">Postal code</label>
                            <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $staff->postal_code) }}">
                        </div>
                        <div class="uf-field {{ $profileHighlightClass('home_county') }}" id="profile-field-home_county">
                            <label for="home_county">Home county</label>
                            <input type="text" id="home_county" name="home_county" value="{{ old('home_county', $staff->home_county) }}">
                        </div>
                        <div class="uf-field {{ $profileHighlightClass('emergency_contact_name') }}" id="profile-field-emergency_contact_name">
                            <label for="emergency_contact_name">Emergency contact name @if ($mustCompleteProfile){!! $reqMark !!}@endif</label>
                            <input type="text" id="emergency_contact_name" name="emergency_contact_name" class="{{ $errors->has('emergency_contact_name') ? 'is-invalid' : '' }}" value="{{ old('emergency_contact_name', $staff->emergency_contact_name) }}" @required($mustCompleteProfile)>
                            @error('emergency_contact_name')<span class="uf-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="uf-field {{ $profileHighlightClass('emergency_contact_phone') }}" id="profile-field-emergency_contact_phone">
                            <label for="emergency_contact_phone">Emergency contact phone @if ($mustCompleteProfile){!! $reqMark !!}@endif</label>
                            <input type="text" id="emergency_contact_phone" name="emergency_contact_phone" class="{{ $errors->has('emergency_contact_phone') ? 'is-invalid' : '' }}" value="{{ old('emergency_contact_phone', $staff->emergency_contact_phone) }}" @required($mustCompleteProfile)>
                            @error('emergency_contact_phone')<span class="uf-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="uf-field {{ $profileHighlightClass('emergency_contact_relationship') }}" id="profile-field-emergency_contact_relationship">
                            <label for="emergency_contact_relationship">Emergency contact relationship @if ($mustCompleteProfile){!! $reqMark !!}@endif</label>
                            <input type="text" id="emergency_contact_relationship" name="emergency_contact_relationship" class="{{ $errors->has('emergency_contact_relationship') ? 'is-invalid' : '' }}" value="{{ old('emergency_contact_relationship', $staff->emergency_contact_relationship) }}" @required($mustCompleteProfile)>
                            @error('emergency_contact_relationship')<span class="uf-error">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>
            </div>

            @unless ($mustCompleteProfile)
                <div class="uf-form-section {{ $profileHighlightClass('qualification') }}" id="profile-field-qualification">
                    <div class="uf-section-head">Qualification / Certificate</div>
                    <div class="uf-section-body">
                        <p class="uf-hint">Optional. New qualifications require HR verification before they appear on your record.</p>
                        <div class="uf-form-grid-2">
                            <div class="uf-field">
                                <label for="qualification_type">Qualification type</label>
                                <select id="qualification_type" name="qualification_type">
                                    <option value="">- Skip -</option>
                                    @foreach ($qualificationTypes as $value => $label)
                                        <option value="{{ $value }}" @selected(old('qualification_type') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="uf-field">
                                <label for="qualification_name">Qualification name</label>
                                <input type="text" id="qualification_name" name="qualification_name" class="{{ $errors->has('qualification_name') ? 'is-invalid' : '' }}" value="{{ old('qualification_name') }}" placeholder="e.g. BSc Community Health">
                                @error('qualification_name')<span class="uf-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="uf-field">
                                <label for="institution">Institution</label>
                                <input type="text" id="institution" name="institution" value="{{ old('institution') }}">
                            </div>
                            <div class="uf-field">
                                <label for="year_completed">Year completed</label>
                                <input type="number" id="year_completed" name="year_completed" min="1950" max="{{ now()->year + 1 }}" value="{{ old('year_completed') }}">
                            </div>
                            <div class="uf-field">
                                <label for="grade_or_class">Grade / class</label>
                                <input type="text" id="grade_or_class" name="grade_or_class" value="{{ old('grade_or_class') }}">
                            </div>
                            <div class="uf-field">
                                <label for="certificate_number">Certificate number</label>
                                <input type="text" id="certificate_number" name="certificate_number" value="{{ old('certificate_number') }}">
                            </div>
                        </div>
                        <div class="uf-field">
                            <label for="certificate_file">Certificate file</label>
                            <input type="file" id="certificate_file" name="certificate_file" accept=".pdf,.jpg,.jpeg,.png">
                            <span class="uf-hint">PDF or image, max 5 MB</span>
                        </div>
                    </div>
                </div>

                <div class="uf-form-section">
                    <div class="uf-section-head">Notes for HR</div>
                    <div class="uf-section-body">
                        <div class="uf-field">
                            <label for="employee_notes">Notes (optional)</label>
                            <textarea id="employee_notes" name="employee_notes" placeholder="Explain any changes if helpful for the reviewer">{{ old('employee_notes') }}</textarea>
                        </div>
                        <div class="uf-form-actions">
                            <button type="submit" class="uf-btn uf-btn-primary">Submit for HR approval</button>
                            <a href="{{ route('employee.dashboard') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                        </div>
                    </div>
                </div>
            @else
                <div class="uf-form-section">
                    <div class="uf-section-head">Confirm &amp; Continue</div>
                    <div class="uf-section-body">
                        <div class="uf-form-actions">
                            <button type="submit" class="uf-btn uf-btn-primary">Save and continue</button>
                        </div>
                    </div>
                </div>
            @endunless

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>

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
