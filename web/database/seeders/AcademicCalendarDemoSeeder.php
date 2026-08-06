<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademicCalendarDemoSeeder extends Seeder
{
    public function run(): void
    {
        $yearId = DB::table('academic_years')->where('year_label', '2025/2026')->value('id');

        if (! $yearId) {
            $yearId = DB::table('academic_years')->insertGetId([
                'year_label' => '2025/2026',
                'start_date' => '2025-09-01',
                'end_date' => '2026-08-31',
                'is_current' => 1,
                'created_at' => now(),
            ]);
        }

        $terms = [
            1 => ['label' => 'Semester 1', 'start' => '2026-01-12', 'end' => '2026-04-30', 'current' => 1],
            2 => ['label' => 'Semester 2', 'start' => '2026-05-05', 'end' => '2026-08-15', 'current' => 0],
            3 => ['label' => 'Semester 3', 'start' => '2026-09-01', 'end' => '2026-12-15', 'current' => 0],
            4 => ['label' => 'Semester 4', 'start' => '2027-01-10', 'end' => '2027-04-25', 'current' => 0],
            5 => ['label' => 'Semester 5', 'start' => '2027-05-05', 'end' => '2027-08-20', 'current' => 0],
            6 => ['label' => 'Semester 6', 'start' => '2027-09-01', 'end' => '2027-12-10', 'current' => 0],
        ];

        foreach ($terms as $number => $term) {
            DB::table('semesters')->updateOrInsert(
                ['academic_year_id' => $yearId, 'semester_number' => $number],
                [
                    'semester_label' => $term['label'],
                    'intake_month' => 'January',
                    'start_date' => $term['start'],
                    'end_date' => $term['end'],
                    'registration_open_date' => $term['start'],
                    'registration_close_date' => $term['start'],
                    'is_current' => $term['current'],
                    'created_at' => now(),
                ]
            );
        }

        $this->command?->info('Demo academic calendar seeded (2025/2026, semesters 1–6).');
    }
}
