@extends('layouts.sis')

@section('sis-content')
    <a href="{{ route('sis.students.index') }}" class="tich-link">&larr; Back to student records</a>

    <div class="tich-mt-4 tich-mb-8">
        <p class="tich-caption">360° student record</p>
        <h1 class="tich-h1" style="font-size: 2rem;">{{ $biodata['identity']['full_name'] }}</h1>
        <p class="tich-text tich-mt-2">
            {{ $student->registration_number }}
            · {{ $biodata['academic']['program'] ?? '-' }}
            · {{ ucfirst($student->enrollment_status) }}
        </p>
    </div>

    <div class="tich-grid tich-grid--2" style="align-items: start; gap: 1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Identity</h2>
            <dl style="display: grid; grid-template-columns: 9rem 1fr; gap: 0.5rem 1rem; margin: 1rem 0 0;">
                @foreach ($biodata['identity'] as $label => $value)
                    <dt class="tich-caption">{{ ucwords(str_replace('_', ' ', $label)) }}</dt>
                    <dd>{{ $value ?: '-' }}</dd>
                @endforeach
            </dl>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Contact</h2>
            <dl style="display: grid; grid-template-columns: 9rem 1fr; gap: 0.5rem 1rem; margin: 1rem 0 0;">
                @foreach ($biodata['contact'] as $label => $value)
                    <dt class="tich-caption">{{ ucwords(str_replace('_', ' ', $label)) }}</dt>
                    <dd>{{ $value ?: '-' }}</dd>
                @endforeach
            </dl>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Academic profile</h2>
            <dl style="display: grid; grid-template-columns: 9rem 1fr; gap: 0.5rem 1rem; margin: 1rem 0 0;">
                @foreach ($biodata['academic'] as $label => $value)
                    <dt class="tich-caption">{{ ucwords(str_replace('_', ' ', $label)) }}</dt>
                    <dd>{{ $value ?: '-' }}</dd>
                @endforeach
            </dl>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Application trail</h2>
            <dl style="display: grid; grid-template-columns: 9rem 1fr; gap: 0.5rem 1rem; margin: 1rem 0 0;">
                @foreach ($biodata['application'] as $label => $value)
                    <dt class="tich-caption">{{ ucwords(str_replace('_', ' ', $label)) }}</dt>
                    <dd>{{ $value ?: '-' }}</dd>
                @endforeach
            </dl>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Enrollment &amp; finance</h2>
            <dl style="display: grid; grid-template-columns: 9rem 1fr; gap: 0.5rem 1rem; margin: 1rem 0 0;">
                @foreach ($biodata['enrollment'] as $label => $value)
                    <dt class="tich-caption">{{ ucwords(str_replace('_', ' ', $label)) }}</dt>
                    <dd>{{ $value ?: '-' }}</dd>
                @endforeach
            </dl>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Student portal</h2>
            <dl style="display: grid; grid-template-columns: 9rem 1fr; gap: 0.5rem 1rem; margin: 1rem 0 0;">
                <dt class="tich-caption">Account</dt>
                <dd>{{ $biodata['portal']['has_account'] ? 'Activated' : 'Not activated' }}</dd>
                <dt class="tich-caption">Username</dt>
                <dd>{{ $biodata['portal']['username'] ?? '-' }}</dd>
                <dt class="tich-caption">Last login</dt>
                <dd>{{ $biodata['portal']['last_login_at'] ?? '-' }}</dd>
                <dt class="tich-caption">Invite</dt>
                <dd>{{ $biodata['portal']['invite_pending'] ? 'Pending activation' : '-' }}</dd>
            </dl>
            @if ($student->portalActivationUrl())
                <p class="tich-caption tich-mt-4">Activation link (share with student if email was missed):</p>
                <p class="tich-text" style="word-break: break-all;">{{ $student->portalActivationUrl() }}</p>
            @endif
        </article>
    </div>

    <article class="tich-card tich-mt-6">
        <h2 class="tich-h3">Application documents</h2>
        @if ($biodata['documents']->isEmpty())
            <p class="tich-text tich-mt-4">No documents uploaded.</p>
        @else
            <table class="tich-admin-table tich-mt-4">
                <thead>
                    <tr>
                        <th>Document</th>
                        <th>File</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($biodata['documents'] as $document)
                        <tr>
                            <td>{{ $document->displayLabel() }}</td>
                            <td>{{ $document->safeFilename() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </article>

    <article class="tich-card tich-mt-6" id="academic-record">
        <h2 class="tich-h3">Academic record</h2>
        <p class="tich-text tich-mt-2">Registered units, CAT marks, exam results, grades, and attendance for this student.</p>

        @include('sis.partials.student-academic-record', [
            'academics' => $academics,
            'compact' => false,
        ])
    </article>
@endsection
