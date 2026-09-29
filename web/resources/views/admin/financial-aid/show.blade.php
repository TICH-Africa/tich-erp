@extends('layouts.admin')

@section('title', $financialAidOpportunity->title)

@section('content')
    <x-page-toolbar title="{{ $financialAidOpportunity->title }}" meta="{{ ucfirst($financialAidOpportunity->funding_type) }}">
        <x-slot:actions>
            <a href="{{ route('admin.financial-aid.applications', $financialAidOpportunity) }}" class="tich-btn tich-btn-primary">
                Applications ({{ $financialAidOpportunity->applications_count }})
            </a>
            <a href="{{ route('admin.financial-aid.edit', $financialAidOpportunity) }}" class="tich-btn tich-btn-secondary">Edit</a>
            <a href="{{ route('admin.financial-aid.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--3 tich-mt-6" style="gap: 1.5rem;">
        <article class="tich-card">
            <div class="tich-card__body">
                <h3 class="tich-h4 tich-mb-4">Overview</h3>
                <dl class="tich-dl">
                    <dt>Title</dt><dd>{{ $financialAidOpportunity->title }}</dd>
                    <dt>Slug</dt><dd>{{ $financialAidOpportunity->slug }}</dd>
                    <dt>Funding Type</dt>
                        <dd>
                            @php
                                $typeColors = [
                                    'scholarship' => 'bg-blue-100 text-blue-800',
                                    'grant' => 'bg-green-100 text-green-800',
                                    'loan' => 'bg-amber-100 text-amber-800',
                                    'work_study' => 'bg-purple-100 text-purple-800',
                                ];
                            @endphp
                            <span class="tich-badge {{ $typeColors[$financialAidOpportunity->funding_type] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst(str_replace('_', ' ', $financialAidOpportunity->funding_type)) }}
                            </span>
                        </dd>
                    <dt>Amount</dt><dd>{{ $financialAidOpportunity->amount ? 'KES ' . number_format((float) $financialAidOpportunity->amount, 2) : 'Not specified' }}</dd>
                    <dt>Status</dt>
                        <dd>
                            @php
                                $statusColors = [
                                    'draft' => 'bg-yellow-100 text-yellow-800',
                                    'published' => 'bg-green-100 text-green-800',
                                    'closed' => 'bg-gray-100 text-gray-800',
                                    'archived' => 'bg-red-100 text-red-800',
                                ];
                            @endphp
                            <span class="tich-badge {{ $statusColors[$financialAidOpportunity->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($financialAidOpportunity->status) }}
                            </span>
                        </dd>
                    <dt>Applications Open</dt><dd>{{ $financialAidOpportunity->application_open_date?->format('d M Y') ?? 'Open' }}</dd>
                    <dt>Deadline</dt><dd>{{ $financialAidOpportunity->application_deadline?->format('d M Y') ?? 'Open' }}</dd>
                    <dt>Applications</dt><dd>{{ $financialAidOpportunity->applications_count }}</dd>
                    <dt>Created</dt><dd>{{ $financialAidOpportunity->created_at->format('d M Y H:i') }}</dd>
                    <dt>Published</dt><dd>{{ $financialAidOpportunity->published_at?->format('d M Y H:i') ?? 'Not published' }}</dd>
                </dl>
            </div>
        </article>

        <article class="tich-card" style="grid-column: span 2;">
            <div class="tich-card__body">
                <h3 class="tich-h4 tich-mb-4">Description</h3>
                <div class="tich-prose">
                    {!! nl2br(e($financialAidOpportunity->description)) !!}
                </div>

                @if ($financialAidOpportunity->eligibility_criteria)
                    <h4 class="tich-h5 tich-mt-6 tich-mb-3">Eligibility Criteria</h4>
                    <div class="tich-prose tich-border-l tich-border-blue-500 tich-pl-4">
                        {!! nl2br(e($financialAidOpportunity->eligibility_criteria)) !!}
                    </div>
                @endif

                @if ($financialAidOpportunity->application_process)
                    <h4 class="tich-h5 tich-mt-6 tich-mb-3">Application Process</h4>
                    <div class="tich-prose tich-border-l tich-border-green-500 tich-pl-4">
                        {!! nl2br(e($financialAidOpportunity->application_process)) !!}
                    </div>
                @endif
            </div>
        </article>
    </div>

    <div class="tich-mt-6">
        <a href="{{ route('financial-aid.show', $financialAidOpportunity->slug) }}" target="_blank" class="tich-btn tich-btn-ghost">
            View Public Page
        </a>
    </div>
@endsection