@extends('layouts.finance')

@section('title', $policy->title)

@section('finance-content')
    <x-page-toolbar :title="$policy->title" :meta="$policy->fiscal_year.' · '.ucfirst($policy->status)">
        <x-slot:actions>
            <a href="{{ route('finance.financial-policies.download', $policy) }}" class="tich-btn tich-btn-secondary">Download</a>
            <a href="{{ route('finance.financial-policies.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif

    <article class="tich-card tich-mt-6">
        <p class="tich-text">{{ $policy->description }}</p>
        <p class="tich-caption tich-mt-2">
            Uploaded {{ $policy->uploaded_at?->format('d M Y H:i') }}
            @if($policy->uploader) by {{ $policy->uploader->fullName() ?? $policy->uploader->first_name }} @endif
            @if($policy->published_at) · Published {{ $policy->published_at->format('d M Y') }} @endif
        </p>
        @if ($policy->status === 'draft')
            <form method="POST" action="{{ route('finance.financial-policies.publish', $policy) }}" class="tich-mt-4">
                @csrf
                <button type="submit" class="tich-btn tich-btn-primary">Publish (require HOD sign-off before budgets)</button>
            </form>
        @endif
    </article>

    <article class="tich-card tich-table-panel tich-mt-6">
        <h2 class="tich-h3">HOD sign-off progress</h2>
        <p class="tich-caption tich-mt-1">{{ $signoff['signed'] }} / {{ $signoff['total'] }} departments</p>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead><tr><th>Department</th><th>Code</th><th>Signed by</th><th>Signed at</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach ($signoff['departments'] as $dept)
                        <tr>
                            <td>{{ $dept['name'] }}</td>
                            <td>{{ $dept['code'] }}</td>
                            <td>{{ $dept['signed'] ? ($dept['signed_name'] ?? '-') : '-' }}</td>
                            <td>{{ $dept['signed'] ? ($dept['signed_at'] ?? '-') : '-' }}</td>
                            <td><x-status-badge :status="$dept['signed'] ? 'signed' : 'pending'" :label="$dept['signed'] ? 'Signed' : 'Pending'" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </article>
@endsection
