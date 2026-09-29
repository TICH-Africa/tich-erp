@php
    use App\Services\Qa\IqaAssessmentSchema;
@endphp

@extends('layouts.ceo')

@section('title', 'IQA assessment #'.$assessment->id)

@section('ceo-content')
    <x-page-toolbar
        title="{{ $assessment->title }}"
        :meta="'Year '.($assessment->assessment_year ?: '—').' · '.ucfirst($assessment->status)"
    >
        <x-slot:actions>
            <a href="{{ route('ceo.quality.index') }}" class="tich-btn tich-btn-ghost">Back</a>
            <a href="{{ route('ceo.quality.pdf', $assessment) }}" class="tich-btn tich-btn-secondary">Download PDF</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-mt-6">
        <dl class="tich-form-grid tich-form-grid--2">
            <div>
                <dt class="tich-caption">Status</dt>
                <dd><x-status-badge :status="$assessment->status" /></dd>
            </div>
            <div>
                <dt class="tich-caption">Assessment year</dt>
                <dd>{{ $assessment->assessment_year ?: '—' }}</dd>
            </div>
            <div>
                <dt class="tich-caption">Published by</dt>
                <dd>{{ $assessment->publisher_name ?: '—' }}</dd>
            </div>
            <div>
                <dt class="tich-caption">Published at</dt>
                <dd>{{ $assessment->published_at?->format('d M Y H:i') }}</dd>
            </div>
        </dl>
    </div>

    @foreach ($meta as $n => $m)
        @if ($n <= 7)
            @php $sectionData = $payload['sections'][(string) $n] ?? ['items' => [], 'tables' => [], 'overall_recommendations' => '']; @endphp
            <div class="tich-mt-8">
                <h2 class="tich-h2">{{ $m['number'] }} {{ $m['title'] }}</h2>
                @include('qa.assessments.partials.section-body', [
                    'section' => $n,
                    'sectionData' => $sectionData,
                    'readonly' => true,
                ])
            </div>
        @endif
    @endforeach

    <div class="tich-card tich-mt-8">
        <h2 class="tich-h3">8.0 Quality Auditors</h2>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>S/No</th>
                        <th>Name</th>
                        <th>Signature</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (($payload['auditors'] ?? []) as $i => $auditor)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $auditor['name'] ?: '—' }}</td>
                            <td>{{ $auditor['signature'] ?: '—' }}</td>
                            <td>{{ $auditor['date'] ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
