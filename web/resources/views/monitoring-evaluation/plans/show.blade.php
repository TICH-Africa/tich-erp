@extends('layouts.monitoring-evaluation')

@section('title', $plan->title)

@section('monitoring-evaluation-content')
    @php
        $budget = $plan->budgetRequest;
        $budgetLines = is_array($budget?->standard_line_items) ? $budget->standard_line_items : [];
        $budgetStructured = $budgetLines !== [] && isset($budgetLines[0]) && is_array($budgetLines[0]) && array_key_exists('unit_price', $budgetLines[0]);
    @endphp

    <x-page-toolbar :title="$plan->title" :meta="($plan->department?->dept_name ?? 'Department').' · '.str_replace('_', ' ', $plan->status)">
        <x-slot:actions>
            <a href="{{ route('monitoring_evaluation.plans.index', ['status' => 'me_review']) }}" class="tich-btn tich-btn-ghost">Back to queue</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
    @endif

    <div class="tich-grid tich-grid--2 tich-mt-6" style="gap:1rem; align-items:start;">
        <article class="tich-card">
            <h2 class="tich-h3">Plan overview</h2>
            <div class="tich-grid tich-grid--2 tich-mt-4" style="gap:0.85rem;">
                <div>
                    <p class="tich-caption">Department</p>
                    <p><strong>{{ $plan->department?->dept_name ?? '-' }}</strong></p>
                </div>
                <div>
                    <p class="tich-caption">Status</p>
                    <p><x-status-badge :status="$plan->status" /></p>
                </div>
                <div>
                    <p class="tich-caption">Fiscal year</p>
                    <p>{{ $plan->fiscal_year ?? '-' }}</p>
                </div>
                <div>
                    <p class="tich-caption">Submitted</p>
                    <p>{{ $plan->submitted_at?->format('d M Y H:i') ?? '-' }}</p>
                </div>
                <div>
                    <p class="tich-caption">Submitted by</p>
                    <p>{{ $plan->submitter?->displayName() ?? '-' }}</p>
                </div>
                <div>
                    <p class="tich-caption">Linked budget</p>
                    <p>
                        @if ($budget)
                            <strong>{{ $budget->request_code }}</strong>
                            <span class="tich-caption">· {{ str_replace('_', ' ', $budget->status) }}</span>
                        @else
                            <span class="tich-caption">Not linked</span>
                        @endif
                    </p>
                </div>
            </div>
            @if ($plan->summary)
                <div class="tich-mt-4">
                    <p class="tich-caption">Plan summary</p>
                    <p class="tich-text">{{ $plan->summary }}</p>
                </div>
            @endif
            @if ($plan->me_notes)
                <div class="tich-mt-4">
                    <p class="tich-caption">M&amp;E notes</p>
                    <p class="tich-text">{{ $plan->me_notes }}</p>
                </div>
            @endif
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Review actions</h2>
            @if (in_array($plan->status, ['me_review', 'returned'], true))
                <p class="tich-caption tich-mt-2">Review the technical plan outputs below, then approve or return to the department.</p>
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
            @else
                <p class="tich-caption tich-mt-2">No actions available for status <strong>{{ str_replace('_', ' ', $plan->status) }}</strong>.</p>
            @endif
        </article>
    </div>

    <div class="tich-card tich-mt-6">
        <h2 class="tich-h3">Technical plan</h2>
        <p class="tich-caption tich-mt-1">Department outputs and activities submitted for M&amp;E baseline review.</p>

        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Output</th>
                        <th>Activity</th>
                        <th>Costable item</th>
                        <th>Planned</th>
                        <th>Unit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plan->outputs as $index => $out)
                        <tr>
                            <td class="tich-caption">{{ $index + 1 }}</td>
                            <td><strong>{{ $out->output }}</strong></td>
                            <td>{{ $out->activity }}</td>
                            <td>{{ $out->costable_item ?: '—' }}</td>
                            <td>{{ number_format((float) $out->planned, 2) }}</td>
                            <td>{{ $out->planned_unit ?: '—' }}</td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', [
                            'colspan' => 6,
                            'title' => 'No technical plan outputs on this record',
                            'icon' => 'inbox',
                        ])
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($budget)
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Linked budget request</h2>
            <p class="tich-caption tich-mt-1">
                {{ $budget->request_code }} · {{ $budget->title }} ·
                KES {{ number_format((float) $budget->requested_amount, 2) }} ·
                {{ str_replace('_', ' ', $budget->status) }}
            </p>
            @if ($budget->justification)
                <p class="tich-text tich-mt-3">{{ $budget->justification }}</p>
            @endif

            <div class="tich-table-wrap tich-mt-4">
                @if ($budgetStructured)
                    <table class="tich-admin-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Description</th>
                                <th>Unit price</th>
                                <th>UoM</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($budgetLines as $line)
                                <tr>
                                    <td>{{ $line['item'] ?? '-' }}</td>
                                    <td>{{ $line['quantity'] ?? '-' }}</td>
                                    <td class="tich-caption">{{ $line['description'] ?: '—' }}</td>
                                    <td>KES {{ number_format((float) ($line['unit_price'] ?? 0), 2) }}</td>
                                    <td class="tich-caption">{{ $line['unit_of_measure'] ?: '—' }}</td>
                                    <td><strong>KES {{ number_format((float) ($line['total'] ?? (($line['quantity'] ?? 0) * ($line['unit_price'] ?? 0))), 2) }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @elseif ($budgetLines !== [])
                    <pre class="tich-pre" style="background:#f8fafc; padding:1rem; border-radius:0.5rem; overflow:auto;">{{ json_encode($budgetLines, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @else
                    <p class="tich-caption">No budget line items on the linked request.</p>
                @endif
            </div>
        </div>
    @endif
@endsection
