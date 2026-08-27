<?php

namespace App\Services;

use App\Models\Staff;

class StaffClockInLocationService
{
    public const LOCATION_REQUIRED_MESSAGE = 'Location is required to clock in. Allow browser location access and try again.';
    /**
     * @return array{name: string, latitude: float, longitude: float, radius_meters: int}
     */
    public function geofenceForStaff(Staff $staff): array
    {
        $geofence = config('hr-attendance.default_geofence', []);

        return [
            'name' => (string) ($geofence['name'] ?? 'Campus'),
            'latitude' => (float) ($geofence['latitude'] ?? 0),
            'longitude' => (float) ($geofence['longitude'] ?? 0),
            'radius_meters' => (int) ($geofence['radius_meters'] ?? 500),
        ];
    }

    /**
     * @return array{
     *     latitude: float,
     *     longitude: float,
     *     accuracy_m: ?float,
     *     location_lat_long: string,
     *     location_verification_status: string,
     *     distance_from_campus_m: ?float,
     * }
     */
    public function resolveClockInLocation(Staff $staff, array $data, bool $replacingUnverified = false): array
    {
        if (! config('hr-attendance.require_location', true)) {
            return $this->optionalLocationPayload($data);
        }

        $latitude = isset($data['clock_in_latitude']) ? (float) $data['clock_in_latitude'] : null;
        $longitude = isset($data['clock_in_longitude']) ? (float) $data['clock_in_longitude'] : null;

        if ($latitude === null || $longitude === null) {
            $legacy = $this->parseLatLongString($data['location_lat_long'] ?? null);
            $latitude = $legacy['latitude'];
            $longitude = $legacy['longitude'];
        }

        if ($latitude === null || $longitude === null) {
            if (!empty($data['is_off_campus'])) {
                return [
                    'latitude' => null,
                    'longitude' => null,
                    'accuracy_m' => null,
                    'location_lat_long' => null,
                    'location_verification_status' => 'off_campus',
                    'distance_from_campus_m' => null,
                ];
            }

            throw new \RuntimeException(self::LOCATION_REQUIRED_MESSAGE);
        }

        $accuracy = isset($data['clock_in_accuracy_m']) && $data['clock_in_accuracy_m'] !== ''
            ? (float) $data['clock_in_accuracy_m']
            : null;

        $maxAccuracy = (int) config('hr-attendance.max_accuracy_meters', 2000);
        if ($accuracy !== null && $accuracy > $maxAccuracy) {
            throw new \RuntimeException(
                'Your location reading is too imprecise ('.round($accuracy).' m). Move to an open area or enable precise location, then try again.'
            );
        }

        $isOffCampus = ! empty($data['is_off_campus']);
        $geofence = $this->geofenceForStaff($staff);
        $distance = $this->distanceMeters(
            $latitude,
            $longitude,
            $geofence['latitude'],
            $geofence['longitude'],
        );

        if ($isOffCampus) {
            return $this->locationPayload($latitude, $longitude, $accuracy, 'off_campus', $distance);
        }

        if ($distance <= $geofence['radius_meters']) {
            return $this->locationPayload($latitude, $longitude, $accuracy, 'on_campus', $distance);
        }

        if ($replacingUnverified) {
            return $this->locationPayload($latitude, $longitude, $accuracy, 'outside_geofence', $distance);
        }

        throw new \RuntimeException(
            'You are about '.round($distance).' m from '.$geofence['name'].'. Clock in on campus only when physically present, or tick "Off-campus / field work" if you are working away from campus.'
        );
    }

    /**
     * @return array{
     *     latitude: float,
     *     longitude: float,
     *     accuracy_m: ?float,
     *     location_lat_long: string,
     *     location_verification_status: string,
     *     distance_from_campus_m: float,
     * }
     */
    private function locationPayload(float $latitude, float $longitude, ?float $accuracy, string $status, float $distance): array
    {
        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy_m' => $accuracy,
            'location_lat_long' => $this->formatLatLong($latitude, $longitude),
            'location_verification_status' => $status,
            'distance_from_campus_m' => $distance,
        ];
    }

    public function statusLabel(?string $status): string
    {
        return match ($status) {
            'on_campus' => 'On campus (verified)',
            'off_campus' => 'Off-campus (GPS recorded)',
            'outside_geofence' => 'Outside campus (GPS recorded)',
            'legacy' => 'Location not recorded',
            null => 'Not verified',
            default => 'Not verified',
        };
    }

    public function mapsUrl(?float $latitude, ?float $longitude): ?string
    {
        if ($latitude === null || $longitude === null) {
            return null;
        }

        return 'https://www.google.com/maps?q='.rawurlencode($latitude.','.$longitude);
    }

    /**
     * @return array{latitude: ?float, longitude: ?float}
     */
    private function parseLatLongString(?string $value): array
    {
        if (! $value || ! str_contains($value, ',')) {
            return ['latitude' => null, 'longitude' => null];
        }

        [$lat, $lng] = array_map('trim', explode(',', $value, 2));

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return ['latitude' => null, 'longitude' => null];
        }

        return [
            'latitude' => (float) $lat,
            'longitude' => (float) $lng,
        ];
    }

    private function formatLatLong(float $latitude, float $longitude): string
    {
        return round($latitude, 6).','.round($longitude, 6);
    }

    /**
     * @return array{
     *     latitude: ?float,
     *     longitude: ?float,
     *     accuracy_m: ?float,
     *     location_lat_long: ?string,
     *     location_verification_status: string,
     *     distance_from_campus_m: null,
     * }
     */
    private function optionalLocationPayload(array $data): array
    {
        $latitude = isset($data['clock_in_latitude']) ? (float) $data['clock_in_latitude'] : null;
        $longitude = isset($data['clock_in_longitude']) ? (float) $data['clock_in_longitude'] : null;
        $accuracy = isset($data['clock_in_accuracy_m']) && $data['clock_in_accuracy_m'] !== ''
            ? (float) $data['clock_in_accuracy_m']
            : null;

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy_m' => $accuracy,
            'location_lat_long' => ($latitude !== null && $longitude !== null)
                ? $this->formatLatLong($latitude, $longitude)
                : ($data['location_lat_long'] ?? null),
            'location_verification_status' => ! empty($data['is_off_campus']) ? 'off_campus' : 'legacy',
            'distance_from_campus_m' => null,
        ];
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $latFrom = deg2rad($lat1);
        $latTo = deg2rad($lat2);
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
