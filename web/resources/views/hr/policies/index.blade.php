@extends('layouts.hr')

@section('title', 'HR Policies')

@section('hr-content')
    <div class="tich-mb-8">
        <div class="tich-flex tich-flex--between tich-flex--start">
            <div>
                <h1 class="tich-h1">HR Policies</h1>
                <p class="tich-text tich-mt-2">Manage and share HR policy documents with staff.</p>
            </div>
            <a href="{{ route('hr.policies.create') }}" class="tich-btn tich-btn-primary">+ Upload Policy</a>
        </div>
    </div>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Effective Date</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                        <th>Uploaded By</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($policies as $policy)
                        <tr>
                            <td>
                                <strong>{{ $policy->title }}</strong>
                                <p class="tich-caption">{{ $policy->original_filename }}</p>
                            </td>
                            <td class="tich-caption">{{ ucfirst($policy->category) }}</td>
                            <td class="tich-caption">{{ $policy->effective_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="tich-caption">{{ $policy->expiry_date?->format('Y-m-d') ?? '—' }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ $policy->is_active ? 'success' : 'warning' }}">
                                    {{ $policy->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="tich-caption">{{ $policy->uploadedBy?->fullName() ?? '—' }}</td>
                            <td>
                                <div class="tich-flex tich-flex--gap">
                                    <a href="{{ route('hr.policies.show', $policy) }}" class="tich-btn tich-btn-ghost">View</a>
                                    <a href="{{ route('hr.policies.download', $policy) }}" class="tich-btn tich-btn-secondary">Download</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="tich-table-empty">No policies found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($policies->hasPages())
            <div class="tich-mt-6">
                {{ $policies->links() }}
            </div>
        @endif
    </div>
@endsection
