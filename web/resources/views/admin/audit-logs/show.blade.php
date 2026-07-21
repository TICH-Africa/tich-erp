@extends('layouts.app')

@section('title', 'Audit Log #'.$log->id)

@section('content')
    @php
        $client = $log->client_context ?? [];
        $location = is_array($client['location'] ?? null) ? $client['location'] : [];
        $network = is_array($client['network'] ?? null) ? $client['network'] : [];
    @endphp

    <section class="tich-section">
        <div class="tich-container" style="max-width: 56rem;">
            <a href="{{ route('admin.audit-logs.index') }}" class="tich-link">&larr; Back to audit logs</a>

            <h1 class="tich-h1 tich-mt-4" style="font-size: 2rem;">Audit record #{{ $log->id }}</h1>

            <div class="tich-card tich-mt-6" style="padding: 1.5rem;">
                <dl style="display: grid; grid-template-columns: 10rem 1fr; gap: 0.75rem 1rem; margin: 0;">
                    <dt class="tich-caption">Timestamp</dt>
                    <dd>{{ $log->created_at?->format('Y-m-d H:i:s') }}</dd>

                    <dt class="tich-caption">User</dt>
                    <dd>{{ $log->user?->username ?? 'System' }} @if($log->user) ({{ $log->user->email }}) @endif</dd>

                    <dt class="tich-caption">Action</dt>
                    <dd>{{ $log->action }}</dd>

                    <dt class="tich-caption">Module</dt>
                    <dd>{{ $log->module ?? '—' }}</dd>

                    <dt class="tich-caption">Entity</dt>
                    <dd>{{ $log->entity_type }} #{{ $log->entity_id }}</dd>

                    <dt class="tich-caption">Status</dt>
                    <dd>{{ ucfirst($log->status) }}</dd>

                    <dt class="tich-caption">IP address</dt>
                    <dd>{{ $client['ip_address'] ?? $log->ip_address ?? '—' }}</dd>

                    <dt class="tich-caption">Reason</dt>
                    <dd>{{ $log->reason ?? '—' }}</dd>

                    <dt class="tich-caption">Record hash</dt>
                    <dd style="word-break: break-all; font-family: monospace; font-size: 0.75rem;">{{ $log->record_hash ?? '—' }}</dd>

                    <dt class="tich-caption">Previous hash</dt>
                    <dd style="word-break: break-all; font-family: monospace; font-size: 0.75rem;">{{ $log->previous_hash ?? '—' }}</dd>
                </dl>
            </div>

            @if ($client !== [])
                <div class="tich-card tich-mt-4" style="padding: 1.5rem;">
                    <h2 class="tich-h3">Client &amp; session context</h2>
                    <dl style="display: grid; grid-template-columns: 10rem 1fr; gap: 0.75rem 1rem; margin: 1rem 0 0;">
                        <dt class="tich-caption">Device type</dt>
                        <dd>{{ ucfirst($client['device_type'] ?? '—') }}</dd>

                        <dt class="tich-caption">Browser</dt>
                        <dd>
                            {{ $client['browser'] ?? '—' }}
                            @if (! empty($client['browser_version']))
                                ({{ $client['browser_version'] }})
                            @endif
                        </dd>

                        <dt class="tich-caption">Operating system</dt>
                        <dd>
                            {{ $client['os'] ?? '—' }}
                            @if (! empty($client['os_version']))
                                ({{ $client['os_version'] }})
                            @endif
                        </dd>

                        <dt class="tich-caption">Location</dt>
                        <dd>{{ $location['label'] ?? '—' }}</dd>

                        <dt class="tich-caption">Internet / network</dt>
                        <dd>{{ $network['label'] ?? ($location['isp'] ?? '—') }}</dd>

                        <dt class="tich-caption">Connection</dt>
                        <dd>
                            @if (! empty($network['connection_type']) || ! empty($network['effective_type']))
                                {{ $network['connection_type'] ?? 'Unknown' }}
                                @if (! empty($network['effective_type']))
                                    · {{ strtoupper($network['effective_type']) }}
                                @endif
                                @if (! empty($network['downlink_mbps']))
                                    · {{ $network['downlink_mbps'] }} Mbps
                                @endif
                            @else
                                —
                            @endif
                        </dd>

                        <dt class="tich-caption">ISP / carrier</dt>
                        <dd>{{ $location['isp'] ?? '—' }}</dd>

                        <dt class="tich-caption">Timezone</dt>
                        <dd>{{ $client['timezone'] ?? '—' }}</dd>

                        <dt class="tich-caption">Language</dt>
                        <dd>{{ $client['language'] ?? '—' }}</dd>

                        <dt class="tich-caption">Screen</dt>
                        <dd>{{ $client['screen'] ?? '—' }}</dd>

                        <dt class="tich-caption">Channel</dt>
                        <dd>{{ ucfirst($client['channel'] ?? '—') }}</dd>

                        <dt class="tich-caption">User agent</dt>
                        <dd style="word-break: break-word;">{{ $client['user_agent'] ?? $log->user_agent ?? '—' }}</dd>
                    </dl>
                </div>
            @elseif ($log->user_agent)
                <div class="tich-card tich-mt-4" style="padding: 1.5rem;">
                    <h2 class="tich-h3">Client context</h2>
                    <p class="tich-text tich-mt-2" style="word-break: break-word;">{{ $log->user_agent }}</p>
                </div>
            @endif

            @if ($log->old_value)
                <div class="tich-card tich-mt-4" style="padding: 1.5rem;">
                    <h2 class="tich-h3">Previous value</h2>
                    <pre style="overflow-x: auto; font-size: 0.75rem; background: var(--tich-neutral); padding: 1rem; border-radius: 0.5rem;">{{ json_encode($log->old_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            @endif

            @if ($log->new_value)
                <div class="tich-card tich-mt-4" style="padding: 1.5rem;">
                    <h2 class="tich-h3">New value</h2>
                    <pre style="overflow-x: auto; font-size: 0.75rem; background: var(--tich-neutral); padding: 1rem; border-radius: 0.5rem;">{{ json_encode($log->new_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            @endif
        </div>
    </section>
@endsection
