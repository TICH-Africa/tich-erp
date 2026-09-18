@extends('layouts.portal')

@section('portal-content')
    @php
        $applicant = $student->applicant;
        $photoUrl = $biodata['identity']['photo_url'] ?? $student->photoUrl();
    @endphp

    <x-page-toolbar title="Update profile" meta="{{ $student->registration_number }}">
        <x-slot:actions>
            <a href="{{ route('portal.dashboard', ['section' => 'profile']) }}" class="tich-btn tich-btn-ghost">Back to profile</a>
        </x-slot:actions>
    </x-page-toolbar>

    <p class="tich-caption tich-mt-2">
        Contact and next-of-kin details save immediately. Name, national ID / passport, date of birth, and photo changes are queued for Academic Registrar approval.
    </p>

    @error('profile')
        <div class="tich-alert tich-alert--danger tich-mt-4">{{ $message }}</div>
    @enderror

    <div class="tich-mt-6" style="display:flex; gap:1rem; align-items:center; margin-bottom:0;">
        <div style="width:4.5rem; height:4.5rem; border-radius:0.75rem; overflow:hidden; background:var(--tich-neutral-100, #f1f5f9); flex-shrink:0; display:flex; align-items:center; justify-content:center;">
            @if ($photoUrl)
                <img src="{{ $photoUrl }}" alt="Student photo" style="width:100%; height:100%; object-fit:cover;">
            @else
                <span class="tich-caption">{{ $student->initials() }}</span>
            @endif
        </div>
        <div>
            <strong>{{ $biodata['identity']['full_name'] ?? $student->registration_number }}</strong>
            <p class="tich-caption" style="margin:0.25rem 0 0;">{{ $biodata['academic']['program'] ?? '' }}</p>
        </div>
    </div>

    <div class="uf-form">
        <form method="POST" action="{{ route('portal.profile.update') }}" enctype="multipart/form-data" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">{{ $student->registration_number }} · Student profile</div>
                    <div class="uf-amount-bar__sum">{{ $biodata['identity']['full_name'] ?? $student->registration_number }}</div>
                </div>
                <span class="uf-badge">Update</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Self-service contact</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="phone_number">Phone</label>
                            <input id="phone_number" name="phone_number" type="text" value="{{ old('phone_number', $applicant?->phone_number) }}">
                        </div>
                        <div class="uf-field">
                            <label for="email">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $applicant?->email) }}">
                        </div>
                        <div class="uf-field">
                            <label for="home_county">Home county</label>
                            <input id="home_county" name="home_county" type="text" value="{{ old('home_county', $applicant?->home_county) }}">
                        </div>
                        <div class="uf-field">
                            <label for="nationality">Nationality</label>
                            <input id="nationality" name="nationality" type="text" value="{{ old('nationality', $applicant?->nationality) }}">
                        </div>
                        <div class="uf-field" style="grid-column: 1 / -1;">
                            <label for="postal_address">Postal address</label>
                            <input id="postal_address" name="postal_address" type="text" value="{{ old('postal_address', $applicant?->postal_address) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Next of kin &amp; emergency</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="next_of_kin_name">Next of kin name</label>
                            <input id="next_of_kin_name" name="next_of_kin_name" type="text" value="{{ old('next_of_kin_name', $applicant?->next_of_kin_name) }}">
                        </div>
                        <div class="uf-field">
                            <label for="next_of_kin_relationship">Relationship</label>
                            <input id="next_of_kin_relationship" name="next_of_kin_relationship" type="text" value="{{ old('next_of_kin_relationship', $applicant?->next_of_kin_relationship) }}">
                        </div>
                        <div class="uf-field">
                            <label for="next_of_kin_phone">Next of kin phone</label>
                            <input id="next_of_kin_phone" name="next_of_kin_phone" type="text" value="{{ old('next_of_kin_phone', $applicant?->next_of_kin_phone) }}">
                        </div>
                        <div class="uf-field">
                            <label for="next_of_kin_address">Next of kin address</label>
                            <input id="next_of_kin_address" name="next_of_kin_address" type="text" value="{{ old('next_of_kin_address', $applicant?->next_of_kin_address) }}">
                        </div>
                        <div class="uf-field">
                            <label for="emergency_contact_name">Emergency contact name</label>
                            <input id="emergency_contact_name" name="emergency_contact_name" type="text" value="{{ old('emergency_contact_name', $student->emergency_contact_name) }}">
                        </div>
                        <div class="uf-field">
                            <label for="emergency_contact_phone">Emergency phone</label>
                            <input id="emergency_contact_phone" name="emergency_contact_phone" type="text" value="{{ old('emergency_contact_phone', $student->emergency_contact_phone) }}">
                        </div>
                        <div class="uf-field">
                            <label for="emergency_contact_relationship">Emergency relationship</label>
                            <input id="emergency_contact_relationship" name="emergency_contact_relationship" type="text" value="{{ old('emergency_contact_relationship', $student->emergency_contact_relationship) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Registrar approval required</div>
                <div class="uf-section-body">
                    <p class="uf-hint">Changes to these fields are queued for Academic Registrar approval before they take effect.</p>
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="first_name">First name</label>
                            <input id="first_name" name="first_name" type="text" value="{{ old('first_name', $applicant?->first_name) }}">
                        </div>
                        <div class="uf-field">
                            <label for="middle_name">Middle name</label>
                            <input id="middle_name" name="middle_name" type="text" value="{{ old('middle_name', $applicant?->middle_name) }}">
                        </div>
                        <div class="uf-field">
                            <label for="surname">Surname</label>
                            <input id="surname" name="surname" type="text" value="{{ old('surname', $applicant?->surname) }}">
                        </div>
                        <div class="uf-field">
                            <label for="date_of_birth">Date of birth</label>
                            <input id="date_of_birth" name="date_of_birth" type="date" value="{{ old('date_of_birth', optional($applicant?->date_of_birth)->format('Y-m-d')) }}">
                        </div>
                        <div class="uf-field">
                            <label for="national_id_number">National ID</label>
                            <input id="national_id_number" name="national_id_number" type="text" value="{{ old('national_id_number', $applicant?->national_id_number) }}">
                        </div>
                        <div class="uf-field">
                            <label for="passport_number">Passport number</label>
                            <input id="passport_number" name="passport_number" type="text" value="{{ old('passport_number', $applicant?->passport_number) }}">
                        </div>
                        <div class="uf-field">
                            <label for="profile_photo">New photo (pending approval)</label>
                            <input id="profile_photo" name="profile_photo" type="file" accept="image/*">
                        </div>
                        <div class="uf-field">
                            <label for="student_notes">Notes for registrar</label>
                            <textarea id="student_notes" name="student_notes" rows="3">{{ old('student_notes') }}</textarea>
                        </div>
                    </div>
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Save profile</button>
                        <a href="{{ route('portal.dashboard', ['section' => 'profile']) }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
