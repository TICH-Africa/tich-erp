@extends('layouts.hr')

@section('title', 'Offboarding questionnaires sent')

@section('hr-content')
    <x-page-toolbar title="Questionnaires sent" meta="Exit feedback from staff who are not renewing">
        <x-slot:actions>
            <a href="{{ route('hr.offboarding.questionnaires.templates.index') }}" class="tich-btn tich-btn-ghost">Templates</a>
            <a href="{{ route('hr.offboarding.questionnaires.invitations.create') }}" class="tich-btn tich-btn-primary">Send questionnaire</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Staff</th>
                        <th>Template</th>
                        <th>Sent</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invitations as $row)
                        <tr>
                            <td>
                                <strong>{{ $row->staff?->fullName() }}</strong>
                                <p class="tich-caption">{{ $row->staff?->employee_number }}</p>
                            </td>
                            <td>{{ $row->template?->title }}</td>
                            <td class="tich-caption">{{ $row->sent_at?->format('d M Y') }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ $row->statusBadgeTone() }}" title="{{ $row->statusLabel() }}">
                                    {{ $row->statusLabel() }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('hr.offboarding.questionnaires.invitations.show', $row) }}" class="tich-btn tich-btn-ghost tich-btn--sm">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="tich-table-empty">No questionnaires sent yet.</td>
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