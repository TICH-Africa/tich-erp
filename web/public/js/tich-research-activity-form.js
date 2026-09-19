(function () {
    function pad(n) {
        return n < 10 ? '0' + n : String(n);
    }

    function toInputDate(d) {
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    }

    function addDuration(startStr, value, unit) {
        if (!startStr || !value) return null;
        var parts = startStr.split('-').map(Number);
        if (parts.length !== 3 || parts.some(isNaN)) return null;
        var d = new Date(parts[0], parts[1] - 1, parts[2]);
        var n = parseInt(value, 10);
        if (!n || n < 1) return null;
        if (unit === 'days') d.setDate(d.getDate() + n);
        else if (unit === 'weeks') d.setDate(d.getDate() + n * 7);
        else if (unit === 'months') d.setMonth(d.getMonth() + n);
        else if (unit === 'years') d.setFullYear(d.getFullYear() + n);
        else d.setDate(d.getDate() + n);
        return toInputDate(d);
    }

    function bindSchedule(root) {
        var start = root.querySelector('[data-start-date]');
        var value = root.querySelector('[data-duration-value]');
        var unit = root.querySelector('[data-duration-unit]');
        var end = root.querySelector('[data-end-date]');
        if (!start || !value || !unit || !end) return;

        var endTouched = false;
        end.addEventListener('change', function () {
            endTouched = true;
        });

        function recalc() {
            if (endTouched && end.value) return;
            var next = addDuration(start.value, value.value, unit.value);
            if (next) end.value = next;
        }

        start.addEventListener('change', function () {
            endTouched = false;
            recalc();
        });
        value.addEventListener('input', function () {
            endTouched = false;
            recalc();
        });
        unit.addEventListener('change', function () {
            endTouched = false;
            recalc();
        });

        if (!end.value) recalc();
    }

    function bindStatusLock(root) {
        var lock = root.querySelector('[data-status-locked]');
        var select = root.querySelector('[data-status-select]');
        if (!lock || !select) return;

        function sync() {
            select.style.opacity = lock.checked ? '1' : '0.55';
            select.style.pointerEvents = lock.checked ? 'auto' : 'none';
        }
        lock.addEventListener('change', sync);
        sync();
    }

    function bindDocRows(form) {
        var wrap = form.querySelector('[data-doc-rows]');
        var addBtn = form.querySelector('[data-add-doc-row]');
        if (!wrap || !addBtn) return;

        addBtn.addEventListener('click', function () {
            var row = wrap.querySelector('[data-doc-row]');
            if (!row) return;
            var clone = row.cloneNode(true);
            clone.querySelectorAll('input').forEach(function (input) {
                input.value = '';
            });
            wrap.appendChild(clone);
        });
    }

    document.querySelectorAll('[data-research-activity-form]').forEach(function (form) {
        var schedule = form.querySelector('[data-research-schedule]');
        if (schedule) {
            bindSchedule(schedule);
            bindStatusLock(schedule);
        }
        bindDocRows(form);
    });
})();
