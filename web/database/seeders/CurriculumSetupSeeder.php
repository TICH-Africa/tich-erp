<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CurriculumSetupSeeder extends Seeder
{
    public function run(): void
    {
        $cvId = \App\Models\CurriculumVersion::max('id');
        if (! $cvId) {
            return;
        }

        $units = \App\Models\Unit::where('unit_code', 'like', 'HMDCC-%')->get();

        foreach ($units as $unit) {
            preg_match('/HMDCC-(\d+)/', $unit->unit_code, $matches);
            $unitNumber = (int) ($matches[1] ?? 1);
            $semester = max(1, (int) ceil($unitNumber / 6));

            \App\Models\CurriculumVersionUnit::query()->firstOrCreate(
                ['curriculum_version_id' => $cvId, 'unit_id' => $unit->id],
                ['semester' => $semester, 'contact_hours' => $unit->contact_hours, 'display_order' => $unitNumber, 'priority' => 0]
            );
        }

        $this->command->info('Curriculum version units synced: ' . $units->count());
    }
}