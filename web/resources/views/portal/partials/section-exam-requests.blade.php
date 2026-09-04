@php
    $specialExamRequests = $specialExamRequests ?? collect();
    $supplementaryExamRequests = $supplementaryExamRequests ?? collect();
    $registeredUnits = $portalData['academics']['registered_units'] ?? collect();
@endphp

<x-page-toolbar title="Exam sitting requests" meta="Special and supplementary exam applications" />

<article class="tich-card tich-mt-8">
    <h2 class="tich-h3">Submit a request</h2>
    <form method="POST" action="{{ route('portal.exam-sitting-requests.store') }}" class="tich-form-stack tich-mt-4">
        @csrf
        <div>
            <label for="request_type" class="tich-label">Request type</label>
            <select id="request_type" name="request_type" class="tich-select" required>
                <option value="special_exam" @selected(old('request_type') === 'special_exam')>Special exam</option>
                <option value="supplementary" @selected(old('request_type') === 'supplementary')>Supplementary exam</option>
            </select>
        </div>
        <div>
            <label for="unit_semester" class="tich-label">Unit</label>
            @if ($registeredUnits->isEmpty())
                <p class="tich-caption">No registered units found. You can still enter IDs if known, or register units first.</p>
                <div class="tich-grid tich-grid--2" style="gap:0.75rem;">
                    <input type="number" name="unit_id" class="tich-input" placeholder="Unit ID" value="{{ old('unit_id') }}" required>
                    <input type="number" name="semester_id" class="tich-input" placeholder="Semester ID" value="{{ old('semester_id') }}" required>
                </div>
            @else
                <select id="unit_semester" name="unit_semester" class="tich-select" required>
                    @foreach ($registeredUnits as $unit)
                        <option
                            value="{{ $unit->unit_id }}:{{ $unit->semester_id }}"
                            @selected(old('unit_id') == $unit->unit_id && old('semester_id') == $unit->semester_id)
                        >
                            {{ $unit->unit_code }} — {{ $unit->unit_name }} ({{ $unit->semester_label }})
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="unit_id" id="exam-req-unit-id" value="{{ old('unit_id', $registeredUnits->first()->unit_id ?? '') }}">
                <input type="hidden" name="semester_id" id="exam-req-semester-id" value="{{ old('semester_id', $registeredUnits->first()->semester_id ?? '') }}">
            @endif
        </div>
        <div id="supplementary-type-wrap">
            <label for="supplementary_type" class="tich-label">Supplementary type</label>
            <select id="supplementary_type" name="supplementary_type" class="tich-select">
                @foreach (\App\Models\SupplementaryExamRequest::TYPES as $value => $label)
                    <option value="{{ $value }}" @selected(old('supplementary_type', 'theory') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="reason" class="tich-label">Reason</label>
            <textarea id="reason" name="reason" rows="5" class="tich-input" required>{{ old('reason') }}</textarea>
        </div>
        <button type="submit" class="tich-btn tich-btn-primary">Submit request</button>
    </form>
</article>

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
                        <th>Type</th>
                        <th>Status</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($supplementaryExamRequests as $item)
                        <tr>
                            <td>{{ $item->unit?->unit_code ?? '-' }} {{ $item->unit?->unit_name }}</td>
                            <td>{{ $item->typeLabel() }}</td>
                            <td>{{ $item->statusLabel() }}</td>
                            <td class="tich-caption">{{ $item->created_at?->format('d M Y') }}</td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 4, 'title' => 'No supplementary requests', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

@if ($registeredUnits->isNotEmpty())
<script>
    (function () {
        const select = document.getElementById('unit_semester');
        const unitInput = document.getElementById('exam-req-unit-id');
        const semesterInput = document.getElementById('exam-req-semester-id');
        const typeSelect = document.getElementById('request_type');
        const suppWrap = document.getElementById('supplementary-type-wrap');
        if (select) {
            const sync = () => {
                const [unitId, semesterId] = (select.value || '').split(':');
                unitInput.value = unitId || '';
                semesterInput.value = semesterId || '';
            };
            select.addEventListener('change', sync);
            sync();
        }
        if (typeSelect && suppWrap) {
            const syncType = () => {
                suppWrap.style.display = typeSelect.value === 'supplementary' ? '' : 'none';
            };
            typeSelect.addEventListener('change', syncType);
            syncType();
        }
    })();
</script>
@endif
