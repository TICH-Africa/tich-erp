-- =============================================================================
-- TICH ERP - production schema PATCHES (intentional alters / drops)
-- =============================================================================
-- Run AFTER deploy/production.sql on production when localhost migrations included
-- DROP TABLE or MODIFY COLUMN changes that production.sql cannot apply.
--
-- production.sql is non-destructive (add-only). This file applies the deltas.
-- Safe to re-run: uses IF EXISTS / checks where possible.
--
-- Last updated: 2026-09-18
-- =============================================================================

SET NAMES utf8mb4;
SET time_zone = '+03:00';
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. Drop legacy permission catalog (runtime RBAC is config-only now)
--    Removes 3 tables → production count should match localhost (~197).
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `user_permissions`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `permissions`;

-- -----------------------------------------------------------------------------
-- 2. Invited staff: department not auto-assigned until HR links them
--    production.sql only ADD COLUMN; cannot change NOT NULL → NULL on existing column.
-- -----------------------------------------------------------------------------
ALTER TABLE `staff` MODIFY COLUMN `department_id` bigint(20) unsigned NULL DEFAULT NULL;

-- Clear auto-assigned HR departments on provisional invite staff (no role department yet).
UPDATE `staff` s
INNER JOIN `users` u ON u.staff_id = s.id
SET s.department_id = NULL
WHERE s.employment_status = 'onboarding'
  AND s.job_title = 'Pending assignment'
  AND NOT EXISTS (
      SELECT 1 FROM `user_roles` ur
      WHERE ur.user_id = u.id AND ur.department_id IS NOT NULL
  );

SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET time_zone = '+03:00';

-- -----------------------------------------------------------------------------
-- 3. Staff profile update prompts (HR / ICT request employee profile updates)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_profile_update_prompts` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `staff_id` bigint(20) unsigned NOT NULL,
    `requested_by_user_id` bigint(20) unsigned DEFAULT NULL,
    `requested_via_module` varchar(32) NOT NULL DEFAULT 'hr',
    `requested_fields` json NOT NULL,
    `notes` text DEFAULT NULL,
    `token` varchar(64) NOT NULL,
    `status` varchar(32) NOT NULL DEFAULT 'pending',
    `emailed_at` timestamp NULL DEFAULT NULL,
    `fulfilled_at` timestamp NULL DEFAULT NULL,
    `expires_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `staff_profile_update_prompts_token_unique` (`token`),
    KEY `staff_profile_update_prompts_staff_id_status_index` (`staff_id`, `status`),
    KEY `staff_profile_update_prompts_staff_id_foreign` (`staff_id`),
    KEY `staff_profile_update_prompts_requested_by_user_id_foreign` (`requested_by_user_id`),
    CONSTRAINT `staff_profile_update_prompts_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
    CONSTRAINT `staff_profile_update_prompts_requested_by_user_id_foreign` FOREIGN KEY (`requested_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. Add super_admin user type (platform operators without HR staff records)
-- -----------------------------------------------------------------------------
ALTER TABLE `users` MODIFY COLUMN `user_type` ENUM('student', 'staff', 'admin', 'external', 'super_admin') NOT NULL DEFAULT 'student';

UPDATE `users` u
INNER JOIN `user_roles` ur ON ur.user_id = u.id
INNER JOIN `roles` r ON r.id = ur.role_id
SET u.user_type = 'super_admin'
WHERE r.role_name = 'Super Admin' AND u.user_type = 'admin';

-- -----------------------------------------------------------------------------
-- 5. Retire legacy "Dean" role → academics "Dean of Students"
-- -----------------------------------------------------------------------------
UPDATE `roles`
SET
    `display_name` = 'Dean of Students',
    `description` = 'Administrator who oversees student services, campus life, counseling, and student discipline; responds to student issues.',
    `module_key` = 'academics',
    `role_category` = 'academic'
WHERE `role_name` = 'Dean of Students';

INSERT INTO `user_roles` (`user_id`, `role_id`, `department_id`, `campus_id`, `assigned_at`, `assigned_by`, `expires_at`)
SELECT ur.`user_id`, dos.`id`, ur.`department_id`, ur.`campus_id`, ur.`assigned_at`, ur.`assigned_by`, ur.`expires_at`
FROM `user_roles` ur
INNER JOIN `roles` dean ON dean.`id` = ur.`role_id` AND dean.`role_name` = 'Dean'
INNER JOIN `roles` dos ON dos.`role_name` = 'Dean of Students'
WHERE NOT EXISTS (
    SELECT 1 FROM `user_roles` ur2
    WHERE ur2.`user_id` = ur.`user_id`
      AND ur2.`role_id` = dos.`id`
      AND (ur2.`department_id` <=> ur.`department_id`)
      AND (ur2.`campus_id` <=> ur.`campus_id`)
);

DELETE ur FROM `user_roles` ur
INNER JOIN `roles` dean ON dean.`id` = ur.`role_id` AND dean.`role_name` = 'Dean';

DELETE FROM `roles` WHERE `role_name` = 'Dean';

-- -----------------------------------------------------------------------------
-- 6. Campus type: sub_county_hub → campus (UI labels: Main, Campus, Community College)
-- -----------------------------------------------------------------------------
UPDATE `campuses` SET `campus_type` = 'campus' WHERE `campus_type` = 'sub_county_hub';

-- -----------------------------------------------------------------------------
-- 7. Applicant academic qualification fields (KCSE grade, year, previous institution)
-- -----------------------------------------------------------------------------
ALTER TABLE `applicants`
    ADD COLUMN IF NOT EXISTS `kcse_grade` VARCHAR(20) NULL AFTER `entry_qualification`,
    ADD COLUMN IF NOT EXISTS `kcse_year` SMALLINT UNSIGNED NULL AFTER `kcse_grade`,
    ADD COLUMN IF NOT EXISTS `previous_institution` VARCHAR(200) NULL AFTER `kcse_year`;

-- -----------------------------------------------------------------------------
-- 8. Student registration numbers: prefix must be TICH (not campus code)
-- -----------------------------------------------------------------------------
UPDATE `students`
SET `registration_number` = CONCAT('TICH', SUBSTRING(`registration_number`, LOCATE('/', `registration_number`)))
WHERE `registration_number` NOT LIKE 'TICH/%'
  AND `registration_number` LIKE '%/%';

-- latest patch


-- -----------------------------------------------------------------------------
-- 9. Unit learning content (2026_09_03_000001_create_unit_contents_table)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `unit_contents` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `unit_id` bigint(20) unsigned NOT NULL,
    `unit_allocation_id` bigint(20) unsigned DEFAULT NULL,
    `created_by` bigint(20) unsigned NOT NULL,
    `title` varchar(300) NOT NULL,
    `content_type` varchar(50) NOT NULL DEFAULT 'lesson_note',
    `content_text` text DEFAULT NULL,
    `file_path` varchar(255) DEFAULT NULL,
    `original_filename` varchar(255) DEFAULT NULL,
    `mime_type` varchar(255) DEFAULT NULL,
    `file_size` int(11) DEFAULT NULL,
    `external_url` varchar(255) DEFAULT NULL,
    `status` varchar(50) NOT NULL DEFAULT 'draft',
    `published_at` datetime DEFAULT NULL,
    `available_from` datetime DEFAULT NULL,
    `available_until` datetime DEFAULT NULL,
    `display_order` int(11) NOT NULL DEFAULT 0,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `unit_contents_unit_id_status_display_order_index` (`unit_id`, `status`, `display_order`),
    KEY `unit_contents_unit_allocation_id_foreign` (`unit_allocation_id`),
    KEY `unit_contents_created_by_foreign` (`created_by`),
    CONSTRAINT `unit_contents_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`),
    CONSTRAINT `unit_contents_unit_allocation_id_foreign` FOREIGN KEY (`unit_allocation_id`) REFERENCES `unit_allocations` (`id`) ON DELETE SET NULL,
    CONSTRAINT `unit_contents_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 10. Assignments + submissions (2026_09_03_000002_create_assignments_tables)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `assignments` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `unit_id` bigint(20) unsigned NOT NULL,
    `unit_allocation_id` bigint(20) unsigned NOT NULL,
    `semester_id` bigint(20) unsigned NOT NULL,
    `created_by` bigint(20) unsigned NOT NULL,
    `title` varchar(300) NOT NULL,
    `description` text DEFAULT NULL,
    `instructions` text DEFAULT NULL,
    `attachment_path` varchar(255) DEFAULT NULL,
    `attachment_filename` varchar(255) DEFAULT NULL,
    `mime_type` varchar(255) DEFAULT NULL,
    `file_size` int(11) DEFAULT NULL,
    `max_score` decimal(6,2) NOT NULL DEFAULT 100.00,
    `due_date` datetime DEFAULT NULL,
    `allow_late_submission` tinyint(1) NOT NULL DEFAULT 0,
    `status` varchar(50) NOT NULL DEFAULT 'draft',
    `published_at` datetime DEFAULT NULL,
    `available_from` datetime DEFAULT NULL,
    `submission_instructions` text DEFAULT NULL,
    `display_order` int(11) NOT NULL DEFAULT 0,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `assignments_unit_allocation_id_status_due_date_index` (`unit_allocation_id`, `status`, `due_date`),
    KEY `assignments_unit_id_foreign` (`unit_id`),
    KEY `assignments_semester_id_foreign` (`semester_id`),
    KEY `assignments_created_by_foreign` (`created_by`),
    CONSTRAINT `assignments_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`),
    CONSTRAINT `assignments_unit_allocation_id_foreign` FOREIGN KEY (`unit_allocation_id`) REFERENCES `unit_allocations` (`id`),
    CONSTRAINT `assignments_semester_id_foreign` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`),
    CONSTRAINT `assignments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `assignment_submissions` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `assignment_id` bigint(20) unsigned NOT NULL,
    `student_id` bigint(20) unsigned NOT NULL,
    `submission_text` text DEFAULT NULL,
    `attachment_path` varchar(255) DEFAULT NULL,
    `attachment_filename` varchar(255) DEFAULT NULL,
    `mime_type` varchar(255) DEFAULT NULL,
    `file_size` int(11) DEFAULT NULL,
    `submitted_at` datetime DEFAULT NULL,
    `grade` decimal(6,2) DEFAULT NULL,
    `feedback` text DEFAULT NULL,
    `graded_by` bigint(20) unsigned DEFAULT NULL,
    `graded_at` datetime DEFAULT NULL,
    `status` varchar(50) NOT NULL DEFAULT 'pending',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `assignment_sub_unique` (`assignment_id`, `student_id`),
    KEY `assignment_submissions_student_id_status_index` (`student_id`, `status`),
    KEY `assignment_submissions_graded_by_foreign` (`graded_by`),
    CONSTRAINT `assignment_submissions_assignment_id_foreign` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
    CONSTRAINT `assignment_submissions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
    CONSTRAINT `assignment_submissions_graded_by_foreign` FOREIGN KEY (`graded_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 11. CAT time windows on objective assessments (2026_09_03_000003)
-- -----------------------------------------------------------------------------
ALTER TABLE `objective_assessments`
    ADD COLUMN IF NOT EXISTS `time_limit_minutes` int(11) DEFAULT NULL AFTER `max_score`,
    ADD COLUMN IF NOT EXISTS `available_from` datetime DEFAULT NULL AFTER `time_limit_minutes`,
    ADD COLUMN IF NOT EXISTS `available_until` datetime DEFAULT NULL AFTER `available_from`,
    ADD COLUMN IF NOT EXISTS `show_results_immediately` tinyint(1) NOT NULL DEFAULT 1 AFTER `available_until`,
    ADD COLUMN IF NOT EXISTS `allow_multiple_attempts` tinyint(1) NOT NULL DEFAULT 0 AFTER `show_results_immediately`,
    ADD COLUMN IF NOT EXISTS `max_attempts` smallint(5) unsigned DEFAULT NULL AFTER `allow_multiple_attempts`,
    ADD COLUMN IF NOT EXISTS `student_started_at` datetime DEFAULT NULL AFTER `max_attempts`,
    ADD COLUMN IF NOT EXISTS `student_submitted_at` datetime DEFAULT NULL AFTER `student_started_at`,
    ADD COLUMN IF NOT EXISTS `time_taken_seconds` int(11) DEFAULT NULL AFTER `student_submitted_at`;

-- -----------------------------------------------------------------------------
-- 12. CAT submission tracking on objective submissions (2026_09_03_000004)
-- -----------------------------------------------------------------------------
ALTER TABLE `objective_submissions`
    ADD COLUMN IF NOT EXISTS `student_started_at` datetime DEFAULT NULL AFTER `updated_at`,
    ADD COLUMN IF NOT EXISTS `student_submitted_at` datetime DEFAULT NULL AFTER `student_started_at`,
    ADD COLUMN IF NOT EXISTS `time_taken_seconds` int(11) DEFAULT NULL AFTER `student_submitted_at`,
    ADD COLUMN IF NOT EXISTS `attempt_number` smallint(5) unsigned NOT NULL DEFAULT 1 AFTER `time_taken_seconds`;

-- -----------------------------------------------------------------------------
-- 13. Student portal capability tables (2026_09_04_100000)
-- -----------------------------------------------------------------------------
ALTER TABLE `applicants`
    ADD COLUMN IF NOT EXISTS `nationality` varchar(100) DEFAULT NULL AFTER `gender`,
    ADD COLUMN IF NOT EXISTS `postal_address` varchar(255) DEFAULT NULL AFTER `home_county`;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `email` varchar(255) NOT NULL,
    `token` varchar(255) NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_attempts` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `email` varchar(191) NOT NULL,
    `user_id` bigint(20) unsigned DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `status` varchar(30) NOT NULL DEFAULT 'sent',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `password_reset_attempts_email_index` (`email`),
    KEY `password_reset_attempts_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_escalations` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) unsigned DEFAULT NULL,
    `email` varchar(191) NOT NULL,
    `status` varchar(30) NOT NULL DEFAULT 'open',
    `attempt_count` smallint(5) unsigned NOT NULL DEFAULT 0,
    `notes` text DEFAULT NULL,
    `resolved_by_user_id` bigint(20) unsigned DEFAULT NULL,
    `resolved_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `password_reset_escalations_user_id_index` (`user_id`),
    KEY `password_reset_escalations_email_index` (`email`),
    KEY `password_reset_escalations_resolved_by_user_id_foreign` (`resolved_by_user_id`),
    CONSTRAINT `password_reset_escalations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `password_reset_escalations_resolved_by_user_id_foreign` FOREIGN KEY (`resolved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_profile_change_requests` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `student_id` bigint(20) unsigned NOT NULL,
    `requested_by_user_id` bigint(20) unsigned NOT NULL,
    `request_type` varchar(50) NOT NULL DEFAULT 'profile_update',
    `status` varchar(30) NOT NULL DEFAULT 'pending',
    `current_snapshot` json DEFAULT NULL,
    `proposed_changes` json NOT NULL,
    `attachment_path` varchar(500) DEFAULT NULL,
    `student_notes` text DEFAULT NULL,
    `reviewer_notes` text DEFAULT NULL,
    `rejection_reason` text DEFAULT NULL,
    `reviewed_by_user_id` bigint(20) unsigned DEFAULT NULL,
    `reviewed_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `student_profile_change_requests_student_id_status_index` (`student_id`, `status`),
    KEY `student_profile_change_requests_status_created_at_index` (`status`, `created_at`),
    KEY `student_profile_change_requests_requested_by_user_id_foreign` (`requested_by_user_id`),
    KEY `student_profile_change_requests_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
    CONSTRAINT `student_profile_change_requests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    CONSTRAINT `student_profile_change_requests_requested_by_user_id_foreign` FOREIGN KEY (`requested_by_user_id`) REFERENCES `users` (`id`),
    CONSTRAINT `student_profile_change_requests_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_transcript_requests` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `student_id` bigint(20) unsigned NOT NULL,
    `requested_by_user_id` bigint(20) unsigned NOT NULL,
    `status` varchar(30) NOT NULL DEFAULT 'pending',
    `delivery_method` varchar(30) NOT NULL DEFAULT 'download',
    `student_notes` text DEFAULT NULL,
    `registrar_notes` text DEFAULT NULL,
    `issued_document_path` varchar(500) DEFAULT NULL,
    `reviewed_by_user_id` bigint(20) unsigned DEFAULT NULL,
    `reviewed_at` timestamp NULL DEFAULT NULL,
    `issued_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `student_transcript_requests_status_created_at_index` (`status`, `created_at`),
    KEY `student_transcript_requests_student_id_foreign` (`student_id`),
    KEY `student_transcript_requests_requested_by_user_id_foreign` (`requested_by_user_id`),
    KEY `student_transcript_requests_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
    CONSTRAINT `student_transcript_requests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    CONSTRAINT `student_transcript_requests_requested_by_user_id_foreign` FOREIGN KEY (`requested_by_user_id`) REFERENCES `users` (`id`),
    CONSTRAINT `student_transcript_requests_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_lifecycle_requests` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `student_id` bigint(20) unsigned NOT NULL,
    `requested_by_user_id` bigint(20) unsigned NOT NULL,
    `request_type` varchar(50) NOT NULL,
    `status` varchar(30) NOT NULL DEFAULT 'pending',
    `registrar_status` varchar(30) NOT NULL DEFAULT 'pending',
    `dean_status` varchar(30) NOT NULL DEFAULT 'pending',
    `effective_date` date DEFAULT NULL,
    `deferment_months` smallint(5) unsigned DEFAULT NULL,
    `reason` text DEFAULT NULL,
    `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
    `reviewer_notes` text DEFAULT NULL,
    `registrar_notes` text DEFAULT NULL,
    `dean_notes` text DEFAULT NULL,
    `reviewed_by_user_id` bigint(20) unsigned DEFAULT NULL,
    `registrar_reviewed_by_user_id` bigint(20) unsigned DEFAULT NULL,
    `dean_reviewed_by_user_id` bigint(20) unsigned DEFAULT NULL,
    `reviewed_at` timestamp NULL DEFAULT NULL,
    `registrar_reviewed_at` timestamp NULL DEFAULT NULL,
    `dean_reviewed_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `student_lifecycle_requests_student_id_status_index` (`student_id`, `status`),
    KEY `student_lifecycle_requests_requested_by_user_id_foreign` (`requested_by_user_id`),
    KEY `student_lifecycle_requests_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
    KEY `student_lifecycle_requests_registrar_reviewed_by_user_id_foreign` (`registrar_reviewed_by_user_id`),
    KEY `student_lifecycle_requests_dean_reviewed_by_user_id_foreign` (`dean_reviewed_by_user_id`),
    CONSTRAINT `student_lifecycle_requests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    CONSTRAINT `student_lifecycle_requests_requested_by_user_id_foreign` FOREIGN KEY (`requested_by_user_id`) REFERENCES `users` (`id`),
    CONSTRAINT `student_lifecycle_requests_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `student_lifecycle_requests_registrar_reviewed_by_user_id_foreign` FOREIGN KEY (`registrar_reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `student_lifecycle_requests_dean_reviewed_by_user_id_foreign` FOREIGN KEY (`dean_reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `course_evaluation_windows` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(191) NOT NULL,
    `semester_id` bigint(20) unsigned DEFAULT NULL,
    `opens_at` datetime NOT NULL,
    `closes_at` datetime NOT NULL,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `course_evaluation_windows_semester_id_index` (`semester_id`),
    KEY `course_evaluation_windows_created_by_user_id_foreign` (`created_by_user_id`),
    CONSTRAINT `course_evaluation_windows_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `course_evaluations` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `window_id` bigint(20) unsigned NOT NULL,
    `student_id` bigint(20) unsigned NOT NULL,
    `unit_id` bigint(20) unsigned DEFAULT NULL,
    `staff_id` bigint(20) unsigned DEFAULT NULL,
    `rating` tinyint(3) unsigned DEFAULT NULL,
    `responses` json DEFAULT NULL,
    `comments` text DEFAULT NULL,
    `submitted_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `course_eval_unique` (`window_id`, `student_id`, `unit_id`),
    KEY `course_evaluations_student_id_foreign` (`student_id`),
    CONSTRAINT `course_evaluations_window_id_foreign` FOREIGN KEY (`window_id`) REFERENCES `course_evaluation_windows` (`id`) ON DELETE CASCADE,
    CONSTRAINT `course_evaluations_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_document_requests` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `student_id` bigint(20) unsigned NOT NULL,
    `requested_by_user_id` bigint(20) unsigned NOT NULL,
    `document_type` varchar(80) NOT NULL,
    `status` varchar(30) NOT NULL DEFAULT 'pending',
    `student_notes` text DEFAULT NULL,
    `reviewer_notes` text DEFAULT NULL,
    `issued_document_path` varchar(500) DEFAULT NULL,
    `reviewed_by_user_id` bigint(20) unsigned DEFAULT NULL,
    `reviewed_at` timestamp NULL DEFAULT NULL,
    `issued_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `student_document_requests_status_created_at_index` (`status`, `created_at`),
    KEY `student_document_requests_student_id_foreign` (`student_id`),
    KEY `student_document_requests_requested_by_user_id_foreign` (`requested_by_user_id`),
    KEY `student_document_requests_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
    CONSTRAINT `student_document_requests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    CONSTRAINT `student_document_requests_requested_by_user_id_foreign` FOREIGN KEY (`requested_by_user_id`) REFERENCES `users` (`id`),
    CONSTRAINT `student_document_requests_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_clearance_items` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `student_id` bigint(20) unsigned NOT NULL,
    `department_key` varchar(50) NOT NULL,
    `label` varchar(120) NOT NULL,
    `status` varchar(30) NOT NULL DEFAULT 'pending',
    `notes` text DEFAULT NULL,
    `cleared_by_user_id` bigint(20) unsigned DEFAULT NULL,
    `cleared_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `student_clearance_items_student_id_department_key_unique` (`student_id`, `department_key`),
    KEY `student_clearance_items_cleared_by_user_id_foreign` (`cleared_by_user_id`),
    CONSTRAINT `student_clearance_items_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    CONSTRAINT `student_clearance_items_cleared_by_user_id_foreign` FOREIGN KEY (`cleared_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_notifications` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `student_id` bigint(20) unsigned NOT NULL,
    `category` varchar(50) NOT NULL DEFAULT 'general',
    `title` varchar(191) NOT NULL,
    `body` text DEFAULT NULL,
    `action_url` varchar(500) DEFAULT NULL,
    `read_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `student_notifications_student_id_index` (`student_id`),
    KEY `student_notifications_student_id_read_at_index` (`student_id`, `read_at`),
    CONSTRAINT `student_notifications_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 14. Special / supplementary exam sitting request enhancements
--     (2026_09_04_120000_enhance_exam_sitting_requests)
-- -----------------------------------------------------------------------------
ALTER TABLE `special_exam_requests`
    MODIFY COLUMN `exam_result_id` bigint(20) unsigned NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `unit_id` bigint(20) unsigned NULL DEFAULT NULL AFTER `exam_result_id`,
    ADD COLUMN IF NOT EXISTS `semester_id` bigint(20) unsigned NULL DEFAULT NULL AFTER `unit_id`,
    ADD COLUMN IF NOT EXISTS `student_notes` text NULL DEFAULT NULL AFTER `reason`,
    ADD COLUMN IF NOT EXISTS `reviewed_notes` text NULL DEFAULT NULL AFTER `reviewed_at`;

ALTER TABLE `supplementary_requests`
    MODIFY COLUMN `exam_result_id` bigint(20) unsigned NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `unit_id` bigint(20) unsigned NULL DEFAULT NULL AFTER `exam_result_id`,
    ADD COLUMN IF NOT EXISTS `semester_id` bigint(20) unsigned NULL DEFAULT NULL AFTER `unit_id`,
    ADD COLUMN IF NOT EXISTS `student_notes` text NULL DEFAULT NULL AFTER `application_status`,
    ADD COLUMN IF NOT EXISTS `reviewed_by` bigint(20) unsigned NULL DEFAULT NULL AFTER `student_notes`,
    ADD COLUMN IF NOT EXISTS `reviewed_at` datetime NULL DEFAULT NULL AFTER `reviewed_by`,
    ADD COLUMN IF NOT EXISTS `reviewed_notes` text NULL DEFAULT NULL AFTER `reviewed_at`;

-- Indexes / FKs (ignore errors if already present when re-run without IF NOT EXISTS helpers)
SET @tich_fk_sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = 'special_exam_requests'
              AND CONSTRAINT_NAME = 'special_exam_requests_unit_id_foreign'
        ),
        'SELECT 1',
        'ALTER TABLE `special_exam_requests` ADD CONSTRAINT `special_exam_requests_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL'
    )
);
PREPARE tich_fk_stmt FROM @tich_fk_sql; EXECUTE tich_fk_stmt; DEALLOCATE PREPARE tich_fk_stmt;

SET @tich_fk_sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = 'special_exam_requests'
              AND CONSTRAINT_NAME = 'special_exam_requests_semester_id_foreign'
        ),
        'SELECT 1',
        'ALTER TABLE `special_exam_requests` ADD CONSTRAINT `special_exam_requests_semester_id_foreign` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE SET NULL'
    )
);
PREPARE tich_fk_stmt FROM @tich_fk_sql; EXECUTE tich_fk_stmt; DEALLOCATE PREPARE tich_fk_stmt;

SET @tich_fk_sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = 'supplementary_requests'
              AND CONSTRAINT_NAME = 'supplementary_requests_unit_id_foreign'
        ),
        'SELECT 1',
        'ALTER TABLE `supplementary_requests` ADD CONSTRAINT `supplementary_requests_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL'
    )
);
PREPARE tich_fk_stmt FROM @tich_fk_sql; EXECUTE tich_fk_stmt; DEALLOCATE PREPARE tich_fk_stmt;

SET @tich_fk_sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = 'supplementary_requests'
              AND CONSTRAINT_NAME = 'supplementary_requests_semester_id_foreign'
        ),
        'SELECT 1',
        'ALTER TABLE `supplementary_requests` ADD CONSTRAINT `supplementary_requests_semester_id_foreign` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE SET NULL'
    )
);
PREPARE tich_fk_stmt FROM @tich_fk_sql; EXECUTE tich_fk_stmt; DEALLOCATE PREPARE tich_fk_stmt;

SET @tich_fk_sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = 'supplementary_requests'
              AND CONSTRAINT_NAME = 'supplementary_requests_reviewed_by_foreign'
        ),
        'SELECT 1',
        'ALTER TABLE `supplementary_requests` ADD CONSTRAINT `supplementary_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL'
    )
);
PREPARE tich_fk_stmt FROM @tich_fk_sql; EXECUTE tich_fk_stmt; DEALLOCATE PREPARE tich_fk_stmt;

-- -----------------------------------------------------------------------------
-- 15. Deferment dual-approval + attachments
--     (2026_09_04_220000_enhance_deferment_and_exam_request_reviews)
-- -----------------------------------------------------------------------------
ALTER TABLE `student_lifecycle_requests`
    ADD COLUMN IF NOT EXISTS `registrar_status` varchar(30) NOT NULL DEFAULT 'pending' AFTER `status`,
    ADD COLUMN IF NOT EXISTS `dean_status` varchar(30) NOT NULL DEFAULT 'pending' AFTER `registrar_status`,
    ADD COLUMN IF NOT EXISTS `deferment_months` smallint(5) unsigned NULL DEFAULT NULL AFTER `effective_date`,
    ADD COLUMN IF NOT EXISTS `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL CHECK (json_valid(`attachments`)) AFTER `reason`,
    ADD COLUMN IF NOT EXISTS `registrar_notes` text NULL DEFAULT NULL AFTER `reviewer_notes`,
    ADD COLUMN IF NOT EXISTS `dean_notes` text NULL DEFAULT NULL AFTER `registrar_notes`,
    ADD COLUMN IF NOT EXISTS `registrar_reviewed_by_user_id` bigint(20) unsigned NULL DEFAULT NULL AFTER `dean_notes`,
    ADD COLUMN IF NOT EXISTS `dean_reviewed_by_user_id` bigint(20) unsigned NULL DEFAULT NULL AFTER `registrar_reviewed_by_user_id`,
    ADD COLUMN IF NOT EXISTS `registrar_reviewed_at` timestamp NULL DEFAULT NULL AFTER `dean_reviewed_by_user_id`,
    ADD COLUMN IF NOT EXISTS `dean_reviewed_at` timestamp NULL DEFAULT NULL AFTER `registrar_reviewed_at`;

-- Backfill review lanes for existing open deferment rows
UPDATE `student_lifecycle_requests`
SET `registrar_status` = CASE
        WHEN `status` IN ('approved', 'rejected') THEN `status`
        ELSE COALESCE(NULLIF(`registrar_status`, ''), 'pending')
    END,
    `dean_status` = CASE
        WHEN `status` IN ('approved', 'rejected') THEN `status`
        ELSE COALESCE(NULLIF(`dean_status`, ''), 'pending')
    END
WHERE `request_type` = 'deferment';

SET @tich_fk_sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = 'student_lifecycle_requests'
              AND CONSTRAINT_NAME = 'student_lifecycle_requests_registrar_reviewed_by_user_id_foreign'
        ),
        'SELECT 1',
        'ALTER TABLE `student_lifecycle_requests` ADD CONSTRAINT `student_lifecycle_requests_registrar_reviewed_by_user_id_foreign` FOREIGN KEY (`registrar_reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL'
    )
);
PREPARE tich_fk_stmt FROM @tich_fk_sql; EXECUTE tich_fk_stmt; DEALLOCATE PREPARE tich_fk_stmt;

SET @tich_fk_sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = 'student_lifecycle_requests'
              AND CONSTRAINT_NAME = 'student_lifecycle_requests_dean_reviewed_by_user_id_foreign'
        ),
        'SELECT 1',
        'ALTER TABLE `student_lifecycle_requests` ADD CONSTRAINT `student_lifecycle_requests_dean_reviewed_by_user_id_foreign` FOREIGN KEY (`dean_reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL'
    )
);
PREPARE tich_fk_stmt FROM @tich_fk_sql; EXECUTE tich_fk_stmt; DEALLOCATE PREPARE tich_fk_stmt;

-- -----------------------------------------------------------------------------
-- 16. In-app notification deep links
--     (2026_09_05_140000_add_action_url_to_notifications_table)
-- -----------------------------------------------------------------------------
ALTER TABLE `notifications`
    ADD COLUMN IF NOT EXISTS `action_url` varchar(500) NULL DEFAULT NULL AFTER `related_entity_id`;

-- -----------------------------------------------------------------------------
-- 17. Legal CMS pages (Privacy Policy & Terms and Conditions)
--     (2026_09_07_120000_create_cms_pages_table)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cms_pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) NOT NULL,
  `title` varchar(300) NOT NULL,
  `body` longtext NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `seo_meta_title` varchar(300) DEFAULT NULL,
  `seo_meta_description` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cms_pages_slug_unique` (`slug`),
  KEY `cms_pages_created_by_foreign` (`created_by`),
  KEY `cms_pages_updated_by_foreign` (`updated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @tich_fk_sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = 'cms_pages'
              AND CONSTRAINT_NAME = 'cms_pages_created_by_foreign'
        ),
        'SELECT 1',
        'ALTER TABLE `cms_pages` ADD CONSTRAINT `cms_pages_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL'
    )
);
PREPARE tich_fk_stmt FROM @tich_fk_sql; EXECUTE tich_fk_stmt; DEALLOCATE PREPARE tich_fk_stmt;

SET @tich_fk_sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = 'cms_pages'
              AND CONSTRAINT_NAME = 'cms_pages_updated_by_foreign'
        ),
        'SELECT 1',
        'ALTER TABLE `cms_pages` ADD CONSTRAINT `cms_pages_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL'
    )
);
PREPARE tich_fk_stmt FROM @tich_fk_sql; EXECUTE tich_fk_stmt; DEALLOCATE PREPARE tich_fk_stmt;

INSERT INTO `cms_pages` (`slug`, `title`, `body`, `status`, `published_at`, `seo_meta_title`, `seo_meta_description`, `created_at`, `updated_at`)
SELECT * FROM (
    SELECT
        'privacy' AS `slug`,
        'Privacy Policy' AS `title`,
        '<p>This Privacy Policy explains how TICH in Africa collects, uses, and protects personal information when you use our website and institutional platforms.</p><p>We collect information you provide during applications, registration, onboarding, and account use. We use that information to deliver education services, manage admissions and employment processes, communicate with you, and meet legal obligations.</p><p>We do not sell personal data. Access is limited to authorised staff and service providers who need it to support institutional operations. You may contact us to request access to or correction of your personal information where applicable.</p><p>This policy may be updated from time to time. The version published on this page is the current version.</p>' AS `body`,
        'published' AS `status`,
        NOW() AS `published_at`,
        'Privacy Policy' AS `seo_meta_title`,
        'How TICH in Africa collects, uses, and protects personal information.' AS `seo_meta_description`,
        NOW() AS `created_at`,
        NOW() AS `updated_at`
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `cms_pages` WHERE `slug` = 'privacy');

INSERT INTO `cms_pages` (`slug`, `title`, `body`, `status`, `published_at`, `seo_meta_title`, `seo_meta_description`, `created_at`, `updated_at`)
SELECT * FROM (
    SELECT
        'terms' AS `slug`,
        'Terms and Conditions' AS `title`,
        '<p>These Terms and Conditions govern your use of the TICH in Africa website and institutional platforms, including applications, student and staff portals, and related online services.</p><p>By creating an account, completing onboarding, or submitting an application, you agree to provide accurate information and to use the platform only for lawful institutional purposes.</p><p>Accounts and access credentials are personal. You are responsible for keeping them secure and for activity under your account. Content and materials on the platform remain the property of TICH in Africa or their respective owners.</p><p>We may update these terms periodically. Continued use of the platform after updates constitutes acceptance of the revised terms.</p>' AS `body`,
        'published' AS `status`,
        NOW() AS `published_at`,
        'Terms and Conditions' AS `seo_meta_title`,
        'Terms governing use of TICH in Africa websites and institutional platforms.' AS `seo_meta_description`,
        NOW() AS `created_at`,
        NOW() AS `updated_at`
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `cms_pages` WHERE `slug` = 'terms');

-- -----------------------------------------------------------------------------
-- 18. QA assessment lifecycle + capacity building registry
--     (2026_09_07_140000_enhance_qa_plans_and_capacity_sessions)
-- -----------------------------------------------------------------------------
ALTER TABLE `qa_plans`
    ADD COLUMN IF NOT EXISTS `description` text NULL DEFAULT NULL AFTER `plan_name`,
    ADD COLUMN IF NOT EXISTS `instructions` text NULL DEFAULT NULL AFTER `description`,
    ADD COLUMN IF NOT EXISTS `due_at` datetime NULL DEFAULT NULL AFTER `period_end`,
    ADD COLUMN IF NOT EXISTS `pass_threshold` decimal(5,2) NOT NULL DEFAULT 70.00 AFTER `due_at`,
    ADD COLUMN IF NOT EXISTS `dispatched_at` datetime NULL DEFAULT NULL AFTER `deployed_at`,
    ADD COLUMN IF NOT EXISTS `compiled_at` datetime NULL DEFAULT NULL AFTER `dispatched_at`,
    ADD COLUMN IF NOT EXISTS `created_by` bigint(20) unsigned NULL DEFAULT NULL AFTER `compiled_at`;

ALTER TABLE `qa_plans`
    MODIFY COLUMN `deployed_by` bigint(20) unsigned NULL DEFAULT NULL,
    MODIFY COLUMN `deployed_at` datetime NULL DEFAULT NULL,
    MODIFY COLUMN `status` varchar(50) NOT NULL DEFAULT 'draft';

SET @tich_fk_sql := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = 'qa_plans'
              AND CONSTRAINT_NAME = 'qa_plans_created_by_foreign'
        ),
        'SELECT 1',
        'ALTER TABLE `qa_plans` ADD CONSTRAINT `qa_plans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL'
    )
);
PREPARE tich_fk_stmt FROM @tich_fk_sql; EXECUTE tich_fk_stmt; DEALLOCATE PREPARE tich_fk_stmt;

CREATE TABLE IF NOT EXISTS `qa_capacity_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(300) NOT NULL,
  `description` text DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `audience` varchar(300) DEFAULT NULL,
  `location` varchar(300) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'scheduled',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `qa_capacity_sessions_created_by_foreign` (`created_by`),
  CONSTRAINT `qa_capacity_sessions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 19. Monitoring & Evaluation module
--     (2026_09_08_100000_create_monitoring_evaluation_tables)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `me_policies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fiscal_year` varchar(20) NOT NULL,
  `title` varchar(300) NOT NULL,
  `version` varchar(50) DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `description` text DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `me_policies_fiscal_year_status_index` (`fiscal_year`,`status`),
  KEY `me_policies_uploaded_by_foreign` (`uploaded_by`),
  CONSTRAINT `me_policies_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `me_policy_signoffs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `policy_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `staff_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `signed_name` varchar(200) NOT NULL,
  `employee_number` varchar(100) DEFAULT NULL,
  `signature` text DEFAULT NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `signed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `me_policy_signoffs_unique` (`policy_id`,`department_id`,`staff_id`),
  KEY `me_policy_signoffs_department_id_foreign` (`department_id`),
  KEY `me_policy_signoffs_staff_id_foreign` (`staff_id`),
  KEY `me_policy_signoffs_user_id_foreign` (`user_id`),
  CONSTRAINT `me_policy_signoffs_policy_id_foreign` FOREIGN KEY (`policy_id`) REFERENCES `me_policies` (`id`),
  CONSTRAINT `me_policy_signoffs_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `me_policy_signoffs_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`),
  CONSTRAINT `me_policy_signoffs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `me_technical_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `budget_request_id` bigint(20) unsigned DEFAULT NULL,
  `planning_cycle_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `title` varchar(300) NOT NULL,
  `fiscal_year` varchar(20) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `summary` text DEFAULT NULL,
  `submitted_by` bigint(20) unsigned DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `me_reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `me_reviewed_at` datetime DEFAULT NULL,
  `me_notes` text DEFAULT NULL,
  `baseline_locked_by` bigint(20) unsigned DEFAULT NULL,
  `baseline_locked_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `me_technical_plans_department_id_status_index` (`department_id`,`status`),
  KEY `me_technical_plans_budget_request_id_foreign` (`budget_request_id`),
  KEY `me_technical_plans_planning_cycle_id_foreign` (`planning_cycle_id`),
  KEY `me_technical_plans_submitted_by_foreign` (`submitted_by`),
  KEY `me_technical_plans_me_reviewed_by_foreign` (`me_reviewed_by`),
  KEY `me_technical_plans_baseline_locked_by_foreign` (`baseline_locked_by`),
  CONSTRAINT `me_technical_plans_budget_request_id_foreign` FOREIGN KEY (`budget_request_id`) REFERENCES `admin_budget_requests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `me_technical_plans_planning_cycle_id_foreign` FOREIGN KEY (`planning_cycle_id`) REFERENCES `admin_planning_cycles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `me_technical_plans_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `me_technical_plans_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `me_technical_plans_me_reviewed_by_foreign` FOREIGN KEY (`me_reviewed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `me_technical_plans_baseline_locked_by_foreign` FOREIGN KEY (`baseline_locked_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `me_plan_outputs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `technical_plan_id` bigint(20) unsigned NOT NULL,
  `output` text NOT NULL,
  `activity` text NOT NULL,
  `costable_item` varchar(500) DEFAULT NULL,
  `planned` decimal(14,2) NOT NULL DEFAULT 0.00,
  `planned_unit` varchar(50) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `me_plan_outputs_technical_plan_id_foreign` (`technical_plan_id`),
  CONSTRAINT `me_plan_outputs_technical_plan_id_foreign` FOREIGN KEY (`technical_plan_id`) REFERENCES `me_technical_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `me_quarters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `technical_plan_id` bigint(20) unsigned NOT NULL,
  `quarter_number` tinyint(3) unsigned NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'open',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `me_quarters_technical_plan_id_quarter_number_unique` (`technical_plan_id`,`quarter_number`),
  CONSTRAINT `me_quarters_technical_plan_id_foreign` FOREIGN KEY (`technical_plan_id`) REFERENCES `me_technical_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `me_quarterly_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `technical_plan_id` bigint(20) unsigned NOT NULL,
  `quarter_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `submitted_by` bigint(20) unsigned DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `me_verified_by` bigint(20) unsigned DEFAULT NULL,
  `me_verified_at` datetime DEFAULT NULL,
  `me_notes` text DEFAULT NULL,
  `ceo_delivered_at` datetime DEFAULT NULL,
  `ceo_reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `ceo_reviewed_at` datetime DEFAULT NULL,
  `ceo_signature` varchar(300) DEFAULT NULL,
  `ceo_notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `me_quarterly_reports_quarter_id_department_id_unique` (`quarter_id`,`department_id`),
  KEY `me_quarterly_reports_technical_plan_id_foreign` (`technical_plan_id`),
  KEY `me_quarterly_reports_department_id_foreign` (`department_id`),
  KEY `me_quarterly_reports_submitted_by_foreign` (`submitted_by`),
  KEY `me_quarterly_reports_me_verified_by_foreign` (`me_verified_by`),
  KEY `me_quarterly_reports_ceo_reviewed_by_foreign` (`ceo_reviewed_by`),
  CONSTRAINT `me_quarterly_reports_technical_plan_id_foreign` FOREIGN KEY (`technical_plan_id`) REFERENCES `me_technical_plans` (`id`),
  CONSTRAINT `me_quarterly_reports_quarter_id_foreign` FOREIGN KEY (`quarter_id`) REFERENCES `me_quarters` (`id`),
  CONSTRAINT `me_quarterly_reports_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `me_quarterly_reports_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `me_quarterly_reports_me_verified_by_foreign` FOREIGN KEY (`me_verified_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `me_quarterly_reports_ceo_reviewed_by_foreign` FOREIGN KEY (`ceo_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `me_quarterly_report_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `quarterly_report_id` bigint(20) unsigned NOT NULL,
  `plan_output_id` bigint(20) unsigned DEFAULT NULL,
  `output` text NOT NULL,
  `activity` text NOT NULL,
  `costable_item` varchar(500) DEFAULT NULL,
  `planned` decimal(14,2) NOT NULL DEFAULT 0.00,
  `achieved` decimal(14,2) NOT NULL DEFAULT 0.00,
  `deviation` decimal(14,2) NOT NULL DEFAULT 0.00,
  `display_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `me_quarterly_report_lines_quarterly_report_id_foreign` (`quarterly_report_id`),
  KEY `me_quarterly_report_lines_plan_output_id_foreign` (`plan_output_id`),
  CONSTRAINT `me_quarterly_report_lines_quarterly_report_id_foreign` FOREIGN KEY (`quarterly_report_id`) REFERENCES `me_quarterly_reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `me_quarterly_report_lines_plan_output_id_foreign` FOREIGN KEY (`plan_output_id`) REFERENCES `me_plan_outputs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `me_department_health_scores` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `department_id` bigint(20) unsigned NOT NULL,
  `fiscal_year` varchar(20) DEFAULT NULL,
  `planning_cycle_id` bigint(20) unsigned DEFAULT NULL,
  `qa_compliance_avg` decimal(5,2) DEFAULT NULL,
  `me_achievement_avg` decimal(5,2) DEFAULT NULL,
  `health_score` decimal(5,2) DEFAULT NULL,
  `health_rating` varchar(50) DEFAULT NULL,
  `calculated_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `me_health_dept_year_unique` (`department_id`,`fiscal_year`),
  KEY `me_department_health_scores_planning_cycle_id_foreign` (`planning_cycle_id`),
  CONSTRAINT `me_department_health_scores_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `me_department_health_scores_planning_cycle_id_foreign` FOREIGN KEY (`planning_cycle_id`) REFERENCES `admin_planning_cycles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET time_zone = '+03:00';

-- -----------------------------------------------------------------------------
-- 20. Anonymous student suggestions + supplementary supporting docs
--     (2026_09_08_150000_add_anonymous_suggestions_and_supplementary_docs)
-- -----------------------------------------------------------------------------
SET @db := DATABASE();

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='student_suggestions' AND COLUMN_NAME='is_anonymous'),
    'SELECT 1',
    'ALTER TABLE `student_suggestions` ADD COLUMN `is_anonymous` tinyint(1) NOT NULL DEFAULT 0 AFTER `student_id`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='supplementary_requests' AND COLUMN_NAME='supporting_docs'),
    'SELECT 1',
    'ALTER TABLE `supplementary_requests` ADD COLUMN `supporting_docs` json DEFAULT NULL AFTER `student_notes`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 21. QA criterion max_score + submission score as integers
--     (2026_09_09_140000_qa_scores_to_integers)
-- -----------------------------------------------------------------------------
UPDATE `qa_audit_checklists` SET `max_score` = ROUND(`max_score`) WHERE `max_score` IS NOT NULL;
ALTER TABLE `qa_audit_checklists` MODIFY `max_score` INT UNSIGNED NOT NULL DEFAULT 100;

UPDATE `qa_department_submissions` SET `score` = ROUND(`score`) WHERE `score` IS NOT NULL;
ALTER TABLE `qa_department_submissions` MODIFY `score` INT UNSIGNED NULL DEFAULT NULL;

SET time_zone = '+03:00';

-- -----------------------------------------------------------------------------
-- 22. M&E policy signoffs: version + role at signature
--     (2026_09_14_000001_add_version_role_to_me_policy_signoffs)
-- -----------------------------------------------------------------------------
SET @db := DATABASE();

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='me_policy_signoffs' AND COLUMN_NAME='policy_version'),
    'SELECT 1',
    'ALTER TABLE `me_policy_signoffs` ADD COLUMN `policy_version` varchar(50) NULL DEFAULT NULL AFTER `policy_id`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='me_policy_signoffs' AND COLUMN_NAME='signed_role'),
    'SELECT 1',
    'ALTER TABLE `me_policy_signoffs` ADD COLUMN `signed_role` varchar(100) NULL DEFAULT NULL AFTER `policy_version`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 23. Procurement requisitions: item / budget / audit detail columns
--     (2026_09_14_000003_add_requisition_details_to_procurement_requisitions)
-- -----------------------------------------------------------------------------
SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='procurement_requisitions' AND COLUMN_NAME='requested_item'),
    'SELECT 1',
    'ALTER TABLE `procurement_requisitions` ADD COLUMN `requested_item` varchar(300) NULL DEFAULT NULL AFTER `justification`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='procurement_requisitions' AND COLUMN_NAME='attachments'),
    'SELECT 1',
    'ALTER TABLE `procurement_requisitions` ADD COLUMN `attachments` json NULL DEFAULT NULL AFTER `requested_item`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='procurement_requisitions' AND COLUMN_NAME='budget_line'),
    'SELECT 1',
    'ALTER TABLE `procurement_requisitions` ADD COLUMN `budget_line` varchar(100) NULL DEFAULT NULL AFTER `budget_code`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='procurement_requisitions' AND COLUMN_NAME='estimated_unit_cost'),
    'SELECT 1',
    'ALTER TABLE `procurement_requisitions` ADD COLUMN `estimated_unit_cost` decimal(12,2) NULL DEFAULT NULL AFTER `estimated_cost`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='procurement_requisitions' AND COLUMN_NAME='quantity'),
    'SELECT 1',
    'ALTER TABLE `procurement_requisitions` ADD COLUMN `quantity` decimal(12,2) NULL DEFAULT NULL AFTER `estimated_unit_cost`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='procurement_requisitions' AND COLUMN_NAME='delivery_location'),
    'SELECT 1',
    'ALTER TABLE `procurement_requisitions` ADD COLUMN `delivery_location` varchar(500) NULL DEFAULT NULL AFTER `delivery_required_by`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='procurement_requisitions' AND COLUMN_NAME='audit_trail'),
    'SELECT 1',
    'ALTER TABLE `procurement_requisitions` ADD COLUMN `audit_trail` text NULL DEFAULT NULL AFTER `ceo_approved_at`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 23b. Procurement requisitions: multi-line items JSON
--     (2026_03_16_000001_add_line_items_to_procurement_requisitions)
-- -----------------------------------------------------------------------------
SET @db := DATABASE();
SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='procurement_requisitions' AND COLUMN_NAME='line_items'),
    'SELECT 1',
    'ALTER TABLE `procurement_requisitions` ADD COLUMN `line_items` json NULL DEFAULT NULL AFTER `requested_item`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 24. Suppliers registry extensions + created_at default
--     (2026_09_14_000004_extend_suppliers_table)
-- -----------------------------------------------------------------------------
SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='suppliers' AND COLUMN_NAME='supplier_category'),
    'SELECT 1',
    'ALTER TABLE `suppliers` ADD COLUMN `supplier_category` varchar(50) NULL DEFAULT NULL AFTER `is_active`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='suppliers' AND COLUMN_NAME='sub_category'),
    'SELECT 1',
    'ALTER TABLE `suppliers` ADD COLUMN `sub_category` varchar(100) NULL DEFAULT NULL AFTER `supplier_category`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='suppliers' AND COLUMN_NAME='risk_rating'),
    'SELECT 1',
    'ALTER TABLE `suppliers` ADD COLUMN `risk_rating` varchar(20) NOT NULL DEFAULT ''medium'''
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='suppliers' AND COLUMN_NAME='compliance_status'),
    'SELECT 1',
    'ALTER TABLE `suppliers` ADD COLUMN `compliance_status` varchar(50) NOT NULL DEFAULT ''pending'''
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='suppliers' AND COLUMN_NAME='performance_score'),
    'SELECT 1',
    'ALTER TABLE `suppliers` ADD COLUMN `performance_score` decimal(5,2) NOT NULL DEFAULT 50.00'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='suppliers' AND COLUMN_NAME='registration_number'),
    'SELECT 1',
    'ALTER TABLE `suppliers` ADD COLUMN `registration_number` varchar(100) NULL DEFAULT NULL AFTER `supplier_name`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='suppliers' AND COLUMN_NAME='pin_certificate_path'),
    'SELECT 1',
    'ALTER TABLE `suppliers` ADD COLUMN `pin_certificate_path` varchar(500) NULL DEFAULT NULL AFTER `compliance_doc_path`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='suppliers' AND COLUMN_NAME='cr12_path'),
    'SELECT 1',
    'ALTER TABLE `suppliers` ADD COLUMN `cr12_path` varchar(500) NULL DEFAULT NULL AFTER `pin_certificate_path`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='suppliers' AND COLUMN_NAME='audited_financial_statements_path'),
    'SELECT 1',
    'ALTER TABLE `suppliers` ADD COLUMN `audited_financial_statements_path` varchar(500) NULL DEFAULT NULL AFTER `cr12_path`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='suppliers' AND COLUMN_NAME='past_contracts'),
    'SELECT 1',
    'ALTER TABLE `suppliers` ADD COLUMN `past_contracts` json NULL DEFAULT NULL'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='suppliers' AND COLUMN_NAME='client_references'),
    'SELECT 1',
    'ALTER TABLE `suppliers` ADD COLUMN `client_references` json NULL DEFAULT NULL'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='suppliers' AND COLUMN_NAME='staff_count'),
    'SELECT 1',
    'ALTER TABLE `suppliers` ADD COLUMN `staff_count` int(11) NULL DEFAULT NULL'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='suppliers' AND COLUMN_NAME='certifications'),
    'SELECT 1',
    'ALTER TABLE `suppliers` ADD COLUMN `certifications` json NULL DEFAULT NULL'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='suppliers' AND COLUMN_NAME='blacklist_status'),
    'SELECT 1',
    'ALTER TABLE `suppliers` ADD COLUMN `blacklist_status` varchar(50) NOT NULL DEFAULT ''active'''
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='suppliers' AND COLUMN_NAME='blacklist_reason'),
    'SELECT 1',
    'ALTER TABLE `suppliers` ADD COLUMN `blacklist_reason` text NULL DEFAULT NULL'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='suppliers' AND COLUMN_NAME='blacklisted_at'),
    'SELECT 1',
    'ALTER TABLE `suppliers` ADD COLUMN `blacklisted_at` datetime NULL DEFAULT NULL'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='suppliers' AND COLUMN_NAME='blacklisted_by'),
    'SELECT 1',
    'ALTER TABLE `suppliers` ADD COLUMN `blacklisted_by` bigint(20) unsigned NULL DEFAULT NULL'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='suppliers' AND COLUMN_NAME='performance_last_updated_at'),
    'SELECT 1',
    'ALTER TABLE `suppliers` ADD COLUMN `performance_last_updated_at` datetime NULL DEFAULT NULL'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='suppliers' AND COLUMN_NAME='performance_updated_by'),
    'SELECT 1',
    'ALTER TABLE `suppliers` ADD COLUMN `performance_updated_by` bigint(20) unsigned NULL DEFAULT NULL'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='suppliers' AND COLUMN_NAME='notes'),
    'SELECT 1',
    'ALTER TABLE `suppliers` ADD COLUMN `notes` text NULL DEFAULT NULL'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- production.sql cannot change existing column defaults; align created_at.
SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='suppliers' AND COLUMN_NAME='created_at'),
    'ALTER TABLE `suppliers` MODIFY COLUMN `created_at` datetime NULL DEFAULT CURRENT_TIMESTAMP',
    'SELECT 1'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 25. Assets: tagging, custodian, location, GRN / requisition links
--     (2026_09_15_000001_extend_assets_table)
-- -----------------------------------------------------------------------------
SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='assets' AND COLUMN_NAME='tag_number'),
    'SELECT 1',
    'ALTER TABLE `assets` ADD COLUMN `tag_number` varchar(50) NULL DEFAULT NULL AFTER `asset_number`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='assets' AND INDEX_NAME='assets_tag_number_unique'),
    'SELECT 1',
    'ALTER TABLE `assets` ADD UNIQUE KEY `assets_tag_number_unique` (`tag_number`)'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='assets' AND COLUMN_NAME='qr_code_path'),
    'SELECT 1',
    'ALTER TABLE `assets` ADD COLUMN `qr_code_path` varchar(500) NULL DEFAULT NULL AFTER `tag_number`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='assets' AND COLUMN_NAME='custodian_id'),
    'SELECT 1',
    'ALTER TABLE `assets` ADD COLUMN `custodian_id` bigint(20) unsigned NULL DEFAULT NULL AFTER `qr_code_path`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='assets' AND COLUMN_NAME='location_name'),
    'SELECT 1',
    'ALTER TABLE `assets` ADD COLUMN `location_name` varchar(255) NULL DEFAULT NULL AFTER `custodian_id`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='assets' AND COLUMN_NAME='building'),
    'SELECT 1',
    'ALTER TABLE `assets` ADD COLUMN `building` varchar(100) NULL DEFAULT NULL AFTER `location_name`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='assets' AND COLUMN_NAME='room'),
    'SELECT 1',
    'ALTER TABLE `assets` ADD COLUMN `room` varchar(100) NULL DEFAULT NULL AFTER `building`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='assets' AND COLUMN_NAME='asset_status'),
    'SELECT 1',
    'ALTER TABLE `assets` ADD COLUMN `asset_status` varchar(50) NOT NULL DEFAULT ''new'' AFTER `room`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='assets' AND COLUMN_NAME='handover_date'),
    'SELECT 1',
    'ALTER TABLE `assets` ADD COLUMN `handover_date` date NULL DEFAULT NULL AFTER `asset_status`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='assets' AND COLUMN_NAME='grn_id'),
    'SELECT 1',
    'ALTER TABLE `assets` ADD COLUMN `grn_id` bigint(20) unsigned NULL DEFAULT NULL AFTER `handover_date`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='assets' AND COLUMN_NAME='procurement_requisition_id'),
    'SELECT 1',
    'ALTER TABLE `assets` ADD COLUMN `procurement_requisition_id` bigint(20) unsigned NULL DEFAULT NULL AFTER `grn_id`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;





-- -----------------------------------------------------------------------------
-- 26. Financial Policy (upload in Finance; HOD sign-off gates department budgets)
--     Prefer deploy/production.sql (CREATE TABLE IF NOT EXISTS + ensure_* helpers).
--     Idempotent CREATE below for hosts that only re-run this patches file.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `finance_policies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fiscal_year` varchar(20) NOT NULL,
  `title` varchar(300) NOT NULL,
  `version` varchar(50) DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `description` text DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `finance_policies_uploaded_by_foreign` (`uploaded_by`),
  KEY `finance_policies_year_status_idx` (`fiscal_year`,`status`),
  CONSTRAINT `finance_policies_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `finance_policy_signoffs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `policy_id` bigint(20) unsigned NOT NULL,
  `policy_version` varchar(50) DEFAULT NULL,
  `signed_role` varchar(100) DEFAULT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `staff_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `signed_name` varchar(200) NOT NULL,
  `employee_number` varchar(100) DEFAULT NULL,
  `signature` text DEFAULT NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `signed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `finance_policy_signoffs_unique` (`policy_id`,`department_id`,`staff_id`),
  KEY `finance_policy_signoffs_department_id_foreign` (`department_id`),
  KEY `finance_policy_signoffs_staff_id_foreign` (`staff_id`),
  KEY `finance_policy_signoffs_user_id_foreign` (`user_id`),
  CONSTRAINT `finance_policy_signoffs_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `finance_policy_signoffs_policy_id_foreign` FOREIGN KEY (`policy_id`) REFERENCES `finance_policies` (`id`),
  CONSTRAINT `finance_policy_signoffs_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`),
  CONSTRAINT `finance_policy_signoffs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 27. Research activities (CMS body, schedule, documents vault)
-- -----------------------------------------------------------------------------
SET @db := DATABASE();

SET @sql := (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='research_projects' AND COLUMN_NAME='slug'),
  'SELECT 1',
  'ALTER TABLE `research_projects` ADD COLUMN `slug` varchar(320) NULL DEFAULT NULL AFTER `title`'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='research_projects' AND COLUMN_NAME='body'),
  'SELECT 1',
  'ALTER TABLE `research_projects` ADD COLUMN `body` longtext NULL AFTER `abstract`'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='research_projects' AND COLUMN_NAME='duration_value'),
  'SELECT 1',
  'ALTER TABLE `research_projects` ADD COLUMN `duration_value` int unsigned NULL DEFAULT NULL AFTER `start_date`'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='research_projects' AND COLUMN_NAME='duration_unit'),
  'SELECT 1',
  'ALTER TABLE `research_projects` ADD COLUMN `duration_unit` varchar(20) NULL DEFAULT NULL AFTER `duration_value`'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='research_projects' AND COLUMN_NAME='status_locked'),
  'SELECT 1',
  'ALTER TABLE `research_projects` ADD COLUMN `status_locked` tinyint(1) NOT NULL DEFAULT 0 AFTER `status`'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='research_projects' AND COLUMN_NAME='visibility'),
  'SELECT 1',
  'ALTER TABLE `research_projects` ADD COLUMN `visibility` varchar(30) NOT NULL DEFAULT \'draft\' AFTER `is_featured`'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='research_projects' AND COLUMN_NAME='published_at'),
  'SELECT 1',
  'ALTER TABLE `research_projects` ADD COLUMN `published_at` datetime NULL DEFAULT NULL AFTER `visibility`'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='research_projects' AND INDEX_NAME='research_projects_slug_unique'),
  'SELECT 1',
  'ALTER TABLE `research_projects` ADD UNIQUE KEY `research_projects_slug_unique` (`slug`)'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `research_project_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `research_project_id` bigint(20) unsigned NOT NULL,
  `title` varchar(300) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_filename` varchar(300) DEFAULT NULL,
  `mime_type` varchar(120) DEFAULT NULL,
  `file_size` bigint(20) unsigned DEFAULT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rpd_project_sort_idx` (`research_project_id`,`sort_order`),
  KEY `rpd_created_by_fk` (`created_by`),
  CONSTRAINT `rpd_project_fk` FOREIGN KEY (`research_project_id`) REFERENCES `research_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rpd_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 28. Research partnership inquiries (extended public partner form)
-- -----------------------------------------------------------------------------
SET @db := DATABASE();

SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='partnership_requests' AND COLUMN_NAME='applicant_type'),'SELECT 1','ALTER TABLE `partnership_requests` ADD COLUMN `applicant_type` varchar(30) NOT NULL DEFAULT \'organisation\' AFTER `request_number`'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='partnership_requests' AND COLUMN_NAME='first_name'),'SELECT 1','ALTER TABLE `partnership_requests` ADD COLUMN `first_name` varchar(120) NULL AFTER `applicant_type`'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='partnership_requests' AND COLUMN_NAME='last_name'),'SELECT 1','ALTER TABLE `partnership_requests` ADD COLUMN `last_name` varchar(120) NULL AFTER `first_name`'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='partnership_requests' AND COLUMN_NAME='alternative_email'),'SELECT 1','ALTER TABLE `partnership_requests` ADD COLUMN `alternative_email` varchar(255) NULL AFTER `email`'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='partnership_requests' AND COLUMN_NAME='alternative_phone'),'SELECT 1','ALTER TABLE `partnership_requests` ADD COLUMN `alternative_phone` varchar(30) NULL AFTER `phone`'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='partnership_requests' AND COLUMN_NAME='research_area'),'SELECT 1','ALTER TABLE `partnership_requests` ADD COLUMN `research_area` varchar(200) NULL AFTER `alternative_phone`'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='partnership_requests' AND COLUMN_NAME='what_they_do'),'SELECT 1','ALTER TABLE `partnership_requests` ADD COLUMN `what_they_do` text NULL AFTER `research_area`'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='partnership_requests' AND COLUMN_NAME='why_partnership'),'SELECT 1','ALTER TABLE `partnership_requests` ADD COLUMN `why_partnership` text NULL AFTER `what_they_do`'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='partnership_requests' AND COLUMN_NAME='organisation_details'),'SELECT 1','ALTER TABLE `partnership_requests` ADD COLUMN `organisation_details` text NULL AFTER `organization_name`'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='partnership_requests' AND COLUMN_NAME='individual_details'),'SELECT 1','ALTER TABLE `partnership_requests` ADD COLUMN `individual_details` text NULL AFTER `organisation_details`'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `partnership_request_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `partnership_request_id` bigint(20) unsigned NOT NULL,
  `title` varchar(300) DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_filename` varchar(300) DEFAULT NULL,
  `mime_type` varchar(120) DEFAULT NULL,
  `file_size` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `prd_request_fk` (`partnership_request_id`),
  CONSTRAINT `prd_request_fk` FOREIGN KEY (`partnership_request_id`) REFERENCES `partnership_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 29. Technical plan outputs — quarter segmentation (independent module plans)
-- -----------------------------------------------------------------------------
SET @db := DATABASE();

SET @sql := (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='me_plan_outputs' AND COLUMN_NAME='quarter'),
  'SELECT 1',
  'ALTER TABLE `me_plan_outputs` ADD COLUMN `quarter` tinyint(3) unsigned NULL DEFAULT NULL AFTER `planned_unit`'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 30. Staff weekly time logs (digitized STAFF WEEKLY TIME LOG form)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_weekly_time_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `log_code` varchar(40) NOT NULL,
  `staff_id` bigint(20) unsigned NOT NULL,
  `log_year` smallint(5) unsigned NOT NULL,
  `log_month` tinyint(3) unsigned NOT NULL,
  `week_number` tinyint(3) unsigned NOT NULL,
  `week_ref` varchar(30) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `total_hours` decimal(8,2) NOT NULL DEFAULT 0.00,
  `total_units` decimal(10,2) DEFAULT NULL,
  `employee_signed_name` varchar(200) DEFAULT NULL,
  `employee_signed_at` timestamp NULL DEFAULT NULL,
  `manager_staff_id` bigint(20) unsigned DEFAULT NULL,
  `manager_signed_name` varchar(200) DEFAULT NULL,
  `manager_signature` varchar(300) DEFAULT NULL,
  `manager_signed_at` timestamp NULL DEFAULT NULL,
  `manager_self_endorsed` tinyint(1) NOT NULL DEFAULT 0,
  `hr_reviewed_by_staff_id` bigint(20) unsigned DEFAULT NULL,
  `hr_reviewed_at` timestamp NULL DEFAULT NULL,
  `hr_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_weekly_time_logs_log_code_unique` (`log_code`),
  UNIQUE KEY `swtl_staff_week_unique` (`staff_id`,`log_year`,`log_month`,`week_number`),
  KEY `swtl_status_submitted_idx` (`status`,`employee_signed_at`),
  KEY `swtl_period_idx` (`log_year`,`log_month`,`week_number`),
  KEY `swtl_manager_staff_fk` (`manager_staff_id`),
  KEY `swtl_hr_reviewer_fk` (`hr_reviewed_by_staff_id`),
  CONSTRAINT `staff_weekly_time_logs_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_weekly_time_logs_manager_staff_id_foreign` FOREIGN KEY (`manager_staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_weekly_time_logs_hr_reviewed_by_staff_id_foreign` FOREIGN KEY (`hr_reviewed_by_staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `staff_weekly_time_log_days` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `weekly_time_log_id` bigint(20) unsigned NOT NULL,
  `work_date` date NOT NULL,
  `day_label` varchar(10) NOT NULL,
  `in_month` tinyint(1) NOT NULL DEFAULT 1,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `tasks_accomplished` text DEFAULT NULL,
  `initials` varchar(20) DEFAULT NULL,
  `department_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`department_ids`)),
  `approval_sign` varchar(120) DEFAULT NULL,
  `total_hours` decimal(6,2) DEFAULT NULL,
  `total_units` decimal(8,2) DEFAULT NULL,
  `display_order` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `swtld_log_date_unique` (`weekly_time_log_id`,`work_date`),
  CONSTRAINT `swtld_log_fk` FOREIGN KEY (`weekly_time_log_id`) REFERENCES `staff_weekly_time_logs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




-- -----------------------------------------------------------------------------
-- 31. IQA assessments (NATIONAL POLYTECHNIC QUALITY AUDIT TOOL)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `iqa_assessments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL DEFAULT 'NATIONAL POLYTECHNIC QUALITY AUDIT TOOL',
  `assessment_year` smallint(5) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `current_section` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `updated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `published_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `publisher_name` varchar(200) DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `iqa_status_year_idx` (`status`,`assessment_year`),
  KEY `iqa_assessments_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `iqa_assessments_updated_by_user_id_foreign` (`updated_by_user_id`),
  KEY `iqa_assessments_published_by_user_id_foreign` (`published_by_user_id`),
  CONSTRAINT `iqa_assessments_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `iqa_assessments_updated_by_user_id_foreign` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `iqa_assessments_published_by_user_id_foreign` FOREIGN KEY (`published_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 32. Retire QCA flags and QA corrective actions
DROP TABLE IF EXISTS `qca_milestones`;
DROP TABLE IF EXISTS `qca_flags`;
DROP TABLE IF EXISTS `qa_corrective_actions`;

-- -----------------------------------------------------------------------------
-- 33. Academic workplans + lesson plan QA acknowledgement columns
-- -----------------------------------------------------------------------------
ALTER TABLE `lesson_plans`
    ADD COLUMN IF NOT EXISTS `qa_acknowledged_by` bigint(20) unsigned NULL DEFAULT NULL AFTER `registrar_visible`,
    ADD COLUMN IF NOT EXISTS `qa_acknowledged_at` datetime NULL DEFAULT NULL AFTER `qa_acknowledged_by`,
    ADD COLUMN IF NOT EXISTS `qa_comments` text NULL DEFAULT NULL AFTER `qa_acknowledged_at`;

CREATE TABLE IF NOT EXISTS `academic_workplans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `workplan_number` varchar(40) NOT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `semester_id` bigint(20) unsigned NOT NULL,
  `title` varchar(300) NOT NULL,
  `objectives` text DEFAULT NULL,
  `resources` text DEFAULT NULL,
  `kpis` text DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'draft',
  `prepared_by_staff_id` bigint(20) unsigned NOT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `registrar_status` varchar(40) NOT NULL DEFAULT 'pending',
  `registrar_staff_id` bigint(20) unsigned DEFAULT NULL,
  `registrar_acted_at` timestamp NULL DEFAULT NULL,
  `registrar_comments` text DEFAULT NULL,
  `qa_status` varchar(40) NOT NULL DEFAULT 'pending',
  `qa_staff_id` bigint(20) unsigned DEFAULT NULL,
  `qa_acted_at` timestamp NULL DEFAULT NULL,
  `qa_comments` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_workplans_workplan_number_unique` (`workplan_number`),
  KEY `awp_dept_sem_status_idx` (`department_id`,`semester_id`,`status`),
  KEY `academic_workplans_semester_id_foreign` (`semester_id`),
  KEY `academic_workplans_prepared_by_staff_id_foreign` (`prepared_by_staff_id`),
  KEY `academic_workplans_registrar_staff_id_foreign` (`registrar_staff_id`),
  KEY `academic_workplans_qa_staff_id_foreign` (`qa_staff_id`),
  CONSTRAINT `academic_workplans_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_workplans_semester_id_foreign` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`),
  CONSTRAINT `academic_workplans_prepared_by_staff_id_foreign` FOREIGN KEY (`prepared_by_staff_id`) REFERENCES `staff` (`id`),
  CONSTRAINT `academic_workplans_registrar_staff_id_foreign` FOREIGN KEY (`registrar_staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `academic_workplans_qa_staff_id_foreign` FOREIGN KEY (`qa_staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `academic_workplan_activities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `workplan_id` bigint(20) unsigned NOT NULL,
  `activity` varchar(500) NOT NULL,
  `timeline_start` date DEFAULT NULL,
  `timeline_end` date DEFAULT NULL,
  `kpi` varchar(500) DEFAULT NULL,
  `resources` varchar(500) DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `academic_workplan_activities_workplan_id_foreign` (`workplan_id`),
  CONSTRAINT `academic_workplan_activities_workplan_id_foreign` FOREIGN KEY (`workplan_id`) REFERENCES `academic_workplans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 34. Staff archive + soft delete (HR archive)
-- -----------------------------------------------------------------------------
ALTER TABLE `staff`
    ADD COLUMN IF NOT EXISTS `archived_at` timestamp NULL DEFAULT NULL AFTER `exit_date`,
    ADD COLUMN IF NOT EXISTS `archived_by` bigint(20) unsigned NULL DEFAULT NULL AFTER `archived_at`,
    ADD COLUMN IF NOT EXISTS `archive_reason` text NULL DEFAULT NULL AFTER `archived_by`,
    ADD COLUMN IF NOT EXISTS `deleted_at` timestamp NULL DEFAULT NULL AFTER `updated_at`;

-- -----------------------------------------------------------------------------
-- 35. Leave catalog overhaul (coverages, sick half-pay, carry-forward LM+HR)
-- -----------------------------------------------------------------------------
-- Prefer: php artisan migrate  (2026_09_23_140000_hardcode_leave_catalog_and_coverages)
-- Catalog source of truth: web/config/tich-leave.php (LeaveCatalogService::ensureSynced).

ALTER TABLE `leave_requests`
    ADD COLUMN IF NOT EXISTS `family_relation` varchar(30) NULL DEFAULT NULL AFTER `reason`,
    ADD COLUMN IF NOT EXISTS `supporting_document_path` varchar(500) NULL DEFAULT NULL AFTER `medical_certificate_path`,
    ADD COLUMN IF NOT EXISTS `supporting_document_name` varchar(255) NULL DEFAULT NULL AFTER `supporting_document_path`,
    ADD COLUMN IF NOT EXISTS `sick_full_pay_days` smallint(5) unsigned NULL DEFAULT NULL AFTER `days_requested`,
    ADD COLUMN IF NOT EXISTS `sick_half_pay_days` smallint(5) unsigned NULL DEFAULT NULL AFTER `sick_full_pay_days`;

CREATE TABLE IF NOT EXISTS `leave_request_coverages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `leave_request_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `cover_staff_id` bigint(20) unsigned NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'accepted',
  `notified_at` timestamp NULL DEFAULT NULL,
  `access_granted_at` timestamp NULL DEFAULT NULL,
  `access_revoked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_cov_req_dept_unique` (`leave_request_id`,`department_id`),
  KEY `leave_request_coverages_department_id_foreign` (`department_id`),
  KEY `leave_request_coverages_cover_staff_id_foreign` (`cover_staff_id`),
  CONSTRAINT `leave_request_coverages_leave_request_id_foreign` FOREIGN KEY (`leave_request_id`) REFERENCES `leave_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_request_coverages_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_request_coverages_cover_staff_id_foreign` FOREIGN KEY (`cover_staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `leave_coverage_access_grants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `leave_request_coverage_id` bigint(20) unsigned NOT NULL,
  `cover_user_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `campus_id` bigint(20) unsigned NULL DEFAULT NULL,
  `was_preexisting` tinyint(1) NOT NULL DEFAULT 0,
  `granted_at` timestamp NULL DEFAULT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leave_cov_grant_cov_fk` (`leave_request_coverage_id`),
  KEY `leave_coverage_access_grants_cover_user_id_foreign` (`cover_user_id`),
  KEY `leave_coverage_access_grants_role_id_foreign` (`role_id`),
  KEY `leave_coverage_access_grants_department_id_foreign` (`department_id`),
  CONSTRAINT `leave_cov_grant_cov_fk` FOREIGN KEY (`leave_request_coverage_id`) REFERENCES `leave_request_coverages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_coverage_access_grants_cover_user_id_foreign` FOREIGN KEY (`cover_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_coverage_access_grants_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_coverage_access_grants_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `leave_sick_pay_adjustments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `leave_request_id` bigint(20) unsigned NOT NULL,
  `staff_id` bigint(20) unsigned NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `month` tinyint(3) unsigned NOT NULL,
  `half_pay_days` smallint(5) unsigned NOT NULL,
  `daily_rate` decimal(12,2) NULL DEFAULT NULL,
  `deduction_amount` decimal(12,2) NULL DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `applied_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_sick_pay_unique` (`leave_request_id`,`year`,`month`),
  KEY `leave_sick_pay_adjustments_staff_id_foreign` (`staff_id`),
  CONSTRAINT `leave_sick_pay_adjustments_leave_request_id_foreign` FOREIGN KEY (`leave_request_id`) REFERENCES `leave_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_sick_pay_adjustments_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `leave_carry_forward_requests`
    ADD COLUMN IF NOT EXISTS `line_manager_status` varchar(30) NOT NULL DEFAULT 'pending' AFTER `status`,
    ADD COLUMN IF NOT EXISTS `line_manager_staff_id` bigint(20) unsigned NULL DEFAULT NULL AFTER `line_manager_status`,
    ADD COLUMN IF NOT EXISTS `line_manager_acted_at` timestamp NULL DEFAULT NULL AFTER `line_manager_staff_id`,
    ADD COLUMN IF NOT EXISTS `line_manager_notes` text NULL DEFAULT NULL AFTER `line_manager_acted_at`,
    ADD COLUMN IF NOT EXISTS `hr_status` varchar(30) NOT NULL DEFAULT 'pending' AFTER `line_manager_notes`;

UPDATE `leave_types` SET `days_allowed_per_year`=5, `calculation_type`='working_days', `is_active`=1, `requires_certificate`=0, `requires_hod_approval`=0, `requires_hr_approval`=1, `description`='5 working days when mother, father, child, or spouse is sick.' WHERE `leave_code`='COMP';
UPDATE `leave_types` SET `days_allowed_per_year`=14, `calculation_type`='working_days', `requires_medical_certificate`=1, `requires_certificate`=1, `requires_hod_approval`=0, `requires_hr_approval`=1, `description`='14 working days: first 7 full pay, next 7 half pay.' WHERE `leave_code`='SICK';
UPDATE `leave_types` SET `days_allowed_per_year`=30, `calculation_type`='calendar_days', `requires_certificate`=1, `requires_hod_approval`=0, `requires_hr_approval`=1, `description`='30 calendar days (includes weekends and public holidays).' WHERE `leave_code`='ADOPT';
INSERT INTO `leave_types` (`leave_code`, `leave_name`, `days_allowed_per_year`, `accrual_type`, `calculation_type`, `is_paid`, `requires_medical_certificate`, `requires_certificate`, `requires_hod_approval`, `requires_hr_approval`, `gender_restriction`, `min_service_months`, `carry_forward_days`, `notice_period_days`, `is_active`, `description`)
SELECT 'BEREAVEMENT', 'Bereavement Leave', 5, 'none', 'working_days', 1, 0, 0, 0, 1, 'any', 0, 0, 0, 1, '5 working days when mother, father, child, or spouse passes away.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `leave_types` WHERE `leave_code`='BEREAVEMENT');
INSERT INTO `leave_types` (`leave_code`, `leave_name`, `days_allowed_per_year`, `accrual_type`, `calculation_type`, `is_paid`, `requires_medical_certificate`, `requires_certificate`, `requires_hod_approval`, `requires_hr_approval`, `gender_restriction`, `min_service_months`, `carry_forward_days`, `notice_period_days`, `is_active`, `description`)
SELECT 'COMPOFF', 'Compensatory Leave', 0, 'none', 'working_days', 1, 0, 0, 0, 1, 'any', 0, 0, 0, 0, 'Unavailable — on hold.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `leave_types` WHERE `leave_code`='COMPOFF');
UPDATE `leave_types` SET `is_active`=0 WHERE `leave_code`='COMPOFF';

-- -----------------------------------------------------------------------------
-- 36. Leave application form contact fields (paper sheet parity)
-- -----------------------------------------------------------------------------
ALTER TABLE `leave_requests`
    ADD COLUMN IF NOT EXISTS `contact_mobile` varchar(40) NULL DEFAULT NULL AFTER `handover_notes`,
    ADD COLUMN IF NOT EXISTS `contact_email` varchar(191) NULL DEFAULT NULL AFTER `contact_mobile`,
    ADD COLUMN IF NOT EXISTS `contact_postal_address` varchar(255) NULL DEFAULT NULL AFTER `contact_email`;


-- -----------------------------------------------------------------------------
-- 37. Marketing roles (CMO / Marketing Officer) + Marketing department modules
--     Roles are also materialized by RbacCatalogService on boot when app code is
--     deployed; this patch covers hosts that only apply SQL and/or never ran the
--     2026_09_22 marketing department_modules migrations.
-- -----------------------------------------------------------------------------
INSERT INTO `roles` (`role_name`, `display_name`, `role_category`, `module_key`, `description`, `is_system_role`, `created_at`)
SELECT 'Chief Marketing Officer', 'Chief Marketing Officer', 'administrative', 'marketing',
       'Marketing leadership - website content, branding, and site settings.', 1, NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `role_name` = 'Chief Marketing Officer');

INSERT INTO `roles` (`role_name`, `display_name`, `role_category`, `module_key`, `description`, `is_system_role`, `created_at`)
SELECT 'Marketing Officer', 'Marketing Officer', 'administrative', 'marketing',
       'Marketing operations - website content, branding, and site settings.', 1, NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `role_name` = 'Marketing Officer');

UPDATE `roles`
SET `display_name` = 'Chief Marketing Officer',
    `role_category` = 'administrative',
    `module_key` = 'marketing',
    `description` = 'Marketing leadership - website content, branding, and site settings.',
    `is_system_role` = 1
WHERE `role_name` = 'Chief Marketing Officer';

UPDATE `roles`
SET `display_name` = 'Marketing Officer',
    `role_category` = 'administrative',
    `module_key` = 'marketing',
    `description` = 'Marketing operations - website content, branding, and site settings.',
    `is_system_role` = 1
WHERE `role_name` = 'Marketing Officer';

INSERT INTO `department_modules` (`department_id`, `module_key`, `assigned_at`, `assigned_by`)
SELECT d.`id`, m.`module_key`, NOW(), NULL
FROM `departments` d
CROSS JOIN (
    SELECT 'portal' AS `module_key`
    UNION ALL SELECT 'site_settings'
) m
WHERE d.`is_active` = 1
  AND (
      d.`dept_code` = 'MKT'
      OR d.`dept_code` LIKE 'MKT%'
      OR d.`dept_name` LIKE '%Marketing%'
  )
  AND NOT EXISTS (
      SELECT 1 FROM `department_modules` dm
      WHERE dm.`department_id` = d.`id` AND dm.`module_key` = m.`module_key`
  );
-- PRESENT IN PRODUCTION UP TO HERE








SET time_zone = '+03:00';

-- Research activities, financial policy, partnership inquiry columns, weekly
-- time logs, IQA assessments, academic workplans, staff archive columns, and
-- leave catalog/coverages/sick-pay/carry-forward/contact columns are also covered by
-- deploy/production.sql / Laravel migrations. Run production.sql first on
-- fresh hosts.

-- Done. Verify: SELECT COUNT(*) FROM information_schema.tables
-- WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE';
