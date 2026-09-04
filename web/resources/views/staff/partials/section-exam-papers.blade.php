<x-page-toolbar title="Exam papers" meta="Draft, table, and moderate examination papers" />

@php
    $examPapers = $examPapers ?? collect();
    $canModerateExamPapers = $canModerateExamPapers ?? false;
@endphp

<div class="tich-grid tich-grid--2 tich-mt-6" style="align-items:start; gap:1.5rem;">
    <article class="tich-card">
        <h2 class="tich-h3">Submit draft paper</h2>
        @if ($portalData['allocations']->isEmpty())
            <p class="tich-text tich-mt-4">No units assigned. Allocations are required before you can upload a paper.</p>
        @else
            <form method="POST" action="{{ route('staff.exam-papers.store') }}" enctype="multipart/form-data" class="tich-mt-4">
                @csrf
                <div class="tich-form-group">
                    <label class="tich-label">Unit / semester</label>
                    <select name="allocation_key" id="exam-paper-allocation" class="tich-input" required>
                        @foreach ($portalData['allocations'] as $allocation)
                            <option
                                value="{{ $allocation->unit_id }}:{{ $allocation->semester_id }}"
                                data-unit="{{ $allocation->unit_id }}"
                                data-semester="{{ $allocation->semester_id }}"
                                @selected(old('unit_id') == $allocation->unit_id && old('semester_id') == $allocation->semester_id)
                            >
                                {{ $allocation->unit?->unit_code }} — {{ $allocation->unit?->unit_name }}
                                ({{ $allocation->semester?->semester_label ?? 'Semester' }})
                            </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="unit_id" id="exam-paper-unit-id" value="{{ old('unit_id', $portalData['allocations']->first()?->unit_id) }}">
                    <input type="hidden" name="semester_id" id="exam-paper-semester-id" value="{{ old('semester_id', $portalData['allocations']->first()?->semester_id) }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Exam type</label>
                    <select name="exam_type" class="tich-input" required>
                        @foreach (\App\Models\ExaminationPaper::EXAM_TYPES as $value => $label)
                            <option value="{{ $value }}" @selected(old('exam_type', 'main') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Version</label>
                    <select name="version" class="tich-input" required>
                        @foreach (\App\Models\ExaminationPaper::VERSIONS as $version)
                            <option value="{{ $version }}" @selected(old('version', 'A') === $version)>{{ $version }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Draft file (PDF/DOC)</label>
                    <input type="file" name="draft_file" class="tich-input" accept=".pdf,.doc,.docx" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" style="display:flex; gap:0.5rem; align-items:center;">
                        <input type="hidden" name="auto_table" value="0">
                        <input type="checkbox" name="auto_table" value="1" @checked(old('auto_table', '1') == '1')>
                        Table for moderation immediately
                    </label>
                </div>
                <button type="submit" class="tich-btn tich-btn-primary">Upload paper</button>
            </form>
            <script>
                (function () {
                    const select = document.getElementById('exam-paper-allocation');
                    const unitInput = document.getElementById('exam-paper-unit-id');
                    const semesterInput = document.getElementById('exam-paper-semester-id');
                    if (!select) return;
                    const sync = () => {
                        const option = select.options[select.selectedIndex];
                        unitInput.value = option.dataset.unit || '';
                        semesterInput.value = option.dataset.semester || '';
                    };
                    select.addEventListener('change', sync);
                    sync();
                })();
            </script>
        @endif
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Your papers</h2>
        @if ($examPapers->isEmpty())
            <p class="tich-text tich-mt-4">No examination papers yet.</p>
        @else
            <div class="tich-table-panel tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Type</th>
                            <th>Ver</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($examPapers as $paper)
                            <tr>
                                <td>
                                    <strong>{{ $paper->unit?->unit_code }}</strong>
                                    <div class="tich-caption">{{ $paper->semester?->semester_label }}</div>
                                </td>
                                <td class="tich-caption">{{ ucfirst($paper->exam_type) }}</td>
                                <td>{{ $paper->version }}</td>
                                <td>{{ $paper->statusLabel() }}</td>
                                <td style="display:flex; flex-wrap:wrap; gap:0.35rem;">
                                    @if ($paper->draft_file_path)
                                        <a href="{{ route('staff.exam-papers.download', ['examinationPaper' => $paper->id, 'kind' => 'draft']) }}" class="tich-btn tich-btn-ghost">Draft</a>
                                    @endif
                                    @if ($paper->status === 'draft' && (int) $paper->prepared_by === (int) $staff->id)
                                        <form method="POST" action="{{ route('staff.exam-papers.table', $paper) }}">
                                            @csrf
                                            <button type="submit" class="tich-btn tich-btn-secondary">Table</button>
                                        </form>
                                    @endif
                                    @if ($canModerateExamPapers && $paper->status === 'tabled')
                                        <form method="POST" action="{{ route('staff.exam-papers.moderate', $paper) }}" enctype="multipart/form-data" style="display:flex; gap:0.35rem; align-items:center;">
                                            @csrf
                                            <input type="file" name="moderated_file" class="tich-input" style="max-width:10rem;" accept=".pdf,.doc,.docx">
                                            <button type="submit" class="tich-btn tich-btn-primary">Moderate</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </article>
</div>
