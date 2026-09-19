@extends('layouts.ict')

@section('title', 'Platform Performance')

@php
    $summary = $initialMetrics['alert_summary'] ?? ['count' => 0, 'critical' => 0, 'warning' => 0, 'needs_action' => false, 'highest' => 'ok'];
    $alerts = $initialMetrics['alerts'] ?? [];
    $flags = $initialMetrics['metric_flags'] ?? [];
    $host = $initialMetrics['host'] ?? [];
    $flagClass = function (?string $metric) use ($flags): string {
        $sev = $flags[$metric] ?? null;
        if ($sev === 'critical') {
            return ' is-flagged is-flagged--critical';
        }
        if ($sev === 'warning') {
            return ' is-flagged is-flagged--warning';
        }

        return '';
    };
@endphp

@section('ict-content')
    <x-page-toolbar title="Platform Performance" meta="Real-time server, application, and database health (last {{ $initialMetrics['window_minutes'] }} minutes)">
        <x-slot:actions>
            <span class="tich-status-badge {{ ($summary['highest'] ?? 'ok') === 'critical' ? 'is-danger' : (($summary['highest'] ?? 'ok') === 'warning' ? 'is-warning' : 'is-success') }}" id="perf-health-badge">
                @if (($summary['needs_action'] ?? false))
                    Needs action ({{ $summary['count'] }})
                @else
                    Healthy
                @endif
            </span>
            <span class="tich-caption" id="perf-updated-at">Updated {{ \Illuminate\Support\Carbon::parse($initialMetrics['collected_at'])->timezone(config('app.timezone'))->format('H:i:s') }}</span>
            <button type="button" class="tich-btn tich-btn-secondary" id="perf-refresh">Refresh now</button>
        </x-slot:actions>
    </x-page-toolbar>

    <p class="tich-caption tich-mt-2" id="perf-status-line">Live metrics refresh every 5 seconds.</p>

    <div id="perf-alerts" class="tich-mt-4" @if (!($summary['needs_action'] ?? false)) hidden @endif>
        <div class="tich-alert {{ ($summary['highest'] ?? '') === 'critical' ? 'tich-alert--error' : 'tich-alert--warning' }}" id="perf-alerts-banner">
            <strong id="perf-alerts-title">
                @if (($summary['critical'] ?? 0) > 0)
                    {{ $summary['critical'] }} critical / {{ $summary['warning'] ?? 0 }} warning — action needed
                @else
                    {{ $summary['warning'] ?? 0 }} warning(s) — review recommended
                @endif
            </strong>
            <ul class="tich-mt-2" id="perf-alerts-list" style="margin:0.5rem 0 0; padding-left:1.25rem;">
                @foreach ($alerts as $alert)
                    <li>
                        <span class="tich-status-badge {{ ($alert['severity'] ?? '') === 'critical' ? 'is-danger' : 'is-warning' }}">{{ strtoupper($alert['severity'] ?? '') }}</span>
                        <strong>{{ $alert['title'] ?? '' }}</strong>
                        — {{ $alert['message'] ?? '' }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    <div id="perf-alerts-ok" class="tich-mt-4" @if ($summary['needs_action'] ?? false) hidden @endif>
        <div class="tich-alert tich-alert--success">All watched thresholds are within range. No action needed.</div>
    </div>

    <h2 class="tich-h3 tich-mt-8">Server &amp; backend performance</h2>
    <div class="tich-grid tich-grid--4 tich-mt-4" id="perf-server-grid">
        <article class="tich-card tich-stat{{ $flagClass('server.ttfb_ms') }}" data-flag-metric="server.ttfb_ms">
            <p class="tich-caption">TTFB (p50)</p>
            <p class="tich-stat__value" data-metric="server.ttfb_ms">{{ number_format($initialMetrics['server']['ttfb_ms'], 1) }}<span class="tich-caption"> ms</span></p>
        </article>
        <article class="tich-card tich-stat{{ $flagClass('server.ttfb_p95_ms') }}" data-flag-metric="server.ttfb_p95_ms">
            <p class="tich-caption">TTFB (p95)</p>
            <p class="tich-stat__value" data-metric="server.ttfb_p95_ms">{{ number_format($initialMetrics['server']['ttfb_p95_ms'], 1) }}<span class="tich-caption"> ms</span></p>
        </article>
        <article class="tich-card tich-stat{{ $flagClass('server.avg_response_ms') }}" data-flag-metric="server.avg_response_ms">
            <p class="tich-caption">Avg response time</p>
            <p class="tich-stat__value" data-metric="server.avg_response_ms">{{ number_format($initialMetrics['server']['avg_response_ms'], 1) }}<span class="tich-caption"> ms</span></p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Throughput (RPS)</p>
            <p class="tich-stat__value" data-metric="server.rps">{{ number_format($initialMetrics['server']['rps'], 3) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">2xx success rate</p>
            <p class="tich-stat__value" data-metric="server.success_rate_2xx_pct">{{ number_format($initialMetrics['server']['success_rate_2xx_pct'], 2) }}<span class="tich-caption"> %</span></p>
        </article>
        <article class="tich-card tich-stat{{ $flagClass('server.error_rate_4xx_pct') }}" data-flag-metric="server.error_rate_4xx_pct">
            <p class="tich-caption">4xx error rate</p>
            <p class="tich-stat__value" data-metric="server.error_rate_4xx_pct">{{ number_format($initialMetrics['server']['error_rate_4xx_pct'], 2) }}<span class="tich-caption"> %</span></p>
        </article>
        <article class="tich-card tich-stat{{ $flagClass('server.error_rate_5xx_pct') }}" data-flag-metric="server.error_rate_5xx_pct">
            <p class="tich-caption">5xx error rate</p>
            <p class="tich-stat__value" data-metric="server.error_rate_5xx_pct">{{ number_format($initialMetrics['server']['error_rate_5xx_pct'], 2) }}<span class="tich-caption"> %</span></p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Requests (window)</p>
            <p class="tich-stat__value" data-metric="server.requests">{{ number_format($initialMetrics['server']['requests']) }}</p>
        </article>
    </div>

    <div class="tich-grid tich-grid--3 tich-mt-4">
        <article class="tich-card">
            <h3 class="tich-h4" style="margin-top:0;">HTTP status mix</h3>
            <dl class="tich-dl">
                <dt>2xx</dt>
                <dd data-metric="server.status_counts.2xx">{{ $initialMetrics['server']['status_counts']['2xx'] }}</dd>
                <dt>4xx</dt>
                <dd data-metric="server.status_counts.4xx">{{ $initialMetrics['server']['status_counts']['4xx'] }}</dd>
                <dt>5xx</dt>
                <dd data-metric="server.status_counts.5xx">{{ $initialMetrics['server']['status_counts']['5xx'] }}</dd>
            </dl>
        </article>
        <article class="tich-card{{ $flagClass('server.memory.used_pct') }}" data-flag-metric="server.memory.used_pct">
            <h3 class="tich-h4" style="margin-top:0;">PHP memory</h3>
            <dl class="tich-dl">
                <dt>Current</dt>
                <dd><span data-metric="server.memory.current_mb">{{ number_format($initialMetrics['server']['memory']['current_mb'], 2) }}</span> MB</dd>
                <dt>Peak (window)</dt>
                <dd><span data-metric="server.memory.peak_mb">{{ number_format($initialMetrics['server']['memory']['peak_mb'], 2) }}</span> MB</dd>
                <dt>PHP limit</dt>
                <dd data-metric="server.memory.limit">{{ $initialMetrics['server']['memory']['limit'] }}</dd>
                <dt>Peak vs limit</dt>
                <dd><span data-metric="server.memory.used_pct">{{ $initialMetrics['server']['memory']['used_pct'] ?? '—' }}</span>%</dd>
            </dl>
        </article>
        <article class="tich-card{{ $flagClass('server.cpu.display') }}" data-flag-metric="server.cpu.display">
            <h3 class="tich-h4" style="margin-top:0;">CPU utilization</h3>
            <p class="tich-text" data-metric-text="server.cpu.note">{{ $initialMetrics['server']['cpu']['note'] }}</p>
            <p class="tich-stat__value tich-mt-4" data-metric="server.cpu.display">
                @if (!empty($initialMetrics['server']['cpu']['load']))
                    {{ implode(' / ', $initialMetrics['server']['cpu']['load']) }}
                @else
                    —
                @endif
            </p>
        </article>
    </div>

    <h2 class="tich-h3 tich-mt-8">Database health</h2>
    <div class="tich-grid tich-grid--4 tich-mt-4">
        <article class="tich-card tich-stat{{ $flagClass('database.avg_query_ms') }}" data-flag-metric="database.avg_query_ms">
            <p class="tich-caption">Avg query time</p>
            <p class="tich-stat__value" data-metric="database.avg_query_ms">{{ number_format($initialMetrics['database']['avg_query_ms'], 2) }}<span class="tich-caption"> ms</span></p>
        </article>
        <article class="tich-card tich-stat{{ $flagClass('database.query_p95_ms') }}" data-flag-metric="database.query_p95_ms">
            <p class="tich-caption">Query p95</p>
            <p class="tich-stat__value" data-metric="database.query_p95_ms">{{ number_format($initialMetrics['database']['query_p95_ms'], 1) }}<span class="tich-caption"> ms</span></p>
        </article>
        <article class="tich-card tich-stat{{ $flagClass('database.connection_latency_ms') }}" data-flag-metric="database.connection_latency_ms">
            <p class="tich-caption">Connection latency</p>
            <p class="tich-stat__value" data-metric="database.connection_latency_ms">
                {{ $initialMetrics['database']['connection_latency_ms'] !== null ? number_format($initialMetrics['database']['connection_latency_ms'], 2) : '—' }}
                <span class="tich-caption"> ms</span>
            </p>
        </article>
        <article class="tich-card tich-stat{{ $flagClass('database.failure_rate_pct') }}" data-flag-metric="database.failure_rate_pct">
            <p class="tich-caption">Query failure rate</p>
            <p class="tich-stat__value" data-metric="database.failure_rate_pct">{{ number_format($initialMetrics['database']['failure_rate_pct'], 2) }}<span class="tich-caption"> %</span></p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Queries (window)</p>
            <p class="tich-stat__value" data-metric="database.queries">{{ number_format($initialMetrics['database']['queries']) }}</p>
        </article>
        <article class="tich-card tich-stat{{ $flagClass('database.cache_hit_rate_pct') }}" data-flag-metric="database.cache_hit_rate_pct">
            <p class="tich-caption">Cache hit rate</p>
            <p class="tich-stat__value" data-metric="database.cache_hit_rate_pct">
                {{ $initialMetrics['database']['cache_hit_rate_pct'] !== null ? number_format($initialMetrics['database']['cache_hit_rate_pct'], 2).' %' : '—' }}
            </p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Cache probe</p>
            <p class="tich-stat__value" data-metric="database.cache_probe_ms">
                {{ $initialMetrics['database']['cache_probe_ms'] !== null ? number_format($initialMetrics['database']['cache_probe_ms'], 2) : '—' }}
                <span class="tich-caption"> ms</span>
            </p>
        </article>
        <article class="tich-card tich-stat{{ $flagClass('database.connection_ok') }}" data-flag-metric="database.connection_ok">
            <p class="tich-caption">DB connection</p>
            <p class="tich-stat__value" data-metric-text="database.connection_ok">
                {{ $initialMetrics['database']['connection_ok'] ? 'Healthy' : 'Down' }}
            </p>
        </article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-4">
        <article class="tich-card{{ $flagClass('database.cache_ok') }}{{ $flagClass('database.redis_ok') }}" data-flag-metric="database.cache_ok">
            <h3 class="tich-h4" style="margin-top:0;">Cache &amp; Redis</h3>
            <dl class="tich-dl">
                <dt>Cache driver</dt>
                <dd data-metric-text="database.cache_driver">{{ $initialMetrics['database']['cache_driver'] }}</dd>
                <dt>Cache store</dt>
                <dd data-metric-text="database.cache_ok">{{ $initialMetrics['database']['cache_ok'] ? 'Reachable' : 'Unreachable' }}</dd>
                <dt>Cache hits / misses</dt>
                <dd><span data-metric="database.cache_hits">{{ $initialMetrics['database']['cache_hits'] }}</span> / <span data-metric="database.cache_misses">{{ $initialMetrics['database']['cache_misses'] }}</span></dd>
                <dt>Redis</dt>
                <dd data-metric-text="database.redis_display">
                    @if ($initialMetrics['database']['redis_ok'] === null)
                        Not configured / not probed
                    @elseif ($initialMetrics['database']['redis_ok'])
                        Reachable ({{ number_format($initialMetrics['database']['redis_latency_ms'] ?? 0, 2) }} ms)
                    @else
                        Unreachable
                    @endif
                </dd>
            </dl>
        </article>
        <article class="tich-card">
            <h3 class="tich-h4" style="margin-top:0;">Runtime</h3>
            <dl class="tich-dl">
                <dt>PHP</dt>
                <dd data-metric-text="host.php_version">{{ $host['php_version'] ?? '' }}</dd>
                <dt>Laravel</dt>
                <dd data-metric-text="host.laravel_version">{{ $host['laravel_version'] ?? '' }}</dd>
                <dt>SAPI</dt>
                <dd data-metric-text="host.sapi">{{ $host['sapi'] ?? '—' }}</dd>
                <dt>Server software</dt>
                <dd data-metric-text="host.server_software">{{ $host['server_software'] ?? '—' }}</dd>
            </dl>
        </article>
    </div>

    <h2 class="tich-h3 tich-mt-8">Host &amp; hosting resources</h2>
    <div class="tich-grid tich-grid--4 tich-mt-4">
        <article class="tich-card tich-stat{{ $flagClass('host.ram.used_pct') }}" data-flag-metric="host.ram.used_pct">
            <p class="tich-caption">RAM used</p>
            <p class="tich-stat__value" data-metric="host.ram.used_pct">{{ $host['ram']['used_pct'] ?? '—' }}<span class="tich-caption"> %</span></p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">RAM total</p>
            <p class="tich-stat__value"><span data-metric="host.ram.total_mb">{{ $host['ram']['total_mb'] ?? '—' }}</span><span class="tich-caption"> MB</span></p>
        </article>
        <article class="tich-card tich-stat{{ $flagClass('host.disk_used_pct') }}" data-flag-metric="host.disk_used_pct">
            <p class="tich-caption">Disk used</p>
            <p class="tich-stat__value" data-metric="host.disk_used_pct">{{ $host['disk_used_pct'] ?? '—' }}<span class="tich-caption"> %</span></p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Host CPU</p>
            <p class="tich-stat__value" data-metric="host.cpu.load_pct">{{ $host['cpu']['load_pct'] ?? '—' }}<span class="tich-caption"> %</span></p>
        </article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-4">
        <article class="tich-card">
            <h3 class="tich-h4" style="margin-top:0;">CPU</h3>
            <dl class="tich-dl">
                <dt>Model</dt>
                <dd data-metric-text="host.cpu.model">{{ $host['cpu']['model'] ?? '—' }}</dd>
                <dt>Physical cores</dt>
                <dd data-metric="host.cpu.cores_physical">{{ $host['cpu']['cores_physical'] ?? '—' }}</dd>
                <dt>Logical processors</dt>
                <dd data-metric="host.cpu.cores_logical">{{ $host['cpu']['cores_logical'] ?? '—' }}</dd>
                <dt>Load</dt>
                <dd><span data-metric="host.cpu.load_pct">{{ $host['cpu']['load_pct'] ?? '—' }}</span>%</dd>
            </dl>
        </article>
        <article class="tich-card{{ $flagClass('host.ram.used_pct') }}" data-flag-metric="host.ram.used_pct">
            <h3 class="tich-h4" style="margin-top:0;">Memory (host RAM)</h3>
            <dl class="tich-dl">
                <dt>Total</dt>
                <dd><span data-metric="host.ram.total_mb">{{ $host['ram']['total_mb'] ?? '—' }}</span> MB</dd>
                <dt>Used</dt>
                <dd><span data-metric="host.ram.used_mb">{{ $host['ram']['used_mb'] ?? '—' }}</span> MB</dd>
                <dt>Free</dt>
                <dd><span data-metric="host.ram.free_mb">{{ $host['ram']['free_mb'] ?? '—' }}</span> MB</dd>
                <dt>Used %</dt>
                <dd><span data-metric="host.ram.used_pct">{{ $host['ram']['used_pct'] ?? '—' }}</span>%</dd>
            </dl>
        </article>
        <article class="tich-card{{ $flagClass('host.disk_used_pct') }}" data-flag-metric="host.disk_used_pct">
            <h3 class="tich-h4" style="margin-top:0;">Disk</h3>
            <dl class="tich-dl">
                <dt>Path</dt>
                <dd data-metric-text="host.disk_path">{{ $host['disk_path'] ?? '—' }}</dd>
                <dt>Total</dt>
                <dd><span data-metric="host.disk_total_gb">{{ $host['disk_total_gb'] ?? '—' }}</span> GB</dd>
                <dt>Free</dt>
                <dd><span data-metric="host.disk_free_gb">{{ $host['disk_free_gb'] ?? '—' }}</span> GB</dd>
                <dt>Used</dt>
                <dd><span data-metric="host.disk_used_pct">{{ $host['disk_used_pct'] ?? '—' }}</span>%</dd>
            </dl>
        </article>
        <article class="tich-card">
            <h3 class="tich-h4" style="margin-top:0;">Machine</h3>
            <dl class="tich-dl">
                <dt>Hostname</dt>
                <dd data-metric-text="host.hostname">{{ $host['hostname'] ?? '—' }}</dd>
                <dt>OS</dt>
                <dd data-metric-text="host.os_version">{{ $host['os_version'] ?? '—' }}</dd>
                <dt>Architecture</dt>
                <dd data-metric-text="host.architecture">{{ $host['architecture'] ?? '—' }}</dd>
                <dt>Uptime</dt>
                <dd data-metric-text="host.uptime_human">{{ $host['uptime_human'] ?? '—' }}</dd>
            </dl>
        </article>
    </div>
@endsection

@section('scripts')
    @parent
    <style>
        .tich-card.is-flagged--warning {
            border-color: var(--tich-status-warning-border);
            box-shadow: inset 3px 0 0 var(--tich-status-warning-border);
        }
        .tich-card.is-flagged--critical {
            border-color: var(--tich-status-danger-border);
            box-shadow: inset 3px 0 0 var(--tich-status-danger-border);
        }
        .tich-alert--warning {
            background-color: var(--tich-status-warning-bg);
            border-color: var(--tich-status-warning-border);
            color: var(--tich-status-warning-text);
        }
        .tich-status-badge.is-success {
            background: var(--tich-status-success-bg);
            color: var(--tich-status-success-text);
            border-color: var(--tich-status-success-border);
        }
        .tich-status-badge.is-danger {
            background: var(--tich-status-danger-bg);
            color: var(--tich-status-danger-text);
            border-color: var(--tich-status-danger-border);
        }
    </style>
    <script>
    (function () {
        var metricsUrl = @json($metricsUrl);
        var refreshBtn = document.getElementById('perf-refresh');
        var updatedAt = document.getElementById('perf-updated-at');
        var statusLine = document.getElementById('perf-status-line');
        var alertsWrap = document.getElementById('perf-alerts');
        var alertsOk = document.getElementById('perf-alerts-ok');
        var alertsBanner = document.getElementById('perf-alerts-banner');
        var alertsTitle = document.getElementById('perf-alerts-title');
        var alertsList = document.getElementById('perf-alerts-list');
        var healthBadge = document.getElementById('perf-health-badge');
        var timer = null;

        function getPath(obj, path) {
            return path.split('.').reduce(function (acc, key) {
                return acc == null ? null : acc[key];
            }, obj);
        }

        function formatNumber(value, digits) {
            if (value == null || value === '') return '—';
            var n = Number(value);
            if (!isFinite(n)) return String(value);
            return n.toLocaleString(undefined, {
                minimumFractionDigits: digits,
                maximumFractionDigits: digits
            });
        }

        function applyFlags(flags) {
            flags = flags || {};
            document.querySelectorAll('[data-flag-metric]').forEach(function (el) {
                el.classList.remove('is-flagged', 'is-flagged--critical', 'is-flagged--warning');
                var metric = el.getAttribute('data-flag-metric');
                var sev = flags[metric];
                if (sev === 'critical' || sev === 'warning') {
                    el.classList.add('is-flagged', 'is-flagged--' + sev);
                }
            });
        }

        function renderAlerts(data) {
            var summary = data.alert_summary || {};
            var alerts = data.alerts || [];
            var highest = summary.highest || 'ok';

            if (healthBadge) {
                healthBadge.classList.remove('is-danger', 'is-warning', 'is-success');
                if (summary.needs_action) {
                    healthBadge.classList.add(highest === 'critical' ? 'is-danger' : 'is-warning');
                    healthBadge.textContent = 'Needs action (' + (summary.count || alerts.length) + ')';
                } else {
                    healthBadge.classList.add('is-success');
                    healthBadge.textContent = 'Healthy';
                }
            }

            if (summary.needs_action) {
                if (alertsWrap) alertsWrap.hidden = false;
                if (alertsOk) alertsOk.hidden = true;
                if (alertsBanner) {
                    alertsBanner.classList.remove('tich-alert--error', 'tich-alert--warning');
                    alertsBanner.classList.add(highest === 'critical' ? 'tich-alert--error' : 'tich-alert--warning');
                }
                if (alertsTitle) {
                    if ((summary.critical || 0) > 0) {
                        alertsTitle.textContent = summary.critical + ' critical / ' + (summary.warning || 0) + ' warning — action needed';
                    } else {
                        alertsTitle.textContent = (summary.warning || 0) + ' warning(s) — review recommended';
                    }
                }
                if (alertsList) {
                    alertsList.innerHTML = alerts.map(function (alert) {
                        var sev = alert.severity || 'warning';
                        var badgeClass = sev === 'critical' ? 'is-danger' : 'is-warning';
                        return '<li><span class="tich-status-badge ' + badgeClass + '">' + String(sev).toUpperCase() +
                            '</span> <strong>' + (alert.title || '') + '</strong> — ' + (alert.message || '') + '</li>';
                    }).join('');
                }
            } else {
                if (alertsWrap) alertsWrap.hidden = true;
                if (alertsOk) alertsOk.hidden = false;
            }

            applyFlags(data.metric_flags || {});
        }

        function render(data) {
            document.querySelectorAll('[data-metric]').forEach(function (el) {
                var path = el.getAttribute('data-metric');
                var value = getPath(data, path);
                if (path === 'database.cache_hit_rate_pct') {
                    el.textContent = value == null ? '—' : (formatNumber(value, 2) + ' %');
                    return;
                }
                if (path === 'database.connection_latency_ms' || path === 'database.cache_probe_ms') {
                    el.innerHTML = (value == null ? '—' : formatNumber(value, 2)) + ' <span class="tich-caption"> ms</span>';
                    return;
                }
                if (path === 'host.cpu.load_pct' || path === 'host.ram.used_pct' || path === 'host.disk_used_pct' || path === 'server.memory.used_pct') {
                    el.innerHTML = (value == null ? '—' : formatNumber(value, path.indexOf('disk') !== -1 || path.indexOf('memory') !== -1 ? 1 : 1)) +
                        (el.querySelector('.tich-caption') || path.indexOf('pct') !== -1 ? '<span class="tich-caption"> %</span>' : '');
                    if (path === 'host.cpu.load_pct' || path === 'host.ram.used_pct' || path === 'host.disk_used_pct') {
                        if (el.classList.contains('tich-stat__value') || el.parentElement && el.parentElement.classList.contains('tich-stat__value')) {
                            // already handled
                        }
                    }
                    if (el.classList.contains('tich-stat__value')) {
                        el.innerHTML = (value == null ? '—' : formatNumber(value, 1)) + ' <span class="tich-caption"> %</span>';
                    } else {
                        el.textContent = value == null ? '—' : formatNumber(value, 1);
                    }
                    return;
                }
                if (path.slice(-3) === '_ms' || path.indexOf('_ms') !== -1) {
                    el.innerHTML = formatNumber(value, path.indexOf('avg_query') !== -1 || path.indexOf('query_p95') !== -1 ? 2 : 1) + ' <span class="tich-caption"> ms</span>';
                    return;
                }
                if (path.indexOf('_pct') !== -1) {
                    el.innerHTML = formatNumber(value, 2) + ' <span class="tich-caption"> %</span>';
                    return;
                }
                if (path === 'server.rps') {
                    el.textContent = formatNumber(value, 3);
                    return;
                }
                if (path === 'server.cpu.display') {
                    var load = getPath(data, 'server.cpu.load');
                    el.textContent = (load && load.length) ? load.join(' / ') : '—';
                    return;
                }
                if (typeof value === 'number') {
                    el.textContent = formatNumber(value, Number.isInteger(value) ? 0 : 1);
                    return;
                }
                el.textContent = value == null ? '—' : String(value);
            });

            document.querySelectorAll('[data-metric-text]').forEach(function (el) {
                var path = el.getAttribute('data-metric-text');
                if (path === 'database.connection_ok') {
                    el.textContent = getPath(data, 'database.connection_ok') ? 'Healthy' : 'Down';
                    return;
                }
                if (path === 'database.cache_ok') {
                    el.textContent = getPath(data, 'database.cache_ok') ? 'Reachable' : 'Unreachable';
                    return;
                }
                if (path === 'database.redis_display') {
                    var ok = getPath(data, 'database.redis_ok');
                    if (ok === null || typeof ok === 'undefined') {
                        el.textContent = 'Not configured / not probed';
                    } else if (ok) {
                        el.textContent = 'Reachable (' + formatNumber(getPath(data, 'database.redis_latency_ms'), 2) + ' ms)';
                    } else {
                        el.textContent = 'Unreachable';
                    }
                    return;
                }
                if (path === 'server.cpu.note') {
                    el.textContent = getPath(data, 'server.cpu.note') || '';
                    return;
                }
                var value = getPath(data, path);
                el.textContent = value == null || value === '' ? '—' : String(value);
            });

            renderAlerts(data);

            if (updatedAt && data.collected_at) {
                var d = new Date(data.collected_at);
                updatedAt.textContent = 'Updated ' + d.toLocaleTimeString();
            }
        }

        function fetchMetrics() {
            if (statusLine) statusLine.textContent = 'Refreshing metrics…';
            fetch(metricsUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
                .then(function (res) {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(function (data) {
                    render(data);
                    if (statusLine) statusLine.textContent = 'Live metrics refresh every 5 seconds.';
                })
                .catch(function () {
                    if (statusLine) statusLine.textContent = 'Could not refresh metrics. Will retry…';
                });
        }

        if (refreshBtn) {
            refreshBtn.addEventListener('click', fetchMetrics);
        }

        timer = window.setInterval(fetchMetrics, 5000);
        window.addEventListener('beforeunload', function () {
            if (timer) window.clearInterval(timer);
        });
    })();
    </script>
@endsection
