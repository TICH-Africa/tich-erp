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

SET time_zone = '+03:00';

-- Done. Verify: SELECT COUNT(*) FROM information_schema.tables
-- WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE';
