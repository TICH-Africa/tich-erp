@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <section class="tich-section tich-dashboard">
        <div class="tich-container">
            <div class="tich-mb-8 tich-dashboard__intro">
                <h1 class="tich-h1 tich-dashboard__title">Welcome, {{ auth()->user()->displayName() }}</h1>
                <p class="tich-text tich-mt-2">
                    Signed in as <span class="tich-caption">{{ ucfirst(auth()->user()->user_type) }}</span>
                    @if (auth()->user()->roles->isNotEmpty())
                        · Roles:
                        @foreach (auth()->user()->roles as $role)
                            <span class="tich-caption">{{ $role->role_name }}</span>@if (! $loop->last), @endif
                        @endforeach
                    @endif
                </p>
                @if ($awaitingDepartmentAssignment ?? false)
                    <div class="tich-alert tich-alert--warning tich-mt-4" role="status">
                        <strong>Department assignment pending.</strong>
                        Browse institutional departments below. HR or ICT will assign you to a unit before module tools unlock.
                    </div>
                @endif
            </div>

            <div class="tich-grid tich-grid--3 tich-dashboard__grid">
                @can('admin.access')
                    <article class="tich-card tich-card--highlight">
                        <p class="tich-caption">Core</p>
                        <h3 class="tich-h3 tich-mt-2">Platform administration</h3>
                        <p class="tich-text tich-mt-2">Campuses, departments, users, roles, and module access.</p>
                        <a href="{{ route('admin.index') }}" class="tich-btn tich-btn-primary tich-mt-4">Open admin panel</a>
                    </article>
                @endcan

                @can('site_settings.read')
                    <article class="tich-card tich-card--highlight">
                        <p class="tich-caption">Core</p>
                        <h3 class="tich-h3 tich-mt-2">Site settings</h3>
                        <p class="tich-text tich-mt-2">Manage the public site logo, hero slides, contact details, and branding.</p>
                        <a href="{{ route('site-settings.index') }}" class="tich-btn tich-btn-primary tich-mt-4">Open site settings</a>
                    </article>
                @endcan

                @forelse ($departments as $department)
                    @php
                        $notificationCount = (int) ($departmentNotificationCounts[$department->id] ?? 0);
                        $notificationLabel = $formatNotificationCount($notificationCount);
                        $awaiting = $awaitingDepartmentAssignment ?? false;
                    @endphp
                    <article class="tich-card">
                        <div class="tich-flex" style="justify-content: space-between; align-items: flex-start; gap: 0.75rem;">
                            <p class="tich-caption">{{ $categoryLabel($department) }}</p>
                            @if ($notificationLabel)
                                <span class="tich-notification-badge" aria-label="{{ $notificationCount }} pending notifications">{{ $notificationLabel }}</span>
                            @endif
                        </div>
                        <h3 class="tich-h3 tich-mt-2">{{ $department->dept_name }}</h3>
                        <p class="tich-text tich-mt-2">{{ $cardDescription($department) }}</p>
                        @if ($department->group)
                            <p class="tich-caption tich-mt-2">{{ $department->group->group_name }}</p>
                        @endif
                        @if ($awaiting)
                            <p class="tich-caption tich-mt-4" style="color: #b45309;">Open after HR assigns you to this department.</p>
                            <span class="tich-btn tich-btn-secondary tich-mt-4" aria-disabled="true">{{ $cardActionLabel }}</span>
                        @else
                            <a href="{{ $entryUrl($department) }}" class="tich-btn tich-btn-secondary tich-mt-4">{{ $cardActionLabel }}</a>
                        @endif
                    </article>
                @empty
                    @unless (auth()->user()->can('admin.access'))
                        <article class="tich-card">
                            <h3 class="tich-h3">No departments assigned</h3>
                            <p class="tich-text">You are not assigned to any department yet. Contact a platform administrator if you need access.</p>
                        </article>
                    @endunless
                @endforelse

                @if (auth()->user()->hasEmployeeProfile() && ! auth()->user()->isEnrolledStudent())
                    <article class="tich-card tich-card--highlight">
                        <p class="tich-caption">My Portal</p>
                        <h3 class="tich-h3 tich-mt-2">My Employee Portal</h3>
                        <p class="tich-text tich-mt-2">View your profile, leave, attendance, documents, and workplace concerns.</p>
                        <a href="{{ route('employee.dashboard') }}" class="tich-btn tich-btn-primary tich-mt-4">Open employee portal</a>
                    </article>
                @endif

                @if (auth()->user()->isTeachingStaff())
                    <article class="tich-card tich-card--highlight">
                        <p class="tich-caption">Teaching & Training</p>
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

                @can('audit_logs.read')
                    <article class="tich-card">
                        <p class="tich-caption">Security</p>
                        <h3 class="tich-h3 tich-mt-2">Audit logs</h3>
                        <p class="tich-text tich-mt-2">Security and compliance activity trail.</p>
                        <a href="{{ route('admin.audit-logs.index') }}" class="tich-btn tich-btn-secondary tich-mt-4">View audit logs</a>
                    </article>
                @endcan
            </div>
        </div>
    </section>
@endsection
