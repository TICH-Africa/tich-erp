<?php

namespace App\Services\Ict;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class PlatformPerformanceService
{
    public const WINDOW_MINUTES = 5;

    public const SAMPLE_LIMIT = 240;

    public const CACHE_PREFIX = 'platform_perf:';

    private bool $recording = false;

    public function recordRequest(int $durationMs, int $statusCode, float $memoryMb): void
    {
        if ($this->recording) {
            return;
        }

        $this->recording = true;
        try {
            $bucket = $this->bucketKey();
            $data = $this->readBucket($bucket);

            $data['requests'] = (int) ($data['requests'] ?? 0) + 1;
            $data['total_ms'] = (int) ($data['total_ms'] ?? 0) + max(0, $durationMs);
            $data['memory_peak_mb'] = max((float) ($data['memory_peak_mb'] ?? 0), $memoryMb);

            $class = (int) floor($statusCode / 100);
            if ($class === 2) {
                $data['status_2xx'] = (int) ($data['status_2xx'] ?? 0) + 1;
            } elseif ($class === 4) {
                $data['status_4xx'] = (int) ($data['status_4xx'] ?? 0) + 1;
            } elseif ($class === 5) {
                $data['status_5xx'] = (int) ($data['status_5xx'] ?? 0) + 1;
            } else {
                $data['status_other'] = (int) ($data['status_other'] ?? 0) + 1;
            }

            $samples = $data['durations'] ?? [];
            $samples[] = max(0, $durationMs);
            if (count($samples) > self::SAMPLE_LIMIT) {
                $samples = array_slice($samples, -self::SAMPLE_LIMIT);
            }
            $data['durations'] = $samples;

            $this->writeBucket($bucket, $data);
        } finally {
            $this->recording = false;
        }
    }

    public function recordQuery(float $durationMs, bool $failed = false): void
    {
        if ($this->recording) {
            return;
        }

        $this->recording = true;
        try {
            $bucket = $this->bucketKey();
            $data = $this->readBucket($bucket);

            $data['queries'] = (int) ($data['queries'] ?? 0) + 1;
            $data['query_total_ms'] = (float) ($data['query_total_ms'] ?? 0) + max(0, $durationMs);
            if ($failed) {
                $data['query_failures'] = (int) ($data['query_failures'] ?? 0) + 1;
            }

            $samples = $data['query_durations'] ?? [];
            $samples[] = round(max(0, $durationMs), 2);
            if (count($samples) > self::SAMPLE_LIMIT) {
                $samples = array_slice($samples, -self::SAMPLE_LIMIT);
            }
            $data['query_durations'] = $samples;

            $this->writeBucket($bucket, $data);
        } finally {
            $this->recording = false;
        }
    }

    public function recordCacheAccess(bool $hit): void
    {
        if ($this->recording) {
            return;
        }

        $this->recording = true;
        try {
            $bucket = $this->bucketKey();
            $data = $this->readBucket($bucket);
            if ($hit) {
                $data['cache_hits'] = (int) ($data['cache_hits'] ?? 0) + 1;
            } else {
                $data['cache_misses'] = (int) ($data['cache_misses'] ?? 0) + 1;
            }
            $this->writeBucket($bucket, $data);
        } finally {
            $this->recording = false;
        }
    }

    public function recordQueryFailure(): void
    {
        $this->recordQuery(0, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $aggregated = $this->aggregateWindow();
        $probes = $this->liveProbes();
        $host = $this->hostSnapshot($probes);
        $cpu = $this->cpuSnapshot($host);

        $requests = (int) ($aggregated['requests'] ?? 0);
        $totalMs = (int) ($aggregated['total_ms'] ?? 0);
        $status2 = (int) ($aggregated['status_2xx'] ?? 0);
        $status4 = (int) ($aggregated['status_4xx'] ?? 0);
        $status5 = (int) ($aggregated['status_5xx'] ?? 0);
        $queries = (int) ($aggregated['queries'] ?? 0);
        $queryTotalMs = (float) ($aggregated['query_total_ms'] ?? 0);
        $queryFailures = (int) ($aggregated['query_failures'] ?? 0);
        $cacheHits = (int) ($aggregated['cache_hits'] ?? 0);
        $cacheMisses = (int) ($aggregated['cache_misses'] ?? 0);
        $cacheTotal = $cacheHits + $cacheMisses;

        $windowSeconds = max(1, self::WINDOW_MINUTES * 60);
        $phpLimitMb = $this->phpMemoryLimitMb();
        $phpPeakMb = round(max(memory_get_peak_usage(true) / 1048576, (float) ($aggregated['memory_peak_mb'] ?? 0)), 2);

        $payload = [
            'collected_at' => now()->timezone(config('app.timezone', 'Africa/Nairobi'))->toIso8601String(),
            'window_minutes' => self::WINDOW_MINUTES,
            'server' => [
                'ttfb_ms' => $this->percentile($aggregated['durations'] ?? [], 50),
                'ttfb_p95_ms' => $this->percentile($aggregated['durations'] ?? [], 95),
                'avg_response_ms' => $requests > 0 ? round($totalMs / $requests, 1) : 0,
                'error_rate_4xx_pct' => $requests > 0 ? round(($status4 / $requests) * 100, 2) : 0,
                'error_rate_5xx_pct' => $requests > 0 ? round(($status5 / $requests) * 100, 2) : 0,
                'success_rate_2xx_pct' => $requests > 0 ? round(($status2 / $requests) * 100, 2) : 0,
                'status_counts' => [
                    '2xx' => $status2,
                    '4xx' => $status4,
                    '5xx' => $status5,
                    'other' => (int) ($aggregated['status_other'] ?? 0),
                ],
                'requests' => $requests,
                'rps' => round($requests / $windowSeconds, 3),
                'memory' => [
                    'current_mb' => round(memory_get_usage(true) / 1048576, 2),
                    'peak_mb' => $phpPeakMb,
                    'limit' => ini_get('memory_limit') ?: 'unknown',
                    'limit_mb' => $phpLimitMb,
                    'used_pct' => $phpLimitMb ? round(($phpPeakMb / $phpLimitMb) * 100, 1) : null,
                ],
                'cpu' => $cpu,
            ],
            'database' => [
                'avg_query_ms' => $queries > 0 ? round($queryTotalMs / $queries, 2) : 0,
                'query_p95_ms' => $this->percentile($aggregated['query_durations'] ?? [], 95),
                'queries' => $queries,
                'failure_rate_pct' => $queries > 0 ? round(($queryFailures / $queries) * 100, 2) : 0,
                'failures' => $queryFailures,
                'connection_latency_ms' => $probes['db_latency_ms'],
                'connection_ok' => $probes['db_ok'],
                'cache_driver' => config('cache.default'),
                'cache_hit_rate_pct' => $cacheTotal > 0 ? round(($cacheHits / $cacheTotal) * 100, 2) : null,
                'cache_hits' => $cacheHits,
                'cache_misses' => $cacheMisses,
                'cache_probe_ms' => $probes['cache_latency_ms'],
                'cache_ok' => $probes['cache_ok'],
                'redis_ok' => $probes['redis_ok'],
                'redis_latency_ms' => $probes['redis_latency_ms'],
            ],
            'host' => $host,
        ];

        $alerts = $this->buildAlerts($payload);
        $criticalCount = count(array_filter($alerts, fn ($a) => ($a['severity'] ?? '') === 'critical'));
        $warningCount = count(array_filter($alerts, fn ($a) => ($a['severity'] ?? '') === 'warning'));
        $payload['alerts'] = $alerts;
        $payload['alert_summary'] = [
            'count' => count($alerts),
            'critical' => $criticalCount,
            'warning' => $warningCount,
            'needs_action' => count($alerts) > 0,
            'highest' => $criticalCount > 0 ? 'critical' : ($warningCount > 0 ? 'warning' : 'ok'),
        ];
        $payload['metric_flags'] = $this->metricFlagsFromAlerts($alerts);

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function aggregateWindow(): array
    {
        $merged = [
            'requests' => 0,
            'total_ms' => 0,
            'status_2xx' => 0,
            'status_4xx' => 0,
            'status_5xx' => 0,
            'status_other' => 0,
            'memory_peak_mb' => 0.0,
            'queries' => 0,
            'query_total_ms' => 0.0,
            'query_failures' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
            'durations' => [],
            'query_durations' => [],
        ];

        for ($i = 0; $i < self::WINDOW_MINUTES; $i++) {
            $key = $this->bucketKey(now()->subMinutes($i));
            $bucket = $this->readBucket($key);
            if ($bucket === []) {
                continue;
            }

            foreach (['requests', 'total_ms', 'status_2xx', 'status_4xx', 'status_5xx', 'status_other', 'queries', 'query_failures', 'cache_hits', 'cache_misses'] as $field) {
                $merged[$field] += (int) ($bucket[$field] ?? 0);
            }
            $merged['query_total_ms'] += (float) ($bucket['query_total_ms'] ?? 0);
            $merged['memory_peak_mb'] = max($merged['memory_peak_mb'], (float) ($bucket['memory_peak_mb'] ?? 0));
            $merged['durations'] = array_merge($merged['durations'], $bucket['durations'] ?? []);
            $merged['query_durations'] = array_merge($merged['query_durations'], $bucket['query_durations'] ?? []);
        }

        if (count($merged['durations']) > self::SAMPLE_LIMIT) {
            $merged['durations'] = array_slice($merged['durations'], -self::SAMPLE_LIMIT);
        }
        if (count($merged['query_durations']) > self::SAMPLE_LIMIT) {
            $merged['query_durations'] = array_slice($merged['query_durations'], -self::SAMPLE_LIMIT);
        }

        return $merged;
    }

    /**
     * @return array<string, mixed>
     */
    private function liveProbes(): array
    {
        $dbOk = false;
        $dbLatency = null;
        try {
            $start = hrtime(true);
            DB::select('select 1');
            $dbLatency = round((hrtime(true) - $start) / 1e6, 2);
            $dbOk = true;
        } catch (Throwable) {
            $dbOk = false;
        }

        $cacheOk = false;
        $cacheLatency = null;
        try {
            $probeKey = self::CACHE_PREFIX.'probe';
            $start = hrtime(true);
            $existing = Cache::get($probeKey);
            if ($existing === null) {
                $this->recordCacheAccess(false);
                Cache::put($probeKey, now()->timestamp, 60);
            } else {
                $this->recordCacheAccess(true);
            }
            $cacheLatency = round((hrtime(true) - $start) / 1e6, 2);
            $cacheOk = true;
        } catch (Throwable) {
            $cacheOk = false;
        }

        $redisOk = null;
        $redisLatency = null;
        if (config('cache.default') === 'redis' || config('session.driver') === 'redis') {
            try {
                $start = hrtime(true);
                Redis::connection()->ping();
                $redisLatency = round((hrtime(true) - $start) / 1e6, 2);
                $redisOk = true;
            } catch (Throwable) {
                $redisOk = false;
            }
        }

        $path = storage_path();
        $free = @disk_free_space($path);
        $total = @disk_total_space($path);
        $diskFreeGb = $free !== false ? round($free / 1073741824, 2) : null;
        $diskTotalGb = $total !== false ? round($total / 1073741824, 2) : null;
        $diskUsedPct = ($free !== false && $total !== false && $total > 0)
            ? round((($total - $free) / $total) * 100, 1)
            : null;

        return [
            'db_ok' => $dbOk,
            'db_latency_ms' => $dbLatency,
            'cache_ok' => $cacheOk,
            'cache_latency_ms' => $cacheLatency,
            'redis_ok' => $redisOk,
            'redis_latency_ms' => $redisLatency,
            'disk_free_gb' => $diskFreeGb,
            'disk_total_gb' => $diskTotalGb,
            'disk_used_pct' => $diskUsedPct,
        ];
    }

    /**
     * @param  array<string, mixed>  $probes
     * @return array<string, mixed>
     */
    private function hostSnapshot(array $probes): array
    {
        $resources = $this->hostResources();

        return [
            'hostname' => gethostname() ?: php_uname('n'),
            'os' => PHP_OS_FAMILY,
            'os_version' => php_uname('s').' '.php_uname('r'),
            'architecture' => php_uname('m'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'sapi' => PHP_SAPI,
            'timezone' => config('app.timezone', date_default_timezone_get()),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? null,
            'disk_free_gb' => $probes['disk_free_gb'],
            'disk_total_gb' => $probes['disk_total_gb'],
            'disk_used_pct' => $probes['disk_used_pct'],
            'disk_path' => storage_path(),
            'cpu' => [
                'model' => $resources['cpu_model'] ?? null,
                'cores_physical' => $resources['cpu_cores_physical'] ?? null,
                'cores_logical' => $resources['cpu_cores_logical'] ?? (int) (getenv('NUMBER_OF_PROCESSORS') ?: 0) ?: null,
                'load_pct' => $resources['cpu_load_pct'] ?? null,
            ],
            'ram' => [
                'total_mb' => $resources['ram_total_mb'] ?? null,
                'free_mb' => $resources['ram_free_mb'] ?? null,
                'used_mb' => $resources['ram_used_mb'] ?? null,
                'used_pct' => $resources['ram_used_pct'] ?? null,
            ],
            'uptime_seconds' => $resources['uptime_seconds'] ?? null,
            'uptime_human' => isset($resources['uptime_seconds'])
                ? $this->formatUptime((int) $resources['uptime_seconds'])
                : null,
            'source' => $resources['source'] ?? 'limited',
        ];
    }

    /**
     * Cached host hardware/OS readings (RAM, CPU model, uptime).
     *
     * @return array<string, mixed>
     */
    private function hostResources(): array
    {
        $cached = $this->metricsStore()->get(self::CACHE_PREFIX.'host_resources');
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        $data = PHP_OS_FAMILY === 'Windows'
            ? $this->hostResourcesWindows()
            : $this->hostResourcesUnix();

        if ($data !== []) {
            $this->metricsStore()->put(self::CACHE_PREFIX.'host_resources', $data, 30);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function hostResourcesWindows(): array
    {
        $data = ['source' => 'windows'];

        $osJson = $this->shellCapture('powershell -NoProfile -Command "$os=Get-CimInstance Win32_OperatingSystem; @{TotalVisibleMemorySize=$os.TotalVisibleMemorySize;FreePhysicalMemory=$os.FreePhysicalMemory;UptimeSeconds=[int]((Get-Date)-$os.LastBootUpTime).TotalSeconds} | ConvertTo-Json -Compress"');
        if ($osJson !== null) {
            $os = json_decode(trim($osJson), true);
            if (is_array($os)) {
                $totalKb = isset($os['TotalVisibleMemorySize']) ? (float) $os['TotalVisibleMemorySize'] : null;
                $freeKb = isset($os['FreePhysicalMemory']) ? (float) $os['FreePhysicalMemory'] : null;
                if ($totalKb && $totalKb > 0) {
                    $data['ram_total_mb'] = round($totalKb / 1024, 1);
                    if ($freeKb !== null) {
                        $data['ram_free_mb'] = round($freeKb / 1024, 1);
                        $data['ram_used_mb'] = round(($totalKb - $freeKb) / 1024, 1);
                        $data['ram_used_pct'] = round((($totalKb - $freeKb) / $totalKb) * 100, 1);
                    }
                }
                if (isset($os['UptimeSeconds']) && is_numeric($os['UptimeSeconds'])) {
                    $data['uptime_seconds'] = max(0, (int) $os['UptimeSeconds']);
                }
            }
        }

        $cpuJson = $this->shellCapture('powershell -NoProfile -Command "Get-CimInstance Win32_Processor | Select-Object Name,NumberOfCores,NumberOfLogicalProcessors,LoadPercentage | ConvertTo-Json -Compress"');
        if ($cpuJson !== null) {
            $cpu = json_decode(trim($cpuJson), true);
            // Multiple sockets return a list.
            if (is_array($cpu) && array_is_list($cpu) && isset($cpu[0]) && is_array($cpu[0])) {
                $coresPhysical = 0;
                $coresLogical = 0;
                $loadSum = 0.0;
                $loadCount = 0;
                $names = [];
                foreach ($cpu as $row) {
                    $coresPhysical += (int) ($row['NumberOfCores'] ?? 0);
                    $coresLogical += (int) ($row['NumberOfLogicalProcessors'] ?? 0);
                    if (isset($row['LoadPercentage']) && $row['LoadPercentage'] !== null && $row['LoadPercentage'] !== '') {
                        $loadSum += (float) $row['LoadPercentage'];
                        $loadCount++;
                    }
                    if (! empty($row['Name'])) {
                        $names[] = trim((string) $row['Name']);
                    }
                }
                if ($names !== []) {
                    $data['cpu_model'] = implode(' + ', array_unique($names));
                }
                if ($coresPhysical > 0) {
                    $data['cpu_cores_physical'] = $coresPhysical;
                }
                if ($coresLogical > 0) {
                    $data['cpu_cores_logical'] = $coresLogical;
                }
                if ($loadCount > 0) {
                    $data['cpu_load_pct'] = round($loadSum / $loadCount, 1);
                }
            } elseif (is_array($cpu)) {
                if (! empty($cpu['Name'])) {
                    $data['cpu_model'] = trim((string) $cpu['Name']);
                }
                if (isset($cpu['NumberOfCores'])) {
                    $data['cpu_cores_physical'] = (int) $cpu['NumberOfCores'];
                }
                if (isset($cpu['NumberOfLogicalProcessors'])) {
                    $data['cpu_cores_logical'] = (int) $cpu['NumberOfLogicalProcessors'];
                }
                if (isset($cpu['LoadPercentage']) && $cpu['LoadPercentage'] !== null && $cpu['LoadPercentage'] !== '') {
                    $data['cpu_load_pct'] = (float) $cpu['LoadPercentage'];
                }
            }
        }

        // Legacy WMIC fallback when PowerShell CIM is unavailable.
        if (! isset($data['ram_total_mb'])) {
            $os = $this->shellCapture('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize,LastBootUpTime /format:value');
            if ($os !== null) {
                $map = $this->parseWmicValues($os);
                $totalKb = isset($map['TotalVisibleMemorySize']) ? (float) $map['TotalVisibleMemorySize'] : null;
                $freeKb = isset($map['FreePhysicalMemory']) ? (float) $map['FreePhysicalMemory'] : null;
                if ($totalKb && $totalKb > 0) {
                    $data['ram_total_mb'] = round($totalKb / 1024, 1);
                    if ($freeKb !== null) {
                        $data['ram_free_mb'] = round($freeKb / 1024, 1);
                        $data['ram_used_mb'] = round(($totalKb - $freeKb) / 1024, 1);
                        $data['ram_used_pct'] = round((($totalKb - $freeKb) / $totalKb) * 100, 1);
                    }
                }
                if (! empty($map['LastBootUpTime'])) {
                    $boot = $this->parseWmicTimestamp($map['LastBootUpTime']);
                    if ($boot !== null) {
                        $data['uptime_seconds'] = max(0, time() - $boot);
                    }
                }
            }
        }

        if (! isset($data['cpu_model'])) {
            $cpu = $this->shellCapture('wmic cpu get Name,NumberOfCores,NumberOfLogicalProcessors,LoadPercentage /format:value');
            if ($cpu !== null) {
                $map = $this->parseWmicValues($cpu);
                if (! empty($map['Name'])) {
                    $data['cpu_model'] = trim($map['Name']);
                }
                if (isset($map['NumberOfCores'])) {
                    $data['cpu_cores_physical'] = (int) $map['NumberOfCores'];
                }
                if (isset($map['NumberOfLogicalProcessors'])) {
                    $data['cpu_cores_logical'] = (int) $map['NumberOfLogicalProcessors'];
                }
                if (isset($map['LoadPercentage']) && $map['LoadPercentage'] !== '') {
                    $data['cpu_load_pct'] = (float) $map['LoadPercentage'];
                }
            }
        }

        if (! isset($data['cpu_cores_logical'])) {
            $procs = (int) (getenv('NUMBER_OF_PROCESSORS') ?: 0);
            if ($procs > 0) {
                $data['cpu_cores_logical'] = $procs;
            }
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function hostResourcesUnix(): array
    {
        $data = ['source' => 'unix'];

        if (is_readable('/proc/meminfo')) {
            $meminfo = @file_get_contents('/proc/meminfo');
            if (is_string($meminfo)) {
                $total = $this->parseMeminfoKb($meminfo, 'MemTotal');
                $available = $this->parseMeminfoKb($meminfo, 'MemAvailable');
                if ($available === null) {
                    $free = $this->parseMeminfoKb($meminfo, 'MemFree');
                    $buffers = $this->parseMeminfoKb($meminfo, 'Buffers') ?? 0;
                    $cached = $this->parseMeminfoKb($meminfo, 'Cached') ?? 0;
                    $available = $free !== null ? $free + $buffers + $cached : null;
                }
                if ($total && $total > 0) {
                    $data['ram_total_mb'] = round($total / 1024, 1);
                    if ($available !== null) {
                        $data['ram_free_mb'] = round($available / 1024, 1);
                        $data['ram_used_mb'] = round(($total - $available) / 1024, 1);
                        $data['ram_used_pct'] = round((($total - $available) / $total) * 100, 1);
                    }
                }
            }
        }

        if (is_readable('/proc/cpuinfo')) {
            $cpuinfo = @file_get_contents('/proc/cpuinfo');
            if (is_string($cpuinfo)) {
                if (preg_match('/^model name\s*:\s*(.+)$/mi', $cpuinfo, $m)) {
                    $data['cpu_model'] = trim($m[1]);
                }
                $data['cpu_cores_logical'] = substr_count($cpuinfo, 'processor');
            }
        }

        if (is_readable('/proc/uptime')) {
            $uptime = @file_get_contents('/proc/uptime');
            if (is_string($uptime)) {
                $parts = preg_split('/\s+/', trim($uptime));
                if (! empty($parts[0])) {
                    $data['uptime_seconds'] = (int) floor((float) $parts[0]);
                }
            }
        }

        $nproc = $this->shellCapture('nproc 2>/dev/null');
        if ($nproc !== null && is_numeric(trim($nproc))) {
            $data['cpu_cores_logical'] = (int) trim($nproc);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $host
     * @return array{available: bool, load: list<float>|null, note: string, host_load_pct: float|null}
     */
    private function cpuSnapshot(array $host = []): array
    {
        $hostLoad = isset($host['cpu']['load_pct']) ? (float) $host['cpu']['load_pct'] : null;

        if (function_exists('sys_getloadavg')) {
            $load = @sys_getloadavg();
            if (is_array($load)) {
                return [
                    'available' => true,
                    'load' => array_map(fn ($v) => round((float) $v, 2), $load),
                    'host_load_pct' => $hostLoad,
                    'note' => '1 / 5 / 15 minute load averages'
                        .($hostLoad !== null ? '; host CPU '.$hostLoad.'%' : ''),
                ];
            }
        }

        if ($hostLoad !== null) {
            return [
                'available' => true,
                'load' => [$hostLoad],
                'host_load_pct' => $hostLoad,
                'note' => 'Host CPU utilization (OS)',
            ];
        }

        // Windows / XAMPP fallback: approximate busy ratio from getrusage deltas.
        $usage = getrusage();
        $now = microtime(true);
        $user = (($usage['ru_utime.tv_sec'] ?? 0) + (($usage['ru_utime.tv_usec'] ?? 0) / 1e6));
        $system = (($usage['ru_stime.tv_sec'] ?? 0) + (($usage['ru_stime.tv_usec'] ?? 0) / 1e6));
        $cpuTime = $user + $system;

        $prev = $this->metricsStore()->get(self::CACHE_PREFIX.'cpu_sample');
        $this->metricsStore()->put(self::CACHE_PREFIX.'cpu_sample', ['t' => $now, 'cpu' => $cpuTime], 120);

        if (! is_array($prev) || ! isset($prev['t'], $prev['cpu'])) {
            return [
                'available' => false,
                'load' => null,
                'host_load_pct' => null,
                'note' => 'Collecting CPU sample… refresh in a few seconds',
            ];
        }

        $elapsed = max(0.001, $now - (float) $prev['t']);
        $cpuDelta = max(0, $cpuTime - (float) $prev['cpu']);
        $busyPct = min(100, round(($cpuDelta / $elapsed) * 100, 1));

        return [
            'available' => true,
            'load' => [$busyPct],
            'host_load_pct' => null,
            'note' => 'Approx. PHP process CPU busy % since last sample (Windows-friendly)',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{id: string, severity: string, metric: string, title: string, message: string, value: mixed, threshold: mixed}>
     */
    private function buildAlerts(array $payload): array
    {
        $alerts = [];
        $server = $payload['server'] ?? [];
        $db = $payload['database'] ?? [];
        $host = $payload['host'] ?? [];
        $requests = (int) ($server['requests'] ?? 0);

        $push = function (
            string $id,
            string $severity,
            string $metric,
            string $title,
            string $message,
            mixed $value,
            mixed $threshold
        ) use (&$alerts): void {
            $alerts[] = compact('id', 'severity', 'metric', 'title', 'message', 'value', 'threshold');
        };

        if (! ($db['connection_ok'] ?? false)) {
            $push('db_down', 'critical', 'database.connection_ok', 'Database unreachable', 'Database probe failed. Check MariaDB/MySQL service and credentials.', false, true);
        }
        if (! ($db['cache_ok'] ?? false)) {
            $push('cache_down', 'critical', 'database.cache_ok', 'Cache unreachable', 'Default cache store probe failed.', false, true);
        }
        if (($db['redis_ok'] ?? null) === false) {
            $push('redis_down', 'critical', 'database.redis_ok', 'Redis unreachable', 'Redis is configured but did not respond to ping.', false, true);
        }

        if ($requests > 0) {
            $rate5 = (float) ($server['error_rate_5xx_pct'] ?? 0);
            if ($rate5 >= 5) {
                $push('http_5xx_critical', 'critical', 'server.error_rate_5xx_pct', 'High 5xx error rate', "{$rate5}% of requests returned server errors in the last window.", $rate5, 5);
            } elseif ($rate5 >= 1) {
                $push('http_5xx_warn', 'warning', 'server.error_rate_5xx_pct', 'Elevated 5xx error rate', "{$rate5}% of requests returned server errors.", $rate5, 1);
            }

            $rate4 = (float) ($server['error_rate_4xx_pct'] ?? 0);
            if ($rate4 >= 25) {
                $push('http_4xx_warn', 'warning', 'server.error_rate_4xx_pct', 'High 4xx error rate', "{$rate4}% client errors — check broken links or auth failures.", $rate4, 25);
            }

            $p95 = (float) ($server['ttfb_p95_ms'] ?? 0);
            if ($p95 >= 2000) {
                $push('ttfb_critical', 'critical', 'server.ttfb_p95_ms', 'TTFB critically slow', "p95 TTFB is {$p95} ms.", $p95, 2000);
            } elseif ($p95 >= 800) {
                $push('ttfb_warn', 'warning', 'server.ttfb_p95_ms', 'TTFB elevated', "p95 TTFB is {$p95} ms.", $p95, 800);
            }

            $avg = (float) ($server['avg_response_ms'] ?? 0);
            if ($avg >= 1500) {
                $push('response_critical', 'critical', 'server.avg_response_ms', 'Response time critically slow', "Average response is {$avg} ms.", $avg, 1500);
            } elseif ($avg >= 500) {
                $push('response_warn', 'warning', 'server.avg_response_ms', 'Response time elevated', "Average response is {$avg} ms.", $avg, 500);
            }
        }

        $queries = (int) ($db['queries'] ?? 0);
        if ($queries > 0) {
            $qFail = (float) ($db['failure_rate_pct'] ?? 0);
            if ($qFail >= 5) {
                $push('query_fail_critical', 'critical', 'database.failure_rate_pct', 'High query failure rate', "{$qFail}% of queries failed.", $qFail, 5);
            } elseif ($qFail >= 1) {
                $push('query_fail_warn', 'warning', 'database.failure_rate_pct', 'Elevated query failures', "{$qFail}% of queries failed.", $qFail, 1);
            }

            $qP95 = (float) ($db['query_p95_ms'] ?? 0);
            if ($qP95 >= 500) {
                $push('query_slow_critical', 'critical', 'database.query_p95_ms', 'Slow queries (critical)', "Query p95 is {$qP95} ms.", $qP95, 500);
            } elseif ($qP95 >= 200) {
                $push('query_slow_warn', 'warning', 'database.query_p95_ms', 'Slow queries', "Query p95 is {$qP95} ms.", $qP95, 200);
            }

            $qAvg = (float) ($db['avg_query_ms'] ?? 0);
            if ($qAvg >= 100) {
                $push('query_avg_warn', 'warning', 'database.avg_query_ms', 'Average query time high', "Average query time is {$qAvg} ms.", $qAvg, 100);
            }
        }

        $connMs = $db['connection_latency_ms'] ?? null;
        if ($connMs !== null && (float) $connMs >= 100) {
            $push('db_latency_warn', 'warning', 'database.connection_latency_ms', 'DB connection latency high', "Connection probe took {$connMs} ms.", $connMs, 100);
        }

        $cacheHit = $db['cache_hit_rate_pct'] ?? null;
        $cacheSamples = (int) ($db['cache_hits'] ?? 0) + (int) ($db['cache_misses'] ?? 0);
        if ($cacheHit !== null && $cacheSamples >= 10 && (float) $cacheHit < 50) {
            $push('cache_hit_warn', 'warning', 'database.cache_hit_rate_pct', 'Low cache hit rate', "Cache hit rate is {$cacheHit}%.", $cacheHit, 50);
        }

        $diskPct = $host['disk_used_pct'] ?? null;
        if ($diskPct !== null && (float) $diskPct >= 90) {
            $push('disk_critical', 'critical', 'host.disk_used_pct', 'Disk nearly full', "Disk is {$diskPct}% used.", $diskPct, 90);
        } elseif ($diskPct !== null && (float) $diskPct >= 80) {
            $push('disk_warn', 'warning', 'host.disk_used_pct', 'Disk space low', "Disk is {$diskPct}% used.", $diskPct, 80);
        }

        $ramPct = $host['ram']['used_pct'] ?? null;
        if ($ramPct !== null && (float) $ramPct >= 90) {
            $push('ram_critical', 'critical', 'host.ram.used_pct', 'Host RAM critically high', "Host RAM is {$ramPct}% used.", $ramPct, 90);
        } elseif ($ramPct !== null && (float) $ramPct >= 80) {
            $push('ram_warn', 'warning', 'host.ram.used_pct', 'Host RAM high', "Host RAM is {$ramPct}% used.", $ramPct, 80);
        }

        $phpMemPct = $server['memory']['used_pct'] ?? null;
        if ($phpMemPct !== null && (float) $phpMemPct >= 85) {
            $push('php_mem_warn', 'warning', 'server.memory.used_pct', 'PHP memory near limit', "Peak PHP memory is {$phpMemPct}% of memory_limit.", $phpMemPct, 85);
        }

        $cpuPct = $server['cpu']['host_load_pct'] ?? null;
        if ($cpuPct === null && ! empty($server['cpu']['load']) && count($server['cpu']['load']) === 1) {
            $cpuPct = (float) $server['cpu']['load'][0];
        }
        if ($cpuPct !== null && (float) $cpuPct >= 90) {
            $push('cpu_critical', 'critical', 'server.cpu.display', 'CPU critically high', "CPU utilization is about {$cpuPct}%.", $cpuPct, 90);
        } elseif ($cpuPct !== null && (float) $cpuPct >= 75) {
            $push('cpu_warn', 'warning', 'server.cpu.display', 'CPU elevated', "CPU utilization is about {$cpuPct}%.", $cpuPct, 75);
        }

        usort($alerts, function ($a, $b) {
            $rank = ['critical' => 0, 'warning' => 1];

            return ($rank[$a['severity']] ?? 9) <=> ($rank[$b['severity']] ?? 9);
        });

        return $alerts;
    }

    /**
     * @param  list<array{metric: string, severity: string}>  $alerts
     * @return array<string, string>
     */
    private function metricFlagsFromAlerts(array $alerts): array
    {
        $flags = [];
        foreach ($alerts as $alert) {
            $metric = $alert['metric'];
            $severity = $alert['severity'];
            if (! isset($flags[$metric]) || $severity === 'critical') {
                $flags[$metric] = $severity;
            }
        }

        return $flags;
    }

    private function phpMemoryLimitMb(): ?float
    {
        $limit = (string) (ini_get('memory_limit') ?: '');
        if ($limit === '' || $limit === '-1') {
            return null;
        }

        if (preg_match('/^\s*(\d+)\s*([KMG])?\s*$/i', $limit, $m)) {
            $n = (float) $m[1];
            $unit = strtoupper($m[2] ?? '');

            return match ($unit) {
                'G' => $n * 1024,
                'K' => round($n / 1024, 2),
                default => $n,
            };
        }

        return null;
    }

    private function formatUptime(int $seconds): string
    {
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $mins = intdiv($seconds % 3600, 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = $days.'d';
        }
        if ($hours > 0 || $days > 0) {
            $parts[] = $hours.'h';
        }
        $parts[] = $mins.'m';

        return implode(' ', $parts);
    }

    private function shellCapture(string $command): ?string
    {
        if (! function_exists('shell_exec') || in_array('shell_exec', array_map('trim', explode(',', (string) ini_get('disable_functions'))), true)) {
            return null;
        }

        try {
            $out = @shell_exec($command);
            if (! is_string($out) || trim($out) === '') {
                return null;
            }

            return $out;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, string>
     */
    private function parseWmicValues(string $raw): array
    {
        $map = [];
        foreach (preg_split("/\r\n|\n|\r/", $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || ! str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            $key = trim($key);
            if ($key !== '') {
                $map[$key] = trim($value);
            }
        }

        return $map;
    }

    private function parseWmicTimestamp(string $value): ?int
    {
        // e.g. 20260317123045.000000+180
        if (! preg_match('/^(\d{14})/', $value, $m)) {
            return null;
        }

        $dt = \DateTimeImmutable::createFromFormat('YmdHis', $m[1]);

        return $dt ? $dt->getTimestamp() : null;
    }

    private function parseMeminfoKb(string $meminfo, string $key): ?float
    {
        if (preg_match('/^'.preg_quote($key, '/').':\s+(\d+)\s+kB/mi', $meminfo, $m)) {
            return (float) $m[1];
        }

        return null;
    }

    /**
     * @param  list<float|int>  $samples
     */
    private function percentile(array $samples, float $percentile): float
    {
        if ($samples === []) {
            return 0.0;
        }

        sort($samples, SORT_NUMERIC);
        $count = count($samples);
        $rank = (int) ceil(($percentile / 100) * $count) - 1;
        $rank = max(0, min($count - 1, $rank));

        return round((float) $samples[$rank], 1);
    }

    private function bucketKey($moment = null): string
    {
        $moment = $moment ?: now();

        return self::CACHE_PREFIX.'bucket:'.$moment->format('YmdHi');
    }

    /**
     * @return array<string, mixed>
     */
    private function readBucket(string $key): array
    {
        $value = $this->metricsStore()->get($key);

        return is_array($value) ? $value : [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function writeBucket(string $key, array $data): void
    {
        $this->metricsStore()->put($key, $data, now()->addMinutes(self::WINDOW_MINUTES + 2));
    }

    /**
     * Prefer file store so metric bookkeeping does not inflate DB query counters
     * when the app cache driver is database.
     */
    private function metricsStore(): \Illuminate\Contracts\Cache\Repository
    {
        try {
            return Cache::store('file');
        } catch (Throwable) {
            return Cache::store();
        }
    }
}
