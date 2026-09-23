@extends('layouts.administration')

@section('title', 'Existing Student Registration')

@section('administration-content')
    <x-page-toolbar title="Add Existing Student" meta="Register an existing student into a program" />

    <div class="tich-card tich-mt-6">
        <form method="POST" action="{{ route('administration.applications.existing-student.store') }}">
            @csrf

            <h3 class="tich-h3">Student Information</h3>
            <div class="tich-grid tich-grid--3 tich-mt-3">
                <div>
                    <label class="tich-label">Registration Number *</label>
                    <input type="text" name="registration_number" class="tich-input" required placeholder="e.g. S2026-0001">
                </div>
                <div>
                    <label class="tich-label">First Name *</label>
                    <input type="text" name="first_name" class="tich-input" required>
                </div>
                <div>
                    <label class="tich-label">Middle Name</label>
                    <input type="text" name="middle_name" class="tich-input">
                </div>
                <div>
                    <label class="tich-label">Surname *</label>
                    <input type="text" name="surname" class="tich-input" required>
                </div>
                <div>
                    <label class="tich-label">Email *</label>
                    <input type="email" name="email" class="tich-input" required>
                </div>
                <div>
                    <label class="tich-label">Phone Number</label>
                    <input type="text" name="phone_number" class="tich-input">
                </div>
            </div>

            <h3 class="tich-h3 tich-mt-6">Program Details</h3>
            <div class="tich-grid tich-grid--3 tich-mt-3">
                <div>
                    <label class="tich-label">Programme *</label>
                    <select name="program_id" class="tich-input" required>
                        <option value="">Select programme</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}">{{ $program->program_code }} - {{ $program->program_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="tich-label">Year Joined *</label>
                    <input type="date" name="year_joined" class="tich-input" required>
                </div>
                <div>
                    <label class="tich-label">Program Type *</label>
                    <select name="program_type" class="tich-input" required>
                        <option value="diploma">Diploma</option>
                        <option value="certificate">Certificate</option>
                    </select>
                </div>
                <div>
                    <label class="tich-label">Duration (months)</label>
                    <input type="number" name="duration_months" class="tich-input" value="36">
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
                        <option value="campus" @selected(old('campus_selection_type', 'campus') === 'campus')>Campus</option>
                        @if($campusSelectionOptions['community_college_sites']->isNotEmpty())
                            <option value="community_college" @selected(old('campus_selection_type') === 'community_college')>Community College</option>
                        @endif
                        <option value="online" @selected(old('campus_selection_type') === 'online')>Online</option>
                    </select>
                    @error('campus_selection_type')<p class="tich-field-error">{{ $message }}</p>@enderror
                </div>
                <div id="campus-field-campus" @if(old('campus_selection_type', 'campus') !== 'campus') hidden @endif>
                    <label class="tich-label">Campus</label>
                    <select id="campus_id" name="campus_id" class="tich-input" @if(old('campus_selection_type', 'campus') !== 'campus') disabled @endif>
                        <option value="">Select campus</option>
                        @foreach ($campusSelectionOptions['campuses'] as $campus)
                            <option value="{{ $campus->id }}" @selected(old('campus_id') == $campus->id)>
                                {{ $campus->campus_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('campus_id')<p class="tich-field-error">{{ $message }}</p>@enderror
                </div>
                <div id="campus-field-community" @if(old('campus_selection_type') !== 'community_college') hidden @endif>
                    <label class="tich-label">County</label>
                    <select id="community_college_county" name="community_college_county" class="tich-input" @if(old('campus_selection_type') !== 'community_college') disabled @endif>
                        <option value="">Select county</option>
                        @foreach($campusSelectionOptions['community_college_sites']->keys() as $county)
                            <option value="{{ $county }}" @selected(old('community_college_county') == $county)>{{ $county }}</option>
                        @endforeach
                    </select>
                    @error('community_college_county')<p class="tich-field-error">{{ $message }}</p>@enderror
                    <label class="tich-label">Community college site</label>
                    <select id="community_college_site_id" name="community_college_site_id" class="tich-input" @if(old('campus_selection_type') !== 'community_college') disabled @endif>
                        <option value="">Select site</option>
                        @php $selectedCounty = old('community_college_county'); @endphp
                        @foreach(($campusSelectionOptions['community_college_sites'][$selectedCounty] ?? collect()) as $site)
                            <option value="{{ $site->id }}" @selected(old('community_college_site_id') == $site->id)>
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
                    <input type="text" name="notes" class="tich-input">
                </div>
            </div>

            <div class="tich-grid tich-grid--2 tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Register Student</button>
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
