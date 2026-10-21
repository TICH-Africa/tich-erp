@extends('layouts.ict')

@section('title', 'Student Portal Invites')

@section('ict-content')
    <x-page-toolbar title="Student portal invites" meta="Send or re-send the portal activation link for enrolled students" />

    <div class="tich-table-panel tich-mt-8">
        <h2 class="tich-h3">Invite a student by email</h2>
        <p class="tich-caption tich-mt-2">Enter the email the student used on their application. They receive an activation link to set their own password, the same way employees receive ERP registration invites. Sending again issues a fresh link and the previous one stops working.</p>

        <form method="POST" action="{{ route('ict.student-invites.store') }}" class="tich-mt-6" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:flex-end;" data-uf="skip">
            @csrf
            <div class="tich-form-group" style="flex:1 1 280px; margin:0;">
                <label for="student_invite_email" class="tich-label">Student email</label>
                <input
                    type="email"
                    id="student_invite_email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    class="tich-input @error('email') tich-input--error @enderror"
                    placeholder="student@gmail.com"
                >
                @error('email')
                    <p class="tich-field-error">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="tich-btn tich-btn-primary">Send activation link</button>
        </form>
    </div>

    <div class="tich-table-panel tich-mt-8">
        <div style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:flex-end; justify-content:space-between;">
            <div>
                <h2 class="tich-h3">{{ $search === '' ? 'Students waiting to activate' : 'Search results' }}</h2>
                <p class="tich-caption tich-mt-2">
                    @if ($search === '')
                        Every listed student still needs to set a portal password.
                    @else
                        Showing students matching “{{ $search }}”.
                    @endif
                </p>
            </div>

            <form method="GET" action="{{ route('ict.student-invites.index') }}" style="display:flex; gap:0.5rem; align-items:flex-end;" data-uf="skip">
                <div class="tich-form-group" style="margin:0;">
                    <label for="student_invite_search" class="tich-label">Search</label>
                    <input type="search" id="student_invite_search" name="q" value="{{ $search }}" class="tich-input" placeholder="Name, reg. number or email">
                </div>
                <button type="submit" class="tich-btn tich-btn-primary">Search</button>
                @if ($search !== '')
                    <a class="tich-btn tich-btn-secondary" href="{{ route('ict.student-invites.index') }}">Clear</a>
                @endif
            </form>
        </div>

        @if ($students->isEmpty())
            <p class="tich-caption tich-mt-6">No students to list.</p>
        @else
            <div class="tich-table-wrap tich-mt-4" style="max-height:30rem; overflow:auto;">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Reg. number</th>
                            <th>Programme</th>
                            <th>Campus</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Link expires</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $student)
                            <tr>
                                <td><strong>{{ $student->displayName() }}</strong></td>
                                <td>{{ $student->registration_number }}</td>
                                <td>{{ $student->program?->program_name ?? '-' }}</td>
                                <td>{{ $student->campus?->campus_name ?? 'Campus not set' }}</td>
                                <td>{{ $student->email ?: ($student->applicant?->email ?: ($student->user?->email ?? '-')) }}</td>
                                <td>
                                    @if ($student->portal_activated_at || $student->user_id)
                                        <span class="tich-badge tich-badge--success">Active</span>
                                    @elseif ($student->portal_invite_token === null)
                                        <span class="tich-badge tich-badge--neutral">No invite yet</span>
                                    @elseif ($student->portal_invite_expires_at?->isPast())
                                        <span class="tich-badge tich-badge--neutral">Expired</span>
                                    @else
                                        <span class="tich-badge tich-badge--pending">Awaiting activation</span>
                                    @endif
                                </td>
                                <td>{{ $student->portal_invite_expires_at?->format('j M Y, H:i') ?? '-' }}</td>
                                <td>
                                    @if ($student->portal_activated_at || $student->user_id)
                                        <span class="tich-caption">-</span>
                                    @else
                                        <form method="POST" action="{{ route('ict.student-invites.resend', $student) }}" style="display:inline;" data-allow-resubmit data-uf="skip">
                                            @csrf
                                            <button type="submit" class="tich-btn tich-btn-secondary tich-btn-sm">
                                                {{ $student->hasActivePortalInvite() ? 'Resend' : 'Invite' }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
