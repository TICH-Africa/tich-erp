<?php

namespace Database\Seeders;

use App\Models\AcademicProgram;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class HmdCcUnitsSeeder extends Seeder
{
    public function run(): void
    {
        $program = AcademicProgram::query()->where('program_code', 'HMD-CC')->first();

        if (! $program) {
            $this->command?->warn('HMD-CC programme not found. Run ProgramsSeeder first.');

            return;
        }

        $units = [
            ['code' => 'HMDCC-01', 'name' => 'Introduction to Community Health', 'semester' => 1, 'contact' => 45, 'learning' => 90, 'core' => true, 'practical' => false],
            ['code' => 'HMDCC-02', 'name' => 'Communication Skills in Health Care', 'semester' => 1, 'contact' => 30, 'learning' => 60, 'core' => true, 'practical' => false],
            ['code' => 'HMDCC-03', 'name' => 'Basic Anatomy and Physiology', 'semester' => 1, 'contact' => 60, 'learning' => 120, 'core' => true, 'practical' => false],
            ['code' => 'HMDCC-04', 'name' => 'Community Health Promotion', 'semester' => 2, 'contact' => 45, 'learning' => 90, 'core' => true, 'practical' => false],
            ['code' => 'HMDCC-05', 'name' => 'Primary Health Care Systems', 'semester' => 2, 'contact' => 45, 'learning' => 90, 'core' => true, 'practical' => false],
            ['code' => 'HMDCC-06', 'name' => 'Maternal and Child Health', 'semester' => 2, 'contact' => 50, 'learning' => 100, 'core' => true, 'practical' => true],
            ['code' => 'HMDCC-07', 'name' => 'Environmental and Occupational Health', 'semester' => 3, 'contact' => 40, 'learning' => 80, 'core' => true, 'practical' => false],
            ['code' => 'HMDCC-08', 'name' => 'Nutrition and Community Wellness', 'semester' => 3, 'contact' => 40, 'learning' => 80, 'core' => true, 'practical' => false],
            ['code' => 'HMDCC-09', 'name' => 'HIV/AIDS, TB and STI Management', 'semester' => 3, 'contact' => 45, 'learning' => 90, 'core' => true, 'practical' => false],
            ['code' => 'HMDCC-10', 'name' => 'Mental Health in Community Settings', 'semester' => 4, 'contact' => 35, 'learning' => 70, 'core' => true, 'practical' => false],
            ['code' => 'HMDCC-11', 'name' => 'Health Education and Behaviour Change', 'semester' => 4, 'contact' => 40, 'learning' => 80, 'core' => true, 'practical' => false],
            ['code' => 'HMDCC-12', 'name' => 'Community Diagnosis and Needs Assessment', 'semester' => 4, 'contact' => 45, 'learning' => 90, 'core' => true, 'practical' => true],
            ['code' => 'HMDCC-13', 'name' => 'Home-Based Care and Palliative Support', 'semester' => 5, 'contact' => 50, 'learning' => 100, 'core' => true, 'practical' => true],
            ['code' => 'HMDCC-14', 'name' => 'Health Project Planning and Management', 'semester' => 5, 'contact' => 45, 'learning' => 90, 'core' => true, 'practical' => false],
            ['code' => 'HMDCC-15', 'name' => 'Field Practicum I', 'semester' => 5, 'contact' => 80, 'learning' => 160, 'core' => true, 'practical' => true],
            ['code' => 'HMDCC-16', 'name' => 'Research Methods in Community Health', 'semester' => 6, 'contact' => 40, 'learning' => 80, 'core' => true, 'practical' => false],
            ['code' => 'HMDCC-17', 'name' => 'Community Health Policy and Ethics', 'semester' => 6, 'contact' => 35, 'learning' => 70, 'core' => true, 'practical' => false],
            ['code' => 'HMDCC-18', 'name' => 'Field Practicum II', 'semester' => 6, 'contact' => 100, 'learning' => 200, 'core' => true, 'practical' => true],
        ];

        foreach ($units as $index => $unit) {
            Unit::query()->updateOrCreate(
                ['unit_code' => $unit['code']],
                [
                    'unit_name' => $unit['name'],
                    'description' => "HMD-CC curriculum unit: {$unit['name']}.",
                    'department_id' => $program->department_id,
                    'program_id' => $program->id,
                    'semester' => $unit['semester'],
                    'contact_hours' => $unit['contact'],
                    'total_learning_hours' => $unit['learning'],
                    'display_priority' => $index + 1,
                    'is_core' => $unit['core'],
                    'is_practical' => $unit['practical'],
                    'status' => 'active',
                    'registrar_approved_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command?->info('Seeded '.count($units).' units for HMD-CC (department '.$program->department_id.').');
    }
}
