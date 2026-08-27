<?php

/**
 * Predefined module-scoped roles and default permission templates.
 *
 * Each role can grant permissions across one or more RBAC modules via `permission_modules`
 * and permission categories via `permission_categories`. Additional slugs can be listed
 * in `extra_slugs`. Custom roles created in the UI should set `module_key` to scope them.
 *
 * Within a department module, peer roles share the same privilege set unless noted.
 */
return [

    'institution_module_key' => '_institution',

    /** Shared full operational privilege set for department peer roles. */
    'full_ops_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export', 'audit'],

    'modules' => [
        'academics' => [
            'label' => 'Academics',
            'description' => 'Curriculum, units, programmes, attendance, and academic records.',
            'roles' => [
                [
                    'role_name' => 'Head of Academics',
                    'display_name' => 'Head of Academics',
                    'role_category' => 'academic',
                    'description' => 'Institution-wide academic leadership and approvals.',
                    'permission_modules' => ['core', 'academics', 'portal'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export', 'audit'],
                ],
                [
                    'role_name' => 'Academic Registrar',
                    'display_name' => 'Academic Registrar',
                    'role_category' => 'executive',
                    'description' => 'Academic records, registry, and curriculum governance.',
                    'permission_modules' => ['core', 'admin', 'academics'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export', 'audit'],
                ],
                [
                    'role_name' => 'Dean of Students',
                    'display_name' => 'Dean of Students',
                    'role_category' => 'academic',
                    'description' => 'Student welfare lead — receives and handles student complaints, requests, and comments.',
                    'permission_modules' => ['core', 'academics', 'portal', 'admin'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export'],
                ],
                [
                    'role_name' => 'HOD',
                    'display_name' => 'Head of Department (Learning Department)',
                    'role_category' => 'academic',
                    'description' => 'Leads a learning department — programmes, staff, and academic delivery.',
                    'permission_modules' => ['core', 'academics', 'hr'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage'],
                ],
                [
                    'role_name' => 'Lecturer/Tutor',
                    'display_name' => 'Lecturer / Tutor',
                    'role_category' => 'teaching',
                    'description' => 'Teaching delivery, lesson plans, attendance, and grading.',
                    'permission_modules' => ['academics'],
                    'permission_categories' => ['view', 'create', 'edit', 'manage'],
                ],
            ],
        ],
        'finance' => [
            'label' => 'Finance',
            'description' => 'Student accounts, invoicing, payroll integration, and GL.',
            'roles' => [
                [
                    'role_name' => 'Finance Manager',
                    'display_name' => 'Finance Manager',
                    'role_category' => 'administrative',
                    'description' => 'Full finance module leadership and approvals.',
                    'permission_modules' => ['core', 'finance'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export', 'audit'],
                ],
                [
                    'role_name' => 'Assistant Finance Manager',
                    'display_name' => 'Assistant Finance Manager',
                    'role_category' => 'administrative',
                    'description' => 'Full finance module access (same privileges as Finance Manager).',
                    'permission_modules' => ['core', 'finance'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export', 'audit'],
                ],
            ],
        ],
        'hr' => [
            'label' => 'Human resources',
            'description' => 'Staff lifecycle, contracts, leave, payroll inputs, and policies.',
            'roles' => [
                [
                    'role_name' => 'HR Manager',
                    'display_name' => 'Human Resource Manager',
                    'role_category' => 'administrative',
                    'description' => 'Full HR module leadership and approvals.',
                    'permission_modules' => ['core', 'hr'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export', 'audit'],
                ],
                [
                    'role_name' => 'Assistant HR Manager',
                    'display_name' => 'Assistant Human Resource Manager',
                    'role_category' => 'administrative',
                    'description' => 'Full HR module access (same privileges as HR Manager).',
                    'permission_modules' => ['core', 'hr'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export', 'audit'],
                ],
            ],
        ],
        'procurement' => [
            'label' => 'Procurement',
            'description' => 'Suppliers, purchase orders, tenders, inventory, and logistics.',
            'roles' => [
                [
                    'role_name' => 'Procurement Manager',
                    'display_name' => 'Chief Procurement Officer',
                    'role_category' => 'administrative',
                    'description' => 'Procurement leadership and tender approvals.',
                    'permission_modules' => ['core', 'procurement'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export', 'audit'],
                ],
                [
                    'role_name' => 'Assistant Procurement Officer',
                    'display_name' => 'Assistant Procurement Officer',
                    'role_category' => 'administrative',
                    'description' => 'Full procurement access (same privileges as Procurement Manager).',
                    'permission_modules' => ['core', 'procurement'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export', 'audit'],
                ],
                [
                    'role_name' => 'Driver',
                    'display_name' => 'Driver',
                    'role_category' => 'administrative',
                    'description' => 'Logistics and transport under procurement (same module privileges).',
                    'permission_modules' => ['core', 'procurement'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export', 'audit'],
                ],
            ],
        ],
        'qa' => [
            'label' => 'Quality assurance',
            'description' => 'QA plans, compliance, and corrective actions.',
            'roles' => [
                [
                    'role_name' => 'QA Officer',
                    'display_name' => 'Chief QA Officer',
                    'role_category' => 'administrative',
                    'description' => 'Quality assurance leadership and audit oversight.',
                    'permission_modules' => ['core', 'qa'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export', 'audit'],
                ],
                [
                    'role_name' => 'Assistant QA Officer',
                    'display_name' => 'Assistant QA Officer',
                    'role_category' => 'administrative',
                    'description' => 'Full QA access (same privileges as QA Officer).',
                    'permission_modules' => ['core', 'qa'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export', 'audit'],
                ],
            ],
        ],
        'administration' => [
            'label' => 'Administration',
            'description' => 'Registry, facilities, and general services.',
            'roles' => [
                [
                    'role_name' => 'Administration Manager',
                    'display_name' => 'Chief Administrator',
                    'role_category' => 'administrative',
                    'description' => 'Administration module leadership.',
                    'permission_modules' => ['core', 'administration'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export', 'audit'],
                ],
                [
                    'role_name' => 'Assistant Administrator',
                    'display_name' => 'Assistant Administrator',
                    'role_category' => 'administrative',
                    'description' => 'Full administration access (same privileges as Administration Manager).',
                    'permission_modules' => ['core', 'administration'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export', 'audit'],
                ],
            ],
        ],
        'research' => [
            'label' => 'Research',
            'description' => 'Research projects, grants, publications, and ethics.',
            'roles' => [
                [
                    'role_name' => 'Chief Research Officer',
                    'display_name' => 'Chief Research Officer',
                    'role_category' => 'administrative',
                    'description' => 'Research module leadership.',
                    'permission_modules' => ['core', 'research', 'portal'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export', 'audit'],
                ],
                [
                    'role_name' => 'Research Officer',
                    'display_name' => 'Research Officer',
                    'role_category' => 'administrative',
                    'description' => 'Research operations (same privileges as Chief Research Officer).',
                    'permission_modules' => ['core', 'research', 'portal'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export', 'audit'],
                ],
            ],
        ],
        'ict' => [
            'label' => 'ICT',
            'description' => 'Systems, infrastructure, helpdesk, and user access.',
            'roles' => [
                [
                    'role_name' => 'Head of ICT',
                    'display_name' => 'Head of ICT',
                    'role_category' => 'administrative',
                    'description' => 'ICT module leadership and platform support.',
                    'permission_modules' => ['core', 'ict', 'site_settings'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export', 'audit'],
                    'extra_slugs' => ['admin_manage_staff_manage', 'admin_manage_staff_view'],
                ],
                [
                    'role_name' => 'System Administrator',
                    'display_name' => 'System Administrator',
                    'role_category' => 'administrative',
                    'description' => 'Systems administration (same privileges as Head of ICT).',
                    'permission_modules' => ['core', 'ict', 'site_settings'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export', 'audit'],
                    'extra_slugs' => ['admin_manage_staff_manage', 'admin_manage_staff_view'],
                ],
                [
                    'role_name' => 'Technician',
                    'display_name' => 'Technician',
                    'role_category' => 'administrative',
                    'description' => 'ICT technical support (same privileges as Head of ICT).',
                    'permission_modules' => ['core', 'ict', 'site_settings'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export', 'audit'],
                    'extra_slugs' => ['admin_manage_staff_manage', 'admin_manage_staff_view'],
                ],
            ],
        ],
        'admissions' => [
            'label' => 'Admissions',
            'description' => 'Applicant intake, screening, and onboarding.',
            'roles' => [
                [
                    'role_name' => 'Admissions Officer',
                    'display_name' => 'Admissions Officer',
                    'role_category' => 'administrative',
                    'description' => 'Admissions processing and applicant review.',
                    'permission_modules' => ['admin'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export'],
                ],
                [
                    'role_name' => 'Assistant Admissions Officer',
                    'display_name' => 'Assistant Admissions Officer',
                    'role_category' => 'administrative',
                    'description' => 'Supports admissions intake and screening.',
                    'permission_modules' => ['admin'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export'],
                ],
            ],
        ],
        'monitoring_evaluation' => [
            'label' => 'Monitoring & evaluation',
            'description' => 'Institutional M&E frameworks, indicators, reporting, and follow-up.',
            'roles' => [
                [
                    'role_name' => 'Monitoring and Evaluation Officer',
                    'display_name' => 'Monitoring and Evaluation Officer',
                    'role_category' => 'administrative',
                    'description' => 'M&E leadership — frameworks, indicators, and reporting.',
                    'permission_modules' => ['core', 'monitoring_evaluation'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export', 'audit'],
                ],
                [
                    'role_name' => 'Assistant Monitoring and Evaluation Officer',
                    'display_name' => 'Assistant Monitoring and Evaluation Officer',
                    'role_category' => 'administrative',
                    'description' => 'Full M&E access (same privileges as Monitoring and Evaluation Officer).',
                    'permission_modules' => ['core', 'monitoring_evaluation'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export', 'audit'],
                ],
            ],
        ],
        '_institution' => [
            'label' => 'Institution-wide',
            'description' => 'Institution-wide roles without a home department (executive and portal identities).',
            'roles' => [
                [
                    'role_name' => 'Super Admin',
                    'display_name' => 'Super Administrator',
                    'role_category' => 'executive',
                    'description' => 'Full system access across all campuses.',
                    'grants_all' => true,
                    'permission_modules' => [],
                    'permission_categories' => [],
                ],
                [
                    'role_name' => 'CEO',
                    'display_name' => 'Chief Executive Officer',
                    'role_category' => 'executive',
                    'description' => 'Executive oversight with a personal dashboard — not tied to a single department.',
                    'permission_modules' => [
                        'core', 'admin', 'academics', 'finance', 'hr', 'portal', 'qa',
                        'administration', 'procurement', 'research', 'ict', 'monitoring_evaluation',
                    ],
                    'permission_categories' => ['approve', 'view', 'manage', 'audit', 'export'],
                ],
                [
                    'role_name' => 'Dean',
                    'display_name' => 'Dean',
                    'role_category' => 'academic',
                    'description' => 'Faculty head with academic oversight.',
                    'permission_modules' => ['core', 'academics', 'hr', 'portal'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export'],
                ],
                [
                    'role_name' => 'Staff',
                    'display_name' => 'General Staff',
                    'role_category' => 'administrative',
                    'description' => 'General institutional staff access.',
                    'permission_modules' => ['core', 'hr'],
                    'permission_categories' => ['view'],
                ],
                [
                    'role_name' => 'Student',
                    'display_name' => 'Student',
                    'role_category' => 'student',
                    'description' => 'Student portal and academic self-service.',
                    'permission_modules' => ['academics', 'finance', 'portal'],
                    'permission_categories' => ['view'],
                ],
                [
                    'role_name' => 'Alumni',
                    'display_name' => 'Alumni',
                    'role_category' => 'student',
                    'description' => 'Alumni engagement and records access.',
                    'permission_modules' => ['portal'],
                    'permission_categories' => ['view'],
                ],
            ],
        ],
    ],

    'permission_categories' => [
        'view' => 'View / read',
        'create' => 'Create',
        'edit' => 'Edit / update',
        'delete' => 'Delete',
        'approve' => 'Approve',
        'reject' => 'Reject',
        'export' => 'Export',
        'import' => 'Import',
        'manage' => 'Manage (full)',
        'audit' => 'Audit',
    ],

];
