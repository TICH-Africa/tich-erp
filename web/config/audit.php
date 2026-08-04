<?php

return [

    'genesis_hash' => '0000000000000000000000000000000000000000000000000000000000000000',

    'geo_lookup_enabled' => env('AUDIT_GEO_LOOKUP_ENABLED', true),

    'sensitive_keys' => [
        'password', 'password_hash', 'password_confirmation',
        'mfa_secret', 'mfa_secret_temp', 'mfa_backup_codes',
        'token', 'remember_token', 'code', 'otp', 'verification_code',
        'secret', 'backup_codes', 'plainTextToken',
    ],

    'actions' => [
        // Auth
        'auth.login.success' => ['module' => 'auth', 'sensitive' => false],
        'auth.login.failed' => ['module' => 'auth', 'sensitive' => false],
        'auth.login.locked' => ['module' => 'auth', 'sensitive' => false],
        'auth.logout' => ['module' => 'auth', 'sensitive' => false],
        'auth.register' => ['module' => 'auth', 'sensitive' => false],
        'auth.mfa.setup_started' => ['module' => 'auth', 'sensitive' => false],
        'auth.mfa.otp_sent' => ['module' => 'auth', 'sensitive' => false],
        'auth.mfa.verify.success' => ['module' => 'auth', 'sensitive' => false],
        'auth.mfa.verify.failed' => ['module' => 'auth', 'sensitive' => false],
        'auth.mfa.enabled' => ['module' => 'auth', 'sensitive' => false],
        'auth.mfa.disabled' => ['module' => 'auth', 'sensitive' => true],
        'auth.mfa.backup_used' => ['module' => 'auth', 'sensitive' => false],

        // RBAC & security
        'rbac.role.assigned' => ['module' => 'rbac', 'sensitive' => false],
        'rbac.role.revoked' => ['module' => 'rbac', 'sensitive' => false],
        'rbac.role.created' => ['module' => 'rbac', 'sensitive' => false],
        'rbac.role.updated' => ['module' => 'rbac', 'sensitive' => false],
        'rbac.role.deleted' => ['module' => 'rbac', 'sensitive' => false],
        'rbac.permission.assigned' => ['module' => 'rbac', 'sensitive' => false],
        'rbac.permission.revoked' => ['module' => 'rbac', 'sensitive' => false],
        'rbac.role.permissions_synced' => ['module' => 'rbac', 'sensitive' => false],
        'rbac.user.access_synced' => ['module' => 'rbac', 'sensitive' => false],
        'access.denied' => ['module' => 'security', 'sensitive' => false],

        // Audit module
        'audit.view' => ['module' => 'audit', 'sensitive' => false],
        'audit.export' => ['module' => 'audit', 'sensitive' => false],

        // Core admin
        'core.campus.created' => ['module' => 'core', 'sensitive' => false],
        'core.campus.updated' => ['module' => 'core', 'sensitive' => false],
        'core.department.created' => ['module' => 'core', 'sensitive' => false],
        'core.department.updated' => ['module' => 'core', 'sensitive' => false],
        'core.department_group.created' => ['module' => 'core', 'sensitive' => false],
        'core.department_group.updated' => ['module' => 'core', 'sensitive' => false],
        'core.program.created' => ['module' => 'core', 'sensitive' => false],
        'core.program.updated' => ['module' => 'core', 'sensitive' => false],

        // SIS
        'sis.student.enrolled' => ['module' => 'sis', 'sensitive' => false],
        'sis.portal.activated' => ['module' => 'sis', 'sensitive' => false],
        'sis.transcript.generated' => ['module' => 'sis', 'sensitive' => false],

        // Admissions
        'admissions.application.submitted' => ['module' => 'admissions', 'sensitive' => false],
        'admissions.application.shortlisted' => ['module' => 'admissions', 'sensitive' => false],
        'admissions.application.approved' => ['module' => 'admissions', 'sensitive' => false],
        'admissions.application.rejected' => ['module' => 'admissions', 'sensitive' => false],
        'admissions.application.confirmation_sent' => ['module' => 'admissions', 'sensitive' => false],
        'admissions.application.shortlist_email_sent' => ['module' => 'admissions', 'sensitive' => false],
        'admissions.application.status_email_sent' => ['module' => 'admissions', 'sensitive' => false],
        'admissions.application.portal_signup_email_sent' => ['module' => 'admissions', 'sensitive' => false],
        'admissions.application.staff_notified' => ['module' => 'admissions', 'sensitive' => false],

        // Academics — units & programs
        'academics.unit.created' => ['module' => 'academics', 'sensitive' => false],
        'academics.unit.updated' => ['module' => 'academics', 'sensitive' => false],
        'academics.unit.submitted' => ['module' => 'academics', 'sensitive' => false],
        'academics.unit.approved' => ['module' => 'academics', 'sensitive' => false],
        'academics.unit.assessment_weights_updated' => ['module' => 'academics', 'sensitive' => false],
        'academics.department.initialized' => ['module' => 'academics', 'sensitive' => false],
        'academics.department.ceo_approved' => ['module' => 'academics', 'sensitive' => false],
        'academics.department.profile_updated' => ['module' => 'academics', 'sensitive' => false],
        'academics.program.curriculum_format_updated' => ['module' => 'academics', 'sensitive' => false],
        'academics.program.units_synced' => ['module' => 'academics', 'sensitive' => false],
        'academics.unit_allocation.assigned' => ['module' => 'academics', 'sensitive' => false],
        'academics.unit_allocation.removed' => ['module' => 'academics', 'sensitive' => false],

        // Academics — curriculum versions / intakes
        'academics.curriculum_version.created' => ['module' => 'academics', 'sensitive' => false],
        'academics.curriculum_version.units_synced' => ['module' => 'academics', 'sensitive' => false],
        'academics.curriculum_version.unit_added' => ['module' => 'academics', 'sensitive' => false],
        'academics.curriculum_version.reopened' => ['module' => 'academics', 'sensitive' => false],
        'academics.curriculum_version.periods_updated' => ['module' => 'academics', 'sensitive' => false],
        'academics.curriculum_version.submitted' => ['module' => 'academics', 'sensitive' => false],
        'academics.curriculum_version.registrar_approved' => ['module' => 'academics', 'sensitive' => false],
        'academics.curriculum_version.ceo_approved' => ['module' => 'academics', 'sensitive' => false],

        // Academics — calendar & timetables
        'academics.calendar.year_created' => ['module' => 'academics', 'sensitive' => false],
        'academics.calendar.semester_updated' => ['module' => 'academics', 'sensitive' => false],
        'academics.timetable.generated' => ['module' => 'academics', 'sensitive' => false],
        'academics.timetable.published' => ['module' => 'academics', 'sensitive' => false],
        'academics.timetable.session_moved' => ['module' => 'academics', 'sensitive' => false],
        'academics.timetable.session_added' => ['module' => 'academics', 'sensitive' => false],
        'academics.timetable_template.updated' => ['module' => 'academics', 'sensitive' => false],
        'academics.timetable_template.slots_updated' => ['module' => 'academics', 'sensitive' => false],

        // Academics — exams
        'academics.exam_schedule.updated' => ['module' => 'academics', 'sensitive' => false],
        'academics.exam_schedule.synced_from_timetable' => ['module' => 'academics', 'sensitive' => false],

        // Staff portal — lesson plans
        'staff.lesson_plan.created' => ['module' => 'staff', 'sensitive' => false],
        'staff.lesson_plan.updated' => ['module' => 'staff', 'sensitive' => false],
        'staff.lesson_plan.submitted' => ['module' => 'staff', 'sensitive' => false],
        'staff.lesson_plan.verified' => ['module' => 'staff', 'sensitive' => false],
        'staff.lesson_plan.approved' => ['module' => 'staff', 'sensitive' => false],
        'staff.lesson_plan.rejected' => ['module' => 'staff', 'sensitive' => false],
        'staff.lesson_plan.modification_requested' => ['module' => 'staff', 'sensitive' => false],
        'staff.lesson_plan.hod_updated' => ['module' => 'staff', 'sensitive' => false],

        // Staff portal — attendance
        'staff.attendance.session_created' => ['module' => 'staff', 'sensitive' => false],
        'staff.attendance.saved' => ['module' => 'staff', 'sensitive' => false],
        'staff.attendance.sheet_uploaded' => ['module' => 'staff', 'sensitive' => false],
        'staff.attendance.class_photo_uploaded' => ['module' => 'staff', 'sensitive' => false],
        'staff.attendance.sync_timetable' => ['module' => 'staff', 'sensitive' => false],
        'staff.attendance.submitted' => ['module' => 'staff', 'sensitive' => false],
        'staff.attendance.hod_verified' => ['module' => 'staff', 'sensitive' => false],
        'staff.attendance.registrar_verified' => ['module' => 'staff', 'sensitive' => false],
        'staff.attendance.roster_verified' => ['module' => 'staff', 'sensitive' => false],
        'staff.attendance.eligibility_checked' => ['module' => 'staff', 'sensitive' => false],

        // Staff portal — grading & content
        'staff.grading.grid_saved' => ['module' => 'staff', 'sensitive' => false],
        'staff.grading.cat_score_recorded' => ['module' => 'staff', 'sensitive' => false],
        'staff.grading.exam_marks_saved' => ['module' => 'staff', 'sensitive' => false],
        'staff.grading.objective_assessment_created' => ['module' => 'staff', 'sensitive' => false],
        'staff.grading.objective_responses_saved' => ['module' => 'staff', 'sensitive' => false],
        'staff.grading.objective_auto_graded' => ['module' => 'staff', 'sensitive' => false],
        'staff.learning_content.uploaded' => ['module' => 'staff', 'sensitive' => false],
    ],

];
