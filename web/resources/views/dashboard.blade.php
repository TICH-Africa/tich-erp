@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <section class="tich-section">
        <div class="tich-container">
            <div class="tich-mb-8">
                <h1 class="tich-h1">Welcome, {{ auth()->user()->username }}</h1>
                <p class="tich-text tich-mt-2">
                    Signed in as <span class="tich-caption">{{ ucfirst(auth()->user()->user_type) }}</span>
                    @if (auth()->user()->roles->isNotEmpty())
                        · Roles:
                        @foreach (auth()->user()->roles as $role)
                            <span class="tich-caption">{{ $role->role_name }}</span>@if (! $loop->last), @endif
                        @endforeach
                    @endif
                </p>
            </div>

            <div class="tich-grid tich-grid--3">
                @can('admin.access')
                    <article class="tich-card tich-card--highlight">
                        <p class="tich-caption">Core</p>
                        <h3 class="tich-h3 tich-mt-2">Platform administration</h3>
                        <p class="tich-text tich-mt-2">Campuses, departments, users, roles, and module access.</p>
                        <a href="{{ route('admin.index') }}" class="tich-btn tich-btn-primary tich-mt-4">Open admin panel</a>
                    </article>
                @endcan

                @forelse ($departments as $department)
                    <article class="tich-card">
                        <p class="tich-caption">{{ $categoryLabel($department) }}</p>
                        <h3 class="tich-h3 tich-mt-2">{{ $department->dept_name }}</h3>
                        <p class="tich-text tich-mt-2">{{ $cardDescription($department) }}</p>
                        @if ($department->group)
                            <p class="tich-caption tich-mt-2">{{ $department->group->group_name }}</p>
                        @endif
                        <a href="{{ $entryUrl($department) }}" class="tich-btn tich-btn-secondary tich-mt-4">Open department</a>
                    </article>
                @empty
                    @unless (auth()->user()->can('admin.access'))
                        <article class="tich-card">
                            <h3 class="tich-h3">No departments assigned</h3>
                            <p class="tich-text">You are not assigned to any department yet. Contact a platform administrator if you need access.</p>
                        </article>
                    @endunless
                @endforelse

                @if (auth()->user()->isTeachingStaff())
                    <article class="tich-card tich-card--highlight">
                        <p class="tich-caption">Teaching</p>
                        <h3 class="tich-h3 tich-mt-2">Staff portal</h3>
                        <p class="tich-text tich-mt-2">Manage units, attendance, assessments, lesson plans, and learning content.</p>
                        <a href="{{ route('staff.dashboard') }}" class="tich-btn tich-btn-primary tich-mt-4">Open staff portal</a>
                    </article>
                @endif

                @if (auth()->user()->student_id || auth()->user()->student)
                    <article class="tich-card tich-card--highlight">
                        <p class="tich-caption">Student</p>
                        <h3 class="tich-h3 tich-mt-2">Student portal</h3>
                        <p class="tich-text tich-mt-2">View your enrolment profile, application history, and student services.</p>
                        <a href="{{ route('portal.dashboard') }}" class="tich-btn tich-btn-primary tich-mt-4">Open student portal</a>
                    </article>
                @endif

                @can('students.read')
                    <article class="tich-card">
                        <p class="tich-caption">Administration</p>
                        <h3 class="tich-h3 tich-mt-2">Student Information System</h3>
                        <p class="tich-text tich-mt-2">360° student biodata records compiled from admissions and enrolment.</p>
                        <a href="{{ route('sis.dashboard') }}" class="tich-btn tich-btn-secondary tich-mt-4">Open SIS hub</a>
                    </article>
                @endcan

                @can('audit_logs.read')
                    <article class="tich-card">
                        <p class="tich-caption">Security</p>
                        <h3 class="tich-h3 tich-mt-2">Audit logs</h3>
                        <p class="tich-text tich-mt-2">Security and compliance activity trail.</p>
                        <a href="{{ route('admin.audit-logs.index') }}" class="tich-btn tich-btn-secondary tich-mt-4">View audit logs</a>
                    </article>
                @endcan

                <article class="tich-card">
                    <h3 class="tich-h3">Security</h3>
                    <p class="tich-text">
                        MFA:
                        @if (auth()->user()->mfa_enabled)
                            <span class="tich-caption">Enabled ({{ str_replace('_', ' ', auth()->user()->mfa_method) }})</span>
                        @else
                            <span class="tich-caption">Not configured</span>
                        @endif
                    </p>
                    @unless (auth()->user()->mfa_enabled)
                        <a href="{{ route('mfa.setup') }}" class="tich-btn tich-btn-secondary tich-mt-4">Set up MFA</a>
                    @endunless
                </article>

                <article class="tich-card">
                    <h3 class="tich-h3">Account</h3>
                    <p class="tich-text">Sign out securely from this device.</p>
                    <form method="POST" action="{{ route('logout') }}" class="tich-mt-4">
                        @csrf
                        <button type="submit" class="tich-btn tich-btn-secondary">Sign out</button>
                    </form>
                </article>
            </div>
        </div>
    </section>
@endsection
