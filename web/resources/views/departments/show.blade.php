@extends('layouts.app')

@section('title', $department->dept_name)

@section('content')
    <section class="tich-section">
        <div class="tich-container">
            <p class="tich-mb-4">
                <a href="{{ route('dashboard') }}" class="tich-link">← Back to dashboard</a>
            </p>

            <div class="tich-mb-8">
                <p class="tich-caption">{{ $categoryLabel }}</p>
                <h1 class="tich-h1 tich-mt-2">{{ $department->dept_name }}</h1>
                <p class="tich-text tich-mt-2">
                    {{ $department->dept_code }}
                    @if ($department->group)
                        · {{ $department->group->group_name }}
                    @endif
                    @if ($department->campus)
                        · {{ $department->campus->campus_name }}
                    @endif
                </p>
            </div>

            @if ($childDepartments->isNotEmpty())
                <div class="tich-mb-8">
                    <h2 class="tich-h2" style="font-size: 1.5rem;">Departments</h2>
                    <p class="tich-text tich-mt-2 tich-mb-6">Select an academic or operational unit within {{ $department->dept_name }}.</p>

                    <div class="tich-grid tich-grid--3">
                        @foreach ($childDepartments as $child)
                            <article class="tich-card">
                                <p class="tich-caption">{{ $categoryLabel($child) }}</p>
                                <h3 class="tich-h3 tich-mt-2">{{ $child->dept_name }}</h3>
                                <p class="tich-text tich-mt-2">{{ $cardDescription($child) }}</p>
                                <a href="{{ route('departments.show', $child) }}" class="tich-btn tich-btn-secondary tich-mt-4">Open</a>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($modules !== [])
                <div>
                    <h2 class="tich-h2" style="font-size: 1.5rem;">Tools</h2>
                    <p class="tich-text tich-mt-2 tich-mb-6">Department modules available to you.</p>

                    <div class="tich-grid tich-grid--3">
                        @foreach ($modules as $module)
                            <article class="tich-card">
                                <h3 class="tich-h3">{{ $module['label'] }}</h3>
                                <p class="tich-text tich-mt-2">{{ $module['description'] }}</p>
                                @if (! empty($module['coming_soon']))
                                    <p class="tich-caption tich-mt-4">Coming soon</p>
                                @else
                                    <a href="{{ route($module['route'], $module['params'] ?? []) }}" class="tich-btn tich-btn-primary tich-mt-4">Open</a>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
            @elseif ($childDepartments->isEmpty())
                <article class="tich-card">
                    <h3 class="tich-h3">No tools available yet</h3>
                    <p class="tich-text">There are no modules assigned for this department, or you do not have permission to access them.</p>
                </article>
            @endif
        </div>
    </section>
@endsection
