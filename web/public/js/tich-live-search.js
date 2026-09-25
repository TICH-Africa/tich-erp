(function () {
    'use strict';

    function normalize(value) {
        return String(value || '').toLowerCase().replace(/\s+/g, ' ').trim();
    }

    function bindLiveSearch(root) {
        if (!root || root.dataset.liveSearchBound === '1') {
            return;
        }
        root.dataset.liveSearchBound = '1';

        var input = root.querySelector('[data-live-search-input]');
        var filters = [...root.querySelectorAll('[data-live-search-filter]')];
        var items = [...root.querySelectorAll('[data-live-search-item]')];
        var empty = root.querySelector('[data-live-search-empty]');
        var count = root.querySelector('[data-live-search-count]');

        if (items.length === 0) {
            return;
        }

        function applyFilter() {
            var query = normalize(input ? input.value : '');
            var activeFilters = {};

            filters.forEach(function (el) {
                var key = el.getAttribute('data-live-search-filter');
                if (key) {
                    activeFilters[key] = normalize(el.value);
                }
            });

            var visible = 0;

            items.forEach(function (item) {
                var haystack = normalize(item.getAttribute('data-search') || item.textContent);
                var matchesQuery = !query || haystack.indexOf(query) !== -1;
                var matchesFilters = true;

                Object.keys(activeFilters).forEach(function (key) {
                    var wanted = activeFilters[key];
                    if (!wanted) {
                        return;
                    }
                    var actual = normalize(item.getAttribute('data-' + key) || '');
                    if (actual !== wanted) {
                        matchesFilters = false;
                    }
                });

                var show = matchesQuery && matchesFilters;
                item.hidden = !show;
                item.setAttribute('aria-hidden', show ? 'false' : 'true');
                if (show) {
                    visible += 1;
                }
            });

            if (empty) {
                empty.hidden = visible > 0;
            }
            if (count) {
                count.textContent = String(visible);
            }
        }

        if (input) {
            input.addEventListener('input', applyFilter);
            input.addEventListener('search', applyFilter);
        }

        filters.forEach(function (el) {
            el.addEventListener('change', applyFilter);
            el.addEventListener('input', applyFilter);
        });

        applyFilter();
    }

    function init() {
        document.querySelectorAll('[data-live-search]').forEach(bindLiveSearch);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
