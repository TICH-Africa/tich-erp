<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            'core' => ['manage_campuses', 'manage_departments', 'manage_programs', 'manage_units', 'manage_academic_years', 'manage_semesters', 'manage_nursing_blocks'],
            'admin' => ['manage_staff', 'manage_applicants', 'manage_students', 'manage_admissions', 'manage_sacco', 'manage_cafeteria'],
            'academics' => ['manage_units', 'manage_lesson_plans', 'manage_attendance', 'manage_registrations', 'manage_exams', 'manage_grades', 'manage_timetable', 'manage_competencies'],
            'finance' => ['manage_suppliers', 'manage_chart_of_accounts', 'manage_fee_structures', 'manage_student_accounts', 'manage_invoices', 'manage_payments', 'manage_payroll', 'manage_procurement', 'manage_assets', 'manage_inventory'],
            'hr' => ['manage_contracts', 'manage_leave', 'manage_attendance', 'manage_performance', 'manage_qualifications', 'manage_licenses', 'manage_recruitment', 'manage_policies'],
            'portal' => ['manage_content', 'manage_research', 'manage_partnerships', 'manage_blog', 'manage_gallery', 'manage_events'],
            'qa' => ['manage_plans', 'manage_checklists', 'manage_submissions', 'manage_corrective_actions', 'manage_compliance'],
            'notifications' => ['manage_templates', 'send_notifications', 'manage_chatbot'],
            'donations' => ['manage_campaigns', 'manage_donations'],
            'newsletter' => ['manage_subscribers', 'manage_campaigns', 'send_newsletters'],
            'site_settings' => ['manage_settings', 'manage_social_links', 'manage_navigation', 'manage_contact_channels']
        ];

        $categories = ['view', 'create', 'edit', 'delete', 'approve', 'reject', 'export', 'import', 'manage', 'audit'];

        $permissions = [];

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                foreach ($categories as $category) {
                    $permissions[] = [
                        'permission_name' => "{$module}.{$action}.{$category}",
                        'slug' => "{$module}_{$action}_{$category}",
                        'module' => $module,
                        'category' => $category,
                        'description' => "Permission to {$category} {$action} in {$module} module",
                        'is_system' => 1,
                        'created_at' => now()
                    ];
                }
            }
        }

        DB::table('permissions')->insert($permissions);

        // Define default roles
        $roles = [
            ['role_name' => 'Super Admin', 'role_category' => 'executive', 'description' => 'Full system access', 'is_system_role' => 1],
            ['role_name' => 'Principal', 'role_category' => 'executive', 'description' => 'Institution head with full access', 'is_system_role' => 1],
            ['role_name' => 'Dean', 'role_category' => 'academic', 'description' => 'Faculty head with academic oversight', 'is_system_role' => 1],
            ['role_name' => 'HOD', 'role_category' => 'academic', 'description' => 'Department head with departmental access', 'is_system_role' => 1],
            ['role_name' => 'Lecturer', 'role_category' => 'teaching', 'description' => 'Teaching staff with academic functions', 'is_system_role' => 1],
            ['role_name' => 'Finance Manager', 'role_category' => 'administrative', 'description' => 'Financial management access', 'is_system_role' => 1],
            ['role_name' => 'HR Manager', 'role_category' => 'administrative', 'description' => 'Human resources management access', 'is_system_role' => 1],
            ['role_name' => 'QA Officer', 'role_category' => 'administrative', 'description' => 'Quality assurance oversight', 'is_system_role' => 1],
            ['role_name' => 'Student', 'role_category' => 'student', 'description' => 'Student with limited access', 'is_system_role' => 1],
            ['role_name' => 'Staff', 'role_category' => 'administrative', 'description' => 'General staff access', 'is_system_role' => 1],
        ];

        DB::table('roles')->insert($roles);

        // Assign all permissions to Super Admin
        $superAdminId = DB::table('roles')->where('role_name', 'Super Admin')->value('id');
        $allPermissionIds = DB::table('permissions')->pluck('id');

        foreach ($allPermissionIds as $permissionId) {
            DB::table('role_permissions')->insert([
                'role_id' => $superAdminId,
                'permission_id' => $permissionId,
                'granted_at' => now()
            ]);
        }

        // Assign module-specific permissions to other roles
        $this->assignRolePermissions('Principal', ['core', 'admin', 'academics', 'finance', 'hr', 'portal', 'qa']);
        $this->assignRolePermissions('Dean', ['core', 'academics', 'hr', 'portal']);
        $this->assignRolePermissions('HOD', ['core', 'academics', 'hr']);
        $this->assignRolePermissions('Lecturer', ['academics']);
        $this->assignRolePermissions('Finance Manager', ['core', 'finance']);
        $this->assignRolePermissions('HR Manager', ['core', 'hr']);
        $this->assignRolePermissions('QA Officer', ['core', 'qa']);
        $this->assignRolePermissions('Student', ['academics', 'finance', 'portal']);
        $this->assignRolePermissions('Staff', ['core', 'hr']);
    }

    private function assignRolePermissions(string $roleName, array $modules): void
    {
        $roleId = DB::table('roles')->where('role_name', $roleName)->value('id');
        $permissionIds = DB::table('permissions')->whereIn('module', $modules)->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'granted_at' => now()
            ]);
        }
    }
}
