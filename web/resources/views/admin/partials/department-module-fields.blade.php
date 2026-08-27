@props([
    'moduleCatalog',
    'assignedModules' => [],
    'fieldIdPrefix' => '',
    'selectedCategory' => null,
])

@php
    $assignedModules = old('module_keys', $assignedModules);
    $assignedModules = is_array($assignedModules) ? $assignedModules : [];
@endphp

<div class="tich-form-group tich-dept-modules" data-dept-modules>
    <label class="tich-label">Platform modules</label>
    <p class="tich-caption tich-mb-3">
        Assign modules this department can access. Submodules (courses, lesson plans, payroll, etc.) are inherited automatically.
        For a top-level academic hub, tick <strong>Academics &amp; curriculum</strong>.
    </p>

    <div class="tich-dept-modules__list" style="display: grid; gap: 0.75rem; max-height: 18rem; overflow-y: auto; padding-right: 0.25rem;">
        @forelse ($moduleCatalog as $module)
            @php
                $isChecked = in_array($module['key'], $assignedModules, true);
                $eligibleCategories = $module['eligible_categories'] ?? [];
                $categoryAttr = $eligibleCategories !== [] ? implode(',', $eligibleCategories) : 'academic,administrative,support';
                $childLabels = collect($module['children'] ?? [])->pluck('label')->all();
            @endphp
            <div
                class="tich-dept-module-option"
                data-eligible-categories="{{ $categoryAttr }}"
                style="padding: 0.85rem 1rem; border: 1px solid var(--tich-border, #e5e7eb); border-radius: 0.5rem;"
            >
                <label style="display: flex; gap: 0.65rem; align-items: start; cursor: pointer; margin: 0;">
                    <input
                        type="checkbox"
                        name="module_keys[]"
                        value="{{ $module['key'] }}"
                        class="tich-dept-module-checkbox"
                        @checked($isChecked)
                    >
                    <span>
                        <strong>{{ $module['label'] }}</strong>
                        <span class="tich-caption" style="display: block; margin-top: 0.15rem;">{{ $module['description'] }}</span>
                        @if ($childLabels !== [])
                            <span class="tich-caption tich-dept-module-children" style="display: block; margin-top: 0.35rem; color: var(--tich-text-muted, #6b7280);">
                                Includes: {{ implode(', ', $childLabels) }}
                            </span>
                        @endif
                    </span>
                </label>
            </div>
        @empty
            <p class="tich-caption">No platform modules are configured.</p>
        @endforelse
    </div>

    <p class="tich-caption tich-mt-2 tich-dept-modules-empty" style="display: none;">
        No modules match the selected category. Change category or contact a platform administrator.
    </p>
</div>

<script>
(function () {
    function filterModules(container) {
        if (!container) {
            return;
        }

        var form = container.closest('form');
        var categoryField = form ? form.querySelector('[name="dept_category"]') : null;
        var category = categoryField ? categoryField.value : '';
        var options = container.querySelectorAll('.tich-dept-module-option');
        var visibleCount = 0;

        options.forEach(function (option) {
            var eligible = (option.getAttribute('data-eligible-categories') || '').split(',').filter(Boolean);
            // If category is empty (form not filled yet), show all options.
            var show = !category || eligible.length === 0 || eligible.indexOf(category) !== -1;
            option.style.display = show ? '' : 'none';

            // Keep checked state when hiding so edit→save does not silently wipe modules.
            // Ineligible keys are filtered server-side by category.
            if (show) {
                visibleCount++;
            }
        });

        var emptyNote = container.querySelector('.tich-dept-modules-empty');
        if (emptyNote) {
            emptyNote.style.display = visibleCount === 0 ? '' : 'none';
        }
    }

    document.querySelectorAll('[data-dept-modules]').forEach(function (container) {
        if (container.getAttribute('data-filter-bound') === '1') {
            return;
        }
        container.setAttribute('data-filter-bound', '1');

        var form = container.closest('form');
        var categoryField = form ? form.querySelector('[name="dept_category"]') : null;

        filterModules(container);

        if (categoryField) {
            categoryField.addEventListener('change', function () {
                filterModules(container);
            });
        }
    });
})();
</script>
