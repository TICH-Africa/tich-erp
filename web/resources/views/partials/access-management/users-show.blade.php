@php
    use App\Support\UserType;

    $staff = $user->staff;
    $isSuperAdmin = $user->user_type === UserType::SUPER_ADMIN;
@endphp

<div class="tich-page-toolbar">
    <div>
        <h1 class="tich-h3">{{ $user->displayName() }}</h1>
        <p class="tich-caption tich-mt-2">{{ $user->email }}</p>
    </div>
    <div>
        <a href="{{ $access->route('users.index', ['audience' => $isSuperAdmin ? 'super_admins' : 'staff']) }}" class="tich-btn tich-btn-ghost">Back to users</a>
    </div>
</div>

<div class="tich-grid tich-grid--2 tich-mt-6">
    <article class="tich-card">
        <h2 class="tich-h3">Account</h2>
        <div class="tich-mt-4">
            <p><strong>Account type:</strong> {{ UserType::label($user->user_type) }}</p>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            @if ($isSuperAdmin)
                <p class="tich-caption tich-mt-2">This account has full platform access. It is not listed in HR staff directories and does not require an employee profile.</p>
            @endif
            <p><strong>Roles &amp; departments:</strong></p>
            <ul class="tich-mt-2" style="list-style:none;padding:0;">
                @forelse ($user->roles as $role)
                    <li class="tich-text tich-mt-1">
                        {{ $role->role_name }}
                        @if ($role->pivot->department_id)
                            <span class="tich-caption">· {{ $departmentNames[$role->pivot->department_id] ?? 'Dept #'.$role->pivot->department_id }}</span>
                        @else
                            <span class="tich-caption">· Institution-wide</span>
                        @endif
                    </li>
                @empty
                    <li class="tich-caption">{{ $isSuperAdmin ? 'Full access via super admin account type' : 'Not assigned' }}</li>
                @endforelse
            </ul>
        </div>
    </article>

    @unless ($isSuperAdmin)
        <article class="tich-card">
            <h2 class="tich-h3">Staff record</h2>
            @if ($staff)
                <div class="tich-mt-4">
                    <p><strong>Employee no.:</strong> {{ $staff->employee_number }}</p>
                    <p><strong>Name:</strong> {{ $staff->fullName() }}</p>
                    <p><strong>Department:</strong> {{ $staff->department->dept_name ?? '-' }}</p>
                    <p><strong>Campus:</strong> {{ $staff->campus->campus_name ?? '-' }}</p>
                    <p><strong>Job title:</strong> {{ $staff->job_title }}</p>
                    <p><strong>Status:</strong> {{ ucfirst($staff->employment_status) }}</p>
                    <p><strong>Personal email:</strong> {{ $staff->primary_email ?? '-' }}</p>
                    <p><strong>Organisation email:</strong> {{ $staff->organisation_email ?? '-' }}</p>
                    <p><strong>Phone:</strong> {{ $staff->phone_number ?? '-' }}</p>
                </div>
                @if ($access->prefix === 'ict')
                    <form method="POST" action="{{ route('ict.staff.organisation-email.update', $staff) }}" class="tich-mt-4" style="padding-top:1rem;border-top:1px solid var(--tich-neutral-border);">
                        @csrf
                        @method('PUT')
                        <label for="organisation_email" class="tich-label">Assign organisation email</label>
                        <input
                            type="email"
                            id="organisation_email"
                            name="organisation_email"
                            value="{{ old('organisation_email', $staff->organisation_email) }}"
                            class="tich-input"
                            pattern=".+@tich\.africa$"
                            placeholder="name@tich.africa"
                        >
                        <p class="tich-caption tich-mt-1">Leave blank and save to clear. Must use @tich.africa.</p>
                        <button type="submit" class="tich-btn tich-btn-primary tich-mt-2">Save organisation email</button>
                    </form>
                @endif
            @else
                <p class="tich-text tich-mt-4">No staff record is linked to this account.</p>
            @endif
        </article>
    @endunless
</div>

@if ($access->prefix === 'ict' && $staff && ! $isSuperAdmin)
    @include('partials.staff-profile-update-prompt-form', [
        'action' => route('ict.staff.profile-update-prompt.store', $staff),
        'promptStaff' => $staff,
    ])
@elseif ($access->prefix === 'ict' && ! $staff && ! $isSuperAdmin)
    <article class="tich-card tich-mt-8">
        <h2 class="tich-h3">Request profile update</h2>
        <p class="tich-caption tich-mt-2">This account has no linked staff record. Link the user to a staff profile before requesting profile updates.</p>
    </article>
@endif
