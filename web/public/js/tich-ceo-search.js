(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    ready(function () {
        var root = document.querySelector('[data-ceo-search]');
        if (!root || root.dataset.bound === '1') {
            return;
        }
        root.dataset.bound = '1';

        var url = root.getAttribute('data-search-url');
        var input = root.querySelector('.ceo-search__input');
        var results = root.querySelector('.ceo-search__results');
        var kbd = root.querySelector('.ceo-search__kbd');
        if (!url || !input || !results) {
            return;
        }

        var timer = null;
        var abortController = null;
        var activeIndex = -1;
        var items = [];

        function setOpen(open) {
            results.hidden = !open;
            input.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (kbd) {
                kbd.hidden = open || input.value.length > 0;
            }
        }

        function renderStatus(message) {
            results.innerHTML = '<div class="ceo-search__status">' + escapeHtml(message) + '</div>';
            items = [];
            activeIndex = -1;
            setOpen(true);
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function highlightActive() {
            var nodes = results.querySelectorAll('[data-ceo-search-item]');
            nodes.forEach(function (node, index) {
                node.classList.toggle('is-active', index === activeIndex);
            });
            if (activeIndex >= 0 && nodes[activeIndex]) {
                nodes[activeIndex].scrollIntoView({ block: 'nearest' });
            }
        }

        function renderResults(payload) {
            var list = Array.isArray(payload.results) ? payload.results : [];
            if (list.length === 0) {
                renderStatus('No matches for “' + (payload.query || input.value) + '”.');
                return;
            }

            var grouped = {};
            list.forEach(function (row) {
                var key = row.category || 'Results';
                if (!grouped[key]) {
                    grouped[key] = [];
                }
                grouped[key].push(row);
            });

            var html = '';
            Object.keys(grouped).forEach(function (category) {
                html += '<div class="ceo-search__group">' + escapeHtml(category) + '</div>';
                grouped[category].forEach(function (row) {
                    html +=
                        '<a class="ceo-search__item" role="option" href="' + escapeHtml(row.href) + '" data-ceo-search-item>' +
                        '<p class="ceo-search__item-title">' + escapeHtml(row.title) + '</p>' +
                        (row.subtitle ? '<p class="ceo-search__item-sub">' + escapeHtml(row.subtitle) + '</p>' : '') +
                        '</a>';
                });
            });

            results.innerHTML = html;
            items = Array.prototype.slice.call(results.querySelectorAll('[data-ceo-search-item]'));
            activeIndex = items.length ? 0 : -1;
            highlightActive();
            setOpen(true);
        }

        function runSearch(raw) {
            var query = String(raw || '').trim();
            if (query.length < 2) {
                results.innerHTML = '';
                items = [];
                activeIndex = -1;
                setOpen(false);
                return;
            }

            renderStatus('Searching…');

            if (abortController) {
                abortController.abort();
            }
            abortController = new AbortController();

            fetch(url + '?q=' + encodeURIComponent(query), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                signal: abortController.signal,
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Search failed');
                    }
                    return response.json();
                })
                .then(renderResults)
                .catch(function (error) {
                    if (error && error.name === 'AbortError') {
                        return;
                    }
                    renderStatus('Search unavailable right now.');
                });
        }

        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                runSearch(input.value);
            }, 220);
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                setOpen(false);
                input.blur();
                return;
            }

            if (!items.length || results.hidden) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                activeIndex = (activeIndex + 1) % items.length;
                highlightActive();
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                activeIndex = (activeIndex - 1 + items.length) % items.length;
                highlightActive();
            } else if (event.key === 'Enter' && activeIndex >= 0 && items[activeIndex]) {
                event.preventDefault();
                window.location.href = items[activeIndex].getAttribute('href');
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== '/' || event.ctrlKey || event.metaKey || event.altKey) {
                return;
            }
            var tag = (event.target && event.target.tagName) || '';
            if (tag === 'INPUT' || tag === 'TEXTAREA' || event.target.isContentEditable) {
                return;
            }
            event.preventDefault();
            input.focus();
            input.select();
        });

        document.addEventListener('click', function (event) {
            if (!root.contains(event.target)) {
                setOpen(false);
            }
        });

        // Deep-link hash tabs from search results on the dashboard.
        if (window.location.hash && document.querySelector('[data-ceo-dash]')) {
            var hash = window.location.hash.replace('#', '');
            var tab = document.querySelector('[data-ceo-tab="' + hash + '"]');
            if (tab) {
                tab.click();
            }
        }
    });
})();
