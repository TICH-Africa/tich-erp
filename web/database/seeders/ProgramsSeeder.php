<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProgramsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('campuses') || ! Schema::hasTable('departments') || ! Schema::hasTable('academic_programs')) {
            return;
        }

        $campusId = DB::table('campuses')->where('campus_code', 'MAIN')->value('id');

        if (! $campusId) {
            $campusId = DB::table('campuses')->insertGetId([
                'campus_code' => 'MAIN',
                'campus_name' => 'TICH Main Campus',
                'campus_type' => 'main',
                'county' => 'Kisumu',
                'physical_address' => 'Kisumu, Kenya',
                'is_active' => 1,
                'created_at' => now(),
            ]);
        }

        $departmentId = DB::table('departments')->where('dept_code', 'CHS')->value('id');

        if (! $departmentId) {
            $departmentId = DB::table('departments')->insertGetId([
                'dept_code' => 'CHS',
                'dept_name' => 'Community Health Sciences',
                'dept_category' => 'academic',
                'campus_id' => $campusId,
                'is_active' => 1,
                'created_at' => now(),
            ]);
        }

        if (DB::table('academic_programs')->exists()) {
            return;
        }

        $programs = [
            [
                'program_code' => 'CHP',
                'program_name' => 'Certificate in Community Health Practice',
                'program_type' => 'certificate',
                'regulatory_body' => 'NITA',
                'duration_months' => 12,
                'homepage_tagline' => 'Frontline community health skills for CHPs and health promoters.',
                'entry_requirements' => 'KCSE mean grade D+ or equivalent; passion for community service.',
                'is_featured_on_homepage' => 1,
                'homepage_display_order' => 1,
            ],
            [
                'program_code' => 'CHD',
                'program_name' => 'Diploma in Community Health Development',
                'program_type' => 'diploma',
                'regulatory_body' => 'CDACC',
                'duration_months' => 24,
                'homepage_tagline' => 'Lead community health programmes and development initiatives.',
                'entry_requirements' => 'KCSE mean grade C- or CHP certificate with experience.',
                'is_featured_on_homepage' => 0,
                'homepage_display_order' => 2,
            ],
            [
                'program_code' => 'HDT',
                'program_name' => 'Health & Development Technician',
                'program_type' => 'diploma',
                'regulatory_body' => 'TVET',
                'duration_months' => 18,
                'homepage_tagline' => 'Technical skills for health systems support and development work.',
                'entry_requirements' => 'KCSE mean grade C- with passes in Maths and English.',
                'is_featured_on_homepage' => 0,
                'homepage_display_order' => 3,
            ],
        ];

        foreach ($programs as $program) {
            DB::table('academic_programs')->insert([
                ...$program,
                'department_id' => $departmentId,
                'status' => 'active',
                'created_at' => now(),
            ]);
        }

        if (Schema::hasTable('navigation_menu_items')) {
            DB::table('navigation_menu_items')
                ->where('label', 'Admissions')
                ->update(['label' => 'Programs/Courses', 'url_or_route' => '/programs']);

            DB::table('navigation_menu_items')
                ->where('label', 'Admissions Guide')
                ->update(['label' => 'Programs & Courses', 'url_or_route' => '/programs']);
        }
    }
}
