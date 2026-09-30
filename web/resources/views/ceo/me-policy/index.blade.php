@extends('layouts.ceo')

@section('title', 'M&E policy')

@section('ceo-content')
    <x-page-toolbar title="M&E policy" meta="Review and digitally sign the published M&E policy" />

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">@foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
    @endif

    @unless ($policy)
        <article class="tich-card tich-mt-6">
            <p class="tich-text">No published M&amp;E policy is available yet.</p>
        </article>
    @else
        <article class="tich-card tich-mt-6">
            <h2 class="tich-h3" style="margin-top:0;">{{ $policy->title }}</h2>
            <p class="tich-caption">{{ $policy->fiscal_year }}@if($policy->version) · v{{ $policy->version }}@endif · Published {{ $policy->published_at?->format('d M Y') }}</p>
            @if ($policy->description)
                <p class="tich-text tich-mt-2">{{ $policy->description }}</p>
            @endif
            <div class="tich-flex tich-mt-4" style="gap:0.5rem; flex-wrap:wrap;">
                <a href="{{ route('ceo.me-policy.view') }}" class="tich-btn tich-btn-secondary" target="_blank">View document</a>
                <a href="{{ route('ceo.me-policy.download') }}" class="tich-btn tich-btn-ghost">Download</a>
            </div>
        </article>

        @if ($signoff)
            <article class="tich-card tich-table-panel tich-mt-6">
                <h2 class="tich-h3">Department sign-off progress</h2>
                <p class="tich-caption tich-mt-1">{{ $signoff['signed'] }} / {{ $signoff['total'] }} departments</p>
                <div class="tich-table-wrap tich-mt-4">
                    <table class="tich-admin-table">
                        <thead><tr><th>Department</th><th>Signed by</th><th>Role</th><th>Signed at</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach ($signoff['departments'] as $dept)
                                <tr>
                                    <td>{{ $dept['name'] }}</td>
                                    <td>{{ $dept['signed'] ? ($dept['signed_name'] ?? '—') : '—' }}</td>
                                    <td>{{ $dept['signed_role'] ?? '—' }}</td>
                                    <td>{{ $dept['signed'] ? ($dept['signed_at'] ?? '—') : '—' }}</td>
                                    <td><x-status-badge :status="$dept['signed'] ? 'signed' : 'pending'" :label="$dept['signed'] ? 'Signed' : 'Pending'" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </article>
        @endif

        @if ($ceoSigned)
            <article class="tich-card tich-mt-6">
                <p class="tich-text">You have already digitally signed this M&amp;E policy as CEO.</p>
            </article>
        @elseif ($executiveReadOnly ?? false)
            <article class="tich-card tich-mt-6">
                <p class="tich-text">Awaiting CEO signature — read-only observers cannot sign policies.</p>
            </article>
        @else
            <form method="POST" action="{{ route('ceo.me-policy.sign') }}" class="tich-card tich-form-stack tich-mt-6">
                @csrf
                <h2 class="tich-h3">CEO digital signature</h2>
                <div class="tich-form-group">
                    <label class="tich-label" for="signed_name">Full name *</label>
                    <input type="text" id="signed_name" name="signed_name" class="tich-input" required maxlength="200"
                           value="{{ old('signed_name', $staff?->fullName()) }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="employee_number">Employee number</label>
                    <input type="text" id="employee_number" name="employee_number" class="tich-input" maxlength="100"
                           value="{{ old('employee_number', $staff?->employee_number) }}">
                </div>
                <button type="submit" class="tich-btn tich-btn-primary">Sign M&amp;E policy</button>
            </form>
        @endif
    @endunless
@endsection
