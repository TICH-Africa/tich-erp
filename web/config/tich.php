<?php

return [

    'auth' => [
        'max_login_attempts' => 5,
        'lockout_minutes' => 15,
        'mfa_session_minutes' => 30,
        'mandatory_mfa_user_types' => ['staff', 'student', 'admin', 'external'],
    ],

    'role_hierarchy' => [
        'Applicant' => 10,
        'Student' => 20,
        'Alumni' => 25,
        'Staff' => 30,
        'Lecturer' => 40,
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

    'default_roles' => [
        'student' => 'Student',
        'staff' => 'Staff',
        'external' => 'Applicant',
        'admin' => 'Staff',
    ],

    'permission_aliases' => [
        'dashboard.access' => 'core_manage_campuses_view',
        'admin.access' => 'core_manage_campuses_manage',
        'campuses.read' => 'core_manage_campuses_view',
        'campuses.manage' => 'core_manage_campuses_manage',
        'departments.read' => 'core_manage_departments_view',
        'departments.manage' => 'core_manage_departments_manage',
        'programs.read' => 'core_manage_programs_view',
        'programs.manage' => 'core_manage_programs_manage',
        'users.access.manage' => 'admin_manage_staff_manage',
        'admissions.read' => 'admin_manage_applicants_view',
        'admissions.write' => 'admin_manage_applicants_manage',
        'admissions.approve' => 'admin_manage_applicants_approve',
        'students.read' => 'admin_manage_students_view',
        'students.write' => 'admin_manage_students_manage',
        'roles.assign' => 'admin_manage_staff_manage',
        'audit_logs.read' => 'core_manage_campuses_audit',
    ],

];
