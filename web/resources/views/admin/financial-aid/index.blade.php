@extends('layouts.administration')

@section('title', 'Financial Aid Opportunities')

@section('administration-content')
    <x-page-toolbar title="Financial Aid Opportunities" meta="Manage scholarships, grants, and financial aid programs">
        <x-slot:actions>
            <a href="{{ route('administration.financial-aid.create') }}" class="tich-btn tich-btn-primary">Add Opportunity</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if ($opportunities->isEmpty())
        <div class="tich-card tich-mt-6">
            <div class="tich-card__body" style="text-align: center; padding: 3rem;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 1rem; color: #9ca3af;">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                </svg>
                <h3 class="tich-h3">No financial aid opportunities yet</h3>
                <p class="tich-text tich-text--muted tich-mt-2">Create your first scholarship, grant, or financial aid program.</p>
                <a href="{{ route('administration.financial-aid.create') }}" class="tich-btn tich-btn-primary tich-mt-4">Create Opportunity</a>
            </div>
        </div>
    @else
        <div class="tich-card tich-mt-6">
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th>Applications</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($opportunities as $opportunity)
                            <tr>
                                <td>
                                    <strong>{{ $opportunity->title }}</strong>
                                    <br><small class="tich-text--muted">{{ Str::limit($opportunity->description, 60) }}</small>
                                </td>
                                <td>
                                    @php
                                        $typeColors = [
                                            'scholarship' => 'bg-blue-100 text-blue-800',
                                            'grant' => 'bg-green-100 text-green-800',
                                            'loan' => 'bg-amber-100 text-amber-800',
                                            'work_study' => 'bg-purple-100 text-purple-800',
                                        ];
                                    @endphp
                                    <span class="tich-badge {{ $typeColors[$opportunity->funding_type] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst(str_replace('_', ' ', $opportunity->funding_type)) }}
                                    </span>
                                </td>
                                <td>{{ $opportunity->amount ? 'KES ' . number_format((float) $opportunity->amount, 2) : '-' }}</td>
                                <td>{{ $opportunity->application_deadline?->format('d M Y') ?? 'Open' }}</td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'draft' => 'bg-yellow-100 text-yellow-800',
                                            'published' => 'bg-green-100 text-green-800',
                                            'closed' => 'bg-gray-100 text-gray-800',
                                            'archived' => 'bg-red-100 text-red-800',
                                        ];
                                    @endphp
                                    <span class="tich-badge {{ $statusColors[$opportunity->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($opportunity->status) }}
                                    </span>
                                </td>
                                <td>{{ $opportunity->applications_count }}</td>
                                <td>{{ $opportunity->created_at->format('d M Y') }}</td>
                                <td>
                                    <div class="tich-flex tich-gap-2">
                                        <a href="{{ route('administration.financial-aid.show', $opportunity) }}" class="tich-btn tich-btn-ghost tich-btn--sm">View</a>
                                        <a href="{{ route('administration.financial-aid.edit', $opportunity) }}" class="tich-btn tich-btn-ghost tich-btn--sm">Edit</a>
                                        <form method="POST" action="{{ route('administration.financial-aid.destroy', $opportunity) }}" class="tich-inline-form" onsubmit="return confirm('Delete this opportunity?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="tich-btn tich-btn-ghost tich-btn--sm tich-text--danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="tich-flex tich-flex--between tich-mt-4">
                <p class="tich-caption tich-text--muted">Showing {{ $opportunities->firstItem() }} to {{ $opportunities->lastItem() }} of {{ $opportunities->total() }} opportunities</p>
                {{ $opportunities->links() }}
            </div>
        </div>
    @endif
@endsection