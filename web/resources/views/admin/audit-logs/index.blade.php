@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')
    <section class="tich-section">
        <div class="tich-container">
            <x-page-toolbar title="Audit logs" meta="Changes across HR, Finance, Academics, Administration, and all modules">
                <x-slot:actions>
                    <a href="{{ route('admin.audit-logs.export', request()->query()) }}" class="tich-btn tich-btn-primary">Export Excel</a>
                    <a href="{{ route('admin.audit-logs.verify') }}" class="tich-btn tich-btn-blue">Verify chain</a>
                </x-slot:actions>
                <x-slot:filters>
                    <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="tich-page-toolbar__filters-form" style="flex-wrap:wrap;gap:0.4rem;">
                        <select name="module" class="tich-input tich-input--compact" title="Module / department">
                            <option value="">All modules</option>
                            @foreach ($modules as $key => $label)
                                <option value="{{ $key }}" @selected(($filters['module'] ?? '') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>

                        <select name="account_type" class="tich-input tich-input--compact" title="Account type">
                            <option value="">All accounts</option>
                            <option value="staff" @selected(($filters['account_type'] ?? '') === 'staff')>Employees</option>
                            <option value="student" @selected(($filters['account_type'] ?? '') === 'student')>Students</option>
                            <option value="system" @selected(($filters['account_type'] ?? '') === 'system')>System</option>
                        </select>

                        <input type="text" name="account" value="{{ $filters['account'] ?? '' }}"
                               class="tich-input tich-input--compact" placeholder="User email / emp no / student"
                               style="min-width:11rem;">

                        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}"
                               class="tich-input tich-input--compact" title="From date">

                        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}"
                               class="tich-input tich-input--compact" title="To date">

                        <select name="status" class="tich-input tich-input--compact">
                            <option value="">Any status</option>
                            <option value="success" @selected(($filters['status'] ?? '') === 'success')>Success</option>
                            <option value="failure" @selected(($filters['status'] ?? '') === 'failure')>Failure</option>
                        </select>

                        @include('partials.search-field', ['placeholder' => 'Action / entity / reason', 'value' => $filters['search'] ?? ''])

                        <button type="submit" class="tich-btn tich-btn-secondary tich-btn--compact">Filter</button>
                        @if (collect($filters)->filter()->isNotEmpty())
                            <a href="{{ route('admin.audit-logs.index') }}" class="tich-btn tich-btn-ghost tich-btn--compact">Clear</a>
                        @endif
                    </form>
                </x-slot:filters>
            </x-page-toolbar>

            <div class="tich-card tich-table-panel">
                <div class="tich-table-wrap">
                    <table class="tich-admin-table" style="font-size:0.75rem;">
                        <thead>
                            <tr>
                                <th style="width:7.5rem;">When</th>
                                <th style="width:7rem;">Module</th>
                                <th>Action</th>
                                <th style="width:10rem;">User</th>
                                <th>Summary</th>
                                <th style="width:4.5rem;">Status</th>
                                <th style="width:3rem;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                @php
                                    $moduleLabel = $modules[$log->module] ?? ($log->module ? ucfirst($log->module) : '-');
                                    $accountHint = null;
                                    if ($log->user?->staff_id) {
                                        $accountHint = $log->user->staff?->employee_number ?? 'Employee';
                                    } elseif ($log->user?->student_id) {
                                        $accountHint = $log->user->student?->registration_number ?? 'Student';
                                    }
                                @endphp
                                <tr>
                                    <td style="white-space:nowrap;" class="tich-caption">{{ $log->created_at?->format('d M y H:i') }}</td>
                                    <td>
                                        <span class="tich-badge tich-badge--secondary" style="font-size:0.65rem;">{{ $moduleLabel }}</span>
                                    </td>
                                    <td>
                                        <span title="{{ $log->action }}">{{ \Illuminate\Support\Str::afterLast($log->action, '.') ?: $log->action }}</span>
                                        <span class="tich-caption" style="display:block;">{{ $log->action }}</span>
                                    </td>
                                    <td>
                                        @if ($log->user)
                                            <span>{{ $log->user->displayName() }}</span>
                                            @if ($accountHint)
                                                <span class="tich-caption" style="display:block;">{{ $accountHint }}</span>
                                            @endif
                                        @else
                                            <span class="tich-caption">System</span>
                                        @endif
                                    </td>
                                    <td class="tich-caption" style="max-width:16rem;">
                                        {{ app(\App\Services\AuditService::class)->summary($log) }}
                                    </td>
                                    <td>
                                        <span style="color: {{ ($log->status ?? '') === 'success' ? 'var(--tich-green)' : '#b45309' }};">
                                            {{ ucfirst($log->status ?? '-') }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.audit-logs.show', $log->id) }}" class="tich-link">View</a>
                                    </td>
                                </tr>
                            @empty
                                @include('partials.states.table-empty', [
                                    'colspan' => 7,
                                    'title' => 'No audit records match',
                                    'description' => 'Try widening the date range or clearing filters.',
                                    'icon' => 'inbox',
                                ])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tich-mt-4" style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
                <p class="tich-caption" style="margin:0;">
                    Showing {{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }}
                </p>
                {{ $logs->withQueryString()->links() }}
            </div>
        </div>
    </section>
@endsection
