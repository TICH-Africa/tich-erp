<?php

return [

    /*
    | Platform modules shown on user dashboards. Each entry maps to an RBAC
    | permission gate; Super Admin can grant these via user/role assignment.
    */
    'modules' => [
        [
            'key' => 'dashboard',
            'label' => 'Main dashboard',
            'description' => 'Personal landing page after sign-in.',
            'route' => 'dashboard',
            'permission' => 'dashboard.access',
            'category' => 'core',
        ],
        [
            'key' => 'admin_hub',
            'label' => 'Platform administration',
            'description' => 'Campuses, departments, users, and system configuration.',
            'route' => 'admin.index',
            'permission' => 'admin.access',
            'category' => 'core',
        ],
        [
            'key' => 'admissions',
            'label' => 'Approval dashboard',
            'description' => 'Verify, accept, and reject student onboarding applications by department.',
            'route' => 'admissions.dashboard',
            'permission' => 'admissions.read',
            'category' => 'admin',
        ],
        [
            'key' => 'students',
            'label' => 'Student records',
            'description' => 'Enrolled student lifecycle and records.',
            'route' => 'dashboard',
            'permission' => 'students.read',
            'category' => 'admin',
            'coming_soon' => true,
        ],
        [
            'key' => 'academics',
            'label' => 'Application approvals',
            'description' => 'Review and approve onboarding applications for your learning department.',
            'route' => 'admissions.dashboard',
            'permission' => 'admissions.read',
            'category' => 'academics',
        ],
        [
            'key' => 'finance',
            'label' => 'Finance',
            'description' => 'Fees, invoices, payroll, and procurement.',
            'route' => 'dashboard',
            'permission' => 'finance.read',
            'category' => 'finance',
            'coming_soon' => true,
        ],
        [
            'key' => 'hr',
            'label' => 'Human resources',
            'description' => 'Staff contracts, leave, and recruitment.',
            'route' => 'dashboard',
            'permission' => 'hr.read',
            'category' => 'hr',
            'coming_soon' => true,
        ],
        [
            'key' => 'audit',
            'label' => 'Audit logs',
            'description' => 'Security and compliance activity trail.',
            'route' => 'admin.audit-logs.index',
            'permission' => 'audit_logs.read',
            'category' => 'security',
        ],
    ],

    'permission_aliases' => [
        'academics.read' => 'academics_manage_units_view',
        'finance.read' => 'finance_manage_student_accounts_view',
        'hr.read' => 'hr_manage_contracts_view',
    ],

];
