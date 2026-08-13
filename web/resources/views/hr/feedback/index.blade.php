@extends('layouts.hr')

@section('title', 'Employee Relations - Feedback')

@section('hr-content')
    <x-page-toolbar title="Feedback" meta="Employee Relations">
        <x-slot:actions>
            <a href="{{ route('hr.employee-relations.feedback.create') }}" class="tich-btn tich-btn-primary">+ New feedback</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($feedbacks as $feedback)
                        <tr>
                            <td>
                                <strong>{{ $feedback->staff->fullName() }}</strong>
                                <p class="tich-caption">{{ $feedback->staff->employee_number }}</p>
                            </td>
                            <td class="tich-caption">{{ $feedback->feedback_type ?? '-' }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ match($feedback->status) {
                                    'open' => 'warning',
                                    'under_review' => 'info',
                                    'resolved' => 'success',
                                    'closed' => 'secondary',
                                    default => 'secondary',
                                } }}">
                                    {{ ucfirst(str_replace('_', ' ', $feedback->status)) }}
                                </span>
                            </td>
                            <td class="tich-caption">{{ $feedback->created_at?->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('hr.employee-relations.feedback.show', $feedback) }}" class="tich-btn tich-btn-ghost">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="tich-table-empty">No feedback found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($feedbacks->hasPages())
            <div class="tich-mt-4">{{ $feedbacks->links() }}</div>
        @endif
    </div>
@endsection
