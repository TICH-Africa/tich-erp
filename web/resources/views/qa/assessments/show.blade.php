@extends('layouts.qa')

@section('title', $plan->plan_name)

@section('qa-content')
    <x-page-toolbar title="{{ $plan->plan_name }}" meta="Lifecycle: {{ str_replace('_', ' ', $plan->status) }}">
        <x-slot:actions>
            @if ($plan->isDraft())
                <a href="{{ route('qa.assessments.edit', $plan) }}" class="tich-btn tich-btn-secondary">Edit</a>
                <form method="POST" action="{{ route('qa.assessments.dispatch', $plan) }}" onsubmit="return confirm('Dispatch this sheet to selected departments and notify them?')">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-primary">Dispatch</button>
                </form>
            @endif
            @if (in_array($plan->status, ['dispatched', 'in_progress'], true))
                <form method="POST" action="{{ route('qa.assessments.compile', $plan) }}" onsubmit="return confirm('Compile scores and send the Quality Level Report to the CEO?')">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-primary">Compile report</button>
                </form>
            @endif
            <a href="{{ route('qa.assessments.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    @error('dispatch')<div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>@enderror
    @error('compile')<div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>@enderror

    <div class="tich-grid tich-grid--3 tich-mt-8">
        <article class="tich-card">
            <p class="tich-caption">Period</p>
            <p class="tich-text tich-mt-2">{{ $plan->period_start?->format('d M Y') }} – {{ $plan->period_end?->format('d M Y') }}</p>
            @if ($plan->due_at)<p class="tich-caption tich-mt-2">Due {{ $plan->due_at->format('d M Y H:i') }}</p>@endif
        </article>
        <article class="tich-card">
            <p class="tich-caption">Pass threshold</p>
            <p class="tich-h2 tich-mt-2">{{ $plan->pass_threshold }}%</p>
        </article>
        <article class="tich-card">
            <p class="tich-caption">Departments</p>
            <p class="tich-h2 tich-mt-2">{{ count($plan->targetDepartmentIds()) }}</p>
        </article>
    </div>

    @if ($plan->instructions)
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Instructions</h2>
            <p class="tich-text tich-mt-2">{{ $plan->instructions }}</p>
        </div>
    @endif

    <div class="tich-card tich-table-panel tich-mt-6">
        <h2 class="tich-h3">Evaluation criteria</h2>
        <table class="tich-admin-table tich-mt-4">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Criterion</th>
                    <th>Category</th>
                    <th>Weight</th>
                    <th>Max</th>
                    <th>Evidence</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($plan->checklists as $item)
                    <tr>
                        <td>{{ $item->display_order }}</td>
                        <td>{{ $item->checklist_item_text }}</td>
                        <td>{{ $item->item_category ?: '-' }}</td>
                        <td>{{ $item->weight }}</td>
                        <td>{{ (int) $item->max_score }}</td>
                        <td>{{ $item->requires_evidence ? 'Required' : 'Optional' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($plan->complianceScores->isNotEmpty() || (isset($departments) && $departments->isNotEmpty()))
        <div class="tich-card tich-table-panel tich-mt-6" id="department-responses">
            <h2 class="tich-h3">Department responses</h2>
            <p class="tich-caption tich-mt-2">Open a department to read submitted answers, scores, and attachments.</p>
            <table class="tich-admin-table tich-mt-4">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Progress</th>
                        <th>Score</th>
                        <th>Result</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (($departments ?? $plan->targetDepartments()) as $department)
                        @php
                            $score = $plan->complianceScores->firstWhere('department_id', $department->id);
                            $stats = $submissionStats[$department->id] ?? null;
                            $submittedRows = (int) ($stats->submitted_rows ?? 0);
                            $openRows = (int) ($stats->open_rows ?? 0);
                            $hasResponse = $submittedRows > 0 || ($score && (int) $score->items_submitted > 0);
                            $awaitingReview = $submittedRows > 0;
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $department->dept_name }}</strong>
                                @if ($awaitingReview)
                                    <span class="tich-signal-dot" title="Responses awaiting review" aria-label="Responses awaiting review" style="margin-left:0.35rem;"></span>
                                @endif
                            </td>
                            <td>
                                @if ($score)
                                    {{ $score->items_submitted }} / {{ $score->total_items }}
                                @elseif ($stats)
                                    {{ $submittedRows }} submitted · {{ $openRows }} open
                                @else
                                    Not started
                                @endif
                            </td>
                            <td>{{ $score ? number_format((float) $score->weighted_score, 1).'%' : '—' }}</td>
                            <td>
                                @if ($score)
                                    <x-status-badge :status="$score->pass_fail_status" />
                                    @if ($score->is_below_threshold)
                                        <span class="tich-caption" style="color:#b91c1c;">below threshold</span>
                                    @endif
                                @else
                                    <x-status-badge status="pending" />
                                @endif
                            </td>
                            <td>
                                @if ($hasResponse || ($stats && (int) ($stats->total_rows ?? 0) > 0))
                                    <a href="{{ route('qa.assessments.responses', [$plan, $department]) }}" class="tich-btn {{ $awaitingReview ? 'tich-btn-success' : 'tich-btn-secondary' }}">
                                        {{ $awaitingReview ? 'Review' : 'View' }}
                                    </a>
                                @else
                                    <span class="tich-caption">Waiting</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

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
