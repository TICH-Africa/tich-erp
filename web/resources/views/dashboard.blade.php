@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <section class="tich-section tich-dashboard">
        <div class="tich-container tich-dashboard__wrap">
            <header class="tich-dashboard__welcome">
                <h1 class="tich-dashboard__title">Welcome, {{ auth()->user()->displayName() }}</h1>
                <p class="tich-dashboard__meta">
                    Signed in as <strong>{{ ucfirst(auth()->user()->user_type) }}</strong>
                    @if (auth()->user()->roles->isNotEmpty())
                        · Roles:
                        <strong>
                            @foreach (auth()->user()->roles as $role)
                                {{ $role->role_name }}@if (! $loop->last), @endif
                            @endforeach
                        </strong>
                    @endif
                </p>
                @if ($awaitingDepartmentAssignment ?? false)
                    <div class="tich-alert tich-alert--warning tich-mt-4" role="status">
                        <strong>Department assignment pending.</strong>
                        Department modules are locked. Use <strong>My Employee Portal</strong> for your personal tools until HR or ICT assigns you to a unit.
                    </div>
                @endif
            </header>

            <div class="tich-dashboard__grid">
                @unless ($awaitingDepartmentAssignment ?? false)
                    @if (auth()->user()->hasAnyRole(['CEO', 'Super Admin']))
                        <article class="tich-portal-card tich-portal-card--exec">
                            <p class="tich-portal-card__eyebrow">Executive</p>
                            <h2 class="tich-portal-card__title">Chief Executive Officer</h2>
                            <p class="tich-portal-card__desc">Budget authorizations, curriculum sign-off, and institution-wide executive oversight.</p>
                            <div class="tich-portal-card__actions">
                                <a href="{{ route('ceo.dashboard') }}" class="tich-portal-card__btn tich-portal-card__btn--primary">Open CEO office</a>
                            </div>
                        </article>
                    @endif

                    @if (auth()->user()->hasAnyRole(['Chief Institution Administrator', 'Super Admin']))
                        <article class="tich-portal-card tich-portal-card--exec">
                            <p class="tich-portal-card__eyebrow">Executive</p>
                            <h2 class="tich-portal-card__title">Chief Institution Administrator</h2>
                            <p class="tich-portal-card__desc">Read-only institution oversight - view executive queues, workforce, students, and finance without acting.</p>
                            <div class="tich-portal-card__actions">
                                <a href="{{ route('institution-admin.dashboard') }}" class="tich-portal-card__btn tich-portal-card__btn--primary">Open oversight office</a>
                            </div>
                        </article>
                    @endif

                    @if (app(\App\Services\RBACService::class)->canAccessPlatformAdministration(auth()->user()))
                        <article class="tich-portal-card tich-portal-card--core">
                            <p class="tich-portal-card__eyebrow">Core</p>
                            <h2 class="tich-portal-card__title">Platform administration</h2>
                            <p class="tich-portal-card__desc">Campuses, departments, users, roles, and module access.</p>
                            <div class="tich-portal-card__actions">
                                <a href="{{ route('admin.index') }}" class="tich-portal-card__btn tich-portal-card__btn--primary">Open admin panel</a>
                            </div>
                        </article>
                    @endif

                    @if (app(\App\Services\RBACService::class)->canAccessSiteSettings(auth()->user()))
                        <article class="tich-portal-card tich-portal-card--core">
                            <p class="tich-portal-card__eyebrow">Core</p>
                            <h2 class="tich-portal-card__title">Site settings</h2>
                            <p class="tich-portal-card__desc">Manage the public site logo, hero slides, contact details, and branding.</p>
                            <div class="tich-portal-card__actions">
                                <a href="{{ route('site-settings.index') }}" class="tich-portal-card__btn tich-portal-card__btn--primary">Open site settings</a>
                            </div>
                        </article>
                    @endif

                    @forelse ($departments as $department)
                        @php
                            $notificationCount = (int) ($departmentNotificationCounts[$department->id] ?? 0);
                            $notificationLabel = $formatNotificationCount($notificationCount);
                        @endphp
                        <article class="tich-portal-card tich-portal-card--unit">
                            @if ($notificationLabel)
                                <span class="tich-portal-card__badge" aria-label="{{ $notificationCount }} pending items">{{ $notificationLabel }}</span>
                            @endif
                            <p class="tich-portal-card__eyebrow">{{ $categoryLabel($department) }}</p>
                            <h2 class="tich-portal-card__title">{{ $department->dept_name }}</h2>
                            <p class="tich-portal-card__desc">{{ $cardDescription($department) }}</p>
                            @if ($department->group)
                                <p class="tich-portal-card__tag">{{ $department->group->group_name }}</p>
                            @endif
                            <div class="tich-portal-card__actions">
                                <a href="{{ $entryUrl($department) }}" class="tich-portal-card__btn tich-portal-card__btn--outline">{{ $cardActionLabel }}</a>
                            </div>
                        </article>
                    @empty
                        @unless (app(\App\Services\RBACService::class)->canAccessPlatformAdministration(auth()->user()))
                            <article class="tich-portal-card tich-portal-card--core">
                                <h2 class="tich-portal-card__title">No departments assigned</h2>
                                <p class="tich-portal-card__desc">You are not assigned to any department yet. Contact a platform administrator if you need access.</p>
                            </article>
                        @endunless
                    @endforelse
                @endunless

                @if (auth()->user()->hasEmployeeProfile() && ! auth()->user()->isEnrolledStudent())
                    <article class="tich-portal-card tich-portal-card--core">
                        <p class="tich-portal-card__eyebrow">My Portal</p>
                        <h2 class="tich-portal-card__title">My Employee Portal</h2>
                        <p class="tich-portal-card__desc">View your profile, leave, attendance, documents, and workplace concerns.</p>
                        <div class="tich-portal-card__actions">
                            <a href="{{ route('employee.dashboard') }}" class="tich-portal-card__btn tich-portal-card__btn--primary">Open employee portal</a>
                        </div>
                    </article>
                @endif

                @if (! ($awaitingDepartmentAssignment ?? false) && auth()->user()->isTeachingStaff())
                    <article class="tich-portal-card tich-portal-card--core">
                        <p class="tich-portal-card__eyebrow">Teaching &amp; Training</p>
                        <h2 class="tich-portal-card__title">Staff portal</h2>
                        <p class="tich-portal-card__desc">Manage units, attendance, assessments, lesson plans, and learning content.</p>
                        <div class="tich-portal-card__actions">
                            <a href="{{ route('staff.dashboard') }}" class="tich-portal-card__btn tich-portal-card__btn--primary">Open staff portal</a>
                        </div>
                    </article>
                @endif

                @if (! ($awaitingDepartmentAssignment ?? false) && (auth()->user()->student_id || auth()->user()->student))
                    <article class="tich-portal-card tich-portal-card--core">
                        <p class="tich-portal-card__eyebrow">Student</p>
                        <h2 class="tich-portal-card__title">Student portal</h2>
                        <p class="tich-portal-card__desc">View your enrolment profile, application history, and student services.</p>
                        <div class="tich-portal-card__actions">
                            <a href="{{ route('portal.dashboard') }}" class="tich-portal-card__btn tich-portal-card__btn--primary">Open student portal</a>
                        </div>
                    </article>
                @endif

                @if (! ($awaitingDepartmentAssignment ?? false))
                    @can('audit_logs.read')
                        <article class="tich-portal-card tich-portal-card--unit">
                            <p class="tich-portal-card__eyebrow">Security</p>
                            <h2 class="tich-portal-card__title">Audit logs</h2>
                            <p class="tich-portal-card__desc">Security and compliance activity trail.</p>
                            <div class="tich-portal-card__actions">
                                <a href="{{ route('admin.audit-logs.index') }}" class="tich-portal-card__btn tich-portal-card__btn--outline">View audit logs</a>
                            </div>
                        </article>
                    @endcan
                @endif
            </div>
        </div>
    </section>
@endsection
