@extends('layouts.hr')

@section('title', 'Appraisal cycles')

@section('hr-content')
    <x-page-toolbar title="Appraisal cycles" meta="HR initiates quarterly cycles · Self → Manager → Calibration → HR">
        <a href="{{ route('hr.appraisals.index') }}" class="tich-btn tich-btn-ghost">All appraisals</a>
        <a href="{{ route('hr.appraisals.corporate-goals') }}" class="tich-btn tich-btn-ghost">Corporate goals</a>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="tich-alert tich-alert--danger tich-mt-4">{{ $errors->first() }}</div>
    @endif

    <div class="tich-card tich-mt-6">
        <h2 class="tich-h3">Create quarterly cycle</h2>
        <p class="tich-caption tich-mt-1">HR opens the cycle to generate appraisal shells for active staff. Resignations later do not remove historical appraisals.</p>
        <form method="POST" action="{{ route('hr.appraisals.cycles.store') }}" class="tich-mt-4">
            @csrf
            <div class="tich-grid tich-grid--5" style="gap:0.75rem;">
                <div class="tich-form-group">
                    <label class="tich-label">Name</label>
                    <input type="text" name="name" class="tich-input" value="{{ old('name') }}" required placeholder="Q1 2026 Performance Appraisal">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Fiscal year</label>
                    <input type="number" name="fiscal_year" class="tich-input" value="{{ old('fiscal_year', now()->year) }}" min="2020" max="2100" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Quarter</label>
                    <select name="quarter" class="tich-input" required>
                        @for ($q = 1; $q <= 4; $q++)
                            <option value="{{ $q }}" @selected((int) old('quarter', 1) === $q)>Q{{ $q }}</option>
                        @endfor
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Period start</label>
                    <input type="date" name="period_start" class="tich-input" value="{{ old('period_start') }}" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Period end</label>
                    <input type="date" name="period_end" class="tich-input" value="{{ old('period_end') }}" required>
                </div>
            </div>
            <div class="tich-form-group tich-mt-3">
                <label class="tich-label">Instructions (optional)</label>
                <textarea name="instructions" class="tich-input" rows="2">{{ old('instructions') }}</textarea>
            </div>
            <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Create draft cycle</button>
        </form>
    </div>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Cycle</th>
                        <th>Period</th>
                        <th>Status</th>
                        <th>Completion</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cycles as $cycle)
                        @php $stats = $cycle->stats ?? ['total' => 0, 'completed' => 0, 'completion_rate' => 0]; @endphp
                        <tr>
                            <td>
                                <strong>{{ $cycle->label() }}</strong>
                                <div class="tich-caption">FY {{ $cycle->fiscal_year }} · Q{{ $cycle->quarter }}</div>
                            </td>
                            <td>{{ $cycle->period_start?->format('d M Y') }} – {{ $cycle->period_end?->format('d M Y') }}</td>
                            <td>{{ $cycleStatuses[$cycle->status] ?? $cycle->status }}</td>
                            <td>
                                {{ $stats['completed'] }}/{{ $stats['total'] }}
                                <span class="tich-caption">({{ $stats['completion_rate'] }}%)</span>
                            </td>
                            <td style="display:flex; flex-wrap:wrap; gap:0.35rem;">
                                <a href="{{ route('hr.appraisals.index', ['cycle_id' => $cycle->id, 'status' => 'all']) }}" class="tich-btn tich-btn-ghost tich-btn--sm">View</a>
                                @if ($cycle->status === 'draft')
                                    <form method="POST" action="{{ route('hr.appraisals.cycles.open', $cycle) }}" onsubmit="return confirm('Open this cycle and create shells for active staff?');">
                                        @csrf
                                        <button class="tich-btn tich-btn-primary tich-btn--sm" type="submit">Open cycle</button>
                                    </form>
                                @endif
                                @if ($cycle->status === 'open')
                                    <form method="POST" action="{{ route('hr.appraisals.cycles.calibration', $cycle) }}">
                                        @csrf
                                        <button class="tich-btn tich-btn-ghost tich-btn--sm" type="submit">Start calibration</button>
                                    </form>
                                @endif
                                @if (in_array($cycle->status, ['open', 'calibration'], true))
                                    <form method="POST" action="{{ route('hr.appraisals.cycles.close', $cycle) }}" onsubmit="return confirm('Close only if all appraisals are completed.');">
                                        @csrf
                                        <button class="tich-btn tich-btn-ghost tich-btn--sm" type="submit">Close</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 5, 'title' => 'No appraisal cycles yet', 'icon' => 'clipboard-check'])
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
