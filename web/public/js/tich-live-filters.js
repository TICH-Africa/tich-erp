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

        function submitNow() {
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        }

        form.querySelectorAll('input[type="search"], input[type="text"], input[type="number"], input:not([type])').forEach(function (input) {
            input.addEventListener('input', submitLive);
            input.addEventListener('change', submitLive);
        });

        form.querySelectorAll('input[type="date"], select').forEach(function (el) {
            el.addEventListener('change', submitNow);
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
