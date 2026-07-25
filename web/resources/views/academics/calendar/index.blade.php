@extends('layouts.academics')

@section('academics-content')
    @php($hub = ['department' => $department->id])

    <div class="tich-section__intro" style="text-align:left;">
        <h1 class="tich-h1" style="font-size: 2rem;">Academic calendar</h1>
        <p class="tich-text">Configure institution-wide academic years and trimester/semester terms for {{ $department->dept_name }}.</p>
    </div>

    <article class="tich-card tich-mt-8">
        <h2 class="tich-h3">Create academic year</h2>
        <form method="POST" action="{{ route('departments.academics.calendar.store-year', $hub) }}" class="tich-mt-4">
            @csrf
            <div class="tich-grid tich-grid--3" style="gap:1rem;">
                <div class="tich-form-group">
                    <label class="tich-label">Year label</label>
                    <input type="text" name="year_label" class="tich-input" value="{{ old('year_label', date('Y')) }}" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Start date</label>
                    <input type="date" name="start_date" class="tich-input" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">End date</label>
                    <input type="date" name="end_date" class="tich-input" required>
                </div>
            </div>
            <div class="tich-grid tich-grid--2 tich-mt-4" style="gap:1rem;">
                <div class="tich-form-group">
                    <label class="tich-label">Terms per year</label>
                    <input type="number" name="term_count" class="tich-input" min="1" max="6" value="{{ $defaultTrimesters }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label"><input type="checkbox" name="is_current" value="1"> Set as current year</label>
                </div>
            </div>
            <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Create year &amp; auto-generate terms</button>
        </form>
    </article>

    @foreach ($years as $year)
        <article class="tich-card tich-mt-8">
            <h2 class="tich-h3">{{ $year->year_label }} @if($year->is_current)<span class="tich-caption">(current)</span>@endif</h2>
            <p class="tich-caption">{{ $year->start_date?->format('d M Y') }} - {{ $year->end_date?->format('d M Y') }}</p>

            @foreach ($year->semesters as $semester)
                <form method="POST" action="{{ route('departments.academics.calendar.update-semester', array_merge($hub, ['semester' => $semester->id])) }}" class="tich-mt-6" style="border-top:1px solid var(--tich-border); padding-top:1rem;">
                    @csrf
                    @method('PUT')
                    <div class="tich-grid tich-grid--4" style="gap:1rem;">
                        <div class="tich-form-group">
                            <label class="tich-label">Label</label>
                            <input type="text" name="semester_label" class="tich-input" value="{{ $semester->semester_label }}" required>
                        </div>
                        <div class="tich-form-group">
                            <label class="tich-label">Number</label>
                            <input type="number" name="semester_number" class="tich-input" value="{{ $semester->semester_number }}" required>
                        </div>
                        <div class="tich-form-group">
                            <label class="tich-label">Intake month</label>
                            <input type="text" name="intake_month" class="tich-input" value="{{ $semester->intake_month }}">
                        </div>
                        <div class="tich-form-group">
                            <label class="tich-label"><input type="checkbox" name="is_current" value="1" @checked($semester->is_current)> Current term</label>
                        </div>
                        <div class="tich-form-group">
                            <label class="tich-label">Start</label>
                            <input type="date" name="start_date" class="tich-input" value="{{ $semester->start_date?->format('Y-m-d') }}" required>
                        </div>
                        <div class="tich-form-group">
                            <label class="tich-label">End</label>
                            <input type="date" name="end_date" class="tich-input" value="{{ $semester->end_date?->format('Y-m-d') }}" required>
                        </div>
                        <div class="tich-form-group">
                            <label class="tich-label">Registration opens</label>
                            <input type="date" name="registration_open_date" class="tich-input" value="{{ $semester->registration_open_date?->format('Y-m-d') }}">
                        </div>
                        <div class="tich-form-group">
                            <label class="tich-label">Registration closes</label>
                            <input type="date" name="registration_close_date" class="tich-input" value="{{ $semester->registration_close_date?->format('Y-m-d') }}">
                        </div>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-secondary tich-mt-4">Update term</button>
                </form>
            @endforeach
        </article>
    @endforeach
@endsection
