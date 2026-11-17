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
            clone.querySelectorAll('input, textarea').forEach(function (field) {
                field.value = '';
            });
            wrap.appendChild(clone);
        });
    }

    function bindObjectiveRows(form) {
        var wrap = form.querySelector('[data-objective-rows]');
        var addBtn = form.querySelector('[data-add-objective-point]');
        if (!wrap || !addBtn) return;

        addBtn.addEventListener('click', function () {
            var row = wrap.querySelector('[data-objective-row]');
            if (!row) return;
            var clone = row.cloneNode(true);
            clone.querySelectorAll('textarea').forEach(function (field) {
                field.value = '';
            });
            wrap.appendChild(clone);
        });
    }

    function median(values) {
        var sorted = values.slice().sort(function (a, b) { return a - b; });
        var middle = Math.floor(sorted.length / 2);

        return sorted.length % 2 === 1
            ? sorted[middle]
            : Math.round((sorted[middle - 1] + sorted[middle]) / 2);
    }

    /**
     * Reads the colour the cover's own border is painted with, the same spots the server samples, so a
     * freshly chosen picture starts the page off in its own colour instead of the usual grey band.
     */
    function colorFromCover(file, callback) {
        var url = URL.createObjectURL(file);
        var image = new Image();

        image.onload = function () {
            URL.revokeObjectURL(url);

            var width = image.naturalWidth;
            var height = image.naturalHeight;
            if (!width || !height) return;

            var canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;

            var context = canvas.getContext('2d');
            if (!context) return;
            context.drawImage(image, 0, 0);

            var spots = [];
            for (var step = 1; step <= 6; step++) {
                var x = Math.min(width - 1, Math.floor(width * step / 7));
                var y = Math.min(height - 1, Math.floor(height * step / 7));
                spots.push([x, 0], [x, height - 1], [0, y], [width - 1, y]);
            }

            var reds = [], greens = [], blues = [];
            spots.forEach(function (spot) {
                var pixel = context.getImageData(spot[0], spot[1], 1, 1).data;
                if (pixel[3] < 64) return;
                reds.push(pixel[0]);
                greens.push(pixel[1]);
                blues.push(pixel[2]);
            });

            if (reds.length < 8) return;

            callback('#' + [median(reds), median(greens), median(blues)]
                .map(function (value) { return ('0' + value.toString(16)).slice(-2); })
                .join(''));
        };

        image.src = url;
    }

    function bindPageColour(form) {
        var picker = form.querySelector('[data-study-page-colour]');
        var cover = form.querySelector('input[name="cover_image"][type="file"]');
        if (!picker || !cover) return;

        cover.addEventListener('change', function () {
            var file = cover.files && cover.files[0];
            if (file) colorFromCover(file, function (hex) { picker.value = hex; });
        });
    }

    document.querySelectorAll('[data-research-activity-form]').forEach(function (form) {
        var schedule = form.querySelector('[data-research-schedule]');
        if (schedule) {
            bindSchedule(schedule);
            bindStatusLock(schedule);
        }
        bindDocRows(form);
        bindObjectiveRows(form);
        bindPageColour(form);
    });
})();
