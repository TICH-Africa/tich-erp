<?php

/**
 * Predefined module-scoped roles and default permission templates.
 *
 * Each role can grant permissions across one or more RBAC modules via `permission_modules`
 * and permission categories via `permission_categories`. Additional slugs can be listed
 * in `extra_slugs`. Custom roles created in the UI should set `module_key` to scope them.
 */
return [

    'institution_module_key' => '_institution',

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
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export'],
                ],
                [
                    'role_name' => 'Academic Registrar',
                    'display_name' => 'Academic Registrar',
                    'role_category' => 'executive',
                    'description' => 'Academic records, registry, and curriculum governance.',
                    'permission_modules' => ['core', 'admin', 'academics'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export'],
                ],
                [
                    'role_name' => 'HOD',
                    'display_name' => 'Head of Department (Learning Department)',
                    'role_category' => 'academic',
                    'description' => 'Leads a learning department - programmes, staff, and academic delivery.',
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
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export'],
                ],
                [
                    'role_name' => 'Assistant Finance Manager',
                    'display_name' => 'Assistant Finance Manager',
                    'role_category' => 'administrative',
                    'description' => 'Supports finance operations with limited approval authority.',
                    'permission_modules' => ['core', 'finance'],
                    'permission_categories' => ['view', 'create', 'edit', 'export'],
                ],
                [
                    'role_name' => 'Auditor General',
                    'display_name' => 'Auditor General',
                    'role_category' => 'administrative',
                    'description' => 'Read-only finance oversight and audit trail access.',
                    'permission_modules' => ['core', 'finance'],
                    'permission_categories' => ['view', 'audit', 'export'],
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
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export'],
                ],
                [
                    'role_name' => 'Assistant HR Manager',
                    'display_name' => 'Assistant Human Resource Manager',
                    'role_category' => 'administrative',
                    'description' => 'Supports HR operations with limited approval authority.',
                    'permission_modules' => ['core', 'hr'],
                    'permission_categories' => ['view', 'create', 'edit', 'export'],
                ],
            ],
        ],
        'procurement' => [
            'label' => 'Procurement',
            'description' => 'Suppliers, purchase orders, tenders, and inventory.',
            'roles' => [
                [
                    'role_name' => 'Procurement Manager',
                    'display_name' => 'Chief Procurement Officer',
                    'role_category' => 'administrative',
                    'description' => 'Procurement leadership and tender approvals.',
                    'permission_modules' => ['core', 'procurement'],
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'export'],
                ],
                [
                    'role_name' => 'Assistant Procurement Officer',
                    'display_name' => 'Assistant Procurement Officer',
                    'role_category' => 'administrative',
                    'description' => 'Supports procurement processing and supplier management.',
                    'permission_modules' => ['core', 'procurement'],
                    'permission_categories' => ['view', 'create', 'edit', 'export'],
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
                    'permission_categories' => ['view', 'create', 'edit', 'approve', 'manage', 'audit'],
                ],
                [
                    'role_name' => 'Assistant QA Officer',
                    'display_name' => 'Assistant QA Officer',
                    'role_category' => 'administrative',
                    'description' => 'Supports QA reviews and compliance tracking.',
                    'permission_modules' => ['core', 'qa'],
                    'permission_categories' => ['view', 'create', 'edit', 'export'],
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
                    'permission_categories' => ['view', 'create', 'edit', 'manage', 'export'],
                ],
                [
                    'role_name' => 'Assistant Administrator',
                    'display_name' => 'Assistant Administrator',
                    'role_category' => 'administrative',
                    'description' => 'Supports registry and general administration.',
                    'permission_modules' => ['core', 'administration'],
                    'permission_categories' => ['view', 'create', 'edit', 'export'],
                ],
            ],
        ],
        'research' => [
            'label' => 'Research',
            'description' => 'Research projects, grants, publications, and ethics.',
            'roles' => [
                [
                    'role_name' => 'Research Manager',
                    'display_name' => 'Research Director',
                    'role_category' => 'administrative',
                    'description' => 'Research module leadership.',
                    'permission_modules' => ['core', 'research', 'portal'],
                    'permission_categories' => ['view', 'create', 'edit', 'manage', 'export'],
                ],
                [
                    'role_name' => 'Assistant Research Manager',
                    'display_name' => 'Assistant Research Manager',
                    'role_category' => 'administrative',
                    'description' => 'Supports research administration.',
                    'permission_modules' => ['core', 'research', 'portal'],
                    'permission_categories' => ['view', 'create', 'edit', 'export'],
                ],
            ],
        ],
        'ict' => [
            'label' => 'ICT',
            'description' => 'Systems, infrastructure, helpdesk, and user access.',
            'roles' => [
                [
                    'role_name' => 'ICT Manager',
                    'display_name' => 'ICT Manager',
                    'role_category' => 'administrative',
                    'description' => 'ICT module leadership and platform support.',
                    'permission_modules' => ['core', 'ict', 'site_settings'],
                    'permission_categories' => ['view', 'create', 'edit', 'manage', 'export'],
                    'extra_slugs' => ['admin_manage_staff_manage', 'admin_manage_staff_view'],
                ],
                [
                    'role_name' => 'Assistant ICT Manager',
                    'display_name' => 'Assistant ICT Manager',
                    'role_category' => 'administrative',
                    'description' => 'Supports ICT operations and helpdesk.',
                    'permission_modules' => ['core', 'ict', 'site_settings'],
                    'permission_categories' => ['view', 'create', 'edit', 'export'],
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
                    'permission_categories' => ['view', 'create', 'edit', 'export'],
                ],
            ],
        ],
        'platform' => [
            'label' => 'Platform administration',
            'description' => 'Campuses, departments, programmes, and institution setup.',
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
                    'description' => 'Executive oversight across all modules.',
                    'permission_modules' => ['core', 'admin', 'academics', 'finance', 'hr', 'portal', 'qa', 'administration', 'procurement', 'research', 'ict'],
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
