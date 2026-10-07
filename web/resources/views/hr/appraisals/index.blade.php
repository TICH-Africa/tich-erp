@extends('layouts.hr')

@section('title', 'Staff appraisals')

@section('hr-content')
    <x-page-toolbar title="Staff appraisals" meta="Quarterly · Self-assessment → Immediate manager → HR sign-off">
        <a href="{{ route('hr.appraisals.cycles') }}" class="tich-btn tich-btn-primary">Cycles</a>
        <a href="{{ route('hr.appraisals.corporate-goals') }}" class="tich-btn tich-btn-ghost">Corporate goals</a>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif

    <form method="GET" class="tich-card tich-mt-6" style="padding:1rem;">
        <div class="tich-grid tich-grid--4" style="gap:0.75rem; align-items:end;">
            <div class="tich-form-group" style="margin:0;">
                <label class="tich-label">Search</label>
                <input type="search" name="search" class="tich-input" value="{{ $filters['search'] ?? '' }}" placeholder="Name or employee no.">
            </div>
            <div class="tich-form-group" style="margin:0;">
                <label class="tich-label">Cycle</label>
                <select name="cycle_id" class="tich-input">
                    <option value="">All cycles</option>
                    @foreach ($cycles as $cycle)
                        <option value="{{ $cycle->id }}" @selected((int) ($filters['cycle_id'] ?? 0) === $cycle->id)>{{ $cycle->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group" style="margin:0;">
                <label class="tich-label">Status</label>
                <select name="status" class="tich-input">
                    <option value="all" @selected(($filters['status'] ?? '') === 'all')>All</option>
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex; gap:0.5rem;">
                <button type="submit" class="tich-btn tich-btn-primary">Filter</button>
                <a href="{{ route('hr.appraisals.index') }}" class="tich-btn tich-btn-ghost">Reset</a>
            </div>
        </div>
    </form>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Staff</th>
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
                            <td>
                                <strong>{{ $appraisal->staff?->fullName() ?? '—' }}</strong>
                                <div class="tich-caption">{{ $appraisal->staff?->employee_number }} · {{ $appraisal->job_title_snapshot }}</div>
                            </td>
                            <td>{{ $appraisal->cycle?->label() }}</td>
                            <td>{{ $appraisal->lineManager?->fullName() ?? '—' }}</td>
                            <td>{{ $statuses[$appraisal->status] ?? $appraisal->status }}</td>
                            <td>
                                @if ($appraisal->finalScore() !== null)
                                    {{ number_format($appraisal->finalScore(), 2) }}
                                    <span class="tich-caption">{{ str_replace('_', ' ', $appraisal->overall_rating ?? '') }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td><a href="{{ route('hr.appraisals.show', $appraisal) }}" class="tich-btn tich-btn-ghost tich-btn--sm">Open</a></td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 6, 'title' => 'No appraisals match these filters', 'icon' => 'clipboard-check'])
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="tich-mt-4">{{ $appraisals->links() }}</div>
    </div>
@endsection
