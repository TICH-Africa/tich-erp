-- =============================================================================
-- TICH ERP - production schema PATCHES (intentional alters / drops)
-- =============================================================================
-- Run AFTER deploy/production.sql on production when localhost migrations included
-- DROP TABLE or MODIFY COLUMN changes that production.sql cannot apply.
--
-- production.sql is non-destructive (add-only). This file applies the deltas.
-- Safe to re-run: uses IF EXISTS / checks where possible.
--
-- Last updated: 2026-09-04
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
    `effective_date` date DEFAULT NULL,
    `reason` text DEFAULT NULL,
    `reviewer_notes` text DEFAULT NULL,
    `reviewed_by_user_id` bigint(20) unsigned DEFAULT NULL,
    `reviewed_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `student_lifecycle_requests_student_id_status_index` (`student_id`, `status`),
    KEY `student_lifecycle_requests_requested_by_user_id_foreign` (`requested_by_user_id`),
    KEY `student_lifecycle_requests_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
    CONSTRAINT `student_lifecycle_requests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    CONSTRAINT `student_lifecycle_requests_requested_by_user_id_foreign` FOREIGN KEY (`requested_by_user_id`) REFERENCES `users` (`id`),
    CONSTRAINT `student_lifecycle_requests_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
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

SET time_zone = '+03:00';

-- Done. Verify: SELECT COUNT(*) FROM information_schema.tables
-- WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE';
