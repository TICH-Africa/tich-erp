@php
    use App\Services\Qa\IqaAssessmentSchema;

    $sectionMeta = $meta[$section] ?? ['number' => (string) $section, 'title' => '', 'short' => ''];
    $sectionKey = (string) $section;
    $sectionData = $payload['sections'][$sectionKey] ?? ['items' => [], 'tables' => [], 'overall_recommendations' => ''];
    $isPublishReview = ($mode ?? 'edit') === 'publish-review';
    $readonly = $isPublishReview;
    $formAction = $isPublishReview
        ? null
        : route('qa.assessments.update', ['assessment' => $assessment, 'section' => $section]);
@endphp

@extends('layouts.qa')

@section('title', 'IQA — '.$sectionMeta['short'])

@section('qa-content')
    <x-page-toolbar
        title="{{ $sectionMeta['number'] }} {{ $sectionMeta['title'] }}"
        :meta="$isPublishReview ? 'Publish walkthrough — review every section, then confirm' : 'Draft — edit freely; publish locks forever'"
    >
        <x-slot:actions>
            <a href="{{ route('qa.assessments.index') }}" class="tich-btn tich-btn-ghost">All assessments</a>
            @if (! $isPublishReview && $section === 8)
                <a href="{{ route('qa.assessments.publish-review', ['assessment' => $assessment, 'section' => 1]) }}" class="tich-btn tich-btn-primary">Start publish review</a>
            @endif
        </x-slot:actions>
    </x-page-toolbar>

    <nav class="tich-card tich-mt-6" aria-label="Assessment sections" style="padding:0.75rem 1rem;">
        <div class="tich-flex tich-flex--wrap" style="gap:0.35rem;">
            @foreach ($meta as $n => $m)
                @php
                    $href = $isPublishReview
                        ? route('qa.assessments.publish-review', ['assessment' => $assessment, 'section' => $n])
                        : route('qa.assessments.edit', ['assessment' => $assessment, 'section' => $n]);
                    $walkedOk = $isPublishReview && in_array((int) $n, $walked ?? [], true);
                @endphp
                <a href="{{ $href }}"
                   class="tich-btn tich-btn--sm {{ (int) $n === (int) $section ? 'tich-btn-primary' : 'tich-btn-secondary' }}">
                    {{ $m['number'] }}
                    @if ($walkedOk) ✓ @endif
                </a>
            @endforeach
        </div>
    </nav>

    @if ($isPublishReview)
        <div class="tich-alert tich-mt-4" style="background:#eff6ff;border:1px solid #bfdbfe;">
            <p class="tich-text">Walk through each section using Next. When all eight are reviewed, confirm publish below.</p>
            <p class="tich-caption tich-mt-2">Reviewed: {{ count($walked ?? []) }}/8</p>
        </div>
    @endif

    @if ($section <= 7)
        @if ($formAction)
            <form method="POST" action="{{ $formAction }}" class="tich-mt-6" id="iqa-section-form">
                @csrf
                @method('PUT')
                @include('qa.assessments.partials.section-body', [
                    'section' => $section,
                    'sectionData' => $sectionData,
                    'readonly' => false,
                ])
                @include('qa.assessments.partials.wizard-nav', [
                    'section' => $section,
                    'isPublishReview' => false,
                ])
            </form>
        @else
            <div class="tich-mt-6">
                @include('qa.assessments.partials.section-body', [
                    'section' => $section,
                    'sectionData' => $sectionData,
                    'readonly' => true,
                ])
                @include('qa.assessments.partials.wizard-nav', [
                    'section' => $section,
                    'isPublishReview' => true,
                ])
            </div>
        @endif
    @else
        {{-- Section 8 Quality Auditors --}}
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Quality Auditors</h2>
            <p class="tich-caption tich-mt-2">On publish, the QA Officer who publishes is recorded (name + date). Signature is left blank for wet-ink on the printed PDF.</p>
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
                        @php $auditors = $payload['auditors'] ?? [['name'=>'','date'=>'','signature'=>'']]; @endphp
                        @foreach ($auditors as $i => $auditor)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $auditor['name'] ?: ($isPublishReview ? auth()->user()->displayName() : '(filled on publish)') }}</td>
                                <td class="tich-caption">{{ $auditor['signature'] ?: '—' }}</td>
                                <td>{{ $auditor['date'] ?: ($isPublishReview ? now()->format('Y-m-d') : '(filled on publish)') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if ($isPublishReview)
            @include('qa.assessments.partials.wizard-nav', [
                'section' => 8,
                'isPublishReview' => true,
            ])
            @if ($walkthroughComplete ?? false)
                <form method="POST" action="{{ route('qa.assessments.publish', $assessment) }}" class="tich-card tich-mt-6" style="border-color:#1669a6;">
                    @csrf
                    <h3 class="tich-h3">Confirm publication</h3>
                    <p class="tich-text tich-mt-2">Publishing locks this assessment forever. Create a new assessment for a later audit.</p>
                    <label class="tich-flex tich-mt-4" style="gap:0.5rem;align-items:flex-start;">
                        <input type="checkbox" name="confirm" value="1" required>
                        <span>I have reviewed all sections and confirm publication as QA Officer.</span>
                    </label>
                    <div class="tich-mt-4">
                        <button type="submit" class="tich-btn tich-btn-primary">Publish &amp; lock</button>
                    </div>
                </form>
            @endif
        @else
            <form method="POST" action="{{ route('qa.assessments.update', ['assessment' => $assessment, 'section' => 8]) }}" class="tich-mt-4">
                @csrf
                @method('PUT')
                @include('qa.assessments.partials.wizard-nav', [
                    'section' => 8,
                    'isPublishReview' => false,
                ])
            </form>
        @endif
    @endif
@endsection
