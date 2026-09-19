@extends('layouts.finance')

@section('title', 'New Fee Structure')

@section('finance-content')
    <x-page-toolbar title="New Fee Structure" meta="Create a new fee structure for a program, academic year, and semester">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.fee-structures.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('finance.student-finance.fee-structures.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">FEE · Fee structure</div>
                    <div class="uf-amount-bar__sum">Programme fee breakdown</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Programme &amp; Academic Year</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="program_id">Program <span class="uf-req">*</span></label>
                            <select
                                name="program_id"
                                id="program_id"
                                required
                                class="{{ $errors->has('program_id') ? 'is-invalid' : '' }}"
                            >
                                <option value="">Loading programs...</option>
                            </select>
                            @error('program_id')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="academic_year_id">Academic Year <span class="uf-req">*</span></label>
                            <select
                                name="academic_year_id"
                                id="academic_year_id"
                                required
                                class="{{ $errors->has('academic_year_id') ? 'is-invalid' : '' }}"
                            >
                                <option value="">Loading academic years...</option>
                            </select>
                            @error('academic_year_id')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="effective_from">Effective From <span class="uf-req">*</span></label>
                            <input
                                type="date"
                                id="effective_from"
                                name="effective_from"
                                required
                                class="{{ $errors->has('effective_from') ? 'is-invalid' : '' }}"
                            />
                            @error('effective_from')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Fee Breakdown</div>
                <div class="uf-section-body">
                    <p class="uf-hint">Set individual fee components for this semester.</p>
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="application_fee">Application Fee</label>
                            <input type="number" name="application_fee" id="application_fee" step="0.01" value="0.00" class="{{ $errors->has('application_fee') ? 'is-invalid' : '' }}" />
                            @error('application_fee')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="tuition_fee">Tuition Fee <span class="uf-req">*</span></label>
                            <input type="number" name="tuition_fee" id="tuition_fee" step="0.01" required placeholder="0.00" class="{{ $errors->has('tuition_fee') ? 'is-invalid' : '' }}" />
                            @error('tuition_fee')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="cautions_fee">Cautions Fee</label>
                            <input type="number" name="cautions_fee" id="cautions_fee" step="0.01" value="0.00" class="{{ $errors->has('cautions_fee') ? 'is-invalid' : '' }}" />
                            @error('cautions_fee')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="computer_lab_fee">Computer Lab Fee</label>
                            <input type="number" name="computer_lab_fee" id="computer_lab_fee" step="0.01" value="0.00" class="{{ $errors->has('computer_lab_fee') ? 'is-invalid' : '' }}" />
                            @error('computer_lab_fee')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="accommodation_fee">Accommodation Fee</label>
                            <input type="number" name="accommodation_fee" id="accommodation_fee" step="0.01" value="0.00" class="{{ $errors->has('accommodation_fee') ? 'is-invalid' : '' }}" />
                            @error('accommodation_fee')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="transport_fee">Transport Fee</label>
                            <input type="number" name="transport_fee" id="transport_fee" step="0.01" value="0.00" class="{{ $errors->has('transport_fee') ? 'is-invalid' : '' }}" />
                            @error('transport_fee')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="partnership_fee">Partnership Fee</label>
                            <input type="number" name="partnership_fee" id="partnership_fee" step="0.01" value="0.00" class="{{ $errors->has('partnership_fee') ? 'is-invalid' : '' }}" />
                            @error('partnership_fee')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="id_card_fee">ID Card Fee</label>
                            <input type="number" name="id_card_fee" id="id_card_fee" step="0.01" value="0.00" class="{{ $errors->has('id_card_fee') ? 'is-invalid' : '' }}" />
                            @error('id_card_fee')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="student_union_fee">Student Union Fee</label>
                            <input type="number" name="student_union_fee" id="student_union_fee" step="0.01" value="0.00" class="{{ $errors->has('student_union_fee') ? 'is-invalid' : '' }}" />
                            @error('student_union_fee')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="quality_assurance_fee">Quality Assurance Fee</label>
                            <input type="number" name="quality_assurance_fee" id="quality_assurance_fee" step="0.01" value="0.00" class="{{ $errors->has('quality_assurance_fee') ? 'is-invalid' : '' }}" />
                            @error('quality_assurance_fee')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="emergency_fund_fee">Emergency Fund Fee</label>
                            <input type="number" name="emergency_fund_fee" id="emergency_fund_fee" step="0.01" value="0.00" class="{{ $errors->has('emergency_fund_fee') ? 'is-invalid' : '' }}" />
                            @error('emergency_fund_fee')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="library_fee">Library Fee</label>
                            <input type="number" name="library_fee" id="library_fee" step="0.01" value="0.00" class="{{ $errors->has('library_fee') ? 'is-invalid' : '' }}" />
                            @error('library_fee')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="indexing_nck_fee">Indexing (NCK) Fee</label>
                            <input type="number" name="indexing_nck_fee" id="indexing_nck_fee" step="0.01" value="0.00" class="{{ $errors->has('indexing_nck_fee') ? 'is-invalid' : '' }}" />
                            @error('indexing_nck_fee')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="examination_external_fee">Examination Fee</label>
                            <input type="number" name="examination_external_fee" id="examination_external_fee" step="0.01" value="0.00" class="{{ $errors->has('examination_external_fee') ? 'is-invalid' : '' }}" />
                            @error('examination_external_fee')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="attachment_fee">Attachment Fee</label>
                            <input type="number" name="attachment_fee" id="attachment_fee" step="0.01" value="0.00" class="{{ $errors->has('attachment_fee') ? 'is-invalid' : '' }}" />
                            @error('attachment_fee')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="graduation_fee">Graduation Fee</label>
                            <input type="number" name="graduation_fee" id="graduation_fee" step="0.01" value="0.00" class="{{ $errors->has('graduation_fee') ? 'is-invalid' : '' }}" />
                            @error('graduation_fee')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Create fee structure</button>
                        <a href="{{ route('finance.student-finance.fee-structures.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
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
