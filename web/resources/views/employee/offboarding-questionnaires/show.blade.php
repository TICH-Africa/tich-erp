@extends('layouts.employee')

@section('employee-content')
    <x-page-toolbar :title="$invitation->template?->title ?? 'Offboarding questionnaire'">
        <x-slot:actions>
            <a href="{{ route('employee.offboarding-questionnaires.index') }}" class="tich-btn tich-btn-ghost">← Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    @error('answers')
        <div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>
    @enderror

    <div class="tich-card tich-mb-6" style="padding: 1.25rem;">
        <h2 class="tich-h4">Your details (pre-filled)</h2>
        <div class="tich-grid tich-grid--2 tich-mt-4">
            <div class="uf-field">
                <label>Name</label>
                <input type="text" class="tich-input" readonly value="{{ $prefill['employee_name'] ?? '' }}">
            </div>
            <div class="uf-field">
                <label>Employee ID</label>
                <input type="text" class="tich-input" readonly value="{{ $prefill['employee_number'] ?? '' }}">
            </div>
            <div class="uf-field">
                <label>Position(s)</label>
                <input type="text" class="tich-input" readonly value="{{ implode(', ', $prefill['positions'] ?? []) }}">
            </div>
            <div class="uf-field">
                <label>Department(s)</label>
                <input type="text" class="tich-input" readonly value="{{ implode(', ', $prefill['departments'] ?? []) }}">
            </div>
            <div class="uf-field">
                <label>Date of joining</label>
                <input type="text" class="tich-input" readonly value="{{ $prefill['date_joined'] ?? '' }}">
            </div>
            <div class="uf-field">
                <label>Expected departure date</label>
                <input type="text" class="tich-input" readonly value="{{ $prefill['expected_departure_date'] ?? '' }}">
            </div>
        </div>
    </div>

    @if ($invitation->isPending())
        <form method="POST" action="{{ route('employee.offboarding-questionnaires.submit', $invitation) }}" class="uf-form">
            @csrf
            <div class="tich-card" style="padding: 1.25rem;">
                <h2 class="tich-h4">Your feedback</h2>
                @foreach ($invitation->template?->questions ?? [] as $question)
                    <div class="uf-field tich-mt-4">
                        <label>
                            {{ $question->question_text }}
                            @if ($question->is_required)<span class="uf-req">*</span>@endif
                        </label>
                        @if ($question->input_type === 'textarea')
                            <textarea name="answers[{{ $question->id }}]" class="tich-input" rows="3" @required($question->is_required)>{{ old('answers.'.$question->id) }}</textarea>
                        @elseif ($question->input_type === 'text')
                            <input type="text" name="answers[{{ $question->id }}]" class="tich-input" value="{{ old('answers.'.$question->id) }}" @required($question->is_required)>
                        @elseif ($question->input_type === 'yes_no')
                            <select name="answers[{{ $question->id }}]" class="tich-input" @required($question->is_required)>
                                <option value="">Select</option>
                                <option value="Yes" @selected(old('answers.'.$question->id) === 'Yes')>Yes</option>
                                <option value="No" @selected(old('answers.'.$question->id) === 'No')>No</option>
                            </select>
                        @elseif ($question->input_type === 'select')
                            <select name="answers[{{ $question->id }}]" class="tich-input" @required($question->is_required)>
                                <option value="">Select</option>
                                @foreach ($question->options ?? [] as $opt)
                                    <option value="{{ $opt }}" @selected(old('answers.'.$question->id) === $opt)>{{ $opt }}</option>
                                @endforeach
                            </select>
                        @else
                            <textarea name="answers[{{ $question->id }}]" class="tich-input" rows="2">{{ old('answers.'.$question->id) }}</textarea>
                        @endif
                    </div>
                @endforeach
            </div>
            <button type="submit" class="tich-btn tich-btn-primary tich-mt-6">Submit to HR</button>
        </form>
    @else
        <div class="tich-card" style="padding: 1.25rem;">
            <p class="tich-caption">Submitted {{ $invitation->submitted_at?->format('d M Y H:i') }}</p>
            <ol class="tich-mt-4">
                @foreach ($invitation->template?->questions ?? [] as $question)
                    @php $answer = $answers->get($question->id); @endphp
                    <li class="tich-mb-3">
                        <strong>{{ $question->question_text }}</strong>
                        <p>{{ $answer?->answer_text ?? '—' }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    @endif
@endsection