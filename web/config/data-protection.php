<?php

/**
 * Kenya Data Protection Act, 2019 — institutional processing register, retention, and notice metadata.
 * Align collection and storage with lawful purpose, minimisation, and data subject rights (Part IV).
 */
return [
    'framework' => 'Kenya Data Protection Act, 2019',
    'policy_version' => env('DATA_PROTECTION_POLICY_VERSION', '2026-11-10'),

    'controller' => [
        'legal_name' => env('DATA_CONTROLLER_NAME', 'Tropical Institute of Community Health and Development in Africa (TICH in Africa)'),
        'registration_country' => 'Kenya',
        'odpc_registration_number' => env('ODPC_REGISTRATION_NUMBER', ''),
    ],

    'dpo' => [
        'name' => env('DATA_PROTECTION_OFFICER_NAME', ''),
        'email' => env('DATA_PROTECTION_OFFICER_EMAIL', 'dpo@tich.africa'),
        'phone' => env('DATA_PROTECTION_OFFICER_PHONE', ''),
    ],

    'odpc' => [
        'name' => 'Office of the Data Protection Commissioner',
        'website' => 'https://www.odpc.go.ke',
    ],

    'lawful_bases' => [
        'consent' => 'Consent of the data subject (Section 25)',
        'contract' => 'Performance of a contract to which the data subject is party (Section 30(1)(b))',
        'legal_obligation' => 'Compliance with a legal obligation (Section 30(1)(c))',
        'vital_interests' => 'Protection of vital interests (Section 30(1)(d))',
        'public_interest' => 'Performance of a task in the public interest (Section 30(1)(e))',
        'legitimate_interests' => 'Legitimate interests of the data controller (Section 30(1)(f))',
    ],

    /**
     * Record of processing activities (RoPA) — maps platform domains to tables and purposes.
     *
     * @var list<array<string, mixed>>
     */
    'processing_activities' => [
        [
            'activity_key' => 'admissions_online_application',
            'name' => 'Online programme applications',
            'description' => 'Personal data submitted through the public application portal for admission decisions.',
            'data_subjects' => ['prospective students', 'applicants'],
            'data_categories' => ['identity', 'contact', 'demographic', 'education', 'next_of_kin', 'government_identifiers', 'uploaded_documents'],
            'database_tables' => ['applicants', 'application_documents', 'applicant_qualifications'],
            'lawful_basis' => 'consent',
            'purposes' => 'Assess eligibility, communicate about the application, admit students, and meet regulatory record-keeping.',
            'retention' => '7 years after final decision or withdrawal, unless law requires longer.',
            'recipients' => ['admissions staff', 'academic reviewers', 'registrar', 'finance (fees)'],
            'cross_border' => false,
            'security_measures' => 'Role-based access, audit logging, encrypted credentials, private document storage.',
        ],
        [
            'activity_key' => 'student_enrolment',
            'name' => 'Student records (SIS)',
            'description' => 'Ongoing academic, disciplinary, and welfare records for enrolled students.',
            'data_subjects' => ['students'],
            'data_categories' => ['identity', 'contact', 'academic', 'financial', 'disciplinary', 'health_related_where_provided'],
            'database_tables' => ['students', 'student_unit_registrations', 'academic_records', 'student_financial_records'],
            'lawful_basis' => 'contract',
            'purposes' => 'Deliver education, examinations, billing, statutory reporting, and student support.',
            'retention' => 'Duration of study plus 7 years; transcripts and statutory records as required by law.',
            'recipients' => ['academic staff', 'registrar', 'finance', 'student welfare'],
            'cross_border' => false,
            'security_measures' => 'Portal authentication, least-privilege RBAC, audit trail on sensitive changes.',
        ],
        [
            'activity_key' => 'employment_hr',
            'name' => 'Human resources and payroll',
            'description' => 'Staff personal, employment, payroll, leave, attendance, and performance data.',
            'data_subjects' => ['employees', 'job applicants (staff)'],
            'data_categories' => ['identity', 'contact', 'employment', 'financial', 'tax_statutory', 'health_related_where_provided', 'performance'],
            'database_tables' => ['staff', 'staff_contracts', 'leave_requests', 'staff_attendance', 'payroll_runs', 'hr_appraisals'],
            'lawful_basis' => 'contract',
            'purposes' => 'Recruitment, employment relationship, payroll, statutory compliance (e.g. tax, NSSF), workplace administration.',
            'retention' => 'Employment plus 7 years for payroll/tax; other HR records per institutional policy and statute.',
            'recipients' => ['HR', 'line managers', 'finance', 'approved auditors'],
            'cross_border' => false,
            'security_measures' => 'Financial field masking in UI, restricted permissions, audit logs, soft-delete/archive for staff.',
        ],
        [
            'activity_key' => 'platform_accounts',
            'name' => 'User accounts and authentication',
            'description' => 'Login identifiers, session security, MFA metadata, and access roles.',
            'data_subjects' => ['staff', 'students', 'administrators'],
            'data_categories' => ['identity', 'contact', 'authentication', 'access_logs'],
            'database_tables' => ['users', 'user_roles', 'session_tokens', 'audit_logs'],
            'lawful_basis' => 'contract',
            'purposes' => 'Secure access to institutional systems and accountability.',
            'retention' => 'While account is active plus 2 years of security logs.',
            'recipients' => ['ICT', 'authorised system administrators'],
            'cross_border' => false,
            'security_measures' => 'Password hashing, MFA, session limits, tamper-evident audit chain where enabled.',
        ],
        [
            'activity_key' => 'communications',
            'name' => 'Operational communications',
            'description' => 'Email/SMS notifications related to applications, academics, HR, and finance.',
            'data_subjects' => ['applicants', 'students', 'staff'],
            'data_categories' => ['contact', 'message_metadata'],
            'database_tables' => ['communication_logs'],
            'lawful_basis' => 'legitimate_interests',
            'purposes' => 'Service notifications and legally required notices.',
            'retention' => '24 months.',
            'recipients' => ['mail/SMS providers under contract'],
            'cross_border' => false,
            'security_measures' => 'Processor agreements, minimal message content in logs.',
        ],
    ],

    'consent_purposes' => [
        'admissions_application' => [
            'label' => 'Admissions application',
            'lawful_basis' => 'consent',
            'channel' => 'web_apply',
        ],
        'employee_portal' => [
            'label' => 'Employee portal use',
            'lawful_basis' => 'contract',
            'channel' => 'employee_portal',
        ],
    ],

    'retention_defaults' => [
        'admissions_applicant_years' => 7,
        'staff_leaver_years' => 7,
        'security_audit_log_years' => 2,
        'communication_log_months' => 24,
    ],

    'data_subject_request_sla_days' => 30,
];
