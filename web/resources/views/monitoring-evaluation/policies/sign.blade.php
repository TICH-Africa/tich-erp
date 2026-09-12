@php
    $moduleContext = $moduleContext ?? [
        'layout' => 'layouts.monitoring-evaluation',
        'content_section' => 'monitoring-evaluation-content',
        'key' => 'monitoring_evaluation',
    ];
    $signStoreRoute = $signStoreRoute ?? 'monitoring_evaluation.policy.sign.store';
    $canViewSignoffRoster = (bool) ($canViewSignoffRoster ?? false);
    $alreadySigned = (bool) ($alreadySigned ?? false);
@endphp

@extends($moduleContext['layout'])

@section('title', 'Sign M&E Policy')

@section($moduleContext['content_section'])
    <x-page-toolbar
        title="Mandatory M&E policy sign-off"
        :meta="$policy->title.' · '.$policy->fiscal_year.($department ? ' · '.$department->dept_name : '')"
    />

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <article class="tich-card tich-mt-6">
        <p class="tich-text">
            @if ($department)
                The Head of <strong>{{ $department->dept_name }}</strong> must view and digitally sign this policy before submitting annual budgets and departmental plans for this unit.
            @else
                Heads of Department must view and digitally sign this policy before submitting annual budgets and departmental plans.
            @endif
        </p>
        @if ($policy->description)
            <p class="tich-caption tich-mt-2">{{ $policy->description }}</p>
        @endif
        @if ($alreadySigned)
            <div class="tich-alert tich-alert--success tich-mt-4">{{ $department?->dept_name ?? 'This department' }} has already signed this policy.</div>
        @endif
    </article>

    @if (($document['exists'] ?? false))
        <article class="tich-card tich-mt-6">
            <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:1rem;align-items:center;">
                <div>
                    <h2 class="tich-h3">Policy document</h2>
                    <p class="tich-caption tich-mt-2">{{ $document['filename'] }}</p>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
                    <a
                        href="{{ route('monitoring_evaluation.policies.view', $policy) }}"
                        class="tich-btn tich-btn-secondary"
                        target="_blank"
                        rel="noopener"
                    >Open in new tab</a>
                    <a href="{{ route('monitoring_evaluation.policies.download', $policy) }}" class="tich-btn tich-btn-ghost">Download</a>
                </div>
            </div>

            <div class="doc-viewer tich-mt-6" style="grid-template-columns:1fr;">
                <div class="doc-viewer__panel">
                    <div class="doc-viewer__toolbar">
                        <strong>{{ $policy->title }}</strong>
                        <span class="tich-caption">{{ $document['filename'] }}</span>
                    </div>
                    <div class="doc-viewer__stage">
                        @if ($document['is_previewable'])
                            @if (str_starts_with($document['mime'], 'image/'))
                                <img
                                    src="{{ route('monitoring_evaluation.policies.view', $policy) }}"
                                    alt="{{ $policy->title }}"
                                    class="doc-viewer__image"
                                >
                            @else
                                <iframe
                                    src="{{ route('monitoring_evaluation.policies.view', $policy) }}"
                                    title="{{ $policy->title }}"
                                    class="doc-viewer__frame"
                                ></iframe>
                            @endif
                        @else
                            <div class="doc-viewer__fallback">
                                <p class="tich-text">This file type cannot be previewed in the browser.</p>
                                <a href="{{ route('monitoring_evaluation.policies.download', $policy) }}" class="tich-btn tich-btn-primary tich-mt-4">Download file</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </article>
        @include('applications.partials.document-viewer-styles')
    @else
        <div class="tich-alert tich-alert--info tich-mt-6">No policy file is attached to the published policy yet.</div>
    @endif

    @unless ($alreadySigned)
        <article class="tich-card tich-mt-6">
            <h2 class="tich-h3">Digital sign-off{{ $department ? ' · '.$department->dept_name : '' }}</h2>
            <form method="POST" action="{{ route($signStoreRoute) }}" class="tich-form-stack tich-mt-4">
                @csrf
                <div class="tich-grid tich-grid--2">
                    <div>
                        <label class="tich-label" for="signed_name">Full name *</label>
                        <input type="text" id="signed_name" name="signed_name" class="tich-input" required value="{{ old('signed_name', $staff?->fullName()) }}">
                    </div>
                    <div>
                        <label class="tich-label" for="employee_number">Employee number</label>
                        <input type="text" id="employee_number" name="employee_number" class="tich-input" value="{{ old('employee_number', $staff?->employee_number) }}">
                    </div>
                    <div class="tich-grid--span-2">
                        <label class="tich-label" for="signature">Digital signature</label>
                        <input type="text" id="signature" name="signature" class="tich-input" value="{{ old('signature') }}" placeholder="Type your full name as digital signature">
                    </div>
                </div>
                <button type="submit" class="tich-btn tich-btn-primary">I have read and sign this M&amp;E policy</button>
            </form>
        </article>
    @endunless

    @if ($canViewSignoffRoster && ($signoff ?? null))
        <article class="tich-card tich-table-panel tich-mt-6">
            <h2 class="tich-h3">Department sign-off progress</h2>
            <p class="tich-caption tich-mt-2">{{ $signoff['signed'] }} / {{ $signoff['total'] }} departments signed</p>
            <div class="tich-table-wrap tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Code</th>
                            <th>Signed by</th>
                            <th>Employee no.</th>
                            <th>Signed at</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($signoff['departments'] as $dept)
                            <tr>
                                <td>{{ $dept['name'] }}</td>
                                <td>{{ $dept['code'] }}</td>
                                <td>{{ $dept['signed'] ? ($dept['signed_name'] ?: '—') : '—' }}</td>
                                <td>{{ $dept['signed'] ? ($dept['employee_number'] ?: '—') : '—' }}</td>
                                <td>{{ $dept['signed'] ? ($dept['signed_at'] ?: '—') : '—' }}</td>
                                <td>
                                    <x-status-badge :status="$dept['signed'] ? 'signed' : 'pending'" :label="$dept['signed'] ? 'Signed' : 'Pending'" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
    @endif
@endsection
