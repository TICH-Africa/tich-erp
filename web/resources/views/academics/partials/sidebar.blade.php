@if (request()->routeIs('departments.academics.programs.curriculum') && ! empty($curriculumSidebarNavigation) && ! empty($program))
    @include('academics.partials.program-curriculum-sidebar', [
        'program' => $program,
        'curriculumSidebarNavigation' => $curriculumSidebarNavigation,
    ])
@elseif (! empty($learningDepartment))
    @php
        $departmentDashboard = app(\App\Services\DepartmentDashboardService::class);
    @endphp
    @include('departments.partials.sidebar', [
        'department' => $learningDepartment,
        'sidebarNavigation' => $departmentDashboard->sidebarNavigation(auth()->user(), $learningDepartment),
        'categoryLabel' => fn (\App\Models\Department $dept) => $departmentDashboard->categoryLabel($dept),
    ])
@else
    @php
        $attendanceBadgeKey = auth()->user()?->hasAnyRole(['Academic Registrar', 'Super Admin'])
            ? 'attendance-ledger.registrar'
            : 'attendance-ledger.hod';
    @endphp
    <aside class="tich-admin-sidebar" id="academics-admin-sidebar">
        @include('partials.navigation.sidebar-user')
        <p class="tich-admin-sidebar__title">{{ $department->dept_name }}</p>
        <p class="tich-caption">Academics &amp; curriculum</p>

        <nav class="tich-admin-sidebar__nav">
            @php
                $hub = \App\Support\AcademicsRouteParams::for([
                    'learning_department' => request()->integer('learning_department') ?: null,
                ]);

                $curriculumActive = request()->routeIs('departments.academics.units.*', 'departments.academics.programs.*', 'departments.academics.lesson-plans.*');
                $assessmentActive = request()->routeIs(
                    'departments.academics.attendance-ledger.*',
                    'departments.academics.clearance.*',
                    'departments.academics.performance.*',
                    'departments.academics.special-exam-requests.*',
                    'departments.academics.supplementary-requests.*'
                );
                $studentVoiceActive = request()->routeIs(
                    'departments.academics.suggestions.*',
                    'departments.academics.lifecycle-requests.*'
                );
                $studentServicesActive = request()->routeIs(
                    'departments.academics.profile-changes.*',
                    'departments.academics.transcript-requests.*',
                    'departments.academics.lifecycle-requests.*',
                    'departments.academics.evaluation-windows.*',
                    'departments.academics.document-requests.*'
                );
                $planningActive = request()->routeIs('departments.academics.calendar.*', 'admin.departments.*');

                $programsQuery = app(\App\Services\AcademicsAccessService::class)->programsQueryForHub(auth()->user(), $department);
                $sidebarProgram = $programsQuery->first();
                $suggestionsOnly = app(\App\Services\AcademicsAccessService::class)->isSuggestionsOnly(auth()->user());
                $canManageStudentServices = auth()->user()?->hasAnyRole(['Academic Registrar', 'Super Admin', 'Head of Academics']);
                $canReviewDeferments = auth()->user()?->hasAnyRole(['Academic Registrar', 'Dean of Students', 'Super Admin', 'Head of Academics']);
            @endphp

            @can('academics.read')
                @unless ($suggestionsOnly)
                    @include('partials.navigation.sidebar-link', [
                        'href' => route('departments.academics.dashboard', $hub),
                        'label' => 'Overview',
                        'icon' => 'dashboard',
                        'active' => request()->routeIs('departments.academics.dashboard'),
                    ])

                    @include('partials.navigation.sidebar-link', [
                        'href' => route('departments.academics.applications.index', $hub),
                        'label' => 'Application review',
                        'icon' => 'clipboard-list',
                        'active' => request()->routeIs('departments.academics.applications.*'),
                        'badgeKey' => 'applications.pending',
                    ])

                    @include('partials.navigation.sidebar-link', [
                        'href' => route('departments.academics.departments.index', $hub),
                        'label' => 'Learning Departments',
                        'icon' => 'building-2',
                        'active' => request()->routeIs('departments.academics.departments.*'),
                    ])

                    @php
                        $curriculumItems = [
                            [
                                'href' => route('departments.academics.units.index', $hub),
                                'label' => 'Unit catalog',
                                'icon' => 'library',
                                'active' => request()->routeIs('departments.academics.units.*'),
                                'badgeKey' => 'units.pending-registry',
                            ],
                        ];

                        if ($sidebarProgram) {
                            $curriculumItems[] = [
                                'type' => 'subgroup',
                                'label' => 'Programme curriculum',
                                'icon' => 'book-open',
                                'open' => request()->routeIs('departments.academics.programs.*'),
                                'active' => request()->routeIs('departments.academics.programs.*'),
                                'badgeKey' => 'curriculum.workflow',
                                'items' => collect(\App\Services\ProgramCurriculumService::curriculumSections())->map(function ($sectionLabel, $sectionKey) use ($hub, $sidebarProgram) {
                                    return [
                                        'href' => route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $sidebarProgram->id, 'section' => $sectionKey])),
                                        'label' => $sectionLabel,
                                        'icon' => 'circle',
                                        'active' => request()->routeIs('departments.academics.programs.curriculum') && request()->query('section') === $sectionKey,
                                    ];
                                })->values()->all(),
                            ];
                        }

                        $curriculumItems[] = [
                            'href' => route('departments.academics.lesson-plans.index', $hub),
                            'label' => 'Lesson plan approval',
                            'icon' => 'notebook',
                            'active' => request()->routeIs('departments.academics.lesson-plans.index') || request()->routeIs('departments.academics.lesson-plans.show'),
                            'badgeKey' => 'lesson-plans.review',
                        ];
                        $curriculumItems[] = [
                            'href' => route('departments.academics.lesson-plans.audit', $hub),
                            'label' => 'Lesson plan audit',
                            'icon' => 'search',
                            'active' => request()->routeIs('departments.academics.lesson-plans.audit'),
                        ];
                    @endphp

                    @include('partials.navigation.sidebar-group', [
                        'label' => 'Curriculum & Teaching',
                        'icon' => 'book-open',
                        'open' => $curriculumActive,
                        'active' => $curriculumActive,
                        'badgeKey' => 'curriculum',
                        'items' => $curriculumItems,
                    ])

                    @include('partials.navigation.sidebar-group', [
                        'label' => 'Assessment & Exams',
                        'icon' => 'clipboard-check',
                        'open' => $assessmentActive,
                        'active' => $assessmentActive,
                        'badgeKey' => 'attendance-ledger.registrar',
                        'items' => [
                            [
                                'href' => route('departments.academics.attendance-ledger.index', $hub),
                                'label' => 'Attendance ledger',
                                'icon' => 'clipboard-check',
                                'active' => request()->routeIs('departments.academics.attendance-ledger.*'),
                            ],
                            [
                                'href' => route('departments.academics.clearance.index', $hub),
                                'label' => 'Academic clearance',
                                'icon' => 'check-circle',
                                'active' => request()->routeIs('departments.academics.clearance.*'),
                            ],
                            [
                                'href' => route('departments.academics.performance.index', $hub),
                                'label' => 'Performance terminal',
                                'icon' => 'bar-chart',
                                'active' => request()->routeIs('departments.academics.performance.*'),
                            ],
                            [
                                'href' => route('departments.academics.special-exam-requests.index', $hub),
                                'label' => 'Special exam requests',
                                'icon' => 'file-text',
                                'active' => request()->routeIs('departments.academics.special-exam-requests.*'),
                            ],
                            [
                                'href' => route('departments.academics.supplementary-requests.index', $hub),
                                'label' => 'Supplementary requests',
                                'icon' => 'refresh-cw',
                                'active' => request()->routeIs('departments.academics.supplementary-requests.*'),
                            ],
                        ],
                    ])
                @endunless

                @include('partials.navigation.sidebar-group', [
                    'label' => 'Student voice',
                    'icon' => 'clipboard-list',
                    'open' => $studentVoiceActive || $suggestionsOnly,
                    'active' => $studentVoiceActive,
                    'badgeKey' => 'suggestions.open',
                    'items' => array_values(array_filter([
                        [
                            'href' => route('departments.academics.suggestions.index', $hub),
                            'label' => 'Suggestion box',
                            'icon' => 'clipboard-list',
                            'active' => request()->routeIs('departments.academics.suggestions.*'),
                            'badgeKey' => 'suggestions.open',
                        ],
                        $canReviewDeferments ? [
                            'href' => route('departments.academics.lifecycle-requests.index', $hub),
                            'label' => 'Deferment requests',
                            'icon' => 'refresh-cw',
                            'active' => request()->routeIs('departments.academics.lifecycle-requests.*'),
                            'badgeKey' => 'lifecycle.pending',
                        ] : null,
                    ])),
                ])

                @unless ($suggestionsOnly)
                    @if ($canManageStudentServices)
                        @include('partials.navigation.sidebar-group', [
                            'label' => 'Student services',
                            'icon' => 'users',
                            'open' => $studentServicesActive && ! request()->routeIs('departments.academics.lifecycle-requests.*'),
                            'active' => $studentServicesActive && ! request()->routeIs('departments.academics.lifecycle-requests.*'),
                            'items' => [
                                [
                                    'href' => route('departments.academics.profile-changes.index', $hub),
                                    'label' => 'Profile approvals',
                                    'icon' => 'user',
                                    'active' => request()->routeIs('departments.academics.profile-changes.*'),
                                ],
                                [
                                    'href' => route('departments.academics.transcript-requests.index', $hub),
                                    'label' => 'Transcript requests',
                                    'icon' => 'file-text',
                                    'active' => request()->routeIs('departments.academics.transcript-requests.*'),
                                ],
                                [
                                    'href' => route('departments.academics.document-requests.index', $hub),
                                    'label' => 'Document requests',
                                    'icon' => 'folder',
                                    'active' => request()->routeIs('departments.academics.document-requests.*'),
                                ],
                                [
                                    'href' => route('departments.academics.evaluation-windows.index', $hub),
                                    'label' => 'Evaluations',
                                    'icon' => 'clipboard-check',
                                    'active' => request()->routeIs('departments.academics.evaluation-windows.*'),
                                ],
                            ],
                        ])
                    @endif

                    @include('partials.navigation.sidebar-group', [
                        'label' => 'Planning',
                        'icon' => 'calendar',
                        'open' => $planningActive,
                        'active' => $planningActive,
                        'items' => [
                            [
                                'href' => route('departments.academics.calendar.index', $hub),
                                'label' => 'Academic calendar',
                                'icon' => 'calendar',
                                'active' => request()->routeIs('departments.academics.calendar.*'),
                            ],
                        ],
                    ])

                    @include('partials.navigation.department-budgeting-link', ['module' => 'academics'])
                @endunless
            @endcan

            <p class="tich-admin-sidebar__section">Navigation</p>
            @include('partials.navigation.sidebar-link', [
                'href' => $suggestionsOnly
                    ? route('departments.academics.suggestions.index', $hub)
                    : route('departments.academics.dashboard', $hub),
                'label' => $department->dept_name.' hub',
                'icon' => 'layout-grid',
                'muted' => true,
            ])
        </nav>
    </aside>
@endif
