(function () {
    function qs(root, selector) {
        return root.querySelector(selector);
    }

    function qsa(root, selector) {
        return Array.prototype.slice.call(root.querySelectorAll(selector));
    }

    function reindexRows(tableBody) {
        qsa(tableBody, '[data-lesson-plan-row]').forEach(function (row, index) {
            qsa(row, 'textarea[name^="session_rows"]').forEach(function (input) {
                var match = input.name.match(/session_rows\[\d+]\[(.+)]/);
                if (match) {
                    input.name = 'session_rows[' + index + '][' + match[1] + ']';
                }
            });
        });

        var rows = qsa(tableBody, '[data-lesson-plan-row]');
        rows.forEach(function (row) {
            var removeBtn = qs(row, '[data-lesson-plan-remove-row]');
            if (removeBtn) {
                removeBtn.hidden = rows.length <= 1;
            }
        });
    }

    function bindRowActions(form) {
        var tableBody = qs(form, '[data-lesson-plan-rows] tbody');
        var template = document.getElementById('lesson-plan-row-template');

        if (! tableBody || ! template) {
            return;
        }

        form.addEventListener('click', function (event) {
            if (event.target.matches('[data-lesson-plan-add-row]')) {
                var nextIndex = qsa(tableBody, '[data-lesson-plan-row]').length;
                var html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex));
                tableBody.insertAdjacentHTML('beforeend', html);
                reindexRows(tableBody);
            }

            if (event.target.matches('[data-lesson-plan-remove-row]')) {
                var row = event.target.closest('[data-lesson-plan-row]');
                if (! row) {
                    return;
                }

                if (qsa(tableBody, '[data-lesson-plan-row]').length <= 1) {
                    return;
                }

                row.remove();
                reindexRows(tableBody);
            }
        });
    }

    function setFieldValue(form, selector, value) {
        var input = qs(form, selector);
        if (! input || value === null || value === undefined || value === '') {
            return;
        }

        input.value = value;
    }

    function applyContext(form, data) {
        setFieldValue(form, '[data-lesson-plan-week]', data.week_number);
        setFieldValue(form, '[data-lesson-plan-session-time]', data.session_time);
        setFieldValue(form, '[data-lesson-plan-intake]', data.intake_class);
        setFieldValue(form, '[data-lesson-plan-venue]', data.venue);
        setFieldValue(form, '[data-lesson-plan-hours]', data.contact_hours);
    }

    function fetchContext(form) {
        var allocationField = qs(form, '[data-lesson-plan-allocation]')
            || qs(form, 'select[name="allocation_id"]')
            || qs(form, 'input[name="allocation_id"]');
        var dateInput = qs(form, '[data-lesson-plan-date]');
        var contextUrl = form.getAttribute('data-context-url');

        if (! allocationField || ! contextUrl) {
            return;
        }
        var allocationId = allocationField.value;
        if (! allocationId) {
            return;
        }

        var url = new URL(contextUrl, window.location.origin);
        url.searchParams.set('allocation_id', allocationId);

        if (dateInput && dateInput.value) {
            url.searchParams.set('planned_date', dateInput.value);
        }

        fetch(url.toString(), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data && data.defaults) {
                    applyContext(form, data.defaults);
                }
            })
            .catch(function () {
                // Prefill is best-effort; tutors can still edit manually.
            });
    }

    function bindContextRefresh(form) {
        var allocationField = qs(form, '[data-lesson-plan-allocation]')
            || qs(form, 'select[name="allocation_id"]')
            || qs(form, 'input[name="allocation_id"]');
        var dateInput = qs(form, '[data-lesson-plan-date]');

        if (allocationField) {
            allocationField.addEventListener('change', function () {
                fetchContext(form);
            });
        }

        if (dateInput) {
            dateInput.addEventListener('change', function () {
                fetchContext(form);
            });
        }

        if (form.getAttribute('data-autofill-context') === '1') {
            fetchContext(form);
        }
    }

    function initForm(form) {
        bindRowActions(form);
        bindContextRefresh(form);
    }

    function init() {
        document.querySelectorAll('form[data-context-url]').forEach(initForm);
        document.querySelectorAll('[data-lesson-plan-form]').forEach(function (inner) {
            var form = inner.closest('form');
            if (form && ! form.hasAttribute('data-context-url')) {
                initForm(form);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
