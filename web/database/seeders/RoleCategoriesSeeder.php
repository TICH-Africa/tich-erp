<?php

namespace Database\Seeders;

use App\Models\RoleCategory;
use Illuminate\Database\Seeder;

class RoleCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['category_code' => 'executive', 'category_name' => 'Executive', 'description' => 'Institution-wide leadership and executive oversight', 'display_order' => 10, 'is_system' => true],
            ['category_code' => 'academic', 'category_name' => 'Academic', 'description' => 'Academic leadership and faculty management', 'display_order' => 20, 'is_system' => true],
            ['category_code' => 'teaching', 'category_name' => 'Teaching', 'description' => 'Teaching and academic delivery staff', 'display_order' => 30, 'is_system' => true],
            ['category_code' => 'administrative', 'category_name' => 'Administrative', 'description' => 'Administrative and support staff', 'display_order' => 40, 'is_system' => true],
            ['category_code' => 'student', 'category_name' => 'Student', 'description' => 'Student, applicant, and alumni portal roles', 'display_order' => 50, 'is_system' => true],
        ];

        foreach ($categories as $category) {
            RoleCategory::updateOrCreate(
                ['category_code' => $category['category_code']],
                [
                    'category_name' => $category['category_name'],
                    'description' => $category['description'],
                    'display_order' => $category['display_order'],
                    'is_system' => $category['is_system'],
                    'is_active' => true,
                ]
            );
        }
    }
}
