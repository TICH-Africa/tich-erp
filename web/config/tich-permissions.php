<?php

/**
 * Permission catalog — source of truth in code (not seeded into DB for runtime checks).
 *
 * Slug format: {module}_{action}_{category}
 * Example: finance_manage_student_accounts_view
 */
return [

    'categories' => [
        'view',
        'create',
        'edit',
        'delete',
        'approve',
        'reject',
        'export',
        'import',
        'manage',
        'audit',
    ],

    'modules' => [
        'core' => [
            'manage_campuses',
            'manage_departments',
            'manage_programs',
            'manage_units',
            'manage_academic_years',
            'manage_semesters',
            'manage_nursing_blocks',
        ],
        'admin' => [
            'manage_staff',
            'manage_applicants',
            'manage_students',
            'manage_admissions',
            'manage_sacco',
            'manage_cafeteria',
        ],
        'academics' => [
            'manage_units',
            'manage_lesson_plans',
            'manage_attendance',
            'manage_registrations',
            'manage_exams',
            'manage_grades',
            'manage_timetable',
            'manage_competencies',
        ],
        'finance' => [
            'manage_suppliers',
            'manage_chart_of_accounts',
            'manage_fee_structures',
            'manage_student_accounts',
            'manage_invoices',
            'manage_payments',
            'manage_payroll',
            'manage_procurement',
            'manage_assets',
            'manage_inventory',
        ],
        'hr' => [
            'manage_contracts',
            'manage_leave',
            'manage_attendance',
            'manage_performance',
            'manage_qualifications',
            'manage_licenses',
            'manage_recruitment',
            'manage_policies',
        ],
        'portal' => [
            'manage_content',
            'manage_research',
            'manage_partnerships',
            'manage_blog',
            'manage_gallery',
            'manage_events',
        ],
        'qa' => [
            'manage_plans',
            'manage_checklists',
            'manage_submissions',
            'manage_corrective_actions',
            'manage_compliance',
        ],
        'administration' => [
            'manage_registry',
            'manage_facilities',
            'manage_general_services',
        ],
        'procurement' => [
            'manage_suppliers',
            'manage_purchase_orders',
            'manage_tenders',
            'manage_inventory',
        ],
        'research' => [
            'manage_projects',
            'manage_grants',
            'manage_publications',
            'manage_ethics',
        ],
        'ict' => [
            'manage_helpdesk',
            'manage_assets',
            'manage_infrastructure',
            'manage_systems',
        ],
        'monitoring_evaluation' => [
            'manage_frameworks',
            'manage_indicators',
            'manage_reports',
            'manage_followups',
        ],
        'notifications' => [
            'manage_templates',
            'send_notifications',
            'manage_chatbot',
        ],
        'donations' => [
            'manage_campaigns',
            'manage_donations',
        ],
        'newsletter' => [
            'manage_subscribers',
            'manage_campaigns',
            'send_newsletters',
        ],
        'site_settings' => [
            'manage_settings',
            'manage_social_links',
            'manage_navigation',
            'manage_contact_channels',
        ],
    ],

];
