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

    <article class="tich-card tich-mt-6">
        <div class="tich-mt-0" style="display:flex; gap:1rem; align-items:center; margin-bottom:1.25rem;">
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

        <form method="POST" action="{{ route('portal.profile.update') }}" enctype="multipart/form-data">
            @csrf

            <div class="tich-grid tich-grid--2" style="gap: 1rem; align-items: start;">
                <div>
                    <h3 class="tich-caption" style="margin-bottom:0.75rem;">Self-service contact</h3>
                    <div class="tich-form-stack">
                        <div>
                            <label for="phone_number" class="tich-label">Phone</label>
                            <input id="phone_number" name="phone_number" type="text" class="tich-input" value="{{ old('phone_number', $applicant?->phone_number) }}">
                        </div>
                        <div>
                            <label for="email" class="tich-label">Email</label>
                            <input id="email" name="email" type="email" class="tich-input" value="{{ old('email', $applicant?->email) }}">
                        </div>
                        <div>
                            <label for="home_county" class="tich-label">Home county</label>
                            <input id="home_county" name="home_county" type="text" class="tich-input" value="{{ old('home_county', $applicant?->home_county) }}">
                        </div>
                        <div>
                            <label for="nationality" class="tich-label">Nationality</label>
                            <input id="nationality" name="nationality" type="text" class="tich-input" value="{{ old('nationality', $applicant?->nationality) }}">
                        </div>
                        <div>
                            <label for="postal_address" class="tich-label">Postal address</label>
                            <input id="postal_address" name="postal_address" type="text" class="tich-input" value="{{ old('postal_address', $applicant?->postal_address) }}">
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="tich-caption" style="margin-bottom:0.75rem;">Next of kin &amp; emergency</h3>
                    <div class="tich-form-stack">
                        <div>
                            <label for="next_of_kin_name" class="tich-label">Next of kin name</label>
                            <input id="next_of_kin_name" name="next_of_kin_name" type="text" class="tich-input" value="{{ old('next_of_kin_name', $applicant?->next_of_kin_name) }}">
                        </div>
                        <div>
                            <label for="next_of_kin_relationship" class="tich-label">Relationship</label>
                            <input id="next_of_kin_relationship" name="next_of_kin_relationship" type="text" class="tich-input" value="{{ old('next_of_kin_relationship', $applicant?->next_of_kin_relationship) }}">
                        </div>
                        <div>
                            <label for="next_of_kin_phone" class="tich-label">Next of kin phone</label>
                            <input id="next_of_kin_phone" name="next_of_kin_phone" type="text" class="tich-input" value="{{ old('next_of_kin_phone', $applicant?->next_of_kin_phone) }}">
                        </div>
                        <div>
                            <label for="next_of_kin_address" class="tich-label">Next of kin address</label>
                            <input id="next_of_kin_address" name="next_of_kin_address" type="text" class="tich-input" value="{{ old('next_of_kin_address', $applicant?->next_of_kin_address) }}">
                        </div>
                        <div>
                            <label for="emergency_contact_name" class="tich-label">Emergency contact name</label>
                            <input id="emergency_contact_name" name="emergency_contact_name" type="text" class="tich-input" value="{{ old('emergency_contact_name', $student->emergency_contact_name) }}">
                        </div>
                        <div>
                            <label for="emergency_contact_phone" class="tich-label">Emergency phone</label>
                            <input id="emergency_contact_phone" name="emergency_contact_phone" type="text" class="tich-input" value="{{ old('emergency_contact_phone', $student->emergency_contact_phone) }}">
                        </div>
                        <div>
                            <label for="emergency_contact_relationship" class="tich-label">Emergency relationship</label>
                            <input id="emergency_contact_relationship" name="emergency_contact_relationship" type="text" class="tich-input" value="{{ old('emergency_contact_relationship', $student->emergency_contact_relationship) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="tich-mt-6" style="padding-top:1rem; border-top:1px solid var(--tich-neutral-border, #e2e8f0);">
                <h3 class="tich-caption" style="margin-bottom:0.75rem;">Registrar approval required</h3>
                <div class="tich-grid tich-grid--2" style="gap:1rem;">
                    <div>
                        <label for="first_name" class="tich-label">First name</label>
                        <input id="first_name" name="first_name" type="text" class="tich-input" value="{{ old('first_name', $applicant?->first_name) }}">
                    </div>
                    <div>
                        <label for="middle_name" class="tich-label">Middle name</label>
                        <input id="middle_name" name="middle_name" type="text" class="tich-input" value="{{ old('middle_name', $applicant?->middle_name) }}">
                    </div>
                    <div>
                        <label for="surname" class="tich-label">Surname</label>
                        <input id="surname" name="surname" type="text" class="tich-input" value="{{ old('surname', $applicant?->surname) }}">
                    </div>
                    <div>
                        <label for="date_of_birth" class="tich-label">Date of birth</label>
                        <input id="date_of_birth" name="date_of_birth" type="date" class="tich-input" value="{{ old('date_of_birth', optional($applicant?->date_of_birth)->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label for="national_id_number" class="tich-label">National ID</label>
                        <input id="national_id_number" name="national_id_number" type="text" class="tich-input" value="{{ old('national_id_number', $applicant?->national_id_number) }}">
                    </div>
                    <div>
                        <label for="passport_number" class="tich-label">Passport number</label>
                        <input id="passport_number" name="passport_number" type="text" class="tich-input" value="{{ old('passport_number', $applicant?->passport_number) }}">
                    </div>
                    <div>
                        <label for="profile_photo" class="tich-label">New photo (pending approval)</label>
                        <input id="profile_photo" name="profile_photo" type="file" class="tich-input" accept="image/*">
                    </div>
                    <div>
                        <label for="student_notes" class="tich-label">Notes for registrar</label>
                        <textarea id="student_notes" name="student_notes" rows="3" class="tich-input">{{ old('student_notes') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="tich-mt-4" style="display:flex; gap:0.75rem; flex-wrap:wrap;">
                <button type="submit" class="tich-btn tich-btn-primary">Save profile</button>
                <a href="{{ route('portal.dashboard', ['section' => 'profile']) }}" class="tich-btn tich-btn-secondary">Cancel</a>
            </div>
        </form>
    </article>
@endsection
