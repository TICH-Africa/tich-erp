@props([
    'program' => null,
    'fieldIdPrefix' => '',
])

@php
    if (! isset($pfOld)) {
        $fieldIdPrefix = $fieldIdPrefix ?? '';
        $programFormScope = str_starts_with((string) $fieldIdPrefix, 'program-create-') || str_starts_with((string) $fieldIdPrefix, 'create_')
            ? 'create'
            : 'edit';
        $failedFormScope = old('_form');
        $useFlashedInput = $failedFormScope === null || $failedFormScope === $programFormScope;
        $pfOld = static fn (string $key, mixed $default = '') => $useFlashedInput ? old($key, $default) : $default;
        $pfOldChecked = static function (string $key, mixed $default = false) use ($useFlashedInput): bool {
            if ($useFlashedInput && old($key) !== null) {
                return filter_var(old($key), FILTER_VALIDATE_BOOLEAN);
            }

            return (bool) $default;
        };
    }

    $tierKeys = config('tich-program-tiers.tier_keys', []);
    $tierLabels = config('tich-program-tiers.tier_labels', []);
    $isTiered = $pfOldChecked('is_tiered', $program->is_tiered ?? false);
    $oldTiersList = $useFlashedInput ? old('tiers', []) : [];
    if ($program && ! $program->relationLoaded('tiers')) {
        $program->load('tiers');
    }
    $existingTiers = $program?->tiers?->keyBy('tier_key') ?? collect();
@endphp

<div data-program-tier-root data-field-prefix="{{ $fieldIdPrefix }}">
    <div class="tich-form-group tich-mt-4">
        <label style="display:flex;gap:0.5rem;align-items:center;">
            <input
                type="checkbox"
                name="is_tiered"
                value="1"
                data-tier-toggle
                @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}is_tiered" @endif
                @checked($isTiered)
            >
            <span class="tich-text"><strong>Institutional tiered programme</strong> (Certificate → Diploma → Advanced Diploma)</span>
        </label>
        <p class="tich-caption tich-mt-2">One programme code with three exit levels. Applicants choose a tier; fees and curriculum map to semesters. Not for NITA/CDACC/KASNEB programmes.</p>
    </div>

    <div class="tich-tier-config" data-tier-panel @if(! $isTiered) hidden @endif>
        <div class="tich-form-group">
            <label class="tich-label">Semester length (months) <x-required-mark /></label>
            <input
                type="number"
                name="tier_semester_months"
                class="tich-input"
                min="1"
                max="12"
                data-tier-semester-months
                data-tier-required
                value="{{ $pfOld('tier_semester_months', $program->tier_semester_months ?? '') }}"
                placeholder="e.g. 3 or 6"
            >
        <p class="tich-caption tich-mt-2">Required when tiered programme is enabled. Used to split each tier’s incremental months into semesters.</p>
    </div>

        @foreach ($tierKeys as $index => $tierKey)
            @php
                $tier = $existingTiers->get($tierKey);
                $oldTier = collect($oldTiersList)->firstWhere('tier_key', $tierKey)
                    ?? (is_array($oldTiersList[$index] ?? null) ? $oldTiersList[$index] : null);
            @endphp
            <fieldset class="tich-card tich-mb-4" style="padding:1rem;">
                <legend class="tich-h4">{{ $tierLabels[$tierKey] ?? $tierKey }}</legend>
                <input type="hidden" name="tiers[{{ $index }}][tier_key]" value="{{ $tierKey }}">

                <div class="tich-form-group">
                    <label class="tich-label">Cumulative duration (months) <x-required-mark /></label>
                    <input
                        type="number"
                        name="tiers[{{ $index }}][cumulative_months]"
                        class="tich-input"
                        min="1"
                        max="120"
                        data-tier-cumulative
                        data-tier-index="{{ $index }}"
                        data-tier-required
                        value="{{ $oldTier['cumulative_months'] ?? $tier?->cumulative_months ?? '' }}"
                        placeholder="{{ $index === 0 ? '6' : ($index === 1 ? '12' : '24') }}"
                    >
                    <p class="tich-caption tich-mt-1" data-tier-incremental-label="{{ $index }}"></p>
                </div>

                <div class="tich-mt-2" data-tier-progress-wrap="{{ $index }}" hidden>
                    <p class="tich-caption">Share of full programme timeline</p>
                    <div style="height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden;">
                        <div data-tier-progress-bar="{{ $index }}" style="height:100%;width:0;background:#1669a6;"></div>
                    </div>
                </div>

                <div class="tich-form-group tich-mt-4">
                    <label class="tich-label">What learners gain at this tier</label>
                    <textarea
                        name="tiers[{{ $index }}][learning_outcomes]"
                        class="tich-input"
                        rows="4"
                        placeholder="Outcomes for {{ $tierLabels[$tierKey] ?? $tierKey }}…"
                    >{{ $oldTier['learning_outcomes'] ?? $tier?->learning_outcomes ?? '' }}</textarea>
                </div>

                <div class="tich-form-group">
                    <label class="tich-label">Public summary (optional)</label>
                    <textarea
                        name="tiers[{{ $index }}][marketing_summary]"
                        class="tich-input"
                        rows="2"
                    >{{ $oldTier['marketing_summary'] ?? $tier?->marketing_summary ?? '' }}</textarea>
                </div>

                <div class="tich-form-group">
                    <label class="tich-label">Total semester fee (KES) for this tier</label>
                    <input
                        type="number"
                        name="tier_fees[{{ $tierKey }}]"
                        class="tich-input"
                        min="0"
                        step="0.01"
                        value="{{ $pfOld('tier_fees.'.$tierKey, '') }}"
                        placeholder="Full tier fee (used for apply & top-up)"
                    >
                    <p class="tich-caption tich-mt-1">Creates a fee structure row tagged to this tier for the current academic year.</p>
                </div>
            </fieldset>
        @endforeach
    </div>
</div>

<script>
(function () {
    const root = document.currentScript?.previousElementSibling;
    if (!root || !root.hasAttribute('data-program-tier-root')) return;

    const panel = root.querySelector('[data-tier-panel]');
    const toggle = root.querySelector('[data-tier-toggle]');
    const prefix = root.getAttribute('data-field-prefix') || '';
    const durationField = root.closest('form')?.querySelector('[name="duration_months"]');

    function parseCumulatives() {
        return Array.from(root.querySelectorAll('[data-tier-cumulative]')).map((input) => parseInt(input.value, 10) || 0);
    }

    function refreshTierUi() {
        const values = parseCumulatives();
        const max = Math.max(...values, 1);
        let prev = 0;
        values.forEach((cum, index) => {
            const incLabel = root.querySelector('[data-tier-incremental-label="' + index + '"]');
            const barWrap = root.querySelector('[data-tier-progress-wrap="' + index + '"]');
            const bar = root.querySelector('[data-tier-progress-bar="' + index + '"]');
            if (cum > prev) {
                if (incLabel) incLabel.textContent = '+' + (cum - prev) + ' months at this tier (total ' + cum + ' months)';
                if (barWrap) barWrap.hidden = false;
                if (bar) bar.style.width = Math.min(100, Math.round((cum / max) * 100)) + '%';
            } else if (incLabel) {
                incLabel.textContent = index === 0 ? 'Enter cumulative months for Certificate' : 'Must exceed previous tier';
            }
            prev = cum;
        });
        if (durationField && values.length) {
            const top = values[values.length - 1];
            if (top > 0) durationField.value = top;
            durationField.readOnly = !!toggle?.checked;
        }
    }

    function syncTierRequired() {
        const on = !!toggle?.checked;
        root.querySelectorAll('[data-tier-required]').forEach(function (el) {
            if (on) {
                el.setAttribute('required', 'required');
            } else {
                el.removeAttribute('required');
            }
        });
    }

    toggle?.addEventListener('change', () => {
        if (panel) panel.hidden = !toggle.checked;
        if (durationField) durationField.readOnly = toggle.checked;
        syncTierRequired();
        refreshTierUi();
    });

    root.querySelectorAll('[data-tier-cumulative]').forEach((el) => el.addEventListener('input', refreshTierUi));
    refreshTierUi();
    syncTierRequired();
    if (toggle?.checked && durationField) durationField.readOnly = true;
})();
</script>
