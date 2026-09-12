@extends('layouts.ceo')

@section('title', 'Quality report')

@section('ceo-content')
    <x-page-toolbar title="{{ $plan->plan_name }}" meta="Quality Level Report">
        <x-slot:actions>
            <a href="{{ route('ceo.quality.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-mt-6">
        <p class="tich-text">{{ $plan->description }}</p>
        <p class="tich-caption tich-mt-2">
            Period {{ $plan->period_start?->format('d M Y') }} – {{ $plan->period_end?->format('d M Y') }}
            · Threshold {{ $plan->pass_threshold }}%
            · Status {{ str_replace('_', ' ', $plan->status) }}
        </p>
    </div>

    <div class="tich-card tich-table-panel tich-mt-6">
        <h2 class="tich-h3">Department scores</h2>
        <table class="tich-admin-table tich-mt-4">
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Items</th>
                    <th>Score</th>
                    <th>Result</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($plan->complianceScores as $score)
                    <tr>
                        <td>{{ $score->department?->dept_name }}</td>
                        <td>{{ $score->items_submitted }} / {{ $score->total_items }}</td>
                        <td>{{ number_format((float) $score->weighted_score, 1) }}%</td>
                        <td>
                            <x-status-badge :status="$score->pass_fail_status" />
                            @if ($score->is_below_threshold)
                                <span class="tich-caption" style="color:#b91c1c;">corrective action flagged</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="tich-table-empty">No scores calculated yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($plan->correctiveActions->isNotEmpty())
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Corrective actions</h2>
            <ul class="tich-mt-4" style="margin:0;padding-left:1.25rem;">
                @foreach ($plan->correctiveActions as $action)
                    <li class="tich-text tich-mt-2">
                        <strong>{{ $action->department?->dept_name }}</strong> · {{ $action->status }}
                        <p class="tich-caption">{{ $action->flagged_reason }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection
