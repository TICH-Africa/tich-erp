@extends('layouts.monitoring-evaluation')

@section('title', $plan->title)

@section('monitoring-evaluation-content')
    <x-page-toolbar :title="$plan->title" :meta="($plan->department?->dept_name ?? '').' · '.str_replace('_', ' ', $plan->status)">
        <x-slot:actions>
            <a href="{{ route('monitoring_evaluation.plans.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
    @endif

    <div class="tich-grid tich-grid--2 tich-mt-6">
        <article class="tich-card">
            <h2 class="tich-h3">Routing</h2>
            <p class="tich-text tich-mt-2">Linked budget: #{{ $plan->budget_request_id }} · {{ $plan->budgetRequest?->status ?? 'n/a' }}</p>
            <p class="tich-caption">Fiscal year: {{ $plan->fiscal_year ?? '—' }}</p>
            <p class="tich-caption">Submitted {{ $plan->submitted_at?->format('d M Y H:i') }}</p>
            @if ($plan->summary)
                <p class="tich-text tich-mt-4">{{ $plan->summary }}</p>
            @endif
            @if ($plan->me_notes)
                <p class="tich-caption tich-mt-4"><strong>M&amp;E notes:</strong> {{ $plan->me_notes }}</p>
            @endif
        </article>
        <article class="tich-card">
            <h2 class="tich-h3">Actions</h2>
            @if (in_array($plan->status, ['me_review', 'returned'], true))
                <form method="POST" action="{{ route('monitoring_evaluation.plans.approve', $plan) }}" class="tich-form-stack tich-mt-4">
                    @csrf
                    <textarea name="me_notes" class="tich-input" rows="2" placeholder="Optional approval notes">{{ old('me_notes') }}</textarea>
                    <button type="submit" class="tich-btn tich-btn-primary">Approve technical plan</button>
                </form>
                <form method="POST" action="{{ route('monitoring_evaluation.plans.return', $plan) }}" class="tich-form-stack tich-mt-4">
                    @csrf
                    <textarea name="me_notes" class="tich-input" rows="2" required placeholder="Return reason">{{ old('me_notes') }}</textarea>
                    <button type="submit" class="tich-btn tich-btn-secondary">Return to department</button>
                </form>
            @elseif ($plan->status === 'me_approved')
                <form method="POST" action="{{ route('monitoring_evaluation.plans.lock', $plan) }}" class="tich-mt-4">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-primary">Try baseline lock</button>
                </form>
                <p class="tich-caption tich-mt-2">Requires linked budget status = approved.</p>
            @elseif ($plan->isBaselineLocked())
                <p class="tich-text tich-mt-2">Baseline locked {{ $plan->baseline_locked_at?->format('d M Y H:i') }}.</p>
                <div class="tich-mt-4" style="display:flex;flex-wrap:wrap;gap:0.5rem;">
                    @foreach ($plan->quarters as $q)
                        <a href="{{ route('monitoring_evaluation.plans.open-quarter', [$plan, $q->quarter_number]) }}" class="tich-btn tich-btn-secondary">Open {{ $q->label() }} report</a>
                    @endforeach
                </div>
            @endif
        </article>
    </div>

    <div class="tich-card tich-table-panel tich-mt-6">
        <h2 class="tich-h3">Baseline outputs</h2>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Output</th>
                        <th>Activity</th>
                        <th>Costable item</th>
                        <th>Planned</th>
                        <th>Unit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($plan->outputs as $out)
                        <tr>
                            <td>{{ $out->output }}</td>
                            <td>{{ $out->activity }}</td>
                            <td>{{ $out->costable_item }}</td>
                            <td>{{ number_format((float) $out->planned, 2) }}</td>
                            <td>{{ $out->planned_unit }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
