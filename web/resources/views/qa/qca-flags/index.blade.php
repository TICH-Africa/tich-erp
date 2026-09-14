@extends('layouts.qa')

@section('title', 'QCA Flags')

@section('qa-content')
    <x-page-toolbar title="Quality Corrective Action Flags" meta="Compliance exceptions with downstream lock enforcement" />

    <div class="tich-card tich-table-panel tich-mt-8">
        <div class="tich-flex tich-flex--between tich-mb-4" style="flex-wrap:wrap;gap:0.5rem;">
            <h2 class="tich-h3">All QCA Flags</h2>
            <a href="{{ route('qa.qca-flags.create') }}" class="tich-btn tich-btn-primary">Raise flag</a>
        </div>
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:0.5rem;align-items:end;justify-content:flex-end;" class="tich-mb-8">
            <div class="tich-form-group" style="min-width:150px;">
                <label class="tich-label" for="category">Category</label>
                <select id="category" name="category" class="tich-input">
                    <option value="">All categories</option>
                    @foreach (\App\Models\Qa\QcaFlag::CATEGORIES as $cat)
                        <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group" style="min-width:130px;">
                <label class="tich-label" for="severity">Severity</label>
                <select id="severity" name="severity" class="tich-input">
                    <option value="">All severities</option>
                    @foreach (\App\Models\Qa\QcaFlag::SEVERITIES as $sev)
                        <option value="{{ $sev }}" @selected(request('severity') === $sev)>{{ $sev }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group" style="min-width:130px;">
                <label class="tich-label" for="status">Status</label>
                <select id="status" name="status" class="tich-input">
                    <option value="">All statuses</option>
                    @foreach (\App\Models\Qa\QcaFlag::STATUSES as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ str_replace('_', ' ', $st) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group" style="min-width:140px;">
                <label class="tich-label" for="assigned_to">Assigned to</label>
                <select id="assigned_to" name="assigned_to" class="tich-input">
                    <option value="">All staff</option>
                    @foreach ($staff as $s)
                        <option value="{{ $s->id }}" @selected((int) request('assigned_to') === $s->id)>{{ $s->first_name }} {{ $s->surname }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group" style="min-width:140px;">
                <label class="tich-label" for="from">From</label>
                <input id="from" type="date" name="from" class="tich-input" value="{{ request('from') }}">
            </div>
            <div class="tich-form-group" style="min-width:140px;">
                <label class="tich-label" for="to">To</label>
                <input id="to" type="date" name="to" class="tich-input" value="{{ request('to') }}">
            </div>
            <div style="align-self:end;">
                <button type="submit" class="tich-btn tich-btn-primary">Filter</button>
                <a href="{{ route('qa.qca-flags.index') }}" class="tich-btn tich-btn-ghost">Clear</a>
            </div>
        </form>

        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Flag</th>
                    <th>Category</th>
                    <th>Severity</th>
                    <th>Assigned</th>
                    <th>Status</th>
                    <th>Locks</th>
                    <th>Deadline</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($flags as $flag)
                    <tr class="{{ $flag->severity === 'Critical' ? 'tich-row--critical' : ($flag->severity === 'High' ? 'tich-row--alert' : '') }}">
                        <td>
                            <a href="{{ route('qa.qca-flags.show', $flag) }}" class="tich-link">{{ $flag->flag_number }}</a>
                            <p class="tich-caption">{{ \Illuminate\Support\Str::limit($flag->description, 80) }}</p>
                        </td>
                        <td>{{ $flag->category }}</td>
                        <td>
                            <span class="tich-badge tich-badge--{{ strtolower($flag->severity) }}">{{ $flag->severity }}</span>
                        </td>
                        <td>{{ $flag->assignedTo?->first_name }} {{ $flag->assignedTo?->surname ?? '-' }}</td>
                        <td><span class="tich-badge">{{ str_replace('_', ' ', $flag->status) }}</span></td>
                        <td>
                            @if ($flag->isHighOrCritical())
                                <span class="tich-caption tich-badge--alert">{{ count($flag->lockedModules()) }} module(s) locked</span>
                            @else
                                <span class="tich-caption">No lock</span>
                            @endif
                        </td>
                        <td>{{ $flag->resolution_deadline?->format('d M Y') ?? '-' }}</td>
                        <td><a href="{{ route('qa.qca-flags.show', $flag) }}" class="tich-btn tich-btn-secondary tich-btn--sm">Open</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">@include('partials.states.table-empty', ['colspan' => 8, 'title' => 'No QCA flags raised yet', 'icon' => 'shield-x'])</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if ($flags->hasPages())
            <div class="tich-mt-4">{{ $flags->links() }}</div>
        @endif
    </div>

    <div class="tich-grid tich-grid--3 tich-mt-8">
        <article class="tich-card">
            <p class="tich-caption">Open</p>
            <p class="tich-h2 tich-mt-2">{{ $flags->whereIn('status', ['open', 'in_progress'])->count() }}</p>
        </article>
        <article class="tich-card">
            <p class="tich-caption">High / Critical</p>
            <p class="tich-h2 tich-mt-2">{{ $flags->whereIn('severity', ['High', 'Critical'])->whereIn('status', ['open', 'in_progress'])->count() }}</p>
        </article>
        <article class="tich-card">
            <p class="tich-caption">Resolved</p>
            <p class="tich-h2 tich-mt-2">{{ $flags->whereIn('status', ['resolved', 'closed'])->count() }}</p>
        </article>
    </div>
@endsection
