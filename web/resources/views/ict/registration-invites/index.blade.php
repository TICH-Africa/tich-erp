@extends('layouts.ict')

@section('title', 'ERP Registration Invites')

@section('ict-content')
    <x-page-toolbar title="ERP registration invites" meta="Send signup invitations to employees using their personal email" />

    @include('partials.staff-registration-invite-form', [
        'action' => route('ict.registration-invites.store'),
    ])

    @if ($recentInvitations->isNotEmpty())
        <article class="tich-card tich-mt-8">
            <h2 class="tich-h3">Recent invitations</h2>
            <div class="tich-table-wrap tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Email</th>
                            <th>Sent by</th>
                            <th>Status</th>
                            <th>Sent</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentInvitations as $invite)
                            <tr>
                                <td>
                                    @if ($invite->staff)
                                        <strong>{{ $invite->staff->fullName() }}</strong>
                                        <p class="tich-caption">{{ $invite->staff->employee_number }}</p>
                                    @else
                                        <span class="tich-caption">Not in staff directory</span>
                                    @endif
                                </td>
                                <td>{{ $invite->email }}</td>
                                <td>{{ $invite->inviter?->email ?? '-' }}</td>
                                <td>
                                    @if ($invite->used_at)
                                        Registered
                                    @elseif ($invite->expires_at->isPast())
                                        Expired
                                    @else
                                        Pending
                                    @endif
                                </td>
                                <td>{{ $invite->created_at?->format('j M Y, H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
    @endif
@endsection
