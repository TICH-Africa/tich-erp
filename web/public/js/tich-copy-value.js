// Copy buttons for the paybill numbers: the value lives in the element named by
// data-copy-target, and the button says "Copied" so a student knows it took.
(function () {
    'use strict';

    function label(button, text) {
        button.dataset.originalLabel = button.dataset.originalLabel || button.textContent;
        button.textContent = text;
        window.setTimeout(function () {
            button.textContent = button.dataset.originalLabel;
        }, 1800);
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-copy-target]');
        if (! button) {
            return;
        }

        var source = document.getElementById(button.dataset.copyTarget);
        if (! source) {
            return;
        }

        var value = source.textContent.trim();

        navigator.clipboard.writeText(value).then(
            function () { label(button, 'Copied'); },
            function () { label(button, 'Copy failed'); }
        );
    });
})();
