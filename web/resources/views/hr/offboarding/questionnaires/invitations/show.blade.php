@extends('layouts.hr')

@section('title', 'Offboarding questionnaire response')

@section('hr-content')
    <x-page-toolbar :title="$invitation->staff?->fullName() ?? 'Questionnaire'" meta="Offboarding exit feedback">
        <x-slot:actions>
            <a href="{{ route('hr.offboarding.questionnaires.invitations.index') }}" class="tich-btn tich-btn-ghost">← All sent</a>
            @if ($invitation->staff)
                <a href="{{ route('hr.staff.show', $invitation->staff) }}" class="tich-btn tich-btn-ghost">Staff profile</a>
            @endif
        </x-slot:actions>
    </x-page-toolbar>

    <section class="tich-leave-hero tich-mt-6 tich-mb-6">
        <div>
            <p class="tich-caption">Exit questionnaire</p>
            <h1 class="tich-leave-hero__title">{{ $invitation->template?->title ?? 'Questionnaire' }}</h1>
            <div class="tich-leave-hero__meta">
                <span class="tich-badge tich-badge--{{ $invitation->statusBadgeTone() }}">
                    {{ $invitation->statusLabel() }}
                </span>
                @if ($invitation->staff?->employee_number)
                    <span class="tich-caption">{{ $invitation->staff->employee_number }}</span>
                @endif
                @if ($invitation->staff?->employment_category)
                    <span class="tich-caption">{{ $categoryLabels[$invitation->staff->employment_category] ?? \App\Support\EmploymentCategory::label($invitation->staff->employment_category) }}</span>
                @endif
            </div>
        </div>
        <div class="tich-leave-hero__stat">
            <span class="tich-caption">Expected departure</span>
            <span class="tich-leave-hero__stat-value" style="font-size:1.125rem;">
                {{ $prefill['expected_departure_date'] ?? ($invitation->contract?->end_date?->format('d M Y') ?? '-') }}
            </span>
        </div>
    </section>

    <div class="tich-grid tich-grid--2 tich-mb-6" style="align-items: start; gap: 1.25rem;">
        <article class="tich-card tich-card--flush">
            <div class="tich-card__head">
                <h2 class="tich-h3">Employee snapshot</h2>
                <p class="tich-caption">Values copied when HR sent the form</p>
            </div>
            <div class="tich-card__body">
                <div class="tich-kv-grid">
                    <div>
                        <span class="tich-kv-grid__label">Name</span>
                        <span class="tich-kv-grid__value">{{ $prefill['employee_name'] ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="tich-kv-grid__label">Employee ID</span>
                        <span class="tich-kv-grid__value">{{ $prefill['employee_number'] ?? '-' }}</span>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <span class="tich-kv-grid__label">Position(s)</span>
                        <span class="tich-kv-grid__value tich-kv-grid__value--block">{{ implode(', ', $prefill['positions'] ?? []) ?: '-' }}</span>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <span class="tich-kv-grid__label">Department(s)</span>
                        <span class="tich-kv-grid__value tich-kv-grid__value--block">{{ implode(', ', $prefill['departments'] ?? []) ?: '-' }}</span>
                    </div>
                    <div>
                        <span class="tich-kv-grid__label">Date joined</span>
                        <span class="tich-kv-grid__value">{{ $prefill['date_joined'] ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="tich-kv-grid__label">Expected departure</span>
                        <span class="tich-kv-grid__value">{{ $prefill['expected_departure_date'] ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </article>

        <article class="tich-card tich-card--flush">
            <div class="tich-card__head">
                <h2 class="tich-h3">Send &amp; workflow</h2>
            </div>
            <div class="tich-card__body">
                <div class="tich-kv-grid">
                    <div>
                        <span class="tich-kv-grid__label">Sent to employee</span>
                        <span class="tich-kv-grid__value">{{ $invitation->sent_at?->format('d M Y, H:i') ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="tich-kv-grid__label">Sent by</span>
                        <span class="tich-kv-grid__value">{{ $invitation->sender?->displayName() ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="tich-kv-grid__label">Submitted</span>
                        <span class="tich-kv-grid__value">
                            @if ($invitation->submitted_at)
                                {{ $invitation->submitted_at->format('d M Y, H:i') }}
                            @else
                                <span class="tich-caption">Not yet</span>
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="tich-kv-grid__label">HR seen</span>
                        <span class="tich-kv-grid__value">
                            @if ($invitation->hr_acknowledged_at)
                                {{ $invitation->hr_acknowledged_at->format('d M Y, H:i') }}
                            @else
                                <span class="tich-caption">Pending first view</span>
                            @endif
                        </span>
                    </div>
                    @if ($invitation->contract)
                        <div style="grid-column: 1 / -1;">
                            <span class="tich-kv-grid__label">Linked contract</span>
                            <span class="tich-kv-grid__value">
                                {{ \App\Support\EmploymentCategory::label($invitation->contract->contract_type) }}
                                @if ($invitation->contract->end_date)
                                    · ends {{ $invitation->contract->end_date->format('d M Y') }}
                                @endif
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </article>
    </div>

    <article class="tich-card tich-card--flush ob-q-responses-panel">
        <div class="tich-card__head tich-flex tich-flex--between tich-flex--center" style="flex-wrap: wrap; gap: 0.75rem;">
            <div>
                <h2 class="tich-h3" style="margin: 0;">Responses</h2>
                <p class="tich-caption">
                    @if ($invitation->status === 'submitted')
                        {{ $invitation->answers->count() }} answer(s) recorded
                    @else
                        Employee has not submitted yet
                    @endif
                </p>
            </div>
            @if ($invitation->isPending())
                <span class="tich-badge tich-badge--warning">Action required from employee</span>
            @endif
        </div>
        <div class="tich-card__body">
            @if ($invitation->status !== 'submitted')
                <div class="ob-q-empty">
                    <p class="tich-h4" style="margin: 0 0 0.35rem;">Waiting for submission</p>
                    <p class="tich-caption">The employee will see this questionnaire in their portal. You will be notified when they submit.</p>
                </div>
            @else
                <div class="ob-q-response-list">
                    @foreach ($invitation->template?->questions ?? [] as $question)
                        @php $answer = $invitation->answers->firstWhere('question_id', $question->id); @endphp
                        <div class="ob-q-response-item">
                            <div class="ob-q-response-item__head">
                                <span class="ob-q-response-item__num">{{ $loop->iteration }}</span>
                                <p class="ob-q-response-item__question">{{ $question->question_text }}</p>
                            </div>
                            <div class="ob-q-response-item__answer">
                                {{ $answer?->answer_text ? trim($answer->answer_text) : '—' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </article>

    <style>
        .tich-card--flush .tich-card__head {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--tich-border, #e2e6ea);
        }

        .tich-card--flush .tich-card__body {
            padding: 1.25rem;
        }

        .ob-q-responses-panel.tich-card {
            border: none;
            box-shadow: none;
            background: transparent;
        }

        .ob-q-responses-panel .tich-card__head {
            border-bottom: none;
            padding: 1rem 0 0.75rem;
        }

        .ob-q-responses-panel .tich-card__body {
            padding: 0;
        }

        .ob-q-empty {
            text-align: center;
            padding: 2rem 1rem;
            background: var(--tich-surface-muted, #f0f2f5);
            border-radius: 4px;
            border: 1px dashed var(--tich-border, #c4cad2);
        }

        .ob-q-response-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .ob-q-response-item {
            border: none;
            border-radius: 4px;
            overflow: hidden;
            background: var(--tich-white, #fff);
        }

        .ob-q-response-item__head {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: var(--tich-surface-muted, #f8f9fa);
            border-bottom: 1px solid var(--tich-border, #e2e6ea);
        }

        .ob-q-response-item__num {
            flex-shrink: 0;
            width: 1.75rem;
            height: 1.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 4px;
            background: #1565c0;
            color: #fff;
        }

        .ob-q-response-item__question {
            margin: 0;
            font-size: 0.875rem;
            font-weight: 600;
            line-height: 1.4;
        }

        .ob-q-response-item__answer {
            display: block;
            padding: 1rem;
            font-size: 0.9375rem;
            line-height: 1.5;
            white-space: pre-wrap;
        }

        [data-theme="dark"] .ob-q-empty {
            background: #454b54;
        }

        [data-theme="dark"] .ob-q-response-item {
            background: #2d3239;
        }

        [data-theme="dark"] .ob-q-response-item__head {
            background: #454b54;
        }
    </style>
@endsection