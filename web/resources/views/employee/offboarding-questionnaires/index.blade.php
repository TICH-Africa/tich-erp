@extends('layouts.employee')

@section('employee-content')
    <x-page-toolbar title="Offboarding questionnaire" meta="Complete when HR requests exit feedback" />

    @if (session('success'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('success') }}</div>
    @endif

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Questionnaire</th>
                        <th>Sent</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invitations as $row)
                        <tr>
                            <td>{{ $row->template?->title }}</td>
                            <td class="tich-caption">{{ $row->sent_at?->format('d M Y') }}</td>
                            <td>{{ $row->statusLabel() }}</td>
                            <td>
                                <a href="{{ route('employee.offboarding-questionnaires.show', $row) }}" class="tich-btn tich-btn-ghost tich-btn--sm">
                                    {{ $row->isPending() ? 'Complete' : 'View' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="tich-table-empty">No questionnaires at the moment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($invitations->hasPages())
            <div class="tich-mt-4">{{ $invitations->links() }}</div>
        @endif
    </div>
@endsection