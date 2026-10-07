@extends('layouts.hr')

@section('title', 'Corporate appraisal goals')

@section('hr-content')
    <x-page-toolbar title="Corporate / cascading goals" meta="Top-down KPIs staff can link into personal SMART goals (role-scoped)">
        <a href="{{ route('hr.appraisals.cycles') }}" class="tich-btn tich-btn-ghost">Cycles</a>
        <a href="{{ route('hr.appraisals.index') }}" class="tich-btn tich-btn-ghost">Appraisals</a>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif

    <div class="tich-card tich-mt-6">
        <h2 class="tich-h3">Add cascading goal</h2>
        <form method="POST" action="{{ route('hr.appraisals.corporate-goals.store') }}" class="tich-mt-3">
            @csrf
            <div class="tich-grid tich-grid--2" style="gap:0.75rem;">
                <div class="tich-form-group">
                    <label class="tich-label">Title</label>
                    <input type="text" name="title" class="tich-input" required value="{{ old('title') }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Code</label>
                    <input type="text" name="code" class="tich-input" value="{{ old('code') }}" placeholder="KPI-HR-01">
                </div>
                <div class="tich-form-group" style="grid-column:1/-1;">
                    <label class="tich-label">Description / KPI link</label>
                    <textarea name="description" class="tich-input" rows="2">{{ old('description') }}</textarea>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Cycle (optional)</label>
                    <select name="cycle_id" class="tich-input">
                        <option value="">All / standing library</option>
                        @foreach ($cycles as $cycle)
                            <option value="{{ $cycle->id }}" @selected((int) old('cycle_id', $cycleId) === $cycle->id)>{{ $cycle->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Scope</label>
                    <select name="role_scope" class="tich-input" id="corp-goal-scope">
                        <option value="all">All employees</option>
                        <option value="department">Specific departments</option>
                        <option value="job_title">Specific job titles</option>
                    </select>
                </div>
                <div class="tich-form-group" id="corp-goal-depts" hidden>
                    <label class="tich-label">Departments</label>
                    <select name="scope_values[]" class="tich-input" multiple size="4">
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->dept_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group" id="corp-goal-titles" hidden>
                    <label class="tich-label">Job titles (one per line)</label>
                    <textarea name="scope_values_text" class="tich-input" rows="3" placeholder="Lecturer&#10;Finance Officer"></textarea>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Weight hint %</label>
                    <input type="number" step="0.01" name="weight_hint" class="tich-input" value="{{ old('weight_hint') }}">
                </div>
                <label class="tich-caption" style="display:flex; gap:0.5rem; align-items:center;">
                    <input type="checkbox" name="is_active" value="1" checked> Active
                </label>
            </div>
            <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Save goal</button>
        </form>
    </div>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Goal</th>
                        <th>Scope</th>
                        <th>Cycle</th>
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
                            <td>{{ $goal->role_scope }}</td>
                            <td>{{ $goal->cycle?->label() ?? 'Standing' }}</td>
                            <td>{{ $goal->is_active ? 'Yes' : 'No' }}</td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 4, 'title' => 'No corporate goals yet', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        (function () {
            var scope = document.getElementById('corp-goal-scope');
            var depts = document.getElementById('corp-goal-depts');
            var titles = document.getElementById('corp-goal-titles');
            var form = scope && scope.closest('form');
            if (!scope || !form) return;

            function sync() {
                depts.hidden = scope.value !== 'department';
                titles.hidden = scope.value !== 'job_title';
            }
            scope.addEventListener('change', sync);
            sync();

            form.addEventListener('submit', function () {
                if (scope.value === 'job_title') {
                    var text = form.querySelector('[name="scope_values_text"]');
                    if (!text) return;
                    var lines = text.value.split(/\r?\n/).map(function (l) { return l.trim(); }).filter(Boolean);
                    form.querySelectorAll('input[name="scope_values[]"][data-dynamic]').forEach(function (el) { el.remove(); });
                    lines.forEach(function (line) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'scope_values[]';
                        input.value = line;
                        input.setAttribute('data-dynamic', '1');
                        form.appendChild(input);
                    });
                }
            });
        })();
    </script>
@endsection
