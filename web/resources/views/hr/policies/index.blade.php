@extends('layouts.hr')

@section('title', 'HR Policies')

@section('hr-content')
    <x-page-toolbar title="HR Policies" meta="Policy documents shared with staff">
        <x-slot:actions>
            <a href="{{ route('hr.policies.create') }}" class="tich-btn tich-btn-primary">+ Upload Policy</a>
        </x-slot:actions>
    </x-page-toolbar>

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
                                    <a href="{{ route('hr.policies.send', $policy) }}" class="tich-btn tich-btn-secondary">Send to staff</a>
                                    <a href="{{ route('hr.policies.acknowledgements', $policy) }}" class="tich-btn tich-btn-secondary">Acknowledgements</a>
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
