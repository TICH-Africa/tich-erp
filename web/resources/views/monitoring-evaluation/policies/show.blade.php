@extends('layouts.monitoring-evaluation')

@section('title', $policy->title)

@section('monitoring-evaluation-content')
    <x-page-toolbar :title="$policy->title" :meta="$policy->fiscal_year.' · '.ucfirst($policy->status)">
        <x-slot:actions>
            <a href="{{ route('monitoring_evaluation.policies.download', $policy) }}" class="tich-btn tich-btn-secondary">Download</a>
            <a href="{{ route('monitoring_evaluation.policies.index') }}" class="tich-btn tich-btn-ghost">Back</a>
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
            <form method="POST" action="{{ route('monitoring_evaluation.policies.publish', $policy) }}" class="tich-mt-4">
                @csrf
                <button type="submit" class="tich-btn tich-btn-primary">Publish (require HOD sign-off)</button>
            </form>
        @endif
    </article>

    <article class="tich-card tich-mt-6">
        <h2 class="tich-h3">HOD sign-off progress</h2>
        <p class="tich-caption tich-mt-1">{{ $signoff['signed'] }} / {{ $signoff['total'] }} departments</p>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead><tr><th>Department</th><th>Code</th><th>Signed</th></tr></thead>
                <tbody>
                    @foreach ($signoff['departments'] as $dept)
                        <tr>
                            <td>{{ $dept['name'] }}</td>
                            <td>{{ $dept['code'] }}</td>
                            <td>{{ $dept['signed'] ? 'Yes' : 'Pending' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </article>
@endsection
