@extends('layouts.monitoring-evaluation')

@section('title', $plan->title)

@section('monitoring-evaluation-content')
    @php
        $budget = $plan->budgetRequest;
    @endphp

    <x-page-toolbar :title="$plan->title" :meta="($plan->department?->dept_name ?? 'Department').' · '.\App\Support\StatusTone::label($plan->status)">
        <x-slot:actions>
            <a href="{{ route('monitoring_evaluation.plans.index', ['status' => 'me_review']) }}" class="tich-btn tich-btn-ghost">Back to queue</a>
            <a href="{{ route('monitoring_evaluation.plans.print', ['plan' => $plan, 'quarter' => 'all']) }}" class="tich-btn tich-btn-secondary">Print plan</a>
            <a href="{{ route('monitoring_evaluation.plans.pdf', ['plan' => $plan, 'quarter' => 'all']) }}" class="tich-btn tich-btn-secondary">Download plan PDF</a>
            @if ($plan->quarterlyReports->isNotEmpty())
                <a href="{{ route('monitoring_evaluation.plans.quarters.report-pdf', ['plan' => $plan, 'quarter' => 'all']) }}" class="tich-btn tich-btn-primary">Download reports PDF</a>
            @endif
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
            @php $clearedCount = $plan->quarters->where('status', 'me_approved')->count(); @endphp
            @if ($plan->isBaselineLocked())
                <p class="tich-text tich-mt-2">Baseline locked {{ $plan->baseline_locked_at?->format('d M Y H:i') }}.</p>
                <div class="tich-mt-4" style="display:flex;flex-wrap:wrap;gap:0.5rem;">
                    @foreach ($plan->quarters as $q)
                        <a href="{{ route('monitoring_evaluation.plans.open-quarter', [$plan, $q->quarter_number]) }}" class="tich-btn tich-btn-secondary">Open {{ $q->label() }} report</a>
                    @endforeach
                </div>
            @elseif (in_array($plan->status, ['me_review', 'returned', 'me_approved'], true))
                <p class="tich-caption tich-mt-2">Review the technical plan outputs below, then approve or return to the department. {{ $clearedCount }} of 4 quarters approved.</p>
                <form method="POST" action="{{ route('monitoring_evaluation.plans.approve', $plan) }}" class="tich-form-stack tich-mt-4">
                    @csrf
                    <textarea name="me_notes" class="tich-input" rows="2" placeholder="Optional approval notes">{{ old('me_notes') }}</textarea>
                    <button type="submit" class="tich-btn tich-btn-primary">Approve filed quarters</button>
                </form>
                <form method="POST" action="{{ route('monitoring_evaluation.plans.return', $plan) }}" class="tich-form-stack tich-mt-4">
                    @csrf
                    <textarea name="me_notes" class="tich-input" rows="2" required placeholder="Return reason">{{ old('me_notes') }}</textarea>
                    <button type="submit" class="tich-btn tich-btn-secondary">Return to department</button>
                </form>
                @if ($plan->status === 'me_approved')
                    <form method="POST" action="{{ route('monitoring_evaluation.plans.lock', $plan) }}" class="tich-mt-4">
                        @csrf
                        <button type="submit" class="tich-btn tich-btn-primary">Try baseline lock</button>
                    </form>
                    <p class="tich-caption tich-mt-2">Locks only when Q1 to Q4 are filled and approved and the linked budget is approved.</p>
                @endif
            @else
                <p class="tich-caption tich-mt-2">No actions available for status <strong>{{ \App\Support\StatusTone::label($plan->status) }}</strong>.</p>
            @endif
        </article>
    </div>

    @php
        $hasQuarterTags = $plan->outputs->contains(fn ($o) => $o->quarter !== null);
        $quarterRows = $plan->quartersByNumber();
    @endphp

    @if ($hasQuarterTags)
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Technical plan</h2>
            <p class="tich-caption tich-mt-1">
                Approve the quarters the department has filed. The baseline plan only locks once Q1 to Q4 are filled and approved.
            </p>
        </div>

        @foreach (\App\Http\Controllers\Module\TechnicalPlanController::QUARTERS as $qKey => $qLabel)
            @php
                $qRows = $plan->outputs->filter(fn ($o) => (int) $o->quarter === (int) $qKey);
                $qRow = $quarterRows[(int) $qKey] ?? null;
            @endphp
            <div class="tich-card tich-mt-4">
                <div class="tich-flex-wrap" style="justify-content:space-between;align-items:center;gap:0.75rem;">
                    <h2 class="tich-h3">{{ $qLabel }}</h2>
                    <div class="tich-flex-wrap" style="gap:0.5rem;">
                        @if ($qRow)
                            <x-status-badge :status="$qRow->status" :label="$qRow->statusLabel()" />
                        @endif
                        <a href="{{ route('monitoring_evaluation.plans.pdf', ['plan' => $plan, 'quarter' => (int) $qKey]) }}" class="tich-btn tich-btn-secondary">Q{{ $qKey }} plan PDF</a>
                        @if ($qRow && $plan->quarterlyReports->contains('quarter_id', $qRow->id))
                            <a href="{{ route('monitoring_evaluation.plans.quarters.report-pdf', ['plan' => $plan, 'quarter' => (int) $qKey]) }}" class="tich-btn tich-btn-secondary">Q{{ $qKey }} report PDF</a>
                        @endif
                        @if ($qRow?->isApproved())
                            <a href="{{ route('monitoring_evaluation.plans.open-quarter', [$plan, $qKey]) }}" class="tich-btn tich-btn-primary">Open {{ $qLabel }} report</a>
                        @endif
                    </div>
                </div>

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
                            @forelse ($qRows as $index => $out)
                                <tr>
                                    <td class="tich-caption">{{ $index + 1 }}</td>
                                    <td style="white-space:pre-wrap;"><strong>{{ $out->output }}</strong></td>
                                    <td style="white-space:pre-wrap;">{{ $out->activity }}</td>
                                    <td>{{ $out->costable_item ?: '-' }}</td>
                                    <td>{{ number_format((float) $out->planned, 2) }}</td>
                                    <td>{{ $out->planned_unit ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="tich-caption">The department has not filed {{ $qLabel }} yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($qRow?->review_notes)
                    <p class="tich-caption tich-mt-3">M&amp;E note on this quarter: {{ $qRow->review_notes }}</p>
                @endif

                @if (! $plan->isBaselineLocked() && $qRows->isNotEmpty() && ! $qRow?->isApproved())
                    <div class="tich-grid tich-grid--2 tich-mt-4" style="gap:1rem;">
                        <form method="POST" action="{{ route('monitoring_evaluation.plans.quarters.approve', [$plan, $qKey]) }}" class="tich-form-stack">
                            @csrf
                            <textarea name="me_notes" class="tich-input" rows="2" placeholder="Optional approval notes">{{ old('me_notes') }}</textarea>
                            <button type="submit" class="tich-btn tich-btn-primary">Approve {{ $qLabel }}</button>
                        </form>
                        <form method="POST" action="{{ route('monitoring_evaluation.plans.quarters.return', [$plan, $qKey]) }}" class="tich-form-stack">
                            @csrf
                            <textarea name="me_notes" class="tich-input" rows="2" required placeholder="Return reason">{{ old('me_notes') }}</textarea>
                            <button type="submit" class="tich-btn tich-btn-secondary">Return {{ $qLabel }}</button>
                        </form>
                    </div>
                @endif
            </div>
        @endforeach
    @else
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
                                <td style="white-space:pre-wrap;"><strong>{{ $out->output }}</strong></td>
                                <td style="white-space:pre-wrap;">{{ $out->activity }}</td>
                                <td>{{ $out->costable_item ?: '-' }}</td>
                                <td>{{ number_format((float) $out->planned, 2) }}</td>
                                <td>{{ $out->planned_unit ?: '-' }}</td>
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
    @endif

    @if ($budget)
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Linked budget request</h2>
            <p class="tich-caption tich-mt-1">
                {{ $budget->request_code }} · {{ $budget->title }} ·
                KES {{ number_format((float) $budget->requested_amount, 2) }} ·
                {{ str_replace('_', ' ', $budget->status) }}
            </p>
            @if ($budget->justification)
                <p class="budrev-justification tich-mt-3">{{ $budget->justification }}</p>
            @endif
        </div>

        @include('partials.budget-request-breakdown', ['budgetRequest' => $budget, 'idPrefix' => 'me-budrev'])
    @endif
@endsection
