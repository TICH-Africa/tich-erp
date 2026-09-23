(function () {
    'use strict';

    function debounce(fn, wait) {
        var timer = null;
        return function () {
            var ctx = this;
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(ctx, args);
            }, wait);
        };
    }

    function bindLiveFilter(form) {
        if (!form || form.dataset.liveFilterBound === '1') {
            return;
        }
        form.dataset.liveFilterBound = '1';

        var submitLive = debounce(function () {
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        }, 320);

        form.querySelectorAll('input[type="search"], input[type="text"], input:not([type])').forEach(function (input) {
            input.addEventListener('input', submitLive);
        });

        form.querySelectorAll('select').forEach(function (select) {
            select.addEventListener('change', function () {
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            });
        });
    }

    function init() {
        document.querySelectorAll('form[data-live-filter]').forEach(bindLiveFilter);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
