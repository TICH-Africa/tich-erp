@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')
    <section class="tich-section">
        <div class="tich-container">
            <x-page-toolbar title="Audit logs" meta="Authentication, MFA, RBAC, and access control events">
                <x-slot:actions>
                    <a href="{{ route('admin.audit-logs.verify') }}" class="tich-btn tich-btn-blue">Verify chain</a>
                </x-slot:actions>
                <x-slot:filters>
                    <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="tich-page-toolbar__filters-form">
                        @include('partials.search-field', ['placeholder' => 'Entity, reason, action', 'value' => $filters['search'] ?? ''])
                        <select id="action" name="action" class="tich-input tich-input--compact">
                            <option value="">All actions</option>
                            @foreach ($actions as $action)
                                <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                            @endforeach
                        </select>
                        <input type="text" id="module" name="module" value="{{ $filters['module'] ?? '' }}" class="tich-input tich-input--compact" placeholder="Module">
                        <select id="status" name="status" class="tich-input tich-input--compact">
                            <option value="">All</option>
                            <option value="success" @selected(($filters['status'] ?? '') === 'success')>Success</option>
                            <option value="failure" @selected(($filters['status'] ?? '') === 'failure')>Failure</option>
                        </select>
                    </form>
                </x-slot:filters>
            </x-page-toolbar>

            <div class="tich-card tich-table-panel">
                <table style="width: 100%; border-collapse: collapse; font-family: var(--font-ui); font-size: 0.8125rem;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--tich-neutral-border); text-align: left;">
                            <th style="padding: 0.75rem;">When</th>
                            <th style="padding: 0.75rem;">User</th>
                            <th style="padding: 0.75rem;">Action</th>
                            <th style="padding: 0.75rem;">Entity</th>
                            <th style="padding: 0.75rem;">Client</th>
                            <th style="padding: 0.75rem;">Status</th>
                            <th style="padding: 0.75rem;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            @php
                                $client = $log->client_context ?? [];
                                $location = is_array($client['location'] ?? null) ? ($client['location']['label'] ?? null) : null;
                            @endphp
                            <tr style="border-bottom: 1px solid var(--tich-neutral-border);">
                                <td style="padding: 0.75rem; white-space: nowrap;">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                                <td style="padding: 0.75rem;">
                                    @if ($log->user)
                                        {{ $log->user->username }}
                                    @else
                                        <span class="tich-caption">System</span>
                                    @endif
                                </td>
                                <td style="padding: 0.75rem;">
                                    <span class="tich-caption">{{ $log->module }}</span><br>
                                    {{ $log->action }}
                                </td>
                                <td style="padding: 0.75rem;">{{ $log->entity_type }} #{{ $log->entity_id }}</td>
                                <td style="padding: 0.75rem;">
                                    @if ($client !== [])
                                        <span class="tich-caption">{{ ucfirst($client['device_type'] ?? 'unknown') }}</span><br>
                                        {{ $client['browser'] ?? 'Unknown browser' }} · {{ $client['os'] ?? 'Unknown OS' }}<br>
                                        {{ $client['ip_address'] ?? $log->ip_address ?? '-' }}
                                        @if ($location)
                                            <br><span class="tich-caption">{{ $location }}</span>
                                        @endif
                                    @else
                                        {{ $log->ip_address ?? '-' }}
                                    @endif
                                </td>
                                <td style="padding: 0.75rem;">
                                    <span style="color: {{ $log->status === 'success' ? 'var(--tich-green)' : '#b45309' }};">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                </td>
                                <td style="padding: 0.75rem;">
                                    <a href="{{ route('admin.audit-logs.show', $log->id) }}" class="tich-link">Details</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding: 2rem; text-align: center;" class="tich-text">No audit records yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="tich-mt-6">
                {{ $logs->withQueryString()->links() }}
            </div>
        </div>
    </section>
@endsection
