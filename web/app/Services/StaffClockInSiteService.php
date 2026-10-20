<?php

namespace App\Services;

use App\Models\Campus;
use App\Support\SchemaProbe;

class StaffClockInSiteService
{
    public const OTHER_VALUE = 'other';

    /**
     * Active campuses and community colleges for the clock-in picker.
     *
     * @return list<array{id: int|string, label: string, group: string}>
     */
    public function optionsForPicker(): array
    {
        if (! SchemaProbe::hasTable('campuses')) {
            return [];
        }

        $rows = Campus::query()
            ->where('is_active', true)
            ->orderBy('campus_name')
            ->get(['id', 'campus_name', 'campus_type', 'county']);

        $options = [];

        foreach ($rows as $campus) {
            $type = (string) ($campus->campus_type ?? '');
            if ($type === 'community_college') {
                $label = $campus->campus_name;
                if (filled($campus->county)) {
                    $label = $campus->county.' · '.$label;
                }
                $options[] = [
                    'id' => (int) $campus->id,
                    'label' => $label,
                    'group' => 'community_college',
                ];

                continue;
            }

            // Main campus uses GPS geofence with "Off campus" unchecked — omit from picker.
            if ($type === 'main') {
                continue;
            }

            $options[] = [
                'id' => (int) $campus->id,
                'label' => (string) $campus->campus_name,
                'group' => 'campus',
            ];
        }

        usort($options, function (array $a, array $b) {
            $groupOrder = ['campus' => 0, 'community_college' => 1];
            $ga = $groupOrder[$a['group']] ?? 9;
            $gb = $groupOrder[$b['group']] ?? 9;
            if ($ga !== $gb) {
                return $ga <=> $gb;
            }

            return strcasecmp($a['label'], $b['label']);
        });

        $options[] = [
            'id' => self::OTHER_VALUE,
            'label' => 'Other (specify)',
            'group' => 'other',
        ];

        return $options;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function normalizeClockInSite(array $data): array
    {
        $isOffCampus = ! empty($data['is_off_campus']);

        if (! $isOffCampus) {
            $data['clock_in_campus_id'] = null;
            $data['clock_in_campus_other'] = null;
            $data['field_project_name'] = null;

            return $data;
        }

        $rawId = $data['clock_in_campus_id'] ?? null;
        $other = trim((string) ($data['clock_in_campus_other'] ?? ''));

        if ($rawId === self::OTHER_VALUE || $rawId === 'other') {
            if ($other === '') {
                throw new \RuntimeException('Enter where you are reporting from when you select Other.');
            }
            $data['clock_in_campus_id'] = null;
            $data['clock_in_campus_other'] = $other;
            $data['field_project_name'] = null;

            return $data;
        }

        if ($rawId === null || $rawId === '') {
            throw new \RuntimeException('Select the campus or community college you are clocking in from.');
        }

        $campusId = (int) $rawId;
        $campus = Campus::query()
            ->where('id', $campusId)
            ->where('is_active', true)
            ->where('campus_type', '!=', 'main')
            ->first();

        if (! $campus) {
            throw new \RuntimeException('The selected campus is not valid.');
        }

        $data['clock_in_campus_id'] = $campusId;
        $data['clock_in_campus_other'] = null;
        $data['field_project_name'] = null;

        return $data;
    }
}
