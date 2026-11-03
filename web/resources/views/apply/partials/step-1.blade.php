<h2 class="tich-h3">Step 1 - Choose programme</h2>
<p class="tich-text tich-mt-2">Select the programme you wish to apply for, your target intake, and your preferred campus.</p>

@php
    $queryProgramCode = strtoupper(trim((string) request()->query('program', '')));
    $selectedProgramCode = strtoupper(old('program_code', data_get($draft, 'program_code', $queryProgramCode)));
    if ($queryProgramCode !== '') {
        $selectedProgramCode = $queryProgramCode;
    }
    $selectedProgramId = old('program_id', data_get($draft, 'program_id', ''));
    $selectedIntakeYear = old('intake_year', data_get($draft, 'intake_year', ''));
    $selectedIntakeMonth = old('intake_month', data_get($draft, 'intake_month', ''));
    $selectedCampusId = old('preferred_campus_id', data_get($draft, 'preferred_campus_id', ''));
    $selectedCampus = collect($campuses)->first(fn ($campus) => ($campus->id ?? null) == $selectedCampusId);
    $selectedCounty = old('community_college_county', data_get($draft, 'community_college_county', $selectedCampus?->county ?? ''));
    $selectedSiteId = old('community_college_site_id', data_get($draft, 'community_college_site_id', $selectedCampus?->campus_type === 'community_college' ? $selectedCampusId : ''));
    $campusSelectionType = old('campus_selection_type', data_get($draft, 'campus_selection_type', $selectedCampus?->campus_type === 'community_college' ? 'community_college' : ($selectedCampusId ? 'campus' : '')));
    $tierKeys = config('tich-program-tiers.tier_keys', []);
    $tierLabels = config('tich-program-tiers.tier_labels', []);
    $normalizeTierKey = static function (?string $raw) use ($tierKeys): string {
        if ($raw === null || trim($raw) === '') {
            return '';
        }
        $key = strtolower(str_replace(['-', ' '], '_', trim($raw)));

        return in_array($key, $tierKeys, true) ? $key : '';
    };
    $queryTierKey = $normalizeTierKey((string) request()->query('tier', ''));
    if ($queryTierKey !== '' && ($queryProgramCode === '' || $queryProgramCode === $selectedProgramCode)) {
        $selectedProgramTier = $queryTierKey;
    } else {
        $selectedProgramTier = $normalizeTierKey((string) old(
            'program_tier',
            data_get($draft, 'program_tier', '')
        ));
    }
    $selectedProgram = null;
    if ($selectedProgramCode !== '') {
        $selectedProgram = $programs->first(
            fn ($program) => strtoupper((string) data_get($program, 'program_code', '')) === $selectedProgramCode
        );
    }
    if (! $selectedProgram && $selectedProgramId !== '') {
        $selectedProgram = $programs->first(
            fn ($program) => (string) data_get($program, 'id') === (string) $selectedProgramId
        );
    }
    if ($selectedProgram) {
        $selectedProgramId = data_get($selectedProgram, 'id');
    }
    $tierOptionsForSelected = [];
    if ($selectedProgram && data_get($selectedProgram, 'is_tiered')) {
        foreach (data_get($selectedProgram, 'tiers', []) as $tier) {
            $tierKey = data_get($tier, 'tier_key', '');
            if ($tierKey === '') {
                continue;
            }
            $tierOptionsForSelected[] = [
                'tier_key' => $tierKey,
                'label' => data_get($tier, 'label') ?: ($tierLabels[$tierKey] ?? $tierKey),
                'summary' => (string) data_get($tier, 'marketing_summary', ''),
            ];
        }
    }
    $showProgramTierField = $tierOptionsForSelected !== [];
    $selectedTierSummary = data_get(
        collect($tierOptionsForSelected)->firstWhere('tier_key', $selectedProgramTier),
        'summary',
        ''
    );
    $programsTierMeta = $programs->mapWithKeys(function ($p) use ($tierLabels) {
        $tiers = collect(data_get($p, 'tiers', []))->map(function ($t) use ($tierLabels) {
            $tierKey = (string) data_get($t, 'tier_key', '');

            return [
                'tier_key' => $tierKey,
                'label' => data_get($t, 'label') ?: ($tierLabels[$tierKey] ?? $tierKey),
                'summary' => (string) data_get($t, 'marketing_summary', ''),
            ];
        })->values()->all();

        return [
            (string) data_get($p, 'id', '') => [
                'is_tiered' => (bool) data_get($p, 'is_tiered', false),
                'tiers' => $tiers,
            ],
        ];
    })->all();
@endphp

@if ($programs->isEmpty())
    <p class="tich-field-error tich-mt-4">Programme catalogue is not available yet. Run <code>php artisan db:seed --class=ProgramsSeeder</code> or add programmes in the admin panel.</p>
@endif

@if ($selectedProgramCode && $programs->isNotEmpty())
    <p class="tich-caption tich-mt-4">Pre-selected programme: <strong>{{ $selectedProgramCode }}</strong></p>
@endif
@if ($selectedProgramTier !== '' && $showProgramTierField)
    @php $preTierLabel = $tierLabels[$selectedProgramTier] ?? $selectedProgramTier; @endphp
    <p class="tich-caption tich-mt-2">Pre-selected exit level: <strong>{{ $preTierLabel }}</strong></p>
@endif

<div class="tich-form-group tich-mt-6">
    <label for="program_id" class="tich-label">Programme</label>
    <select id="program_id" name="program_id" class="tich-input" required>
        <option value="">Select a programme</option>
        @foreach ($programs as $program)
            <option
                value="{{ $program->id ?? '' }}"
                @selected((string) $selectedProgramId !== '' && (string) $selectedProgramId === (string) ($program->id ?? ''))
            >
                {{ $program->program_code }} - {{ $program->program_name }}
            </option>
        @endforeach
    </select>
    @error('program_id')<p class="tich-field-error">{{ $message }}</p>@enderror
</div>

<div class="tich-form-group" id="program-tier-field" @if(! $showProgramTierField) hidden @endif>
    <label for="program_tier" class="tich-label">Exit level (tier)</label>
    <select id="program_tier" name="program_tier" class="tich-input" @if($showProgramTierField) required @endif>
        <option value="">Select tier</option>
        @foreach ($tierOptionsForSelected as $tierOption)
            <option
                value="{{ $tierOption['tier_key'] }}"
                data-summary="{{ $tierOption['summary'] }}"
                @selected($selectedProgramTier === $tierOption['tier_key'])
            >
                {{ $tierOption['label'] }}
            </option>
        @endforeach
    </select>
    <p id="program-tier-summary" class="tich-caption tich-mt-2">{{ $selectedTierSummary }}</p>
    @error('program_tier')<p class="tich-field-error">{{ $message }}</p>@enderror
    <p class="tich-caption tich-mt-2">You must complete lower tiers before advancing. Entry requirements are the same for all tiers.</p>
</div>

<div class="tich-form-group" id="intake-field" style="display:none;">
    <label for="intake_selection" class="tich-label">Target intake</label>
    <select id="intake_selection" class="tich-input">
        <option value="">Select intake</option>
    </select>
    <input type="hidden" id="intake_year" name="intake_year" value="{{ $selectedIntakeYear }}">
    <input type="hidden" id="intake_month" name="intake_month" value="{{ $selectedIntakeMonth }}">
    @error('intake_year')<p class="tich-field-error">{{ $message }}</p>@enderror
    @error('intake_month')<p class="tich-field-error">{{ $message }}</p>@enderror
    <p class="tich-caption tich-mt-2">Choose the intake you plan to join. This must match an open intake for the programme.</p>
</div>

<div class="tich-form-group">
    <label for="campus_selection_type" class="tich-label">Preferred location type</label>
    <select id="campus_selection_type" name="campus_selection_type" class="tich-input">
        <option value="">No preference</option>
        @if($campusSelectionOptions['campuses']->isNotEmpty())
            <option value="campus" @selected($campusSelectionType === 'campus')>Campus</option>
        @endif
        @if($campusSelectionOptions['community_college_sites']->isNotEmpty())
            <option value="community_college" @selected($campusSelectionType === 'community_college')>Community College</option>
        @endif
        <option value="online" @selected($campusSelectionType === 'online')>Online</option>
    </select>
    @error('campus_selection_type')<p class="tich-field-error">{{ $message }}</p>@enderror
</div>

<div class="tich-form-group" id="campus-selection-campus-field" @if($campusSelectionType !== 'campus') hidden @endif>
    <label for="preferred_campus_id" class="tich-label">Preferred campus</label>
    <select id="preferred_campus_id" name="preferred_campus_id" class="tich-input" @if($campusSelectionType !== 'campus') disabled @endif>
        <option value="">Select campus</option>
        @foreach ($campusSelectionOptions['campuses'] as $campus)
            <option value="{{ $campus->id }}" @selected($selectedCampusId == $campus->id)>
                {{ $campus->campus_name }}
            </option>
        @endforeach
    </select>
    @error('preferred_campus_id')<p class="tich-field-error">{{ $message }}</p>@enderror
</div>

<div class="tich-form-group" id="campus-selection-community-field" @if($campusSelectionType !== 'community_college') hidden @endif>
    <label for="community_college_county" class="tich-label">County</label>
    <select id="community_college_county" name="community_college_county" class="tich-input" @if($campusSelectionType !== 'community_college') disabled @endif>
        <option value="">Select county</option>
        @foreach($campusSelectionOptions['community_college_sites']->keys() as $county)
            <option value="{{ $county }}" @selected($selectedCounty == $county)>{{ $county }}</option>
        @endforeach
    </select>
    @error('community_college_county')<p class="tich-field-error">{{ $message }}</p>@enderror
    <label for="community_college_site_id" class="tich-label">Community college site</label>
    <select id="community_college_site_id" name="community_college_site_id" class="tich-input" @if($campusSelectionType !== 'community_college') disabled @endif>
        <option value="">Select site</option>
        @foreach(($campusSelectionOptions['community_college_sites'][$selectedCounty] ?? collect()) as $site)
            <option value="{{ $site->id }}" @selected($selectedSiteId == $site->id)>
                {{ $site->campus_name }}
            </option>
        @endforeach
    </select>
    @error('community_college_site_id')<p class="tich-field-error">{{ $message }}</p>@enderror
</div>

@php
    $intakeOptions = [];
    foreach ($programIntakes as $programId => $intakes) {
        $intakeOptions[$programId] = $intakes->map(fn ($intake) => [
            'year' => (int) $intake->intake_year,
            'month' => (int) $intake->intake_month,
            'label' => $intake->intakeLabel(),
        ])->values()->all();
    }
@endphp

<script>
document.addEventListener('DOMContentLoaded', function () {
    const intakesByProgram = @json($intakeOptions);
    const tierMetaByProgram = @json($programsTierMeta);
    const selectedTier = @json((string) $selectedProgramTier);
    const programSelect = document.getElementById('program_id');
    const tierField = document.getElementById('program-tier-field');
    const tierSelect = document.getElementById('program_tier');
    const tierSummary = document.getElementById('program-tier-summary');
    const intakeField = document.getElementById('intake-field');
    const intakeSelect = document.getElementById('intake_selection');
    const intakeYearInput = document.getElementById('intake_year');
    const intakeMonthInput = document.getElementById('intake_month');
    const selectedYear = @json((int) $selectedIntakeYear);
    const selectedMonth = @json((int) $selectedIntakeMonth);

    function renderIntakes(programId) {
        intakeSelect.innerHTML = '<option value="">Select intake</option>';
        intakeYearInput.value = '';
        intakeMonthInput.value = '';

        const intakes = intakesByProgram[programId] || [];
        if (!intakes.length) {
            intakeField.style.display = 'none';
            intakeSelect.removeAttribute('required');
            return;
        }

        intakeField.style.display = '';
        intakeSelect.setAttribute('required', 'required');

        intakes.forEach(function (intake) {
            const option = document.createElement('option');
            option.value = intake.year + '-' + intake.month;
            option.textContent = intake.label;
            if (intake.year === selectedYear && intake.month === selectedMonth) {
                option.selected = true;
                intakeYearInput.value = intake.year;
                intakeMonthInput.value = intake.month;
            }
            intakeSelect.appendChild(option);
        });
    }

    intakeSelect.addEventListener('change', function () {
        const parts = intakeSelect.value.split('-');
        intakeYearInput.value = parts[0] || '';
        intakeMonthInput.value = parts[1] || '';
    });

    function normalizeTierKey(raw) {
        if (!raw) return '';
        return String(raw).trim().toLowerCase().replace(/[\s-]+/g, '_');
    }

    function renderTiers(programId, preferredTier) {
        const hasPreferred = preferredTier !== undefined && preferredTier !== null && String(preferredTier).trim() !== '';
        const keepTier = hasPreferred
            ? normalizeTierKey(preferredTier)
            : (normalizeTierKey(selectedTier) || normalizeTierKey(tierSelect.value));
        tierSelect.innerHTML = '<option value="">Select tier</option>';
        tierSummary.textContent = '';
        const meta = tierMetaByProgram[programId];
        if (!meta || !meta.is_tiered || !meta.tiers.length) {
            tierField.hidden = true;
            tierSelect.removeAttribute('required');
            return;
        }
        tierField.hidden = false;
        tierSelect.setAttribute('required', 'required');
        meta.tiers.forEach(function (tier) {
            const option = document.createElement('option');
            option.value = tier.tier_key;
            option.textContent = tier.label || tier.tier_key;
            if (tier.tier_key === keepTier) {
                option.selected = true;
                tierSummary.textContent = tier.summary || '';
            }
            tierSelect.appendChild(option);
        });
    }

    tierSelect.addEventListener('change', function () {
        const selected = tierSelect.options[tierSelect.selectedIndex];
        tierSummary.textContent = selected?.getAttribute('data-summary') || '';
        const meta = tierMetaByProgram[programSelect.value];
        const tier = (meta?.tiers || []).find(function (t) { return t.tier_key === tierSelect.value; });
        if (tier?.summary) {
            tierSummary.textContent = tier.summary;
        }
    });

    programSelect.addEventListener('change', function () {
        renderIntakes(programSelect.value);
        renderTiers(programSelect.value, '');
    });

    if (programSelect.value) {
        renderIntakes(programSelect.value);
        renderTiers(programSelect.value, selectedTier);
    }

    const campusSelectionType = document.getElementById('campus_selection_type');
    const campusSelectionField = document.getElementById('campus-selection-campus-field');
    const campusSelect = document.getElementById('preferred_campus_id');
    const communityField = document.getElementById('campus-selection-community-field');
    const countySelect = document.getElementById('community_college_county');
    const siteSelect = document.getElementById('community_college_site_id');
    const ccSites = @json($campusSelectionOptions['community_college_sites']);

    function renderCcSites(county) {
        siteSelect.innerHTML = '<option value="">Select site</option>';
        (ccSites[county] || []).forEach(function (site) {
            const option = document.createElement('option');
            option.value = site.id;
            option.textContent = site.campus_name;
            siteSelect.appendChild(option);
        });
    }

    function syncCampusSelection() {
        const type = campusSelectionType.value;
        const isCampus = type === 'campus';
        const isCommunity = type === 'community_college';
        const isOnline = type === 'online';
        campusSelectionField.hidden = !isCampus;
        campusSelect.disabled = !isCampus;
        communityField.hidden = !isCommunity;
        countySelect.disabled = !isCommunity;
        siteSelect.disabled = !isCommunity;
        if (isCommunity && countySelect.value) {
            renderCcSites(countySelect.value);
        } else {
            siteSelect.innerHTML = '<option value="">Select site</option>';
        }
    }

    if (campusSelectionType) {
        campusSelectionType.addEventListener('change', syncCampusSelection);
        countySelect.addEventListener('change', function () {
            renderCcSites(countySelect.value);
        });
        syncCampusSelection();
    }
});
</script>
