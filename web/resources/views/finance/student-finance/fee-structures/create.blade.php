@extends('layouts.finance')

@section('title', 'New Fee Structure')

@section('finance-content')
    <x-page-toolbar title="New Fee Structure" meta="Create a new fee structure for a program, academic year, and semester">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.fee-structures.index', ['department' => $department->id]) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card">
        <form method="POST" action="{{ route('finance.student-finance.fee-structures.store', ['department' => $department->id]) }}" class="tich-mt-4">
            @csrf
            <div class="tich-form-grid tich-form-grid--2">
                <div class="tich-form-group">
                    <label class="tich-label">Program</label>
                    <select name="program_id" id="program_id" class="tich-input" required>
                        <option value="">Loading programs...</option>
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Academic Year</label>
                    <select name="academic_year_id" id="academic_year_id" class="tich-input" required>
                        <option value="">Loading academic years...</option>
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Effective From</label>
                    <input type="date" name="effective_from" class="tich-input" required />
                </div>
            </div>

            <div class="tich-form-toolbar tich-mt-6">
                <h3 class="tich-h4">Fee Breakdown</h3>
                <p class="tich-caption">Set individual fee components for this semester.</p>
            </div>

            <div class="tich-form-grid tich-form-grid--2 tich-mt-4">
                <div class="tich-form-group">
                    <label class="tich-label">Application Fee</label>
                    <input type="number" name="application_fee" class="tich-input" step="0.01" value="0.00" />
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Tuition Fee</label>
                    <input type="number" name="tuition_fee" class="tich-input" step="0.01" required placeholder="0.00" />
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Cautions Fee</label>
                    <input type="number" name="cautions_fee" class="tich-input" step="0.01" value="0.00" />
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Computer Lab Fee</label>
                    <input type="number" name="computer_lab_fee" class="tich-input" step="0.01" value="0.00" />
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Accommodation Fee</label>
                    <input type="number" name="accommodation_fee" class="tich-input" step="0.01" value="0.00" />
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Transport Fee</label>
                    <input type="number" name="transport_fee" class="tich-input" step="0.01" value="0.00" />
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Partnership Fee</label>
                    <input type="number" name="partnership_fee" class="tich-input" step="0.01" value="0.00" />
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">ID Card Fee</label>
                    <input type="number" name="id_card_fee" class="tich-input" step="0.01" value="0.00" />
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Student Union Fee</label>
                    <input type="number" name="student_union_fee" class="tich-input" step="0.01" value="0.00" />
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Quality Assurance Fee</label>
                    <input type="number" name="quality_assurance_fee" class="tich-input" step="0.01" value="0.00" />
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Emergency Fund Fee</label>
                    <input type="number" name="emergency_fund_fee" class="tich-input" step="0.01" value="0.00" />
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Library Fee</label>
                    <input type="number" name="library_fee" class="tich-input" step="0.01" value="0.00" />
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Indexing (NCK) Fee</label>
                    <input type="number" name="indexing_nck_fee" class="tich-input" step="0.01" value="0.00" />
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Examination Fee</label>
                    <input type="number" name="examination_external_fee" class="tich-input" step="0.01" value="0.00" />
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Attachment Fee</label>
                    <input type="number" name="attachment_fee" class="tich-input" step="0.01" value="0.00" />
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Graduation Fee</label>
                    <input type="number" name="graduation_fee" class="tich-input" step="0.01" value="0.00" />
                </div>
            </div>

            <div class="tich-form-group tich-mt-4">
                <button type="submit" class="tich-btn tich-btn-primary">Create fee structure</button>
                <a href="{{ route('finance.student-finance.fee-structures.index', $department) }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const programSelect = document.getElementById('program_id');
    const yearSelect = document.getElementById('academic_year_id');

    function loadPrograms() {
        fetch('{{ route('finance.api.programs') }}')
            .then(response => response.json())
            .then(data => {
                programSelect.innerHTML = '<option value="">Select program</option>';
                data.forEach(function(program) {
                    const option = document.createElement('option');
                    option.value = program.id;
                    option.textContent = program.program_name + ' (' + program.program_code + ')';
                    programSelect.appendChild(option);
                });
            })
            .catch(() => {
                programSelect.innerHTML = '<option value="">Failed to load programs</option>';
            });
    }

    function loadAcademicYears() {
        fetch('{{ route('finance.api.academic-years') }}')
            .then(response => response.json())
            .then(data => {
                yearSelect.innerHTML = '<option value="">Select academic year</option>';
                data.forEach(function(year) {
                    const option = document.createElement('option');
                    option.value = year.id;
                    option.textContent = year.year_label;
                    yearSelect.appendChild(option);
                });
            })
            .catch(() => {
                yearSelect.innerHTML = '<option value="">Failed to load academic years</option>';
            });
    }

    loadPrograms();
    loadAcademicYears();
});
</script>
@endsection
