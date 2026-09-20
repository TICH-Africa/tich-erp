@extends('layouts.app')

@section('title', 'Research')
@section('meta_description', config('tich-seo.pages.research.description'))

@section('content')
    <x-animated-section animation="top">
        <section class="tich-section" aria-labelledby="research-heading">
            <div class="tich-container">
                <header class="tich-section__intro tich-mb-8">
                    <h1 id="research-heading" class="tich-h1">Research</h1>
                    <p class="tich-text tich-mt-2">A hub of research excellence linking classrooms to communities and policy.</p>
                </header>

                @if (session('status'))
                    <div class="tich-alert tich-alert--success tich-mb-6">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="tich-alert tich-alert--error tich-mb-6">
                        @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                    </div>
                @endif

                <div class="tich-grid tich-grid--2" style="gap: 2rem; align-items: start;">
                    <x-animated-card animation="left">
                        <div>
                            <h2 class="tich-h2">Research excellence</h2>
                            <p class="tich-text tich-mt-4">
                                From health and education to technology, environment, and social development, our multidisciplinary research teams work at the intersection of theory and practice-translating research into action and policy.
                            </p>
                            <p class="tich-text tich-mt-4">
                                Through partnership with local and global organizations, we address real-world challenges by generating knowledge that empowers individuals and strengthens communities.
                            </p>

                            <div class="tich-mt-6">
                                <h3 class="tich-h3">Our research areas</h3>
                                <ul class="tich-mt-4">
                                    <li>Community Health Systems Strengthening</li>
                                    <li>Maternal, Neonatal, and Child Health (MNCH)</li>
                                    <li>Health Equity and Social Determinants of Health</li>
                                    <li>Digital Health and Innovation</li>
                                    <li>Health Policy and Implementation Science</li>
                                    <li>Climate Change and Health</li>
                                </ul>
                            </div>
                        </div>
                    </x-animated-card>

                    <x-animated-card animation="right">
                        <div class="research-partner-form">
                            <h3 class="tich-h3">Be a research partner</h3>
                            <form method="POST" action="{{ route('research.partnerships.store') }}" enctype="multipart/form-data" class="tich-mt-4 uf-form" data-partnership-form data-uf="skip" style="margin-top:1rem;">
                                @csrf
                                <div class="uf-field">
                                    <label for="applicant_type">Applying as <span class="uf-req">*</span></label>
                                    <select id="applicant_type" name="applicant_type" required data-applicant-type>
                                        <option value="organisation" @selected(old('applicant_type', 'organisation') === 'organisation')>Organisation</option>
                                        <option value="individual" @selected(old('applicant_type') === 'individual')>Individual</option>
                                    </select>
                                </div>

                                <div class="tich-grid tich-grid--2 tich-mt-4">
                                    <div class="uf-field">
                                        <label for="first_name">First name <span class="uf-req">*</span></label>
                                        <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required maxlength="120">
                                    </div>
                                    <div class="uf-field">
                                        <label for="last_name">Last name <span class="uf-req">*</span></label>
                                        <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required maxlength="120">
                                    </div>
                                </div>

                                <div class="tich-grid tich-grid--2 tich-mt-4">
                                    <div class="uf-field">
                                        <label for="email">Email <span class="uf-req">*</span></label>
                                        <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                                    </div>
                                    <div class="uf-field">
                                        <label for="alternative_email">Alternative email</label>
                                        <input type="email" id="alternative_email" name="alternative_email" value="{{ old('alternative_email') }}">
                                    </div>
                                </div>

                                <div class="tich-grid tich-grid--2 tich-mt-4">
                                    <div class="uf-field">
                                        <label for="phone">Phone</label>
                                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}">
                                    </div>
                                    <div class="uf-field">
                                        <label for="alternative_phone">Alternative phone</label>
                                        <input type="tel" id="alternative_phone" name="alternative_phone" value="{{ old('alternative_phone') }}">
                                    </div>
                                </div>

                                @php
                                    $areaOptions = [
                                        'Community Health Systems',
                                        'MNCH',
                                        'Health Equity',
                                        'Digital Health',
                                        'Health Policy',
                                        'Climate Change and Health',
                                        'Other',
                                    ];
                                    $oldArea = old('research_area');
                                    $areaSelected = in_array($oldArea, $areaOptions, true)
                                        ? $oldArea
                                        : (old('research_area_other') ? 'Other' : ($oldArea ?: 'Community Health Systems'));
                                @endphp
                                <div class="uf-field tich-mt-4">
                                    <label for="research_area">Interested research area</label>
                                    <select id="research_area" name="research_area" data-research-area>
                                        @foreach ($areaOptions as $area)
                                            <option value="{{ $area }}" @selected($areaSelected === $area)>{{ $area }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="uf-field tich-mt-4" data-research-area-other @if($areaSelected !== 'Other') hidden @endif>
                                    <label for="research_area_other">Specify research area <span class="uf-req">*</span></label>
                                    <input
                                        type="text"
                                        id="research_area_other"
                                        name="research_area_other"
                                        value="{{ old('research_area_other') }}"
                                        maxlength="200"
                                        placeholder="Type the research area"
                                    >
                                </div>

                                <div data-org-fields class="tich-mt-4">
                                    <div class="uf-field">
                                        <label for="organization_name">Organisation name <span class="uf-req">*</span></label>
                                        <input type="text" id="organization_name" name="organization_name" value="{{ old('organization_name') }}" maxlength="300">
                                    </div>
                                    @php
                                        $typeOptions = [
                                            'ngo' => 'NGO',
                                            'county_government' => 'County government',
                                            'academic_institution' => 'Academic institution',
                                            'corporate' => 'Corporate',
                                            'other' => 'Other',
                                        ];
                                        $oldType = old('organization_type');
                                        $typeSelected = array_key_exists((string) $oldType, $typeOptions)
                                            ? $oldType
                                            : (old('organization_type_other') ? 'other' : ($oldType ?: 'ngo'));
                                    @endphp
                                    <div class="uf-field tich-mt-4">
                                        <label for="organization_type">Organisation type <span class="uf-req">*</span></label>
                                        <select id="organization_type" name="organization_type" data-organization-type>
                                            @foreach ($typeOptions as $value => $label)
                                                <option value="{{ $value }}" @selected($typeSelected === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="uf-field tich-mt-4" data-organization-type-other @if($typeSelected !== 'other') hidden @endif>
                                        <label for="organization_type_other">Specify organisation type <span class="uf-req">*</span></label>
                                        <input
                                            type="text"
                                            id="organization_type_other"
                                            name="organization_type_other"
                                            value="{{ old('organization_type_other') }}"
                                            maxlength="100"
                                            placeholder="Type the organisation type"
                                        >
                                    </div>
                                    <div class="uf-field tich-mt-4">
                                        <label for="organisation_details">Organisation details <span class="uf-req">*</span></label>
                                        <textarea id="organisation_details" name="organisation_details" rows="3" maxlength="5000">{{ old('organisation_details') }}</textarea>
                                    </div>
                                </div>

                                <div data-individual-fields class="tich-mt-4" hidden>
                                    <div class="uf-field">
                                        <label for="individual_details">About you <span class="uf-req">*</span></label>
                                        <textarea id="individual_details" name="individual_details" rows="3" maxlength="5000">{{ old('individual_details') }}</textarea>
                                    </div>
                                </div>

                                <div class="uf-field tich-mt-4">
                                    <label for="what_they_do">What do you do? <span class="uf-req">*</span></label>
                                    <textarea id="what_they_do" name="what_they_do" rows="3" required maxlength="5000">{{ old('what_they_do') }}</textarea>
                                </div>
                                <div class="uf-field tich-mt-4">
                                    <label for="why_partnership">Why do you want this partnership? <span class="uf-req">*</span></label>
                                    <textarea id="why_partnership" name="why_partnership" rows="3" required maxlength="5000">{{ old('why_partnership') }}</textarea>
                                </div>
                                <div class="uf-field tich-mt-4">
                                    <label for="attachments">Supporting document(s)</label>
                                    <input type="file" id="attachments" name="attachments[]" multiple accept=".pdf,.doc,.docx,image/png,image/jpeg">
                                    <span class="uf-hint">Optional. Up to 5 files (PDF, Word, or image).</span>
                                </div>

                                <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Submit partnership inquiry</button>
                            </form>
                        </div>
                    </x-animated-card>
                </div>

                @if ($projects->isNotEmpty())
                    <x-animated-section animation="bottom">
                        <div class="tich-mt-10">
                            <h2 class="tich-h2">Research activities</h2>
                            <div class="tich-grid tich-grid--3 tich-mt-6">
                                @foreach ($projects as $project)
                                    <x-animated-card animation="scale" :delay="$loop->iteration * 80">
                                        <article class="tich-card">
                                            <a href="{{ route('research.show', $project->slug) }}" class="tich-link" style="text-decoration:none;color:inherit;display:block;">
                                                @if ($project->coverUrl())
                                                    <img
                                                        src="{{ $project->coverUrl() }}"
                                                        alt="{{ $project->title }}"
                                                        class="tich-blog-card__image"
                                                        style="margin-bottom: 1rem;"
                                                    >
                                                @endif
                                                <p class="tich-caption">{{ $project->statusLabel() }}@if ($project->is_featured) · Featured @endif</p>
                                                <h3 class="tich-h3 tich-mt-2">{{ $project->title }}</h3>
                                                @if ($project->summary)
                                                    <p class="tich-text tich-mt-2">{{ \Illuminate\Support\Str::limit($project->summary, 180) }}</p>
                                                @endif
                                                <p class="tich-caption tich-mt-4">Read more →</p>
                                            </a>
                                        </article>
                                    </x-animated-card>
                                @endforeach
                            </div>
                        </div>
                    </x-animated-section>
                @elseif ($featured)
                    <x-animated-card animation="fade">
                        <div class="tich-mt-10 tich-card">
                            <p class="tich-caption">Featured {{ $featured->status ?? 'ongoing' }} project</p>
                            <h2 class="tich-h3 tich-mt-2">{{ $featured->title }}</h2>
                            <p class="tich-text tich-mt-4">{{ $featured->summary }}</p>
                            @if (!empty($featured->url))
                                <p class="tich-mt-4"><a href="{{ $featured->url }}" class="tich-link">View activity →</a></p>
                            @endif
                        </div>
                    </x-animated-card>
                @endif
            </div>
        </section>
    </x-animated-section>
@endsection

@section('scripts')
    @parent
    <script>
    (function () {
        var form = document.querySelector('[data-partnership-form]');
        if (!form) return;

        var type = form.querySelector('[data-applicant-type]');
        var org = form.querySelector('[data-org-fields]');
        var ind = form.querySelector('[data-individual-fields]');
        var areaSelect = form.querySelector('[data-research-area]');
        var areaOtherWrap = form.querySelector('[data-research-area-other]');
        var areaOtherInput = form.querySelector('#research_area_other');
        var orgTypeSelect = form.querySelector('[data-organization-type]');
        var orgTypeOtherWrap = form.querySelector('[data-organization-type-other]');
        var orgTypeOtherInput = form.querySelector('#organization_type_other');

        function syncApplicant() {
            var isOrg = type.value === 'organisation';
            org.hidden = !isOrg;
            ind.hidden = isOrg;

            ['organization_name', 'organization_type', 'organisation_details'].forEach(function (name) {
                var el = org.querySelector('[name="' + name + '"]');
                if (el) el.required = isOrg;
            });
            ind.querySelectorAll('textarea').forEach(function (el) {
                el.required = !isOrg;
            });
            syncOrgTypeOther(isOrg);
        }

        function syncAreaOther() {
            var isOther = areaSelect && areaSelect.value === 'Other';
            if (areaOtherWrap) areaOtherWrap.hidden = !isOther;
            if (areaOtherInput) areaOtherInput.required = !!isOther;
        }

        function syncOrgTypeOther(isOrg) {
            if (typeof isOrg === 'undefined') {
                isOrg = type.value === 'organisation';
            }
            var isOther = isOrg && orgTypeSelect && orgTypeSelect.value === 'other';
            if (orgTypeOtherWrap) orgTypeOtherWrap.hidden = !isOther;
            if (orgTypeOtherInput) orgTypeOtherInput.required = !!isOther;
        }

        type.addEventListener('change', syncApplicant);
        if (areaSelect) areaSelect.addEventListener('change', syncAreaOther);
        if (orgTypeSelect) orgTypeSelect.addEventListener('change', function () { syncOrgTypeOther(); });

        syncApplicant();
        syncAreaOther();
    })();
    </script>
@endsection
