<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('permissions')->exists()) {
            return;
        }

        $modules = [
            'core' => ['manage_campuses', 'manage_departments', 'manage_programs', 'manage_units', 'manage_academic_years', 'manage_semesters', 'manage_nursing_blocks'],
            'admin' => ['manage_staff', 'manage_applicants', 'manage_students', 'manage_admissions', 'manage_sacco', 'manage_cafeteria'],
            'academics' => ['manage_units', 'manage_lesson_plans', 'manage_attendance', 'manage_registrations', 'manage_exams', 'manage_grades', 'manage_timetable', 'manage_competencies'],
            'finance' => ['manage_suppliers', 'manage_chart_of_accounts', 'manage_fee_structures', 'manage_student_accounts', 'manage_invoices', 'manage_payments', 'manage_payroll', 'manage_procurement', 'manage_assets', 'manage_inventory'],
            'hr' => ['manage_contracts', 'manage_leave', 'manage_attendance', 'manage_performance', 'manage_qualifications', 'manage_licenses', 'manage_recruitment', 'manage_policies'],
            'portal' => ['manage_content', 'manage_research', 'manage_partnerships', 'manage_blog', 'manage_gallery', 'manage_events'],
            'qa' => ['manage_plans', 'manage_checklists', 'manage_submissions', 'manage_corrective_actions', 'manage_compliance'],
            'administration' => ['manage_registry', 'manage_facilities', 'manage_general_services'],
            'procurement' => ['manage_suppliers', 'manage_purchase_orders', 'manage_tenders', 'manage_inventory'],
            'research' => ['manage_projects', 'manage_grants', 'manage_publications', 'manage_ethics'],
            'ict' => ['manage_helpdesk', 'manage_assets', 'manage_infrastructure', 'manage_systems'],
            'notifications' => ['manage_templates', 'send_notifications', 'manage_chatbot'],
            'donations' => ['manage_campaigns', 'manage_donations'],
            'newsletter' => ['manage_subscribers', 'manage_campaigns', 'send_newsletters'],
            'site_settings' => ['manage_settings', 'manage_social_links', 'manage_navigation', 'manage_contact_channels'],
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
                        'created_at' => now(),
                    ];
                }
            }
        }

        DB::table('permissions')->insert($permissions);

        $roles = [
            ['role_name' => 'Super Admin', 'display_name' => 'Super Administrator', 'role_category' => 'executive', 'description' => 'Full system access across all campuses', 'is_system_role' => 1],
            ['role_name' => 'CEO', 'display_name' => 'Chief Executive Officer', 'role_category' => 'executive', 'description' => 'Executive oversight and final approvals', 'is_system_role' => 1],
            ['role_name' => 'Academic Registrar', 'display_name' => 'Academic Registrar', 'role_category' => 'executive', 'description' => 'Academic records, admissions, and student lifecycle', 'is_system_role' => 1],
            ['role_name' => 'Dean', 'display_name' => 'Dean', 'role_category' => 'academic', 'description' => 'Faculty head with academic oversight', 'is_system_role' => 1],
            ['role_name' => 'HOD', 'display_name' => 'Head of Department', 'role_category' => 'academic', 'description' => 'Departmental academic and staff oversight', 'is_system_role' => 1],
            ['role_name' => 'Lecturer/Tutor', 'display_name' => 'Lecturer/Tutor', 'role_category' => 'teaching', 'description' => 'Teaching staff with academic delivery functions', 'is_system_role' => 1],
            ['role_name' => 'Admissions Officer', 'display_name' => 'Admissions Officer', 'role_category' => 'administrative', 'description' => 'Applicant intake, screening, and onboarding', 'is_system_role' => 1],
            ['role_name' => 'Finance Manager', 'display_name' => 'Finance Manager', 'role_category' => 'administrative', 'description' => 'Financial management and approvals', 'is_system_role' => 1],
            ['role_name' => 'HR Manager', 'display_name' => 'HR Manager', 'role_category' => 'administrative', 'description' => 'Human resources management', 'is_system_role' => 1],
            ['role_name' => 'QA Officer', 'display_name' => 'Quality Assurance Officer', 'role_category' => 'administrative', 'description' => 'Quality assurance and compliance oversight', 'is_system_role' => 1],
            ['role_name' => 'Administration Manager', 'display_name' => 'Administration Manager', 'role_category' => 'administrative', 'description' => 'General administration and registry services', 'is_system_role' => 1],
            ['role_name' => 'Procurement Manager', 'display_name' => 'Procurement Manager', 'role_category' => 'administrative', 'description' => 'Procurement, suppliers, and logistics', 'is_system_role' => 1],
            ['role_name' => 'Research Manager', 'display_name' => 'Research Manager', 'role_category' => 'administrative', 'description' => 'Research projects, grants, and publications', 'is_system_role' => 1],
            ['role_name' => 'ICT Manager', 'display_name' => 'ICT Manager', 'role_category' => 'administrative', 'description' => 'Information systems, infrastructure, and IT support', 'is_system_role' => 1],
            ['role_name' => 'Staff', 'display_name' => 'General Staff', 'role_category' => 'administrative', 'description' => 'General institutional staff access', 'is_system_role' => 1],
            ['role_name' => 'Student', 'display_name' => 'Student', 'role_category' => 'student', 'description' => 'Student portal and academic self-service', 'is_system_role' => 1],
            ['role_name' => 'Applicant', 'display_name' => 'Applicant', 'role_category' => 'student', 'description' => 'Pre-admission applicant portal access', 'is_system_role' => 1],
            ['role_name' => 'Alumni', 'display_name' => 'Alumni', 'role_category' => 'student', 'description' => 'Alumni engagement and records access', 'is_system_role' => 1],
        ];

        DB::table('roles')->insert($roles);

        $superAdminId = DB::table('roles')->where('role_name', 'Super Admin')->value('id');
        $allPermissionIds = DB::table('permissions')->pluck('id');

        foreach ($allPermissionIds as $permissionId) {
            DB::table('role_permissions')->insert([
                'role_id' => $superAdminId,
                'permission_id' => $permissionId,
                'granted_at' => now(),
            ]);
        }

        $this->assignRolePermissions('CEO', ['core', 'admin', 'academics', 'finance', 'hr', 'portal', 'qa', 'administration', 'procurement', 'research', 'ict'], ['approve', 'view', 'manage', 'audit', 'export']);
        $this->assignRolePermissions('Academic Registrar', ['core', 'admin', 'academics'], ['approve', 'view', 'create', 'edit', 'manage', 'export']);
        $this->assignRolePermissions('Dean', ['core', 'academics', 'hr', 'portal'], ['view', 'create', 'edit', 'approve', 'manage', 'export']);
        $this->assignRolePermissions('HOD', ['core', 'academics', 'hr'], ['view', 'create', 'edit', 'approve', 'manage']);
        $this->assignRolePermissions('Lecturer/Tutor', ['academics'], ['view', 'create', 'edit', 'manage']);
        $this->assignRolePermissions('Admissions Officer', ['admin'], ['view', 'create', 'edit', 'approve', 'manage', 'export']);
        $this->assignRolePermissions('Finance Manager', ['core', 'finance'], ['view', 'create', 'edit', 'approve', 'manage', 'export']);
        $this->assignRolePermissions('HR Manager', ['core', 'hr'], ['view', 'create', 'edit', 'approve', 'manage', 'export']);
        $this->assignRolePermissions('QA Officer', ['core', 'qa'], ['view', 'create', 'edit', 'approve', 'manage', 'audit']);
        $this->assignRolePermissions('Administration Manager', ['core', 'administration'], ['view', 'create', 'edit', 'manage', 'export']);
        $this->assignRolePermissions('Procurement Manager', ['core', 'procurement'], ['view', 'create', 'edit', 'approve', 'manage', 'export']);
        $this->assignRolePermissions('Research Manager', ['core', 'research', 'portal'], ['view', 'create', 'edit', 'manage', 'export']);
        $this->assignRolePermissions('ICT Manager', ['core', 'ict', 'site_settings'], ['view', 'create', 'edit', 'manage', 'export']);
        $this->grantRolePermissionSlugs('ICT Manager', ['admin_manage_staff_manage', 'admin_manage_staff_view']);
        $this->assignRolePermissions('Student', ['academics', 'finance', 'portal'], ['view']);
        $this->assignRolePermissions('Applicant', ['admin'], ['view', 'create']);
        $this->assignRolePermissions('Alumni', ['portal'], ['view']);
        $this->assignRolePermissions('Staff', ['core', 'hr'], ['view']);
    }

    private function assignRolePermissions(string $roleName, array $modules, ?array $categories = null): void
    {
        $roleId = DB::table('roles')->where('role_name', $roleName)->value('id');

        $query = DB::table('permissions')->whereIn('module', $modules);

        if ($categories) {
            $query->whereIn('category', $categories);
        }

        foreach ($query->pluck('id') as $permissionId) {
            DB::table('role_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'granted_at' => now(),
            ]);
        }
    }

    private function grantRolePermissionSlugs(string $roleName, array $slugs): void
    {
        $roleId = DB::table('roles')->where('role_name', $roleName)->value('id');

        if (! $roleId) {
            return;
        }

        foreach ($slugs as $slug) {
            $permissionId = DB::table('permissions')->where('slug', $slug)->value('id');

            if (! $permissionId) {
                continue;
            }

            DB::table('role_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'granted_at' => now(),
            ]);
        }
    }
}
