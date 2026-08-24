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
                $assessmentActive = request()->routeIs('departments.academics.attendance-ledger.*', 'departments.academics.clearance.*', 'departments.academics.performance.*');
                $planningActive = request()->routeIs('departments.academics.calendar.*', 'admin.departments.*');

                $programsQuery = app(\App\Services\AcademicsAccessService::class)->programsQueryForHub(auth()->user(), $department);
                $sidebarProgram = $programsQuery->first();
            @endphp

            @can('academics.read')
                @include('partials.navigation.sidebar-link', [
                    'href' => route('departments.academics.dashboard', $hub),
                    'label' => 'Overview',
                    'icon' => 'dashboard',
                    'active' => request()->routeIs('departments.academics.dashboard'),
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
                    ],
                ])

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
                        [
                            'href' => route('admin.departments.index'),
                            'label' => 'Department budgeting',
                            'icon' => 'dollar-sign',
                            'active' => request()->routeIs('admin.departments.*'),
                        ],
                    ],
                ])
            @endcan

            <p class="tich-admin-sidebar__section">Navigation</p>
            @include('partials.navigation.sidebar-link', ['href' => route('departments.academics.dashboard', $hub), 'label' => $department->dept_name.' hub', 'icon' => 'layout-grid', 'muted' => true])
        </nav>
    </aside>
@endif
