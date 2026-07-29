<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication & MFA
    |--------------------------------------------------------------------------
    */
    'auth' => [
        'max_login_attempts' => 5,
        'lockout_minutes' => 15,
        'mfa_session_minutes' => 30,
        'mandatory_mfa_user_types' => ['staff', 'student', 'admin', 'external'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Role hierarchy (higher index = more authority)
    |--------------------------------------------------------------------------
    */
    'role_hierarchy' => [
        'Applicant' => 10,
        'Student' => 20,
        'Alumni' => 25,
        'Staff' => 30,
        'Lecturer/Tutor' => 40,
        'Admissions Officer' => 45,
        'HOD' => 50,
        'QA Officer' => 55,
        'HR Manager' => 60,
        'Finance Manager' => 65,
        'Academic Registrar' => 70,
        'Dean' => 75,
        'CEO' => 90,
        'Super Admin' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default role assignment on self-registration
    |--------------------------------------------------------------------------
    */
    'default_roles' => [
        'student' => 'Student',
        'staff' => 'Staff',
        'external' => 'Applicant',
        'admin' => 'Staff',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission aliases for route middleware (gate => slug)
    |--------------------------------------------------------------------------
    */
    'permission_aliases' => [
        'admissions.read' => 'admin_manage_applicants_view',
        'admissions.write' => 'admin_manage_applicants_manage',
        'admissions.approve' => 'admin_manage_applicants_approve',
        'students.read' => 'admin_manage_students_view',
        'students.write' => 'admin_manage_students_manage',
        'roles.assign' => 'admin_manage_staff_manage',
        'audit_logs.read' => 'core_manage_campuses_audit',
        'dashboard.access' => 'core_manage_campuses_view',
    ],

];
