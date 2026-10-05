@extends('layouts.administration')

@section('title', ($student ?? null) ? 'Edit Student' : 'Existing Student Registration')

@section('administration-content')
    @php
        $editing = isset($student) && $student;
        $currentCampusId = ($student ?? null)?->enrollment_campus_id;
        $currentCampus = $currentCampusId ? \App\Models\Campus::find($currentCampusId) : null;
        $editingSelectionType = old(
            'campus_selection_type',
            $currentCampus?->campus_type === 'community_college' ? 'community_college' : ($currentCampus ? 'campus' : 'campus')
        );
    @endphp

    <x-page-toolbar
        :title="$editing ? 'Edit Student ' . $student->registration_number : 'Add Existing Student'"
        :meta="$editing ? 'Correct wrongly entered student details' : 'Register an existing student into a program'"
    >
        @if ($editing)
            <x-slot:actions>
                <a href="{{ route('administration.applications.existing-student.show', $student) }}" class="tich-btn tich-btn-secondary">Back to profile</a>
            </x-slot:actions>
        @endif
    </x-page-toolbar>

    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-6">
            @foreach ($errors->all() as $message)
                <p>{{ $message }}</p>
            @endforeach
        </div>
    @endif

    <div class="tich-card tich-mt-6">
            <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('administration.applications.existing-student.update', $student) : route('administration.applications.existing-student.store') }}">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <h3 class="tich-h3">Student Information</h3>
            <div class="tich-grid tich-grid--3 tich-mt-3">
                <div>
                    <label class="tich-label">Registration Number *</label>
                    <input type="text" name="registration_number" class="tich-input" required placeholder="e.g. S2026-0001" value="{{ old('registration_number', $student->registration_number ?? '') }}">
                    @error('registration_number')<p class="tich-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="tich-label">First Name *</label>
                    <input type="text" name="first_name" class="tich-input" required value="{{ old('first_name', $student->first_name ?? '') }}">
                    @error('first_name')<p class="tich-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="tich-label">Middle Name</label>
                    <input type="text" name="middle_name" class="tich-input" value="{{ old('middle_name', $student->middle_name ?? '') }}">
                    @error('middle_name')<p class="tich-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="tich-label">Surname *</label>
                    <input type="text" name="surname" class="tich-input" required value="{{ old('surname', $student->surname ?? '') }}">
                    @error('surname')<p class="tich-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="tich-label">Email *</label>
                    <input type="email" name="email" class="tich-input" required value="{{ old('email', ($student ?? null)?->user?->email ?? ($student ?? null)?->applicant?->email ?? '') }}">
                    @error('email')<p class="tich-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="tich-label">Phone Number</label>
                    <input type="text" name="phone_number" class="tich-input" value="{{ old('phone_number', $student->user->phone_number ?? '') }}">
                    @error('phone_number')<p class="tich-field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <h3 class="tich-h3 tich-mt-6">Emergency Contact</h3>
            <div class="tich-grid tich-grid--3 tich-mt-3">
                <div>
                    <label class="tich-label">Contact Name</label>
                    <input type="text" name="emergency_contact_name" class="tich-input" value="{{ old('emergency_contact_name', $student->emergency_contact_name ?? '') }}">
                    @error('emergency_contact_name')
                        <p class="tich-field-error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="tich-label">Phone</label>
                    <input type="text" name="emergency_contact_phone" class="tich-input" value="{{ old('emergency_contact_phone', $student->emergency_contact_phone ?? '') }}">
                    @error('emergency_contact_phone')
                        <p class="tich-field-error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="tich-label">Relationship</label>
                    <input type="text" name="emergency_contact_relationship" class="tich-input" value="{{ old('emergency_contact_relationship', $student->emergency_contact_relationship ?? '') }}">
                    @error('emergency_contact_relationship')
                        <p class="tich-field-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <h3 class="tich-h3 tich-mt-6">Profile Photo</h3>
            <div class="tich-grid tich-grid--3 tich-mt-3">
                <div style="grid-column: 1 / -1;">
                    @if ($editing && ($student->photo_path ?? null))
                        <div class="tich-flex tich-flex--middle tich-gap-4 tich-mb-2">
                            <img src="{{ $student->photoUrl() }}" alt="{{ $student->fullName() }}"
                                 style="width:72px; height:72px; object-fit:cover; border-radius:50%; border:1px solid #e2e8f0;">
                            <label class="tich-caption" style="display:flex; align-items:center; gap:0.5rem;">
                                <input type="checkbox" name="photo_remove" value="1"
                                       {{ old('photo_remove') ? 'checked' : '' }}>
                                Remove current photo
                            </label>
                        </div>
                    @endif

                    <label class="tich-label">Photo (jpg, png, webp, max 2 MB)</label>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="tich-input">
                    @error('photo')<p class="tich-field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <h3 class="tich-h3 tich-mt-6">Program Details</h3>
            <div class="tich-grid tich-grid--3 tich-mt-3">
                <div>
                    <label class="tich-label">Programme *</label>
                    <select name="program_id" class="tich-input" required>
                        <option value="">Select programme</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" @selected(old('program_id', $student->program_id ?? null) == $program->id)>{{ $program->program_code }} - {{ $program->program_name }}</option>
                        @endforeach
                    </select>
                    @error('program_id')<p class="tich-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="tich-label">Year Joined *</label>
                    <input type="date" name="year_joined" class="tich-input" required value="{{ old('year_joined', optional(($student ?? null)?->date_of_admission)->format('Y-m-d')) }}">
                    @error('year_joined')<p class="tich-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="tich-label">Current Year *</label>
                    <select name="current_year" class="tich-input" required>
                        <option value="">Select current year</option>
                        <option value="1" @selected(old('current_year', $student->current_year ?? null) == 1)>Year 1</option>
                        <option value="2" @selected(old('current_year', $student->current_year ?? null) == 2)>Year 2</option>
                        <option value="3" @selected(old('current_year', $student->current_year ?? null) == 3)>Year 3</option>
                        <option value="4" @selected(old('current_year', $student->current_year ?? null) == 4)>Year 4</option>
                    </select>
                    @error('current_year')<p class="tich-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="tich-label">Program Type *</label>
                    <select name="program_type" class="tich-input" required>
                        <option value="diploma" @selected(old('program_type', $student->entry_pathway ?? 'diploma') === 'diploma')>Diploma</option>
                        <option value="certificate" @selected(old('program_type', $student->entry_pathway ?? null) === 'certificate')>Certificate</option>
                    </select>
                    @error('program_type')<p class="tich-field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="tich-label">Duration (months)</label>
                    <input type="number" name="duration_months" class="tich-input" value="{{ old('duration_months', '36') }}">
                </div>
                <div>
                    <label class="tich-label">Semester</label>
                    <select name="semester" class="tich-input">
                        <option value="first">First</option>
                        <option value="second">Second</option>
                        <option value="third">Third</option>
                    </select>
                </div>
                <div>
                    <label class="tich-label">Campus type</label>
                    <select id="campus_selection_type" name="campus_selection_type" class="tich-input" required>
                        <option value="">Select type</option>
                        <option value="campus" @selected($editingSelectionType === 'campus')>Campus</option>
                        @if($campusSelectionOptions['community_college_sites']->isNotEmpty())
                            <option value="community_college" @selected($editingSelectionType === 'community_college')>Community College</option>
                        @endif
                        <option value="online" @selected($editingSelectionType === 'online')>Online</option>
                    </select>
                    @error('campus_selection_type')<p class="tich-field-error">{{ $message }}</p>@enderror
                </div>
                <div id="campus-field-campus" @if($editingSelectionType !== 'campus') hidden @endif>
                    <label class="tich-label">Campus</label>
                    <select id="campus_id" name="campus_id" class="tich-input" @if($editingSelectionType !== 'campus') disabled @endif>
                        <option value="">Select campus</option>
                        @foreach ($campusSelectionOptions['campuses'] as $campus)
                            <option value="{{ $campus->id }}" @selected(old('campus_id', $currentCampusId) == $campus->id)>
                                {{ $campus->campus_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('campus_id')<p class="tich-field-error">{{ $message }}</p>@enderror
                </div>
                <div id="campus-field-community" @if($editingSelectionType !== 'community_college') hidden @endif>
                    <label class="tich-label">County</label>
                    <select id="community_college_county" name="community_college_county" class="tich-input" @if($editingSelectionType !== 'community_college') disabled @endif>
                        <option value="">Select county</option>
                        @foreach($campusSelectionOptions['community_college_sites']->keys() as $county)
                            <option value="{{ $county }}" @selected(old('community_college_county', $editingSelectionType === 'community_college' ? $currentCampus?->county : null) == $county)>{{ $county }}</option>
                        @endforeach
                    </select>
                    @error('community_college_county')<p class="tich-field-error">{{ $message }}</p>@enderror
                    <label class="tich-label">Community college site</label>
                    <select id="community_college_site_id" name="community_college_site_id" class="tich-input" @if($editingSelectionType !== 'community_college') disabled @endif>
                        <option value="">Select site</option>
                        @php $selectedCounty = old('community_college_county', $editingSelectionType === 'community_college' ? $currentCampus?->county : null); @endphp
                        @foreach(($campusSelectionOptions['community_college_sites'][$selectedCounty] ?? collect()) as $site)
                            <option value="{{ $site->id }}" @selected(old('community_college_site_id', $editingSelectionType === 'community_college' ? $currentCampusId : null) == $site->id)>
                                {{ $site->campus_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('community_college_site_id')<p class="tich-field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="tich-grid tich-grid--2 tich-mt-3">
                <div>
                    <label class="tich-label">Notes</label>
                    <input type="text" name="notes" class="tich-input" value="{{ old('notes') }}">
                </div>
            </div>

            <div class="tich-grid tich-grid--2 tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">{{ $editing ? 'Save Changes' : 'Register Student' }}</button>
                @if ($editing)
                    <a href="{{ route('administration.applications.existing-student.show', $student) }}" class="tich-btn tich-btn-ghost">Cancel</a>
                @endif
            </div>
        </form>
    </div>

    <script>
    (function () {
        var campuses = @json($campusSelectionOptions['community_college_sites']);
        var typeSelect = document.getElementById('campus_selection_type');
        var campusField = document.getElementById('campus-field-campus');
        var campusSelect = document.getElementById('campus_id');
        var communityField = document.getElementById('campus-field-community');
        var countySelect = document.getElementById('community_college_county');
        var siteSelect = document.getElementById('community_college_site_id');

        function renderSites(county) {
            siteSelect.innerHTML = '<option value="">Select site</option>';
            var sites = campuses[county] || [];
            sites.forEach(function (site) {
                var option = document.createElement('option');
                option.value = site.id;
                option.textContent = site.campus_name;
                siteSelect.appendChild(option);
            });
        }

        function syncCampusSelection() {
            var isCampus = typeSelect.value === 'campus';
            var isCommunity = typeSelect.value === 'community_college';
            campusField.hidden = !isCampus;
            campusSelect.disabled = !isCampus;
            communityField.hidden = !isCommunity;
            countySelect.disabled = !isCommunity;
            siteSelect.disabled = !isCommunity;
            if (isCommunity && countySelect.value) {
                renderSites(countySelect.value);
            } else {
                siteSelect.innerHTML = '<option value="">Select site</option>';
            }
        }

        if (typeSelect) {
            typeSelect.addEventListener('change', syncCampusSelection);
            countySelect.addEventListener('change', function () {
                renderSites(countySelect.value);
            });
            syncCampusSelection();
        }
    })();
    </script>
@endsection
