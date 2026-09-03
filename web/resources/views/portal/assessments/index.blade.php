@extends('layouts.portal')

@section('portal-content')
    <x-page-toolbar title="Assessments & CATs" meta="Take your continuous assessments and view results">
    </x-page-toolbar>

    @php
        $assessments = $assessments ?? ($portalData['academics']['assessments'] ?? collect());
        $mySubmissions = $mySubmissions ?? ($portalData['academics']['my_submissions'] ?? collect());
    @endphp

    @if ($assessments->isEmpty())
        @include('partials.states.empty', [
            'title' => 'No assessments available',
            'description' => 'Your lecturer has not published any assessments yet. Check back later.',
            'icon' => 'clipboard-check',
        ])
    @else
        <div class="tich-card tich-table-panel">
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Assessment</th>
                            <th>Unit</th>
                            <th>Type</th>
                            <th>Available</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($assessments as $assessment)
                            @php
                                $submission = $mySubmissions[$assessment->id] ?? null;
                                $isAvailable = $assessment->isAvailable();
                                $isExpired = $assessment->available_until && now()->gt($assessment->available_until);
                                $isUpcoming = $assessment->available_from && now()->lt($assessment->available_from);
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $assessment->name }}</strong>
                                    @if ($assessment->time_limit_minutes)
                                        <div class="tich-caption tich-mt-1">{{ $assessment->time_limit_minutes }} min limit</div>
                                    @endif
                                </td>
                                <td>{{ $assessment->allocation->unit->unit_code ?? '-' }}</td>
                                <td class="tich-caption">{{ ucfirst(str_replace('_', ' ', $assessment->assessment_type)) }}</td>
                                <td class="tich-caption">
                                    @if ($assessment->available_from)
                                        From {{ $assessment->available_from->format('d M Y H:i') }}
                                    @else
                                        Anytime
                                    @endif
                                    @if ($assessment->available_until)
                                        <br>Until {{ $assessment->available_until->format('d M Y H:i') }}
                                    @endif
                                </td>
                                <td>
                                    @if ($submission && $submission->student_submitted_at)
                                        <span class="tich-badge tich-badge--success">Completed</span>
                                        <div class="tich-caption tich-mt-1">
                                            {{ $submission->score_obtained }} / {{ $assessment->max_score }} ({{ $submission->percentage_score }}%)
                                        </div>
                                    @elseif ($assessment->status === 'graded')
                                        <span class="tich-badge tich-badge--info">Results available</span>
                                    @elseif ($isExpired)
                                        <span class="tich-badge tich-badge--danger">Expired</span>
                                    @elseif ($isUpcoming)
                                        <span class="tich-badge tich-badge--info">Upcoming</span>
                                    @else
                                        <span class="tich-badge tich-badge--warning">Available</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($submission && $submission->student_submitted_at)
                                        <a href="{{ route('portal.assessments.result', $assessment) }}" class="tich-btn tich-btn-secondary" style="font-size:0.875rem; padding:0.35rem 0.75rem;">View result</a>
                                    @elseif ($assessment->status === 'graded')
                                        <span class="tich-caption">Results released</span>
                                    @elseif ($isExpired || $isUpcoming)
                                        <span class="tich-caption">{{ $isExpired ? 'Closed' : 'Not yet open' }}</span>
                                    @else
                                        <a href="{{ route('portal.assessments.take', $assessment) }}" class="tich-btn tich-btn-primary" style="font-size:0.875rem; padding:0.35rem 0.75rem;" onclick="return confirm('You are about to start {{ $assessment->name }}. Once started, your timer will begin. Click OK to start now.')">Start assessment</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
