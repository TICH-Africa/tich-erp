@extends('layouts.administration')

@section('title', 'Administration Workflow')

@section('administration-content')
    <x-page-toolbar title="Annual to weekly workflow" meta="Institutional calendar, departmental delivery, deadline control, and monthly learning" />

    @if ($department)
        <p class="tich-caption tich-mt-4">Scoped to {{ $department->dept_name }} ({{ $department->dept_code }}).</p>
    @else
        <p class="tich-caption tich-mt-4">Institution-wide Administration workspace. Select an administrative department for scoped task and variance records.</p>
    @endif

    <div class="tich-grid tich-grid--2 tich-mt-8" style="gap:1.5rem;align-items:start;">
        <article class="tich-card">
            <h2 class="tich-h3">1. Annual institutional plan</h2>
            <p class="tich-caption tich-mt-2">Record intakes, trimesters, holidays, graduation, and field-placement blocks.</p>
            <form method="POST" action="{{ route('administration.workflow.calendar.store') }}" class="tich-mt-4">
                @csrf
                <div class="tich-form-grid">
                    <label>Fiscal year<input class="tich-input" type="number" name="fiscal_year" value="{{ now()->year }}" required></label>
                    <label>Event type<select class="tich-input" name="event_type" required><option value="intake">Intake</option><option value="trimester">Trimester</option><option value="holiday">Holiday</option><option value="graduation">Graduation</option><option value="field_placement">Field placement</option></select></label>
                    <label>Title<input class="tich-input" name="title" required></label>
                    <label>Starts<input class="tich-input" type="date" name="starts_on" required></label>
                    <label>Ends<input class="tich-input" type="date" name="ends_on"></label>
                    <label>Notes<textarea class="tich-input" name="notes" rows="2"></textarea></label>
                </div>
                <button class="tich-btn tich-btn-primary tich-mt-4">Save calendar event</button>
            </form>
            <ul class="tich-program-card__meta tich-mt-6">
                @forelse ($events as $event)
                    <li><strong>{{ $event->title }}</strong> <span class="tich-caption">{{ ucfirst(str_replace('_', ' ', $event->event_type)) }} · {{ $event->starts_on?->format('d M Y') }}</span></li>
                @empty
                    <li class="tich-caption">No annual events recorded yet.</li>
                @endforelse
            </ul>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">2-3. Monthly to weekly task board</h2>
            <p class="tich-caption tich-mt-2">Section Heads add named owners, due dates, milestones, and budget implications.</p>
            <form method="POST" action="{{ route('administration.workflow.tasks.store') }}" class="tich-mt-4">
                @csrf
                <div class="tich-form-grid">
                    <label>Department<select class="tich-input" name="department_id" required>@foreach (\App\Models\Department::query()->where('dept_category', 'administrative')->where('is_active', true)->orderBy('dept_name')->get() as $unit)<option value="{{ $unit->id }}" @selected($department?->id === $unit->id)>{{ $unit->dept_name }}</option>@endforeach</select></label>
                    <label>Monthly plan<select class="tich-input" name="planning_cycle_id"><option value="">Unlinked</option>@foreach ($cycles->where('plan_tier', 'monthly') as $cycle)<option value="{{ $cycle->id }}">{{ $cycle->title }}</option>@endforeach</select></label>
                    <label>Task or milestone<input class="tich-input" name="title" required></label>
                    <label>Owner user ID<input class="tich-input" type="number" name="owner_id"></label>
                    <label>Due date<input class="tich-input" type="date" name="due_on" required></label>
                    <label>Budget implication<input class="tich-input" type="number" step="0.01" min="0" name="budget_implication" value="0"></label>
                    <label>Description<textarea class="tich-input" name="description" rows="2"></textarea></label>
                </div>
                <button class="tich-btn tich-btn-primary tich-mt-4">Add weekly task</button>
            </form>
            <ul class="tich-program-card__meta tich-mt-6">
                @forelse ($tasks as $task)
                    <li><strong>{{ $task->title }}</strong> <span class="tich-caption">{{ $task->due_on?->format('d M Y') }} · {{ ucfirst($task->status) }}</span>@if ($task->status !== 'completed') <form method="POST" action="{{ route('administration.workflow.tasks.complete', $task) }}" style="display:inline;margin-left:.5rem;">@csrf<button class="tich-btn tich-btn-secondary">Complete</button></form>@endif</li>
                @empty
                    <li class="tich-caption">No weekly tasks recorded yet.</li>
                @endforelse
            </ul>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">4. Prior-month deadline control</h2>
            <p class="tich-caption tich-mt-2">Planning cycles keep their submission deadline. Budget requests submitted after it are marked late with the deadline timestamp for review.</p>
            <ul class="tich-program-card__meta tich-mt-4">
                @forelse ($cycles as $cycle)
                    <li><strong>{{ $cycle->title }}</strong> <span class="tich-caption">{{ ucfirst($cycle->plan_tier) }} · deadline {{ $cycle->requisition_deadline?->format('d M Y H:i') }} · {{ ucfirst($cycle->status) }}</span></li>
                @empty
                    <li class="tich-caption">Create a planning cycle to enable deadline control.</li>
                @endforelse
            </ul>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">5. Monthly variance and lessons</h2>
            <p class="tich-caption tich-mt-2">Director of Administration records planned versus executed amounts and carries lessons into the next plan.</p>
            <form method="POST" action="{{ route('administration.workflow.variances.store') }}" class="tich-mt-4">
                @csrf
                <div class="tich-form-grid">
                    <label>Department<select class="tich-input" name="department_id" required>@foreach (\App\Models\Department::query()->where('dept_category', 'administrative')->where('is_active', true)->orderBy('dept_name')->get() as $unit)<option value="{{ $unit->id }}" @selected($department?->id === $unit->id)>{{ $unit->dept_name }}</option>@endforeach</select></label>
                    <label>Year<input class="tich-input" type="number" name="fiscal_year" value="{{ now()->year }}" required></label>
                    <label>Month<input class="tich-input" type="number" min="1" max="12" name="month" value="{{ now()->month }}" required></label>
                    <label>Planned amount<input class="tich-input" type="number" step="0.01" min="0" name="planned_amount" required></label>
                    <label>Actual amount<input class="tich-input" type="number" step="0.01" min="0" name="actual_amount" required></label>
                    <label>Monthly plan<select class="tich-input" name="planning_cycle_id"><option value="">Unlinked</option>@foreach ($cycles->where('plan_tier', 'monthly') as $cycle)<option value="{{ $cycle->id }}">{{ $cycle->title }}</option>@endforeach</select></label>
                    <label>Explanation<textarea class="tich-input" name="explanation" rows="2"></textarea></label>
                    <label>Lessons for next plan<textarea class="tich-input" name="lessons" rows="2"></textarea></label>
                </div>
                <button class="tich-btn tich-btn-primary tich-mt-4">Save variance review</button>
            </form>
            <ul class="tich-program-card__meta tich-mt-6">
                @forelse ($variances as $variance)
                    <li><strong>{{ $variance->fiscal_year }}/{{ $variance->month }}</strong> <span class="tich-caption">Planned KES {{ number_format($variance->planned_amount, 2) }} · actual KES {{ number_format($variance->actual_amount, 2) }}</span>@if ($variance->lessons)<div class="tich-caption">{{ $variance->lessons }}</div>@endif</li>
                @empty
                    <li class="tich-caption">No monthly variance reviews recorded yet.</li>
                @endforelse
            </ul>
        </article>
    </div>
@endsection
