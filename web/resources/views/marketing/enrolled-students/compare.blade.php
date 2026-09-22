@extends('layouts.marketing')

@section('title', 'Compare Enrolment by Intake')

@section('department-content')
    <x-page-toolbar title="Compare Enrolment" meta="Compare student enrolment across different intakes" />

    <form method="GET" class="tich-form-grid tich-form-grid--3 tich-mt-4" style="gap: 1rem;">
        <div class="tich-form-group">
            <label class="tich-label" for="intake_1">Intake 1</label>
            <select id="intake_1" name="intake_1" class="tich-input">
                <option value="">Select Intake</option>
                @foreach ($intakes as $intake)
                    <option value="{{ $intake }}" {{ $intake_1 === $intake ? 'selected' : '' }}>{{ $intake }}</option>
                @endforeach
            </select>
        </div>
        <div class="tich-form-group">
            <label class="tich-label" for="intake_2">Intake 2</label>
            <select id="intake_2" name="intake_2" class="tich-input">
                <option value="">Select Intake</option>
                @foreach ($intakes as $intake)
                    <option value="{{ $intake }}" {{ $intake_2 === $intake ? 'selected' : '' }}>{{ $intake }}</option>
                @endforeach
            </select>
        </div>
        <div class="tich-form-group">
            <label class="tich-label" for="intake_3">Intake 3</label>
            <select id="intake_3" name="intake_3" class="tich-input">
                <option value="">Select Intake</option>
                @foreach ($intakes as $intake)
                    <option value="{{ $intake }}" {{ $intake_3 === $intake ? 'selected' : '' }}>{{ $intake }}</option>
                @endforeach
            </select>
        </div>
        <div class="tich-form-group" style="grid-column: 1 / -1; align-self: end;">
            <button type="submit" class="tich-btn tich-btn-primary">Compare</button>
        </div>
    </form>

    @if (! empty($stats))
        <div class="tich-card tich-mt-4 tich-mb-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Metric</th>
                        @foreach ($stats as $stat)
                            <th>{{ $stat['intake'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Total Enrolled</strong></td>
                        @foreach ($stats as $stat)
                            <td>{{ $stat['total'] }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td><strong>Male</strong></td>
                        @foreach ($stats as $stat)
                            <td>{{ $stat['males'] }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <td><strong>Female</strong></td>
                        @foreach ($stats as $stat)
                            <td>{{ $stat['females'] }}</td>
                        @endforeach
                    </tr>
                    @foreach ($programs as $program)
                        <tr>
                            <td>{{ $program->program_name }}</td>
                            @foreach ($stats as $stat)
                                <td>{{ $stat['by_program'][$program->id] ?? 0 }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                    @foreach (['enrolled', 'graduated', 'withdrawn', 'suspended'] as $status)
                        <tr>
                            <td>{{ ucfirst($status) }}</td>
                            @foreach ($stats as $stat)
                                <td>{{ $stat['enrollment_status'][$status] ?? 0 }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
