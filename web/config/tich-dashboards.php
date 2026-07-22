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
            'scope' => 'institution',
        ],
        [
            'key' => 'admin_hub',
            'label' => 'Platform administration',
            'description' => 'Campuses, departments, users, and system configuration.',
            'route' => 'admin.index',
            'permission' => 'admin.access',
            'category' => 'core',
            'scope' => 'institution',
        ],
        [
            'key' => 'admissions',
            'label' => 'Approval dashboard',
            'description' => 'Verify, accept, and reject student onboarding applications by department.',
            'route' => 'admissions.dashboard',
            'permission' => 'admissions.read',
            'category' => 'admin',
            'scope' => 'department',
        ],
        [
            'key' => 'students',
            'label' => 'Student records (SIS)',
            'description' => 'Centralized 360° student biodata and enrolment records.',
            'route' => 'sis.students.index',
            'permission' => 'students.read',
            'category' => 'admin',
            'scope' => 'department',
        ],
        [
            'key' => 'academics',
            'label' => 'Application approvals',
            'description' => 'Review and approve onboarding applications for your academic department.',
            'route' => 'admissions.dashboard',
            'permission' => 'admissions.read',
            'category' => 'academics',
            'scope' => 'department',
        ],
        [
            'key' => 'finance',
            'label' => 'Finance',
            'description' => 'Fees, invoices, payroll, and procurement.',
            'route' => 'dashboard',
            'permission' => 'finance.read',
            'category' => 'finance',
            'coming_soon' => true,
            'scope' => 'department',
        ],
        [
            'key' => 'hr',
            'label' => 'Human resources',
            'description' => 'Staff contracts, leave, and recruitment.',
            'route' => 'dashboard',
            'permission' => 'hr.read',
            'category' => 'hr',
            'coming_soon' => true,
            'scope' => 'department',
        ],
        [
            'key' => 'audit',
            'label' => 'Audit logs',
            'description' => 'Security and compliance activity trail.',
            'route' => 'admin.audit-logs.index',
            'permission' => 'audit_logs.read',
            'category' => 'security',
            'scope' => 'institution',
        ],
    ],

    'permission_aliases' => [
        'academics.read' => 'academics_manage_units_view',
        'finance.read' => 'finance_manage_student_accounts_view',
        'hr.read' => 'hr_manage_contracts_view',
    ],

];
