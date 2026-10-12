@extends('layouts.hr')

@section('title', 'Corporate appraisal goals')

@section('hr-content')
    <x-page-toolbar title="Corporate / cascading goals" meta="Top-down KPIs assigned to staff — employees accept and work them on their appraisal">
        <x-slot:actions>
            <a href="{{ route('hr.appraisals.cycles') }}" class="tich-btn tich-btn-ghost">Cycles</a>
            <a href="{{ route('hr.appraisals.index') }}" class="tich-btn tich-btn-ghost">Appraisals</a>
            <a href="{{ route('hr.appraisals.corporate-goals.create') }}" class="tich-btn tich-btn-primary">+ Create goals</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="tich-alert tich-alert--danger tich-mt-4">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('hr.appraisals.corporate-goals') }}" class="tich-filters tich-mt-4">
        <div class="tich-form-group" style="min-width:14rem;">
            <label class="tich-label" for="filter-cycle">Cycle</label>
            <select id="filter-cycle" name="cycle_id" class="tich-input" onchange="this.form.submit()">
                <option value="">All cycles</option>
                @foreach ($cycles as $cycle)
                    <option value="{{ $cycle->id }}" @selected((int) ($cycleId ?? 0) === $cycle->id)>{{ $cycle->label() }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="tich-card tich-table-panel tich-mt-4">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Goal</th>
                        <th>Scope</th>
                        <th>Cycle</th>
                        <th>Weight hint</th>
                        <th>Active</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($goals as $goal)
                        <tr>
                            <td>
                                <strong>{{ $goal->code ? $goal->code.' · ' : '' }}{{ $goal->title }}</strong>
                                @if ($goal->description)
                                    <div class="tich-caption">{{ \Illuminate\Support\Str::limit($goal->description, 120) }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="tich-badge tich-badge--info tich-badge--sm">{{ $goal->scopeLabel() }}</span>
                                @if (is_array($goal->scope_values) && count($goal->scope_values))
                                    <div class="tich-caption tich-mt-1">{{ count($goal->scope_values) }} selected</div>
                                @endif
                            </td>
                            <td>{{ $goal->cycle?->label() ?? '—' }}</td>
                            <td>{{ $goal->weight_hint !== null ? number_format((float) $goal->weight_hint, 1).'%' : '—' }}</td>
                            <td>
                                @if ($goal->is_active)
                                    <span class="tich-badge tich-badge--success tich-badge--sm">Active</span>
                                @else
                                    <span class="tich-badge tich-badge--sm">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', [
                            'colspan' => 5,
                            'title' => 'No corporate goals yet',
                            'icon' => 'inbox',
                        ])
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
