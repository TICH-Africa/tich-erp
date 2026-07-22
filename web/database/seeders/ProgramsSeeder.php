<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProgramsSeeder extends Seeder
{
    public function run(): void
    {
        $this->syncNavigationLabels();

        if (! Schema::hasTable('campuses') || ! Schema::hasTable('departments') || ! Schema::hasTable('academic_programs')) {
            return;
        }

        $campusId = $this->ensureMainCampus();
        $groups = $this->getOrCreateDepartmentGroups();
        $departments = $this->getOrCreateDepartmentStructure($campusId, $groups);

        foreach ($this->programs() as $program) {
            $code = $program['program_code'];
            $payload = [
                ...$program,
                'department_id' => $this->getDepartmentForProgram($code, $departments),
                'status' => 'active',
            ];

            $existingId = DB::table('academic_programs')->where('program_code', $code)->value('id');

            if ($existingId) {
                DB::table('academic_programs')->where('id', $existingId)->update([
                    ...$payload,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('academic_programs')->insert([
                    ...$payload,
                    'created_at' => now(),
                ]);
            }
        }
    }

    private function ensureMainCampus(): int
    {
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

        return (int) $campusId;
    }

    private function getOrCreateDepartmentGroups(): array
    {
        $groups = [
            'IDM' => ['name' => 'Institutional Development Management', 'order' => 1],
            'OTH' => ['name' => 'Others', 'order' => 2],
        ];

        $result = [];

        foreach ($groups as $code => $meta) {
            if (Schema::hasTable('department_groups')) {
                $id = DB::table('department_groups')->where('group_code', $code)->value('id');

                if (! $id) {
                    $id = DB::table('department_groups')->insertGetId([
                        'group_code' => $code,
                        'group_name' => $meta['name'],
                        'display_order' => $meta['order'],
                        'is_active' => 1,
                        'created_at' => now(),
                    ]);
                } else {
                    DB::table('department_groups')->where('id', $id)->update([
                        'group_name' => $meta['name'],
                        'display_order' => $meta['order'],
                    ]);
                }

                $result[$code] = (int) $id;
            }
        }

        return $result;
    }

    private function getOrCreateDepartmentStructure(int $campusId, array $groups): array
    {
        $idmGroupId = $groups['IDM'] ?? null;
        $othGroupId = $groups['OTH'] ?? null;

        $adminUnits = [
            ['code' => 'HR', 'name' => 'Human Resource', 'group' => 'IDM', 'order' => 1],
            ['code' => 'FIN', 'name' => 'Finance', 'group' => 'IDM', 'order' => 2],
            ['code' => 'PRC', 'name' => 'Procurement & Logistics', 'group' => 'IDM', 'order' => 3],
            ['code' => 'RES', 'name' => 'Research', 'group' => 'OTH', 'order' => 1],
            ['code' => 'ICTO', 'name' => 'ICT', 'group' => 'OTH', 'order' => 2],
            ['code' => 'ACAD', 'name' => 'Academics', 'group' => 'OTH', 'order' => 3],
            ['code' => 'ADM', 'name' => 'Admin', 'group' => 'OTH', 'order' => 4],
            ['code' => 'MKT', 'name' => 'Marketing', 'group' => 'OTH', 'order' => 5],
        ];

        $learningDepartments = [
            ['code' => 'CHS', 'name' => 'Health and Social Sciences', 'parent' => 'ACAD', 'order' => 1],
            ['code' => 'HOS', 'name' => 'Catering and Hospitality', 'parent' => 'ACAD', 'order' => 2],
            ['code' => 'BUS', 'name' => 'Business and Accounting', 'parent' => 'ACAD', 'order' => 3],
            ['code' => 'ICT', 'name' => 'Information Communication Technology', 'parent' => 'ACAD', 'order' => 4],
            ['code' => 'TEC', 'name' => 'Technical Department', 'parent' => 'ACAD', 'order' => 5],
        ];

        $result = [];

        foreach ($adminUnits as $unit) {
            $groupId = $unit['group'] === 'IDM' ? $idmGroupId : $othGroupId;
            $result[$unit['code']] = $this->upsertDepartment(
                $unit['code'],
                $unit['name'],
                'administrative',
                $campusId,
                $groupId,
                null,
                $unit['order']
            );
        }

        foreach ($learningDepartments as $dept) {
            $parentId = $result[$dept['parent']] ?? null;
            $result[$dept['code']] = $this->upsertDepartment(
                $dept['code'],
                $dept['name'],
                'academic',
                $campusId,
                $othGroupId,
                $parentId,
                $dept['order']
            );
        }

        return $result;
    }

    private function upsertDepartment(
        string $code,
        string $name,
        string $category,
        int $campusId,
        ?int $groupId,
        ?int $parentId,
        int $order,
    ): int {
        $id = DB::table('departments')->where('dept_code', $code)->value('id');

        $payload = [
            'dept_name' => $name,
            'dept_category' => $category,
            'campus_id' => $campusId,
            'department_group_id' => $groupId,
            'parent_dept_id' => $parentId,
            'display_order' => $order,
            'is_active' => 1,
        ];

        if (! $id) {
            $id = DB::table('departments')->insertGetId([
                'dept_code' => $code,
                ...$payload,
                'created_at' => now(),
            ]);
        } else {
            DB::table('departments')->where('id', $id)->update($payload);
        }

        return (int) $id;
    }

    private function programs(): array
    {
        return [
            // Department of Health and Social Sciences
            ['program_code' => 'CHP', 'program_name' => 'Certificate in Community Health Practice', 'program_type' => 'certificate', 'regulatory_body' => 'NITA', 'duration_months' => 12, 'homepage_tagline' => 'The Certificate in Community Health Practice is designed to equip frontline health workers with essential community health skills, preventive care knowledge, and practical field experience for serving rural and underserved populations across Western Kenya.', 'entry_requirements' => 'KCSE mean grade D+ or equivalent', 'is_featured_on_homepage' => 1, 'homepage_display_order' => 1],
            ['program_code' => 'CHD', 'program_name' => 'Diploma in Community Health Development', 'program_type' => 'diploma', 'regulatory_body' => 'CDACC', 'duration_months' => 24, 'homepage_tagline' => 'The Diploma in Community Health Development prepares students to lead health programmes, coordinate community interventions, and manage health projects in faith-based and community organizations.', 'entry_requirements' => 'KCSE mean grade C- or CHP certificate', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 2],
            ['program_code' => 'CHN', 'program_name' => 'Diploma in Community Health Nursing', 'program_type' => 'diploma', 'regulatory_body' => 'Nursing Council', 'duration_months' => 36, 'homepage_tagline' => 'The Community Health Nursing diploma trains holistic nurses with expertise in community health, home-based care, and faith-based health ministry, combining clinical excellence with spiritual care.', 'entry_requirements' => 'KCSE mean grade C or above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 3],
            ['program_code' => 'HMD', 'program_name' => 'Diploma in Homecare Management', 'program_type' => 'diploma', 'regulatory_body' => 'NITA', 'duration_months' => 24, 'homepage_tagline' => 'Specialised homecare management training focusing on patient support services, chronic disease management, and compassionate care delivery in home settings.', 'entry_requirements' => 'KCSE mean grade C-', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 4],
            ['program_code' => 'HMD-CC', 'program_name' => 'Community Health and Development (Community College)', 'program_type' => 'certificate', 'regulatory_body' => 'NITA', 'duration_months' => 12, 'homepage_tagline' => 'Accelerated certificate programme delivered at our community college campuses for accessible community health training.', 'entry_requirements' => 'KCSE mean grade D+ and above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 5],
            ['program_code' => 'HCA', 'program_name' => 'Health Care Assistant', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 6, 'homepage_tagline' => 'Six-month intensive training for health care assistants focusing on basic patient care, clinical support, and health service delivery.', 'entry_requirements' => 'KCSE mean grade C-', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 6],
            ['program_code' => 'HCA-CC', 'program_name' => 'Homecare Management', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 3, 'homepage_tagline' => 'Three-month homecare specialization for qualified CHPs with practical skills in home-based patient management and support.', 'entry_requirements' => 'CHPs', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 7],
            ['program_code' => 'HCA-C', 'program_name' => 'Homecare Management', 'program_type' => 'certificate', 'regulatory_body' => 'NITA', 'duration_months' => 12, 'homepage_tagline' => 'Comprehensive certificate programme in homecare management with emphasis on long-term care planning and family support services.', 'entry_requirements' => 'KCSE mean grade D+', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 8],
            ['program_code' => 'CLM', 'program_name' => 'Diploma in Clinical Medicine', 'program_type' => 'diploma', 'regulatory_body' => 'TVET', 'duration_months' => 36, 'homepage_tagline' => 'Three-year clinical medicine programme training competent clinical officers for diagnosis, treatment, and patient management in various healthcare settings.', 'entry_requirements' => 'KCSE mean grade C', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 9],
            ['program_code' => 'PHT', 'program_name' => 'Diploma in Perioperative Health Technology', 'program_type' => 'diploma', 'regulatory_body' => 'TVET', 'duration_months' => 36, 'homepage_tagline' => 'Specialized training in perioperative care, surgical technology, and operating theatre management for health technologists.', 'entry_requirements' => 'KCSE mean grade C', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 10],
            // Department of Catering and Hospitality
            ['program_code' => 'FBV4', 'program_name' => 'Food and Beverage Level 4', 'program_type' => 'certificate', 'regulatory_body' => 'TVET', 'duration_months' => 12, 'homepage_tagline' => 'Introductory food service training.', 'entry_requirements' => 'KCSE mean grade D-', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 8],
            ['program_code' => 'FBV5', 'program_name' => 'Food and Beverage Level 5', 'program_type' => 'diploma', 'regulatory_body' => 'TVET', 'duration_months' => 24, 'homepage_tagline' => 'Intermediate hospitality training.', 'entry_requirements' => 'KCSE mean grade D+', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 9],
            ['program_code' => 'FBV6', 'program_name' => 'Food and Beverage Level 6', 'program_type' => 'diploma', 'regulatory_body' => 'TVET', 'duration_months' => 36, 'homepage_tagline' => 'Advanced hospitality management.', 'entry_requirements' => 'KCSE mean grade C-', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 10],
            // Department of Business and Accounting
            ['program_code' => 'CPA', 'program_name' => 'Certified Public Accountant (CPA)', 'program_type' => 'diploma', 'regulatory_body' => 'KASNEB', 'duration_months' => 24, 'homepage_tagline' => 'Professional accounting certification.', 'entry_requirements' => 'KCSE mean grade C-', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 11],
            ['program_code' => 'ATD', 'program_name' => 'Accountant Technician Diploma', 'program_type' => 'diploma', 'regulatory_body' => 'KASNEB', 'duration_months' => 24, 'homepage_tagline' => 'Technician-level accounting skills.', 'entry_requirements' => 'KCSE mean grade D+', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 12],
            ['program_code' => 'AGD', 'program_name' => 'Diploma in Agribusiness', 'program_type' => 'diploma', 'regulatory_body' => 'TVET', 'duration_months' => 36, 'homepage_tagline' => 'Agricultural business management.', 'entry_requirements' => 'KCSE mean grade C-', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 13],
            ['program_code' => 'AGC', 'program_name' => 'Certificate in Agribusiness', 'program_type' => 'certificate', 'regulatory_body' => 'TVET', 'duration_months' => 12, 'homepage_tagline' => 'Basic agribusiness skills.', 'entry_requirements' => 'KCSE mean grade D', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 14],
            // Department of Data Science and Analytics
            ['program_code' => 'DSC', 'program_name' => 'Certificate in Data Science', 'program_type' => 'certificate', 'regulatory_body' => 'TVET', 'duration_months' => 12, 'homepage_tagline' => 'Data science fundamentals.', 'entry_requirements' => 'KCSE mean grade D+', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 15],
            ['program_code' => 'DSD', 'program_name' => 'Diploma in Data Science', 'program_type' => 'diploma', 'regulatory_body' => 'TVET', 'duration_months' => 24, 'homepage_tagline' => 'Advanced data science skills.', 'entry_requirements' => 'KCSE mean grade C-', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 16],
            // Department of ICT
            ['program_code' => 'ICT4', 'program_name' => 'Information Communication Technology Level 4', 'program_type' => 'certificate', 'regulatory_body' => 'TVET', 'duration_months' => 12, 'homepage_tagline' => 'Basic ICT skills.', 'entry_requirements' => 'KCSE mean grade D-', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 17],
            ['program_code' => 'ICT5', 'program_name' => 'Information Communication Technology Level 5', 'program_type' => 'diploma', 'regulatory_body' => 'TVET', 'duration_months' => 24, 'homepage_tagline' => 'Intermediate ICT training.', 'entry_requirements' => 'KCSE mean grade D-', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 18],
            ['program_code' => 'ICT6', 'program_name' => 'Information Communication Technology Level 6', 'program_type' => 'diploma', 'regulatory_body' => 'TVET', 'duration_months' => 36, 'homepage_tagline' => 'Advanced ICT diploma.', 'entry_requirements' => 'KCSE mean grade C-', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 19],
            ['program_code' => 'CRM', 'program_name' => 'Computer Repair and Maintenance', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 12, 'homepage_tagline' => 'Computer hardware repair skills.', 'entry_requirements' => 'KCSE mean grade D-', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 20],
            ['program_code' => 'CPK', 'program_name' => 'Computer Packages', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 12, 'homepage_tagline' => 'Computer applications package training.', 'entry_requirements' => 'KCSE mean grade D-', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 21],
            ['program_code' => 'CHM', 'program_name' => 'Computer Hardware and Maintenance', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 12, 'homepage_tagline' => 'Hardware maintenance training.', 'entry_requirements' => 'KCSE mean grade D-', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 22],
            ['program_code' => 'SWE', 'program_name' => 'Software Engineering', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 12, 'homepage_tagline' => 'Software development fundamentals.', 'entry_requirements' => 'KCSE mean grade D-', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 23],
            ['program_code' => 'CNS', 'program_name' => 'Computer Network and Security', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 12, 'homepage_tagline' => 'Network and security training.', 'entry_requirements' => 'KCSE mean grade D-', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 24],
            ['program_code' => 'SYS', 'program_name' => 'System Administration', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 12, 'homepage_tagline' => 'System administration skills.', 'entry_requirements' => 'KCSE mean grade D-', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 25],
            ['program_code' => 'WDD', 'program_name' => 'Web Design and Development', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 12, 'homepage_tagline' => 'Web design and development skills.', 'entry_requirements' => 'KCSE mean grade D-', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 26],
            // Catering and Hospitality (NITA 6-month variants - removed duplicates, already in HOS)
            // Note: Food and Beverage Production/Service and Motor Vehicle Mechanics are already covered under HOS/FBV codes
            ['program_code' => 'EWI', 'program_name' => 'Electrical Wireman Installation', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 6, 'homepage_tagline' => 'Electrical installation skills.', 'entry_requirements' => 'KCPE and above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 27],
            ['program_code' => 'MAS', 'program_name' => 'Masonry', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 6, 'homepage_tagline' => 'Masonry construction skills.', 'entry_requirements' => 'KCPE and above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 28],
            ['program_code' => 'CPO', 'program_name' => 'Computer Operator', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 6, 'homepage_tagline' => 'Computer operation skills.', 'entry_requirements' => 'KCPE and above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 29],
            ['program_code' => 'GRD', 'program_name' => 'Graphics Design', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 6, 'homepage_tagline' => 'Graphic design skills.', 'entry_requirements' => 'KCPE and above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 30],
            ['program_code' => 'PLB', 'program_name' => 'Plumbing', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 6, 'homepage_tagline' => 'Plumbing skills.', 'entry_requirements' => 'KCPE and above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 31],
            ['program_code' => 'CCTV', 'program_name' => 'CCTV Camera Installation', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 6, 'homepage_tagline' => 'CCTV installation skills.', 'entry_requirements' => 'KCPE and above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 32],
            ['program_code' => 'FBP', 'program_name' => 'Food and Beverage Production', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 6, 'homepage_tagline' => 'Food production skills.', 'entry_requirements' => 'KCPE and above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 33],
            ['program_code' => 'FBS', 'program_name' => 'Food and Beverage Service', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 6, 'homepage_tagline' => 'Food service skills.', 'entry_requirements' => 'KCPE and above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 34],
            ['program_code' => 'MVM', 'program_name' => 'Motor Vehicle Mechanics', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 6, 'homepage_tagline' => 'Motor vehicle mechanics skills.', 'entry_requirements' => 'KCPE and above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 35],
            ['program_code' => 'SOL', 'program_name' => 'Basic Solar Installation', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 6, 'homepage_tagline' => 'Solar installation skills.', 'entry_requirements' => 'KCPE and above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 36],
            ['program_code' => 'ELF', 'program_name' => 'Electrical Filter', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 6, 'homepage_tagline' => 'Electrical filter installation.', 'entry_requirements' => 'KCPE and above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 37],
            ['program_code' => 'MCM', 'program_name' => 'Motor Cycle Mechanics', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 6, 'homepage_tagline' => 'Motorcycle repair skills.', 'entry_requirements' => 'KCPE and above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 38],
            ['program_code' => 'MVE', 'program_name' => 'Motor Vehicle Electrical', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 6, 'homepage_tagline' => 'Motor vehicle electrical systems.', 'entry_requirements' => 'KCPE and above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 39],
            ['program_code' => 'MVB', 'program_name' => 'Motor Vehicle Body Building', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 6, 'homepage_tagline' => 'Vehicle body building skills.', 'entry_requirements' => 'KCPE and above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 40],
            ['program_code' => 'SPP', 'program_name' => 'Spray Painting', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 6, 'homepage_tagline' => 'Spray painting skills.', 'entry_requirements' => 'KCPE and above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 41],
            ['program_code' => 'REF', 'program_name' => 'Refrigeration', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 6, 'homepage_tagline' => 'Refrigeration repair skills.', 'entry_requirements' => 'KCPE and above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 42],
            ['program_code' => 'WLD', 'program_name' => 'Welding and Fabrication', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 6, 'homepage_tagline' => 'Welding and fabrication skills.', 'entry_requirements' => 'KCPE and above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 43],
            ['program_code' => 'MRW', 'program_name' => 'Motor Rewinding', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 6, 'homepage_tagline' => 'Motor rewinding skills.', 'entry_requirements' => 'KCPE and above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 44],
            ['program_code' => 'ELM', 'program_name' => 'Electronics Mechanics', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 6, 'homepage_tagline' => 'Electronics repair skills.', 'entry_requirements' => 'KCPE and above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 45],
            ['program_code' => 'BSC', 'program_name' => 'Bio Sanitation', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 6, 'homepage_tagline' => 'Bio sanitation skills.', 'entry_requirements' => 'KCPE and above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 46],
            ['program_code' => 'WDW', 'program_name' => 'Wood Work', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 6, 'homepage_tagline' => 'Woodwork skills.', 'entry_requirements' => 'KCPE and above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 47],
            ['program_code' => 'HRD', 'program_name' => 'Hairdressing', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 6, 'homepage_tagline' => 'Hairdressing skills.', 'entry_requirements' => 'KCPE and above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 48],
            ['program_code' => 'BTH', 'program_name' => 'Beauty Therapy', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 6, 'homepage_tagline' => 'Beauty therapy skills.', 'entry_requirements' => 'KCPE and above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 49],
            ['program_code' => 'TDM', 'program_name' => 'Tailoring and Dressmaking', 'program_type' => 'artisan', 'regulatory_body' => 'NITA', 'duration_months' => 6, 'homepage_tagline' => 'Tailoring and dressmaking skills.', 'entry_requirements' => 'KCPE and above', 'is_featured_on_homepage' => 0, 'homepage_display_order' => 50],
        ];
    }

    private function getDepartmentForProgram(string $code, array $departments): ?int
    {
        $mapping = [
            'CHP' => 'CHS', 'CHD' => 'CHS', 'CHN' => 'CHS', 'HMD' => 'CHS', 'HMD-CC' => 'CHS', 'HCA' => 'CHS',
            'HCA-CC' => 'CHS', 'HCA-C' => 'CHS', 'CLM' => 'CHS', 'PHT' => 'CHS', 'HDT' => 'CHS',
            'FBV4' => 'HOS', 'FBV5' => 'HOS', 'FBV6' => 'HOS', 'FBP' => 'HOS', 'FBS' => 'HOS',
            'CPA' => 'BUS', 'ATD' => 'BUS', 'AGD' => 'BUS', 'AGC' => 'BUS',
            'DSC' => 'ICT', 'DSD' => 'ICT',
            'ICT4' => 'ICT', 'ICT5' => 'ICT', 'ICT6' => 'ICT', 'CRM' => 'ICT',
            'CPK' => 'ICT', 'CHM' => 'ICT', 'SWE' => 'ICT', 'CNS' => 'ICT', 'SYS' => 'ICT', 'WDD' => 'ICT',
            'EWI' => 'TEC', 'MAS' => 'TEC', 'CPO' => 'TEC', 'GRD' => 'TEC', 'PLB' => 'TEC',
            'CCTV' => 'TEC', 'MVM' => 'TEC', 'SOL' => 'TEC', 'ELF' => 'TEC', 'MCM' => 'TEC', 'MVE' => 'TEC', 'MVB' => 'TEC', 'SPP' => 'TEC', 'REF' => 'TEC',
            'WLD' => 'TEC', 'MRW' => 'TEC', 'ELM' => 'TEC', 'BSC' => 'TEC', 'WDW' => 'TEC',
            'HRD' => 'TEC', 'BTH' => 'TEC', 'TDM' => 'TEC',
        ];

        $deptCode = $mapping[$code] ?? 'CHS';

        return $departments[$deptCode] ?? $departments['CHS'] ?? null;
    }

    private function syncNavigationLabels(): void
    {
        if (! Schema::hasTable('navigation_menu_items')) {
            return;
        }

        DB::table('navigation_menu_items')
            ->whereIn('label', ['Admissions', 'Programs/Courses', 'Programs & Courses'])
            ->update(['label' => 'Programs & courses', 'url_or_route' => '/programs']);

        DB::table('navigation_menu_items')
            ->where('label', 'Admissions Guide')
            ->update(['label' => 'Programs & courses', 'url_or_route' => '/programs']);
    }
}