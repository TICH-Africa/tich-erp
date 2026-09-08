@extends('layouts.monitoring-evaluation')

@section('title', 'PIME Workspace')

@section('monitoring-evaluation-content')
    <x-page-toolbar title="PIME meeting workspace" meta="Planning · Implementation · Monitoring · Evaluation — planned vs achieved">
        <x-slot:actions>
            <form method="POST" action="{{ route('monitoring_evaluation.pime.recalculate') }}">
                @csrf
                <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">
                <button type="submit" class="tich-btn tich-btn-secondary">Recalculate health scores</button>
            </form>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif

    <form method="GET" class="tich-card tich-mt-6 tich-flex-wrap" style="gap:1rem;align-items:end;">
        <div class="tich-form-group" style="margin:0;">
            <label class="tich-label" for="fiscal_year">Fiscal year</label>
            <select id="fiscal_year" name="fiscal_year" class="tich-input">
                @foreach ($years as $y)
                    <option value="{{ $y }}" @selected((string) $y === (string) $fiscalYear)>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div class="tich-form-group" style="margin:0;">
            <label class="tich-label" for="quarter">Quarter</label>
            <select id="quarter" name="quarter" class="tich-input">
                @for ($q = 1; $q <= 4; $q++)
                    <option value="{{ $q }}" @selected($quarter === $q)>Q{{ $q }}</option>
                @endfor
            </select>
        </div>
        <button type="submit" class="tich-btn tich-btn-primary">Refresh</button>
    </form>

    <div class="tich-grid tich-grid--3 tich-mt-6">
        <article class="tich-card"><p class="tich-caption">Total planned</p><p class="tich-h2 tich-mt-2">{{ number_format($totals['planned'], 2) }}</p></article>
        <article class="tich-card"><p class="tich-caption">Total achieved</p><p class="tich-h2 tich-mt-2">{{ number_format($totals['achieved'], 2) }}</p></article>
        <article class="tich-card"><p class="tich-caption">Net deviation</p><p class="tich-h2 tich-mt-2">{{ number_format($totals['deviation'], 2) }}</p></article>
    </div>

    <div class="tich-card tich-mt-6">
        <h2 class="tich-h3">Comparative chart — Q{{ $quarter }}</h2>
        <div class="tich-mt-4" style="display:flex;flex-direction:column;gap:0.75rem;">
            @php $max = max(1, collect($comparison)->max(fn ($r) => max($r['planned'], $r['achieved'])) ?: 1); @endphp
            @forelse ($comparison as $row)
                <div>
                    <div class="tich-flex" style="justify-content:space-between;gap:1rem;">
                        <strong>{{ $row['department'] }}</strong>
                        <span class="tich-caption">P {{ number_format($row['planned'], 1) }} · A {{ number_format($row['achieved'], 1) }} · Δ {{ number_format($row['deviation'], 1) }}</span>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr;gap:0.25rem;margin-top:0.35rem;">
                        <div style="background:#e2e8f0;border-radius:4px;height:10px;overflow:hidden;">
                            <div style="width:{{ min(100, ($row['planned'] / $max) * 100) }}%;height:100%;background:#64748b;"></div>
                        </div>
                        <div style="background:#e2e8f0;border-radius:4px;height:10px;overflow:hidden;">
                            <div style="width:{{ min(100, ($row['achieved'] / $max) * 100) }}%;height:100%;background:{{ $row['deviation'] < 0 ? '#ea580c' : '#0f766e' }};"></div>
                        </div>
                    </div>
                </div>
            @empty
                <p class="tich-text">No submitted/verified reports for this quarter yet.</p>
            @endforelse
        </div>
    </div>

    <article class="tich-card tich-mt-6">
        <h2 class="tich-h3">Interlocked health scores (QA × M&amp;E)</h2>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>QA compliance</th>
                        <th>M&amp;E achievement</th>
                        <th>Health score</th>
                        <th>Rating</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($health as $row)
                        <tr>
                            <td>{{ $row->department?->dept_name }}</td>
                            <td>{{ $row->qa_compliance_avg !== null ? number_format($row->qa_compliance_avg, 1).'%' : '—' }}</td>
                            <td>{{ $row->me_achievement_avg !== null ? number_format($row->me_achievement_avg, 1).'%' : '—' }}</td>
                            <td>{{ $row->health_score !== null ? number_format($row->health_score, 1) : '—' }}</td>
                            <td>{{ $row->health_rating ? ucfirst($row->health_rating) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No health scores for this year yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>
@endsection
