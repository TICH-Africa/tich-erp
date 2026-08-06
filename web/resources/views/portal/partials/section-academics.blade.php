@php
    $academics = $portalData['academics'];
    $currentSemesterNumber = $academics['current_period']?->semester;
    $allSemesters = collect($academics['curriculum_by_semester'])->sortKeys();
    $otherSemesters = $currentSemesterNumber
        ? $allSemesters->except($currentSemesterNumber)
        : $allSemesters;

    $portalPeriodStatus = function ($period) {
        if (! $period?->start_date || ! $period?->end_date) {
            return null;
        }

        $today = now()->startOfDay();

        if ($today->lt($period->start_date)) {
            return 'Upcoming';
        }

        if ($today->gt($period->end_date)) {
            return 'Completed';
        }

        return 'In progress';
    };

    $academicsTab = $academicsTab ?? 'units';
    $tabTitles = [
        'units' => 'My Units',
        'exams' => 'Exams & Grades',
        'attendance' => 'Attendance',
    ];
@endphp

@php
    $academicsMeta = $biodata['academic']['program'];
    if ($academics['current_period']) {
        $academicsMeta .= ' · Semester '.$academics['current_period']->semester;
    } elseif ($academics['current_semester']) {
        $academicsMeta .= ' · '.$academics['current_semester']->semester_label;
    }
    if ($academics['curriculum']) {
        $academicsMeta .= ' · '.$academics['curriculum']->intakeLabel();
    }
@endphp

<x-page-toolbar :title="$tabTitles[$academicsTab] ?? 'Academics'" :meta="$academicsMeta" />

@if ($academics['curriculum'] && ! $academics['curriculum_is_published'])
    <div class="tich-notice tich-notice--info tich-mt-4">
        <p class="tich-text" style="margin:0;">
            Your programme curriculum for {{ $academics['curriculum']->intakeLabel() }} is still being finalised by the academic office.
            The units and dates shown below are provisional until the intake is published.
        </p>
    </div>
@endif

@if ($academicsTab === 'units')
    @include('portal.partials.academics.units')
@elseif ($academicsTab === 'exams')
    @include('portal.partials.academics.exams-grades')
@elseif ($academicsTab === 'attendance')
    @include('portal.partials.academics.attendance')
@endif
