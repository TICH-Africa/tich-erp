-- =============================================================================
-- TICH ERP - production schema PATCHES (intentional alters / drops)
-- =============================================================================
-- Run AFTER deploy/production.sql on production when localhost migrations included
-- DROP TABLE or MODIFY COLUMN changes that production.sql cannot apply.
--
-- production.sql is non-destructive (add-only). This file applies the deltas.
-- Safe to re-run: uses IF EXISTS / checks where possible.
--
-- Last updated: 2026-08-28
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

SET time_zone = '+03:00';

-- Done. Verify: SELECT COUNT(*) FROM information_schema.tables
-- WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE';
