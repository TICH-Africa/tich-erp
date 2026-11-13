@php
    $picked = collect(old('student_ids', request('student_id') ? [request('student_id')] : []))
        ->map(fn ($id) => (int) $id)
        ->filter()
        ->all();
    $pickerError = $errors->first('student_ids') ?: $errors->first('student_ids.*');
@endphp

<div class="uf-field uf-students" id="student-picker">
    <div class="uf-students__head">
        <label for="student_search">Students <span class="uf-req">*</span></label>
        <span class="uf-students__count" data-student-count>{{ count($picked) }} selected</span>
    </div>

    <input
        type="search"
        id="student_search"
        class="uf-students__search"
        placeholder="Search by name or student ID…"
        autocomplete="off"
        data-student-filter
    >

    <div class="uf-students__tools">
        <button type="button" class="uf-students__tool" data-student-select="shown">Select all shown</button>
        <button type="button" class="uf-students__tool" data-student-select="clear">Clear</button>
    </div>

    <div class="uf-students__list">
        @forelse ($students as $student)
            @php($reg = trim((string) $student->registration_number))
            @php($label = $reg.($student->displayName() !== $reg ? ' - '.$student->displayName() : ''))
            @php($credit = (float) ($studentCredits[$student->id] ?? 0))
            <label
                class="uf-students__row"
                data-search="{{ mb_strtolower($label.' '.($student->program?->program_name ?? '')) }}"
            >
                <input
                    type="checkbox"
                    name="student_ids[]"
                    value="{{ $student->id }}"
                    data-credit="{{ $credit }}"
                    @checked(in_array((int) $student->id, $picked, true))
                >
                <span class="uf-students__name">{{ $label }}</span>
                @if ($student->relationLoaded('program') && $student->program)
                    <span class="uf-students__meta">{{ $student->program->program_name }}</span>
                @endif
                @if ($credit > 0)
                    <span class="uf-students__credit">KES {{ number_format($credit, 2) }} in credit</span>
                @endif
            </label>
        @empty
            <p class="uf-students__empty">No students on record yet.</p>
        @endforelse
    </div>

    @if ($pickerError)
        <span class="uf-error">{{ $pickerError }}</span>
    @endif
    <span class="uf-hint">Tick every student this bill applies to — each one gets their own invoice in the same press.</span>
</div>

<script>
    (function () {
        var picker = document.getElementById('student-picker');
        if (!picker) return;

        var rows = [].slice.call(picker.querySelectorAll('.uf-students__row'));
        var boxes = [].slice.call(picker.querySelectorAll('input[name="student_ids[]"]'));
        var filter = picker.querySelector('[data-student-filter]');
        var counter = picker.querySelector('[data-student-count]');

        function shownRows() {
            return rows.filter(function (row) { return row.hidden !== true && row.style.display !== 'none'; });
        }

        function syncCount() {
            var picked = boxes.filter(function (box) { return box.checked; }).length;
            counter.textContent = picked + ' selected';
        }

        filter?.addEventListener('input', function () {
            var term = filter.value.trim().toLowerCase();
            rows.forEach(function (row) {
                row.style.display = !term || (row.dataset.search || '').indexOf(term) > -1 ? '' : 'none';
            });
        });

        picker.querySelectorAll('[data-student-select]').forEach(function (button) {
            button.addEventListener('click', function () {
                var targets = 'clear' === button.dataset.studentSelect ? boxes : shownRows().map(function (row) {
                    return row.querySelector('input[type="checkbox"]');
                });
                var state = 'clear' !== button.dataset.studentSelect;
                targets.forEach(function (box) { if (box) box.checked = state; });
                syncCount();
            });
        });

        picker.addEventListener('change', syncCount);
        syncCount();
    })();
</script>
