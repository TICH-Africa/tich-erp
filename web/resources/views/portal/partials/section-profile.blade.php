@php
    $applicant = $student->applicant;
    $photoUrl = $biodata['identity']['photo_url'] ?? $student->photoUrl();
    $profileChangeRequests = $profileChangeRequests ?? collect();
@endphp

<x-page-toolbar title="My profile" meta="{{ $student->registration_number }} · {{ $biodata['academic']['program'] ?? '' }}" />

<div class="tich-grid tich-grid--2 tich-mt-8" style="align-items: start; gap: 1.5rem;">
    <article class="tich-card">
        <h2 class="tich-h3">Identity</h2>
        <div class="tich-mt-4" style="display:flex; gap:1rem; align-items:flex-start;">
            <div style="width:5.5rem; height:5.5rem; border-radius:0.75rem; overflow:hidden; background:var(--tich-neutral-100, #f1f5f9); flex-shrink:0; display:flex; align-items:center; justify-content:center;">
                @if ($photoUrl)
                    <img src="{{ $photoUrl }}" alt="Student photo" style="width:100%; height:100%; object-fit:cover;">
                @else
                    <span class="tich-caption">{{ $student->initials() }}</span>
                @endif
            </div>
            <dl style="display: grid; grid-template-columns: 8rem 1fr; gap: 0.4rem 0.75rem; margin: 0; flex:1;">
                <dt class="tich-caption">Full name</dt>
                <dd>{{ $biodata['identity']['full_name'] ?? '-' }}</dd>
                <dt class="tich-caption">Date of birth</dt>
                <dd>{{ $biodata['identity']['date_of_birth'] ?? '-' }}</dd>
                <dt class="tich-caption">Gender</dt>
                <dd>{{ $biodata['identity']['gender'] ?? '-' }}</dd>
                <dt class="tich-caption">Nationality</dt>
                <dd>{{ $biodata['identity']['nationality'] ?? $biodata['contact']['nationality'] ?? '-' }}</dd>
                <dt class="tich-caption">National ID</dt>
                <dd>{{ $biodata['identity']['national_id_number'] ?? '-' }}</dd>
                <dt class="tich-caption">Passport</dt>
                <dd>{{ $biodata['identity']['passport_number'] ?? '-' }}</dd>
            </dl>
        </div>
        <p class="tich-caption tich-mt-4">Name, national ID / passport, and date of birth changes require Academic Registrar approval.</p>
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Academic profile</h2>
        <dl style="display: grid; grid-template-columns: 9rem 1fr; gap: 0.5rem 1rem; margin: 1rem 0 0;">
            <dt class="tich-caption">Registration no.</dt>
            <dd>{{ $biodata['academic']['registration_number'] ?? '-' }}</dd>
            <dt class="tich-caption">Programme</dt>
            <dd>{{ $biodata['academic']['program'] ?? '-' }}</dd>
            <dt class="tich-caption">Department</dt>
            <dd>{{ $biodata['academic']['department'] ?? '-' }}</dd>
            <dt class="tich-caption">Year of study</dt>
            <dd>{{ $biodata['academic']['year_of_study'] ?? '-' }}</dd>
            <dt class="tich-caption">Current semester</dt>
            <dd>{{ $biodata['academic']['current_semester'] ?? '-' }}</dd>
            <dt class="tich-caption">Cohort / intake</dt>
            <dd>{{ $biodata['academic']['cohort_intake'] ?? '-' }}</dd>
        </dl>
    </article>

    <article class="tich-card" style="grid-column: 1 / -1;">
        <h2 class="tich-h3">Update profile</h2>
        <p class="tich-caption tich-mt-2">Contact and next-of-kin details save immediately. Restricted identity fields are queued for registrar approval.</p>

        <form method="POST" action="{{ route('portal.profile.update') }}" enctype="multipart/form-data" class="tich-mt-4">
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

            <div class="tich-mt-4">
                <button type="submit" class="tich-btn tich-btn-primary">Save profile</button>
            </div>
        </form>
    </article>
</div>

@if ($profileChangeRequests->isNotEmpty())
    <section class="tich-portal-panel tich-mt-8">
        <div class="tich-portal-panel__head">
            <h2 class="tich-h3">Pending / recent change requests</h2>
        </div>
        <div class="tich-card tich-table-panel tich-mt-4">
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($profileChangeRequests as $req)
                            <tr>
                                <td>{{ ucfirst(str_replace('_', ' ', $req->request_type)) }}</td>
                                <td>{{ ucfirst($req->status) }}</td>
                                <td>{{ $req->created_at?->format('d M Y H:i') }}</td>
                                <td class="tich-caption">
                                    {{ $req->rejection_reason ?: ($req->reviewer_notes ?: ($req->student_notes ?: '-')) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endif
