@extends('layouts.staff')

@section('staff-content')
    <p class="tich-caption tich-mb-2"><a href="{{ route('staff.workplans.show', $workplan) }}" class="tich-link">← {{ $workplan->workplan_number }}</a></p>

    <x-page-toolbar :title="'Edit · '.$workplan->workplan_number" :meta="$workplan->title" />

    @if ($workplan->status === 'pending')
        <p class="tich-caption tich-mt-4">Editing a pending workplan resets Academic Registrar and QA approvals to pending.</p>
    @endif

    @if (in_array($workplan->status, ['pending', 'changes_requested', 'rejected'], true))
        <div class="tich-mt-4">
            @include('staff.workplans.partials.approval-status', ['summary' => $summary])
        </div>
    @endif

    <form method="POST" action="{{ route('staff.workplans.update', $workplan) }}" class="tich-card tich-mt-6" style="padding:1.5rem;">
        @csrf
        @method('PUT')
        @include('staff.workplans.partials.form-fields', ['workplan' => $workplan, 'semesters' => $semesters, 'activities' => $activities])

        <div class="tich-mt-6" style="display:flex; gap:0.75rem; flex-wrap:wrap;">
            <button type="submit" class="tich-btn tich-btn-secondary" name="submit" value="0">Save</button>
            <button type="submit" class="tich-btn tich-btn-primary" name="submit" value="1">Save &amp; submit</button>
        </div>
    </form>
@endsection
