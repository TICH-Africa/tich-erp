@php
    $specialExamRequests = $specialExamRequests ?? collect();
    $supplementaryExamRequests = $supplementaryExamRequests ?? collect();
    $registeredUnits = $portalData['academics']['registered_units'] ?? collect();
    $applyType = request()->query('apply');
    $prefillUnitId = (string) request()->query('unit_id', old('unit_id', ''));
    $prefillSemesterId = (string) request()->query('semester_id', old('semester_id', ''));
    $openExamRequestModal = ($errors->any() && old('_form') === 'exam-sitting')
        || in_array($applyType, ['supplementary', 'special_exam'], true);
@endphp

<x-page-toolbar title="Supplementary &amp; Special Exams" meta="Apply for supplementary or special exam sittings">
    <x-slot:actions>
        <button type="button" class="tich-btn tich-btn-primary" data-open-modal="exam-sitting-request-modal">
            New application
        </button>
    </x-slot:actions>
</x-page-toolbar>

<section class="tich-portal-panel tich-mt-8">
    <div class="tich-portal-panel__head">
        <h2 class="tich-h3">Special exam requests</h2>
    </div>
    <div class="tich-card tich-table-panel tich-mt-4">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($specialExamRequests as $item)
                        <tr>
                            <td>{{ $item->unit?->unit_code ?? '-' }} {{ $item->unit?->unit_name }}</td>
                            <td>{{ $item->statusLabel() }}</td>
                            <td class="tich-caption">{{ $item->created_at?->format('d M Y') }}</td>
                            <td class="tich-caption">{{ $item->reviewed_notes ?: \Illuminate\Support\Str::limit($item->reason, 80) }}</td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 4, 'title' => 'No special exam requests', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="tich-portal-panel tich-mt-8">
    <div class="tich-portal-panel__head">
        <h2 class="tich-h3">Supplementary requests</h2>
    </div>
    <div class="tich-card tich-table-panel tich-mt-4">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>Status</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($supplementaryExamRequests as $item)
                        <tr>
                            <td>{{ $item->unit?->unit_code ?? '-' }} {{ $item->unit?->unit_name }}</td>
                            <td>{{ $item->statusLabel() }}</td>
                            <td class="tich-caption">{{ $item->created_at?->format('d M Y') }}</td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 3, 'title' => 'No supplementary requests', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

<div
    id="exam-sitting-request-modal"
    class="tich-modal{{ $openExamRequestModal ? ' is-open' : '' }}"
    aria-hidden="{{ $openExamRequestModal ? 'false' : 'true' }}"
    role="dialog"
    aria-modal="true"
    aria-labelledby="exam-sitting-request-modal-title"
>
    <div class="tich-modal__backdrop" data-close-modal="exam-sitting-request-modal"></div>
    <div class="tich-modal__dialog" style="max-width: 32rem;">
        <header class="tich-modal__header">
            <h2 id="exam-sitting-request-modal-title" class="tich-h3" style="margin:0;">Exam application</h2>
            <button type="button" class="tich-modal__close" data-close-modal="exam-sitting-request-modal" aria-label="Close">&times;</button>
        </header>
        <form method="POST" action="{{ route('portal.exam-sitting-requests.store') }}" enctype="multipart/form-data" class="tich-modal__body">
            @csrf
            <input type="hidden" name="_form" value="exam-sitting">

            @if ($errors->any() && old('_form') === 'exam-sitting')
                <div class="tich-modal__errors tich-mb-4">
                    <ul style="margin:0; padding-left:1.25rem;">
                        @foreach ($errors->all() as $error)
                            <li class="tich-text">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div style="display:grid; gap:1rem; margin-top:0.25rem;">
                <div class="tich-form-group" style="margin:0;">
                    <label for="exam_request_type" class="tich-label">Application type</label>
                    <select id="exam_request_type" name="request_type" class="tich-select" required>
                        <option value="supplementary" @selected(old('request_type', $applyType === 'special_exam' ? 'special_exam' : 'supplementary') === 'supplementary')>Supplementary exam</option>
                        <option value="special_exam" @selected(old('request_type', $applyType) === 'special_exam')>Special exam</option>
                    </select>
                </div>

                <div class="tich-form-group" style="margin:0;">
                    <label for="exam_unit_semester" class="tich-label">Unit</label>
                    @if ($registeredUnits->isEmpty())
                        <p class="tich-caption">No registered units found. Enter unit and semester IDs.</p>
                        <div class="tich-grid tich-grid--2" style="gap:0.75rem;">
                            <input type="number" name="unit_id" class="tich-input" placeholder="Unit ID" value="{{ old('unit_id', $prefillUnitId) }}" required>
                            <input type="number" name="semester_id" class="tich-input" placeholder="Semester ID" value="{{ old('semester_id', $prefillSemesterId) }}" required>
                        </div>
                    @else
                        <select id="exam_unit_semester" name="unit_semester" class="tich-select" required>
                            @foreach ($registeredUnits as $unit)
                                @php
                                    $selected = (string) old('unit_id', $prefillUnitId) === (string) $unit->unit_id
                                        && (string) old('semester_id', $prefillSemesterId) === (string) $unit->semester_id;
                                @endphp
                                <option value="{{ $unit->unit_id }}:{{ $unit->semester_id }}" @selected($selected)>
                                    {{ $unit->unit_code }} — {{ $unit->unit_name }} ({{ $unit->semester_label }})
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="unit_id" id="exam-req-unit-id" value="{{ old('unit_id', $prefillUnitId ?: ($registeredUnits->first()->unit_id ?? '')) }}">
                        <input type="hidden" name="semester_id" id="exam-req-semester-id" value="{{ old('semester_id', $prefillSemesterId ?: ($registeredUnits->first()->semester_id ?? '')) }}">
                    @endif
                </div>

                <div class="tich-form-group" id="exam-special-reason-wrap" style="margin:0;">
                    <label for="exam_reason" class="tich-label">Reason for missing the exam</label>
                    <textarea id="exam_reason" name="reason" rows="4" class="tich-input">{{ old('reason') }}</textarea>
                </div>

                <div class="tich-form-group" id="exam-special-attachments-wrap" style="margin:0;">
                    <label for="exam_attachments" class="tich-label">Supporting document(s)</label>
                    <input id="exam_attachments" name="attachments[]" type="file" class="tich-input" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" multiple>
                    <p class="tich-caption">Required for special exams. PDF, Word, or image. Max 10&nbsp;MB each.</p>
                </div>
            </div>

            <footer class="tich-modal__footer">
                <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="exam-sitting-request-modal">Cancel</button>
                <button type="submit" class="tich-btn tich-btn-primary">Submit application</button>
            </footer>
        </form>
    </div>
</div>

@include('admin.partials.tich-modal-assets')

<script>
    (function () {
        const typeSelect = document.getElementById('exam_request_type');
        const reasonWrap = document.getElementById('exam-special-reason-wrap');
        const attachWrap = document.getElementById('exam-special-attachments-wrap');
        const reasonInput = document.getElementById('exam_reason');
        const attachInput = document.getElementById('exam_attachments');
        const select = document.getElementById('exam_unit_semester');
        const unitInput = document.getElementById('exam-req-unit-id');
        const semesterInput = document.getElementById('exam-req-semester-id');

        const syncType = () => {
            const isSpecial = typeSelect && typeSelect.value === 'special_exam';
            if (reasonWrap) reasonWrap.style.display = isSpecial ? '' : 'none';
            if (attachWrap) attachWrap.style.display = isSpecial ? '' : 'none';
            if (reasonInput) reasonInput.required = !!isSpecial;
            if (attachInput) attachInput.required = !!isSpecial;
        };

        if (typeSelect) {
            typeSelect.addEventListener('change', syncType);
            syncType();
        }

        if (select && unitInput && semesterInput) {
            const syncUnit = () => {
                const [unitId, semesterId] = (select.value || '').split(':');
                unitInput.value = unitId || '';
                semesterInput.value = semesterId || '';
            };
            select.addEventListener('change', syncUnit);
            if (!unitInput.value || !semesterInput.value) {
                syncUnit();
            } else {
                const match = Array.from(select.options).find((opt) => opt.value === `${unitInput.value}:${semesterInput.value}`);
                if (match) select.value = match.value;
                else syncUnit();
            }
        }
    })();
</script>
