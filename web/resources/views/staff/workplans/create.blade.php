@extends('layouts.staff')

@section('staff-content')
    <p class="tich-caption tich-mb-2"><a href="{{ route('staff.workplans.index') }}" class="tich-link">← Semester workplans</a></p>

    <x-page-toolbar title="New semester workplan" :meta="$staff->department?->dept_name" />

    <form method="POST" action="{{ route('staff.workplans.store') }}" class="tich-card tich-mt-6" style="padding:1.5rem;">
        @csrf
        @include('staff.workplans.partials.form-fields', ['workplan' => null, 'semesters' => $semesters, 'activities' => $activities])

        <div class="tich-mt-6" style="display:flex; gap:0.75rem; flex-wrap:wrap;">
            <button type="submit" class="tich-btn tich-btn-secondary" name="submit" value="0">Save draft</button>
            <button type="submit" class="tich-btn tich-btn-primary" name="submit" value="1">Save &amp; submit</button>
        </div>
    </form>
@endsection
