@extends('layouts.staff')

@section('staff-content')
    <div class="tich-mb-8">
        <h1 class="tich-h1">HR Policies</h1>
        <p class="tich-text tich-mt-2">View and acknowledge company policies and documents shared by HR.</p>
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
                            <td>
                                <a href="{{ route('staff.policies.view', $policy) }}" class="tich-btn tich-btn-primary tich-btn--sm" target="_blank">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="tich-table-empty">No policies available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
