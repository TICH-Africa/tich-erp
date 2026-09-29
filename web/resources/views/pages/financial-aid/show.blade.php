@extends('layouts.app')

@section('title', $opportunity->title . ' | Financial Aid')
@section('meta_description', Str::limit(strip_tags($opportunity->description), 160))

@section('content')
    <header class="tich-course-page-header" style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);">
        <div class="tich-container">
            <div class="tich-flex tich-flex--wrap tich-flex--between tich-mb-4">
                <div>
                    @php
                        $typeColors = [
                            'scholarship' => 'bg-blue-100 text-blue-800',
                            'grant' => 'bg-green-100 text-green-800',
                            'loan' => 'bg-amber-100 text-amber-800',
                            'work_study' => 'bg-purple-100 text-purple-800',
                        ];
                    @endphp
                    <span class="tich-badge {{ $typeColors[$opportunity->funding_type] ?? 'bg-gray-100 text-gray-800' }} tich-mb-3">
                        {{ ucfirst(str_replace('_', ' ', $opportunity->funding_type)) }}
                    </span>
                    <h1 class="tich-course-page-header__title">{{ $opportunity->title }}</h1>
                    @if ($opportunity->amount)
                        <p class="tich-course-page-header__lead tich-mt-2">
                            <strong>Amount: KES {{ number_format((float) $opportunity->amount, 2) }}</strong>
                        </p>
                    @endif
                </div>
                <div class="tich-flex tich-flex--wrap tich-gap-4 tich-text--sm tich-text--muted">
                    @if ($opportunity->application_open_date || $opportunity->application_deadline)
                        <span>
                            <strong>Application Period:</strong>
                            @if ($opportunity->application_open_date)
                                {{ $opportunity->application_open_date->format('d M Y') }}
                            @else
                                Open
                            @endif
                            @if ($opportunity->application_deadline)
                                &ndash; {{ $opportunity->application_deadline->format('d M Y') }}
                            @else
                                &ndash; Open
                            @endif
                        </span>
                    @endif
                    <span>
                        <strong>Status:</strong>
                        @php
                            $statusColors = [
                                'published' => 'bg-green-100 text-green-800',
                                'closed' => 'bg-gray-100 text-gray-800',
                                'draft' => 'bg-yellow-100 text-yellow-800',
                            ];
                        @endphp
                        <span class="tich-badge {{ $statusColors[$opportunity->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst($opportunity->status) }}
                        </span>
                    </span>
                </div>
            </div>
        </div>
    </header>

    <section class="tich-section tich-mt-8">
        <div class="tich-container">
            <div class="tich-grid tich-grid--3" style="gap: 2rem;">
                <article class="tich-card" style="grid-column: span 2;">
                    <div class="tich-card__body">
                        <h2 class="tich-h3 tich-mb-4">About This Opportunity</h2>
                        <div class="tich-prose">
                            {!! nl2br(e($opportunity->description)) !!}
                        </div>

                        @if ($opportunity->eligibility_criteria)
                            <h3 class="tich-h4 tich-mt-8 tich-mb-4">Eligibility Criteria</h3>
                            <div class="tich-prose tich-border-l tich-border-blue-500 tich-pl-4">
                                {!! nl2br(e($opportunity->eligibility_criteria)) !!}
                            </div>
                        @endif

                        @if ($opportunity->application_process)
                            <h3 class="tich-h4 tich-mt-8 tich-mb-4">Application Process</h3>
                            <div class="tich-prose tich-border-l tich-border-green-500 tich-pl-4">
                                {!! nl2br(e($opportunity->application_process)) !!}
                            </div>
                        @endif
                    </div>
                </article>

                <aside class="tich-card" style="position: sticky; top: 2rem;">
                    <div class="tich-card__body">
                        <h3 class="tich-h4 tich-mb-4">Quick Details</h3>
                        <dl class="tich-dl tich-mb-6">
                            <dt>Funding Type</dt>
                            <dd>{{ ucfirst(str_replace('_', ' ', $opportunity->funding_type)) }}</dd>
                            @if ($opportunity->amount)
                                <dt>Amount</dt>
                                <dd>KES {{ number_format((float) $opportunity->amount, 2) }}</dd>
                            @endif
                            @if ($opportunity->application_open_date)
                                <dt>Applications Open</dt>
                                <dd>{{ $opportunity->application_open_date->format('d M Y') }}</dd>
                            @endif
                            @if ($opportunity->application_deadline)
                                <dt>Application Deadline</dt>
                                <dd>{{ $opportunity->application_deadline->format('d M Y') }}</dd>
                            @endif
                            <dt>Status</dt>
                            <dd>
                                @php
                                    $statusColors = [
                                        'published' => 'bg-green-100 text-green-800',
                                        'closed' => 'bg-gray-100 text-gray-800',
                                        'draft' => 'bg-yellow-100 text-yellow-800',
                                    ];
                                @endphp
                                <span class="tich-badge {{ $statusColors[$opportunity->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($opportunity->status) }}
                                </span>
                            </dd>
                        </dl>

                        @if ($isOpen)
                            <a href="{{ route('financial-aid.apply', $opportunity->slug) }}" class="tich-btn tich-btn-primary" style="width: 100%;">
                                Apply Now
                            </a>
                        @else
                            <div class="tich-alert tich-alert--warning tich-mb-4">
                                @if ($opportunity->application_deadline && $opportunity->application_deadline < now())
                                    <p class="tich-text tich-mb-0"><strong>Applications Closed</strong></p>
                                    <p class="tich-text tich-text--sm tich-text--muted tich-mt-1 tich-mb-0">The application deadline ({{ $opportunity->application_deadline->format('d M Y') }}) has passed.</p>
                                @elseif ($opportunity->application_open_date && $opportunity->application_open_date > now())
                                    <p class="tich-text tich-mb-0"><strong>Applications Not Yet Open</strong></p>
                                    <p class="tich-text tich-text--sm tich-text--muted tich-mt-1 tich-mb-0">Applications open on {{ $opportunity->application_open_date->format('d M Y') }}.</p>
                                @else
                                    <p class="tich-text tich-mb-0"><strong>Currently Not Accepting Applications</strong></p>
                                @endif
                            </div>
                        @endif

                        <hr class="tich-my-4">

                        <h3 class="tich-h5 tich-mb-3">Need Help?</h3>
                        <p class="tich-text tich-text--sm tich-text--muted">Contact our Financial Aid Office for assistance with your application.</p>
                        <a href="{{ route('contact') }}" class="tich-link tich-text--sm">Visit Contact Us page</a>
                    </div>
                </aside>
            </div>
        </div>
    </section>
@endsection