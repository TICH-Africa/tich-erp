@extends('layouts.employee')

@section('title', 'Performance appraisals')

@section('employee-content')
    <x-page-toolbar title="Performance appraisals" meta="Goals → Self-assessment → Manager review · Quarterly cycles" />

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif

    <div class="tich-card tich-mt-6">
        <h2 class="tich-h3">My appraisals</h2>
        <div class="tich-table-wrap tich-mt-3">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Cycle</th>
                        <th>Manager</th>
                        <th>Status</th>
                        <th>Score</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($appraisals as $appraisal)
                        <tr>
                            <td>{{ $appraisal->cycle?->label() }}</td>
                            <td>{{ $appraisal->lineManager?->fullName() ?? '—' }}</td>
                            <td>{{ $statuses[$appraisal->status] ?? $appraisal->status }}</td>
                            <td>{{ $appraisal->finalScore() !== null ? number_format($appraisal->finalScore(), 2) : '—' }}</td>
                            <td><a href="{{ route('employee.appraisals.show', $appraisal) }}" class="tich-btn tich-btn-ghost tich-btn--sm">Open</a></td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 5, 'title' => 'No appraisals yet — HR opens each quarterly cycle', 'icon' => 'clipboard-check'])
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($teamAppraisals->isNotEmpty())
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Team appraisals (immediate manager)</h2>
            <p class="tich-caption">Approve goals and complete manager reviews for your direct reports.</p>
            <div class="tich-table-wrap tich-mt-3">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Staff</th>
                            <th>Cycle</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($teamAppraisals as $appraisal)
                            <tr>
                                <td>
                                    <strong>{{ $appraisal->staff?->fullName() }}</strong>
                                    <div class="tich-caption">{{ $appraisal->job_title_snapshot }}</div>
                                </td>
                                <td>{{ $appraisal->cycle?->label() }}</td>
                                <td>{{ $statuses[$appraisal->status] ?? $appraisal->status }}</td>
                                <td><a href="{{ route('employee.appraisals.team.show', $appraisal) }}" class="tich-btn tich-btn-primary tich-btn--sm">Review</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
