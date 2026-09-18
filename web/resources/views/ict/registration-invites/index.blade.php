@extends('layouts.ict')

@section('title', 'ERP Registration Invites')

@section('ict-content')
    <x-page-toolbar title="ERP registration invites" meta="Send signup invitations to employees using their personal email" />

    @if (\App\Models\ErpRegistrationInvitation::appUrlLooksPrivate())
        <div class="tich-toast tich-toast--warning tich-mt-4" role="status">
            <div class="tich-toast__content">
                <p class="tich-toast__message">
                    Invite links currently use a private/local address (<code>{{ config('app.invite_base_url') ?: config('app.url') }}</code>).
                    Recipients outside this network cannot open them. Set a public <code>APP_URL</code> or <code>INVITE_BASE_URL</code> in <code>.env</code>, then run <code>php artisan config:clear</code>.
                    Until then, copy the registration link from the list below and share it directly.
                </p>
            </div>
        </div>
    @endif

    @if (session('invite_register_url'))
        <div class="tich-toast tich-toast--success tich-mt-4" role="status">
            <div class="tich-toast__content">
                <p class="tich-toast__message">
                    Registration link (copy and share if needed):
                    <a class="tich-link" href="{{ session('invite_register_url') }}" target="_blank" rel="noopener">{{ session('invite_register_url') }}</a>
                </p>
            </div>
        </div>
    @endif

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
                            <th>Registration link</th>
                            <th>Actions</th>
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
                                        <span class="tich-badge tich-badge--success">Registered</span>
                                    @elseif ($invite->expires_at->isPast())
                                        <span class="tich-badge tich-badge--neutral">Expired</span>
                                    @else
                                        <span class="tich-badge tich-badge--pending">Pending</span>
                                    @endif
                                </td>
                                <td>{{ $invite->created_at?->format('j M Y, H:i') }}</td>
                                <td>
                                    @if ($invite->used_at)
                                        <span class="tich-caption">-</span>
                                    @else
                                        <a class="tich-link" href="{{ $invite->registerUrl() }}" target="_blank" rel="noopener">Open link</a>
                                    @endif
                                </td>
                                <td>
                                    @if ($invite->used_at)
                                        <span class="tich-caption">-</span>
                                    @else
                                        <form method="POST" action="{{ route('ict.registration-invites.resend', $invite) }}" style="display:inline;" data-allow-resubmit>
                                            @csrf
                                            <button type="submit" class="tich-btn tich-btn-secondary tich-btn-sm">
                                                {{ $invite->expires_at->isPast() ? 'Re-invite' : 'Resend' }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
    @endif
@endsection
