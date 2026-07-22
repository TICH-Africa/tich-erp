<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ClientContextResolver
{
    /**
     * @return array<string, mixed>
     */
    public function fromRequest(?Request $request): array
    {
        if (! $request) {
            return [];
        }

        $ipAddress = $request->ip();
        $userAgent = $request->userAgent() ?? '';
        $parsed = $this->parseUserAgent($userAgent);

        $context = [
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent !== '' ? substr($userAgent, 0, 500) : null,
            'browser' => $parsed['browser'],
            'browser_version' => $parsed['browser_version'],
            'os' => $parsed['os'],
            'os_version' => $parsed['os_version'],
            'device_type' => $this->resolveDeviceType($request, $parsed['device_type']),
            'language' => $this->resolveLanguage($request),
            'timezone' => $this->stringOrNull($request->input('client_timezone')),
            'screen' => $this->stringOrNull($request->input('client_screen')),
            'connection_type' => $this->stringOrNull($request->input('client_connection_type')),
            'connection_effective_type' => $this->stringOrNull($request->input('client_connection_effective_type')),
            'connection_downlink_mbps' => $this->numericOrNull($request->input('client_connection_downlink')),
            'online' => $this->booleanOrNull($request->input('client_online')),
            'channel' => $request->expectsJson() || $request->is('api/*') ? 'api' : 'web',
            'referer' => $request->headers->get('referer'),
            'accept_language' => $request->headers->get('accept-language'),
        ];

        $context['location'] = $this->resolveLocation($request, $ipAddress);
        $context['network'] = $this->resolveNetwork($context);

        return array_filter($context, fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @return array{browser: string, browser_version: ?string, os: string, os_version: ?string, device_type: string}
     */
    public function parseUserAgent(string $userAgent): array
    {
        $deviceType = 'desktop';

        if (preg_match('/Mobile|Android.*Mobile|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent)) {
            $deviceType = 'mobile';
        } elseif (preg_match('/iPad|Tablet|Kindle|Silk|PlayBook|Android(?!.*Mobile)/i', $userAgent)) {
            $deviceType = 'tablet';
        }

        $browser = 'Unknown';
        $browserVersion = null;

        if (preg_match('/Edg\/([\d.]+)/i', $userAgent, $matches)) {
            $browser = 'Edge';
            $browserVersion = $matches[1];
        } elseif (preg_match('/OPR\/([\d.]+)/i', $userAgent, $matches)) {
            $browser = 'Opera';
            $browserVersion = $matches[1];
        } elseif (preg_match('/Chrome\/([\d.]+)/i', $userAgent, $matches) && ! preg_match('/Edg\/|OPR\//i', $userAgent)) {
            $browser = 'Chrome';
            $browserVersion = $matches[1];
        } elseif (preg_match('/Version\/([\d.]+).*Safari/i', $userAgent, $matches) && preg_match('/Safari/i', $userAgent) && ! preg_match('/Chrome|Chromium/i', $userAgent)) {
            $browser = 'Safari';
            $browserVersion = $matches[1];
        } elseif (preg_match('/Firefox\/([\d.]+)/i', $userAgent, $matches)) {
            $browser = 'Firefox';
            $browserVersion = $matches[1];
        } elseif (preg_match('/MSIE ([\d.]+)/i', $userAgent, $matches) || preg_match('/Trident\/.*rv:([\d.]+)/i', $userAgent, $matches)) {
            $browser = 'Internet Explorer';
            $browserVersion = $matches[1];
        }

        $os = 'Unknown';
        $osVersion = null;

        if (preg_match('/Windows NT ([\d.]+)/i', $userAgent, $matches)) {
            $os = 'Windows';
            $osVersion = $this->mapWindowsVersion($matches[1]);
        } elseif (preg_match('/Mac OS X ([\d_]+)/i', $userAgent, $matches)) {
            $os = 'macOS';
            $osVersion = str_replace('_', '.', $matches[1]);
        } elseif (preg_match('/Android ([\d.]+)/i', $userAgent, $matches)) {
            $os = 'Android';
            $osVersion = $matches[1];
        } elseif (preg_match('/iPhone OS ([\d_]+)/i', $userAgent, $matches) || preg_match('/CPU OS ([\d_]+)/i', $userAgent, $matches)) {
            $os = 'iOS';
            $osVersion = str_replace('_', '.', $matches[1]);
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/CrOS/i', $userAgent)) {
            $os = 'Chrome OS';
        }

        return [
            'browser' => $browser,
            'browser_version' => $browserVersion,
            'os' => $os,
            'os_version' => $osVersion,
            'device_type' => $deviceType,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveLocation(Request $request, ?string $ipAddress): array
    {
        $countryHeader = $request->headers->get('CF-IPCountry')
            ?? $request->headers->get('X-AppEngine-Country')
            ?? $request->headers->get('X-Country-Code');

        if ($countryHeader && strtoupper($countryHeader) !== 'XX') {
            return [
                'country' => strtoupper($countryHeader),
                'source' => 'request_header',
            ];
        }

        if (! $ipAddress || $this->isPrivateIp($ipAddress)) {
            return [
                'label' => 'Private or local network',
                'country' => null,
                'source' => 'private_ip',
            ];
        }

        if (! config('audit.geo_lookup_enabled', true)) {
            return [
                'label' => 'Geo lookup disabled',
                'country' => null,
                'source' => 'disabled',
            ];
        }

        return Cache::remember(
            'audit:geo:'.md5($ipAddress),
            now()->addHours(12),
            fn () => $this->lookupGeoLocation($ipAddress)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function lookupGeoLocation(string $ipAddress): array
    {
        try {
            $response = Http::timeout(2)
                ->get("http://ip-api.com/json/{$ipAddress}", [
                    'fields' => 'status,message,country,countryCode,regionName,city,isp,org,mobile,proxy,hosting,query',
                ]);

            if (! $response->successful()) {
                return ['label' => 'Geo lookup unavailable', 'source' => 'lookup_failed'];
            }

            $data = $response->json();

            if (($data['status'] ?? '') !== 'success') {
                return [
                    'label' => $data['message'] ?? 'Geo lookup failed',
                    'source' => 'lookup_failed',
                ];
            }

            $parts = array_filter([
                $data['city'] ?? null,
                $data['regionName'] ?? null,
                $data['country'] ?? null,
            ]);

            return [
                'label' => $parts !== [] ? implode(', ', $parts) : ($data['country'] ?? 'Unknown'),
                'country' => $data['country'] ?? null,
                'country_code' => $data['countryCode'] ?? null,
                'region' => $data['regionName'] ?? null,
                'city' => $data['city'] ?? null,
                'isp' => $data['isp'] ?? null,
                'organization' => $data['org'] ?? null,
                'mobile_network' => (bool) ($data['mobile'] ?? false),
                'proxy' => (bool) ($data['proxy'] ?? false),
                'hosting' => (bool) ($data['hosting'] ?? false),
                'source' => 'ip_lookup',
            ];
        } catch (\Throwable) {
            return ['label' => 'Geo lookup unavailable', 'source' => 'lookup_failed'];
        }
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function resolveNetwork(array $context): array
    {
        $location = is_array($context['location'] ?? null) ? $context['location'] : [];
        $connectionType = $context['connection_type'] ?? null;
        $effectiveType = $context['connection_effective_type'] ?? null;

        $parts = array_filter([
            $connectionType,
            $effectiveType ? "({$effectiveType})" : null,
            $location['isp'] ?? null,
        ]);

        return array_filter([
            'label' => $parts !== [] ? implode(' · ', $parts) : ($location['isp'] ?? null),
            'isp' => $location['isp'] ?? null,
            'organization' => $location['organization'] ?? null,
            'connection_type' => $connectionType,
            'effective_type' => $effectiveType,
            'downlink_mbps' => $context['connection_downlink_mbps'] ?? null,
            'mobile_network' => $location['mobile_network'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function resolveDeviceType(Request $request, string $fallback): string
    {
        $clientDevice = strtolower((string) $request->input('client_device_type', ''));

        return in_array($clientDevice, ['desktop', 'mobile', 'tablet'], true)
            ? $clientDevice
            : $fallback;
    }

    private function resolveLanguage(Request $request): ?string
    {
        $clientLanguage = $this->stringOrNull($request->input('client_language'));

        if ($clientLanguage) {
            return $clientLanguage;
        }

        $acceptLanguage = $request->headers->get('accept-language');

        if (! $acceptLanguage) {
            return null;
        }

        return trim(explode(',', $acceptLanguage)[0]);
    }

    private function mapWindowsVersion(string $ntVersion): string
    {
        return match ($ntVersion) {
            '10.0' => '10/11',
            '6.3' => '8.1',
            '6.2' => '8',
            '6.1' => '7',
            default => $ntVersion,
        };
    }

    private function isPrivateIp(string $ipAddress): bool
    {
        return ! filter_var(
            $ipAddress,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr(trim((string) $value), 0, 255);
    }

    private function numericOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function booleanOrNull(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
