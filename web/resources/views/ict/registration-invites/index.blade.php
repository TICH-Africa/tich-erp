@extends('layouts.ict')

@section('title', 'ERP Registration Invites')

@section('ict-content')
    <x-page-toolbar title="ERP registration invites" meta="Send signup invitations to employees using their personal email" />

    @if (session('invite_register_url'))
        <div class="tich-toast tich-toast--success tich-mt-4" role="status">
            <div class="tich-toast__content">
                <p class="tich-toast__message">
                    Registration link:
                    <button
                        type="button"
                        class="tich-link"
                        data-copy-text="{{ session('invite_register_url') }}"
                        data-copy-label="Copy link"
                    >Copy link</button>
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
                                        <button
                                            type="button"
                                            class="tich-btn tich-btn-secondary tich-btn-sm"
                                            data-copy-text="{{ $invite->registerUrl() }}"
                                            data-copy-label="Copy link"
                                        >Copy link</button>
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

    <script>
    (function () {
        function copyText(value) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                return navigator.clipboard.writeText(value);
            }
            return new Promise(function (resolve, reject) {
                var area = document.createElement('textarea');
                area.value = value;
                area.setAttribute('readonly', '');
                area.style.position = 'absolute';
                area.style.left = '-9999px';
                document.body.appendChild(area);
                area.select();
                try {
                    document.execCommand('copy');
                    resolve();
                } catch (err) {
                    reject(err);
                } finally {
                    document.body.removeChild(area);
                }
            });
        }

        document.addEventListener('click', function (event) {
            var btn = event.target.closest('[data-copy-text]');
            if (!btn) return;
            var text = btn.getAttribute('data-copy-text') || '';
            var label = btn.getAttribute('data-copy-label') || 'Copy link';
            copyText(text).then(function () {
                btn.textContent = 'Copied';
                window.setTimeout(function () {
                    btn.textContent = label;
                }, 1600);
            }).catch(function () {
                btn.textContent = 'Copy failed';
                window.setTimeout(function () {
                    btn.textContent = label;
                }, 1600);
            });
        });
    })();
    </script>
@endsection
