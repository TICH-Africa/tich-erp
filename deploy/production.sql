-- =============================================================================
-- TICH ERP — production schema sync (idempotent, non-destructive)
-- =============================================================================
-- Generated: 2026-08-27 17:32:04 EAT
-- Source DB: tich_erp
-- Time zone: Africa/Nairobi (GMT+3)
--
-- SAFE FOR PRODUCTION DATA:
--   * Never DROP TABLE / DROP COLUMN / TRUNCATE / DELETE
--   * CREATE TABLE IF NOT EXISTS
--   * ADD COLUMN / INDEX / FOREIGN KEY only when missing
--   * Re-runnable: already-applied objects are skipped
--
-- Regenerate locally after migrations:
--   php artisan migrate
--   php artisan tich:export-production-schema
-- (Also auto-refreshed when migrations finish successfully.)
-- =============================================================================

SET NAMES utf8mb4;
SET time_zone = '+03:00';
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;
SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS;
SET UNIQUE_CHECKS = 0;

-- Helper routines (recreated each run; no application data involved)
DROP PROCEDURE IF EXISTS `tich_ensure_column`;
DROP PROCEDURE IF EXISTS `tich_ensure_index`;
DROP PROCEDURE IF EXISTS `tich_ensure_unique`;
DROP PROCEDURE IF EXISTS `tich_ensure_fk`;

DELIMITER $$

CREATE PROCEDURE `tich_ensure_column`(
    IN p_table VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND COLUMN_NAME = p_column
    ) THEN
        SET @tich_sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE tich_stmt FROM @tich_sql;
        EXECUTE tich_stmt;
        DEALLOCATE PREPARE tich_stmt;
    END IF;
END$$

CREATE PROCEDURE `tich_ensure_index`(
    IN p_table VARCHAR(64),
    IN p_index VARCHAR(64),
    IN p_columns TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND INDEX_NAME = p_index
    ) THEN
        SET @tich_sql = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` (', p_columns, ')');
        PREPARE tich_stmt FROM @tich_sql;
        EXECUTE tich_stmt;
        DEALLOCATE PREPARE tich_stmt;
    END IF;
END$$

CREATE PROCEDURE `tich_ensure_unique`(
    IN p_table VARCHAR(64),
    IN p_index VARCHAR(64),
    IN p_columns TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND INDEX_NAME = p_index
    ) THEN
        SET @tich_sql = CONCAT('ALTER TABLE `', p_table, '` ADD UNIQUE INDEX `', p_index, '` (', p_columns, ')');
        PREPARE tich_stmt FROM @tich_sql;
        EXECUTE tich_stmt;
        DEALLOCATE PREPARE tich_stmt;
    END IF;
END$$

CREATE PROCEDURE `tich_ensure_fk`(
    IN p_table VARCHAR(64),
    IN p_constraint VARCHAR(64),
    IN p_columns TEXT,
    IN p_ref_table VARCHAR(64),
    IN p_ref_columns TEXT,
    IN p_on_update VARCHAR(16),
    IN p_on_delete VARCHAR(16)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND CONSTRAINT_NAME = p_constraint
          AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    ) THEN
        SET @tich_sql = CONCAT(
            'ALTER TABLE `', p_table, '` ADD CONSTRAINT `', p_constraint,
            '` FOREIGN KEY (', p_columns, ') REFERENCES `', p_ref_table, '` (', p_ref_columns, ')',
            ' ON UPDATE ', p_on_update, ' ON DELETE ', p_on_delete
        );
        PREPARE tich_stmt FROM @tich_sql;
        EXECUTE tich_stmt;
        DEALLOCATE PREPARE tich_stmt;
    END IF;
END$$

DELIMITER ;

-- -----------------------------------------------------------------------------
-- Table: `about_content_blocks`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `about_content_blocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `block_key` varchar(100) NOT NULL,
  `title` varchar(300) NOT NULL,
  `subtitle` varchar(500) DEFAULT NULL,
  `body` longtext NOT NULL,
  `featured_image_path` varchar(500) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `about_content_blocks_block_key_unique` (`block_key`),
  KEY `about_content_blocks_created_by_foreign` (`created_by`),
  KEY `about_content_blocks_updated_by_foreign` (`updated_by`),
  CONSTRAINT `about_content_blocks_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `about_content_blocks_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `about_content_blocks` (add only if missing)
CALL `tich_ensure_column`('about_content_blocks', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('about_content_blocks', 'block_key', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('about_content_blocks', 'title', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('about_content_blocks', 'subtitle', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('about_content_blocks', 'body', 'longtext NOT NULL');
CALL `tich_ensure_column`('about_content_blocks', 'featured_image_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('about_content_blocks', 'display_order', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('about_content_blocks', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('about_content_blocks', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('about_content_blocks', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('about_content_blocks', 'updated_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('about_content_blocks', 'updated_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `about_content_blocks` (add only if missing)
CALL `tich_ensure_unique`('about_content_blocks', 'about_content_blocks_block_key_unique', '`block_key`');
CALL `tich_ensure_index`('about_content_blocks', 'about_content_blocks_created_by_foreign', '`created_by`');
CALL `tich_ensure_index`('about_content_blocks', 'about_content_blocks_updated_by_foreign', '`updated_by`');

-- -----------------------------------------------------------------------------
-- Table: `academic_programs`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `academic_programs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `program_code` varchar(30) NOT NULL,
  `program_name` varchar(300) NOT NULL,
  `program_type` varchar(50) NOT NULL,
  `regulatory_body` varchar(50) DEFAULT NULL,
  `curriculum_format` varchar(50) NOT NULL DEFAULT 'trimester',
  `department_id` bigint(20) unsigned NOT NULL,
  `duration_months` int(11) DEFAULT NULL,
  `semester_count` int(11) NOT NULL DEFAULT 0,
  `block_count` int(11) NOT NULL DEFAULT 0,
  `is_nursing_track` tinyint(4) NOT NULL DEFAULT 0,
  `min_attendance_pct` decimal(5,2) NOT NULL DEFAULT 90.00,
  `theory_pass_mark` decimal(5,2) NOT NULL DEFAULT 40.00,
  `clinical_pass_mark` decimal(5,2) NOT NULL DEFAULT 50.00,
  `status` varchar(50) NOT NULL DEFAULT 'pending_ceo',
  `is_featured_on_homepage` tinyint(4) NOT NULL DEFAULT 0,
  `homepage_display_order` int(11) NOT NULL DEFAULT 0,
  `homepage_tagline` varchar(500) DEFAULT NULL,
  `cover_image_path` varchar(500) DEFAULT NULL,
  `entry_requirements` text DEFAULT NULL,
  `approved_by_ceo_at` datetime DEFAULT NULL,
  `approved_by_ceo_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_programs_program_code_unique` (`program_code`),
  KEY `academic_programs_approved_by_ceo_id_foreign` (`approved_by_ceo_id`),
  KEY `academic_programs_status_index` (`status`),
  KEY `academic_programs_department_status_index` (`department_id`,`status`),
  CONSTRAINT `academic_programs_approved_by_ceo_id_foreign` FOREIGN KEY (`approved_by_ceo_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `academic_programs_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `academic_programs` (add only if missing)
CALL `tich_ensure_column`('academic_programs', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('academic_programs', 'program_code', 'varchar(30) NOT NULL');
CALL `tich_ensure_column`('academic_programs', 'program_name', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('academic_programs', 'program_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('academic_programs', 'regulatory_body', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('academic_programs', 'curriculum_format', 'varchar(50) NOT NULL DEFAULT \'\\\'trimester\\\'\'');
CALL `tich_ensure_column`('academic_programs', 'department_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('academic_programs', 'duration_months', 'int(11) NULL DEFAULT NULL');
CALL `tich_ensure_column`('academic_programs', 'semester_count', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('academic_programs', 'block_count', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('academic_programs', 'is_nursing_track', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('academic_programs', 'min_attendance_pct', 'decimal(5,2) NOT NULL DEFAULT \'90.00\'');
CALL `tich_ensure_column`('academic_programs', 'theory_pass_mark', 'decimal(5,2) NOT NULL DEFAULT \'40.00\'');
CALL `tich_ensure_column`('academic_programs', 'clinical_pass_mark', 'decimal(5,2) NOT NULL DEFAULT \'50.00\'');
CALL `tich_ensure_column`('academic_programs', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending_ceo\\\'\'');
CALL `tich_ensure_column`('academic_programs', 'is_featured_on_homepage', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('academic_programs', 'homepage_display_order', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('academic_programs', 'homepage_tagline', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('academic_programs', 'cover_image_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('academic_programs', 'entry_requirements', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('academic_programs', 'approved_by_ceo_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('academic_programs', 'approved_by_ceo_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('academic_programs', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('academic_programs', 'updated_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('academic_programs', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `academic_programs` (add only if missing)
CALL `tich_ensure_index`('academic_programs', 'academic_programs_approved_by_ceo_id_foreign', '`approved_by_ceo_id`');
CALL `tich_ensure_index`('academic_programs', 'academic_programs_department_status_index', '`department_id`, `status`');
CALL `tich_ensure_unique`('academic_programs', 'academic_programs_program_code_unique', '`program_code`');
CALL `tich_ensure_index`('academic_programs', 'academic_programs_status_index', '`status`');

-- -----------------------------------------------------------------------------
-- Table: `academic_years`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `academic_years` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `year_label` varchar(20) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_years_year_label_unique` (`year_label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `academic_years` (add only if missing)
CALL `tich_ensure_column`('academic_years', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('academic_years', 'year_label', 'varchar(20) NOT NULL');
CALL `tich_ensure_column`('academic_years', 'start_date', 'date NOT NULL');
CALL `tich_ensure_column`('academic_years', 'end_date', 'date NOT NULL');
CALL `tich_ensure_column`('academic_years', 'is_current', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('academic_years', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('academic_years', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `academic_years` (add only if missing)
CALL `tich_ensure_unique`('academic_years', 'academic_years_year_label_unique', '`year_label`');

-- -----------------------------------------------------------------------------
-- Table: `accounts_payable`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `accounts_payable` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `requisition_id` bigint(20) unsigned DEFAULT NULL,
  `purchase_order_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `invoice_amount` decimal(12,2) NOT NULL,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `invoice_file_path` varchar(500) DEFAULT NULL,
  `three_way_match_status` varchar(50) NOT NULL DEFAULT 'pending',
  `three_way_match_by` bigint(20) unsigned DEFAULT NULL,
  `three_way_match_at` datetime DEFAULT NULL,
  `finance_approval_status` varchar(50) NOT NULL DEFAULT 'pending',
  `finance_approved_by` bigint(20) unsigned DEFAULT NULL,
  `finance_approved_at` datetime DEFAULT NULL,
  `payment_status` varchar(50) NOT NULL DEFAULT 'unpaid',
  `payment_date` date DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `is_quickbooks_synced` tinyint(4) NOT NULL DEFAULT 0,
  `quickbooks_sync_ref` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounts_payable_invoice_number_unique` (`invoice_number`),
  KEY `accounts_payable_supplier_id_foreign` (`supplier_id`),
  KEY `accounts_payable_requisition_id_foreign` (`requisition_id`),
  KEY `accounts_payable_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `accounts_payable_three_way_match_by_foreign` (`three_way_match_by`),
  KEY `accounts_payable_finance_approved_by_foreign` (`finance_approved_by`),
  CONSTRAINT `accounts_payable_finance_approved_by_foreign` FOREIGN KEY (`finance_approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounts_payable_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounts_payable_requisition_id_foreign` FOREIGN KEY (`requisition_id`) REFERENCES `procurement_requisitions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounts_payable_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `accounts_payable_three_way_match_by_foreign` FOREIGN KEY (`three_way_match_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `accounts_payable` (add only if missing)
CALL `tich_ensure_column`('accounts_payable', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('accounts_payable', 'invoice_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('accounts_payable', 'supplier_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('accounts_payable', 'requisition_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('accounts_payable', 'purchase_order_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('accounts_payable', 'invoice_date', 'date NOT NULL');
CALL `tich_ensure_column`('accounts_payable', 'due_date', 'date NOT NULL');
CALL `tich_ensure_column`('accounts_payable', 'invoice_amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('accounts_payable', 'tax_amount', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('accounts_payable', 'total_amount', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('accounts_payable', 'amount_paid', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('accounts_payable', 'balance', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('accounts_payable', 'invoice_file_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('accounts_payable', 'three_way_match_status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('accounts_payable', 'three_way_match_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('accounts_payable', 'three_way_match_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('accounts_payable', 'finance_approval_status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('accounts_payable', 'finance_approved_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('accounts_payable', 'finance_approved_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('accounts_payable', 'payment_status', 'varchar(50) NOT NULL DEFAULT \'\\\'unpaid\\\'\'');
CALL `tich_ensure_column`('accounts_payable', 'payment_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('accounts_payable', 'payment_reference', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('accounts_payable', 'payment_method', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('accounts_payable', 'is_quickbooks_synced', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('accounts_payable', 'quickbooks_sync_ref', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('accounts_payable', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('accounts_payable', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `accounts_payable` (add only if missing)
CALL `tich_ensure_index`('accounts_payable', 'accounts_payable_finance_approved_by_foreign', '`finance_approved_by`');
CALL `tich_ensure_unique`('accounts_payable', 'accounts_payable_invoice_number_unique', '`invoice_number`');
CALL `tich_ensure_index`('accounts_payable', 'accounts_payable_purchase_order_id_foreign', '`purchase_order_id`');
CALL `tich_ensure_index`('accounts_payable', 'accounts_payable_requisition_id_foreign', '`requisition_id`');
CALL `tich_ensure_index`('accounts_payable', 'accounts_payable_supplier_id_foreign', '`supplier_id`');
CALL `tich_ensure_index`('accounts_payable', 'accounts_payable_three_way_match_by_foreign', '`three_way_match_by`');

-- -----------------------------------------------------------------------------
-- Table: `account_ledger`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `account_ledger` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ledger_date` date NOT NULL,
  `transaction_type` varchar(50) NOT NULL,
  `debit_account_code` varchar(30) DEFAULT NULL,
  `credit_account_code` varchar(30) DEFAULT NULL,
  `debit_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `credit_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `narration` varchar(500) NOT NULL,
  `reference_table` varchar(50) DEFAULT NULL,
  `reference_id` varchar(50) DEFAULT NULL,
  `source_module` varchar(50) NOT NULL,
  `is_reversed` tinyint(4) NOT NULL DEFAULT 0,
  `reversal_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `recorded_by` bigint(20) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `account_ledger_recorded_by_foreign` (`recorded_by`),
  KEY `account_ledger_ledger_date_index` (`ledger_date`),
  KEY `account_ledger_reference_table_reference_id_index` (`reference_table`,`reference_id`),
  KEY `account_ledger_debit_account_code_credit_account_code_index` (`debit_account_code`,`credit_account_code`),
  CONSTRAINT `account_ledger_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `account_ledger` (add only if missing)
CALL `tich_ensure_column`('account_ledger', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('account_ledger', 'ledger_date', 'date NOT NULL');
CALL `tich_ensure_column`('account_ledger', 'transaction_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('account_ledger', 'debit_account_code', 'varchar(30) NULL DEFAULT NULL');
CALL `tich_ensure_column`('account_ledger', 'credit_account_code', 'varchar(30) NULL DEFAULT NULL');
CALL `tich_ensure_column`('account_ledger', 'debit_amount', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('account_ledger', 'credit_amount', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('account_ledger', 'narration', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('account_ledger', 'reference_table', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('account_ledger', 'reference_id', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('account_ledger', 'source_module', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('account_ledger', 'is_reversed', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('account_ledger', 'reversal_ledger_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('account_ledger', 'recorded_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('account_ledger', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `account_ledger` (add only if missing)
CALL `tich_ensure_index`('account_ledger', 'account_ledger_debit_account_code_credit_account_code_index', '`debit_account_code`, `credit_account_code`');
CALL `tich_ensure_index`('account_ledger', 'account_ledger_ledger_date_index', '`ledger_date`');
CALL `tich_ensure_index`('account_ledger', 'account_ledger_recorded_by_foreign', '`recorded_by`');
CALL `tich_ensure_index`('account_ledger', 'account_ledger_reference_table_reference_id_index', '`reference_table`, `reference_id`');

-- -----------------------------------------------------------------------------
-- Table: `admin_budget_requests`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_budget_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_code` varchar(50) NOT NULL,
  `planning_cycle_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `title` varchar(300) NOT NULL,
  `framework` varchar(50) NOT NULL DEFAULT 'standard',
  `budget_type` varchar(50) DEFAULT NULL,
  `standard_line_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`standard_line_items`)),
  `cbe_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cbe_details`)),
  `requested_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `verified_amount` decimal(14,2) DEFAULT NULL,
  `approved_amount` decimal(14,2) DEFAULT NULL,
  `allocated_amount` decimal(14,2) DEFAULT NULL,
  `group_allocations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`group_allocations`)),
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `justification` text DEFAULT NULL,
  `submitted_by` bigint(20) unsigned DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `is_late` tinyint(1) NOT NULL DEFAULT 0,
  `deadline_at` datetime DEFAULT NULL,
  `finance_verified_by` bigint(20) unsigned DEFAULT NULL,
  `finance_verified_at` datetime DEFAULT NULL,
  `executive_approved_by` bigint(20) unsigned DEFAULT NULL,
  `executive_approved_at` datetime DEFAULT NULL,
  `disbursed_at` datetime DEFAULT NULL,
  `disbursed_by` bigint(20) unsigned DEFAULT NULL,
  `receipt_number` varchar(100) DEFAULT NULL,
  `workflow_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_budget_requests_request_code_unique` (`request_code`),
  KEY `admin_budget_requests_planning_cycle_id_foreign` (`planning_cycle_id`),
  KEY `admin_budget_requests_department_id_foreign` (`department_id`),
  KEY `admin_budget_requests_status_department_id_index` (`status`,`department_id`),
  KEY `admin_budget_requests_submitted_by_index` (`submitted_by`),
  KEY `admin_budget_requests_submitted_at_index` (`submitted_at`),
  CONSTRAINT `admin_budget_requests_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `admin_budget_requests_planning_cycle_id_foreign` FOREIGN KEY (`planning_cycle_id`) REFERENCES `admin_planning_cycles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `admin_budget_requests` (add only if missing)
CALL `tich_ensure_column`('admin_budget_requests', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('admin_budget_requests', 'request_code', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('admin_budget_requests', 'planning_cycle_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_budget_requests', 'department_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('admin_budget_requests', 'title', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('admin_budget_requests', 'framework', 'varchar(50) NOT NULL DEFAULT \'\\\'standard\\\'\'');
CALL `tich_ensure_column`('admin_budget_requests', 'budget_type', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_budget_requests', 'standard_line_items', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_budget_requests', 'cbe_details', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_budget_requests', 'requested_amount', 'decimal(14,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('admin_budget_requests', 'verified_amount', 'decimal(14,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_budget_requests', 'approved_amount', 'decimal(14,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_budget_requests', 'allocated_amount', 'decimal(14,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_budget_requests', 'group_allocations', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_budget_requests', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'draft\\\'\'');
CALL `tich_ensure_column`('admin_budget_requests', 'justification', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_budget_requests', 'submitted_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_budget_requests', 'submitted_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_budget_requests', 'is_late', 'tinyint(1) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('admin_budget_requests', 'deadline_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_budget_requests', 'finance_verified_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_budget_requests', 'finance_verified_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_budget_requests', 'executive_approved_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_budget_requests', 'executive_approved_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_budget_requests', 'disbursed_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_budget_requests', 'disbursed_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_budget_requests', 'receipt_number', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_budget_requests', 'workflow_notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_budget_requests', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_budget_requests', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `admin_budget_requests` (add only if missing)
CALL `tich_ensure_index`('admin_budget_requests', 'admin_budget_requests_department_id_foreign', '`department_id`');
CALL `tich_ensure_index`('admin_budget_requests', 'admin_budget_requests_planning_cycle_id_foreign', '`planning_cycle_id`');
CALL `tich_ensure_unique`('admin_budget_requests', 'admin_budget_requests_request_code_unique', '`request_code`');
CALL `tich_ensure_index`('admin_budget_requests', 'admin_budget_requests_status_department_id_index', '`status`, `department_id`');
CALL `tich_ensure_index`('admin_budget_requests', 'admin_budget_requests_submitted_at_index', '`submitted_at`');
CALL `tich_ensure_index`('admin_budget_requests', 'admin_budget_requests_submitted_by_index', '`submitted_by`');

-- -----------------------------------------------------------------------------
-- Table: `admin_calendar_events`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_calendar_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fiscal_year` smallint(5) unsigned NOT NULL,
  `event_type` varchar(40) NOT NULL,
  `title` varchar(300) NOT NULL,
  `starts_on` date NOT NULL,
  `ends_on` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_calendar_events_fiscal_year_event_type_starts_on_index` (`fiscal_year`,`event_type`,`starts_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `admin_calendar_events` (add only if missing)
CALL `tich_ensure_column`('admin_calendar_events', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('admin_calendar_events', 'fiscal_year', 'smallint(5) unsigned NOT NULL');
CALL `tich_ensure_column`('admin_calendar_events', 'event_type', 'varchar(40) NOT NULL');
CALL `tich_ensure_column`('admin_calendar_events', 'title', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('admin_calendar_events', 'starts_on', 'date NOT NULL');
CALL `tich_ensure_column`('admin_calendar_events', 'ends_on', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_calendar_events', 'notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_calendar_events', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_calendar_events', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_calendar_events', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `admin_calendar_events` (add only if missing)
CALL `tich_ensure_index`('admin_calendar_events', 'admin_calendar_events_fiscal_year_event_type_starts_on_index', '`fiscal_year`, `event_type`, `starts_on`');

-- -----------------------------------------------------------------------------
-- Table: `admin_fund_allocations`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_fund_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `allocation_code` varchar(50) NOT NULL,
  `budget_request_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `fiscal_year` smallint(5) unsigned NOT NULL,
  `month` tinyint(3) unsigned DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `released_by` bigint(20) unsigned DEFAULT NULL,
  `released_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_fund_allocations_allocation_code_unique` (`allocation_code`),
  KEY `admin_fund_allocations_budget_request_id_foreign` (`budget_request_id`),
  KEY `admin_fund_allocations_department_id_foreign` (`department_id`),
  KEY `admin_fund_allocations_fiscal_year_month_status_index` (`fiscal_year`,`month`,`status`),
  CONSTRAINT `admin_fund_allocations_budget_request_id_foreign` FOREIGN KEY (`budget_request_id`) REFERENCES `admin_budget_requests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `admin_fund_allocations_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `admin_fund_allocations` (add only if missing)
CALL `tich_ensure_column`('admin_fund_allocations', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('admin_fund_allocations', 'allocation_code', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('admin_fund_allocations', 'budget_request_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_fund_allocations', 'department_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('admin_fund_allocations', 'fiscal_year', 'smallint(5) unsigned NOT NULL');
CALL `tich_ensure_column`('admin_fund_allocations', 'month', 'tinyint(3) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_fund_allocations', 'amount', 'decimal(14,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('admin_fund_allocations', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('admin_fund_allocations', 'released_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_fund_allocations', 'released_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_fund_allocations', 'notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_fund_allocations', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_fund_allocations', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `admin_fund_allocations` (add only if missing)
CALL `tich_ensure_unique`('admin_fund_allocations', 'admin_fund_allocations_allocation_code_unique', '`allocation_code`');
CALL `tich_ensure_index`('admin_fund_allocations', 'admin_fund_allocations_budget_request_id_foreign', '`budget_request_id`');
CALL `tich_ensure_index`('admin_fund_allocations', 'admin_fund_allocations_department_id_foreign', '`department_id`');
CALL `tich_ensure_index`('admin_fund_allocations', 'admin_fund_allocations_fiscal_year_month_status_index', '`fiscal_year`, `month`, `status`');

-- -----------------------------------------------------------------------------
-- Table: `admin_inspection_checks`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_inspection_checks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `check_code` varchar(50) NOT NULL,
  `area` varchar(100) NOT NULL,
  `requirement` varchar(500) NOT NULL,
  `regulator` varchar(50) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `evidence_path` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_inspection_checks_check_code_unique` (`check_code`),
  KEY `admin_inspection_checks_status_regulator_index` (`status`,`regulator`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `admin_inspection_checks` (add only if missing)
CALL `tich_ensure_column`('admin_inspection_checks', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('admin_inspection_checks', 'check_code', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('admin_inspection_checks', 'area', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('admin_inspection_checks', 'requirement', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('admin_inspection_checks', 'regulator', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_inspection_checks', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('admin_inspection_checks', 'evidence_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_inspection_checks', 'notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_inspection_checks', 'reviewed_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_inspection_checks', 'reviewed_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_inspection_checks', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_inspection_checks', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `admin_inspection_checks` (add only if missing)
CALL `tich_ensure_unique`('admin_inspection_checks', 'admin_inspection_checks_check_code_unique', '`check_code`');
CALL `tich_ensure_index`('admin_inspection_checks', 'admin_inspection_checks_status_regulator_index', '`status`, `regulator`');

-- -----------------------------------------------------------------------------
-- Table: `admin_planning_cycles`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_planning_cycles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cycle_code` varchar(50) NOT NULL,
  `title` varchar(300) NOT NULL,
  `plan_tier` varchar(30) NOT NULL,
  `fiscal_year` smallint(5) unsigned NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `requisition_deadline` datetime NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'open',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_planning_cycles_cycle_code_unique` (`cycle_code`),
  KEY `admin_planning_cycles_plan_tier_fiscal_year_status_index` (`plan_tier`,`fiscal_year`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `admin_planning_cycles` (add only if missing)
CALL `tich_ensure_column`('admin_planning_cycles', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('admin_planning_cycles', 'cycle_code', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('admin_planning_cycles', 'title', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('admin_planning_cycles', 'plan_tier', 'varchar(30) NOT NULL');
CALL `tich_ensure_column`('admin_planning_cycles', 'fiscal_year', 'smallint(5) unsigned NOT NULL');
CALL `tich_ensure_column`('admin_planning_cycles', 'period_start', 'date NOT NULL');
CALL `tich_ensure_column`('admin_planning_cycles', 'period_end', 'date NOT NULL');
CALL `tich_ensure_column`('admin_planning_cycles', 'requisition_deadline', 'datetime NOT NULL');
CALL `tich_ensure_column`('admin_planning_cycles', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'open\\\'\'');
CALL `tich_ensure_column`('admin_planning_cycles', 'notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_planning_cycles', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_planning_cycles', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_planning_cycles', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `admin_planning_cycles` (add only if missing)
CALL `tich_ensure_unique`('admin_planning_cycles', 'admin_planning_cycles_cycle_code_unique', '`cycle_code`');
CALL `tich_ensure_index`('admin_planning_cycles', 'admin_planning_cycles_plan_tier_fiscal_year_status_index', '`plan_tier`, `fiscal_year`, `status`');

-- -----------------------------------------------------------------------------
-- Table: `admin_quickbooks_sync_logs`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_quickbooks_sync_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sync_batch` varchar(50) NOT NULL,
  `source_type` varchar(50) NOT NULL,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `external_ref` varchar(150) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `payload` text DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `triggered_by` bigint(20) unsigned DEFAULT NULL,
  `synced_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_quickbooks_sync_logs_status_source_type_index` (`status`,`source_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `admin_quickbooks_sync_logs` (add only if missing)
CALL `tich_ensure_column`('admin_quickbooks_sync_logs', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('admin_quickbooks_sync_logs', 'sync_batch', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('admin_quickbooks_sync_logs', 'source_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('admin_quickbooks_sync_logs', 'source_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_quickbooks_sync_logs', 'external_ref', 'varchar(150) NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_quickbooks_sync_logs', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('admin_quickbooks_sync_logs', 'payload', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_quickbooks_sync_logs', 'error_message', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_quickbooks_sync_logs', 'triggered_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_quickbooks_sync_logs', 'synced_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_quickbooks_sync_logs', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_quickbooks_sync_logs', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `admin_quickbooks_sync_logs` (add only if missing)
CALL `tich_ensure_index`('admin_quickbooks_sync_logs', 'admin_quickbooks_sync_logs_status_source_type_index', '`status`, `source_type`');

-- -----------------------------------------------------------------------------
-- Table: `admin_statutory_certifications`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_statutory_certifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `certificate_code` varchar(50) NOT NULL,
  `title` varchar(300) NOT NULL,
  `authority` varchar(255) NOT NULL,
  `certificate_number` varchar(150) DEFAULT NULL,
  `issued_on` date DEFAULT NULL,
  `expires_on` date DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `document_path` varchar(500) DEFAULT NULL,
  `alignment_notes` text DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_statutory_certifications_certificate_code_unique` (`certificate_code`),
  KEY `admin_statutory_certifications_authority_status_expires_on_index` (`authority`,`status`,`expires_on`),
  KEY `admin_statutory_certifications_status_expires_index` (`status`,`expires_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `admin_statutory_certifications` (add only if missing)
CALL `tich_ensure_column`('admin_statutory_certifications', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('admin_statutory_certifications', 'certificate_code', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('admin_statutory_certifications', 'title', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('admin_statutory_certifications', 'authority', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('admin_statutory_certifications', 'certificate_number', 'varchar(150) NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_statutory_certifications', 'issued_on', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_statutory_certifications', 'expires_on', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_statutory_certifications', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'active\\\'\'');
CALL `tich_ensure_column`('admin_statutory_certifications', 'document_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_statutory_certifications', 'alignment_notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_statutory_certifications', 'updated_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_statutory_certifications', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_statutory_certifications', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `admin_statutory_certifications` (add only if missing)
CALL `tich_ensure_index`('admin_statutory_certifications', 'admin_statutory_certifications_authority_status_expires_on_index', '`authority`, `status`, `expires_on`');
CALL `tich_ensure_unique`('admin_statutory_certifications', 'admin_statutory_certifications_certificate_code_unique', '`certificate_code`');
CALL `tich_ensure_index`('admin_statutory_certifications', 'admin_statutory_certifications_status_expires_index', '`status`, `expires_on`');

-- -----------------------------------------------------------------------------
-- Table: `admin_tasks`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `planning_cycle_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `title` varchar(300) NOT NULL,
  `description` text DEFAULT NULL,
  `owner_id` bigint(20) unsigned DEFAULT NULL,
  `due_on` date NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `budget_implication` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_tasks_planning_cycle_id_foreign` (`planning_cycle_id`),
  KEY `admin_tasks_department_id_due_on_status_index` (`department_id`,`due_on`,`status`),
  CONSTRAINT `admin_tasks_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `admin_tasks_planning_cycle_id_foreign` FOREIGN KEY (`planning_cycle_id`) REFERENCES `admin_planning_cycles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `admin_tasks` (add only if missing)
CALL `tich_ensure_column`('admin_tasks', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('admin_tasks', 'planning_cycle_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_tasks', 'department_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('admin_tasks', 'title', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('admin_tasks', 'description', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_tasks', 'owner_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_tasks', 'due_on', 'date NOT NULL');
CALL `tich_ensure_column`('admin_tasks', 'status', 'varchar(30) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('admin_tasks', 'budget_implication', 'decimal(14,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('admin_tasks', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_tasks', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `admin_tasks` (add only if missing)
CALL `tich_ensure_index`('admin_tasks', 'admin_tasks_department_id_due_on_status_index', '`department_id`, `due_on`, `status`');
CALL `tich_ensure_index`('admin_tasks', 'admin_tasks_planning_cycle_id_foreign', '`planning_cycle_id`');

-- -----------------------------------------------------------------------------
-- Table: `admin_variances`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_variances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `planning_cycle_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `fiscal_year` smallint(5) unsigned NOT NULL,
  `month` tinyint(3) unsigned NOT NULL,
  `planned_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `actual_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `explanation` text DEFAULT NULL,
  `lessons` text DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_variances_planning_cycle_id_foreign` (`planning_cycle_id`),
  KEY `admin_variances_department_id_fiscal_year_month_index` (`department_id`,`fiscal_year`,`month`),
  CONSTRAINT `admin_variances_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `admin_variances_planning_cycle_id_foreign` FOREIGN KEY (`planning_cycle_id`) REFERENCES `admin_planning_cycles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `admin_variances` (add only if missing)
CALL `tich_ensure_column`('admin_variances', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('admin_variances', 'planning_cycle_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_variances', 'department_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('admin_variances', 'fiscal_year', 'smallint(5) unsigned NOT NULL');
CALL `tich_ensure_column`('admin_variances', 'month', 'tinyint(3) unsigned NOT NULL');
CALL `tich_ensure_column`('admin_variances', 'planned_amount', 'decimal(14,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('admin_variances', 'actual_amount', 'decimal(14,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('admin_variances', 'explanation', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_variances', 'lessons', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_variances', 'reviewed_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_variances', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('admin_variances', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `admin_variances` (add only if missing)
CALL `tich_ensure_index`('admin_variances', 'admin_variances_department_id_fiscal_year_month_index', '`department_id`, `fiscal_year`, `month`');
CALL `tich_ensure_index`('admin_variances', 'admin_variances_planning_cycle_id_foreign', '`planning_cycle_id`');

-- -----------------------------------------------------------------------------
-- Table: `admission_letters`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admission_letters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `letter_number` varchar(50) NOT NULL,
  `issue_date` date NOT NULL,
  `reporting_date` date NOT NULL,
  `enrollment_campus_id` bigint(20) unsigned NOT NULL,
  `generated_by` bigint(20) unsigned NOT NULL,
  `is_printed` tinyint(4) NOT NULL DEFAULT 0,
  `is_dispatched` tinyint(4) NOT NULL DEFAULT 0,
  `dispatched_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admission_letters_letter_number_unique` (`letter_number`),
  KEY `admission_letters_enrollment_campus_id_foreign` (`enrollment_campus_id`),
  KEY `admission_letters_generated_by_foreign` (`generated_by`),
  KEY `admission_letters_student_id_foreign` (`student_id`),
  CONSTRAINT `admission_letters_enrollment_campus_id_foreign` FOREIGN KEY (`enrollment_campus_id`) REFERENCES `campuses` (`id`),
  CONSTRAINT `admission_letters_generated_by_foreign` FOREIGN KEY (`generated_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `admission_letters_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `admission_letters` (add only if missing)
CALL `tich_ensure_column`('admission_letters', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('admission_letters', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('admission_letters', 'letter_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('admission_letters', 'issue_date', 'date NOT NULL');
CALL `tich_ensure_column`('admission_letters', 'reporting_date', 'date NOT NULL');
CALL `tich_ensure_column`('admission_letters', 'enrollment_campus_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('admission_letters', 'generated_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('admission_letters', 'is_printed', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('admission_letters', 'is_dispatched', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('admission_letters', 'dispatched_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('admission_letters', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('admission_letters', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `admission_letters` (add only if missing)
CALL `tich_ensure_index`('admission_letters', 'admission_letters_enrollment_campus_id_foreign', '`enrollment_campus_id`');
CALL `tich_ensure_index`('admission_letters', 'admission_letters_generated_by_foreign', '`generated_by`');
CALL `tich_ensure_unique`('admission_letters', 'admission_letters_letter_number_unique', '`letter_number`');
CALL `tich_ensure_index`('admission_letters', 'admission_letters_student_id_foreign', '`student_id`');

-- -----------------------------------------------------------------------------
-- Table: `applicants`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `applicants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `application_number` varchar(50) NOT NULL,
  `program_id` bigint(20) unsigned NOT NULL,
  `intake_year` smallint(5) unsigned DEFAULT NULL,
  `intake_month` tinyint(3) unsigned DEFAULT NULL,
  `handling_department_id` bigint(20) unsigned DEFAULT NULL,
  `preferred_campus_id` bigint(20) unsigned DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `surname` varchar(100) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` varchar(20) NOT NULL,
  `national_id_number` varchar(50) DEFAULT NULL,
  `passport_number` varchar(50) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone_number` varchar(30) NOT NULL,
  `home_county` varchar(100) DEFAULT NULL,
  `next_of_kin_name` varchar(300) DEFAULT NULL,
  `next_of_kin_relationship` varchar(50) DEFAULT NULL,
  `next_of_kin_phone` varchar(30) DEFAULT NULL,
  `next_of_kin_address` varchar(500) DEFAULT NULL,
  `entry_qualification` varchar(50) DEFAULT NULL,
  `sponsorship_type` varchar(50) DEFAULT NULL,
  `sponsor_organization` varchar(200) DEFAULT NULL,
  `sponsor_address` varchar(500) DEFAULT NULL,
  `sponsor_phone` varchar(30) DEFAULT NULL,
  `application_fee_paid` tinyint(4) NOT NULL DEFAULT 0,
  `application_fee_payment_ref` varchar(100) DEFAULT NULL,
  `application_fee_paid_at` datetime DEFAULT NULL,
  `rpl_application_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'submitted',
  `academic_review_status` varchar(50) NOT NULL DEFAULT 'pending',
  `review_notes` text DEFAULT NULL,
  `rejection_reason` varchar(500) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `academic_reviewer_id` bigint(20) unsigned DEFAULT NULL,
  `application_source` varchar(50) NOT NULL DEFAULT 'online',
  `is_offline_cached` tinyint(4) NOT NULL DEFAULT 0,
  `offline_sync_id` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `applicants_application_number_unique` (`application_number`),
  KEY `applicants_program_id_foreign` (`program_id`),
  KEY `applicants_preferred_campus_id_foreign` (`preferred_campus_id`),
  KEY `applicants_rpl_application_id_foreign` (`rpl_application_id`),
  KEY `applicants_academic_reviewer_id_foreign` (`academic_reviewer_id`),
  KEY `applicants_handling_department_id_foreign` (`handling_department_id`),
  KEY `applicants_status_index` (`status`),
  KEY `applicants_status_program_id_index` (`status`,`program_id`),
  KEY `applicants_email_index` (`email`),
  KEY `applicants_created_at_index` (`created_at`),
  KEY `applicants_academic_review_status_index` (`academic_review_status`),
  CONSTRAINT `applicants_academic_reviewer_id_foreign` FOREIGN KEY (`academic_reviewer_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `applicants_handling_department_id_foreign` FOREIGN KEY (`handling_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `applicants_preferred_campus_id_foreign` FOREIGN KEY (`preferred_campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `applicants_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `academic_programs` (`id`),
  CONSTRAINT `applicants_rpl_application_id_foreign` FOREIGN KEY (`rpl_application_id`) REFERENCES `rpl_applications` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `applicants` (add only if missing)
CALL `tich_ensure_column`('applicants', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('applicants', 'application_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('applicants', 'program_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('applicants', 'intake_year', 'smallint(5) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'intake_month', 'tinyint(3) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'handling_department_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'preferred_campus_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'first_name', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('applicants', 'middle_name', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'surname', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('applicants', 'date_of_birth', 'date NOT NULL');
CALL `tich_ensure_column`('applicants', 'gender', 'varchar(20) NOT NULL');
CALL `tich_ensure_column`('applicants', 'national_id_number', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'passport_number', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'email', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('applicants', 'phone_number', 'varchar(30) NOT NULL');
CALL `tich_ensure_column`('applicants', 'home_county', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'next_of_kin_name', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'next_of_kin_relationship', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'next_of_kin_phone', 'varchar(30) NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'next_of_kin_address', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'entry_qualification', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'sponsorship_type', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'sponsor_organization', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'sponsor_address', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'sponsor_phone', 'varchar(30) NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'application_fee_paid', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('applicants', 'application_fee_payment_ref', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'application_fee_paid_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'rpl_application_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'submitted\\\'\'');
CALL `tich_ensure_column`('applicants', 'academic_review_status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('applicants', 'review_notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'rejection_reason', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'reviewed_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'academic_reviewer_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'application_source', 'varchar(50) NOT NULL DEFAULT \'\\\'online\\\'\'');
CALL `tich_ensure_column`('applicants', 'is_offline_cached', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('applicants', 'offline_sync_id', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('applicants', 'updated_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('applicants', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `applicants` (add only if missing)
CALL `tich_ensure_index`('applicants', 'applicants_academic_reviewer_id_foreign', '`academic_reviewer_id`');
CALL `tich_ensure_index`('applicants', 'applicants_academic_review_status_index', '`academic_review_status`');
CALL `tich_ensure_unique`('applicants', 'applicants_application_number_unique', '`application_number`');
CALL `tich_ensure_index`('applicants', 'applicants_created_at_index', '`created_at`');
CALL `tich_ensure_index`('applicants', 'applicants_email_index', '`email`');
CALL `tich_ensure_index`('applicants', 'applicants_handling_department_id_foreign', '`handling_department_id`');
CALL `tich_ensure_index`('applicants', 'applicants_preferred_campus_id_foreign', '`preferred_campus_id`');
CALL `tich_ensure_index`('applicants', 'applicants_program_id_foreign', '`program_id`');
CALL `tich_ensure_index`('applicants', 'applicants_rpl_application_id_foreign', '`rpl_application_id`');
CALL `tich_ensure_index`('applicants', 'applicants_status_index', '`status`');
CALL `tich_ensure_index`('applicants', 'applicants_status_program_id_index', '`status`, `program_id`');

-- -----------------------------------------------------------------------------
-- Table: `application_documents`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `application_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `applicant_id` bigint(20) unsigned NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `is_verified` tinyint(4) NOT NULL DEFAULT 0,
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `application_documents_applicant_id_foreign` (`applicant_id`),
  KEY `application_documents_verified_by_foreign` (`verified_by`),
  CONSTRAINT `application_documents_applicant_id_foreign` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`),
  CONSTRAINT `application_documents_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `application_documents` (add only if missing)
CALL `tich_ensure_column`('application_documents', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('application_documents', 'applicant_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('application_documents', 'document_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('application_documents', 'file_path', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('application_documents', 'original_filename', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('application_documents', 'mime_type', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('application_documents', 'is_verified', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('application_documents', 'verified_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('application_documents', 'verified_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('application_documents', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('application_documents', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `application_documents` (add only if missing)
CALL `tich_ensure_index`('application_documents', 'application_documents_applicant_id_foreign', '`applicant_id`');
CALL `tich_ensure_index`('application_documents', 'application_documents_verified_by_foreign', '`verified_by`');

-- -----------------------------------------------------------------------------
-- Table: `assets`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `assets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `asset_number` varchar(50) NOT NULL,
  `asset_name` varchar(300) NOT NULL,
  `asset_category` varchar(50) NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `acquisition_date` date NOT NULL,
  `acquisition_cost` decimal(12,2) NOT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `purchase_order_id` bigint(20) unsigned DEFAULT NULL,
  `useful_life_years` int(11) NOT NULL DEFAULT 0,
  `depreciation_method` varchar(50) NOT NULL DEFAULT 'straight_line',
  `salvage_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `current_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `depreciation_per_year` decimal(12,2) NOT NULL DEFAULT 0.00,
  `accumulated_depreciation` decimal(12,2) NOT NULL DEFAULT 0.00,
  `condition` varchar(50) NOT NULL DEFAULT 'new',
  `disposed_date` date DEFAULT NULL,
  `disposed_value` decimal(12,2) DEFAULT NULL,
  `disposed_reason` varchar(500) DEFAULT NULL,
  `warranty_expiry_date` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `assets_asset_number_unique` (`asset_number`),
  KEY `assets_supplier_id_foreign` (`supplier_id`),
  KEY `assets_purchase_order_id_foreign` (`purchase_order_id`),
  CONSTRAINT `assets_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `assets_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `assets` (add only if missing)
CALL `tich_ensure_column`('assets', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('assets', 'asset_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('assets', 'asset_name', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('assets', 'asset_category', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('assets', 'serial_number', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('assets', 'description', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('assets', 'acquisition_date', 'date NOT NULL');
CALL `tich_ensure_column`('assets', 'acquisition_cost', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('assets', 'supplier_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('assets', 'purchase_order_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('assets', 'useful_life_years', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('assets', 'depreciation_method', 'varchar(50) NOT NULL DEFAULT \'\\\'straight_line\\\'\'');
CALL `tich_ensure_column`('assets', 'salvage_value', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('assets', 'current_value', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('assets', 'depreciation_per_year', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('assets', 'accumulated_depreciation', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('assets', 'condition', 'varchar(50) NOT NULL DEFAULT \'\\\'new\\\'\'');
CALL `tich_ensure_column`('assets', 'disposed_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('assets', 'disposed_value', 'decimal(12,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('assets', 'disposed_reason', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('assets', 'warranty_expiry_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('assets', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('assets', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `assets` (add only if missing)
CALL `tich_ensure_unique`('assets', 'assets_asset_number_unique', '`asset_number`');
CALL `tich_ensure_index`('assets', 'assets_purchase_order_id_foreign', '`purchase_order_id`');
CALL `tich_ensure_index`('assets', 'assets_supplier_id_foreign', '`supplier_id`');

-- -----------------------------------------------------------------------------
-- Table: `asset_assignments`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `asset_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint(20) unsigned NOT NULL,
  `assigned_to_type` varchar(50) NOT NULL,
  `assigned_to_id` bigint(20) unsigned NOT NULL,
  `assignment_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `is_returned` tinyint(4) NOT NULL DEFAULT 0,
  `condition_on_assignment` varchar(50) NOT NULL DEFAULT 'new',
  `condition_on_return` varchar(50) DEFAULT NULL,
  `assigned_by` bigint(20) unsigned NOT NULL,
  `returned_to` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `asset_assignments_asset_id_foreign` (`asset_id`),
  KEY `asset_assignments_assigned_by_foreign` (`assigned_by`),
  KEY `asset_assignments_returned_to_foreign` (`returned_to`),
  CONSTRAINT `asset_assignments_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`),
  CONSTRAINT `asset_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `asset_assignments_returned_to_foreign` FOREIGN KEY (`returned_to`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `asset_assignments` (add only if missing)
CALL `tich_ensure_column`('asset_assignments', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('asset_assignments', 'asset_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('asset_assignments', 'assigned_to_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('asset_assignments', 'assigned_to_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('asset_assignments', 'assignment_date', 'date NOT NULL');
CALL `tich_ensure_column`('asset_assignments', 'return_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('asset_assignments', 'is_returned', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('asset_assignments', 'condition_on_assignment', 'varchar(50) NOT NULL DEFAULT \'\\\'new\\\'\'');
CALL `tich_ensure_column`('asset_assignments', 'condition_on_return', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('asset_assignments', 'assigned_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('asset_assignments', 'returned_to', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('asset_assignments', 'notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('asset_assignments', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `asset_assignments` (add only if missing)
CALL `tich_ensure_index`('asset_assignments', 'asset_assignments_asset_id_foreign', '`asset_id`');
CALL `tich_ensure_index`('asset_assignments', 'asset_assignments_assigned_by_foreign', '`assigned_by`');
CALL `tich_ensure_index`('asset_assignments', 'asset_assignments_returned_to_foreign', '`returned_to`');

-- -----------------------------------------------------------------------------
-- Table: `attendance_records`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `attendance_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `is_present` tinyint(4) NOT NULL DEFAULT 0,
  `sign_in_time` time DEFAULT NULL,
  `recorded_by_tutor` tinyint(4) NOT NULL DEFAULT 0,
  `verified_by_hod` tinyint(4) NOT NULL DEFAULT 0,
  `verification_note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `att_rec_unique` (`session_id`,`student_id`),
  KEY `attendance_records_student_id_index` (`student_id`),
  CONSTRAINT `attendance_records_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `attendance_sessions` (`id`),
  CONSTRAINT `attendance_records_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `attendance_records` (add only if missing)
CALL `tich_ensure_column`('attendance_records', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('attendance_records', 'session_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('attendance_records', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('attendance_records', 'is_present', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('attendance_records', 'sign_in_time', 'time NULL DEFAULT NULL');
CALL `tich_ensure_column`('attendance_records', 'recorded_by_tutor', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('attendance_records', 'verified_by_hod', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('attendance_records', 'verification_note', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('attendance_records', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `attendance_records` (add only if missing)
CALL `tich_ensure_index`('attendance_records', 'attendance_records_student_id_index', '`student_id`');
CALL `tich_ensure_unique`('attendance_records', 'att_rec_unique', '`session_id`, `student_id`');

-- -----------------------------------------------------------------------------
-- Table: `attendance_sessions`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `attendance_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_number` varchar(50) NOT NULL,
  `unit_allocation_id` bigint(20) unsigned NOT NULL,
  `program_timetable_session_id` bigint(20) unsigned DEFAULT NULL,
  `session_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `venue` varchar(200) DEFAULT NULL,
  `session_type` varchar(50) NOT NULL DEFAULT 'physical',
  `virtual_meeting_url` varchar(500) DEFAULT NULL,
  `is_mandatory` tinyint(4) NOT NULL DEFAULT 1,
  `total_expected_attendees` int(11) NOT NULL DEFAULT 0,
  `signed_sheet_image_path` varchar(500) DEFAULT NULL,
  `sheet_image_hash` varchar(64) DEFAULT NULL,
  `class_photo_image_path` varchar(500) DEFAULT NULL,
  `class_photo_image_hash` varchar(64) DEFAULT NULL,
  `recorded_by` bigint(20) unsigned NOT NULL,
  `recorded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_locked` tinyint(4) NOT NULL DEFAULT 0,
  `verification_status` varchar(50) NOT NULL DEFAULT 'draft',
  `submitted_at` datetime DEFAULT NULL,
  `hod_verified_by` bigint(20) unsigned DEFAULT NULL,
  `hod_verified_at` datetime DEFAULT NULL,
  `registrar_verified_by` bigint(20) unsigned DEFAULT NULL,
  `registrar_verified_at` datetime DEFAULT NULL,
  `roster_verified_by` bigint(20) unsigned DEFAULT NULL,
  `roster_verified_at` datetime DEFAULT NULL,
  `exam_eligibility_checked_by` bigint(20) unsigned DEFAULT NULL,
  `exam_eligibility_checked_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_sessions_session_number_unique` (`session_number`),
  UNIQUE KEY `att_sess_timetable_slot_date_unique` (`program_timetable_session_id`,`session_date`),
  KEY `attendance_sessions_unit_allocation_id_foreign` (`unit_allocation_id`),
  KEY `attendance_sessions_recorded_by_foreign` (`recorded_by`),
  KEY `attendance_sessions_hod_verified_by_foreign` (`hod_verified_by`),
  KEY `attendance_sessions_registrar_verified_by_foreign` (`registrar_verified_by`),
  CONSTRAINT `att_sess_timetable_slot_fk` FOREIGN KEY (`program_timetable_session_id`) REFERENCES `program_timetable_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendance_sessions_hod_verified_by_foreign` FOREIGN KEY (`hod_verified_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendance_sessions_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `attendance_sessions_registrar_verified_by_foreign` FOREIGN KEY (`registrar_verified_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendance_sessions_unit_allocation_id_foreign` FOREIGN KEY (`unit_allocation_id`) REFERENCES `unit_allocations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `attendance_sessions` (add only if missing)
CALL `tich_ensure_column`('attendance_sessions', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('attendance_sessions', 'session_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('attendance_sessions', 'unit_allocation_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('attendance_sessions', 'program_timetable_session_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('attendance_sessions', 'session_date', 'date NOT NULL');
CALL `tich_ensure_column`('attendance_sessions', 'start_time', 'time NOT NULL');
CALL `tich_ensure_column`('attendance_sessions', 'end_time', 'time NOT NULL');
CALL `tich_ensure_column`('attendance_sessions', 'venue', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('attendance_sessions', 'session_type', 'varchar(50) NOT NULL DEFAULT \'\\\'physical\\\'\'');
CALL `tich_ensure_column`('attendance_sessions', 'virtual_meeting_url', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('attendance_sessions', 'is_mandatory', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('attendance_sessions', 'total_expected_attendees', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('attendance_sessions', 'signed_sheet_image_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('attendance_sessions', 'sheet_image_hash', 'varchar(64) NULL DEFAULT NULL');
CALL `tich_ensure_column`('attendance_sessions', 'class_photo_image_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('attendance_sessions', 'class_photo_image_hash', 'varchar(64) NULL DEFAULT NULL');
CALL `tich_ensure_column`('attendance_sessions', 'recorded_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('attendance_sessions', 'recorded_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('attendance_sessions', 'is_locked', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('attendance_sessions', 'verification_status', 'varchar(50) NOT NULL DEFAULT \'\\\'draft\\\'\'');
CALL `tich_ensure_column`('attendance_sessions', 'submitted_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('attendance_sessions', 'hod_verified_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('attendance_sessions', 'hod_verified_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('attendance_sessions', 'registrar_verified_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('attendance_sessions', 'registrar_verified_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('attendance_sessions', 'roster_verified_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('attendance_sessions', 'roster_verified_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('attendance_sessions', 'exam_eligibility_checked_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('attendance_sessions', 'exam_eligibility_checked_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `attendance_sessions` (add only if missing)
CALL `tich_ensure_index`('attendance_sessions', 'attendance_sessions_hod_verified_by_foreign', '`hod_verified_by`');
CALL `tich_ensure_index`('attendance_sessions', 'attendance_sessions_recorded_by_foreign', '`recorded_by`');
CALL `tich_ensure_index`('attendance_sessions', 'attendance_sessions_registrar_verified_by_foreign', '`registrar_verified_by`');
CALL `tich_ensure_unique`('attendance_sessions', 'attendance_sessions_session_number_unique', '`session_number`');
CALL `tich_ensure_index`('attendance_sessions', 'attendance_sessions_unit_allocation_id_foreign', '`unit_allocation_id`');
CALL `tich_ensure_unique`('attendance_sessions', 'att_sess_timetable_slot_date_unique', '`program_timetable_session_id`, `session_date`');

-- -----------------------------------------------------------------------------
-- Table: `attendance_summaries`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `attendance_summaries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `unit_id` bigint(20) unsigned NOT NULL,
  `semester_id` bigint(20) unsigned NOT NULL,
  `total_sessions` int(11) NOT NULL DEFAULT 0,
  `total_present` int(11) NOT NULL DEFAULT 0,
  `attendance_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `status_flag` varchar(20) NOT NULL DEFAULT 'green',
  `last_calculated_at` datetime NOT NULL,
  `amber_alert_sent_at` datetime DEFAULT NULL,
  `red_alert_sent_at` datetime DEFAULT NULL,
  `exam_eligibility_blocked` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `att_sum_unique` (`student_id`,`unit_id`,`semester_id`),
  KEY `attendance_summaries_unit_id_foreign` (`unit_id`),
  KEY `attendance_summaries_semester_id_foreign` (`semester_id`),
  CONSTRAINT `attendance_summaries_semester_id_foreign` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`),
  CONSTRAINT `attendance_summaries_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `attendance_summaries_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `attendance_summaries` (add only if missing)
CALL `tich_ensure_column`('attendance_summaries', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('attendance_summaries', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('attendance_summaries', 'unit_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('attendance_summaries', 'semester_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('attendance_summaries', 'total_sessions', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('attendance_summaries', 'total_present', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('attendance_summaries', 'attendance_percentage', 'decimal(5,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('attendance_summaries', 'status_flag', 'varchar(20) NOT NULL DEFAULT \'\\\'green\\\'\'');
CALL `tich_ensure_column`('attendance_summaries', 'last_calculated_at', 'datetime NOT NULL');
CALL `tich_ensure_column`('attendance_summaries', 'amber_alert_sent_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('attendance_summaries', 'red_alert_sent_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('attendance_summaries', 'exam_eligibility_blocked', 'tinyint(4) NOT NULL DEFAULT \'0\'');

-- Indexes for `attendance_summaries` (add only if missing)
CALL `tich_ensure_index`('attendance_summaries', 'attendance_summaries_semester_id_foreign', '`semester_id`');
CALL `tich_ensure_index`('attendance_summaries', 'attendance_summaries_unit_id_foreign', '`unit_id`');
CALL `tich_ensure_unique`('attendance_summaries', 'att_sum_unique', '`student_id`, `unit_id`, `semester_id`');

-- -----------------------------------------------------------------------------
-- Table: `audit_logs`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(100) NOT NULL,
  `entity_id` varchar(50) NOT NULL,
  `old_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_value`)),
  `new_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_value`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `client_context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`client_context`)),
  `reason` varchar(500) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'success',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `module` varchar(50) DEFAULT NULL,
  `previous_hash` varchar(64) DEFAULT NULL,
  `record_hash` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  KEY `audit_logs_user_id_index` (`user_id`),
  KEY `audit_logs_action_index` (`action`),
  KEY `audit_logs_created_at_index` (`created_at`),
  KEY `audit_logs_module_index` (`module`),
  KEY `audit_logs_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `audit_logs` (add only if missing)
CALL `tich_ensure_column`('audit_logs', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('audit_logs', 'user_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('audit_logs', 'action', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('audit_logs', 'entity_type', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('audit_logs', 'entity_id', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('audit_logs', 'old_value', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('audit_logs', 'new_value', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('audit_logs', 'ip_address', 'varchar(45) NULL DEFAULT NULL');
CALL `tich_ensure_column`('audit_logs', 'user_agent', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('audit_logs', 'client_context', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('audit_logs', 'reason', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('audit_logs', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'success\\\'\'');
CALL `tich_ensure_column`('audit_logs', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('audit_logs', 'module', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('audit_logs', 'previous_hash', 'varchar(64) NULL DEFAULT NULL');
CALL `tich_ensure_column`('audit_logs', 'record_hash', 'varchar(64) NULL DEFAULT NULL');

-- Indexes for `audit_logs` (add only if missing)
CALL `tich_ensure_index`('audit_logs', 'audit_logs_action_index', '`action`');
CALL `tich_ensure_index`('audit_logs', 'audit_logs_created_at_index', '`created_at`');
CALL `tich_ensure_index`('audit_logs', 'audit_logs_entity_type_entity_id_index', '`entity_type`, `entity_id`');
CALL `tich_ensure_index`('audit_logs', 'audit_logs_module_index', '`module`');
CALL `tich_ensure_index`('audit_logs', 'audit_logs_status_index', '`status`');
CALL `tich_ensure_index`('audit_logs', 'audit_logs_user_id_index', '`user_id`');

-- -----------------------------------------------------------------------------
-- Table: `bank_transactions`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bank_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_date` date NOT NULL,
  `value_date` date NOT NULL,
  `description` varchar(500) NOT NULL,
  `debit_amount` decimal(12,2) NOT NULL,
  `credit_amount` decimal(12,2) NOT NULL,
  `balance` decimal(12,2) NOT NULL,
  `bank_reference` varchar(100) DEFAULT NULL,
  `eft_reference` varchar(100) DEFAULT NULL,
  `is_reconciled` tinyint(4) NOT NULL DEFAULT 0,
  `reconciled_at` datetime DEFAULT NULL,
  `source_file` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `bank_transactions` (add only if missing)
CALL `tich_ensure_column`('bank_transactions', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('bank_transactions', 'transaction_date', 'date NOT NULL');
CALL `tich_ensure_column`('bank_transactions', 'value_date', 'date NOT NULL');
CALL `tich_ensure_column`('bank_transactions', 'description', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('bank_transactions', 'debit_amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('bank_transactions', 'credit_amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('bank_transactions', 'balance', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('bank_transactions', 'bank_reference', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('bank_transactions', 'eft_reference', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('bank_transactions', 'is_reconciled', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('bank_transactions', 'reconciled_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('bank_transactions', 'source_file', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('bank_transactions', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `bank_transactions` (add only if missing)
-- (no secondary indexes)

-- -----------------------------------------------------------------------------
-- Table: `blog_categories`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blog_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blog_categories_name_unique` (`name`),
  UNIQUE KEY `blog_categories_slug_unique` (`slug`),
  KEY `blog_categories_created_by_foreign` (`created_by`),
  KEY `blog_categories_updated_by_foreign` (`updated_by`),
  CONSTRAINT `blog_categories_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `blog_categories_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `blog_categories` (add only if missing)
CALL `tich_ensure_column`('blog_categories', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('blog_categories', 'name', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('blog_categories', 'slug', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('blog_categories', 'description', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('blog_categories', 'display_order', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('blog_categories', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('blog_categories', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('blog_categories', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('blog_categories', 'updated_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('blog_categories', 'updated_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `blog_categories` (add only if missing)
CALL `tich_ensure_index`('blog_categories', 'blog_categories_created_by_foreign', '`created_by`');
CALL `tich_ensure_unique`('blog_categories', 'blog_categories_name_unique', '`name`');
CALL `tich_ensure_unique`('blog_categories', 'blog_categories_slug_unique', '`slug`');
CALL `tich_ensure_index`('blog_categories', 'blog_categories_updated_by_foreign', '`updated_by`');

-- -----------------------------------------------------------------------------
-- Table: `blog_posts`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(300) NOT NULL,
  `subtitle` varchar(500) DEFAULT NULL,
  `slug` varchar(300) NOT NULL,
  `excerpt` varchar(500) DEFAULT NULL,
  `body` longtext NOT NULL,
  `featured_image_path` varchar(500) DEFAULT NULL,
  `author_staff_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `reading_time_minutes` int(11) DEFAULT NULL,
  `view_count` int(11) NOT NULL DEFAULT 0,
  `seo_meta_title` varchar(300) DEFAULT NULL,
  `seo_meta_description` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blog_posts_slug_unique` (`slug`),
  KEY `blog_posts_author_staff_id_foreign` (`author_staff_id`),
  KEY `blog_posts_created_by_foreign` (`created_by`),
  KEY `blog_posts_updated_by_foreign` (`updated_by`),
  CONSTRAINT `blog_posts_author_staff_id_foreign` FOREIGN KEY (`author_staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `blog_posts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `blog_posts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `blog_posts` (add only if missing)
CALL `tich_ensure_column`('blog_posts', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('blog_posts', 'title', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('blog_posts', 'subtitle', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('blog_posts', 'slug', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('blog_posts', 'excerpt', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('blog_posts', 'body', 'longtext NOT NULL');
CALL `tich_ensure_column`('blog_posts', 'featured_image_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('blog_posts', 'author_staff_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('blog_posts', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'draft\\\'\'');
CALL `tich_ensure_column`('blog_posts', 'published_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('blog_posts', 'reading_time_minutes', 'int(11) NULL DEFAULT NULL');
CALL `tich_ensure_column`('blog_posts', 'view_count', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('blog_posts', 'seo_meta_title', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('blog_posts', 'seo_meta_description', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('blog_posts', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('blog_posts', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('blog_posts', 'updated_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('blog_posts', 'updated_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `blog_posts` (add only if missing)
CALL `tich_ensure_index`('blog_posts', 'blog_posts_author_staff_id_foreign', '`author_staff_id`');
CALL `tich_ensure_index`('blog_posts', 'blog_posts_created_by_foreign', '`created_by`');
CALL `tich_ensure_unique`('blog_posts', 'blog_posts_slug_unique', '`slug`');
CALL `tich_ensure_index`('blog_posts', 'blog_posts_updated_by_foreign', '`updated_by`');

-- -----------------------------------------------------------------------------
-- Table: `blog_post_categories`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blog_post_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `blog_post_categories_unique` (`post_id`,`category_id`),
  KEY `blog_post_categories_category_id_foreign` (`category_id`),
  KEY `blog_post_categories_created_by_foreign` (`created_by`),
  CONSTRAINT `blog_post_categories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`),
  CONSTRAINT `blog_post_categories_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `blog_post_categories_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `blog_post_categories` (add only if missing)
CALL `tich_ensure_column`('blog_post_categories', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('blog_post_categories', 'post_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('blog_post_categories', 'category_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('blog_post_categories', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('blog_post_categories', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `blog_post_categories` (add only if missing)
CALL `tich_ensure_index`('blog_post_categories', 'blog_post_categories_category_id_foreign', '`category_id`');
CALL `tich_ensure_index`('blog_post_categories', 'blog_post_categories_created_by_foreign', '`created_by`');
CALL `tich_ensure_unique`('blog_post_categories', 'blog_post_categories_unique', '`post_id`, `category_id`');

-- -----------------------------------------------------------------------------
-- Table: `blog_post_revisions`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blog_post_revisions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL,
  `revision_number` int(11) NOT NULL,
  `title_snapshot` varchar(300) NOT NULL,
  `body_snapshot` longtext NOT NULL,
  `edited_by` bigint(20) unsigned NOT NULL,
  `edited_at` datetime NOT NULL DEFAULT current_timestamp(),
  `change_summary` varchar(500) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `blog_post_revisions_post_id_foreign` (`post_id`),
  KEY `blog_post_revisions_edited_by_foreign` (`edited_by`),
  KEY `blog_post_revisions_created_by_foreign` (`created_by`),
  CONSTRAINT `blog_post_revisions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `blog_post_revisions_edited_by_foreign` FOREIGN KEY (`edited_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `blog_post_revisions_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `blog_post_revisions` (add only if missing)
CALL `tich_ensure_column`('blog_post_revisions', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('blog_post_revisions', 'post_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('blog_post_revisions', 'revision_number', 'int(11) NOT NULL');
CALL `tich_ensure_column`('blog_post_revisions', 'title_snapshot', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('blog_post_revisions', 'body_snapshot', 'longtext NOT NULL');
CALL `tich_ensure_column`('blog_post_revisions', 'edited_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('blog_post_revisions', 'edited_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('blog_post_revisions', 'change_summary', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('blog_post_revisions', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `blog_post_revisions` (add only if missing)
CALL `tich_ensure_index`('blog_post_revisions', 'blog_post_revisions_created_by_foreign', '`created_by`');
CALL `tich_ensure_index`('blog_post_revisions', 'blog_post_revisions_edited_by_foreign', '`edited_by`');
CALL `tich_ensure_index`('blog_post_revisions', 'blog_post_revisions_post_id_foreign', '`post_id`');

-- -----------------------------------------------------------------------------
-- Table: `cache`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `cache` (add only if missing)
CALL `tich_ensure_column`('cache', 'key', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('cache', 'value', 'mediumtext NOT NULL');
CALL `tich_ensure_column`('cache', 'expiration', 'int(11) NOT NULL');

-- Indexes for `cache` (add only if missing)
CALL `tich_ensure_index`('cache', 'cache_expiration_index', '`expiration`');

-- -----------------------------------------------------------------------------
-- Table: `cache_locks`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `cache_locks` (add only if missing)
CALL `tich_ensure_column`('cache_locks', 'key', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('cache_locks', 'owner', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('cache_locks', 'expiration', 'int(11) NOT NULL');

-- Indexes for `cache_locks` (add only if missing)
CALL `tich_ensure_index`('cache_locks', 'cache_locks_expiration_index', '`expiration`');

-- -----------------------------------------------------------------------------
-- Table: `cafeteria_staff_memberships`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeteria_staff_memberships` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned NOT NULL,
  `membership_status` varchar(50) NOT NULL DEFAULT 'active',
  `monthly_deduction` decimal(12,2) NOT NULL DEFAULT 0.00,
  `enrolled_at` date NOT NULL,
  `withdrawn_at` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cafeteria_staff_memberships_staff_id_foreign` (`staff_id`),
  CONSTRAINT `cafeteria_staff_memberships_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `cafeteria_staff_memberships` (add only if missing)
CALL `tich_ensure_column`('cafeteria_staff_memberships', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('cafeteria_staff_memberships', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('cafeteria_staff_memberships', 'membership_status', 'varchar(50) NOT NULL DEFAULT \'\\\'active\\\'\'');
CALL `tich_ensure_column`('cafeteria_staff_memberships', 'monthly_deduction', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('cafeteria_staff_memberships', 'enrolled_at', 'date NOT NULL');
CALL `tich_ensure_column`('cafeteria_staff_memberships', 'withdrawn_at', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('cafeteria_staff_memberships', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('cafeteria_staff_memberships', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `cafeteria_staff_memberships` (add only if missing)
CALL `tich_ensure_index`('cafeteria_staff_memberships', 'cafeteria_staff_memberships_staff_id_foreign', '`staff_id`');

-- -----------------------------------------------------------------------------
-- Table: `campuses`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `campuses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `campus_code` varchar(20) NOT NULL,
  `campus_name` varchar(200) NOT NULL,
  `campus_type` varchar(50) NOT NULL,
  `parent_campus_id` bigint(20) unsigned DEFAULT NULL,
  `county` varchar(100) DEFAULT NULL,
  `sub_county` varchar(100) DEFAULT NULL,
  `physical_address` varchar(500) DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campuses_campus_code_unique` (`campus_code`),
  KEY `campuses_parent_campus_id_foreign` (`parent_campus_id`),
  CONSTRAINT `campuses_parent_campus_id_foreign` FOREIGN KEY (`parent_campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `campuses` (add only if missing)
CALL `tich_ensure_column`('campuses', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('campuses', 'campus_code', 'varchar(20) NOT NULL');
CALL `tich_ensure_column`('campuses', 'campus_name', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('campuses', 'campus_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('campuses', 'parent_campus_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('campuses', 'county', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('campuses', 'sub_county', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('campuses', 'physical_address', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('campuses', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('campuses', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('campuses', 'updated_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('campuses', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `campuses` (add only if missing)
CALL `tich_ensure_unique`('campuses', 'campuses_campus_code_unique', '`campus_code`');
CALL `tich_ensure_index`('campuses', 'campuses_parent_campus_id_foreign', '`parent_campus_id`');

-- -----------------------------------------------------------------------------
-- Table: `cat_scores`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cat_scores` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `unit_id` bigint(20) unsigned NOT NULL,
  `semester_id` bigint(20) unsigned NOT NULL,
  `assessment_type` varchar(50) NOT NULL,
  `assessment_name` varchar(200) NOT NULL,
  `max_score` decimal(5,2) NOT NULL,
  `score_obtained` decimal(5,2) NOT NULL,
  `percentage_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `weight_in_final` decimal(5,2) NOT NULL DEFAULT 0.00,
  `recorded_by` bigint(20) unsigned NOT NULL,
  `verified_by_hod` tinyint(4) NOT NULL DEFAULT 0,
  `recorded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cat_scores_unit_id_foreign` (`unit_id`),
  KEY `cat_scores_semester_id_foreign` (`semester_id`),
  KEY `cat_scores_recorded_by_foreign` (`recorded_by`),
  KEY `cat_scores_student_id_unit_id_semester_id_index` (`student_id`,`unit_id`,`semester_id`),
  CONSTRAINT `cat_scores_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `cat_scores_semester_id_foreign` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`),
  CONSTRAINT `cat_scores_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `cat_scores_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `cat_scores` (add only if missing)
CALL `tich_ensure_column`('cat_scores', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('cat_scores', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('cat_scores', 'unit_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('cat_scores', 'semester_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('cat_scores', 'assessment_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('cat_scores', 'assessment_name', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('cat_scores', 'max_score', 'decimal(5,2) NOT NULL');
CALL `tich_ensure_column`('cat_scores', 'score_obtained', 'decimal(5,2) NOT NULL');
CALL `tich_ensure_column`('cat_scores', 'percentage_score', 'decimal(5,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('cat_scores', 'weight_in_final', 'decimal(5,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('cat_scores', 'recorded_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('cat_scores', 'verified_by_hod', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('cat_scores', 'recorded_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('cat_scores', 'updated_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('cat_scores', 'updated_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `cat_scores` (add only if missing)
CALL `tich_ensure_index`('cat_scores', 'cat_scores_recorded_by_foreign', '`recorded_by`');
CALL `tich_ensure_index`('cat_scores', 'cat_scores_semester_id_foreign', '`semester_id`');
CALL `tich_ensure_index`('cat_scores', 'cat_scores_student_id_unit_id_semester_id_index', '`student_id`, `unit_id`, `semester_id`');
CALL `tich_ensure_index`('cat_scores', 'cat_scores_unit_id_foreign', '`unit_id`');

-- -----------------------------------------------------------------------------
-- Table: `chart_of_accounts`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chart_of_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_code` varchar(30) NOT NULL,
  `account_name` varchar(200) NOT NULL,
  `account_type` varchar(50) NOT NULL,
  `account_category` varchar(100) NOT NULL,
  `parent_account_code` varchar(30) DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `is_system_account` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `chart_of_accounts_account_code_unique` (`account_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `chart_of_accounts` (add only if missing)
CALL `tich_ensure_column`('chart_of_accounts', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('chart_of_accounts', 'account_code', 'varchar(30) NOT NULL');
CALL `tich_ensure_column`('chart_of_accounts', 'account_name', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('chart_of_accounts', 'account_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('chart_of_accounts', 'account_category', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('chart_of_accounts', 'parent_account_code', 'varchar(30) NULL DEFAULT NULL');
CALL `tich_ensure_column`('chart_of_accounts', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('chart_of_accounts', 'is_system_account', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('chart_of_accounts', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `chart_of_accounts` (add only if missing)
CALL `tich_ensure_unique`('chart_of_accounts', 'chart_of_accounts_account_code_unique', '`account_code`');

-- -----------------------------------------------------------------------------
-- Table: `chatbot_conversations`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chatbot_conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(100) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `user_type` varchar(50) DEFAULT NULL,
  `language` varchar(10) NOT NULL DEFAULT 'en',
  `device_type` varchar(50) NOT NULL DEFAULT 'mobile',
  `escalated_to_human` tinyint(4) NOT NULL DEFAULT 0,
  `escalated_to_user_id` bigint(20) unsigned DEFAULT NULL,
  `escalated_at` datetime DEFAULT NULL,
  `satisfaction_rating` int(11) DEFAULT NULL,
  `started_at` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `message_count` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `chatbot_conversations_session_id_unique` (`session_id`),
  KEY `chatbot_conversations_user_id_foreign` (`user_id`),
  KEY `chatbot_conversations_escalated_to_user_id_foreign` (`escalated_to_user_id`),
  CONSTRAINT `chatbot_conversations_escalated_to_user_id_foreign` FOREIGN KEY (`escalated_to_user_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chatbot_conversations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `chatbot_conversations` (add only if missing)
CALL `tich_ensure_column`('chatbot_conversations', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('chatbot_conversations', 'session_id', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('chatbot_conversations', 'user_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('chatbot_conversations', 'user_type', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('chatbot_conversations', 'language', 'varchar(10) NOT NULL DEFAULT \'\\\'en\\\'\'');
CALL `tich_ensure_column`('chatbot_conversations', 'device_type', 'varchar(50) NOT NULL DEFAULT \'\\\'mobile\\\'\'');
CALL `tich_ensure_column`('chatbot_conversations', 'escalated_to_human', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('chatbot_conversations', 'escalated_to_user_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('chatbot_conversations', 'escalated_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('chatbot_conversations', 'satisfaction_rating', 'int(11) NULL DEFAULT NULL');
CALL `tich_ensure_column`('chatbot_conversations', 'started_at', 'datetime NOT NULL');
CALL `tich_ensure_column`('chatbot_conversations', 'ended_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('chatbot_conversations', 'message_count', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('chatbot_conversations', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `chatbot_conversations` (add only if missing)
CALL `tich_ensure_index`('chatbot_conversations', 'chatbot_conversations_escalated_to_user_id_foreign', '`escalated_to_user_id`');
CALL `tich_ensure_unique`('chatbot_conversations', 'chatbot_conversations_session_id_unique', '`session_id`');
CALL `tich_ensure_index`('chatbot_conversations', 'chatbot_conversations_user_id_foreign', '`user_id`');

-- -----------------------------------------------------------------------------
-- Table: `chatbot_messages`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chatbot_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `sender` varchar(50) NOT NULL,
  `message_body` text NOT NULL,
  `intent_detected` varchar(100) DEFAULT NULL,
  `intent_confidence` decimal(5,2) DEFAULT NULL,
  `entities_extracted` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`entities_extracted`)),
  `quick_reply_selected` varchar(500) DEFAULT NULL,
  `is_escalation_trigger` tinyint(4) NOT NULL DEFAULT 0,
  `escalation_reason` varchar(500) DEFAULT NULL,
  `human_agent_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `chatbot_messages_human_agent_id_foreign` (`human_agent_id`),
  KEY `chatbot_messages_conversation_id_index` (`conversation_id`),
  KEY `chatbot_messages_intent_detected_index` (`intent_detected`),
  CONSTRAINT `chatbot_messages_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `chatbot_conversations` (`id`),
  CONSTRAINT `chatbot_messages_human_agent_id_foreign` FOREIGN KEY (`human_agent_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `chatbot_messages` (add only if missing)
CALL `tich_ensure_column`('chatbot_messages', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('chatbot_messages', 'conversation_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('chatbot_messages', 'sender', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('chatbot_messages', 'message_body', 'text NOT NULL');
CALL `tich_ensure_column`('chatbot_messages', 'intent_detected', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('chatbot_messages', 'intent_confidence', 'decimal(5,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('chatbot_messages', 'entities_extracted', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('chatbot_messages', 'quick_reply_selected', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('chatbot_messages', 'is_escalation_trigger', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('chatbot_messages', 'escalation_reason', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('chatbot_messages', 'human_agent_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('chatbot_messages', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `chatbot_messages` (add only if missing)
CALL `tich_ensure_index`('chatbot_messages', 'chatbot_messages_conversation_id_index', '`conversation_id`');
CALL `tich_ensure_index`('chatbot_messages', 'chatbot_messages_human_agent_id_foreign', '`human_agent_id`');
CALL `tich_ensure_index`('chatbot_messages', 'chatbot_messages_intent_detected_index', '`intent_detected`');

-- -----------------------------------------------------------------------------
-- Table: `clearance_checklist_items`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `clearance_checklist_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `offboarding_request_id` bigint(20) unsigned NOT NULL,
  `department` varchar(100) NOT NULL,
  `item` varchar(300) NOT NULL,
  `is_completed` tinyint(4) NOT NULL DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `completed_by` bigint(20) unsigned DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `clearance_checklist_items_completed_by_foreign` (`completed_by`),
  KEY `clearance_checklist_items_offboarding_request_id_index` (`offboarding_request_id`),
  CONSTRAINT `clearance_checklist_items_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clearance_checklist_items_offboarding_request_id_foreign` FOREIGN KEY (`offboarding_request_id`) REFERENCES `offboarding_requests` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `clearance_checklist_items` (add only if missing)
CALL `tich_ensure_column`('clearance_checklist_items', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('clearance_checklist_items', 'offboarding_request_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('clearance_checklist_items', 'department', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('clearance_checklist_items', 'item', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('clearance_checklist_items', 'is_completed', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('clearance_checklist_items', 'remarks', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('clearance_checklist_items', 'completed_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('clearance_checklist_items', 'completed_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('clearance_checklist_items', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('clearance_checklist_items', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `clearance_checklist_items` (add only if missing)
CALL `tich_ensure_index`('clearance_checklist_items', 'clearance_checklist_items_completed_by_foreign', '`completed_by`');
CALL `tich_ensure_index`('clearance_checklist_items', 'clearance_checklist_items_offboarding_request_id_index', '`offboarding_request_id`');

-- -----------------------------------------------------------------------------
-- Table: `communication_logs`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `communication_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `recipient_user_id` bigint(20) unsigned DEFAULT NULL,
  `recipient_email` varchar(255) DEFAULT NULL,
  `recipient_phone` varchar(30) DEFAULT NULL,
  `channel` varchar(20) NOT NULL,
  `template_key` varchar(100) DEFAULT NULL,
  `subject` varchar(500) DEFAULT NULL,
  `body_preview` varchar(500) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'queued',
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `communication_logs_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `communication_logs` (add only if missing)
CALL `tich_ensure_column`('communication_logs', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('communication_logs', 'recipient_user_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('communication_logs', 'recipient_email', 'varchar(255) NULL DEFAULT NULL');
CALL `tich_ensure_column`('communication_logs', 'recipient_phone', 'varchar(30) NULL DEFAULT NULL');
CALL `tich_ensure_column`('communication_logs', 'channel', 'varchar(20) NOT NULL');
CALL `tich_ensure_column`('communication_logs', 'template_key', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('communication_logs', 'subject', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('communication_logs', 'body_preview', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('communication_logs', 'status', 'varchar(20) NOT NULL DEFAULT \'\\\'queued\\\'\'');
CALL `tich_ensure_column`('communication_logs', 'sent_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('communication_logs', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('communication_logs', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `communication_logs` (add only if missing)
CALL `tich_ensure_index`('communication_logs', 'communication_logs_created_at_index', '`created_at`');

-- -----------------------------------------------------------------------------
-- Table: `competency_assessments`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `competency_assessments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `competency_checklist_id` bigint(20) unsigned NOT NULL,
  `is_competent` tinyint(4) NOT NULL DEFAULT 0,
  `assessment_date` date NOT NULL,
  `assessed_by` bigint(20) unsigned NOT NULL,
  `evidence_file_paths` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`evidence_file_paths`)),
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `competency_assessments_competency_checklist_id_foreign` (`competency_checklist_id`),
  KEY `competency_assessments_assessed_by_foreign` (`assessed_by`),
  KEY `competency_assessments_student_id_index` (`student_id`),
  CONSTRAINT `competency_assessments_assessed_by_foreign` FOREIGN KEY (`assessed_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `competency_assessments_competency_checklist_id_foreign` FOREIGN KEY (`competency_checklist_id`) REFERENCES `competency_checklists` (`id`),
  CONSTRAINT `competency_assessments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `competency_assessments` (add only if missing)
CALL `tich_ensure_column`('competency_assessments', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('competency_assessments', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('competency_assessments', 'competency_checklist_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('competency_assessments', 'is_competent', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('competency_assessments', 'assessment_date', 'date NOT NULL');
CALL `tich_ensure_column`('competency_assessments', 'assessed_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('competency_assessments', 'evidence_file_paths', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('competency_assessments', 'notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('competency_assessments', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `competency_assessments` (add only if missing)
CALL `tich_ensure_index`('competency_assessments', 'competency_assessments_assessed_by_foreign', '`assessed_by`');
CALL `tich_ensure_index`('competency_assessments', 'competency_assessments_competency_checklist_id_foreign', '`competency_checklist_id`');
CALL `tich_ensure_index`('competency_assessments', 'competency_assessments_student_id_index', '`student_id`');

-- -----------------------------------------------------------------------------
-- Table: `competency_checklists`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `competency_checklists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `unit_id` bigint(20) unsigned NOT NULL,
  `competency_code` varchar(50) NOT NULL,
  `competency_name` varchar(300) NOT NULL,
  `description` text DEFAULT NULL,
  `mentor_id` bigint(20) unsigned DEFAULT NULL,
  `sub_county_hub_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `competency_checklists_unit_id_foreign` (`unit_id`),
  KEY `competency_checklists_mentor_id_foreign` (`mentor_id`),
  KEY `competency_checklists_sub_county_hub_id_foreign` (`sub_county_hub_id`),
  CONSTRAINT `competency_checklists_mentor_id_foreign` FOREIGN KEY (`mentor_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `competency_checklists_sub_county_hub_id_foreign` FOREIGN KEY (`sub_county_hub_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `competency_checklists_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `competency_checklists` (add only if missing)
CALL `tich_ensure_column`('competency_checklists', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('competency_checklists', 'unit_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('competency_checklists', 'competency_code', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('competency_checklists', 'competency_name', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('competency_checklists', 'description', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('competency_checklists', 'mentor_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('competency_checklists', 'sub_county_hub_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('competency_checklists', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `competency_checklists` (add only if missing)
CALL `tich_ensure_index`('competency_checklists', 'competency_checklists_mentor_id_foreign', '`mentor_id`');
CALL `tich_ensure_index`('competency_checklists', 'competency_checklists_sub_county_hub_id_foreign', '`sub_county_hub_id`');
CALL `tich_ensure_index`('competency_checklists', 'competency_checklists_unit_id_foreign', '`unit_id`');

-- -----------------------------------------------------------------------------
-- Table: `contact_channels`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contact_channels` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `channel_type` varchar(50) NOT NULL,
  `label` varchar(200) NOT NULL,
  `label_sw` varchar(200) DEFAULT NULL,
  `value` varchar(500) NOT NULL,
  `display_value` varchar(500) DEFAULT NULL,
  `department_scope` varchar(50) NOT NULL DEFAULT 'institution_wide',
  `is_primary` tinyint(4) NOT NULL DEFAULT 0,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `contact_channels` (add only if missing)
CALL `tich_ensure_column`('contact_channels', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('contact_channels', 'channel_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('contact_channels', 'label', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('contact_channels', 'label_sw', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('contact_channels', 'value', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('contact_channels', 'display_value', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('contact_channels', 'department_scope', 'varchar(50) NOT NULL DEFAULT \'\\\'institution_wide\\\'\'');
CALL `tich_ensure_column`('contact_channels', 'is_primary', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('contact_channels', 'display_order', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('contact_channels', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('contact_channels', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('contact_channels', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `contact_channels` (add only if missing)
-- (no secondary indexes)

-- -----------------------------------------------------------------------------
-- Table: `credit_memos`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `credit_memos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `credit_memo_number` varchar(50) NOT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `student_account_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reason` varchar(500) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'issued',
  `issued_by` bigint(20) unsigned NOT NULL,
  `issued_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `credit_memos_credit_memo_number_unique` (`credit_memo_number`),
  KEY `credit_memos_invoice_id_foreign` (`invoice_id`),
  KEY `credit_memos_student_account_id_foreign` (`student_account_id`),
  KEY `credit_memos_student_id_foreign` (`student_id`),
  KEY `credit_memos_issued_by_foreign` (`issued_by`),
  CONSTRAINT `credit_memos_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `credit_memos_issued_by_foreign` FOREIGN KEY (`issued_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `credit_memos_student_account_id_foreign` FOREIGN KEY (`student_account_id`) REFERENCES `student_accounts` (`id`),
  CONSTRAINT `credit_memos_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `credit_memos` (add only if missing)
CALL `tich_ensure_column`('credit_memos', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('credit_memos', 'credit_memo_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('credit_memos', 'invoice_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('credit_memos', 'student_account_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('credit_memos', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('credit_memos', 'amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('credit_memos', 'reason', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('credit_memos', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'issued\\\'\'');
CALL `tich_ensure_column`('credit_memos', 'issued_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('credit_memos', 'issued_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('credit_memos', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `credit_memos` (add only if missing)
CALL `tich_ensure_unique`('credit_memos', 'credit_memos_credit_memo_number_unique', '`credit_memo_number`');
CALL `tich_ensure_index`('credit_memos', 'credit_memos_invoice_id_foreign', '`invoice_id`');
CALL `tich_ensure_index`('credit_memos', 'credit_memos_issued_by_foreign', '`issued_by`');
CALL `tich_ensure_index`('credit_memos', 'credit_memos_student_account_id_foreign', '`student_account_id`');
CALL `tich_ensure_index`('credit_memos', 'credit_memos_student_id_foreign', '`student_id`');

-- -----------------------------------------------------------------------------
-- Table: `curriculum_versions`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `curriculum_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `program_id` bigint(20) unsigned NOT NULL,
  `academic_year_id` bigint(20) unsigned DEFAULT NULL,
  `intake_year` smallint(5) unsigned DEFAULT NULL,
  `intake_month` tinyint(3) unsigned DEFAULT NULL,
  `version_label` varchar(100) NOT NULL,
  `version_number` int(11) NOT NULL DEFAULT 1,
  `curriculum_format` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `submitted_by` bigint(20) unsigned DEFAULT NULL,
  `registrar_approved_at` datetime DEFAULT NULL,
  `registrar_approved_by` bigint(20) unsigned DEFAULT NULL,
  `ceo_approved_at` datetime DEFAULT NULL,
  `ceo_approved_by` bigint(20) unsigned DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `published_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cv_program_intake_unique` (`program_id`,`intake_year`,`intake_month`),
  KEY `curriculum_versions_academic_year_id_foreign` (`academic_year_id`),
  KEY `curriculum_versions_program_id_status_index` (`program_id`,`status`),
  CONSTRAINT `curriculum_versions_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE SET NULL,
  CONSTRAINT `curriculum_versions_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `academic_programs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `curriculum_versions` (add only if missing)
CALL `tich_ensure_column`('curriculum_versions', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('curriculum_versions', 'program_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('curriculum_versions', 'academic_year_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('curriculum_versions', 'intake_year', 'smallint(5) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('curriculum_versions', 'intake_month', 'tinyint(3) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('curriculum_versions', 'version_label', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('curriculum_versions', 'version_number', 'int(11) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('curriculum_versions', 'curriculum_format', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('curriculum_versions', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'draft\\\'\'');
CALL `tich_ensure_column`('curriculum_versions', 'notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('curriculum_versions', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('curriculum_versions', 'submitted_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('curriculum_versions', 'submitted_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('curriculum_versions', 'registrar_approved_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('curriculum_versions', 'registrar_approved_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('curriculum_versions', 'ceo_approved_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('curriculum_versions', 'ceo_approved_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('curriculum_versions', 'published_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('curriculum_versions', 'published_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('curriculum_versions', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('curriculum_versions', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `curriculum_versions` (add only if missing)
CALL `tich_ensure_index`('curriculum_versions', 'curriculum_versions_academic_year_id_foreign', '`academic_year_id`');
CALL `tich_ensure_index`('curriculum_versions', 'curriculum_versions_program_id_status_index', '`program_id`, `status`');
CALL `tich_ensure_unique`('curriculum_versions', 'cv_program_intake_unique', '`program_id`, `intake_year`, `intake_month`');

-- -----------------------------------------------------------------------------
-- Table: `curriculum_version_periods`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `curriculum_version_periods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `curriculum_version_id` bigint(20) unsigned NOT NULL,
  `semester` tinyint(3) unsigned NOT NULL,
  `block_id` bigint(20) unsigned DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `learning_start_date` date DEFAULT NULL,
  `learning_end_date` date DEFAULT NULL,
  `exam_start_date` date DEFAULT NULL,
  `exam_end_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cv_period_unique` (`curriculum_version_id`,`semester`,`block_id`),
  KEY `curriculum_version_periods_block_id_foreign` (`block_id`),
  CONSTRAINT `curriculum_version_periods_block_id_foreign` FOREIGN KEY (`block_id`) REFERENCES `nursing_blocks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `curriculum_version_periods_curriculum_version_id_foreign` FOREIGN KEY (`curriculum_version_id`) REFERENCES `curriculum_versions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `curriculum_version_periods` (add only if missing)
CALL `tich_ensure_column`('curriculum_version_periods', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('curriculum_version_periods', 'curriculum_version_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('curriculum_version_periods', 'semester', 'tinyint(3) unsigned NOT NULL');
CALL `tich_ensure_column`('curriculum_version_periods', 'block_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('curriculum_version_periods', 'start_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('curriculum_version_periods', 'end_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('curriculum_version_periods', 'learning_start_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('curriculum_version_periods', 'learning_end_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('curriculum_version_periods', 'exam_start_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('curriculum_version_periods', 'exam_end_date', 'date NULL DEFAULT NULL');

-- Indexes for `curriculum_version_periods` (add only if missing)
CALL `tich_ensure_index`('curriculum_version_periods', 'curriculum_version_periods_block_id_foreign', '`block_id`');
CALL `tich_ensure_unique`('curriculum_version_periods', 'cv_period_unique', '`curriculum_version_id`, `semester`, `block_id`');

-- -----------------------------------------------------------------------------
-- Table: `curriculum_version_units`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `curriculum_version_units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `curriculum_version_id` bigint(20) unsigned NOT NULL,
  `unit_id` bigint(20) unsigned NOT NULL,
  `semester` int(11) NOT NULL DEFAULT 1,
  `block_id` bigint(20) unsigned DEFAULT NULL,
  `is_compulsory` tinyint(4) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `priority` int(11) NOT NULL DEFAULT 0,
  `credit_hours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `contact_hours` int(11) NOT NULL DEFAULT 0,
  `total_learning_hours` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cv_unit_unique` (`curriculum_version_id`,`unit_id`),
  KEY `curriculum_version_units_unit_id_foreign` (`unit_id`),
  KEY `curriculum_version_units_block_id_foreign` (`block_id`),
  CONSTRAINT `curriculum_version_units_block_id_foreign` FOREIGN KEY (`block_id`) REFERENCES `nursing_blocks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `curriculum_version_units_curriculum_version_id_foreign` FOREIGN KEY (`curriculum_version_id`) REFERENCES `curriculum_versions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `curriculum_version_units_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `curriculum_version_units` (add only if missing)
CALL `tich_ensure_column`('curriculum_version_units', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('curriculum_version_units', 'curriculum_version_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('curriculum_version_units', 'unit_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('curriculum_version_units', 'semester', 'int(11) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('curriculum_version_units', 'block_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('curriculum_version_units', 'is_compulsory', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('curriculum_version_units', 'display_order', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('curriculum_version_units', 'priority', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('curriculum_version_units', 'credit_hours', 'decimal(5,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('curriculum_version_units', 'contact_hours', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('curriculum_version_units', 'total_learning_hours', 'int(11) NOT NULL DEFAULT \'0\'');

-- Indexes for `curriculum_version_units` (add only if missing)
CALL `tich_ensure_index`('curriculum_version_units', 'curriculum_version_units_block_id_foreign', '`block_id`');
CALL `tich_ensure_index`('curriculum_version_units', 'curriculum_version_units_unit_id_foreign', '`unit_id`');
CALL `tich_ensure_unique`('curriculum_version_units', 'cv_unit_unique', '`curriculum_version_id`, `unit_id`');

-- -----------------------------------------------------------------------------
-- Table: `deferral_requests`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `deferral_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `requested_semester_id` bigint(20) unsigned NOT NULL,
  `reason` text NOT NULL,
  `supporting_document_path` varchar(500) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_comments` text DEFAULT NULL,
  `effective_from_semester_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `deferral_requests_student_id_foreign` (`student_id`),
  KEY `deferral_requests_requested_semester_id_foreign` (`requested_semester_id`),
  KEY `deferral_requests_effective_from_semester_id_foreign` (`effective_from_semester_id`),
  KEY `deferral_requests_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `deferral_requests_effective_from_semester_id_foreign` FOREIGN KEY (`effective_from_semester_id`) REFERENCES `semesters` (`id`) ON DELETE SET NULL,
  CONSTRAINT `deferral_requests_requested_semester_id_foreign` FOREIGN KEY (`requested_semester_id`) REFERENCES `semesters` (`id`),
  CONSTRAINT `deferral_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `deferral_requests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `deferral_requests` (add only if missing)
CALL `tich_ensure_column`('deferral_requests', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('deferral_requests', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('deferral_requests', 'requested_semester_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('deferral_requests', 'reason', 'text NOT NULL');
CALL `tich_ensure_column`('deferral_requests', 'supporting_document_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('deferral_requests', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('deferral_requests', 'reviewed_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('deferral_requests', 'reviewed_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('deferral_requests', 'review_comments', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('deferral_requests', 'effective_from_semester_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('deferral_requests', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `deferral_requests` (add only if missing)
CALL `tich_ensure_index`('deferral_requests', 'deferral_requests_effective_from_semester_id_foreign', '`effective_from_semester_id`');
CALL `tich_ensure_index`('deferral_requests', 'deferral_requests_requested_semester_id_foreign', '`requested_semester_id`');
CALL `tich_ensure_index`('deferral_requests', 'deferral_requests_reviewed_by_foreign', '`reviewed_by`');
CALL `tich_ensure_index`('deferral_requests', 'deferral_requests_student_id_foreign', '`student_id`');

-- -----------------------------------------------------------------------------
-- Table: `departments`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `dept_code` varchar(20) NOT NULL,
  `dept_name` varchar(200) NOT NULL,
  `dept_category` varchar(50) NOT NULL,
  `curriculum_profile` varchar(50) NOT NULL DEFAULT 'standard',
  `department_group_id` bigint(20) unsigned DEFAULT NULL,
  `display_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `hod_id` bigint(20) unsigned DEFAULT NULL,
  `parent_dept_id` bigint(20) unsigned DEFAULT NULL,
  `campus_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `approval_status` varchar(50) NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_dept_code_unique` (`dept_code`),
  KEY `departments_campus_id_foreign` (`campus_id`),
  KEY `departments_hod_id_foreign` (`hod_id`),
  KEY `departments_department_group_id_foreign` (`department_group_id`),
  KEY `departments_parent_active_index` (`parent_dept_id`,`is_active`),
  CONSTRAINT `departments_campus_id_foreign` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `departments_department_group_id_foreign` FOREIGN KEY (`department_group_id`) REFERENCES `department_groups` (`id`) ON DELETE SET NULL,
  CONSTRAINT `departments_hod_id_foreign` FOREIGN KEY (`hod_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `departments_parent_dept_id_foreign` FOREIGN KEY (`parent_dept_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `departments` (add only if missing)
CALL `tich_ensure_column`('departments', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('departments', 'dept_code', 'varchar(20) NOT NULL');
CALL `tich_ensure_column`('departments', 'dept_name', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('departments', 'dept_category', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('departments', 'curriculum_profile', 'varchar(50) NOT NULL DEFAULT \'\\\'standard\\\'\'');
CALL `tich_ensure_column`('departments', 'department_group_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('departments', 'display_order', 'smallint(5) unsigned NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('departments', 'hod_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('departments', 'parent_dept_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('departments', 'campus_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('departments', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('departments', 'approval_status', 'varchar(50) NOT NULL DEFAULT \'\\\'active\\\'\'');
CALL `tich_ensure_column`('departments', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('departments', 'updated_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('departments', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `departments` (add only if missing)
CALL `tich_ensure_index`('departments', 'departments_campus_id_foreign', '`campus_id`');
CALL `tich_ensure_index`('departments', 'departments_department_group_id_foreign', '`department_group_id`');
CALL `tich_ensure_unique`('departments', 'departments_dept_code_unique', '`dept_code`');
CALL `tich_ensure_index`('departments', 'departments_hod_id_foreign', '`hod_id`');
CALL `tich_ensure_index`('departments', 'departments_parent_active_index', '`parent_dept_id`, `is_active`');

-- -----------------------------------------------------------------------------
-- Table: `department_groups`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `department_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_code` varchar(20) NOT NULL,
  `group_name` varchar(200) NOT NULL,
  `display_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `department_groups_group_code_unique` (`group_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `department_groups` (add only if missing)
CALL `tich_ensure_column`('department_groups', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('department_groups', 'group_code', 'varchar(20) NOT NULL');
CALL `tich_ensure_column`('department_groups', 'group_name', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('department_groups', 'display_order', 'smallint(5) unsigned NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('department_groups', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('department_groups', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('department_groups', 'updated_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('department_groups', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `department_groups` (add only if missing)
CALL `tich_ensure_unique`('department_groups', 'department_groups_group_code_unique', '`group_code`');

-- -----------------------------------------------------------------------------
-- Table: `department_modules`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `department_modules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `department_id` bigint(20) unsigned NOT NULL,
  `module_key` varchar(64) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `department_modules_department_id_module_key_unique` (`department_id`,`module_key`),
  KEY `department_modules_assigned_by_foreign` (`assigned_by`),
  KEY `department_modules_module_key_index` (`module_key`),
  CONSTRAINT `department_modules_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `department_modules_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `department_modules` (add only if missing)
CALL `tich_ensure_column`('department_modules', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('department_modules', 'department_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('department_modules', 'module_key', 'varchar(64) NOT NULL');
CALL `tich_ensure_column`('department_modules', 'assigned_at', 'timestamp NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('department_modules', 'assigned_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `department_modules` (add only if missing)
CALL `tich_ensure_index`('department_modules', 'department_modules_assigned_by_foreign', '`assigned_by`');
CALL `tich_ensure_unique`('department_modules', 'department_modules_department_id_module_key_unique', '`department_id`, `module_key`');
CALL `tich_ensure_index`('department_modules', 'department_modules_module_key_index', '`module_key`');

-- -----------------------------------------------------------------------------
-- Table: `device_tokens`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `device_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `device_token` varchar(500) NOT NULL,
  `platform` varchar(50) NOT NULL,
  `device_name` varchar(200) DEFAULT NULL,
  `app_version` varchar(20) DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `last_used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `device_tokens_unique` (`user_id`,`device_token`,`platform`),
  CONSTRAINT `device_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `device_tokens` (add only if missing)
CALL `tich_ensure_column`('device_tokens', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('device_tokens', 'user_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('device_tokens', 'device_token', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('device_tokens', 'platform', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('device_tokens', 'device_name', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('device_tokens', 'app_version', 'varchar(20) NULL DEFAULT NULL');
CALL `tich_ensure_column`('device_tokens', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('device_tokens', 'last_used_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('device_tokens', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('device_tokens', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `device_tokens` (add only if missing)
CALL `tich_ensure_unique`('device_tokens', 'device_tokens_unique', '`user_id`, `device_token`, `platform`');

-- -----------------------------------------------------------------------------
-- Table: `disciplinary_cases`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `disciplinary_cases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `case_number` varchar(255) NOT NULL,
  `staff_id` bigint(20) unsigned NOT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `incident_date` date NOT NULL,
  `incident_description` text NOT NULL,
  `investigation_notes` text DEFAULT NULL,
  `witness_information` text DEFAULT NULL,
  `hearing_date` date DEFAULT NULL,
  `committee_members` text DEFAULT NULL,
  `decision` text DEFAULT NULL,
  `action_type` enum('warning','suspension','termination','appeal','other') DEFAULT NULL,
  `action_details` text DEFAULT NULL,
  `action_start_date` date DEFAULT NULL,
  `action_end_date` date DEFAULT NULL,
  `status` enum('open','under_investigation','hearing_scheduled','decided','appealed','closed') NOT NULL DEFAULT 'open',
  `hr_comments` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `disciplinary_cases_case_number_unique` (`case_number`),
  KEY `disciplinary_cases_assigned_to_foreign` (`assigned_to`),
  KEY `disciplinary_cases_staff_id_status_index` (`staff_id`,`status`),
  KEY `disciplinary_cases_case_number_index` (`case_number`),
  CONSTRAINT `disciplinary_cases_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `disciplinary_cases_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `disciplinary_cases` (add only if missing)
CALL `tich_ensure_column`('disciplinary_cases', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('disciplinary_cases', 'case_number', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('disciplinary_cases', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('disciplinary_cases', 'assigned_to', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('disciplinary_cases', 'incident_date', 'date NOT NULL');
CALL `tich_ensure_column`('disciplinary_cases', 'incident_description', 'text NOT NULL');
CALL `tich_ensure_column`('disciplinary_cases', 'investigation_notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('disciplinary_cases', 'witness_information', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('disciplinary_cases', 'hearing_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('disciplinary_cases', 'committee_members', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('disciplinary_cases', 'decision', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('disciplinary_cases', 'action_type', 'enum(\'warning\',\'suspension\',\'termination\',\'appeal\',\'other\') NULL DEFAULT NULL');
CALL `tich_ensure_column`('disciplinary_cases', 'action_details', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('disciplinary_cases', 'action_start_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('disciplinary_cases', 'action_end_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('disciplinary_cases', 'status', 'enum(\'open\',\'under_investigation\',\'hearing_scheduled\',\'decided\',\'appealed\',\'closed\') NOT NULL DEFAULT \'\\\'open\\\'\'');
CALL `tich_ensure_column`('disciplinary_cases', 'hr_comments', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('disciplinary_cases', 'metadata', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('disciplinary_cases', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('disciplinary_cases', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `disciplinary_cases` (add only if missing)
CALL `tich_ensure_index`('disciplinary_cases', 'disciplinary_cases_assigned_to_foreign', '`assigned_to`');
CALL `tich_ensure_index`('disciplinary_cases', 'disciplinary_cases_case_number_index', '`case_number`');
CALL `tich_ensure_unique`('disciplinary_cases', 'disciplinary_cases_case_number_unique', '`case_number`');
CALL `tich_ensure_index`('disciplinary_cases', 'disciplinary_cases_staff_id_status_index', '`staff_id`, `status`');

-- -----------------------------------------------------------------------------
-- Table: `disciplinary_documents`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `disciplinary_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `disciplinary_case_id` bigint(20) unsigned NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `document_path` varchar(255) NOT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disciplinary_documents_disciplinary_case_id_foreign` (`disciplinary_case_id`),
  CONSTRAINT `disciplinary_documents_disciplinary_case_id_foreign` FOREIGN KEY (`disciplinary_case_id`) REFERENCES `disciplinary_cases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `disciplinary_documents` (add only if missing)
CALL `tich_ensure_column`('disciplinary_documents', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('disciplinary_documents', 'disciplinary_case_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('disciplinary_documents', 'document_name', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('disciplinary_documents', 'document_path', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('disciplinary_documents', 'mime_type', 'varchar(255) NULL DEFAULT NULL');
CALL `tich_ensure_column`('disciplinary_documents', 'description', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('disciplinary_documents', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('disciplinary_documents', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `disciplinary_documents` (add only if missing)
CALL `tich_ensure_index`('disciplinary_documents', 'disciplinary_documents_disciplinary_case_id_foreign', '`disciplinary_case_id`');

-- -----------------------------------------------------------------------------
-- Table: `disciplinary_records`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `disciplinary_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `case_number` varchar(50) NOT NULL,
  `incident_date` date NOT NULL,
  `incident_description` text NOT NULL,
  `violation_type` varchar(100) NOT NULL,
  `severity` varchar(50) NOT NULL,
  `reported_by` bigint(20) unsigned NOT NULL,
  `assigned_officer_id` bigint(20) unsigned DEFAULT NULL,
  `case_status` varchar(50) NOT NULL DEFAULT 'open',
  `hearing_date` datetime DEFAULT NULL,
  `decision` text DEFAULT NULL,
  `sanction` varchar(500) DEFAULT NULL,
  `appeal_status` varchar(50) DEFAULT NULL,
  `appeal_notes` text DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `disciplinary_records_case_number_unique` (`case_number`),
  KEY `disciplinary_records_student_id_foreign` (`student_id`),
  KEY `disciplinary_records_reported_by_foreign` (`reported_by`),
  KEY `disciplinary_records_assigned_officer_id_foreign` (`assigned_officer_id`),
  CONSTRAINT `disciplinary_records_assigned_officer_id_foreign` FOREIGN KEY (`assigned_officer_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `disciplinary_records_reported_by_foreign` FOREIGN KEY (`reported_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `disciplinary_records_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `disciplinary_records` (add only if missing)
CALL `tich_ensure_column`('disciplinary_records', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('disciplinary_records', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('disciplinary_records', 'case_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('disciplinary_records', 'incident_date', 'date NOT NULL');
CALL `tich_ensure_column`('disciplinary_records', 'incident_description', 'text NOT NULL');
CALL `tich_ensure_column`('disciplinary_records', 'violation_type', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('disciplinary_records', 'severity', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('disciplinary_records', 'reported_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('disciplinary_records', 'assigned_officer_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('disciplinary_records', 'case_status', 'varchar(50) NOT NULL DEFAULT \'\\\'open\\\'\'');
CALL `tich_ensure_column`('disciplinary_records', 'hearing_date', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('disciplinary_records', 'decision', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('disciplinary_records', 'sanction', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('disciplinary_records', 'appeal_status', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('disciplinary_records', 'appeal_notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('disciplinary_records', 'resolved_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('disciplinary_records', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('disciplinary_records', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `disciplinary_records` (add only if missing)
CALL `tich_ensure_index`('disciplinary_records', 'disciplinary_records_assigned_officer_id_foreign', '`assigned_officer_id`');
CALL `tich_ensure_unique`('disciplinary_records', 'disciplinary_records_case_number_unique', '`case_number`');
CALL `tich_ensure_index`('disciplinary_records', 'disciplinary_records_reported_by_foreign', '`reported_by`');
CALL `tich_ensure_index`('disciplinary_records', 'disciplinary_records_student_id_foreign', '`student_id`');

-- -----------------------------------------------------------------------------
-- Table: `donations`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `donations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `donation_number` varchar(50) NOT NULL,
  `campaign_id` bigint(20) unsigned DEFAULT NULL,
  `donor_name` varchar(300) DEFAULT NULL,
  `donor_email` varchar(255) DEFAULT NULL,
  `donor_phone` varchar(30) DEFAULT NULL,
  `donor_type` varchar(50) NOT NULL DEFAULT 'anonymous',
  `amount` decimal(14,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'KES',
  `amount_KES` decimal(14,2) NOT NULL,
  `exchange_rate_used` decimal(12,4) NOT NULL DEFAULT 1.0000,
  `payment_method` varchar(50) NOT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `is_anonymous` tinyint(4) NOT NULL DEFAULT 0,
  `deductible_for_tax` tinyint(4) NOT NULL DEFAULT 0,
  `receipt_sent` tinyint(4) NOT NULL DEFAULT 0,
  `receipt_reference` varchar(100) DEFAULT NULL,
  `is_reconciled` tinyint(4) NOT NULL DEFAULT 0,
  `reconciled_by` bigint(20) unsigned DEFAULT NULL,
  `reconciled_at` datetime DEFAULT NULL,
  `donation_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `donations_donation_number_unique` (`donation_number`),
  KEY `donations_campaign_id_foreign` (`campaign_id`),
  KEY `donations_reconciled_by_foreign` (`reconciled_by`),
  CONSTRAINT `donations_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `donation_campaigns` (`id`) ON DELETE SET NULL,
  CONSTRAINT `donations_reconciled_by_foreign` FOREIGN KEY (`reconciled_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `donations` (add only if missing)
CALL `tich_ensure_column`('donations', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('donations', 'donation_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('donations', 'campaign_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('donations', 'donor_name', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('donations', 'donor_email', 'varchar(255) NULL DEFAULT NULL');
CALL `tich_ensure_column`('donations', 'donor_phone', 'varchar(30) NULL DEFAULT NULL');
CALL `tich_ensure_column`('donations', 'donor_type', 'varchar(50) NOT NULL DEFAULT \'\\\'anonymous\\\'\'');
CALL `tich_ensure_column`('donations', 'amount', 'decimal(14,2) NOT NULL');
CALL `tich_ensure_column`('donations', 'currency', 'varchar(10) NOT NULL DEFAULT \'\\\'KES\\\'\'');
CALL `tich_ensure_column`('donations', 'amount_KES', 'decimal(14,2) NOT NULL');
CALL `tich_ensure_column`('donations', 'exchange_rate_used', 'decimal(12,4) NOT NULL DEFAULT \'1.0000\'');
CALL `tich_ensure_column`('donations', 'payment_method', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('donations', 'payment_reference', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('donations', 'is_anonymous', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('donations', 'deductible_for_tax', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('donations', 'receipt_sent', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('donations', 'receipt_reference', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('donations', 'is_reconciled', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('donations', 'reconciled_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('donations', 'reconciled_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('donations', 'donation_date', 'date NOT NULL');
CALL `tich_ensure_column`('donations', 'notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('donations', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `donations` (add only if missing)
CALL `tich_ensure_index`('donations', 'donations_campaign_id_foreign', '`campaign_id`');
CALL `tich_ensure_unique`('donations', 'donations_donation_number_unique', '`donation_number`');
CALL `tich_ensure_index`('donations', 'donations_reconciled_by_foreign', '`reconciled_by`');

-- -----------------------------------------------------------------------------
-- Table: `donation_campaigns`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `donation_campaigns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `campaign_code` varchar(50) NOT NULL,
  `title` varchar(300) NOT NULL,
  `description` text DEFAULT NULL,
  `fund_allocation` varchar(50) NOT NULL,
  `target_amount` decimal(14,2) NOT NULL,
  `raised_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) NOT NULL DEFAULT 'KES',
  `cover_image_path` varchar(500) DEFAULT NULL,
  `mpesa_paybill_number` varchar(50) DEFAULT NULL,
  `mpesa_account_name` varchar(200) DEFAULT NULL,
  `bank_account_name` varchar(200) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `bank_name` varchar(200) DEFAULT NULL,
  `swift_code` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_public` tinyint(4) NOT NULL DEFAULT 1,
  `is_featured` tinyint(4) NOT NULL DEFAULT 0,
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `donation_campaigns_campaign_code_unique` (`campaign_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `donation_campaigns` (add only if missing)
CALL `tich_ensure_column`('donation_campaigns', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('donation_campaigns', 'campaign_code', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('donation_campaigns', 'title', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('donation_campaigns', 'description', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('donation_campaigns', 'fund_allocation', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('donation_campaigns', 'target_amount', 'decimal(14,2) NOT NULL');
CALL `tich_ensure_column`('donation_campaigns', 'raised_amount', 'decimal(14,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('donation_campaigns', 'currency', 'varchar(10) NOT NULL DEFAULT \'\\\'KES\\\'\'');
CALL `tich_ensure_column`('donation_campaigns', 'cover_image_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('donation_campaigns', 'mpesa_paybill_number', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('donation_campaigns', 'mpesa_account_name', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('donation_campaigns', 'bank_account_name', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('donation_campaigns', 'bank_account_number', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('donation_campaigns', 'bank_name', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('donation_campaigns', 'swift_code', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('donation_campaigns', 'start_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('donation_campaigns', 'end_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('donation_campaigns', 'is_public', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('donation_campaigns', 'is_featured', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('donation_campaigns', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'active\\\'\'');
CALL `tich_ensure_column`('donation_campaigns', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('donation_campaigns', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `donation_campaigns` (add only if missing)
CALL `tich_ensure_unique`('donation_campaigns', 'donation_campaigns_campaign_code_unique', '`campaign_code`');

-- -----------------------------------------------------------------------------
-- Table: `donor_disbursements`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `donor_disbursements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `disbursement_number` varchar(50) NOT NULL,
  `donor_project_id` bigint(20) unsigned NOT NULL,
  `amount_received` decimal(14,2) NOT NULL,
  `currency_received` varchar(10) NOT NULL DEFAULT 'USD',
  `exchange_rate` decimal(12,4) NOT NULL,
  `kes_amount` decimal(14,2) NOT NULL,
  `receipt_date` date NOT NULL,
  `bank_reference` varchar(100) DEFAULT NULL,
  `purpose` varchar(500) DEFAULT NULL,
  `account_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `donor_disbursements_disbursement_number_unique` (`disbursement_number`),
  KEY `donor_disbursements_donor_project_id_foreign` (`donor_project_id`),
  KEY `donor_disbursements_account_ledger_id_foreign` (`account_ledger_id`),
  CONSTRAINT `donor_disbursements_account_ledger_id_foreign` FOREIGN KEY (`account_ledger_id`) REFERENCES `account_ledger` (`id`) ON DELETE SET NULL,
  CONSTRAINT `donor_disbursements_donor_project_id_foreign` FOREIGN KEY (`donor_project_id`) REFERENCES `donor_projects` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `donor_disbursements` (add only if missing)
CALL `tich_ensure_column`('donor_disbursements', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('donor_disbursements', 'disbursement_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('donor_disbursements', 'donor_project_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('donor_disbursements', 'amount_received', 'decimal(14,2) NOT NULL');
CALL `tich_ensure_column`('donor_disbursements', 'currency_received', 'varchar(10) NOT NULL DEFAULT \'\\\'USD\\\'\'');
CALL `tich_ensure_column`('donor_disbursements', 'exchange_rate', 'decimal(12,4) NOT NULL');
CALL `tich_ensure_column`('donor_disbursements', 'kes_amount', 'decimal(14,2) NOT NULL');
CALL `tich_ensure_column`('donor_disbursements', 'receipt_date', 'date NOT NULL');
CALL `tich_ensure_column`('donor_disbursements', 'bank_reference', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('donor_disbursements', 'purpose', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('donor_disbursements', 'account_ledger_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('donor_disbursements', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `donor_disbursements` (add only if missing)
CALL `tich_ensure_index`('donor_disbursements', 'donor_disbursements_account_ledger_id_foreign', '`account_ledger_id`');
CALL `tich_ensure_unique`('donor_disbursements', 'donor_disbursements_disbursement_number_unique', '`disbursement_number`');
CALL `tich_ensure_index`('donor_disbursements', 'donor_disbursements_donor_project_id_foreign', '`donor_project_id`');

-- -----------------------------------------------------------------------------
-- Table: `donor_projects`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `donor_projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_code` varchar(50) NOT NULL,
  `project_name` varchar(300) NOT NULL,
  `donor_name` varchar(300) NOT NULL,
  `donor_type` varchar(50) NOT NULL,
  `total_grant_amount` decimal(14,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'KES',
  `disbursed_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `disbursement_currency` varchar(10) NOT NULL DEFAULT 'USD',
  `exchange_rate_at_disbursement` decimal(12,4) NOT NULL DEFAULT 1.0000,
  `kes_equivalent` decimal(14,2) NOT NULL DEFAULT 0.00,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `project_leader_id` bigint(20) unsigned NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `donor_projects_project_code_unique` (`project_code`),
  KEY `donor_projects_project_leader_id_foreign` (`project_leader_id`),
  CONSTRAINT `donor_projects_project_leader_id_foreign` FOREIGN KEY (`project_leader_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `donor_projects` (add only if missing)
CALL `tich_ensure_column`('donor_projects', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('donor_projects', 'project_code', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('donor_projects', 'project_name', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('donor_projects', 'donor_name', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('donor_projects', 'donor_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('donor_projects', 'total_grant_amount', 'decimal(14,2) NOT NULL');
CALL `tich_ensure_column`('donor_projects', 'currency', 'varchar(10) NOT NULL DEFAULT \'\\\'KES\\\'\'');
CALL `tich_ensure_column`('donor_projects', 'disbursed_amount', 'decimal(14,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('donor_projects', 'disbursement_currency', 'varchar(10) NOT NULL DEFAULT \'\\\'USD\\\'\'');
CALL `tich_ensure_column`('donor_projects', 'exchange_rate_at_disbursement', 'decimal(12,4) NOT NULL DEFAULT \'1.0000\'');
CALL `tich_ensure_column`('donor_projects', 'kes_equivalent', 'decimal(14,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('donor_projects', 'start_date', 'date NOT NULL');
CALL `tich_ensure_column`('donor_projects', 'end_date', 'date NOT NULL');
CALL `tich_ensure_column`('donor_projects', 'project_leader_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('donor_projects', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'active\\\'\'');
CALL `tich_ensure_column`('donor_projects', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `donor_projects` (add only if missing)
CALL `tich_ensure_unique`('donor_projects', 'donor_projects_project_code_unique', '`project_code`');
CALL `tich_ensure_index`('donor_projects', 'donor_projects_project_leader_id_foreign', '`project_leader_id`');

-- -----------------------------------------------------------------------------
-- Table: `email_gateway_logs`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `email_gateway_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `notification_id` bigint(20) unsigned DEFAULT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `body_preview` varchar(500) DEFAULT NULL,
  `provider` varchar(100) NOT NULL,
  `provider_message_id` varchar(200) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'queued',
  `bounce_reason` varchar(500) DEFAULT NULL,
  `complaint_reason` varchar(500) DEFAULT NULL,
  `opened_at` datetime DEFAULT NULL,
  `clicked_at` datetime DEFAULT NULL,
  `dispatched_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `email_gateway_logs_notification_id_index` (`notification_id`),
  KEY `email_gateway_logs_recipient_email_index` (`recipient_email`),
  KEY `email_gateway_logs_created_at_index` (`created_at`),
  CONSTRAINT `email_gateway_logs_notification_id_foreign` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `email_gateway_logs` (add only if missing)
CALL `tich_ensure_column`('email_gateway_logs', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('email_gateway_logs', 'notification_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('email_gateway_logs', 'recipient_email', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('email_gateway_logs', 'subject', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('email_gateway_logs', 'body_preview', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('email_gateway_logs', 'provider', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('email_gateway_logs', 'provider_message_id', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('email_gateway_logs', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'queued\\\'\'');
CALL `tich_ensure_column`('email_gateway_logs', 'bounce_reason', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('email_gateway_logs', 'complaint_reason', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('email_gateway_logs', 'opened_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('email_gateway_logs', 'clicked_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('email_gateway_logs', 'dispatched_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('email_gateway_logs', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `email_gateway_logs` (add only if missing)
CALL `tich_ensure_index`('email_gateway_logs', 'email_gateway_logs_created_at_index', '`created_at`');
CALL `tich_ensure_index`('email_gateway_logs', 'email_gateway_logs_notification_id_index', '`notification_id`');
CALL `tich_ensure_index`('email_gateway_logs', 'email_gateway_logs_recipient_email_index', '`recipient_email`');

-- -----------------------------------------------------------------------------
-- Table: `erp_registration_invitations`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `erp_registration_invitations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `sent_via_module` varchar(20) NOT NULL,
  `invited_by` bigint(20) unsigned NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `erp_registration_invitations_token_unique` (`token`),
  KEY `erp_registration_invitations_invited_by_foreign` (`invited_by`),
  KEY `erp_registration_invitations_email_used_at_index` (`email`,`used_at`),
  KEY `erp_registration_invitations_staff_id_foreign` (`staff_id`),
  CONSTRAINT `erp_registration_invitations_invited_by_foreign` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`),
  CONSTRAINT `erp_registration_invitations_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `erp_registration_invitations` (add only if missing)
CALL `tich_ensure_column`('erp_registration_invitations', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('erp_registration_invitations', 'staff_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('erp_registration_invitations', 'email', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('erp_registration_invitations', 'token', 'varchar(64) NOT NULL');
CALL `tich_ensure_column`('erp_registration_invitations', 'sent_via_module', 'varchar(20) NOT NULL');
CALL `tich_ensure_column`('erp_registration_invitations', 'invited_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('erp_registration_invitations', 'expires_at', 'datetime NOT NULL');
CALL `tich_ensure_column`('erp_registration_invitations', 'used_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('erp_registration_invitations', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `erp_registration_invitations` (add only if missing)
CALL `tich_ensure_index`('erp_registration_invitations', 'erp_registration_invitations_email_used_at_index', '`email`, `used_at`');
CALL `tich_ensure_index`('erp_registration_invitations', 'erp_registration_invitations_invited_by_foreign', '`invited_by`');
CALL `tich_ensure_index`('erp_registration_invitations', 'erp_registration_invitations_staff_id_foreign', '`staff_id`');
CALL `tich_ensure_unique`('erp_registration_invitations', 'erp_registration_invitations_token_unique', '`token`');

-- -----------------------------------------------------------------------------
-- Table: `events`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(300) NOT NULL,
  `slug` varchar(300) DEFAULT NULL,
  `subtitle` varchar(500) DEFAULT NULL,
  `event_type` varchar(50) NOT NULL,
  `description` longtext DEFAULT NULL,
  `cover_image_path` varchar(500) DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `venue` varchar(300) DEFAULT NULL,
  `registration_url_or_form` varchar(500) DEFAULT NULL,
  `is_public` tinyint(4) NOT NULL DEFAULT 1,
  `is_featured` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `events_slug_unique` (`slug`),
  KEY `events_created_by_foreign` (`created_by`),
  KEY `events_updated_by_foreign` (`updated_by`),
  CONSTRAINT `events_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `events_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `events` (add only if missing)
CALL `tich_ensure_column`('events', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('events', 'title', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('events', 'slug', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('events', 'subtitle', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('events', 'event_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('events', 'description', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('events', 'cover_image_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('events', 'start_datetime', 'datetime NOT NULL');
CALL `tich_ensure_column`('events', 'end_datetime', 'datetime NOT NULL');
CALL `tich_ensure_column`('events', 'venue', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('events', 'registration_url_or_form', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('events', 'is_public', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('events', 'is_featured', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('events', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('events', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('events', 'updated_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('events', 'updated_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `events` (add only if missing)
CALL `tich_ensure_index`('events', 'events_created_by_foreign', '`created_by`');
CALL `tich_ensure_unique`('events', 'events_slug_unique', '`slug`');
CALL `tich_ensure_index`('events', 'events_updated_by_foreign', '`updated_by`');

-- -----------------------------------------------------------------------------
-- Table: `examination_papers`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `examination_papers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `unit_id` bigint(20) unsigned NOT NULL,
  `semester_id` bigint(20) unsigned NOT NULL,
  `exam_type` varchar(50) NOT NULL,
  `version` varchar(10) NOT NULL DEFAULT 'A',
  `draft_file_path` varchar(500) DEFAULT NULL,
  `moderated_file_path` varchar(500) DEFAULT NULL,
  `approved_file_path` varchar(500) DEFAULT NULL,
  `is_encrypted` tinyint(4) NOT NULL DEFAULT 0,
  `encryption_key_hash` varchar(255) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `prepared_by` bigint(20) unsigned NOT NULL,
  `tabled_at` datetime DEFAULT NULL,
  `moderated_at` datetime DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `examination_papers_unit_id_foreign` (`unit_id`),
  KEY `examination_papers_semester_id_foreign` (`semester_id`),
  KEY `examination_papers_prepared_by_foreign` (`prepared_by`),
  KEY `examination_papers_approved_by_foreign` (`approved_by`),
  CONSTRAINT `examination_papers_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `examination_papers_prepared_by_foreign` FOREIGN KEY (`prepared_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `examination_papers_semester_id_foreign` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`),
  CONSTRAINT `examination_papers_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `examination_papers` (add only if missing)
CALL `tich_ensure_column`('examination_papers', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('examination_papers', 'unit_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('examination_papers', 'semester_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('examination_papers', 'exam_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('examination_papers', 'version', 'varchar(10) NOT NULL DEFAULT \'\\\'A\\\'\'');
CALL `tich_ensure_column`('examination_papers', 'draft_file_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('examination_papers', 'moderated_file_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('examination_papers', 'approved_file_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('examination_papers', 'is_encrypted', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('examination_papers', 'encryption_key_hash', 'varchar(255) NULL DEFAULT NULL');
CALL `tich_ensure_column`('examination_papers', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'draft\\\'\'');
CALL `tich_ensure_column`('examination_papers', 'prepared_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('examination_papers', 'tabled_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('examination_papers', 'moderated_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('examination_papers', 'approved_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('examination_papers', 'approved_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('examination_papers', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `examination_papers` (add only if missing)
CALL `tich_ensure_index`('examination_papers', 'examination_papers_approved_by_foreign', '`approved_by`');
CALL `tich_ensure_index`('examination_papers', 'examination_papers_prepared_by_foreign', '`prepared_by`');
CALL `tich_ensure_index`('examination_papers', 'examination_papers_semester_id_foreign', '`semester_id`');
CALL `tich_ensure_index`('examination_papers', 'examination_papers_unit_id_foreign', '`unit_id`');

-- -----------------------------------------------------------------------------
-- Table: `exam_cards`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exam_cards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `exam_card_number` varchar(50) NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `semester_id` bigint(20) unsigned NOT NULL,
  `examination_number` varchar(50) DEFAULT NULL,
  `qr_code_data` varchar(500) DEFAULT NULL,
  `issued_at` datetime NOT NULL,
  `is_voided` tinyint(4) NOT NULL DEFAULT 0,
  `voided_reason` varchar(500) DEFAULT NULL,
  `voided_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exam_card_unique` (`student_id`,`semester_id`),
  UNIQUE KEY `exam_cards_exam_card_number_unique` (`exam_card_number`),
  KEY `exam_cards_semester_id_foreign` (`semester_id`),
  KEY `exam_cards_voided_by_foreign` (`voided_by`),
  CONSTRAINT `exam_cards_semester_id_foreign` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`),
  CONSTRAINT `exam_cards_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `exam_cards_voided_by_foreign` FOREIGN KEY (`voided_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `exam_cards` (add only if missing)
CALL `tich_ensure_column`('exam_cards', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('exam_cards', 'exam_card_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('exam_cards', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('exam_cards', 'semester_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('exam_cards', 'examination_number', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('exam_cards', 'qr_code_data', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('exam_cards', 'issued_at', 'datetime NOT NULL');
CALL `tich_ensure_column`('exam_cards', 'is_voided', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('exam_cards', 'voided_reason', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('exam_cards', 'voided_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `exam_cards` (add only if missing)
CALL `tich_ensure_unique`('exam_cards', 'exam_cards_exam_card_number_unique', '`exam_card_number`');
CALL `tich_ensure_index`('exam_cards', 'exam_cards_semester_id_foreign', '`semester_id`');
CALL `tich_ensure_index`('exam_cards', 'exam_cards_voided_by_foreign', '`voided_by`');
CALL `tich_ensure_unique`('exam_cards', 'exam_card_unique', '`student_id`, `semester_id`');

-- -----------------------------------------------------------------------------
-- Table: `exam_eligibility_matrix`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exam_eligibility_matrix` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `unit_id` bigint(20) unsigned NOT NULL,
  `semester_id` bigint(20) unsigned NOT NULL,
  `attendance_check_passed` tinyint(4) NOT NULL DEFAULT 0,
  `attendance_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `fee_clearance_check_passed` tinyint(4) NOT NULL DEFAULT 0,
  `invigilator_assigned` tinyint(4) NOT NULL DEFAULT 0,
  `exam_card_issued` tinyint(4) NOT NULL DEFAULT 0,
  `eligible_for_exams` tinyint(4) NOT NULL DEFAULT 0,
  `calculated_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `exam_eligibility_matrix_student_id_unit_id_semester_id_unique` (`student_id`,`unit_id`,`semester_id`),
  KEY `exam_eligibility_matrix_unit_id_foreign` (`unit_id`),
  KEY `exam_eligibility_matrix_semester_id_foreign` (`semester_id`),
  CONSTRAINT `exam_eligibility_matrix_semester_id_foreign` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`),
  CONSTRAINT `exam_eligibility_matrix_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `exam_eligibility_matrix_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `exam_eligibility_matrix` (add only if missing)
CALL `tich_ensure_column`('exam_eligibility_matrix', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('exam_eligibility_matrix', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('exam_eligibility_matrix', 'unit_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('exam_eligibility_matrix', 'semester_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('exam_eligibility_matrix', 'attendance_check_passed', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('exam_eligibility_matrix', 'attendance_percentage', 'decimal(5,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('exam_eligibility_matrix', 'fee_clearance_check_passed', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('exam_eligibility_matrix', 'invigilator_assigned', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('exam_eligibility_matrix', 'exam_card_issued', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('exam_eligibility_matrix', 'eligible_for_exams', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('exam_eligibility_matrix', 'calculated_at', 'datetime NOT NULL');
CALL `tich_ensure_column`('exam_eligibility_matrix', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `exam_eligibility_matrix` (add only if missing)
CALL `tich_ensure_index`('exam_eligibility_matrix', 'exam_eligibility_matrix_semester_id_foreign', '`semester_id`');
CALL `tich_ensure_unique`('exam_eligibility_matrix', 'exam_eligibility_matrix_student_id_unit_id_semester_id_unique', '`student_id`, `unit_id`, `semester_id`');
CALL `tich_ensure_index`('exam_eligibility_matrix', 'exam_eligibility_matrix_unit_id_foreign', '`unit_id`');

-- -----------------------------------------------------------------------------
-- Table: `exam_results`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exam_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `exam_card_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `unit_id` bigint(20) unsigned NOT NULL,
  `semester_id` bigint(20) unsigned NOT NULL,
  `cat_total` decimal(5,2) NOT NULL DEFAULT 0.00,
  `practical_total` decimal(5,2) NOT NULL DEFAULT 0.00,
  `final_exam_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `final_total_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `grade_letter` varchar(5) DEFAULT NULL,
  `grade_points` decimal(5,2) DEFAULT NULL,
  `theory_pass_check` tinyint(4) NOT NULL DEFAULT 0,
  `clinical_pass_check` tinyint(4) NOT NULL DEFAULT 0,
  `is_supplementary` tinyint(4) NOT NULL DEFAULT 0,
  `supplementary_triggered` tinyint(4) NOT NULL DEFAULT 0,
  `clinical_supplementary_triggered` tinyint(4) NOT NULL DEFAULT 0,
  `is_special_exam` tinyint(4) NOT NULL DEFAULT 0,
  `is_remark_requested` tinyint(4) NOT NULL DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `moderator_id` bigint(20) unsigned DEFAULT NULL,
  `moderated_at` datetime DEFAULT NULL,
  `board_approved` tinyint(4) NOT NULL DEFAULT 0,
  `board_approved_at` datetime DEFAULT NULL,
  `is_published` tinyint(4) NOT NULL DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `entered_by` bigint(20) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `exam_results_exam_card_id_foreign` (`exam_card_id`),
  KEY `exam_results_semester_id_foreign` (`semester_id`),
  KEY `exam_results_moderator_id_foreign` (`moderator_id`),
  KEY `exam_results_entered_by_foreign` (`entered_by`),
  KEY `exam_results_student_id_semester_id_index` (`student_id`,`semester_id`),
  KEY `exam_results_unit_id_semester_id_index` (`unit_id`,`semester_id`),
  CONSTRAINT `exam_results_entered_by_foreign` FOREIGN KEY (`entered_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `exam_results_exam_card_id_foreign` FOREIGN KEY (`exam_card_id`) REFERENCES `exam_cards` (`id`),
  CONSTRAINT `exam_results_moderator_id_foreign` FOREIGN KEY (`moderator_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `exam_results_semester_id_foreign` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`),
  CONSTRAINT `exam_results_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `exam_results_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `exam_results` (add only if missing)
CALL `tich_ensure_column`('exam_results', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('exam_results', 'exam_card_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('exam_results', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('exam_results', 'unit_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('exam_results', 'semester_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('exam_results', 'cat_total', 'decimal(5,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('exam_results', 'practical_total', 'decimal(5,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('exam_results', 'final_exam_score', 'decimal(5,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('exam_results', 'final_total_score', 'decimal(5,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('exam_results', 'grade_letter', 'varchar(5) NULL DEFAULT NULL');
CALL `tich_ensure_column`('exam_results', 'grade_points', 'decimal(5,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('exam_results', 'theory_pass_check', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('exam_results', 'clinical_pass_check', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('exam_results', 'is_supplementary', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('exam_results', 'supplementary_triggered', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('exam_results', 'clinical_supplementary_triggered', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('exam_results', 'is_special_exam', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('exam_results', 'is_remark_requested', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('exam_results', 'remarks', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('exam_results', 'moderator_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('exam_results', 'moderated_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('exam_results', 'board_approved', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('exam_results', 'board_approved_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('exam_results', 'is_published', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('exam_results', 'published_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('exam_results', 'entered_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('exam_results', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('exam_results', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `exam_results` (add only if missing)
CALL `tich_ensure_index`('exam_results', 'exam_results_entered_by_foreign', '`entered_by`');
CALL `tich_ensure_index`('exam_results', 'exam_results_exam_card_id_foreign', '`exam_card_id`');
CALL `tich_ensure_index`('exam_results', 'exam_results_moderator_id_foreign', '`moderator_id`');
CALL `tich_ensure_index`('exam_results', 'exam_results_semester_id_foreign', '`semester_id`');
CALL `tich_ensure_index`('exam_results', 'exam_results_student_id_semester_id_index', '`student_id`, `semester_id`');
CALL `tich_ensure_index`('exam_results', 'exam_results_unit_id_semester_id_index', '`unit_id`, `semester_id`');

-- -----------------------------------------------------------------------------
-- Table: `exam_schedules`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exam_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `unit_id` bigint(20) unsigned NOT NULL,
  `semester_id` bigint(20) unsigned NOT NULL,
  `program_timetable_session_id` bigint(20) unsigned DEFAULT NULL,
  `exam_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `venue` varchar(200) NOT NULL,
  `exam_type` varchar(50) NOT NULL DEFAULT 'main',
  `invigilator_id` bigint(20) unsigned DEFAULT NULL,
  `second_invigilator_id` bigint(20) unsigned DEFAULT NULL,
  `total_candidates` int(11) NOT NULL DEFAULT 0,
  `status` varchar(50) NOT NULL DEFAULT 'scheduled',
  PRIMARY KEY (`id`),
  UNIQUE KEY `exam_schedules_pts_unique` (`program_timetable_session_id`),
  KEY `exam_schedules_semester_id_foreign` (`semester_id`),
  KEY `exam_schedules_invigilator_id_foreign` (`invigilator_id`),
  KEY `exam_schedules_second_invigilator_id_foreign` (`second_invigilator_id`),
  KEY `exam_schedules_unit_id_semester_id_index` (`unit_id`,`semester_id`),
  CONSTRAINT `exam_schedules_invigilator_id_foreign` FOREIGN KEY (`invigilator_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `exam_schedules_pts_fk` FOREIGN KEY (`program_timetable_session_id`) REFERENCES `program_timetable_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `exam_schedules_second_invigilator_id_foreign` FOREIGN KEY (`second_invigilator_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `exam_schedules_semester_id_foreign` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`),
  CONSTRAINT `exam_schedules_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `exam_schedules` (add only if missing)
CALL `tich_ensure_column`('exam_schedules', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('exam_schedules', 'unit_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('exam_schedules', 'semester_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('exam_schedules', 'program_timetable_session_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('exam_schedules', 'exam_date', 'date NOT NULL');
CALL `tich_ensure_column`('exam_schedules', 'start_time', 'time NOT NULL');
CALL `tich_ensure_column`('exam_schedules', 'end_time', 'time NOT NULL');
CALL `tich_ensure_column`('exam_schedules', 'venue', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('exam_schedules', 'exam_type', 'varchar(50) NOT NULL DEFAULT \'\\\'main\\\'\'');
CALL `tich_ensure_column`('exam_schedules', 'invigilator_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('exam_schedules', 'second_invigilator_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('exam_schedules', 'total_candidates', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('exam_schedules', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'scheduled\\\'\'');

-- Indexes for `exam_schedules` (add only if missing)
CALL `tich_ensure_index`('exam_schedules', 'exam_schedules_invigilator_id_foreign', '`invigilator_id`');
CALL `tich_ensure_unique`('exam_schedules', 'exam_schedules_pts_unique', '`program_timetable_session_id`');
CALL `tich_ensure_index`('exam_schedules', 'exam_schedules_second_invigilator_id_foreign', '`second_invigilator_id`');
CALL `tich_ensure_index`('exam_schedules', 'exam_schedules_semester_id_foreign', '`semester_id`');
CALL `tich_ensure_index`('exam_schedules', 'exam_schedules_unit_id_semester_id_index', '`unit_id`, `semester_id`');

-- -----------------------------------------------------------------------------
-- Table: `faqs`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `faqs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `question` varchar(500) NOT NULL,
  `answer` text NOT NULL,
  `category` varchar(100) NOT NULL,
  `subcategory` varchar(100) DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `language` varchar(10) NOT NULL DEFAULT 'en',
  `view_count` int(11) NOT NULL DEFAULT 0,
  `is_featured` tinyint(4) NOT NULL DEFAULT 0,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `faqs_created_by_foreign` (`created_by`),
  KEY `faqs_updated_by_foreign` (`updated_by`),
  CONSTRAINT `faqs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `faqs_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `faqs` (add only if missing)
CALL `tich_ensure_column`('faqs', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('faqs', 'question', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('faqs', 'answer', 'text NOT NULL');
CALL `tich_ensure_column`('faqs', 'category', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('faqs', 'subcategory', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('faqs', 'tags', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('faqs', 'language', 'varchar(10) NOT NULL DEFAULT \'\\\'en\\\'\'');
CALL `tich_ensure_column`('faqs', 'view_count', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('faqs', 'is_featured', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('faqs', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('faqs', 'display_order', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('faqs', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('faqs', 'updated_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('faqs', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('faqs', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `faqs` (add only if missing)
CALL `tich_ensure_index`('faqs', 'faqs_created_by_foreign', '`created_by`');
CALL `tich_ensure_index`('faqs', 'faqs_updated_by_foreign', '`updated_by`');

-- -----------------------------------------------------------------------------
-- Table: `feedback`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `feedback` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned NOT NULL,
  `feedback_type` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `status` enum('open','under_review','resolved','closed') NOT NULL DEFAULT 'open',
  `response` text DEFAULT NULL,
  `resolved_at` date DEFAULT NULL,
  `hr_comments` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `feedback_staff_id_status_index` (`staff_id`,`status`),
  CONSTRAINT `feedback_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `feedback` (add only if missing)
CALL `tich_ensure_column`('feedback', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('feedback', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('feedback', 'feedback_type', 'varchar(255) NULL DEFAULT NULL');
CALL `tich_ensure_column`('feedback', 'description', 'text NOT NULL');
CALL `tich_ensure_column`('feedback', 'status', 'enum(\'open\',\'under_review\',\'resolved\',\'closed\') NOT NULL DEFAULT \'\\\'open\\\'\'');
CALL `tich_ensure_column`('feedback', 'response', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('feedback', 'resolved_at', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('feedback', 'hr_comments', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('feedback', 'metadata', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('feedback', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('feedback', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `feedback` (add only if missing)
CALL `tich_ensure_index`('feedback', 'feedback_staff_id_status_index', '`staff_id`, `status`');

-- -----------------------------------------------------------------------------
-- Table: `fee_structures`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fee_structures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `program_id` bigint(20) unsigned NOT NULL,
  `academic_year_id` bigint(20) unsigned NOT NULL,
  `application_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tuition_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `caution_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cautions_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `computer_lab_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `accommodation_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `accommodation_optional` tinyint(4) NOT NULL DEFAULT 1,
  `transport_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `transport_optional` tinyint(4) NOT NULL DEFAULT 1,
  `partnership_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `id_card_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `student_union_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `quality_assurance_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `emergency_fund_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `library_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `examination_external_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `indexing_nck_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `requires_indexing_nck` tinyint(4) NOT NULL DEFAULT 0,
  `attachment_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `qa_annual_fee` decimal(12,2) NOT NULL DEFAULT 1000.00,
  `graduation_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_semester_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `is_approved` tinyint(4) NOT NULL DEFAULT 0,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `effective_from` date NOT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `version` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fee_struct_program_year_unique` (`program_id`,`academic_year_id`),
  KEY `fee_structures_academic_year_id_foreign` (`academic_year_id`),
  KEY `fee_structures_approved_by_foreign` (`approved_by`),
  KEY `fee_structures_program_id_index` (`program_id`),
  CONSTRAINT `fee_structures_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`),
  CONSTRAINT `fee_structures_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fee_structures_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `academic_programs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `fee_structures` (add only if missing)
CALL `tich_ensure_column`('fee_structures', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('fee_structures', 'program_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('fee_structures', 'academic_year_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('fee_structures', 'application_fee', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('fee_structures', 'tuition_fee', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('fee_structures', 'caution_fee', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('fee_structures', 'cautions_fee', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('fee_structures', 'computer_lab_fee', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('fee_structures', 'accommodation_fee', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('fee_structures', 'accommodation_optional', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('fee_structures', 'transport_fee', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('fee_structures', 'transport_optional', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('fee_structures', 'partnership_fee', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('fee_structures', 'id_card_fee', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('fee_structures', 'student_union_fee', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('fee_structures', 'quality_assurance_fee', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('fee_structures', 'emergency_fund_fee', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('fee_structures', 'library_fee', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('fee_structures', 'examination_external_fee', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('fee_structures', 'indexing_nck_fee', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('fee_structures', 'requires_indexing_nck', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('fee_structures', 'attachment_fee', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('fee_structures', 'qa_annual_fee', 'decimal(12,2) NOT NULL DEFAULT \'1000.00\'');
CALL `tich_ensure_column`('fee_structures', 'graduation_fee', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('fee_structures', 'total_semester_fee', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('fee_structures', 'is_approved', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('fee_structures', 'approved_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('fee_structures', 'approved_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('fee_structures', 'effective_from', 'date NOT NULL');
CALL `tich_ensure_column`('fee_structures', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('fee_structures', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'draft\\\'\'');
CALL `tich_ensure_column`('fee_structures', 'version', 'varchar(20) NULL DEFAULT NULL');
CALL `tich_ensure_column`('fee_structures', 'notes', 'text NULL DEFAULT NULL');

-- Indexes for `fee_structures` (add only if missing)
CALL `tich_ensure_index`('fee_structures', 'fee_structures_academic_year_id_foreign', '`academic_year_id`');
CALL `tich_ensure_index`('fee_structures', 'fee_structures_approved_by_foreign', '`approved_by`');
CALL `tich_ensure_index`('fee_structures', 'fee_structures_program_id_index', '`program_id`');
CALL `tich_ensure_unique`('fee_structures', 'fee_struct_program_year_unique', '`program_id`, `academic_year_id`');

-- -----------------------------------------------------------------------------
-- Table: `finance_budgets`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `finance_budgets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `budget_code` varchar(50) NOT NULL,
  `budget_name` varchar(300) NOT NULL,
  `budget_type` varchar(50) NOT NULL DEFAULT 'departmental',
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `fiscal_year` smallint(5) unsigned NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `allocated_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `spent_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `committed_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `finance_budgets_budget_code_unique` (`budget_code`),
  KEY `finance_budgets_department_id_foreign` (`department_id`),
  KEY `finance_budgets_approved_by_foreign` (`approved_by`),
  KEY `finance_budgets_fiscal_year_status_index` (`fiscal_year`,`status`),
  CONSTRAINT `finance_budgets_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `finance_budgets_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `finance_budgets` (add only if missing)
CALL `tich_ensure_column`('finance_budgets', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('finance_budgets', 'budget_code', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('finance_budgets', 'budget_name', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('finance_budgets', 'budget_type', 'varchar(50) NOT NULL DEFAULT \'\\\'departmental\\\'\'');
CALL `tich_ensure_column`('finance_budgets', 'department_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('finance_budgets', 'fiscal_year', 'smallint(5) unsigned NOT NULL');
CALL `tich_ensure_column`('finance_budgets', 'period_start', 'date NOT NULL');
CALL `tich_ensure_column`('finance_budgets', 'period_end', 'date NOT NULL');
CALL `tich_ensure_column`('finance_budgets', 'allocated_amount', 'decimal(14,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('finance_budgets', 'spent_amount', 'decimal(14,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('finance_budgets', 'committed_amount', 'decimal(14,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('finance_budgets', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'draft\\\'\'');
CALL `tich_ensure_column`('finance_budgets', 'approved_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('finance_budgets', 'approved_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('finance_budgets', 'notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('finance_budgets', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('finance_budgets', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `finance_budgets` (add only if missing)
CALL `tich_ensure_index`('finance_budgets', 'finance_budgets_approved_by_foreign', '`approved_by`');
CALL `tich_ensure_unique`('finance_budgets', 'finance_budgets_budget_code_unique', '`budget_code`');
CALL `tich_ensure_index`('finance_budgets', 'finance_budgets_department_id_foreign', '`department_id`');
CALL `tich_ensure_index`('finance_budgets', 'finance_budgets_fiscal_year_status_index', '`fiscal_year`, `status`');

-- -----------------------------------------------------------------------------
-- Table: `finance_budget_cycles`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `finance_budget_cycles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `budget_id` bigint(20) unsigned NOT NULL,
  `cycle_type` varchar(50) NOT NULL,
  `label` varchar(200) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `allocated_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `spent_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `committed_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `finance_budget_cycles_budget_id_cycle_type_index` (`budget_id`,`cycle_type`),
  CONSTRAINT `finance_budget_cycles_budget_id_foreign` FOREIGN KEY (`budget_id`) REFERENCES `finance_budgets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `finance_budget_cycles` (add only if missing)
CALL `tich_ensure_column`('finance_budget_cycles', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('finance_budget_cycles', 'budget_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('finance_budget_cycles', 'cycle_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('finance_budget_cycles', 'label', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('finance_budget_cycles', 'period_start', 'date NOT NULL');
CALL `tich_ensure_column`('finance_budget_cycles', 'period_end', 'date NOT NULL');
CALL `tich_ensure_column`('finance_budget_cycles', 'allocated_amount', 'decimal(14,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('finance_budget_cycles', 'spent_amount', 'decimal(14,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('finance_budget_cycles', 'committed_amount', 'decimal(14,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('finance_budget_cycles', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'active\\\'\'');
CALL `tich_ensure_column`('finance_budget_cycles', 'notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('finance_budget_cycles', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('finance_budget_cycles', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `finance_budget_cycles` (add only if missing)
CALL `tich_ensure_index`('finance_budget_cycles', 'finance_budget_cycles_budget_id_cycle_type_index', '`budget_id`, `cycle_type`');

-- -----------------------------------------------------------------------------
-- Table: `finance_mpesa_settings`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `finance_mpesa_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `environment` varchar(20) NOT NULL DEFAULT 'sandbox',
  `shortcode` varchar(20) DEFAULT NULL,
  `passkey` text DEFAULT NULL,
  `consumer_key` varchar(255) DEFAULT NULL,
  `consumer_secret` text DEFAULT NULL,
  `transaction_type` varchar(50) NOT NULL DEFAULT 'CustomerPayBillOnline',
  `account_reference_prefix` varchar(30) NOT NULL DEFAULT 'TICH',
  `callback_url_override` varchar(500) DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `finance_mpesa_settings` (add only if missing)
CALL `tich_ensure_column`('finance_mpesa_settings', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('finance_mpesa_settings', 'is_enabled', 'tinyint(1) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('finance_mpesa_settings', 'environment', 'varchar(20) NOT NULL DEFAULT \'\\\'sandbox\\\'\'');
CALL `tich_ensure_column`('finance_mpesa_settings', 'shortcode', 'varchar(20) NULL DEFAULT NULL');
CALL `tich_ensure_column`('finance_mpesa_settings', 'passkey', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('finance_mpesa_settings', 'consumer_key', 'varchar(255) NULL DEFAULT NULL');
CALL `tich_ensure_column`('finance_mpesa_settings', 'consumer_secret', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('finance_mpesa_settings', 'transaction_type', 'varchar(50) NOT NULL DEFAULT \'\\\'CustomerPayBillOnline\\\'\'');
CALL `tich_ensure_column`('finance_mpesa_settings', 'account_reference_prefix', 'varchar(30) NOT NULL DEFAULT \'\\\'TICH\\\'\'');
CALL `tich_ensure_column`('finance_mpesa_settings', 'callback_url_override', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('finance_mpesa_settings', 'updated_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('finance_mpesa_settings', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('finance_mpesa_settings', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `finance_mpesa_settings` (add only if missing)
-- (no secondary indexes)

-- -----------------------------------------------------------------------------
-- Table: `financial_adjustments`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `financial_adjustments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_account_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_item_id` bigint(20) unsigned DEFAULT NULL,
  `adjustment_type` varchar(50) NOT NULL,
  `reason` varchar(500) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `requested_by` bigint(20) unsigned NOT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `financial_adjustments_student_account_id_foreign` (`student_account_id`),
  KEY `financial_adjustments_student_id_foreign` (`student_id`),
  KEY `financial_adjustments_invoice_id_foreign` (`invoice_id`),
  KEY `financial_adjustments_invoice_item_id_foreign` (`invoice_item_id`),
  KEY `financial_adjustments_requested_by_foreign` (`requested_by`),
  KEY `financial_adjustments_approved_by_foreign` (`approved_by`),
  KEY `financial_adjustments_status_index` (`status`),
  CONSTRAINT `financial_adjustments_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `financial_adjustments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `financial_adjustments_invoice_item_id_foreign` FOREIGN KEY (`invoice_item_id`) REFERENCES `invoice_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `financial_adjustments_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `financial_adjustments_student_account_id_foreign` FOREIGN KEY (`student_account_id`) REFERENCES `student_accounts` (`id`),
  CONSTRAINT `financial_adjustments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `financial_adjustments` (add only if missing)
CALL `tich_ensure_column`('financial_adjustments', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('financial_adjustments', 'student_account_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('financial_adjustments', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('financial_adjustments', 'invoice_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('financial_adjustments', 'invoice_item_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('financial_adjustments', 'adjustment_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('financial_adjustments', 'reason', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('financial_adjustments', 'amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('financial_adjustments', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('financial_adjustments', 'requested_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('financial_adjustments', 'approved_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('financial_adjustments', 'approved_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('financial_adjustments', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `financial_adjustments` (add only if missing)
CALL `tich_ensure_index`('financial_adjustments', 'financial_adjustments_approved_by_foreign', '`approved_by`');
CALL `tich_ensure_index`('financial_adjustments', 'financial_adjustments_invoice_id_foreign', '`invoice_id`');
CALL `tich_ensure_index`('financial_adjustments', 'financial_adjustments_invoice_item_id_foreign', '`invoice_item_id`');
CALL `tich_ensure_index`('financial_adjustments', 'financial_adjustments_requested_by_foreign', '`requested_by`');
CALL `tich_ensure_index`('financial_adjustments', 'financial_adjustments_status_index', '`status`');
CALL `tich_ensure_index`('financial_adjustments', 'financial_adjustments_student_account_id_foreign', '`student_account_id`');
CALL `tich_ensure_index`('financial_adjustments', 'financial_adjustments_student_id_foreign', '`student_id`');

-- -----------------------------------------------------------------------------
-- Table: `gallery_albums`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gallery_albums` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `album_name` varchar(300) NOT NULL,
  `subtitle` varchar(500) DEFAULT NULL,
  `cover_image_path` varchar(500) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gallery_albums_created_by_foreign` (`created_by`),
  KEY `gallery_albums_updated_by_foreign` (`updated_by`),
  CONSTRAINT `gallery_albums_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `gallery_albums_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `gallery_albums` (add only if missing)
CALL `tich_ensure_column`('gallery_albums', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('gallery_albums', 'album_name', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('gallery_albums', 'subtitle', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('gallery_albums', 'cover_image_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('gallery_albums', 'display_order', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('gallery_albums', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('gallery_albums', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('gallery_albums', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('gallery_albums', 'updated_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('gallery_albums', 'updated_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `gallery_albums` (add only if missing)
CALL `tich_ensure_index`('gallery_albums', 'gallery_albums_created_by_foreign', '`created_by`');
CALL `tich_ensure_index`('gallery_albums', 'gallery_albums_updated_by_foreign', '`updated_by`');

-- -----------------------------------------------------------------------------
-- Table: `gallery_images`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gallery_images` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `album_id` bigint(20) unsigned NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `caption` varchar(500) DEFAULT NULL,
  `alt_text` varchar(300) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gallery_images_album_id_foreign` (`album_id`),
  KEY `gallery_images_uploaded_by_foreign` (`uploaded_by`),
  KEY `gallery_images_created_by_foreign` (`created_by`),
  CONSTRAINT `gallery_images_album_id_foreign` FOREIGN KEY (`album_id`) REFERENCES `gallery_albums` (`id`),
  CONSTRAINT `gallery_images_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `gallery_images_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `gallery_images` (add only if missing)
CALL `tich_ensure_column`('gallery_images', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('gallery_images', 'album_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('gallery_images', 'file_path', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('gallery_images', 'caption', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('gallery_images', 'alt_text', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('gallery_images', 'display_order', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('gallery_images', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('gallery_images', 'uploaded_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('gallery_images', 'uploaded_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('gallery_images', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `gallery_images` (add only if missing)
CALL `tich_ensure_index`('gallery_images', 'gallery_images_album_id_foreign', '`album_id`');
CALL `tich_ensure_index`('gallery_images', 'gallery_images_created_by_foreign', '`created_by`');
CALL `tich_ensure_index`('gallery_images', 'gallery_images_uploaded_by_foreign', '`uploaded_by`');

-- -----------------------------------------------------------------------------
-- Table: `goods_received_notes`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `goods_received_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `grn_number` varchar(50) NOT NULL,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `supplier_delivery_note` varchar(100) DEFAULT NULL,
  `received_date` date NOT NULL,
  `received_by` bigint(20) unsigned NOT NULL,
  `inspection_status` varchar(50) NOT NULL DEFAULT 'pending',
  `inspection_notes` text DEFAULT NULL,
  `shortages_or_damages` text DEFAULT NULL,
  `is_complete` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `goods_received_notes_grn_number_unique` (`grn_number`),
  KEY `goods_received_notes_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `goods_received_notes_received_by_foreign` (`received_by`),
  CONSTRAINT `goods_received_notes_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`),
  CONSTRAINT `goods_received_notes_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `goods_received_notes` (add only if missing)
CALL `tich_ensure_column`('goods_received_notes', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('goods_received_notes', 'grn_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('goods_received_notes', 'purchase_order_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('goods_received_notes', 'supplier_delivery_note', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('goods_received_notes', 'received_date', 'date NOT NULL');
CALL `tich_ensure_column`('goods_received_notes', 'received_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('goods_received_notes', 'inspection_status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('goods_received_notes', 'inspection_notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('goods_received_notes', 'shortages_or_damages', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('goods_received_notes', 'is_complete', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('goods_received_notes', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `goods_received_notes` (add only if missing)
CALL `tich_ensure_unique`('goods_received_notes', 'goods_received_notes_grn_number_unique', '`grn_number`');
CALL `tich_ensure_index`('goods_received_notes', 'goods_received_notes_purchase_order_id_foreign', '`purchase_order_id`');
CALL `tich_ensure_index`('goods_received_notes', 'goods_received_notes_received_by_foreign', '`received_by`');

-- -----------------------------------------------------------------------------
-- Table: `grade_records`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `grade_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `unit_id` bigint(20) unsigned NOT NULL,
  `semester_id` bigint(20) unsigned NOT NULL,
  `nursing_block_id` bigint(20) unsigned DEFAULT NULL,
  `exam_result_id` bigint(20) unsigned DEFAULT NULL,
  `final_score` decimal(5,2) NOT NULL,
  `grade_letter` varchar(5) NOT NULL,
  `grade_points` decimal(5,2) NOT NULL,
  `credit_hours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `is_repeat` tinyint(4) NOT NULL DEFAULT 0,
  `is_supplementary_pass` tinyint(4) NOT NULL DEFAULT 0,
  `gpa_at_time` decimal(5,2) DEFAULT NULL,
  `recorded_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `grade_rec_unique` (`student_id`,`unit_id`,`semester_id`),
  KEY `grade_records_unit_id_foreign` (`unit_id`),
  KEY `grade_records_semester_id_foreign` (`semester_id`),
  KEY `grade_records_nursing_block_id_foreign` (`nursing_block_id`),
  KEY `grade_records_exam_result_id_foreign` (`exam_result_id`),
  CONSTRAINT `grade_records_exam_result_id_foreign` FOREIGN KEY (`exam_result_id`) REFERENCES `exam_results` (`id`) ON DELETE SET NULL,
  CONSTRAINT `grade_records_nursing_block_id_foreign` FOREIGN KEY (`nursing_block_id`) REFERENCES `nursing_blocks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `grade_records_semester_id_foreign` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`),
  CONSTRAINT `grade_records_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `grade_records_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `grade_records` (add only if missing)
CALL `tich_ensure_column`('grade_records', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('grade_records', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('grade_records', 'unit_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('grade_records', 'semester_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('grade_records', 'nursing_block_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('grade_records', 'exam_result_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('grade_records', 'final_score', 'decimal(5,2) NOT NULL');
CALL `tich_ensure_column`('grade_records', 'grade_letter', 'varchar(5) NOT NULL');
CALL `tich_ensure_column`('grade_records', 'grade_points', 'decimal(5,2) NOT NULL');
CALL `tich_ensure_column`('grade_records', 'credit_hours', 'decimal(5,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('grade_records', 'is_repeat', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('grade_records', 'is_supplementary_pass', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('grade_records', 'gpa_at_time', 'decimal(5,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('grade_records', 'recorded_at', 'datetime NOT NULL');
CALL `tich_ensure_column`('grade_records', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `grade_records` (add only if missing)
CALL `tich_ensure_index`('grade_records', 'grade_records_exam_result_id_foreign', '`exam_result_id`');
CALL `tich_ensure_index`('grade_records', 'grade_records_nursing_block_id_foreign', '`nursing_block_id`');
CALL `tich_ensure_index`('grade_records', 'grade_records_semester_id_foreign', '`semester_id`');
CALL `tich_ensure_index`('grade_records', 'grade_records_unit_id_foreign', '`unit_id`');
CALL `tich_ensure_unique`('grade_records', 'grade_rec_unique', '`student_id`, `unit_id`, `semester_id`');

-- -----------------------------------------------------------------------------
-- Table: `grievances`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `grievances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference_number` varchar(30) DEFAULT NULL,
  `staff_id` bigint(20) unsigned NOT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `grievance_type` varchar(255) DEFAULT NULL,
  `subject` varchar(300) DEFAULT NULL,
  `description` text NOT NULL,
  `incident_date` date DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `status` enum('open','under_review','resolved','closed') NOT NULL DEFAULT 'open',
  `resolved_at` date DEFAULT NULL,
  `hr_comments` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `grievances_reference_number_unique` (`reference_number`),
  KEY `grievances_assigned_to_foreign` (`assigned_to`),
  KEY `grievances_staff_id_status_index` (`staff_id`,`status`),
  CONSTRAINT `grievances_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `grievances_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `grievances` (add only if missing)
CALL `tich_ensure_column`('grievances', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('grievances', 'reference_number', 'varchar(30) NULL DEFAULT NULL');
CALL `tich_ensure_column`('grievances', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('grievances', 'assigned_to', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('grievances', 'grievance_type', 'varchar(255) NULL DEFAULT NULL');
CALL `tich_ensure_column`('grievances', 'subject', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('grievances', 'description', 'text NOT NULL');
CALL `tich_ensure_column`('grievances', 'incident_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('grievances', 'resolution_notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('grievances', 'status', 'enum(\'open\',\'under_review\',\'resolved\',\'closed\') NOT NULL DEFAULT \'\\\'open\\\'\'');
CALL `tich_ensure_column`('grievances', 'resolved_at', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('grievances', 'hr_comments', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('grievances', 'metadata', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('grievances', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('grievances', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `grievances` (add only if missing)
CALL `tich_ensure_index`('grievances', 'grievances_assigned_to_foreign', '`assigned_to`');
CALL `tich_ensure_unique`('grievances', 'grievances_reference_number_unique', '`reference_number`');
CALL `tich_ensure_index`('grievances', 'grievances_staff_id_status_index', '`staff_id`, `status`');

-- -----------------------------------------------------------------------------
-- Table: `homepage_carousel_slides`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `homepage_carousel_slides` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `program_id` bigint(20) unsigned DEFAULT NULL,
  `event_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(300) NOT NULL,
  `subtitle` varchar(500) DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `cta_label` varchar(100) DEFAULT NULL,
  `cta_url` varchar(500) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `homepage_carousel_slides_program_id_unique` (`program_id`),
  UNIQUE KEY `homepage_carousel_slides_event_id_unique` (`event_id`),
  CONSTRAINT `homepage_carousel_slides_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
  CONSTRAINT `homepage_carousel_slides_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `academic_programs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `homepage_carousel_slides` (add only if missing)
CALL `tich_ensure_column`('homepage_carousel_slides', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('homepage_carousel_slides', 'program_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('homepage_carousel_slides', 'event_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('homepage_carousel_slides', 'title', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('homepage_carousel_slides', 'subtitle', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('homepage_carousel_slides', 'image_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('homepage_carousel_slides', 'video_url', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('homepage_carousel_slides', 'cta_label', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('homepage_carousel_slides', 'cta_url', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('homepage_carousel_slides', 'display_order', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('homepage_carousel_slides', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('homepage_carousel_slides', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('homepage_carousel_slides', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `homepage_carousel_slides` (add only if missing)
CALL `tich_ensure_unique`('homepage_carousel_slides', 'homepage_carousel_slides_event_id_unique', '`event_id`');
CALL `tich_ensure_unique`('homepage_carousel_slides', 'homepage_carousel_slides_program_id_unique', '`program_id`');

-- -----------------------------------------------------------------------------
-- Table: `hr_policies`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `hr_policies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `version` varchar(20) NOT NULL DEFAULT '1.0',
  `description` text DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `category` varchar(100) NOT NULL DEFAULT 'general',
  `effective_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `tags` text DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `hr_policies_slug_unique` (`slug`),
  KEY `hr_policies_uploaded_by_foreign` (`uploaded_by`),
  KEY `hr_policies_category_index` (`category`),
  KEY `hr_policies_is_active_index` (`is_active`),
  CONSTRAINT `hr_policies_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `hr_policies` (add only if missing)
CALL `tich_ensure_column`('hr_policies', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('hr_policies', 'title', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('hr_policies', 'slug', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('hr_policies', 'version', 'varchar(20) NOT NULL DEFAULT \'\\\'1.0\\\'\'');
CALL `tich_ensure_column`('hr_policies', 'description', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('hr_policies', 'file_path', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('hr_policies', 'original_filename', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('hr_policies', 'mime_type', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('hr_policies', 'file_size', 'int(11) NULL DEFAULT NULL');
CALL `tich_ensure_column`('hr_policies', 'category', 'varchar(100) NOT NULL DEFAULT \'\\\'general\\\'\'');
CALL `tich_ensure_column`('hr_policies', 'effective_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('hr_policies', 'expiry_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('hr_policies', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('hr_policies', 'tags', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('hr_policies', 'uploaded_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('hr_policies', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('hr_policies', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `hr_policies` (add only if missing)
CALL `tich_ensure_index`('hr_policies', 'hr_policies_category_index', '`category`');
CALL `tich_ensure_index`('hr_policies', 'hr_policies_is_active_index', '`is_active`');
CALL `tich_ensure_unique`('hr_policies', 'hr_policies_slug_unique', '`slug`');
CALL `tich_ensure_index`('hr_policies', 'hr_policies_uploaded_by_foreign', '`uploaded_by`');

-- -----------------------------------------------------------------------------
-- Table: `installment_plans`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `installment_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_account_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `academic_year_id` bigint(20) unsigned DEFAULT NULL,
  `semester_id` bigint(20) unsigned DEFAULT NULL,
  `plan_number` varchar(50) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `remaining_amount` decimal(12,2) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `installment_plans_plan_number_unique` (`plan_number`),
  KEY `installment_plans_student_account_id_foreign` (`student_account_id`),
  KEY `installment_plans_student_id_foreign` (`student_id`),
  KEY `installment_plans_invoice_id_foreign` (`invoice_id`),
  KEY `installment_plans_status_index` (`status`),
  CONSTRAINT `installment_plans_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `installment_plans_student_account_id_foreign` FOREIGN KEY (`student_account_id`) REFERENCES `student_accounts` (`id`),
  CONSTRAINT `installment_plans_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `installment_plans` (add only if missing)
CALL `tich_ensure_column`('installment_plans', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('installment_plans', 'student_account_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('installment_plans', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('installment_plans', 'invoice_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('installment_plans', 'academic_year_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('installment_plans', 'semester_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('installment_plans', 'plan_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('installment_plans', 'total_amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('installment_plans', 'paid_amount', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('installment_plans', 'remaining_amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('installment_plans', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'active\\\'\'');
CALL `tich_ensure_column`('installment_plans', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `installment_plans` (add only if missing)
CALL `tich_ensure_index`('installment_plans', 'installment_plans_invoice_id_foreign', '`invoice_id`');
CALL `tich_ensure_unique`('installment_plans', 'installment_plans_plan_number_unique', '`plan_number`');
CALL `tich_ensure_index`('installment_plans', 'installment_plans_status_index', '`status`');
CALL `tich_ensure_index`('installment_plans', 'installment_plans_student_account_id_foreign', '`student_account_id`');
CALL `tich_ensure_index`('installment_plans', 'installment_plans_student_id_foreign', '`student_id`');

-- -----------------------------------------------------------------------------
-- Table: `installment_plan_items`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `installment_plan_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `installment_plan_id` bigint(20) unsigned NOT NULL,
  `installment_number` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `due_date` date NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `installment_plan_items_installment_plan_id_foreign` (`installment_plan_id`),
  CONSTRAINT `installment_plan_items_installment_plan_id_foreign` FOREIGN KEY (`installment_plan_id`) REFERENCES `installment_plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `installment_plan_items` (add only if missing)
CALL `tich_ensure_column`('installment_plan_items', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('installment_plan_items', 'installment_plan_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('installment_plan_items', 'installment_number', 'int(11) NOT NULL');
CALL `tich_ensure_column`('installment_plan_items', 'amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('installment_plan_items', 'due_date', 'date NOT NULL');
CALL `tich_ensure_column`('installment_plan_items', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('installment_plan_items', 'paid_amount', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('installment_plan_items', 'paid_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `installment_plan_items` (add only if missing)
CALL `tich_ensure_index`('installment_plan_items', 'installment_plan_items_installment_plan_id_foreign', '`installment_plan_id`');

-- -----------------------------------------------------------------------------
-- Table: `institutional_timeline_events`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `institutional_timeline_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `year` int(11) NOT NULL,
  `title` varchar(300) NOT NULL,
  `subtitle` varchar(500) DEFAULT NULL,
  `description` text NOT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `institutional_timeline_events_created_by_foreign` (`created_by`),
  KEY `institutional_timeline_events_updated_by_foreign` (`updated_by`),
  CONSTRAINT `institutional_timeline_events_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `institutional_timeline_events_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `institutional_timeline_events` (add only if missing)
CALL `tich_ensure_column`('institutional_timeline_events', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('institutional_timeline_events', 'year', 'int(11) NOT NULL');
CALL `tich_ensure_column`('institutional_timeline_events', 'title', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('institutional_timeline_events', 'subtitle', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('institutional_timeline_events', 'description', 'text NOT NULL');
CALL `tich_ensure_column`('institutional_timeline_events', 'image_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('institutional_timeline_events', 'display_order', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('institutional_timeline_events', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('institutional_timeline_events', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('institutional_timeline_events', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('institutional_timeline_events', 'updated_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('institutional_timeline_events', 'updated_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `institutional_timeline_events` (add only if missing)
CALL `tich_ensure_index`('institutional_timeline_events', 'institutional_timeline_events_created_by_foreign', '`created_by`');
CALL `tich_ensure_index`('institutional_timeline_events', 'institutional_timeline_events_updated_by_foreign', '`updated_by`');

-- -----------------------------------------------------------------------------
-- Table: `inventory_items`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inventory_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `item_code` varchar(50) NOT NULL,
  `item_name` varchar(300) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `unit_of_measure` varchar(30) NOT NULL DEFAULT 'unit',
  `current_stock` int(11) NOT NULL DEFAULT 0,
  `minimum_stock` int(11) NOT NULL DEFAULT 0,
  `maximum_stock` int(11) NOT NULL DEFAULT 0,
  `reorder_level` int(11) NOT NULL DEFAULT 0,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `store_location` varchar(100) DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventory_items_item_code_unique` (`item_code`),
  KEY `inventory_items_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `inventory_items_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `inventory_items` (add only if missing)
CALL `tich_ensure_column`('inventory_items', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('inventory_items', 'item_code', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('inventory_items', 'item_name', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('inventory_items', 'category', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('inventory_items', 'unit_of_measure', 'varchar(30) NOT NULL DEFAULT \'\\\'unit\\\'\'');
CALL `tich_ensure_column`('inventory_items', 'current_stock', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('inventory_items', 'minimum_stock', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('inventory_items', 'maximum_stock', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('inventory_items', 'reorder_level', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('inventory_items', 'unit_cost', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('inventory_items', 'supplier_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('inventory_items', 'store_location', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('inventory_items', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('inventory_items', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `inventory_items` (add only if missing)
CALL `tich_ensure_unique`('inventory_items', 'inventory_items_item_code_unique', '`item_code`');
CALL `tich_ensure_index`('inventory_items', 'inventory_items_supplier_id_foreign', '`supplier_id`');

-- -----------------------------------------------------------------------------
-- Table: `inventory_transactions`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inventory_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inventory_item_id` bigint(20) unsigned NOT NULL,
  `transaction_type` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_cost` decimal(12,2) NOT NULL,
  `total_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reference_table` varchar(50) DEFAULT NULL,
  `reference_id` varchar(50) DEFAULT NULL,
  `from_location` varchar(100) DEFAULT NULL,
  `to_location` varchar(100) DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `recorded_by` bigint(20) unsigned NOT NULL,
  `transaction_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `inventory_transactions_inventory_item_id_foreign` (`inventory_item_id`),
  KEY `inventory_transactions_department_id_foreign` (`department_id`),
  KEY `inventory_transactions_recorded_by_foreign` (`recorded_by`),
  CONSTRAINT `inventory_transactions_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_transactions_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`),
  CONSTRAINT `inventory_transactions_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `inventory_transactions` (add only if missing)
CALL `tich_ensure_column`('inventory_transactions', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('inventory_transactions', 'inventory_item_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('inventory_transactions', 'transaction_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('inventory_transactions', 'quantity', 'int(11) NOT NULL');
CALL `tich_ensure_column`('inventory_transactions', 'unit_cost', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('inventory_transactions', 'total_cost', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('inventory_transactions', 'reference_table', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('inventory_transactions', 'reference_id', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('inventory_transactions', 'from_location', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('inventory_transactions', 'to_location', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('inventory_transactions', 'department_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('inventory_transactions', 'recorded_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('inventory_transactions', 'transaction_date', 'date NOT NULL');
CALL `tich_ensure_column`('inventory_transactions', 'notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('inventory_transactions', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `inventory_transactions` (add only if missing)
CALL `tich_ensure_index`('inventory_transactions', 'inventory_transactions_department_id_foreign', '`department_id`');
CALL `tich_ensure_index`('inventory_transactions', 'inventory_transactions_inventory_item_id_foreign', '`inventory_item_id`');
CALL `tich_ensure_index`('inventory_transactions', 'inventory_transactions_recorded_by_foreign', '`recorded_by`');

-- -----------------------------------------------------------------------------
-- Table: `invoices`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `student_account_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `semester_id` bigint(20) unsigned DEFAULT NULL,
  `fee_structure_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_type` varchar(50) NOT NULL,
  `description` varchar(500) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `amount_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'issued',
  `payment_gateway_ref` varchar(100) DEFAULT NULL,
  `is_sent_to_portal` tinyint(4) NOT NULL DEFAULT 0,
  `sent_at` datetime DEFAULT NULL,
  `last_reminder_sent_at` datetime DEFAULT NULL,
  `reminder_count` int(10) unsigned NOT NULL DEFAULT 0,
  `waiver_reason` varchar(500) DEFAULT NULL,
  `waived_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
  KEY `invoices_student_account_id_foreign` (`student_account_id`),
  KEY `invoices_semester_id_foreign` (`semester_id`),
  KEY `invoices_fee_structure_id_foreign` (`fee_structure_id`),
  KEY `invoices_waived_by_foreign` (`waived_by`),
  KEY `invoices_student_id_index` (`student_id`),
  KEY `invoices_status_index` (`status`),
  KEY `invoices_student_id_status_index` (`student_id`,`status`),
  KEY `invoices_status_due_date_index` (`status`,`due_date`),
  CONSTRAINT `invoices_fee_structure_id_foreign` FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structures` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_semester_id_foreign` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_student_account_id_foreign` FOREIGN KEY (`student_account_id`) REFERENCES `student_accounts` (`id`),
  CONSTRAINT `invoices_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `invoices_waived_by_foreign` FOREIGN KEY (`waived_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `invoices` (add only if missing)
CALL `tich_ensure_column`('invoices', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('invoices', 'invoice_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('invoices', 'student_account_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('invoices', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('invoices', 'semester_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('invoices', 'fee_structure_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('invoices', 'invoice_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('invoices', 'description', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('invoices', 'amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('invoices', 'amount_paid', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('invoices', 'balance', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('invoices', 'issue_date', 'date NOT NULL');
CALL `tich_ensure_column`('invoices', 'due_date', 'date NOT NULL');
CALL `tich_ensure_column`('invoices', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'issued\\\'\'');
CALL `tich_ensure_column`('invoices', 'payment_gateway_ref', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('invoices', 'is_sent_to_portal', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('invoices', 'sent_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('invoices', 'last_reminder_sent_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('invoices', 'reminder_count', 'int(10) unsigned NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('invoices', 'waiver_reason', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('invoices', 'waived_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('invoices', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('invoices', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `invoices` (add only if missing)
CALL `tich_ensure_index`('invoices', 'invoices_fee_structure_id_foreign', '`fee_structure_id`');
CALL `tich_ensure_unique`('invoices', 'invoices_invoice_number_unique', '`invoice_number`');
CALL `tich_ensure_index`('invoices', 'invoices_semester_id_foreign', '`semester_id`');
CALL `tich_ensure_index`('invoices', 'invoices_status_due_date_index', '`status`, `due_date`');
CALL `tich_ensure_index`('invoices', 'invoices_status_index', '`status`');
CALL `tich_ensure_index`('invoices', 'invoices_student_account_id_foreign', '`student_account_id`');
CALL `tich_ensure_index`('invoices', 'invoices_student_id_index', '`student_id`');
CALL `tich_ensure_index`('invoices', 'invoices_student_id_status_index', '`student_id`, `status`');
CALL `tich_ensure_index`('invoices', 'invoices_waived_by_foreign', '`waived_by`');

-- -----------------------------------------------------------------------------
-- Table: `invoice_items`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `fee_item` varchar(100) NOT NULL,
  `description` varchar(300) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `scholarship_adjustment` decimal(12,2) NOT NULL DEFAULT 0.00,
  `bursary_adjustment` decimal(12,2) NOT NULL DEFAULT 0.00,
  `waiver_adjustment` decimal(12,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(12,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `invoice_items_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `invoice_items` (add only if missing)
CALL `tich_ensure_column`('invoice_items', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('invoice_items', 'invoice_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('invoice_items', 'fee_item', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('invoice_items', 'description', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('invoice_items', 'amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('invoice_items', 'scholarship_adjustment', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('invoice_items', 'bursary_adjustment', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('invoice_items', 'waiver_adjustment', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('invoice_items', 'net_amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('invoice_items', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `invoice_items` (add only if missing)
CALL `tich_ensure_index`('invoice_items', 'invoice_items_invoice_id_foreign', '`invoice_id`');

-- -----------------------------------------------------------------------------
-- Table: `job_vacancies`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `job_vacancies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vacancy_number` varchar(50) NOT NULL,
  `job_title` varchar(200) NOT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `employment_type` varchar(50) NOT NULL,
  `position_grade` varchar(20) DEFAULT NULL,
  `slots_available` int(11) NOT NULL DEFAULT 1,
  `job_description` text NOT NULL,
  `requirements` text NOT NULL,
  `responsibilities` text NOT NULL,
  `salary_scale` varchar(200) DEFAULT NULL,
  `benefits` text DEFAULT NULL,
  `min_qualification` varchar(50) NOT NULL,
  `closing_date` date NOT NULL,
  `is_published` tinyint(4) NOT NULL DEFAULT 0,
  `published_on` date DEFAULT NULL,
  `is_closed` tinyint(4) NOT NULL DEFAULT 0,
  `closes_automatically` tinyint(4) NOT NULL DEFAULT 1,
  `slots_filled` int(11) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_vacancies_vacancy_number_unique` (`vacancy_number`),
  KEY `job_vacancies_department_id_foreign` (`department_id`),
  KEY `job_vacancies_created_by_foreign` (`created_by`),
  CONSTRAINT `job_vacancies_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `job_vacancies_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `job_vacancies` (add only if missing)
CALL `tich_ensure_column`('job_vacancies', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('job_vacancies', 'vacancy_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('job_vacancies', 'job_title', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('job_vacancies', 'department_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('job_vacancies', 'employment_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('job_vacancies', 'position_grade', 'varchar(20) NULL DEFAULT NULL');
CALL `tich_ensure_column`('job_vacancies', 'slots_available', 'int(11) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('job_vacancies', 'job_description', 'text NOT NULL');
CALL `tich_ensure_column`('job_vacancies', 'requirements', 'text NOT NULL');
CALL `tich_ensure_column`('job_vacancies', 'responsibilities', 'text NOT NULL');
CALL `tich_ensure_column`('job_vacancies', 'salary_scale', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('job_vacancies', 'benefits', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('job_vacancies', 'min_qualification', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('job_vacancies', 'closing_date', 'date NOT NULL');
CALL `tich_ensure_column`('job_vacancies', 'is_published', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('job_vacancies', 'published_on', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('job_vacancies', 'is_closed', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('job_vacancies', 'closes_automatically', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('job_vacancies', 'slots_filled', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('job_vacancies', 'created_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('job_vacancies', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `job_vacancies` (add only if missing)
CALL `tich_ensure_index`('job_vacancies', 'job_vacancies_created_by_foreign', '`created_by`');
CALL `tich_ensure_index`('job_vacancies', 'job_vacancies_department_id_foreign', '`department_id`');
CALL `tich_ensure_unique`('job_vacancies', 'job_vacancies_vacancy_number_unique', '`vacancy_number`');

-- -----------------------------------------------------------------------------
-- Table: `leave_balances`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `leave_balances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned NOT NULL,
  `leave_type_id` bigint(20) unsigned NOT NULL,
  `year` int(11) NOT NULL,
  `entitled_days` int(11) NOT NULL,
  `carried_forward_days` decimal(5,2) NOT NULL DEFAULT 0.00,
  `days_taken` int(11) NOT NULL DEFAULT 0,
  `days_pending` int(11) NOT NULL DEFAULT 0,
  `balance_days` int(11) NOT NULL DEFAULT 0,
  `last_updated` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_balances_staff_id_leave_type_id_year_unique` (`staff_id`,`leave_type_id`,`year`),
  KEY `leave_balances_leave_type_id_foreign` (`leave_type_id`),
  CONSTRAINT `leave_balances_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`),
  CONSTRAINT `leave_balances_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `leave_balances` (add only if missing)
CALL `tich_ensure_column`('leave_balances', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('leave_balances', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('leave_balances', 'leave_type_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('leave_balances', 'year', 'int(11) NOT NULL');
CALL `tich_ensure_column`('leave_balances', 'entitled_days', 'int(11) NOT NULL');
CALL `tich_ensure_column`('leave_balances', 'carried_forward_days', 'decimal(5,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('leave_balances', 'days_taken', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('leave_balances', 'days_pending', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('leave_balances', 'balance_days', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('leave_balances', 'last_updated', 'date NOT NULL');
CALL `tich_ensure_column`('leave_balances', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `leave_balances` (add only if missing)
CALL `tich_ensure_index`('leave_balances', 'leave_balances_leave_type_id_foreign', '`leave_type_id`');
CALL `tich_ensure_unique`('leave_balances', 'leave_balances_staff_id_leave_type_id_year_unique', '`staff_id`, `leave_type_id`, `year`');

-- -----------------------------------------------------------------------------
-- Table: `leave_carry_forward_requests`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `leave_carry_forward_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned NOT NULL,
  `leave_type_id` bigint(20) unsigned NOT NULL,
  `from_year` int(11) NOT NULL,
  `to_year` int(11) NOT NULL,
  `days_requested` decimal(5,2) NOT NULL,
  `days_approved` decimal(5,2) DEFAULT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `carry_forward_unique_per_year` (`staff_id`,`leave_type_id`,`from_year`),
  KEY `leave_carry_forward_requests_leave_type_id_foreign` (`leave_type_id`),
  KEY `leave_carry_forward_requests_reviewed_by_foreign` (`reviewed_by`),
  KEY `leave_carry_forward_requests_status_index` (`status`),
  CONSTRAINT `leave_carry_forward_requests_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_carry_forward_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_carry_forward_requests_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `leave_carry_forward_requests` (add only if missing)
CALL `tich_ensure_column`('leave_carry_forward_requests', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('leave_carry_forward_requests', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('leave_carry_forward_requests', 'leave_type_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('leave_carry_forward_requests', 'from_year', 'int(11) NOT NULL');
CALL `tich_ensure_column`('leave_carry_forward_requests', 'to_year', 'int(11) NOT NULL');
CALL `tich_ensure_column`('leave_carry_forward_requests', 'days_requested', 'decimal(5,2) NOT NULL');
CALL `tich_ensure_column`('leave_carry_forward_requests', 'days_approved', 'decimal(5,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('leave_carry_forward_requests', 'reason', 'text NOT NULL');
CALL `tich_ensure_column`('leave_carry_forward_requests', 'status', 'enum(\'pending\',\'approved\',\'rejected\') NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('leave_carry_forward_requests', 'reviewed_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('leave_carry_forward_requests', 'reviewed_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('leave_carry_forward_requests', 'review_notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('leave_carry_forward_requests', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('leave_carry_forward_requests', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `leave_carry_forward_requests` (add only if missing)
CALL `tich_ensure_unique`('leave_carry_forward_requests', 'carry_forward_unique_per_year', '`staff_id`, `leave_type_id`, `from_year`');
CALL `tich_ensure_index`('leave_carry_forward_requests', 'leave_carry_forward_requests_leave_type_id_foreign', '`leave_type_id`');
CALL `tich_ensure_index`('leave_carry_forward_requests', 'leave_carry_forward_requests_reviewed_by_foreign', '`reviewed_by`');
CALL `tich_ensure_index`('leave_carry_forward_requests', 'leave_carry_forward_requests_status_index', '`status`');

-- -----------------------------------------------------------------------------
-- Table: `leave_requests`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `leave_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `leave_number` varchar(50) NOT NULL,
  `staff_id` bigint(20) unsigned NOT NULL,
  `leave_type_id` bigint(20) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days_requested` int(10) unsigned NOT NULL,
  `reason` text NOT NULL,
  `is_emergency` tinyint(4) NOT NULL DEFAULT 0,
  `medical_certificate_path` varchar(500) DEFAULT NULL,
  `hod_approval_status` varchar(50) NOT NULL DEFAULT 'pending',
  `hod_approved_by` bigint(20) unsigned DEFAULT NULL,
  `hod_approved_at` datetime DEFAULT NULL,
  `hr_approval_status` varchar(50) NOT NULL DEFAULT 'pending',
  `hr_approved_by` bigint(20) unsigned DEFAULT NULL,
  `hr_approved_at` datetime DEFAULT NULL,
  `overall_status` varchar(50) NOT NULL DEFAULT 'pending_hod',
  `is_cancelled` tinyint(4) NOT NULL DEFAULT 0,
  `cancellation_reason` text DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `is_completed` tinyint(4) NOT NULL DEFAULT 0,
  `handover_notes` text DEFAULT NULL,
  `hr_review_notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_requests_leave_number_unique` (`leave_number`),
  KEY `leave_requests_leave_type_id_foreign` (`leave_type_id`),
  KEY `leave_requests_hod_approved_by_foreign` (`hod_approved_by`),
  KEY `leave_requests_hr_approved_by_foreign` (`hr_approved_by`),
  KEY `leave_requests_staff_id_index` (`staff_id`),
  KEY `leave_requests_overall_status_index` (`overall_status`),
  KEY `leave_requests_staff_overall_status_index` (`staff_id`,`overall_status`),
  KEY `leave_requests_overall_status_created_at_index` (`overall_status`,`created_at`),
  CONSTRAINT `leave_requests_hod_approved_by_foreign` FOREIGN KEY (`hod_approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_requests_hr_approved_by_foreign` FOREIGN KEY (`hr_approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_requests_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`),
  CONSTRAINT `leave_requests_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `leave_requests` (add only if missing)
CALL `tich_ensure_column`('leave_requests', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('leave_requests', 'leave_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('leave_requests', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('leave_requests', 'leave_type_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('leave_requests', 'start_date', 'date NOT NULL');
CALL `tich_ensure_column`('leave_requests', 'end_date', 'date NOT NULL');
CALL `tich_ensure_column`('leave_requests', 'days_requested', 'int(10) unsigned NOT NULL');
CALL `tich_ensure_column`('leave_requests', 'reason', 'text NOT NULL');
CALL `tich_ensure_column`('leave_requests', 'is_emergency', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('leave_requests', 'medical_certificate_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('leave_requests', 'hod_approval_status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('leave_requests', 'hod_approved_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('leave_requests', 'hod_approved_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('leave_requests', 'hr_approval_status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('leave_requests', 'hr_approved_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('leave_requests', 'hr_approved_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('leave_requests', 'overall_status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending_hod\\\'\'');
CALL `tich_ensure_column`('leave_requests', 'is_cancelled', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('leave_requests', 'cancellation_reason', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('leave_requests', 'return_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('leave_requests', 'is_completed', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('leave_requests', 'handover_notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('leave_requests', 'hr_review_notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('leave_requests', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('leave_requests', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `leave_requests` (add only if missing)
CALL `tich_ensure_index`('leave_requests', 'leave_requests_hod_approved_by_foreign', '`hod_approved_by`');
CALL `tich_ensure_index`('leave_requests', 'leave_requests_hr_approved_by_foreign', '`hr_approved_by`');
CALL `tich_ensure_unique`('leave_requests', 'leave_requests_leave_number_unique', '`leave_number`');
CALL `tich_ensure_index`('leave_requests', 'leave_requests_leave_type_id_foreign', '`leave_type_id`');
CALL `tich_ensure_index`('leave_requests', 'leave_requests_overall_status_created_at_index', '`overall_status`, `created_at`');
CALL `tich_ensure_index`('leave_requests', 'leave_requests_overall_status_index', '`overall_status`');
CALL `tich_ensure_index`('leave_requests', 'leave_requests_staff_id_index', '`staff_id`');
CALL `tich_ensure_index`('leave_requests', 'leave_requests_staff_overall_status_index', '`staff_id`, `overall_status`');

-- -----------------------------------------------------------------------------
-- Table: `leave_types`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `leave_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `leave_code` varchar(20) NOT NULL,
  `leave_name` varchar(200) NOT NULL,
  `days_allowed_per_year` int(11) NOT NULL,
  `accrual_type` varchar(50) NOT NULL DEFAULT 'none',
  `accrual_rate` decimal(6,2) DEFAULT NULL,
  `calculation_type` varchar(50) NOT NULL DEFAULT 'calendar_days',
  `is_paid` tinyint(4) NOT NULL DEFAULT 1,
  `is_payable` tinyint(4) NOT NULL DEFAULT 1,
  `requires_medical_certificate` tinyint(4) NOT NULL DEFAULT 0,
  `requires_certificate` tinyint(4) NOT NULL DEFAULT 0,
  `requires_hod_approval` tinyint(4) NOT NULL DEFAULT 1,
  `requires_hr_approval` tinyint(4) NOT NULL DEFAULT 1,
  `requires_approval_hod` tinyint(4) NOT NULL DEFAULT 1,
  `requires_approval_hr` tinyint(4) NOT NULL DEFAULT 1,
  `gender_restriction` varchar(50) NOT NULL DEFAULT 'any',
  `min_service_months` int(11) NOT NULL DEFAULT 0,
  `carry_forward_days` int(11) NOT NULL DEFAULT 0,
  `max_consecutive_days` int(11) DEFAULT NULL,
  `notice_period_days` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_types_leave_code_unique` (`leave_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `leave_types` (add only if missing)
CALL `tich_ensure_column`('leave_types', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('leave_types', 'leave_code', 'varchar(20) NOT NULL');
CALL `tich_ensure_column`('leave_types', 'leave_name', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('leave_types', 'days_allowed_per_year', 'int(11) NOT NULL');
CALL `tich_ensure_column`('leave_types', 'accrual_type', 'varchar(50) NOT NULL DEFAULT \'\\\'none\\\'\'');
CALL `tich_ensure_column`('leave_types', 'accrual_rate', 'decimal(6,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('leave_types', 'calculation_type', 'varchar(50) NOT NULL DEFAULT \'\\\'calendar_days\\\'\'');
CALL `tich_ensure_column`('leave_types', 'is_paid', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('leave_types', 'is_payable', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('leave_types', 'requires_medical_certificate', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('leave_types', 'requires_certificate', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('leave_types', 'requires_hod_approval', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('leave_types', 'requires_hr_approval', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('leave_types', 'requires_approval_hod', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('leave_types', 'requires_approval_hr', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('leave_types', 'gender_restriction', 'varchar(50) NOT NULL DEFAULT \'\\\'any\\\'\'');
CALL `tich_ensure_column`('leave_types', 'min_service_months', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('leave_types', 'carry_forward_days', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('leave_types', 'max_consecutive_days', 'int(11) NULL DEFAULT NULL');
CALL `tich_ensure_column`('leave_types', 'notice_period_days', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('leave_types', 'description', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('leave_types', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('leave_types', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `leave_types` (add only if missing)
CALL `tich_ensure_unique`('leave_types', 'leave_types_leave_code_unique', '`leave_code`');

-- -----------------------------------------------------------------------------
-- Table: `lesson_plans`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lesson_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `plan_number` varchar(50) NOT NULL,
  `unit_allocation_id` bigint(20) unsigned NOT NULL,
  `prepared_by` bigint(20) unsigned NOT NULL,
  `source_type` varchar(20) NOT NULL DEFAULT 'form',
  `lesson_title` varchar(255) DEFAULT NULL,
  `lesson_objectives` text NOT NULL,
  `topics_covered` text DEFAULT NULL,
  `competencies_targeted` text DEFAULT NULL,
  `contact_hours` int(11) NOT NULL,
  `week_number` int(11) NOT NULL,
  `planned_date` date NOT NULL,
  `teaching_methods` varchar(500) DEFAULT NULL,
  `resources_required` varchar(500) DEFAULT NULL,
  `uploaded_file_path` varchar(500) DEFAULT NULL,
  `uploaded_file_name` varchar(255) DEFAULT NULL,
  `form_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`form_payload`)),
  `tutor_verified_at` datetime DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `hod_comments` text DEFAULT NULL,
  `hod_id` bigint(20) unsigned DEFAULT NULL,
  `hod_action_at` datetime DEFAULT NULL,
  `registrar_visible` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `lesson_plans_plan_number_unique` (`plan_number`),
  KEY `lesson_plans_prepared_by_foreign` (`prepared_by`),
  KEY `lesson_plans_hod_id_foreign` (`hod_id`),
  KEY `lesson_plans_status_index` (`status`),
  KEY `lesson_plans_allocation_status_date_index` (`unit_allocation_id`,`status`,`planned_date`),
  CONSTRAINT `lesson_plans_hod_id_foreign` FOREIGN KEY (`hod_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lesson_plans_prepared_by_foreign` FOREIGN KEY (`prepared_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `lesson_plans_unit_allocation_id_foreign` FOREIGN KEY (`unit_allocation_id`) REFERENCES `unit_allocations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `lesson_plans` (add only if missing)
CALL `tich_ensure_column`('lesson_plans', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('lesson_plans', 'plan_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('lesson_plans', 'unit_allocation_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('lesson_plans', 'prepared_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('lesson_plans', 'source_type', 'varchar(20) NOT NULL DEFAULT \'\\\'form\\\'\'');
CALL `tich_ensure_column`('lesson_plans', 'lesson_title', 'varchar(255) NULL DEFAULT NULL');
CALL `tich_ensure_column`('lesson_plans', 'lesson_objectives', 'text NOT NULL');
CALL `tich_ensure_column`('lesson_plans', 'topics_covered', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('lesson_plans', 'competencies_targeted', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('lesson_plans', 'contact_hours', 'int(11) NOT NULL');
CALL `tich_ensure_column`('lesson_plans', 'week_number', 'int(11) NOT NULL');
CALL `tich_ensure_column`('lesson_plans', 'planned_date', 'date NOT NULL');
CALL `tich_ensure_column`('lesson_plans', 'teaching_methods', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('lesson_plans', 'resources_required', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('lesson_plans', 'uploaded_file_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('lesson_plans', 'uploaded_file_name', 'varchar(255) NULL DEFAULT NULL');
CALL `tich_ensure_column`('lesson_plans', 'form_payload', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('lesson_plans', 'tutor_verified_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('lesson_plans', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'draft\\\'\'');
CALL `tich_ensure_column`('lesson_plans', 'hod_comments', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('lesson_plans', 'hod_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('lesson_plans', 'hod_action_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('lesson_plans', 'registrar_visible', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('lesson_plans', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('lesson_plans', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `lesson_plans` (add only if missing)
CALL `tich_ensure_index`('lesson_plans', 'lesson_plans_allocation_status_date_index', '`unit_allocation_id`, `status`, `planned_date`');
CALL `tich_ensure_index`('lesson_plans', 'lesson_plans_hod_id_foreign', '`hod_id`');
CALL `tich_ensure_unique`('lesson_plans', 'lesson_plans_plan_number_unique', '`plan_number`');
CALL `tich_ensure_index`('lesson_plans', 'lesson_plans_prepared_by_foreign', '`prepared_by`');
CALL `tich_ensure_index`('lesson_plans', 'lesson_plans_status_index', '`status`');

-- -----------------------------------------------------------------------------
-- Table: `lesson_plan_approvals`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lesson_plan_approvals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lesson_plan_id` bigint(20) unsigned NOT NULL,
  `approver_id` bigint(20) unsigned NOT NULL,
  `approval_level` varchar(50) NOT NULL,
  `decision` varchar(50) NOT NULL,
  `comments` text DEFAULT NULL,
  `decided_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `lesson_plan_approvals_lesson_plan_id_foreign` (`lesson_plan_id`),
  KEY `lesson_plan_approvals_approver_id_foreign` (`approver_id`),
  CONSTRAINT `lesson_plan_approvals_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `staff` (`id`),
  CONSTRAINT `lesson_plan_approvals_lesson_plan_id_foreign` FOREIGN KEY (`lesson_plan_id`) REFERENCES `lesson_plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `lesson_plan_approvals` (add only if missing)
CALL `tich_ensure_column`('lesson_plan_approvals', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('lesson_plan_approvals', 'lesson_plan_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('lesson_plan_approvals', 'approver_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('lesson_plan_approvals', 'approval_level', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('lesson_plan_approvals', 'decision', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('lesson_plan_approvals', 'comments', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('lesson_plan_approvals', 'decided_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `lesson_plan_approvals` (add only if missing)
CALL `tich_ensure_index`('lesson_plan_approvals', 'lesson_plan_approvals_approver_id_foreign', '`approver_id`');
CALL `tich_ensure_index`('lesson_plan_approvals', 'lesson_plan_approvals_lesson_plan_id_foreign', '`lesson_plan_id`');

-- -----------------------------------------------------------------------------
-- Table: `media_attachments`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `media_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(100) NOT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `title` varchar(300) DEFAULT NULL,
  `caption` varchar(500) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `media_attachments_uploaded_by_foreign` (`uploaded_by`),
  KEY `media_attachments_created_by_foreign` (`created_by`),
  KEY `media_attachments_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  CONSTRAINT `media_attachments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `media_attachments_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `media_attachments` (add only if missing)
CALL `tich_ensure_column`('media_attachments', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('media_attachments', 'entity_type', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('media_attachments', 'entity_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('media_attachments', 'file_path', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('media_attachments', 'file_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('media_attachments', 'title', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('media_attachments', 'caption', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('media_attachments', 'display_order', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('media_attachments', 'uploaded_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('media_attachments', 'uploaded_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('media_attachments', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `media_attachments` (add only if missing)
CALL `tich_ensure_index`('media_attachments', 'media_attachments_created_by_foreign', '`created_by`');
CALL `tich_ensure_index`('media_attachments', 'media_attachments_entity_type_entity_id_index', '`entity_type`, `entity_id`');
CALL `tich_ensure_index`('media_attachments', 'media_attachments_uploaded_by_foreign', '`uploaded_by`');

-- -----------------------------------------------------------------------------
-- Table: `medical_records`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `medical_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `blood_group` varchar(10) DEFAULT NULL,
  `known_medical_conditions` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `special_needs` text DEFAULT NULL,
  `disability_status` varchar(50) NOT NULL DEFAULT 'none',
  `disability_description` text DEFAULT NULL,
  `medical_form_file_path` varchar(500) DEFAULT NULL,
  `is_approved` tinyint(4) NOT NULL DEFAULT 0,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `medical_records_student_id_foreign` (`student_id`),
  KEY `medical_records_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `medical_records_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `medical_records_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `medical_records` (add only if missing)
CALL `tich_ensure_column`('medical_records', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('medical_records', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('medical_records', 'blood_group', 'varchar(10) NULL DEFAULT NULL');
CALL `tich_ensure_column`('medical_records', 'known_medical_conditions', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('medical_records', 'allergies', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('medical_records', 'special_needs', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('medical_records', 'disability_status', 'varchar(50) NOT NULL DEFAULT \'\\\'none\\\'\'');
CALL `tich_ensure_column`('medical_records', 'disability_description', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('medical_records', 'medical_form_file_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('medical_records', 'is_approved', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('medical_records', 'reviewed_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('medical_records', 'reviewed_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('medical_records', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('medical_records', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `medical_records` (add only if missing)
CALL `tich_ensure_index`('medical_records', 'medical_records_reviewed_by_foreign', '`reviewed_by`');
CALL `tich_ensure_index`('medical_records', 'medical_records_student_id_foreign', '`student_id`');

-- -----------------------------------------------------------------------------
-- Table: `migrations`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `migrations` (add only if missing)
CALL `tich_ensure_column`('migrations', 'id', 'int(10) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('migrations', 'migration', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('migrations', 'batch', 'int(11) NOT NULL');

-- Indexes for `migrations` (add only if missing)
-- (no secondary indexes)

-- -----------------------------------------------------------------------------
-- Table: `mpesa_stk_requests`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mpesa_stk_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `student_id` bigint(20) unsigned DEFAULT NULL,
  `applicant_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `account_reference` varchar(50) NOT NULL,
  `merchant_request_id` varchar(255) DEFAULT NULL,
  `checkout_request_id` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `result_code` int(11) DEFAULT NULL,
  `result_desc` varchar(500) DEFAULT NULL,
  `mpesa_receipt_number` varchar(50) DEFAULT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `callback_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`callback_payload`)),
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mpesa_stk_requests_checkout_request_id_unique` (`checkout_request_id`),
  KEY `mpesa_stk_requests_payment_id_foreign` (`payment_id`),
  KEY `mpesa_stk_requests_invoice_id_status_index` (`invoice_id`,`status`),
  KEY `mpesa_stk_requests_status_created_at_index` (`status`,`created_at`),
  KEY `mpesa_stk_requests_merchant_request_id_index` (`merchant_request_id`),
  KEY `mpesa_stk_requests_mpesa_receipt_number_index` (`mpesa_receipt_number`),
  KEY `mpesa_stk_requests_student_id_foreign` (`student_id`),
  KEY `mpesa_stk_requests_applicant_id_status_index` (`applicant_id`,`status`),
  CONSTRAINT `mpesa_stk_requests_applicant_id_foreign` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mpesa_stk_requests_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mpesa_stk_requests_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mpesa_stk_requests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `mpesa_stk_requests` (add only if missing)
CALL `tich_ensure_column`('mpesa_stk_requests', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('mpesa_stk_requests', 'invoice_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('mpesa_stk_requests', 'student_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('mpesa_stk_requests', 'applicant_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('mpesa_stk_requests', 'amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('mpesa_stk_requests', 'phone', 'varchar(20) NOT NULL');
CALL `tich_ensure_column`('mpesa_stk_requests', 'account_reference', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('mpesa_stk_requests', 'merchant_request_id', 'varchar(255) NULL DEFAULT NULL');
CALL `tich_ensure_column`('mpesa_stk_requests', 'checkout_request_id', 'varchar(255) NULL DEFAULT NULL');
CALL `tich_ensure_column`('mpesa_stk_requests', 'status', 'varchar(20) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('mpesa_stk_requests', 'result_code', 'int(11) NULL DEFAULT NULL');
CALL `tich_ensure_column`('mpesa_stk_requests', 'result_desc', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('mpesa_stk_requests', 'mpesa_receipt_number', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('mpesa_stk_requests', 'payment_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('mpesa_stk_requests', 'callback_payload', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('mpesa_stk_requests', 'completed_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('mpesa_stk_requests', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('mpesa_stk_requests', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `mpesa_stk_requests` (add only if missing)
CALL `tich_ensure_index`('mpesa_stk_requests', 'mpesa_stk_requests_applicant_id_status_index', '`applicant_id`, `status`');
CALL `tich_ensure_unique`('mpesa_stk_requests', 'mpesa_stk_requests_checkout_request_id_unique', '`checkout_request_id`');
CALL `tich_ensure_index`('mpesa_stk_requests', 'mpesa_stk_requests_invoice_id_status_index', '`invoice_id`, `status`');
CALL `tich_ensure_index`('mpesa_stk_requests', 'mpesa_stk_requests_merchant_request_id_index', '`merchant_request_id`');
CALL `tich_ensure_index`('mpesa_stk_requests', 'mpesa_stk_requests_mpesa_receipt_number_index', '`mpesa_receipt_number`');
CALL `tich_ensure_index`('mpesa_stk_requests', 'mpesa_stk_requests_payment_id_foreign', '`payment_id`');
CALL `tich_ensure_index`('mpesa_stk_requests', 'mpesa_stk_requests_status_created_at_index', '`status`, `created_at`');
CALL `tich_ensure_index`('mpesa_stk_requests', 'mpesa_stk_requests_student_id_foreign', '`student_id`');

-- -----------------------------------------------------------------------------
-- Table: `navigation_menus`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `navigation_menus` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `menu_name` varchar(100) NOT NULL,
  `display_label` varchar(200) NOT NULL,
  `location` varchar(50) NOT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `navigation_menus_menu_name_unique` (`menu_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `navigation_menus` (add only if missing)
CALL `tich_ensure_column`('navigation_menus', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('navigation_menus', 'menu_name', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('navigation_menus', 'display_label', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('navigation_menus', 'location', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('navigation_menus', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('navigation_menus', 'display_order', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('navigation_menus', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `navigation_menus` (add only if missing)
CALL `tich_ensure_unique`('navigation_menus', 'navigation_menus_menu_name_unique', '`menu_name`');

-- -----------------------------------------------------------------------------
-- Table: `navigation_menu_items`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `navigation_menu_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `menu_id` bigint(20) unsigned NOT NULL,
  `parent_item_id` bigint(20) unsigned DEFAULT NULL,
  `label` varchar(200) NOT NULL,
  `label_sw` varchar(200) DEFAULT NULL,
  `url_or_route` varchar(500) DEFAULT NULL,
  `icon_name` varchar(50) DEFAULT NULL,
  `target` varchar(20) NOT NULL DEFAULT 'self',
  `requires_auth` tinyint(4) NOT NULL DEFAULT 0,
  `allowed_user_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_user_types`)),
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `navigation_menu_items_menu_id_foreign` (`menu_id`),
  KEY `navigation_menu_items_parent_item_id_foreign` (`parent_item_id`),
  CONSTRAINT `navigation_menu_items_menu_id_foreign` FOREIGN KEY (`menu_id`) REFERENCES `navigation_menus` (`id`),
  CONSTRAINT `navigation_menu_items_parent_item_id_foreign` FOREIGN KEY (`parent_item_id`) REFERENCES `navigation_menu_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `navigation_menu_items` (add only if missing)
CALL `tich_ensure_column`('navigation_menu_items', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('navigation_menu_items', 'menu_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('navigation_menu_items', 'parent_item_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('navigation_menu_items', 'label', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('navigation_menu_items', 'label_sw', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('navigation_menu_items', 'url_or_route', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('navigation_menu_items', 'icon_name', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('navigation_menu_items', 'target', 'varchar(20) NOT NULL DEFAULT \'\\\'self\\\'\'');
CALL `tich_ensure_column`('navigation_menu_items', 'requires_auth', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('navigation_menu_items', 'allowed_user_types', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('navigation_menu_items', 'display_order', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('navigation_menu_items', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('navigation_menu_items', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('navigation_menu_items', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `navigation_menu_items` (add only if missing)
CALL `tich_ensure_index`('navigation_menu_items', 'navigation_menu_items_menu_id_foreign', '`menu_id`');
CALL `tich_ensure_index`('navigation_menu_items', 'navigation_menu_items_parent_item_id_foreign', '`parent_item_id`');

-- -----------------------------------------------------------------------------
-- Table: `newsletter_campaigns`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `newsletter_campaigns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `campaign_name` varchar(300) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `preview_text` varchar(200) DEFAULT NULL,
  `body` longtext NOT NULL,
  `sender_name` varchar(200) NOT NULL,
  `sender_email` varchar(255) NOT NULL,
  `target_segment` varchar(50) NOT NULL DEFAULT 'all_active',
  `custom_filter_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_filter_json`)),
  `recipient_count` int(11) NOT NULL DEFAULT 0,
  `scheduled_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `sent_by` bigint(20) unsigned NOT NULL,
  `opens_count` int(11) NOT NULL DEFAULT 0,
  `clicks_count` int(11) NOT NULL DEFAULT 0,
  `unsubscribes_count` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `newsletter_campaigns_sent_by_foreign` (`sent_by`),
  CONSTRAINT `newsletter_campaigns_sent_by_foreign` FOREIGN KEY (`sent_by`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `newsletter_campaigns` (add only if missing)
CALL `tich_ensure_column`('newsletter_campaigns', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('newsletter_campaigns', 'campaign_name', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('newsletter_campaigns', 'subject', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('newsletter_campaigns', 'preview_text', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('newsletter_campaigns', 'body', 'longtext NOT NULL');
CALL `tich_ensure_column`('newsletter_campaigns', 'sender_name', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('newsletter_campaigns', 'sender_email', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('newsletter_campaigns', 'target_segment', 'varchar(50) NOT NULL DEFAULT \'\\\'all_active\\\'\'');
CALL `tich_ensure_column`('newsletter_campaigns', 'custom_filter_json', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('newsletter_campaigns', 'recipient_count', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('newsletter_campaigns', 'scheduled_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('newsletter_campaigns', 'sent_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('newsletter_campaigns', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'draft\\\'\'');
CALL `tich_ensure_column`('newsletter_campaigns', 'sent_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('newsletter_campaigns', 'opens_count', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('newsletter_campaigns', 'clicks_count', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('newsletter_campaigns', 'unsubscribes_count', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('newsletter_campaigns', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('newsletter_campaigns', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `newsletter_campaigns` (add only if missing)
CALL `tich_ensure_index`('newsletter_campaigns', 'newsletter_campaigns_sent_by_foreign', '`sent_by`');

-- -----------------------------------------------------------------------------
-- Table: `newsletter_campaign_recipients`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `newsletter_campaign_recipients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` bigint(20) unsigned NOT NULL,
  `subscriber_id` bigint(20) unsigned NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'queued',
  `opened_at` datetime DEFAULT NULL,
  `clicked_at` datetime DEFAULT NULL,
  `bounced_reason` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nl_camp_sub_unique` (`campaign_id`,`subscriber_id`),
  KEY `newsletter_campaign_recipients_subscriber_id_foreign` (`subscriber_id`),
  CONSTRAINT `newsletter_campaign_recipients_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `newsletter_campaigns` (`id`),
  CONSTRAINT `newsletter_campaign_recipients_subscriber_id_foreign` FOREIGN KEY (`subscriber_id`) REFERENCES `newsletter_subscribers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `newsletter_campaign_recipients` (add only if missing)
CALL `tich_ensure_column`('newsletter_campaign_recipients', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('newsletter_campaign_recipients', 'campaign_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('newsletter_campaign_recipients', 'subscriber_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('newsletter_campaign_recipients', 'email', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('newsletter_campaign_recipients', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'queued\\\'\'');
CALL `tich_ensure_column`('newsletter_campaign_recipients', 'opened_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('newsletter_campaign_recipients', 'clicked_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('newsletter_campaign_recipients', 'bounced_reason', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('newsletter_campaign_recipients', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `newsletter_campaign_recipients` (add only if missing)
CALL `tich_ensure_index`('newsletter_campaign_recipients', 'newsletter_campaign_recipients_subscriber_id_foreign', '`subscriber_id`');
CALL `tich_ensure_unique`('newsletter_campaign_recipients', 'nl_camp_sub_unique', '`campaign_id`, `subscriber_id`');

-- -----------------------------------------------------------------------------
-- Table: `newsletter_subscribers`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `subscriber_name` varchar(200) DEFAULT NULL,
  `subscriber_type` varchar(50) NOT NULL DEFAULT 'guest',
  `linked_user_id` bigint(20) unsigned DEFAULT NULL,
  `subscribed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `unsubscribe_token` varchar(100) NOT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `is_confirmed` tinyint(4) NOT NULL DEFAULT 0,
  `confirmed_at` datetime DEFAULT NULL,
  `unsubscribed_at` datetime DEFAULT NULL,
  `last_sent_at` datetime DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `newsletter_subscribers_email_unique` (`email`),
  UNIQUE KEY `newsletter_subscribers_unsubscribe_token_unique` (`unsubscribe_token`),
  KEY `newsletter_subscribers_linked_user_id_foreign` (`linked_user_id`),
  CONSTRAINT `newsletter_subscribers_linked_user_id_foreign` FOREIGN KEY (`linked_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `newsletter_subscribers` (add only if missing)
CALL `tich_ensure_column`('newsletter_subscribers', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('newsletter_subscribers', 'email', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('newsletter_subscribers', 'subscriber_name', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('newsletter_subscribers', 'subscriber_type', 'varchar(50) NOT NULL DEFAULT \'\\\'guest\\\'\'');
CALL `tich_ensure_column`('newsletter_subscribers', 'linked_user_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('newsletter_subscribers', 'subscribed_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('newsletter_subscribers', 'unsubscribe_token', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('newsletter_subscribers', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('newsletter_subscribers', 'is_confirmed', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('newsletter_subscribers', 'confirmed_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('newsletter_subscribers', 'unsubscribed_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('newsletter_subscribers', 'last_sent_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('newsletter_subscribers', 'source', 'varchar(100) NULL DEFAULT NULL');

-- Indexes for `newsletter_subscribers` (add only if missing)
CALL `tich_ensure_unique`('newsletter_subscribers', 'newsletter_subscribers_email_unique', '`email`');
CALL `tich_ensure_index`('newsletter_subscribers', 'newsletter_subscribers_linked_user_id_foreign', '`linked_user_id`');
CALL `tich_ensure_unique`('newsletter_subscribers', 'newsletter_subscribers_unsubscribe_token_unique', '`unsubscribe_token`');

-- -----------------------------------------------------------------------------
-- Table: `notifications`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `template_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(500) NOT NULL,
  `body` text NOT NULL,
  `channels_sent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`channels_sent`)),
  `related_entity_type` varchar(100) DEFAULT NULL,
  `related_entity_id` varchar(50) DEFAULT NULL,
  `is_read` tinyint(4) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `read_device_info` varchar(500) DEFAULT NULL,
  `priority` varchar(20) NOT NULL DEFAULT 'normal',
  `is_dismissed` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `notifications_template_id_foreign` (`template_id`),
  KEY `notifications_user_id_index` (`user_id`),
  KEY `notifications_user_id_is_read_index` (`user_id`,`is_read`),
  KEY `notifications_created_at_index` (`created_at`),
  CONSTRAINT `notifications_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `notification_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `notifications` (add only if missing)
CALL `tich_ensure_column`('notifications', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('notifications', 'user_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('notifications', 'template_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('notifications', 'title', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('notifications', 'body', 'text NOT NULL');
CALL `tich_ensure_column`('notifications', 'channels_sent', 'longtext NOT NULL');
CALL `tich_ensure_column`('notifications', 'related_entity_type', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('notifications', 'related_entity_id', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('notifications', 'is_read', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('notifications', 'read_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('notifications', 'read_device_info', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('notifications', 'priority', 'varchar(20) NOT NULL DEFAULT \'\\\'normal\\\'\'');
CALL `tich_ensure_column`('notifications', 'is_dismissed', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('notifications', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `notifications` (add only if missing)
CALL `tich_ensure_index`('notifications', 'notifications_created_at_index', '`created_at`');
CALL `tich_ensure_index`('notifications', 'notifications_template_id_foreign', '`template_id`');
CALL `tich_ensure_index`('notifications', 'notifications_user_id_index', '`user_id`');
CALL `tich_ensure_index`('notifications', 'notifications_user_id_is_read_index', '`user_id`, `is_read`');

-- -----------------------------------------------------------------------------
-- Table: `notification_templates`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notification_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `template_key` varchar(100) NOT NULL,
  `event_type` varchar(100) NOT NULL,
  `title_template` varchar(500) NOT NULL,
  `body_template` text NOT NULL,
  `channels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`channels`)),
  `priority` varchar(20) NOT NULL DEFAULT 'normal',
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `language` varchar(10) NOT NULL DEFAULT 'en',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_templates_template_key_unique` (`template_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `notification_templates` (add only if missing)
CALL `tich_ensure_column`('notification_templates', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('notification_templates', 'template_key', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('notification_templates', 'event_type', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('notification_templates', 'title_template', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('notification_templates', 'body_template', 'text NOT NULL');
CALL `tich_ensure_column`('notification_templates', 'channels', 'longtext NOT NULL');
CALL `tich_ensure_column`('notification_templates', 'priority', 'varchar(20) NOT NULL DEFAULT \'\\\'normal\\\'\'');
CALL `tich_ensure_column`('notification_templates', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('notification_templates', 'language', 'varchar(10) NOT NULL DEFAULT \'\\\'en\\\'\'');
CALL `tich_ensure_column`('notification_templates', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('notification_templates', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `notification_templates` (add only if missing)
CALL `tich_ensure_unique`('notification_templates', 'notification_templates_template_key_unique', '`template_key`');

-- -----------------------------------------------------------------------------
-- Table: `nursing_blocks`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `nursing_blocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `block_label` varchar(50) NOT NULL,
  `block_order` int(11) NOT NULL,
  `duration_months` int(11) NOT NULL DEFAULT 4,
  `program_id` bigint(20) unsigned NOT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nursing_blocks_program_id_foreign` (`program_id`),
  CONSTRAINT `nursing_blocks_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `academic_programs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `nursing_blocks` (add only if missing)
CALL `tich_ensure_column`('nursing_blocks', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('nursing_blocks', 'block_label', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('nursing_blocks', 'block_order', 'int(11) NOT NULL');
CALL `tich_ensure_column`('nursing_blocks', 'duration_months', 'int(11) NOT NULL DEFAULT \'4\'');
CALL `tich_ensure_column`('nursing_blocks', 'program_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('nursing_blocks', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('nursing_blocks', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('nursing_blocks', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `nursing_blocks` (add only if missing)
CALL `tich_ensure_index`('nursing_blocks', 'nursing_blocks_program_id_foreign', '`program_id`');

-- -----------------------------------------------------------------------------
-- Table: `nursing_block_progress`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `nursing_block_progress` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `block_id` bigint(20) unsigned NOT NULL,
  `program_id` bigint(20) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `expected_end_date` date DEFAULT NULL,
  `actual_end_date` date DEFAULT NULL,
  `clinical_field_logs_submitted` tinyint(4) NOT NULL DEFAULT 0,
  `skills_lab_assessment_passed` tinyint(4) NOT NULL DEFAULT 0,
  `theory_block_exam_score` decimal(5,2) DEFAULT NULL,
  `clinical_exam_score` decimal(5,2) DEFAULT NULL,
  `is_block_passed` tinyint(4) NOT NULL DEFAULT 0,
  `is_progression_locked` tinyint(4) NOT NULL DEFAULT 0,
  `block_status` varchar(50) NOT NULL DEFAULT 'in_progress',
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nursing_block_progress_student_id_block_id_unique` (`student_id`,`block_id`),
  KEY `nursing_block_progress_block_id_foreign` (`block_id`),
  KEY `nursing_block_progress_program_id_foreign` (`program_id`),
  CONSTRAINT `nursing_block_progress_block_id_foreign` FOREIGN KEY (`block_id`) REFERENCES `nursing_blocks` (`id`),
  CONSTRAINT `nursing_block_progress_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `academic_programs` (`id`),
  CONSTRAINT `nursing_block_progress_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `nursing_block_progress` (add only if missing)
CALL `tich_ensure_column`('nursing_block_progress', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('nursing_block_progress', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('nursing_block_progress', 'block_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('nursing_block_progress', 'program_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('nursing_block_progress', 'start_date', 'date NOT NULL');
CALL `tich_ensure_column`('nursing_block_progress', 'expected_end_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('nursing_block_progress', 'actual_end_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('nursing_block_progress', 'clinical_field_logs_submitted', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('nursing_block_progress', 'skills_lab_assessment_passed', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('nursing_block_progress', 'theory_block_exam_score', 'decimal(5,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('nursing_block_progress', 'clinical_exam_score', 'decimal(5,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('nursing_block_progress', 'is_block_passed', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('nursing_block_progress', 'is_progression_locked', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('nursing_block_progress', 'block_status', 'varchar(50) NOT NULL DEFAULT \'\\\'in_progress\\\'\'');
CALL `tich_ensure_column`('nursing_block_progress', 'completed_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('nursing_block_progress', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `nursing_block_progress` (add only if missing)
CALL `tich_ensure_index`('nursing_block_progress', 'nursing_block_progress_block_id_foreign', '`block_id`');
CALL `tich_ensure_index`('nursing_block_progress', 'nursing_block_progress_program_id_foreign', '`program_id`');
CALL `tich_ensure_unique`('nursing_block_progress', 'nursing_block_progress_student_id_block_id_unique', '`student_id`, `block_id`');

-- -----------------------------------------------------------------------------
-- Table: `objective_assessments`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `objective_assessments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `unit_allocation_id` bigint(20) unsigned NOT NULL,
  `unit_id` bigint(20) unsigned NOT NULL,
  `semester_id` bigint(20) unsigned NOT NULL,
  `name` varchar(200) NOT NULL,
  `assessment_type` varchar(50) NOT NULL DEFAULT 'mcq',
  `max_score` decimal(5,2) NOT NULL DEFAULT 100.00,
  `created_by` bigint(20) unsigned NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `auto_graded_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `objective_assessments_unit_allocation_id_foreign` (`unit_allocation_id`),
  KEY `objective_assessments_unit_id_foreign` (`unit_id`),
  KEY `objective_assessments_semester_id_foreign` (`semester_id`),
  KEY `objective_assessments_created_by_foreign` (`created_by`),
  CONSTRAINT `objective_assessments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `objective_assessments_semester_id_foreign` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`),
  CONSTRAINT `objective_assessments_unit_allocation_id_foreign` FOREIGN KEY (`unit_allocation_id`) REFERENCES `unit_allocations` (`id`),
  CONSTRAINT `objective_assessments_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `objective_assessments` (add only if missing)
CALL `tich_ensure_column`('objective_assessments', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('objective_assessments', 'unit_allocation_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('objective_assessments', 'unit_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('objective_assessments', 'semester_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('objective_assessments', 'name', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('objective_assessments', 'assessment_type', 'varchar(50) NOT NULL DEFAULT \'\\\'mcq\\\'\'');
CALL `tich_ensure_column`('objective_assessments', 'max_score', 'decimal(5,2) NOT NULL DEFAULT \'100.00\'');
CALL `tich_ensure_column`('objective_assessments', 'created_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('objective_assessments', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'draft\\\'\'');
CALL `tich_ensure_column`('objective_assessments', 'auto_graded_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('objective_assessments', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('objective_assessments', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `objective_assessments` (add only if missing)
CALL `tich_ensure_index`('objective_assessments', 'objective_assessments_created_by_foreign', '`created_by`');
CALL `tich_ensure_index`('objective_assessments', 'objective_assessments_semester_id_foreign', '`semester_id`');
CALL `tich_ensure_index`('objective_assessments', 'objective_assessments_unit_allocation_id_foreign', '`unit_allocation_id`');
CALL `tich_ensure_index`('objective_assessments', 'objective_assessments_unit_id_foreign', '`unit_id`');

-- -----------------------------------------------------------------------------
-- Table: `objective_questions`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `objective_questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `objective_assessment_id` bigint(20) unsigned NOT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 1,
  `question_text` text NOT NULL,
  `question_type` varchar(50) NOT NULL DEFAULT 'mcq',
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `correct_answer` varchar(500) NOT NULL,
  `points` decimal(5,2) NOT NULL DEFAULT 1.00,
  PRIMARY KEY (`id`),
  KEY `objective_questions_objective_assessment_id_foreign` (`objective_assessment_id`),
  CONSTRAINT `objective_questions_objective_assessment_id_foreign` FOREIGN KEY (`objective_assessment_id`) REFERENCES `objective_assessments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `objective_questions` (add only if missing)
CALL `tich_ensure_column`('objective_questions', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('objective_questions', 'objective_assessment_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('objective_questions', 'sort_order', 'smallint(5) unsigned NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('objective_questions', 'question_text', 'text NOT NULL');
CALL `tich_ensure_column`('objective_questions', 'question_type', 'varchar(50) NOT NULL DEFAULT \'\\\'mcq\\\'\'');
CALL `tich_ensure_column`('objective_questions', 'options', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('objective_questions', 'correct_answer', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('objective_questions', 'points', 'decimal(5,2) NOT NULL DEFAULT \'1.00\'');

-- Indexes for `objective_questions` (add only if missing)
CALL `tich_ensure_index`('objective_questions', 'objective_questions_objective_assessment_id_foreign', '`objective_assessment_id`');

-- -----------------------------------------------------------------------------
-- Table: `objective_submissions`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `objective_submissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `objective_assessment_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `responses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`responses`)),
  `score_obtained` decimal(5,2) DEFAULT NULL,
  `percentage_score` decimal(5,2) DEFAULT NULL,
  `correct_count` smallint(5) unsigned NOT NULL DEFAULT 0,
  `question_count` smallint(5) unsigned NOT NULL DEFAULT 0,
  `auto_graded_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `obj_sub_unique` (`objective_assessment_id`,`student_id`),
  KEY `objective_submissions_student_id_foreign` (`student_id`),
  CONSTRAINT `objective_submissions_objective_assessment_id_foreign` FOREIGN KEY (`objective_assessment_id`) REFERENCES `objective_assessments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `objective_submissions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `objective_submissions` (add only if missing)
CALL `tich_ensure_column`('objective_submissions', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('objective_submissions', 'objective_assessment_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('objective_submissions', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('objective_submissions', 'responses', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('objective_submissions', 'score_obtained', 'decimal(5,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('objective_submissions', 'percentage_score', 'decimal(5,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('objective_submissions', 'correct_count', 'smallint(5) unsigned NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('objective_submissions', 'question_count', 'smallint(5) unsigned NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('objective_submissions', 'auto_graded_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('objective_submissions', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('objective_submissions', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `objective_submissions` (add only if missing)
CALL `tich_ensure_index`('objective_submissions', 'objective_submissions_student_id_foreign', '`student_id`');
CALL `tich_ensure_unique`('objective_submissions', 'obj_sub_unique', '`objective_assessment_id`, `student_id`');

-- -----------------------------------------------------------------------------
-- Table: `offboarding_requests`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `offboarding_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned NOT NULL,
  `exit_type` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `exit_date` date NOT NULL,
  `notice_period_days` int(11) DEFAULT NULL,
  `last_working_day` date DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `termination_reason` text DEFAULT NULL,
  `initiated_by` bigint(20) unsigned NOT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `processed_by` bigint(20) unsigned DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `offboarding_requests_initiated_by_foreign` (`initiated_by`),
  KEY `offboarding_requests_approved_by_foreign` (`approved_by`),
  KEY `offboarding_requests_processed_by_foreign` (`processed_by`),
  KEY `offboarding_requests_staff_id_status_index` (`staff_id`,`status`),
  KEY `offboarding_requests_exit_type_index` (`exit_type`),
  CONSTRAINT `offboarding_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `offboarding_requests_initiated_by_foreign` FOREIGN KEY (`initiated_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `offboarding_requests_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `offboarding_requests_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `offboarding_requests` (add only if missing)
CALL `tich_ensure_column`('offboarding_requests', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('offboarding_requests', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('offboarding_requests', 'exit_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('offboarding_requests', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('offboarding_requests', 'exit_date', 'date NOT NULL');
CALL `tich_ensure_column`('offboarding_requests', 'notice_period_days', 'int(11) NULL DEFAULT NULL');
CALL `tich_ensure_column`('offboarding_requests', 'last_working_day', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('offboarding_requests', 'reason', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('offboarding_requests', 'termination_reason', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('offboarding_requests', 'initiated_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('offboarding_requests', 'approved_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('offboarding_requests', 'approved_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('offboarding_requests', 'processed_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('offboarding_requests', 'processed_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('offboarding_requests', 'notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('offboarding_requests', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('offboarding_requests', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `offboarding_requests` (add only if missing)
CALL `tich_ensure_index`('offboarding_requests', 'offboarding_requests_approved_by_foreign', '`approved_by`');
CALL `tich_ensure_index`('offboarding_requests', 'offboarding_requests_exit_type_index', '`exit_type`');
CALL `tich_ensure_index`('offboarding_requests', 'offboarding_requests_initiated_by_foreign', '`initiated_by`');
CALL `tich_ensure_index`('offboarding_requests', 'offboarding_requests_processed_by_foreign', '`processed_by`');
CALL `tich_ensure_index`('offboarding_requests', 'offboarding_requests_staff_id_status_index', '`staff_id`, `status`');

-- -----------------------------------------------------------------------------
-- Table: `partnership_requests`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `partnership_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_number` varchar(50) NOT NULL,
  `organization_name` varchar(300) NOT NULL,
  `organization_type` varchar(50) NOT NULL,
  `contact_person` varchar(200) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `proposed_scope` text NOT NULL,
  `target_sub_counties` varchar(500) DEFAULT NULL,
  `supporting_document_path` varchar(500) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending_review',
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `alert_sent_to_registrar` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `partnership_requests_request_number_unique` (`request_number`),
  KEY `partnership_requests_reviewed_by_foreign` (`reviewed_by`),
  KEY `partnership_requests_created_by_foreign` (`created_by`),
  KEY `partnership_requests_updated_by_foreign` (`updated_by`),
  CONSTRAINT `partnership_requests_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `partnership_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `partnership_requests_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `partnership_requests` (add only if missing)
CALL `tich_ensure_column`('partnership_requests', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('partnership_requests', 'request_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('partnership_requests', 'organization_name', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('partnership_requests', 'organization_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('partnership_requests', 'contact_person', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('partnership_requests', 'email', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('partnership_requests', 'phone', 'varchar(30) NOT NULL');
CALL `tich_ensure_column`('partnership_requests', 'proposed_scope', 'text NOT NULL');
CALL `tich_ensure_column`('partnership_requests', 'target_sub_counties', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('partnership_requests', 'supporting_document_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('partnership_requests', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending_review\\\'\'');
CALL `tich_ensure_column`('partnership_requests', 'reviewed_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('partnership_requests', 'reviewed_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('partnership_requests', 'review_notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('partnership_requests', 'alert_sent_to_registrar', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('partnership_requests', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('partnership_requests', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('partnership_requests', 'updated_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('partnership_requests', 'updated_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `partnership_requests` (add only if missing)
CALL `tich_ensure_index`('partnership_requests', 'partnership_requests_created_by_foreign', '`created_by`');
CALL `tich_ensure_unique`('partnership_requests', 'partnership_requests_request_number_unique', '`request_number`');
CALL `tich_ensure_index`('partnership_requests', 'partnership_requests_reviewed_by_foreign', '`reviewed_by`');
CALL `tich_ensure_index`('partnership_requests', 'partnership_requests_updated_by_foreign', '`updated_by`');

-- -----------------------------------------------------------------------------
-- Table: `partner_logos`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `partner_logos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `partner_name` varchar(300) NOT NULL,
  `category` varchar(50) NOT NULL,
  `logo_path` varchar(500) NOT NULL,
  `website_url` varchar(500) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `partner_logos_created_by_foreign` (`created_by`),
  KEY `partner_logos_updated_by_foreign` (`updated_by`),
  CONSTRAINT `partner_logos_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `partner_logos_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `partner_logos` (add only if missing)
CALL `tich_ensure_column`('partner_logos', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('partner_logos', 'partner_name', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('partner_logos', 'category', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('partner_logos', 'logo_path', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('partner_logos', 'website_url', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('partner_logos', 'display_order', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('partner_logos', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('partner_logos', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('partner_logos', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('partner_logos', 'updated_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('partner_logos', 'updated_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `partner_logos` (add only if missing)
CALL `tich_ensure_index`('partner_logos', 'partner_logos_created_by_foreign', '`created_by`');
CALL `tich_ensure_index`('partner_logos', 'partner_logos_updated_by_foreign', '`updated_by`');

-- -----------------------------------------------------------------------------
-- Table: `payments`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_number` varchar(50) NOT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `student_account_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'INITIATED',
  `mpesa_receipt_number` varchar(50) DEFAULT NULL,
  `transaction_channel_ref` varchar(100) DEFAULT NULL,
  `is_reconciled` tinyint(4) NOT NULL DEFAULT 0,
  `reconciled_by` bigint(20) unsigned DEFAULT NULL,
  `reconciled_at` datetime DEFAULT NULL,
  `recorded_by` bigint(20) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_payment_number_unique` (`payment_number`),
  UNIQUE KEY `payments_mpesa_receipt_number_unique` (`mpesa_receipt_number`),
  KEY `payments_student_account_id_foreign` (`student_account_id`),
  KEY `payments_reconciled_by_foreign` (`reconciled_by`),
  KEY `payments_recorded_by_foreign` (`recorded_by`),
  KEY `payments_student_id_payment_date_index` (`student_id`,`payment_date`),
  KEY `payments_payment_reference_index` (`payment_reference`),
  KEY `payments_status_index` (`status`),
  KEY `payments_payment_date_index` (`payment_date`),
  KEY `payments_invoice_id_status_index` (`invoice_id`,`status`),
  CONSTRAINT `payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `payments_reconciled_by_foreign` FOREIGN KEY (`reconciled_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `payments_student_account_id_foreign` FOREIGN KEY (`student_account_id`) REFERENCES `student_accounts` (`id`),
  CONSTRAINT `payments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `payments` (add only if missing)
CALL `tich_ensure_column`('payments', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('payments', 'payment_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('payments', 'invoice_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('payments', 'student_account_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('payments', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('payments', 'payment_date', 'date NOT NULL');
CALL `tich_ensure_column`('payments', 'amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('payments', 'payment_method', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('payments', 'payment_reference', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('payments', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'INITIATED\\\'\'');
CALL `tich_ensure_column`('payments', 'mpesa_receipt_number', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('payments', 'transaction_channel_ref', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('payments', 'is_reconciled', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('payments', 'reconciled_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('payments', 'reconciled_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('payments', 'recorded_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('payments', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `payments` (add only if missing)
CALL `tich_ensure_index`('payments', 'payments_invoice_id_status_index', '`invoice_id`, `status`');
CALL `tich_ensure_unique`('payments', 'payments_mpesa_receipt_number_unique', '`mpesa_receipt_number`');
CALL `tich_ensure_index`('payments', 'payments_payment_date_index', '`payment_date`');
CALL `tich_ensure_unique`('payments', 'payments_payment_number_unique', '`payment_number`');
CALL `tich_ensure_index`('payments', 'payments_payment_reference_index', '`payment_reference`');
CALL `tich_ensure_index`('payments', 'payments_reconciled_by_foreign', '`reconciled_by`');
CALL `tich_ensure_index`('payments', 'payments_recorded_by_foreign', '`recorded_by`');
CALL `tich_ensure_index`('payments', 'payments_status_index', '`status`');
CALL `tich_ensure_index`('payments', 'payments_student_account_id_foreign', '`student_account_id`');
CALL `tich_ensure_index`('payments', 'payments_student_id_payment_date_index', '`student_id`, `payment_date`');

-- -----------------------------------------------------------------------------
-- Table: `payment_allocations`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `allocated_amount` decimal(12,2) NOT NULL,
  `allocated_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `payment_allocations_payment_id_foreign` (`payment_id`),
  KEY `payment_allocations_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `payment_allocations_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `payment_allocations_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `payment_allocations` (add only if missing)
CALL `tich_ensure_column`('payment_allocations', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('payment_allocations', 'payment_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('payment_allocations', 'invoice_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('payment_allocations', 'allocated_amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('payment_allocations', 'allocated_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `payment_allocations` (add only if missing)
CALL `tich_ensure_index`('payment_allocations', 'payment_allocations_invoice_id_foreign', '`invoice_id`');
CALL `tich_ensure_index`('payment_allocations', 'payment_allocations_payment_id_foreign', '`payment_id`');

-- -----------------------------------------------------------------------------
-- Table: `payment_milestones`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_milestones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_account_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `milestone_type` varchar(50) NOT NULL,
  `percentage` tinyint(4) NOT NULL DEFAULT 0,
  `milestone_amount` decimal(12,2) NOT NULL,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `payment_milestones_student_id_foreign` (`student_id`),
  KEY `payment_milestones_invoice_id_foreign` (`invoice_id`),
  KEY `payment_milestones_recorded_by_foreign` (`recorded_by`),
  KEY `payment_milestones_student_account_id_milestone_type_index` (`student_account_id`,`milestone_type`),
  KEY `payment_milestones_status_index` (`status`),
  CONSTRAINT `payment_milestones_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payment_milestones_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payment_milestones_student_account_id_foreign` FOREIGN KEY (`student_account_id`) REFERENCES `student_accounts` (`id`),
  CONSTRAINT `payment_milestones_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `payment_milestones` (add only if missing)
CALL `tich_ensure_column`('payment_milestones', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('payment_milestones', 'student_account_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('payment_milestones', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('payment_milestones', 'invoice_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('payment_milestones', 'milestone_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('payment_milestones', 'percentage', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('payment_milestones', 'milestone_amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('payment_milestones', 'paid_amount', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('payment_milestones', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('payment_milestones', 'due_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('payment_milestones', 'paid_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('payment_milestones', 'recorded_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('payment_milestones', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('payment_milestones', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `payment_milestones` (add only if missing)
CALL `tich_ensure_index`('payment_milestones', 'payment_milestones_invoice_id_foreign', '`invoice_id`');
CALL `tich_ensure_index`('payment_milestones', 'payment_milestones_recorded_by_foreign', '`recorded_by`');
CALL `tich_ensure_index`('payment_milestones', 'payment_milestones_status_index', '`status`');
CALL `tich_ensure_index`('payment_milestones', 'payment_milestones_student_account_id_milestone_type_index', '`student_account_id`, `milestone_type`');
CALL `tich_ensure_index`('payment_milestones', 'payment_milestones_student_id_foreign', '`student_id`');

-- -----------------------------------------------------------------------------
-- Table: `payroll_band_deduction_rates`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payroll_band_deduction_rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payroll_tax_band_id` bigint(20) unsigned NOT NULL,
  `payroll_deduction_type_id` bigint(20) unsigned NOT NULL,
  `rate_percent` decimal(7,4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payroll_band_deduction_unique` (`payroll_tax_band_id`,`payroll_deduction_type_id`),
  KEY `payroll_band_deduction_rates_payroll_deduction_type_id_foreign` (`payroll_deduction_type_id`),
  CONSTRAINT `payroll_band_deduction_rates_payroll_deduction_type_id_foreign` FOREIGN KEY (`payroll_deduction_type_id`) REFERENCES `payroll_deduction_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payroll_band_deduction_rates_payroll_tax_band_id_foreign` FOREIGN KEY (`payroll_tax_band_id`) REFERENCES `payroll_tax_bands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `payroll_band_deduction_rates` (add only if missing)
CALL `tich_ensure_column`('payroll_band_deduction_rates', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('payroll_band_deduction_rates', 'payroll_tax_band_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('payroll_band_deduction_rates', 'payroll_deduction_type_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('payroll_band_deduction_rates', 'rate_percent', 'decimal(7,4) NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_band_deduction_rates', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_band_deduction_rates', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `payroll_band_deduction_rates` (add only if missing)
CALL `tich_ensure_index`('payroll_band_deduction_rates', 'payroll_band_deduction_rates_payroll_deduction_type_id_foreign', '`payroll_deduction_type_id`');
CALL `tich_ensure_unique`('payroll_band_deduction_rates', 'payroll_band_deduction_unique', '`payroll_tax_band_id`, `payroll_deduction_type_id`');

-- -----------------------------------------------------------------------------
-- Table: `payroll_deduction_types`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payroll_deduction_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `label` varchar(120) NOT NULL,
  `value_type` enum('band_percent','global_fixed','withholding_percent') NOT NULL DEFAULT 'band_percent',
  `fixed_amount` decimal(12,2) DEFAULT NULL,
  `employer_rate_percent` decimal(7,4) DEFAULT NULL,
  `reduces_taxable` tinyint(4) NOT NULL DEFAULT 0,
  `display_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payroll_deduction_types_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `payroll_deduction_types` (add only if missing)
CALL `tich_ensure_column`('payroll_deduction_types', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('payroll_deduction_types', 'code', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('payroll_deduction_types', 'label', 'varchar(120) NOT NULL');
CALL `tich_ensure_column`('payroll_deduction_types', 'value_type', 'enum(\'band_percent\',\'global_fixed\',\'withholding_percent\') NOT NULL DEFAULT \'\\\'band_percent\\\'\'');
CALL `tich_ensure_column`('payroll_deduction_types', 'fixed_amount', 'decimal(12,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_deduction_types', 'employer_rate_percent', 'decimal(7,4) NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_deduction_types', 'reduces_taxable', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('payroll_deduction_types', 'display_order', 'smallint(5) unsigned NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('payroll_deduction_types', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('payroll_deduction_types', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_deduction_types', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `payroll_deduction_types` (add only if missing)
CALL `tich_ensure_unique`('payroll_deduction_types', 'payroll_deduction_types_code_unique', '`code`');

-- -----------------------------------------------------------------------------
-- Table: `payroll_items`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payroll_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payroll_run_id` bigint(20) unsigned DEFAULT NULL,
  `payslip_number` varchar(50) NOT NULL,
  `staff_id` bigint(20) unsigned NOT NULL,
  `pay_period_year` int(11) NOT NULL,
  `pay_period_month` int(11) NOT NULL,
  `basic_salary` decimal(12,2) NOT NULL,
  `gross_salary` decimal(12,2) NOT NULL,
  `total_allowances` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_deductions` decimal(12,2) NOT NULL DEFAULT 0.00,
  `net_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `calculation_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`calculation_snapshot`)),
  `is_processed` tinyint(4) NOT NULL DEFAULT 0,
  `processed_by` bigint(20) unsigned DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `is_approved` tinyint(4) NOT NULL DEFAULT 0,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `is_disbursed` tinyint(4) NOT NULL DEFAULT 0,
  `disbursement_date` date DEFAULT NULL,
  `eft_reference` varchar(100) DEFAULT NULL,
  `bank_transaction_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `payroll_items_payslip_number_unique` (`payslip_number`),
  KEY `payroll_items_processed_by_foreign` (`processed_by`),
  KEY `payroll_items_approved_by_foreign` (`approved_by`),
  KEY `payroll_items_staff_id_pay_period_year_pay_period_month_index` (`staff_id`,`pay_period_year`,`pay_period_month`),
  KEY `payroll_items_bank_transaction_id_foreign` (`bank_transaction_id`),
  KEY `payroll_items_payroll_run_id_foreign` (`payroll_run_id`),
  CONSTRAINT `payroll_items_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_items_bank_transaction_id_foreign` FOREIGN KEY (`bank_transaction_id`) REFERENCES `bank_transactions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_items_payroll_run_id_foreign` FOREIGN KEY (`payroll_run_id`) REFERENCES `payroll_runs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payroll_items_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_items_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `payroll_items` (add only if missing)
CALL `tich_ensure_column`('payroll_items', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('payroll_items', 'payroll_run_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_items', 'payslip_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('payroll_items', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('payroll_items', 'pay_period_year', 'int(11) NOT NULL');
CALL `tich_ensure_column`('payroll_items', 'pay_period_month', 'int(11) NOT NULL');
CALL `tich_ensure_column`('payroll_items', 'basic_salary', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('payroll_items', 'gross_salary', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('payroll_items', 'total_allowances', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('payroll_items', 'total_deductions', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('payroll_items', 'net_salary', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('payroll_items', 'calculation_snapshot', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_items', 'is_processed', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('payroll_items', 'processed_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_items', 'processed_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_items', 'is_approved', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('payroll_items', 'approved_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_items', 'approved_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_items', 'is_disbursed', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('payroll_items', 'disbursement_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_items', 'eft_reference', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_items', 'bank_transaction_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_items', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `payroll_items` (add only if missing)
CALL `tich_ensure_index`('payroll_items', 'payroll_items_approved_by_foreign', '`approved_by`');
CALL `tich_ensure_index`('payroll_items', 'payroll_items_bank_transaction_id_foreign', '`bank_transaction_id`');
CALL `tich_ensure_index`('payroll_items', 'payroll_items_payroll_run_id_foreign', '`payroll_run_id`');
CALL `tich_ensure_unique`('payroll_items', 'payroll_items_payslip_number_unique', '`payslip_number`');
CALL `tich_ensure_index`('payroll_items', 'payroll_items_processed_by_foreign', '`processed_by`');
CALL `tich_ensure_index`('payroll_items', 'payroll_items_staff_id_pay_period_year_pay_period_month_index', '`staff_id`, `pay_period_year`, `pay_period_month`');

-- -----------------------------------------------------------------------------
-- Table: `payroll_runs`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payroll_runs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `run_number` varchar(50) NOT NULL,
  `pay_period_year` smallint(5) unsigned NOT NULL,
  `pay_period_month` tinyint(3) unsigned NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `staff_count` int(10) unsigned NOT NULL DEFAULT 0,
  `total_gross` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_deductions` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_net` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_paye` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_nssf` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_sha` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_ahl` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_employer_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `posted_by` bigint(20) unsigned DEFAULT NULL,
  `posted_at` datetime DEFAULT NULL,
  `gl_reference` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payroll_runs_run_number_unique` (`run_number`),
  KEY `payroll_runs_created_by_foreign` (`created_by`),
  KEY `payroll_runs_approved_by_foreign` (`approved_by`),
  KEY `payroll_runs_posted_by_foreign` (`posted_by`),
  KEY `payroll_runs_status_index` (`status`),
  KEY `payroll_runs_period_index` (`pay_period_year`,`pay_period_month`),
  CONSTRAINT `payroll_runs_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_runs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_runs_posted_by_foreign` FOREIGN KEY (`posted_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `payroll_runs` (add only if missing)
CALL `tich_ensure_column`('payroll_runs', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('payroll_runs', 'run_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('payroll_runs', 'pay_period_year', 'smallint(5) unsigned NOT NULL');
CALL `tich_ensure_column`('payroll_runs', 'pay_period_month', 'tinyint(3) unsigned NOT NULL');
CALL `tich_ensure_column`('payroll_runs', 'status', 'varchar(30) NOT NULL DEFAULT \'\\\'draft\\\'\'');
CALL `tich_ensure_column`('payroll_runs', 'staff_count', 'int(10) unsigned NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('payroll_runs', 'total_gross', 'decimal(14,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('payroll_runs', 'total_deductions', 'decimal(14,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('payroll_runs', 'total_net', 'decimal(14,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('payroll_runs', 'total_paye', 'decimal(14,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('payroll_runs', 'total_nssf', 'decimal(14,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('payroll_runs', 'total_sha', 'decimal(14,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('payroll_runs', 'total_ahl', 'decimal(14,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('payroll_runs', 'total_employer_cost', 'decimal(14,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('payroll_runs', 'notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_runs', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_runs', 'approved_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_runs', 'approved_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_runs', 'posted_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_runs', 'posted_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_runs', 'gl_reference', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_runs', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('payroll_runs', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `payroll_runs` (add only if missing)
CALL `tich_ensure_index`('payroll_runs', 'payroll_runs_approved_by_foreign', '`approved_by`');
CALL `tich_ensure_index`('payroll_runs', 'payroll_runs_created_by_foreign', '`created_by`');
CALL `tich_ensure_index`('payroll_runs', 'payroll_runs_period_index', '`pay_period_year`, `pay_period_month`');
CALL `tich_ensure_index`('payroll_runs', 'payroll_runs_posted_by_foreign', '`posted_by`');
CALL `tich_ensure_unique`('payroll_runs', 'payroll_runs_run_number_unique', '`run_number`');
CALL `tich_ensure_index`('payroll_runs', 'payroll_runs_status_index', '`status`');

-- -----------------------------------------------------------------------------
-- Table: `payroll_statutory_rates`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payroll_statutory_rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `label` varchar(120) NOT NULL,
  `rate_percent` decimal(7,4) DEFAULT NULL,
  `employer_rate_percent` decimal(7,4) DEFAULT NULL,
  `fixed_amount` decimal(12,2) DEFAULT NULL,
  `floor_amount` decimal(12,2) DEFAULT NULL,
  `ceiling_amount` decimal(12,2) DEFAULT NULL,
  `applies_to` varchar(30) NOT NULL DEFAULT 'gross',
  `notes` text DEFAULT NULL,
  `display_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payroll_statutory_rates_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `payroll_statutory_rates` (add only if missing)
CALL `tich_ensure_column`('payroll_statutory_rates', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('payroll_statutory_rates', 'code', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('payroll_statutory_rates', 'label', 'varchar(120) NOT NULL');
CALL `tich_ensure_column`('payroll_statutory_rates', 'rate_percent', 'decimal(7,4) NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_statutory_rates', 'employer_rate_percent', 'decimal(7,4) NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_statutory_rates', 'fixed_amount', 'decimal(12,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_statutory_rates', 'floor_amount', 'decimal(12,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_statutory_rates', 'ceiling_amount', 'decimal(12,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_statutory_rates', 'applies_to', 'varchar(30) NOT NULL DEFAULT \'\\\'gross\\\'\'');
CALL `tich_ensure_column`('payroll_statutory_rates', 'notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_statutory_rates', 'display_order', 'smallint(5) unsigned NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('payroll_statutory_rates', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('payroll_statutory_rates', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_statutory_rates', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `payroll_statutory_rates` (add only if missing)
CALL `tich_ensure_unique`('payroll_statutory_rates', 'payroll_statutory_rates_code_unique', '`code`');

-- -----------------------------------------------------------------------------
-- Table: `payroll_tax_bands`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payroll_tax_bands` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(120) NOT NULL,
  `min_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `max_amount` decimal(12,2) DEFAULT NULL,
  `rate_percent` decimal(5,2) NOT NULL,
  `display_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `effective_from` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `payroll_tax_bands` (add only if missing)
CALL `tich_ensure_column`('payroll_tax_bands', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('payroll_tax_bands', 'label', 'varchar(120) NOT NULL');
CALL `tich_ensure_column`('payroll_tax_bands', 'min_amount', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('payroll_tax_bands', 'max_amount', 'decimal(12,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_tax_bands', 'rate_percent', 'decimal(5,2) NOT NULL');
CALL `tich_ensure_column`('payroll_tax_bands', 'display_order', 'smallint(5) unsigned NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('payroll_tax_bands', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('payroll_tax_bands', 'effective_from', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_tax_bands', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('payroll_tax_bands', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `payroll_tax_bands` (add only if missing)
-- (no secondary indexes)

-- -----------------------------------------------------------------------------
-- Table: `pension_schemes`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pension_schemes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `scheme_code` varchar(20) NOT NULL,
  `scheme_name` varchar(300) NOT NULL,
  `scheme_type` varchar(50) NOT NULL,
  `employer_contribution_pct` decimal(5,2) NOT NULL DEFAULT 0.00,
  `employee_contribution_pct` decimal(5,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `pension_schemes_scheme_code_unique` (`scheme_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `pension_schemes` (add only if missing)
CALL `tich_ensure_column`('pension_schemes', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('pension_schemes', 'scheme_code', 'varchar(20) NOT NULL');
CALL `tich_ensure_column`('pension_schemes', 'scheme_name', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('pension_schemes', 'scheme_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('pension_schemes', 'employer_contribution_pct', 'decimal(5,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('pension_schemes', 'employee_contribution_pct', 'decimal(5,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('pension_schemes', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('pension_schemes', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `pension_schemes` (add only if missing)
CALL `tich_ensure_unique`('pension_schemes', 'pension_schemes_scheme_code_unique', '`scheme_code`');

-- -----------------------------------------------------------------------------
-- Table: `performance_reviews`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `performance_reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `review_number` varchar(50) NOT NULL,
  `staff_id` bigint(20) unsigned NOT NULL,
  `reviewer_id` bigint(20) unsigned NOT NULL,
  `review_period_start` date NOT NULL,
  `review_period_end` date NOT NULL,
  `review_date` date NOT NULL,
  `kpi_scores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`kpi_scores`)),
  `overall_rating` varchar(50) NOT NULL,
  `strengths` text DEFAULT NULL,
  `development_areas` text DEFAULT NULL,
  `training_recommendations` text DEFAULT NULL,
  `goals_for_next_period` text DEFAULT NULL,
  `staff_comments` text DEFAULT NULL,
  `staff_agrees` tinyint(4) NOT NULL DEFAULT 0,
  `staff_signed_at` datetime DEFAULT NULL,
  `reviewer_signed_at` datetime DEFAULT NULL,
  `hr_approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `performance_reviews_review_number_unique` (`review_number`),
  KEY `performance_reviews_staff_id_index` (`staff_id`),
  KEY `performance_reviews_reviewer_id_index` (`reviewer_id`),
  CONSTRAINT `performance_reviews_reviewer_id_foreign` FOREIGN KEY (`reviewer_id`) REFERENCES `staff` (`id`),
  CONSTRAINT `performance_reviews_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `performance_reviews` (add only if missing)
CALL `tich_ensure_column`('performance_reviews', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('performance_reviews', 'review_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('performance_reviews', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('performance_reviews', 'reviewer_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('performance_reviews', 'review_period_start', 'date NOT NULL');
CALL `tich_ensure_column`('performance_reviews', 'review_period_end', 'date NOT NULL');
CALL `tich_ensure_column`('performance_reviews', 'review_date', 'date NOT NULL');
CALL `tich_ensure_column`('performance_reviews', 'kpi_scores', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('performance_reviews', 'overall_rating', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('performance_reviews', 'strengths', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('performance_reviews', 'development_areas', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('performance_reviews', 'training_recommendations', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('performance_reviews', 'goals_for_next_period', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('performance_reviews', 'staff_comments', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('performance_reviews', 'staff_agrees', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('performance_reviews', 'staff_signed_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('performance_reviews', 'reviewer_signed_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('performance_reviews', 'hr_approved_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('performance_reviews', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `performance_reviews` (add only if missing)
CALL `tich_ensure_index`('performance_reviews', 'performance_reviews_reviewer_id_index', '`reviewer_id`');
CALL `tich_ensure_unique`('performance_reviews', 'performance_reviews_review_number_unique', '`review_number`');
CALL `tich_ensure_index`('performance_reviews', 'performance_reviews_staff_id_index', '`staff_id`');

-- -----------------------------------------------------------------------------
-- Table: `personal_access_tokens`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `personal_access_tokens` (add only if missing)
CALL `tich_ensure_column`('personal_access_tokens', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('personal_access_tokens', 'tokenable_type', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('personal_access_tokens', 'tokenable_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('personal_access_tokens', 'name', 'text NOT NULL');
CALL `tich_ensure_column`('personal_access_tokens', 'token', 'varchar(64) NOT NULL');
CALL `tich_ensure_column`('personal_access_tokens', 'abilities', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('personal_access_tokens', 'last_used_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('personal_access_tokens', 'expires_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('personal_access_tokens', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('personal_access_tokens', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `personal_access_tokens` (add only if missing)
CALL `tich_ensure_index`('personal_access_tokens', 'personal_access_tokens_expires_at_index', '`expires_at`');
CALL `tich_ensure_index`('personal_access_tokens', 'personal_access_tokens_tokenable_type_tokenable_id_index', '`tokenable_type`, `tokenable_id`');
CALL `tich_ensure_unique`('personal_access_tokens', 'personal_access_tokens_token_unique', '`token`');

-- -----------------------------------------------------------------------------
-- Table: `policy_acknowledgements`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `policy_acknowledgements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `policy_id` bigint(20) unsigned DEFAULT NULL,
  `policy_name` varchar(200) NOT NULL,
  `policy_version` varchar(20) NOT NULL,
  `policy_file_path` varchar(500) NOT NULL,
  `effective_date` date NOT NULL,
  `staff_id` bigint(20) unsigned NOT NULL,
  `is_acknowledged` tinyint(4) NOT NULL DEFAULT 0,
  `acknowledged_at` datetime DEFAULT NULL,
  `acknowledged_by` varchar(200) DEFAULT NULL,
  `employee_number` varchar(50) DEFAULT NULL,
  `signature` text DEFAULT NULL,
  `acknowledgement_method` varchar(50) NOT NULL DEFAULT 'digital',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `policy_ack_unique` (`staff_id`,`policy_name`,`policy_version`),
  KEY `policy_acknowledgements_policy_id_foreign` (`policy_id`),
  CONSTRAINT `policy_acknowledgements_policy_id_foreign` FOREIGN KEY (`policy_id`) REFERENCES `hr_policies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `policy_acknowledgements_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `policy_acknowledgements` (add only if missing)
CALL `tich_ensure_column`('policy_acknowledgements', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('policy_acknowledgements', 'policy_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('policy_acknowledgements', 'policy_name', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('policy_acknowledgements', 'policy_version', 'varchar(20) NOT NULL');
CALL `tich_ensure_column`('policy_acknowledgements', 'policy_file_path', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('policy_acknowledgements', 'effective_date', 'date NOT NULL');
CALL `tich_ensure_column`('policy_acknowledgements', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('policy_acknowledgements', 'is_acknowledged', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('policy_acknowledgements', 'acknowledged_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('policy_acknowledgements', 'acknowledged_by', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('policy_acknowledgements', 'employee_number', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('policy_acknowledgements', 'signature', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('policy_acknowledgements', 'acknowledgement_method', 'varchar(50) NOT NULL DEFAULT \'\\\'digital\\\'\'');
CALL `tich_ensure_column`('policy_acknowledgements', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('policy_acknowledgements', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `policy_acknowledgements` (add only if missing)
CALL `tich_ensure_index`('policy_acknowledgements', 'policy_acknowledgements_policy_id_foreign', '`policy_id`');
CALL `tich_ensure_unique`('policy_acknowledgements', 'policy_ack_unique', '`staff_id`, `policy_name`, `policy_version`');

-- -----------------------------------------------------------------------------
-- Table: `procurement_requisitions`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `procurement_requisitions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `requisition_number` varchar(50) NOT NULL,
  `requesting_department_id` bigint(20) unsigned NOT NULL,
  `requested_by` bigint(20) unsigned NOT NULL,
  `request_date` date NOT NULL,
  `justification` text NOT NULL,
  `estimated_cost` decimal(12,2) NOT NULL,
  `budget_code` varchar(50) DEFAULT NULL,
  `delivery_required_by` date DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `hod_approval_status` varchar(50) NOT NULL DEFAULT 'pending',
  `hod_approved_by` bigint(20) unsigned DEFAULT NULL,
  `hod_approved_at` datetime DEFAULT NULL,
  `finance_approval_status` varchar(50) NOT NULL DEFAULT 'pending',
  `finance_approved_by` bigint(20) unsigned DEFAULT NULL,
  `finance_approved_at` datetime DEFAULT NULL,
  `ceo_approval_status` varchar(50) NOT NULL DEFAULT 'pending',
  `ceo_approved_by` bigint(20) unsigned DEFAULT NULL,
  `ceo_approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `procurement_requisitions_requisition_number_unique` (`requisition_number`),
  KEY `procurement_requisitions_requesting_department_id_foreign` (`requesting_department_id`),
  KEY `procurement_requisitions_requested_by_foreign` (`requested_by`),
  KEY `procurement_requisitions_hod_approved_by_foreign` (`hod_approved_by`),
  KEY `procurement_requisitions_finance_approved_by_foreign` (`finance_approved_by`),
  KEY `procurement_requisitions_ceo_approved_by_foreign` (`ceo_approved_by`),
  CONSTRAINT `procurement_requisitions_ceo_approved_by_foreign` FOREIGN KEY (`ceo_approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procurement_requisitions_finance_approved_by_foreign` FOREIGN KEY (`finance_approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procurement_requisitions_hod_approved_by_foreign` FOREIGN KEY (`hod_approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procurement_requisitions_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `procurement_requisitions_requesting_department_id_foreign` FOREIGN KEY (`requesting_department_id`) REFERENCES `departments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `procurement_requisitions` (add only if missing)
CALL `tich_ensure_column`('procurement_requisitions', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('procurement_requisitions', 'requisition_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('procurement_requisitions', 'requesting_department_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('procurement_requisitions', 'requested_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('procurement_requisitions', 'request_date', 'date NOT NULL');
CALL `tich_ensure_column`('procurement_requisitions', 'justification', 'text NOT NULL');
CALL `tich_ensure_column`('procurement_requisitions', 'estimated_cost', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('procurement_requisitions', 'budget_code', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('procurement_requisitions', 'delivery_required_by', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('procurement_requisitions', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'draft\\\'\'');
CALL `tich_ensure_column`('procurement_requisitions', 'hod_approval_status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('procurement_requisitions', 'hod_approved_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('procurement_requisitions', 'hod_approved_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('procurement_requisitions', 'finance_approval_status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('procurement_requisitions', 'finance_approved_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('procurement_requisitions', 'finance_approved_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('procurement_requisitions', 'ceo_approval_status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('procurement_requisitions', 'ceo_approved_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('procurement_requisitions', 'ceo_approved_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('procurement_requisitions', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `procurement_requisitions` (add only if missing)
CALL `tich_ensure_index`('procurement_requisitions', 'procurement_requisitions_ceo_approved_by_foreign', '`ceo_approved_by`');
CALL `tich_ensure_index`('procurement_requisitions', 'procurement_requisitions_finance_approved_by_foreign', '`finance_approved_by`');
CALL `tich_ensure_index`('procurement_requisitions', 'procurement_requisitions_hod_approved_by_foreign', '`hod_approved_by`');
CALL `tich_ensure_index`('procurement_requisitions', 'procurement_requisitions_requested_by_foreign', '`requested_by`');
CALL `tich_ensure_index`('procurement_requisitions', 'procurement_requisitions_requesting_department_id_foreign', '`requesting_department_id`');
CALL `tich_ensure_unique`('procurement_requisitions', 'procurement_requisitions_requisition_number_unique', '`requisition_number`');

-- -----------------------------------------------------------------------------
-- Table: `professional_development`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `professional_development` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned NOT NULL,
  `staff_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`staff_ids`)),
  `activity_type` varchar(50) NOT NULL,
  `activity_name` varchar(300) NOT NULL,
  `organizer` varchar(300) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `hours_or_days` decimal(6,2) NOT NULL,
  `cpd_credits_earned` decimal(5,2) NOT NULL DEFAULT 0.00,
  `location` varchar(300) DEFAULT NULL,
  `is_external` tinyint(4) NOT NULL DEFAULT 0,
  `cost` decimal(12,2) DEFAULT NULL,
  `funded_by` varchar(50) DEFAULT NULL,
  `certificate_path` varchar(500) DEFAULT NULL,
  `is_completed` tinyint(4) NOT NULL DEFAULT 0,
  `appraisal_relevance` varchar(500) DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `professional_development_staff_id_foreign` (`staff_id`),
  KEY `professional_development_approved_by_foreign` (`approved_by`),
  CONSTRAINT `professional_development_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `professional_development_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `professional_development` (add only if missing)
CALL `tich_ensure_column`('professional_development', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('professional_development', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('professional_development', 'staff_ids', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('professional_development', 'activity_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('professional_development', 'activity_name', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('professional_development', 'organizer', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('professional_development', 'start_date', 'date NOT NULL');
CALL `tich_ensure_column`('professional_development', 'end_date', 'date NOT NULL');
CALL `tich_ensure_column`('professional_development', 'hours_or_days', 'decimal(6,2) NOT NULL');
CALL `tich_ensure_column`('professional_development', 'cpd_credits_earned', 'decimal(5,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('professional_development', 'location', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('professional_development', 'is_external', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('professional_development', 'cost', 'decimal(12,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('professional_development', 'funded_by', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('professional_development', 'certificate_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('professional_development', 'is_completed', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('professional_development', 'appraisal_relevance', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('professional_development', 'approved_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('professional_development', 'approved_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('professional_development', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `professional_development` (add only if missing)
CALL `tich_ensure_index`('professional_development', 'professional_development_approved_by_foreign', '`approved_by`');
CALL `tich_ensure_index`('professional_development', 'professional_development_staff_id_foreign', '`staff_id`');

-- -----------------------------------------------------------------------------
-- Table: `program_timetables`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `program_timetables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `program_id` bigint(20) unsigned NOT NULL,
  `curriculum_version_id` bigint(20) unsigned DEFAULT NULL,
  `teaching_period` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `title` varchar(200) DEFAULT NULL,
  `timetable_kind` varchar(50) NOT NULL DEFAULT 'lesson',
  `template_id` bigint(20) unsigned DEFAULT NULL,
  `campus_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `generation_notes` text DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `published_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `program_timetables_curriculum_version_id_foreign` (`curriculum_version_id`),
  KEY `program_timetables_template_id_foreign` (`template_id`),
  KEY `program_timetables_campus_id_foreign` (`campus_id`),
  KEY `pt_scope_idx` (`program_id`,`curriculum_version_id`,`teaching_period`),
  CONSTRAINT `program_timetables_campus_id_foreign` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `program_timetables_curriculum_version_id_foreign` FOREIGN KEY (`curriculum_version_id`) REFERENCES `curriculum_versions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `program_timetables_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `academic_programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `program_timetables_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `program_timetable_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `program_timetables` (add only if missing)
CALL `tich_ensure_column`('program_timetables', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('program_timetables', 'program_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('program_timetables', 'curriculum_version_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('program_timetables', 'teaching_period', 'tinyint(3) unsigned NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('program_timetables', 'title', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('program_timetables', 'timetable_kind', 'varchar(50) NOT NULL DEFAULT \'\\\'lesson\\\'\'');
CALL `tich_ensure_column`('program_timetables', 'template_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('program_timetables', 'campus_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('program_timetables', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'draft\\\'\'');
CALL `tich_ensure_column`('program_timetables', 'generation_notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('program_timetables', 'published_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('program_timetables', 'published_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('program_timetables', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('program_timetables', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `program_timetables` (add only if missing)
CALL `tich_ensure_index`('program_timetables', 'program_timetables_campus_id_foreign', '`campus_id`');
CALL `tich_ensure_index`('program_timetables', 'program_timetables_curriculum_version_id_foreign', '`curriculum_version_id`');
CALL `tich_ensure_index`('program_timetables', 'program_timetables_template_id_foreign', '`template_id`');
CALL `tich_ensure_index`('program_timetables', 'pt_scope_idx', '`program_id`, `curriculum_version_id`, `teaching_period`');

-- -----------------------------------------------------------------------------
-- Table: `program_timetable_segments`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `program_timetable_segments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint(20) unsigned NOT NULL,
  `label` varchar(120) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `segment_type` varchar(50) NOT NULL DEFAULT 'lesson',
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `program_timetable_segments_template_id_foreign` (`template_id`),
  CONSTRAINT `program_timetable_segments_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `program_timetable_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `program_timetable_segments` (add only if missing)
CALL `tich_ensure_column`('program_timetable_segments', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('program_timetable_segments', 'template_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('program_timetable_segments', 'label', 'varchar(120) NOT NULL');
CALL `tich_ensure_column`('program_timetable_segments', 'start_time', 'time NOT NULL');
CALL `tich_ensure_column`('program_timetable_segments', 'end_time', 'time NOT NULL');
CALL `tich_ensure_column`('program_timetable_segments', 'segment_type', 'varchar(50) NOT NULL DEFAULT \'\\\'lesson\\\'\'');
CALL `tich_ensure_column`('program_timetable_segments', 'sort_order', 'smallint(5) unsigned NOT NULL DEFAULT \'0\'');

-- Indexes for `program_timetable_segments` (add only if missing)
CALL `tich_ensure_index`('program_timetable_segments', 'program_timetable_segments_template_id_foreign', '`template_id`');

-- -----------------------------------------------------------------------------
-- Table: `program_timetable_sessions`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `program_timetable_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `program_timetable_id` bigint(20) unsigned NOT NULL,
  `unit_id` bigint(20) unsigned DEFAULT NULL,
  `staff_id` bigint(20) unsigned DEFAULT NULL,
  `room_id` bigint(20) unsigned DEFAULT NULL,
  `day_of_week` tinyint(3) unsigned NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `session_type` varchar(50) NOT NULL DEFAULT 'lesson',
  `title` varchar(200) DEFAULT NULL,
  `venue` varchar(200) DEFAULT NULL,
  `class_group` varchar(100) DEFAULT NULL,
  `segment_id` bigint(20) unsigned DEFAULT NULL,
  `lesson_plan_cleared` tinyint(1) NOT NULL DEFAULT 0,
  `lesson_plan_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `program_timetable_sessions_unit_id_foreign` (`unit_id`),
  KEY `program_timetable_sessions_staff_id_foreign` (`staff_id`),
  KEY `program_timetable_sessions_room_id_foreign` (`room_id`),
  KEY `program_timetable_sessions_segment_id_foreign` (`segment_id`),
  KEY `pts_day_idx` (`program_timetable_id`,`day_of_week`),
  KEY `program_timetable_sessions_lesson_plan_id_foreign` (`lesson_plan_id`),
  CONSTRAINT `program_timetable_sessions_lesson_plan_id_foreign` FOREIGN KEY (`lesson_plan_id`) REFERENCES `lesson_plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `program_timetable_sessions_program_timetable_id_foreign` FOREIGN KEY (`program_timetable_id`) REFERENCES `program_timetables` (`id`) ON DELETE CASCADE,
  CONSTRAINT `program_timetable_sessions_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `program_timetable_sessions_segment_id_foreign` FOREIGN KEY (`segment_id`) REFERENCES `program_timetable_segments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `program_timetable_sessions_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `program_timetable_sessions_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `program_timetable_sessions` (add only if missing)
CALL `tich_ensure_column`('program_timetable_sessions', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('program_timetable_sessions', 'program_timetable_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('program_timetable_sessions', 'unit_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('program_timetable_sessions', 'staff_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('program_timetable_sessions', 'room_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('program_timetable_sessions', 'day_of_week', 'tinyint(3) unsigned NOT NULL');
CALL `tich_ensure_column`('program_timetable_sessions', 'start_time', 'time NOT NULL');
CALL `tich_ensure_column`('program_timetable_sessions', 'end_time', 'time NOT NULL');
CALL `tich_ensure_column`('program_timetable_sessions', 'session_type', 'varchar(50) NOT NULL DEFAULT \'\\\'lesson\\\'\'');
CALL `tich_ensure_column`('program_timetable_sessions', 'title', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('program_timetable_sessions', 'venue', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('program_timetable_sessions', 'class_group', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('program_timetable_sessions', 'segment_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('program_timetable_sessions', 'lesson_plan_cleared', 'tinyint(1) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('program_timetable_sessions', 'lesson_plan_id', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `program_timetable_sessions` (add only if missing)
CALL `tich_ensure_index`('program_timetable_sessions', 'program_timetable_sessions_lesson_plan_id_foreign', '`lesson_plan_id`');
CALL `tich_ensure_index`('program_timetable_sessions', 'program_timetable_sessions_room_id_foreign', '`room_id`');
CALL `tich_ensure_index`('program_timetable_sessions', 'program_timetable_sessions_segment_id_foreign', '`segment_id`');
CALL `tich_ensure_index`('program_timetable_sessions', 'program_timetable_sessions_staff_id_foreign', '`staff_id`');
CALL `tich_ensure_index`('program_timetable_sessions', 'program_timetable_sessions_unit_id_foreign', '`unit_id`');
CALL `tich_ensure_index`('program_timetable_sessions', 'pts_day_idx', '`program_timetable_id`, `day_of_week`');

-- -----------------------------------------------------------------------------
-- Table: `program_timetable_templates`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `program_timetable_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `program_id` bigint(20) unsigned NOT NULL,
  `name` varchar(120) NOT NULL DEFAULT 'Default bell schedule',
  `is_default` tinyint(4) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `program_timetable_templates_program_id_foreign` (`program_id`),
  CONSTRAINT `program_timetable_templates_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `academic_programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `program_timetable_templates` (add only if missing)
CALL `tich_ensure_column`('program_timetable_templates', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('program_timetable_templates', 'program_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('program_timetable_templates', 'name', 'varchar(120) NOT NULL DEFAULT \'\\\'Default bell schedule\\\'\'');
CALL `tich_ensure_column`('program_timetable_templates', 'is_default', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('program_timetable_templates', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('program_timetable_templates', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('program_timetable_templates', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `program_timetable_templates` (add only if missing)
CALL `tich_ensure_index`('program_timetable_templates', 'program_timetable_templates_program_id_foreign', '`program_id`');

-- -----------------------------------------------------------------------------
-- Table: `program_timetable_template_days`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `program_timetable_template_days` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint(20) unsigned NOT NULL,
  `day_of_week` tinyint(3) unsigned NOT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ptt_day_unique` (`template_id`,`day_of_week`),
  CONSTRAINT `program_timetable_template_days_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `program_timetable_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `program_timetable_template_days` (add only if missing)
CALL `tich_ensure_column`('program_timetable_template_days', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('program_timetable_template_days', 'template_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('program_timetable_template_days', 'day_of_week', 'tinyint(3) unsigned NOT NULL');
CALL `tich_ensure_column`('program_timetable_template_days', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');

-- Indexes for `program_timetable_template_days` (add only if missing)
CALL `tich_ensure_unique`('program_timetable_template_days', 'ptt_day_unique', '`template_id`, `day_of_week`');

-- -----------------------------------------------------------------------------
-- Table: `program_units`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `program_units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `program_id` bigint(20) unsigned NOT NULL,
  `unit_id` bigint(20) unsigned NOT NULL,
  `semester` int(11) NOT NULL,
  `block_id` bigint(20) unsigned DEFAULT NULL,
  `is_compulsory` tinyint(4) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `priority` int(11) NOT NULL DEFAULT 0,
  `contact_hours` int(11) NOT NULL DEFAULT 0,
  `total_learning_hours` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prog_unit_unique` (`program_id`,`unit_id`),
  KEY `program_units_unit_id_foreign` (`unit_id`),
  KEY `program_units_block_id_foreign` (`block_id`),
  CONSTRAINT `program_units_block_id_foreign` FOREIGN KEY (`block_id`) REFERENCES `nursing_blocks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `program_units_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `academic_programs` (`id`),
  CONSTRAINT `program_units_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `program_units` (add only if missing)
CALL `tich_ensure_column`('program_units', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('program_units', 'program_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('program_units', 'unit_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('program_units', 'semester', 'int(11) NOT NULL');
CALL `tich_ensure_column`('program_units', 'block_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('program_units', 'is_compulsory', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('program_units', 'display_order', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('program_units', 'priority', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('program_units', 'contact_hours', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('program_units', 'total_learning_hours', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('program_units', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');

-- Indexes for `program_units` (add only if missing)
CALL `tich_ensure_index`('program_units', 'program_units_block_id_foreign', '`block_id`');
CALL `tich_ensure_index`('program_units', 'program_units_unit_id_foreign', '`unit_id`');
CALL `tich_ensure_unique`('program_units', 'prog_unit_unique', '`program_id`, `unit_id`');

-- -----------------------------------------------------------------------------
-- Table: `public_holidays`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `public_holidays` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `holiday_date` date NOT NULL,
  `holiday_name` varchar(200) NOT NULL,
  `holiday_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `public_holidays_holiday_date_unique` (`holiday_date`),
  KEY `public_holidays_holiday_date_index` (`holiday_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `public_holidays` (add only if missing)
CALL `tich_ensure_column`('public_holidays', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('public_holidays', 'holiday_date', 'date NOT NULL');
CALL `tich_ensure_column`('public_holidays', 'holiday_name', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('public_holidays', 'holiday_type', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('public_holidays', 'description', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('public_holidays', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('public_holidays', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('public_holidays', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `public_holidays` (add only if missing)
CALL `tich_ensure_index`('public_holidays', 'public_holidays_holiday_date_index', '`holiday_date`');
CALL `tich_ensure_unique`('public_holidays', 'public_holidays_holiday_date_unique', '`holiday_date`');

-- -----------------------------------------------------------------------------
-- Table: `purchase_orders`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `po_number` varchar(50) NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `requisition_id` bigint(20) unsigned NOT NULL,
  `issue_date` date NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `terms` varchar(500) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'issued',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_orders_po_number_unique` (`po_number`),
  KEY `purchase_orders_supplier_id_foreign` (`supplier_id`),
  KEY `purchase_orders_requisition_id_foreign` (`requisition_id`),
  CONSTRAINT `purchase_orders_requisition_id_foreign` FOREIGN KEY (`requisition_id`) REFERENCES `procurement_requisitions` (`id`),
  CONSTRAINT `purchase_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `purchase_orders` (add only if missing)
CALL `tich_ensure_column`('purchase_orders', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('purchase_orders', 'po_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('purchase_orders', 'supplier_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('purchase_orders', 'requisition_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('purchase_orders', 'issue_date', 'date NOT NULL');
CALL `tich_ensure_column`('purchase_orders', 'delivery_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('purchase_orders', 'total_amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('purchase_orders', 'terms', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('purchase_orders', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'issued\\\'\'');
CALL `tich_ensure_column`('purchase_orders', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `purchase_orders` (add only if missing)
CALL `tich_ensure_unique`('purchase_orders', 'purchase_orders_po_number_unique', '`po_number`');
CALL `tich_ensure_index`('purchase_orders', 'purchase_orders_requisition_id_foreign', '`requisition_id`');
CALL `tich_ensure_index`('purchase_orders', 'purchase_orders_supplier_id_foreign', '`supplier_id`');

-- -----------------------------------------------------------------------------
-- Table: `qa_audit_checklists`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qa_audit_checklists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `qa_plan_id` bigint(20) unsigned NOT NULL,
  `checklist_item_text` text NOT NULL,
  `item_category` varchar(100) DEFAULT NULL,
  `weight` decimal(5,2) NOT NULL DEFAULT 1.00,
  `max_score` decimal(5,2) NOT NULL DEFAULT 100.00,
  `applies_to_department_id` bigint(20) unsigned DEFAULT NULL,
  `requires_evidence` tinyint(4) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `qa_audit_checklists_qa_plan_id_foreign` (`qa_plan_id`),
  KEY `qa_audit_checklists_applies_to_department_id_foreign` (`applies_to_department_id`),
  CONSTRAINT `qa_audit_checklists_applies_to_department_id_foreign` FOREIGN KEY (`applies_to_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `qa_audit_checklists_qa_plan_id_foreign` FOREIGN KEY (`qa_plan_id`) REFERENCES `qa_plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `qa_audit_checklists` (add only if missing)
CALL `tich_ensure_column`('qa_audit_checklists', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('qa_audit_checklists', 'qa_plan_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('qa_audit_checklists', 'checklist_item_text', 'text NOT NULL');
CALL `tich_ensure_column`('qa_audit_checklists', 'item_category', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('qa_audit_checklists', 'weight', 'decimal(5,2) NOT NULL DEFAULT \'1.00\'');
CALL `tich_ensure_column`('qa_audit_checklists', 'max_score', 'decimal(5,2) NOT NULL DEFAULT \'100.00\'');
CALL `tich_ensure_column`('qa_audit_checklists', 'applies_to_department_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('qa_audit_checklists', 'requires_evidence', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('qa_audit_checklists', 'display_order', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('qa_audit_checklists', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('qa_audit_checklists', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `qa_audit_checklists` (add only if missing)
CALL `tich_ensure_index`('qa_audit_checklists', 'qa_audit_checklists_applies_to_department_id_foreign', '`applies_to_department_id`');
CALL `tich_ensure_index`('qa_audit_checklists', 'qa_audit_checklists_qa_plan_id_foreign', '`qa_plan_id`');

-- -----------------------------------------------------------------------------
-- Table: `qa_compliance_scores`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qa_compliance_scores` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `qa_plan_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `total_items` int(11) NOT NULL DEFAULT 0,
  `items_submitted` int(11) NOT NULL DEFAULT 0,
  `weighted_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `pass_fail_status` varchar(50) NOT NULL DEFAULT 'pending',
  `is_below_threshold` tinyint(4) NOT NULL DEFAULT 0,
  `threshold_met_at` datetime DEFAULT NULL,
  `calculated_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `qa_compliance_scores_qa_plan_id_department_id_unique` (`qa_plan_id`,`department_id`),
  KEY `qa_compliance_scores_department_id_foreign` (`department_id`),
  CONSTRAINT `qa_compliance_scores_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `qa_compliance_scores_qa_plan_id_foreign` FOREIGN KEY (`qa_plan_id`) REFERENCES `qa_plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `qa_compliance_scores` (add only if missing)
CALL `tich_ensure_column`('qa_compliance_scores', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('qa_compliance_scores', 'qa_plan_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('qa_compliance_scores', 'department_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('qa_compliance_scores', 'total_items', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('qa_compliance_scores', 'items_submitted', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('qa_compliance_scores', 'weighted_score', 'decimal(5,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('qa_compliance_scores', 'pass_fail_status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('qa_compliance_scores', 'is_below_threshold', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('qa_compliance_scores', 'threshold_met_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('qa_compliance_scores', 'calculated_at', 'datetime NOT NULL');
CALL `tich_ensure_column`('qa_compliance_scores', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `qa_compliance_scores` (add only if missing)
CALL `tich_ensure_index`('qa_compliance_scores', 'qa_compliance_scores_department_id_foreign', '`department_id`');
CALL `tich_ensure_unique`('qa_compliance_scores', 'qa_compliance_scores_qa_plan_id_department_id_unique', '`qa_plan_id`, `department_id`');

-- -----------------------------------------------------------------------------
-- Table: `qa_corrective_actions`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qa_corrective_actions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `qa_plan_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `checklist_item_id` bigint(20) unsigned DEFAULT NULL,
  `flagged_reason` text NOT NULL,
  `compliance_score_at_flag` decimal(5,2) DEFAULT NULL,
  `resolution_deadline` date NOT NULL,
  `resolution_plan` text DEFAULT NULL,
  `responsible_officer_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'open',
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by` bigint(20) unsigned DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `is_module_lock_active` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `qa_corrective_actions_qa_plan_id_foreign` (`qa_plan_id`),
  KEY `qa_corrective_actions_department_id_foreign` (`department_id`),
  KEY `qa_corrective_actions_checklist_item_id_foreign` (`checklist_item_id`),
  KEY `qa_corrective_actions_responsible_officer_id_foreign` (`responsible_officer_id`),
  KEY `qa_corrective_actions_resolved_by_foreign` (`resolved_by`),
  CONSTRAINT `qa_corrective_actions_checklist_item_id_foreign` FOREIGN KEY (`checklist_item_id`) REFERENCES `qa_audit_checklists` (`id`) ON DELETE SET NULL,
  CONSTRAINT `qa_corrective_actions_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `qa_corrective_actions_qa_plan_id_foreign` FOREIGN KEY (`qa_plan_id`) REFERENCES `qa_plans` (`id`),
  CONSTRAINT `qa_corrective_actions_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `qa_corrective_actions_responsible_officer_id_foreign` FOREIGN KEY (`responsible_officer_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `qa_corrective_actions` (add only if missing)
CALL `tich_ensure_column`('qa_corrective_actions', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('qa_corrective_actions', 'qa_plan_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('qa_corrective_actions', 'department_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('qa_corrective_actions', 'checklist_item_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('qa_corrective_actions', 'flagged_reason', 'text NOT NULL');
CALL `tich_ensure_column`('qa_corrective_actions', 'compliance_score_at_flag', 'decimal(5,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('qa_corrective_actions', 'resolution_deadline', 'date NOT NULL');
CALL `tich_ensure_column`('qa_corrective_actions', 'resolution_plan', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('qa_corrective_actions', 'responsible_officer_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('qa_corrective_actions', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'open\\\'\'');
CALL `tich_ensure_column`('qa_corrective_actions', 'resolved_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('qa_corrective_actions', 'resolved_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('qa_corrective_actions', 'resolution_notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('qa_corrective_actions', 'is_module_lock_active', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('qa_corrective_actions', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('qa_corrective_actions', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `qa_corrective_actions` (add only if missing)
CALL `tich_ensure_index`('qa_corrective_actions', 'qa_corrective_actions_checklist_item_id_foreign', '`checklist_item_id`');
CALL `tich_ensure_index`('qa_corrective_actions', 'qa_corrective_actions_department_id_foreign', '`department_id`');
CALL `tich_ensure_index`('qa_corrective_actions', 'qa_corrective_actions_qa_plan_id_foreign', '`qa_plan_id`');
CALL `tich_ensure_index`('qa_corrective_actions', 'qa_corrective_actions_resolved_by_foreign', '`resolved_by`');
CALL `tich_ensure_index`('qa_corrective_actions', 'qa_corrective_actions_responsible_officer_id_foreign', '`responsible_officer_id`');

-- -----------------------------------------------------------------------------
-- Table: `qa_department_submissions`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qa_department_submissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `qa_plan_id` bigint(20) unsigned NOT NULL,
  `checklist_item_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `submitted_by` bigint(20) unsigned NOT NULL,
  `submission_text` text DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `submission_status` varchar(50) NOT NULL DEFAULT 'pending',
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verified_notes` text DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `qa_dept_sub_unique` (`checklist_item_id`,`department_id`,`qa_plan_id`),
  KEY `qa_department_submissions_qa_plan_id_foreign` (`qa_plan_id`),
  KEY `qa_department_submissions_department_id_foreign` (`department_id`),
  KEY `qa_department_submissions_submitted_by_foreign` (`submitted_by`),
  KEY `qa_department_submissions_verified_by_foreign` (`verified_by`),
  CONSTRAINT `qa_department_submissions_checklist_item_id_foreign` FOREIGN KEY (`checklist_item_id`) REFERENCES `qa_audit_checklists` (`id`),
  CONSTRAINT `qa_department_submissions_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `qa_department_submissions_qa_plan_id_foreign` FOREIGN KEY (`qa_plan_id`) REFERENCES `qa_plans` (`id`),
  CONSTRAINT `qa_department_submissions_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `qa_department_submissions_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `qa_department_submissions` (add only if missing)
CALL `tich_ensure_column`('qa_department_submissions', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('qa_department_submissions', 'qa_plan_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('qa_department_submissions', 'checklist_item_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('qa_department_submissions', 'department_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('qa_department_submissions', 'submitted_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('qa_department_submissions', 'submission_text', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('qa_department_submissions', 'score', 'decimal(5,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('qa_department_submissions', 'submission_status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('qa_department_submissions', 'verified_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('qa_department_submissions', 'verified_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('qa_department_submissions', 'verified_notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('qa_department_submissions', 'submitted_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `qa_department_submissions` (add only if missing)
CALL `tich_ensure_index`('qa_department_submissions', 'qa_department_submissions_department_id_foreign', '`department_id`');
CALL `tich_ensure_index`('qa_department_submissions', 'qa_department_submissions_qa_plan_id_foreign', '`qa_plan_id`');
CALL `tich_ensure_index`('qa_department_submissions', 'qa_department_submissions_submitted_by_foreign', '`submitted_by`');
CALL `tich_ensure_index`('qa_department_submissions', 'qa_department_submissions_verified_by_foreign', '`verified_by`');
CALL `tich_ensure_unique`('qa_department_submissions', 'qa_dept_sub_unique', '`checklist_item_id`, `department_id`, `qa_plan_id`');

-- -----------------------------------------------------------------------------
-- Table: `qa_evidence_attachments`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qa_evidence_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `evidence_type` varchar(50) NOT NULL,
  `linked_id` bigint(20) unsigned NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned NOT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `qa_evidence_attachments_uploaded_by_foreign` (`uploaded_by`),
  KEY `qa_evidence_attachments_evidence_type_linked_id_index` (`evidence_type`,`linked_id`),
  CONSTRAINT `qa_evidence_attachments_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `qa_evidence_attachments` (add only if missing)
CALL `tich_ensure_column`('qa_evidence_attachments', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('qa_evidence_attachments', 'evidence_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('qa_evidence_attachments', 'linked_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('qa_evidence_attachments', 'file_path', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('qa_evidence_attachments', 'file_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('qa_evidence_attachments', 'description', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('qa_evidence_attachments', 'uploaded_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('qa_evidence_attachments', 'uploaded_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `qa_evidence_attachments` (add only if missing)
CALL `tich_ensure_index`('qa_evidence_attachments', 'qa_evidence_attachments_evidence_type_linked_id_index', '`evidence_type`, `linked_id`');
CALL `tich_ensure_index`('qa_evidence_attachments', 'qa_evidence_attachments_uploaded_by_foreign', '`uploaded_by`');

-- -----------------------------------------------------------------------------
-- Table: `qa_plans`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qa_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `plan_name` varchar(300) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `scope_type` varchar(50) NOT NULL,
  `department_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`department_ids`)),
  `deployed_by` bigint(20) unsigned NOT NULL,
  `deployed_at` datetime NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `qa_plans_deployed_by_foreign` (`deployed_by`),
  CONSTRAINT `qa_plans_deployed_by_foreign` FOREIGN KEY (`deployed_by`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `qa_plans` (add only if missing)
CALL `tich_ensure_column`('qa_plans', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('qa_plans', 'plan_name', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('qa_plans', 'period_start', 'date NOT NULL');
CALL `tich_ensure_column`('qa_plans', 'period_end', 'date NOT NULL');
CALL `tich_ensure_column`('qa_plans', 'scope_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('qa_plans', 'department_ids', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('qa_plans', 'deployed_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('qa_plans', 'deployed_at', 'datetime NOT NULL');
CALL `tich_ensure_column`('qa_plans', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'active\\\'\'');
CALL `tich_ensure_column`('qa_plans', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('qa_plans', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `qa_plans` (add only if missing)
CALL `tich_ensure_index`('qa_plans', 'qa_plans_deployed_by_foreign', '`deployed_by`');

-- -----------------------------------------------------------------------------
-- Table: `receipts`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `receipts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `receipt_number` varchar(50) NOT NULL,
  `payment_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `student_account_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `issued_at` datetime NOT NULL DEFAULT current_timestamp(),
  `issued_by` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipts_receipt_number_unique` (`receipt_number`),
  KEY `receipts_payment_id_foreign` (`payment_id`),
  KEY `receipts_invoice_id_foreign` (`invoice_id`),
  KEY `receipts_student_account_id_foreign` (`student_account_id`),
  KEY `receipts_student_id_foreign` (`student_id`),
  KEY `receipts_issued_by_foreign` (`issued_by`),
  CONSTRAINT `receipts_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `receipts_issued_by_foreign` FOREIGN KEY (`issued_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `receipts_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`),
  CONSTRAINT `receipts_student_account_id_foreign` FOREIGN KEY (`student_account_id`) REFERENCES `student_accounts` (`id`),
  CONSTRAINT `receipts_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `receipts` (add only if missing)
CALL `tich_ensure_column`('receipts', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('receipts', 'receipt_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('receipts', 'payment_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('receipts', 'invoice_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('receipts', 'student_account_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('receipts', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('receipts', 'amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('receipts', 'payment_method', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('receipts', 'payment_reference', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('receipts', 'issued_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('receipts', 'issued_by', 'bigint(20) unsigned NOT NULL');

-- Indexes for `receipts` (add only if missing)
CALL `tich_ensure_index`('receipts', 'receipts_invoice_id_foreign', '`invoice_id`');
CALL `tich_ensure_index`('receipts', 'receipts_issued_by_foreign', '`issued_by`');
CALL `tich_ensure_index`('receipts', 'receipts_payment_id_foreign', '`payment_id`');
CALL `tich_ensure_unique`('receipts', 'receipts_receipt_number_unique', '`receipt_number`');
CALL `tich_ensure_index`('receipts', 'receipts_student_account_id_foreign', '`student_account_id`');
CALL `tich_ensure_index`('receipts', 'receipts_student_id_foreign', '`student_id`');

-- -----------------------------------------------------------------------------
-- Table: `recruitment_applications`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `recruitment_applications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `application_number` varchar(50) NOT NULL,
  `vacancy_id` bigint(20) unsigned NOT NULL,
  `full_name` varchar(300) NOT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `marital_status` varchar(50) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone_number` varchar(30) NOT NULL,
  `postal_address` varchar(300) DEFAULT NULL,
  `physical_address` varchar(500) DEFAULT NULL,
  `highest_qualification` varchar(50) NOT NULL,
  `qualification_other` varchar(200) DEFAULT NULL,
  `institution` varchar(300) DEFAULT NULL,
  `year_completed` int(11) DEFAULT NULL,
  `grade` varchar(50) DEFAULT NULL,
  `current_organization` varchar(300) DEFAULT NULL,
  `area_of_specialization` varchar(300) DEFAULT NULL,
  `years_of_experience` int(11) NOT NULL DEFAULT 0,
  `referee1_name` varchar(300) DEFAULT NULL,
  `referee1_title` varchar(200) DEFAULT NULL,
  `referee1_organization` varchar(300) DEFAULT NULL,
  `referee1_contact` varchar(100) DEFAULT NULL,
  `referee2_name` varchar(300) DEFAULT NULL,
  `referee2_title` varchar(200) DEFAULT NULL,
  `referee2_organization` varchar(300) DEFAULT NULL,
  `referee2_contact` varchar(100) DEFAULT NULL,
  `expected_salary` varchar(100) DEFAULT NULL,
  `notice_period` varchar(50) DEFAULT NULL,
  `how_did_you_hear` varchar(300) DEFAULT NULL,
  `cv_file_path` varchar(500) NOT NULL,
  `cover_letter_file_path` varchar(500) DEFAULT NULL,
  `certificates_file_paths` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`certificates_file_paths`)),
  `is_shortlisted` tinyint(4) NOT NULL DEFAULT 0,
  `shortlist_status` varchar(50) NOT NULL DEFAULT 'pending',
  `interview_date` datetime DEFAULT NULL,
  `interview_panel_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`interview_panel_ids`)),
  `interview_score` decimal(5,2) DEFAULT NULL,
  `interview_notes` text DEFAULT NULL,
  `offer_made` tinyint(4) NOT NULL DEFAULT 0,
  `offer_accepted` tinyint(4) NOT NULL DEFAULT 0,
  `new_staff_id` bigint(20) unsigned DEFAULT NULL,
  `is_onboarded` tinyint(4) NOT NULL DEFAULT 0,
  `rejection_reason` text DEFAULT NULL,
  `application_source` varchar(50) NOT NULL DEFAULT 'portal',
  `status` varchar(50) NOT NULL DEFAULT 'submitted',
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `decision` varchar(50) DEFAULT NULL,
  `decision_notes` text DEFAULT NULL,
  `is_viewed` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `recruitment_applications_application_number_unique` (`application_number`),
  KEY `recruitment_applications_new_staff_id_foreign` (`new_staff_id`),
  KEY `recruitment_applications_vacancy_id_index` (`vacancy_id`),
  KEY `recruitment_applications_shortlist_status_index` (`shortlist_status`),
  KEY `recruitment_applications_reviewed_by_foreign` (`reviewed_by`),
  KEY `recruitment_applications_status_index` (`status`),
  KEY `recruitment_applications_decision_index` (`decision`),
  CONSTRAINT `recruitment_applications_new_staff_id_foreign` FOREIGN KEY (`new_staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recruitment_applications_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recruitment_applications_vacancy_id_foreign` FOREIGN KEY (`vacancy_id`) REFERENCES `job_vacancies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `recruitment_applications` (add only if missing)
CALL `tich_ensure_column`('recruitment_applications', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('recruitment_applications', 'application_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'vacancy_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'full_name', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'id_number', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'date_of_birth', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'gender', 'varchar(20) NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'marital_status', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'email', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'phone_number', 'varchar(30) NOT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'postal_address', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'physical_address', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'highest_qualification', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'qualification_other', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'institution', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'year_completed', 'int(11) NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'grade', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'current_organization', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'area_of_specialization', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'years_of_experience', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('recruitment_applications', 'referee1_name', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'referee1_title', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'referee1_organization', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'referee1_contact', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'referee2_name', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'referee2_title', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'referee2_organization', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'referee2_contact', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'expected_salary', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'notice_period', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'how_did_you_hear', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'cv_file_path', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'cover_letter_file_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'certificates_file_paths', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'is_shortlisted', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('recruitment_applications', 'shortlist_status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('recruitment_applications', 'interview_date', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'interview_panel_ids', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'interview_score', 'decimal(5,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'interview_notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'offer_made', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('recruitment_applications', 'offer_accepted', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('recruitment_applications', 'new_staff_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'is_onboarded', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('recruitment_applications', 'rejection_reason', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'application_source', 'varchar(50) NOT NULL DEFAULT \'\\\'portal\\\'\'');
CALL `tich_ensure_column`('recruitment_applications', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'submitted\\\'\'');
CALL `tich_ensure_column`('recruitment_applications', 'reviewed_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'reviewed_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'decision', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'decision_notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('recruitment_applications', 'is_viewed', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('recruitment_applications', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('recruitment_applications', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `recruitment_applications` (add only if missing)
CALL `tich_ensure_unique`('recruitment_applications', 'recruitment_applications_application_number_unique', '`application_number`');
CALL `tich_ensure_index`('recruitment_applications', 'recruitment_applications_decision_index', '`decision`');
CALL `tich_ensure_index`('recruitment_applications', 'recruitment_applications_new_staff_id_foreign', '`new_staff_id`');
CALL `tich_ensure_index`('recruitment_applications', 'recruitment_applications_reviewed_by_foreign', '`reviewed_by`');
CALL `tich_ensure_index`('recruitment_applications', 'recruitment_applications_shortlist_status_index', '`shortlist_status`');
CALL `tich_ensure_index`('recruitment_applications', 'recruitment_applications_status_index', '`status`');
CALL `tich_ensure_index`('recruitment_applications', 'recruitment_applications_vacancy_id_index', '`vacancy_id`');

-- -----------------------------------------------------------------------------
-- Table: `refunds`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `refunds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `refund_number` varchar(50) NOT NULL,
  `payment_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `student_account_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reason` varchar(500) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `requested_by` bigint(20) unsigned NOT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `processed_by` bigint(20) unsigned DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `refunds_refund_number_unique` (`refund_number`),
  KEY `refunds_payment_id_foreign` (`payment_id`),
  KEY `refunds_invoice_id_foreign` (`invoice_id`),
  KEY `refunds_student_account_id_foreign` (`student_account_id`),
  KEY `refunds_student_id_foreign` (`student_id`),
  KEY `refunds_requested_by_foreign` (`requested_by`),
  KEY `refunds_approved_by_foreign` (`approved_by`),
  KEY `refunds_processed_by_foreign` (`processed_by`),
  KEY `refunds_status_index` (`status`),
  CONSTRAINT `refunds_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `refunds_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `refunds_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`),
  CONSTRAINT `refunds_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `refunds_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `refunds_student_account_id_foreign` FOREIGN KEY (`student_account_id`) REFERENCES `student_accounts` (`id`),
  CONSTRAINT `refunds_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `refunds` (add only if missing)
CALL `tich_ensure_column`('refunds', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('refunds', 'refund_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('refunds', 'payment_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('refunds', 'invoice_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('refunds', 'student_account_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('refunds', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('refunds', 'amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('refunds', 'reason', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('refunds', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('refunds', 'requested_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('refunds', 'approved_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('refunds', 'approved_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('refunds', 'processed_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('refunds', 'processed_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('refunds', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `refunds` (add only if missing)
CALL `tich_ensure_index`('refunds', 'refunds_approved_by_foreign', '`approved_by`');
CALL `tich_ensure_index`('refunds', 'refunds_invoice_id_foreign', '`invoice_id`');
CALL `tich_ensure_index`('refunds', 'refunds_payment_id_foreign', '`payment_id`');
CALL `tich_ensure_index`('refunds', 'refunds_processed_by_foreign', '`processed_by`');
CALL `tich_ensure_unique`('refunds', 'refunds_refund_number_unique', '`refund_number`');
CALL `tich_ensure_index`('refunds', 'refunds_requested_by_foreign', '`requested_by`');
CALL `tich_ensure_index`('refunds', 'refunds_status_index', '`status`');
CALL `tich_ensure_index`('refunds', 'refunds_student_account_id_foreign', '`student_account_id`');
CALL `tich_ensure_index`('refunds', 'refunds_student_id_foreign', '`student_id`');

-- -----------------------------------------------------------------------------
-- Table: `registered_units`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `registered_units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `semester_registration_id` bigint(20) unsigned NOT NULL,
  `unit_id` bigint(20) unsigned NOT NULL,
  `is_additional` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reg_units_unique` (`semester_registration_id`,`unit_id`),
  KEY `registered_units_unit_id_foreign` (`unit_id`),
  CONSTRAINT `registered_units_semester_registration_id_foreign` FOREIGN KEY (`semester_registration_id`) REFERENCES `student_semester_registrations` (`id`),
  CONSTRAINT `registered_units_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `registered_units` (add only if missing)
CALL `tich_ensure_column`('registered_units', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('registered_units', 'semester_registration_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('registered_units', 'unit_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('registered_units', 'is_additional', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('registered_units', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `registered_units` (add only if missing)
CALL `tich_ensure_index`('registered_units', 'registered_units_unit_id_foreign', '`unit_id`');
CALL `tich_ensure_unique`('registered_units', 'reg_units_unique', '`semester_registration_id`, `unit_id`');

-- -----------------------------------------------------------------------------
-- Table: `remark_requests`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `remark_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `exam_result_id` bigint(20) unsigned NOT NULL,
  `reason` text NOT NULL,
  `fee_amount` decimal(12,2) NOT NULL,
  `fee_paid` tinyint(4) NOT NULL DEFAULT 0,
  `status` varchar(50) NOT NULL DEFAULT 'pending_fee',
  `assigned_examiner_id` bigint(20) unsigned DEFAULT NULL,
  `original_script_path` varchar(500) DEFAULT NULL,
  `new_marks` decimal(5,2) DEFAULT NULL,
  `original_marks` decimal(5,2) DEFAULT NULL,
  `outcome` varchar(50) DEFAULT NULL,
  `outcome_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `remark_requests_student_id_foreign` (`student_id`),
  KEY `remark_requests_exam_result_id_foreign` (`exam_result_id`),
  KEY `remark_requests_assigned_examiner_id_foreign` (`assigned_examiner_id`),
  CONSTRAINT `remark_requests_assigned_examiner_id_foreign` FOREIGN KEY (`assigned_examiner_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `remark_requests_exam_result_id_foreign` FOREIGN KEY (`exam_result_id`) REFERENCES `exam_results` (`id`),
  CONSTRAINT `remark_requests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `remark_requests` (add only if missing)
CALL `tich_ensure_column`('remark_requests', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('remark_requests', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('remark_requests', 'exam_result_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('remark_requests', 'reason', 'text NOT NULL');
CALL `tich_ensure_column`('remark_requests', 'fee_amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('remark_requests', 'fee_paid', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('remark_requests', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending_fee\\\'\'');
CALL `tich_ensure_column`('remark_requests', 'assigned_examiner_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('remark_requests', 'original_script_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('remark_requests', 'new_marks', 'decimal(5,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('remark_requests', 'original_marks', 'decimal(5,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('remark_requests', 'outcome', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('remark_requests', 'outcome_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('remark_requests', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `remark_requests` (add only if missing)
CALL `tich_ensure_index`('remark_requests', 'remark_requests_assigned_examiner_id_foreign', '`assigned_examiner_id`');
CALL `tich_ensure_index`('remark_requests', 'remark_requests_exam_result_id_foreign', '`exam_result_id`');
CALL `tich_ensure_index`('remark_requests', 'remark_requests_student_id_foreign', '`student_id`');

-- -----------------------------------------------------------------------------
-- Table: `research_focus_areas`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `research_focus_areas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `icon_path` varchar(500) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `research_focus_areas_created_by_foreign` (`created_by`),
  KEY `research_focus_areas_updated_by_foreign` (`updated_by`),
  CONSTRAINT `research_focus_areas_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `research_focus_areas_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `research_focus_areas` (add only if missing)
CALL `tich_ensure_column`('research_focus_areas', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('research_focus_areas', 'name', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('research_focus_areas', 'description', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('research_focus_areas', 'icon_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('research_focus_areas', 'display_order', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('research_focus_areas', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('research_focus_areas', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('research_focus_areas', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('research_focus_areas', 'updated_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('research_focus_areas', 'updated_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `research_focus_areas` (add only if missing)
CALL `tich_ensure_index`('research_focus_areas', 'research_focus_areas_created_by_foreign', '`created_by`');
CALL `tich_ensure_index`('research_focus_areas', 'research_focus_areas_updated_by_foreign', '`updated_by`');

-- -----------------------------------------------------------------------------
-- Table: `research_projects`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `research_projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(300) NOT NULL,
  `subtitle` varchar(500) DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `focus_area_id` bigint(20) unsigned DEFAULT NULL,
  `summary` text NOT NULL,
  `abstract` longtext DEFAULT NULL,
  `cover_image_path` varchar(500) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `lead_researcher_id` bigint(20) unsigned DEFAULT NULL,
  `is_featured` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `research_projects_focus_area_id_foreign` (`focus_area_id`),
  KEY `research_projects_lead_researcher_id_foreign` (`lead_researcher_id`),
  KEY `research_projects_created_by_foreign` (`created_by`),
  KEY `research_projects_updated_by_foreign` (`updated_by`),
  CONSTRAINT `research_projects_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `research_projects_focus_area_id_foreign` FOREIGN KEY (`focus_area_id`) REFERENCES `research_focus_areas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `research_projects_lead_researcher_id_foreign` FOREIGN KEY (`lead_researcher_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `research_projects_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `research_projects` (add only if missing)
CALL `tich_ensure_column`('research_projects', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('research_projects', 'title', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('research_projects', 'subtitle', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('research_projects', 'status', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('research_projects', 'focus_area_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('research_projects', 'summary', 'text NOT NULL');
CALL `tich_ensure_column`('research_projects', 'abstract', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('research_projects', 'cover_image_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('research_projects', 'start_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('research_projects', 'end_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('research_projects', 'lead_researcher_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('research_projects', 'is_featured', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('research_projects', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('research_projects', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('research_projects', 'updated_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('research_projects', 'updated_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `research_projects` (add only if missing)
CALL `tich_ensure_index`('research_projects', 'research_projects_created_by_foreign', '`created_by`');
CALL `tich_ensure_index`('research_projects', 'research_projects_focus_area_id_foreign', '`focus_area_id`');
CALL `tich_ensure_index`('research_projects', 'research_projects_lead_researcher_id_foreign', '`lead_researcher_id`');
CALL `tich_ensure_index`('research_projects', 'research_projects_updated_by_foreign', '`updated_by`');

-- -----------------------------------------------------------------------------
-- Table: `research_publications`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `research_publications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(300) NOT NULL,
  `subtitle` varchar(500) DEFAULT NULL,
  `publication_type` varchar(50) NOT NULL,
  `authors` varchar(500) DEFAULT NULL,
  `cover_image_path` varchar(500) DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `publish_date` date NOT NULL,
  `is_downloadable` tinyint(4) NOT NULL DEFAULT 1,
  `download_count` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `research_publications_created_by_foreign` (`created_by`),
  KEY `research_publications_updated_by_foreign` (`updated_by`),
  CONSTRAINT `research_publications_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `research_publications_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `research_publications` (add only if missing)
CALL `tich_ensure_column`('research_publications', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('research_publications', 'title', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('research_publications', 'subtitle', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('research_publications', 'publication_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('research_publications', 'authors', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('research_publications', 'cover_image_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('research_publications', 'file_path', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('research_publications', 'publish_date', 'date NOT NULL');
CALL `tich_ensure_column`('research_publications', 'is_downloadable', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('research_publications', 'download_count', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('research_publications', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('research_publications', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('research_publications', 'updated_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('research_publications', 'updated_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `research_publications` (add only if missing)
CALL `tich_ensure_index`('research_publications', 'research_publications_created_by_foreign', '`created_by`');
CALL `tich_ensure_index`('research_publications', 'research_publications_updated_by_foreign', '`updated_by`');

-- -----------------------------------------------------------------------------
-- Table: `roles`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_name` varchar(100) NOT NULL,
  `display_name` varchar(200) DEFAULT NULL,
  `role_category` varchar(50) NOT NULL,
  `module_key` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_system_role` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_role_name_unique` (`role_name`),
  KEY `roles_module_key_index` (`module_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `roles` (add only if missing)
CALL `tich_ensure_column`('roles', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('roles', 'role_name', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('roles', 'display_name', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('roles', 'role_category', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('roles', 'module_key', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('roles', 'description', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('roles', 'is_system_role', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('roles', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('roles', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `roles` (add only if missing)
CALL `tich_ensure_index`('roles', 'roles_module_key_index', '`module_key`');
CALL `tich_ensure_unique`('roles', 'roles_role_name_unique', '`role_name`');

-- -----------------------------------------------------------------------------
-- Table: `role_categories`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `role_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_code` varchar(50) NOT NULL,
  `category_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_categories_category_code_unique` (`category_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `role_categories` (add only if missing)
CALL `tich_ensure_column`('role_categories', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('role_categories', 'category_code', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('role_categories', 'category_name', 'varchar(150) NOT NULL');
CALL `tich_ensure_column`('role_categories', 'description', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('role_categories', 'display_order', 'smallint(5) unsigned NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('role_categories', 'is_system', 'tinyint(1) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('role_categories', 'is_active', 'tinyint(1) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('role_categories', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('role_categories', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `role_categories` (add only if missing)
CALL `tich_ensure_unique`('role_categories', 'role_categories_category_code_unique', '`category_code`');

-- -----------------------------------------------------------------------------
-- Table: `rooms`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rooms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `campus_id` bigint(20) unsigned NOT NULL,
  `room_code` varchar(50) NOT NULL,
  `room_name` varchar(150) NOT NULL,
  `capacity` smallint(5) unsigned NOT NULL DEFAULT 30,
  `room_type` varchar(50) NOT NULL DEFAULT 'lecture',
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `rooms_campus_id_room_code_unique` (`campus_id`,`room_code`),
  CONSTRAINT `rooms_campus_id_foreign` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `rooms` (add only if missing)
CALL `tich_ensure_column`('rooms', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('rooms', 'campus_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('rooms', 'room_code', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('rooms', 'room_name', 'varchar(150) NOT NULL');
CALL `tich_ensure_column`('rooms', 'capacity', 'smallint(5) unsigned NOT NULL DEFAULT \'30\'');
CALL `tich_ensure_column`('rooms', 'room_type', 'varchar(50) NOT NULL DEFAULT \'\\\'lecture\\\'\'');
CALL `tich_ensure_column`('rooms', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('rooms', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `rooms` (add only if missing)
CALL `tich_ensure_unique`('rooms', 'rooms_campus_id_room_code_unique', '`campus_id`, `room_code`');

-- -----------------------------------------------------------------------------
-- Table: `rpl_applications`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rpl_applications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `applicant_id` bigint(20) unsigned NOT NULL,
  `program_id` bigint(20) unsigned NOT NULL,
  `prior_experience_type` varchar(50) NOT NULL,
  `prior_experience_years` int(11) DEFAULT NULL,
  `supporting_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`supporting_documents`)),
  `trade_equivalence_score` decimal(5,2) DEFAULT NULL,
  `credit_exemption_unit_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`credit_exemption_unit_ids`)),
  `status` varchar(50) NOT NULL DEFAULT 'pending_assessment',
  `assessed_by` bigint(20) unsigned DEFAULT NULL,
  `assessed_at` datetime DEFAULT NULL,
  `total_credits_awarded` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rpl_applications_program_id_foreign` (`program_id`),
  KEY `rpl_applications_applicant_id_foreign` (`applicant_id`),
  KEY `rpl_applications_assessed_by_foreign` (`assessed_by`),
  CONSTRAINT `rpl_applications_applicant_id_foreign` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`),
  CONSTRAINT `rpl_applications_assessed_by_foreign` FOREIGN KEY (`assessed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rpl_applications_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `academic_programs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `rpl_applications` (add only if missing)
CALL `tich_ensure_column`('rpl_applications', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('rpl_applications', 'applicant_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('rpl_applications', 'program_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('rpl_applications', 'prior_experience_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('rpl_applications', 'prior_experience_years', 'int(11) NULL DEFAULT NULL');
CALL `tich_ensure_column`('rpl_applications', 'supporting_documents', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('rpl_applications', 'trade_equivalence_score', 'decimal(5,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('rpl_applications', 'credit_exemption_unit_ids', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('rpl_applications', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending_assessment\\\'\'');
CALL `tich_ensure_column`('rpl_applications', 'assessed_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('rpl_applications', 'assessed_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('rpl_applications', 'total_credits_awarded', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('rpl_applications', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('rpl_applications', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `rpl_applications` (add only if missing)
CALL `tich_ensure_index`('rpl_applications', 'rpl_applications_applicant_id_foreign', '`applicant_id`');
CALL `tich_ensure_index`('rpl_applications', 'rpl_applications_assessed_by_foreign', '`assessed_by`');
CALL `tich_ensure_index`('rpl_applications', 'rpl_applications_program_id_foreign', '`program_id`');

-- -----------------------------------------------------------------------------
-- Table: `sacco_loans`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sacco_loans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `loan_number` varchar(50) NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `loan_type` varchar(50) NOT NULL,
  `principal_amount` decimal(12,2) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL,
  `loan_term_months` int(11) NOT NULL,
  `monthly_repayment` decimal(12,2) NOT NULL,
  `total_interest` decimal(12,2) NOT NULL,
  `total_amount_due` decimal(12,2) NOT NULL,
  `max_eligible_amount` decimal(12,2) NOT NULL,
  `guarantor_1_id` bigint(20) unsigned DEFAULT NULL,
  `guarantor_2_id` bigint(20) unsigned DEFAULT NULL,
  `application_date` date NOT NULL,
  `application_status` varchar(50) NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_date` date DEFAULT NULL,
  `disbursement_date` date DEFAULT NULL,
  `outstanding_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `default_status` varchar(50) NOT NULL DEFAULT 'none',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sacco_loans_loan_number_unique` (`loan_number`),
  KEY `sacco_loans_member_id_foreign` (`member_id`),
  KEY `sacco_loans_guarantor_1_id_foreign` (`guarantor_1_id`),
  KEY `sacco_loans_guarantor_2_id_foreign` (`guarantor_2_id`),
  KEY `sacco_loans_approved_by_foreign` (`approved_by`),
  CONSTRAINT `sacco_loans_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sacco_loans_guarantor_1_id_foreign` FOREIGN KEY (`guarantor_1_id`) REFERENCES `sacco_members` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sacco_loans_guarantor_2_id_foreign` FOREIGN KEY (`guarantor_2_id`) REFERENCES `sacco_members` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sacco_loans_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `sacco_members` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `sacco_loans` (add only if missing)
CALL `tich_ensure_column`('sacco_loans', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('sacco_loans', 'loan_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('sacco_loans', 'member_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('sacco_loans', 'loan_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('sacco_loans', 'principal_amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('sacco_loans', 'interest_rate', 'decimal(5,2) NOT NULL');
CALL `tich_ensure_column`('sacco_loans', 'loan_term_months', 'int(11) NOT NULL');
CALL `tich_ensure_column`('sacco_loans', 'monthly_repayment', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('sacco_loans', 'total_interest', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('sacco_loans', 'total_amount_due', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('sacco_loans', 'max_eligible_amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('sacco_loans', 'guarantor_1_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('sacco_loans', 'guarantor_2_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('sacco_loans', 'application_date', 'date NOT NULL');
CALL `tich_ensure_column`('sacco_loans', 'application_status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('sacco_loans', 'approved_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('sacco_loans', 'approved_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('sacco_loans', 'disbursement_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('sacco_loans', 'outstanding_balance', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('sacco_loans', 'default_status', 'varchar(50) NOT NULL DEFAULT \'\\\'none\\\'\'');
CALL `tich_ensure_column`('sacco_loans', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('sacco_loans', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `sacco_loans` (add only if missing)
CALL `tich_ensure_index`('sacco_loans', 'sacco_loans_approved_by_foreign', '`approved_by`');
CALL `tich_ensure_index`('sacco_loans', 'sacco_loans_guarantor_1_id_foreign', '`guarantor_1_id`');
CALL `tich_ensure_index`('sacco_loans', 'sacco_loans_guarantor_2_id_foreign', '`guarantor_2_id`');
CALL `tich_ensure_unique`('sacco_loans', 'sacco_loans_loan_number_unique', '`loan_number`');
CALL `tich_ensure_index`('sacco_loans', 'sacco_loans_member_id_foreign', '`member_id`');

-- -----------------------------------------------------------------------------
-- Table: `sacco_loan_repayments`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sacco_loan_repayments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` bigint(20) unsigned NOT NULL,
  `repayment_number` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `due_amount` decimal(12,2) NOT NULL,
  `principal_portion` decimal(12,2) NOT NULL,
  `interest_portion` decimal(12,2) NOT NULL,
  `amount_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_date` date DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sacco_loan_repayments_loan_id_foreign` (`loan_id`),
  CONSTRAINT `sacco_loan_repayments_loan_id_foreign` FOREIGN KEY (`loan_id`) REFERENCES `sacco_loans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `sacco_loan_repayments` (add only if missing)
CALL `tich_ensure_column`('sacco_loan_repayments', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('sacco_loan_repayments', 'loan_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('sacco_loan_repayments', 'repayment_number', 'int(11) NOT NULL');
CALL `tich_ensure_column`('sacco_loan_repayments', 'due_date', 'date NOT NULL');
CALL `tich_ensure_column`('sacco_loan_repayments', 'due_amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('sacco_loan_repayments', 'principal_portion', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('sacco_loan_repayments', 'interest_portion', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('sacco_loan_repayments', 'amount_paid', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('sacco_loan_repayments', 'payment_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('sacco_loan_repayments', 'payment_reference', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('sacco_loan_repayments', 'payment_method', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('sacco_loan_repayments', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('sacco_loan_repayments', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('sacco_loan_repayments', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `sacco_loan_repayments` (add only if missing)
CALL `tich_ensure_index`('sacco_loan_repayments', 'sacco_loan_repayments_loan_id_foreign', '`loan_id`');

-- -----------------------------------------------------------------------------
-- Table: `sacco_members`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sacco_members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_number` varchar(50) NOT NULL,
  `staff_id` bigint(20) unsigned NOT NULL,
  `registration_fee` decimal(12,2) NOT NULL DEFAULT 500.00,
  `registration_fee_paid` tinyint(4) NOT NULL DEFAULT 0,
  `registration_fee_paid_date` date DEFAULT NULL,
  `membership_status` varchar(50) NOT NULL DEFAULT 'pending_fee',
  `monthly_contribution_min` decimal(12,2) NOT NULL DEFAULT 200.00,
  `total_savings_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `max_loan_eligibility` decimal(12,2) NOT NULL DEFAULT 0.00,
  `guarantor_1_id` bigint(20) unsigned DEFAULT NULL,
  `guarantor_2_id` bigint(20) unsigned DEFAULT NULL,
  `joining_date` date NOT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sacco_members_member_number_unique` (`member_number`),
  KEY `sacco_members_staff_id_foreign` (`staff_id`),
  KEY `sacco_members_guarantor_1_id_foreign` (`guarantor_1_id`),
  KEY `sacco_members_guarantor_2_id_foreign` (`guarantor_2_id`),
  CONSTRAINT `sacco_members_guarantor_1_id_foreign` FOREIGN KEY (`guarantor_1_id`) REFERENCES `sacco_members` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sacco_members_guarantor_2_id_foreign` FOREIGN KEY (`guarantor_2_id`) REFERENCES `sacco_members` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sacco_members_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `sacco_members` (add only if missing)
CALL `tich_ensure_column`('sacco_members', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('sacco_members', 'member_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('sacco_members', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('sacco_members', 'registration_fee', 'decimal(12,2) NOT NULL DEFAULT \'500.00\'');
CALL `tich_ensure_column`('sacco_members', 'registration_fee_paid', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('sacco_members', 'registration_fee_paid_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('sacco_members', 'membership_status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending_fee\\\'\'');
CALL `tich_ensure_column`('sacco_members', 'monthly_contribution_min', 'decimal(12,2) NOT NULL DEFAULT \'200.00\'');
CALL `tich_ensure_column`('sacco_members', 'total_savings_balance', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('sacco_members', 'max_loan_eligibility', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('sacco_members', 'guarantor_1_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('sacco_members', 'guarantor_2_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('sacco_members', 'joining_date', 'date NOT NULL');
CALL `tich_ensure_column`('sacco_members', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('sacco_members', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('sacco_members', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `sacco_members` (add only if missing)
CALL `tich_ensure_index`('sacco_members', 'sacco_members_guarantor_1_id_foreign', '`guarantor_1_id`');
CALL `tich_ensure_index`('sacco_members', 'sacco_members_guarantor_2_id_foreign', '`guarantor_2_id`');
CALL `tich_ensure_unique`('sacco_members', 'sacco_members_member_number_unique', '`member_number`');
CALL `tich_ensure_index`('sacco_members', 'sacco_members_staff_id_foreign', '`staff_id`');

-- -----------------------------------------------------------------------------
-- Table: `sacco_savings`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sacco_savings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) unsigned NOT NULL,
  `transaction_type` varchar(50) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `transaction_date` date NOT NULL,
  `reference_number` varchar(50) NOT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `processed_by` bigint(20) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sacco_savings_reference_number_unique` (`reference_number`),
  KEY `sacco_savings_member_id_foreign` (`member_id`),
  KEY `sacco_savings_processed_by_foreign` (`processed_by`),
  CONSTRAINT `sacco_savings_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `sacco_members` (`id`),
  CONSTRAINT `sacco_savings_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `sacco_savings` (add only if missing)
CALL `tich_ensure_column`('sacco_savings', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('sacco_savings', 'member_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('sacco_savings', 'transaction_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('sacco_savings', 'amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('sacco_savings', 'transaction_date', 'date NOT NULL');
CALL `tich_ensure_column`('sacco_savings', 'reference_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('sacco_savings', 'notes', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('sacco_savings', 'processed_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('sacco_savings', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('sacco_savings', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `sacco_savings` (add only if missing)
CALL `tich_ensure_index`('sacco_savings', 'sacco_savings_member_id_foreign', '`member_id`');
CALL `tich_ensure_index`('sacco_savings', 'sacco_savings_processed_by_foreign', '`processed_by`');
CALL `tich_ensure_unique`('sacco_savings', 'sacco_savings_reference_number_unique', '`reference_number`');

-- -----------------------------------------------------------------------------
-- Table: `semesters`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `semesters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `academic_year_id` bigint(20) unsigned NOT NULL,
  `semester_label` varchar(20) NOT NULL,
  `semester_number` int(11) NOT NULL,
  `intake_month` varchar(20) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `registration_open_date` date DEFAULT NULL,
  `registration_close_date` date DEFAULT NULL,
  `is_current` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `semesters_academic_year_id_foreign` (`academic_year_id`),
  CONSTRAINT `semesters_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `semesters` (add only if missing)
CALL `tich_ensure_column`('semesters', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('semesters', 'academic_year_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('semesters', 'semester_label', 'varchar(20) NOT NULL');
CALL `tich_ensure_column`('semesters', 'semester_number', 'int(11) NOT NULL');
CALL `tich_ensure_column`('semesters', 'intake_month', 'varchar(20) NULL DEFAULT NULL');
CALL `tich_ensure_column`('semesters', 'start_date', 'date NOT NULL');
CALL `tich_ensure_column`('semesters', 'end_date', 'date NOT NULL');
CALL `tich_ensure_column`('semesters', 'registration_open_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('semesters', 'registration_close_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('semesters', 'is_current', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('semesters', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('semesters', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `semesters` (add only if missing)
CALL `tich_ensure_index`('semesters', 'semesters_academic_year_id_foreign', '`academic_year_id`');

-- -----------------------------------------------------------------------------
-- Table: `sessions`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `sessions` (add only if missing)
CALL `tich_ensure_column`('sessions', 'id', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('sessions', 'user_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('sessions', 'ip_address', 'varchar(45) NULL DEFAULT NULL');
CALL `tich_ensure_column`('sessions', 'user_agent', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('sessions', 'payload', 'longtext NOT NULL');
CALL `tich_ensure_column`('sessions', 'last_activity', 'int(11) NOT NULL');

-- Indexes for `sessions` (add only if missing)
CALL `tich_ensure_index`('sessions', 'sessions_last_activity_index', '`last_activity`');
CALL `tich_ensure_index`('sessions', 'sessions_user_id_index', '`user_id`');

-- -----------------------------------------------------------------------------
-- Table: `session_tokens`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `session_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `token_type` enum('session','api','password_reset','email_verify','mfa') NOT NULL DEFAULT 'session',
  `device_info` varchar(500) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `is_revoked` tinyint(4) NOT NULL DEFAULT 0,
  `revoked_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_tokens_token_hash_unique` (`token_hash`),
  KEY `session_tokens_user_id_index` (`user_id`),
  KEY `session_tokens_expires_at_index` (`expires_at`),
  CONSTRAINT `session_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `session_tokens` (add only if missing)
CALL `tich_ensure_column`('session_tokens', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('session_tokens', 'user_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('session_tokens', 'token_hash', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('session_tokens', 'token_type', 'enum(\'session\',\'api\',\'password_reset\',\'email_verify\',\'mfa\') NOT NULL DEFAULT \'\\\'session\\\'\'');
CALL `tich_ensure_column`('session_tokens', 'device_info', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('session_tokens', 'ip_address', 'varchar(45) NULL DEFAULT NULL');
CALL `tich_ensure_column`('session_tokens', 'last_used_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('session_tokens', 'expires_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('session_tokens', 'is_revoked', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('session_tokens', 'revoked_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('session_tokens', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `session_tokens` (add only if missing)
CALL `tich_ensure_index`('session_tokens', 'session_tokens_expires_at_index', '`expires_at`');
CALL `tich_ensure_unique`('session_tokens', 'session_tokens_token_hash_unique', '`token_hash`');
CALL `tich_ensure_index`('session_tokens', 'session_tokens_user_id_index', '`user_id`');

-- -----------------------------------------------------------------------------
-- Table: `site_settings`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `site_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `value_type` varchar(50) NOT NULL DEFAULT 'string',
  `group_name` varchar(100) DEFAULT NULL,
  `label` varchar(200) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `is_public` tinyint(4) NOT NULL DEFAULT 1,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `site_settings_setting_key_unique` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `site_settings` (add only if missing)
CALL `tich_ensure_column`('site_settings', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('site_settings', 'setting_key', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('site_settings', 'setting_value', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('site_settings', 'value_type', 'varchar(50) NOT NULL DEFAULT \'\\\'string\\\'\'');
CALL `tich_ensure_column`('site_settings', 'group_name', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('site_settings', 'label', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('site_settings', 'description', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('site_settings', 'is_public', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('site_settings', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('site_settings', 'updated_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('site_settings', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `site_settings` (add only if missing)
CALL `tich_ensure_unique`('site_settings', 'site_settings_setting_key_unique', '`setting_key`');

-- -----------------------------------------------------------------------------
-- Table: `sms_gateway_logs`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sms_gateway_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `notification_id` bigint(20) unsigned DEFAULT NULL,
  `recipient_phone` varchar(30) NOT NULL,
  `message_body` text NOT NULL,
  `provider` varchar(50) NOT NULL,
  `provider_message_id` varchar(100) DEFAULT NULL,
  `segments` int(11) NOT NULL DEFAULT 1,
  `status` varchar(50) NOT NULL DEFAULT 'queued',
  `status_code` varchar(50) DEFAULT NULL,
  `error_message` varchar(500) DEFAULT NULL,
  `cost_KES` decimal(10,2) NOT NULL DEFAULT 0.00,
  `dispatched_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sms_gateway_logs_notification_id_index` (`notification_id`),
  KEY `sms_gateway_logs_recipient_phone_index` (`recipient_phone`),
  KEY `sms_gateway_logs_created_at_index` (`created_at`),
  CONSTRAINT `sms_gateway_logs_notification_id_foreign` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `sms_gateway_logs` (add only if missing)
CALL `tich_ensure_column`('sms_gateway_logs', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('sms_gateway_logs', 'notification_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('sms_gateway_logs', 'recipient_phone', 'varchar(30) NOT NULL');
CALL `tich_ensure_column`('sms_gateway_logs', 'message_body', 'text NOT NULL');
CALL `tich_ensure_column`('sms_gateway_logs', 'provider', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('sms_gateway_logs', 'provider_message_id', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('sms_gateway_logs', 'segments', 'int(11) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('sms_gateway_logs', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'queued\\\'\'');
CALL `tich_ensure_column`('sms_gateway_logs', 'status_code', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('sms_gateway_logs', 'error_message', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('sms_gateway_logs', 'cost_KES', 'decimal(10,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('sms_gateway_logs', 'dispatched_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('sms_gateway_logs', 'delivered_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('sms_gateway_logs', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `sms_gateway_logs` (add only if missing)
CALL `tich_ensure_index`('sms_gateway_logs', 'sms_gateway_logs_created_at_index', '`created_at`');
CALL `tich_ensure_index`('sms_gateway_logs', 'sms_gateway_logs_notification_id_index', '`notification_id`');
CALL `tich_ensure_index`('sms_gateway_logs', 'sms_gateway_logs_recipient_phone_index', '`recipient_phone`');

-- -----------------------------------------------------------------------------
-- Table: `social_links`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `social_links` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `platform` varchar(50) NOT NULL,
  `display_name` varchar(200) NOT NULL,
  `url` varchar(500) NOT NULL,
  `icon_name` varchar(50) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `social_links` (add only if missing)
CALL `tich_ensure_column`('social_links', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('social_links', 'platform', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('social_links', 'display_name', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('social_links', 'url', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('social_links', 'icon_name', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('social_links', 'display_order', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('social_links', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('social_links', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('social_links', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `social_links` (add only if missing)
-- (no secondary indexes)

-- -----------------------------------------------------------------------------
-- Table: `special_exam_requests`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `special_exam_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `exam_result_id` bigint(20) unsigned NOT NULL,
  `reason` text NOT NULL,
  `supporting_docs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`supporting_docs`)),
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `scheduled_exam_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `special_exam_requests_student_id_foreign` (`student_id`),
  KEY `special_exam_requests_exam_result_id_foreign` (`exam_result_id`),
  KEY `special_exam_requests_reviewed_by_foreign` (`reviewed_by`),
  KEY `special_exam_requests_scheduled_exam_id_foreign` (`scheduled_exam_id`),
  CONSTRAINT `special_exam_requests_exam_result_id_foreign` FOREIGN KEY (`exam_result_id`) REFERENCES `exam_results` (`id`),
  CONSTRAINT `special_exam_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `special_exam_requests_scheduled_exam_id_foreign` FOREIGN KEY (`scheduled_exam_id`) REFERENCES `exam_schedules` (`id`) ON DELETE SET NULL,
  CONSTRAINT `special_exam_requests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `special_exam_requests` (add only if missing)
CALL `tich_ensure_column`('special_exam_requests', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('special_exam_requests', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('special_exam_requests', 'exam_result_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('special_exam_requests', 'reason', 'text NOT NULL');
CALL `tich_ensure_column`('special_exam_requests', 'supporting_docs', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('special_exam_requests', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('special_exam_requests', 'reviewed_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('special_exam_requests', 'reviewed_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('special_exam_requests', 'scheduled_exam_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('special_exam_requests', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `special_exam_requests` (add only if missing)
CALL `tich_ensure_index`('special_exam_requests', 'special_exam_requests_exam_result_id_foreign', '`exam_result_id`');
CALL `tich_ensure_index`('special_exam_requests', 'special_exam_requests_reviewed_by_foreign', '`reviewed_by`');
CALL `tich_ensure_index`('special_exam_requests', 'special_exam_requests_scheduled_exam_id_foreign', '`scheduled_exam_id`');
CALL `tich_ensure_index`('special_exam_requests', 'special_exam_requests_student_id_foreign', '`student_id`');

-- -----------------------------------------------------------------------------
-- Table: `staff`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_number` varchar(50) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `surname` varchar(100) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` varchar(20) NOT NULL,
  `marital_status` varchar(50) DEFAULT NULL,
  `national_id_number` varchar(50) DEFAULT NULL,
  `passport_number` varchar(50) DEFAULT NULL,
  `nationality` varchar(100) NOT NULL DEFAULT 'Kenyan',
  `home_county` varchar(100) DEFAULT NULL,
  `primary_email` varchar(255) NOT NULL,
  `organisation_email` varchar(255) DEFAULT NULL,
  `phone_number` varchar(30) NOT NULL,
  `alt_phone_number` varchar(30) DEFAULT NULL,
  `postal_address` varchar(300) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `physical_address` varchar(500) DEFAULT NULL,
  `emergency_contact_name` varchar(300) DEFAULT NULL,
  `emergency_contact_phone` varchar(30) DEFAULT NULL,
  `emergency_contact_relationship` varchar(50) DEFAULT NULL,
  `photo_path` varchar(500) DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `campus_id` bigint(20) unsigned DEFAULT NULL,
  `line_manager_id` bigint(20) unsigned DEFAULT NULL,
  `job_title` varchar(200) NOT NULL,
  `job_grade` varchar(20) DEFAULT NULL,
  `salary_scale` varchar(50) DEFAULT NULL,
  `incremental_date` date DEFAULT NULL,
  `employment_category` varchar(50) NOT NULL,
  `payroll_scheme` varchar(30) NOT NULL DEFAULT 'employee',
  `project_code` varchar(100) DEFAULT NULL,
  `employment_start_date` date NOT NULL,
  `contract_end_date` date DEFAULT NULL,
  `is_on_probation` tinyint(4) NOT NULL DEFAULT 0,
  `probation_end_date` date DEFAULT NULL,
  `confirmation_date` date DEFAULT NULL,
  `gross_monthly_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `allowances_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowances_json`)),
  `bank_id` bigint(20) unsigned DEFAULT NULL,
  `kra_pin` varchar(50) DEFAULT NULL,
  `nssf_number` varchar(50) DEFAULT NULL,
  `sha_number` varchar(50) DEFAULT NULL,
  `helb_number` varchar(50) DEFAULT NULL,
  `pension_scheme_id` bigint(20) unsigned DEFAULT NULL,
  `employment_status` varchar(50) NOT NULL DEFAULT 'active',
  `exit_date` date DEFAULT NULL,
  `exit_reason` varchar(500) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `onboarding_token` varchar(64) DEFAULT NULL,
  `onboarding_token_expires_at` datetime DEFAULT NULL,
  `is_teaching_staff` tinyint(4) NOT NULL DEFAULT 0,
  `is_profile_locked` tinyint(4) NOT NULL DEFAULT 0,
  `onboarding_completed_at` datetime DEFAULT NULL,
  `is_nursing_license_required` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_employee_number_unique` (`employee_number`),
  UNIQUE KEY `staff_organisation_email_unique` (`organisation_email`),
  UNIQUE KEY `staff_onboarding_token_unique` (`onboarding_token`),
  KEY `staff_department_id_index` (`department_id`),
  KEY `staff_employment_status_index` (`employment_status`),
  KEY `staff_national_id_number_index` (`national_id_number`),
  KEY `staff_pension_scheme_id_foreign` (`pension_scheme_id`),
  KEY `staff_bank_id_foreign` (`bank_id`),
  KEY `staff_user_id_foreign` (`user_id`),
  KEY `staff_campus_id_foreign` (`campus_id`),
  KEY `staff_line_manager_id_foreign` (`line_manager_id`),
  KEY `staff_department_employment_status_index` (`department_id`,`employment_status`),
  CONSTRAINT `staff_bank_id_foreign` FOREIGN KEY (`bank_id`) REFERENCES `staff_bank_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_campus_id_foreign` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `staff_line_manager_id_foreign` FOREIGN KEY (`line_manager_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_pension_scheme_id_foreign` FOREIGN KEY (`pension_scheme_id`) REFERENCES `pension_schemes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `staff` (add only if missing)
CALL `tich_ensure_column`('staff', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('staff', 'employee_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('staff', 'title', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'first_name', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('staff', 'middle_name', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'surname', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('staff', 'date_of_birth', 'date NOT NULL');
CALL `tich_ensure_column`('staff', 'gender', 'varchar(20) NOT NULL');
CALL `tich_ensure_column`('staff', 'marital_status', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'national_id_number', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'passport_number', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'nationality', 'varchar(100) NOT NULL DEFAULT \'\\\'Kenyan\\\'\'');
CALL `tich_ensure_column`('staff', 'home_county', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'primary_email', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('staff', 'organisation_email', 'varchar(255) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'phone_number', 'varchar(30) NOT NULL');
CALL `tich_ensure_column`('staff', 'alt_phone_number', 'varchar(30) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'postal_address', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'postal_code', 'varchar(20) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'physical_address', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'emergency_contact_name', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'emergency_contact_phone', 'varchar(30) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'emergency_contact_relationship', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'photo_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'department_id', 'bigint(20) unsigned DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'campus_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'line_manager_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'job_title', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('staff', 'job_grade', 'varchar(20) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'salary_scale', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'incremental_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'employment_category', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('staff', 'payroll_scheme', 'varchar(30) NOT NULL DEFAULT \'\\\'employee\\\'\'');
CALL `tich_ensure_column`('staff', 'project_code', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'employment_start_date', 'date NOT NULL');
CALL `tich_ensure_column`('staff', 'contract_end_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'is_on_probation', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('staff', 'probation_end_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'confirmation_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'gross_monthly_salary', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('staff', 'allowances_json', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'bank_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'kra_pin', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'nssf_number', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'sha_number', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'helb_number', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'pension_scheme_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'employment_status', 'varchar(50) NOT NULL DEFAULT \'\\\'active\\\'\'');
CALL `tich_ensure_column`('staff', 'exit_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'exit_reason', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'user_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'onboarding_token', 'varchar(64) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'onboarding_token_expires_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'is_teaching_staff', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('staff', 'is_profile_locked', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('staff', 'onboarding_completed_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'is_nursing_license_required', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('staff', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('staff', 'updated_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `staff` (add only if missing)
CALL `tich_ensure_index`('staff', 'staff_bank_id_foreign', '`bank_id`');
CALL `tich_ensure_index`('staff', 'staff_campus_id_foreign', '`campus_id`');
CALL `tich_ensure_index`('staff', 'staff_department_employment_status_index', '`department_id`, `employment_status`');
CALL `tich_ensure_index`('staff', 'staff_department_id_index', '`department_id`');
CALL `tich_ensure_unique`('staff', 'staff_employee_number_unique', '`employee_number`');
CALL `tich_ensure_index`('staff', 'staff_employment_status_index', '`employment_status`');
CALL `tich_ensure_index`('staff', 'staff_line_manager_id_foreign', '`line_manager_id`');
CALL `tich_ensure_index`('staff', 'staff_national_id_number_index', '`national_id_number`');
CALL `tich_ensure_unique`('staff', 'staff_onboarding_token_unique', '`onboarding_token`');
CALL `tich_ensure_unique`('staff', 'staff_organisation_email_unique', '`organisation_email`');
CALL `tich_ensure_index`('staff', 'staff_pension_scheme_id_foreign', '`pension_scheme_id`');
CALL `tich_ensure_index`('staff', 'staff_user_id_foreign', '`user_id`');

-- -----------------------------------------------------------------------------
-- Table: `staff_allowances`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_allowances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned NOT NULL,
  `allowance_type` varchar(50) NOT NULL,
  `allowance_name` varchar(200) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `effective_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `staff_allowances_approved_by_foreign` (`approved_by`),
  KEY `staff_allowances_staff_id_index` (`staff_id`),
  CONSTRAINT `staff_allowances_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_allowances_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `staff_allowances` (add only if missing)
CALL `tich_ensure_column`('staff_allowances', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('staff_allowances', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('staff_allowances', 'allowance_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('staff_allowances', 'allowance_name', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('staff_allowances', 'amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('staff_allowances', 'effective_date', 'date NOT NULL');
CALL `tich_ensure_column`('staff_allowances', 'end_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_allowances', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('staff_allowances', 'approved_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_allowances', 'approved_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_allowances', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `staff_allowances` (add only if missing)
CALL `tich_ensure_index`('staff_allowances', 'staff_allowances_approved_by_foreign', '`approved_by`');
CALL `tich_ensure_index`('staff_allowances', 'staff_allowances_staff_id_index', '`staff_id`');

-- -----------------------------------------------------------------------------
-- Table: `staff_attendance`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_attendance` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned NOT NULL,
  `attendance_date` date NOT NULL,
  `clock_in_time` time DEFAULT NULL,
  `clock_out_time` time DEFAULT NULL,
  `work_hours` decimal(5,2) DEFAULT NULL,
  `is_present` tinyint(4) NOT NULL DEFAULT 0,
  `is_leave_day` tinyint(4) NOT NULL DEFAULT 0,
  `leave_request_id` bigint(20) unsigned DEFAULT NULL,
  `is_off_campus` tinyint(4) NOT NULL DEFAULT 0,
  `field_project_name` varchar(300) DEFAULT NULL,
  `location_lat_long` varchar(100) DEFAULT NULL,
  `clock_in_latitude` decimal(10,7) DEFAULT NULL,
  `clock_in_longitude` decimal(10,7) DEFAULT NULL,
  `clock_in_accuracy_m` decimal(8,2) DEFAULT NULL,
  `location_verification_status` varchar(30) DEFAULT NULL,
  `hr_review_status` varchar(20) NOT NULL DEFAULT 'pending',
  `hr_reviewed_by_staff_id` bigint(20) unsigned DEFAULT NULL,
  `hr_reviewed_at` timestamp NULL DEFAULT NULL,
  `hr_review_notes` varchar(2000) DEFAULT NULL,
  `hr_rejection_reason` varchar(1000) DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_attendance_staff_id_attendance_date_unique` (`staff_id`,`attendance_date`),
  KEY `staff_attendance_leave_request_id_foreign` (`leave_request_id`),
  KEY `staff_attendance_recorded_by_foreign` (`recorded_by`),
  CONSTRAINT `staff_attendance_leave_request_id_foreign` FOREIGN KEY (`leave_request_id`) REFERENCES `leave_requests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_attendance_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_attendance_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `staff_attendance` (add only if missing)
CALL `tich_ensure_column`('staff_attendance', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('staff_attendance', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('staff_attendance', 'attendance_date', 'date NOT NULL');
CALL `tich_ensure_column`('staff_attendance', 'clock_in_time', 'time NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_attendance', 'clock_out_time', 'time NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_attendance', 'work_hours', 'decimal(5,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_attendance', 'is_present', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('staff_attendance', 'is_leave_day', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('staff_attendance', 'leave_request_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_attendance', 'is_off_campus', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('staff_attendance', 'field_project_name', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_attendance', 'location_lat_long', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_attendance', 'clock_in_latitude', 'decimal(10,7) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_attendance', 'clock_in_longitude', 'decimal(10,7) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_attendance', 'clock_in_accuracy_m', 'decimal(8,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_attendance', 'location_verification_status', 'varchar(30) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_attendance', 'hr_review_status', 'varchar(20) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('staff_attendance', 'hr_reviewed_by_staff_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_attendance', 'hr_reviewed_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_attendance', 'hr_review_notes', 'varchar(2000) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_attendance', 'hr_rejection_reason', 'varchar(1000) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_attendance', 'notes', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_attendance', 'recorded_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_attendance', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `staff_attendance` (add only if missing)
CALL `tich_ensure_index`('staff_attendance', 'staff_attendance_leave_request_id_foreign', '`leave_request_id`');
CALL `tich_ensure_index`('staff_attendance', 'staff_attendance_recorded_by_foreign', '`recorded_by`');
CALL `tich_ensure_unique`('staff_attendance', 'staff_attendance_staff_id_attendance_date_unique', '`staff_id`, `attendance_date`');

-- -----------------------------------------------------------------------------
-- Table: `staff_attendance_summary`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_attendance_summary` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `total_work_days` int(11) NOT NULL,
  `days_present` int(11) NOT NULL DEFAULT 0,
  `days_absent` int(11) NOT NULL DEFAULT 0,
  `days_on_leave` int(11) NOT NULL DEFAULT 0,
  `late_arrivals` int(11) NOT NULL DEFAULT 0,
  `early_departures` int(11) NOT NULL DEFAULT 0,
  `off_campus_days` int(11) NOT NULL DEFAULT 0,
  `overtime_hours` decimal(6,2) NOT NULL DEFAULT 0.00,
  `attendance_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `last_calculated_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_attendance_summary_staff_id_month_year_unique` (`staff_id`,`month`,`year`),
  CONSTRAINT `staff_attendance_summary_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `staff_attendance_summary` (add only if missing)
CALL `tich_ensure_column`('staff_attendance_summary', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('staff_attendance_summary', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('staff_attendance_summary', 'month', 'int(11) NOT NULL');
CALL `tich_ensure_column`('staff_attendance_summary', 'year', 'int(11) NOT NULL');
CALL `tich_ensure_column`('staff_attendance_summary', 'total_work_days', 'int(11) NOT NULL');
CALL `tich_ensure_column`('staff_attendance_summary', 'days_present', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('staff_attendance_summary', 'days_absent', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('staff_attendance_summary', 'days_on_leave', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('staff_attendance_summary', 'late_arrivals', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('staff_attendance_summary', 'early_departures', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('staff_attendance_summary', 'off_campus_days', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('staff_attendance_summary', 'overtime_hours', 'decimal(6,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('staff_attendance_summary', 'attendance_percentage', 'decimal(5,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('staff_attendance_summary', 'last_calculated_at', 'datetime NOT NULL');
CALL `tich_ensure_column`('staff_attendance_summary', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `staff_attendance_summary` (add only if missing)
CALL `tich_ensure_unique`('staff_attendance_summary', 'staff_attendance_summary_staff_id_month_year_unique', '`staff_id`, `month`, `year`');

-- -----------------------------------------------------------------------------
-- Table: `staff_bank_accounts`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_bank_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned NOT NULL,
  `account_name` varchar(300) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `bank_name` varchar(200) NOT NULL,
  `bank_branch` varchar(200) DEFAULT NULL,
  `bank_code` varchar(20) NOT NULL,
  `is_primary` tinyint(4) NOT NULL DEFAULT 1,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_bank_accounts_staff_id_bank_code_account_number_unique` (`staff_id`,`bank_code`,`account_number`),
  CONSTRAINT `staff_bank_accounts_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `staff_bank_accounts` (add only if missing)
CALL `tich_ensure_column`('staff_bank_accounts', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('staff_bank_accounts', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('staff_bank_accounts', 'account_name', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('staff_bank_accounts', 'account_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('staff_bank_accounts', 'bank_name', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('staff_bank_accounts', 'bank_branch', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_bank_accounts', 'bank_code', 'varchar(20) NOT NULL');
CALL `tich_ensure_column`('staff_bank_accounts', 'is_primary', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('staff_bank_accounts', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('staff_bank_accounts', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `staff_bank_accounts` (add only if missing)
CALL `tich_ensure_unique`('staff_bank_accounts', 'staff_bank_accounts_staff_id_bank_code_account_number_unique', '`staff_id`, `bank_code`, `account_number`');

-- -----------------------------------------------------------------------------
-- Table: `staff_contracts`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_contracts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `contract_number` varchar(50) NOT NULL,
  `staff_id` bigint(20) unsigned NOT NULL,
  `contract_type` varchar(50) NOT NULL,
  `job_title` varchar(200) NOT NULL,
  `job_grade` varchar(50) DEFAULT NULL,
  `payroll_scheme` varchar(50) DEFAULT NULL,
  `salary_scale` decimal(12,2) DEFAULT NULL,
  `line_manager_id` bigint(20) unsigned DEFAULT NULL,
  `organisation_email` varchar(255) DEFAULT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `campus_id` bigint(20) unsigned DEFAULT NULL,
  `gross_salary` decimal(12,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `is_renewable` tinyint(4) NOT NULL DEFAULT 0,
  `renewal_notice_sent` tinyint(4) NOT NULL DEFAULT 0,
  `renewal_notice_date` date DEFAULT NULL,
  `renewal_status` varchar(50) NOT NULL DEFAULT 'pending',
  `new_contract_id` bigint(20) unsigned DEFAULT NULL,
  `probation_end_date` date DEFAULT NULL,
  `probation_status` varchar(50) NOT NULL DEFAULT 'not_applicable',
  `contract_document_path` varchar(500) DEFAULT NULL,
  `is_signed` tinyint(4) NOT NULL DEFAULT 0,
  `signed_date` date DEFAULT NULL,
  `witnessed_by` varchar(200) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_contracts_contract_number_unique` (`contract_number`),
  KEY `staff_contracts_department_id_foreign` (`department_id`),
  KEY `staff_contracts_staff_id_index` (`staff_id`),
  KEY `staff_contracts_end_date_index` (`end_date`),
  KEY `staff_contracts_new_contract_id_foreign` (`new_contract_id`),
  KEY `staff_contracts_campus_id_index` (`campus_id`),
  KEY `staff_contracts_line_manager_id_index` (`line_manager_id`),
  CONSTRAINT `staff_contracts_campus_id_foreign` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_contracts_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `staff_contracts_line_manager_id_foreign` FOREIGN KEY (`line_manager_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_contracts_new_contract_id_foreign` FOREIGN KEY (`new_contract_id`) REFERENCES `staff_contracts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_contracts_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `staff_contracts` (add only if missing)
CALL `tich_ensure_column`('staff_contracts', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('staff_contracts', 'contract_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('staff_contracts', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('staff_contracts', 'contract_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('staff_contracts', 'job_title', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('staff_contracts', 'job_grade', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_contracts', 'payroll_scheme', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_contracts', 'salary_scale', 'decimal(12,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_contracts', 'line_manager_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_contracts', 'organisation_email', 'varchar(255) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_contracts', 'department_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('staff_contracts', 'campus_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_contracts', 'gross_salary', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('staff_contracts', 'start_date', 'date NOT NULL');
CALL `tich_ensure_column`('staff_contracts', 'end_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_contracts', 'is_renewable', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('staff_contracts', 'renewal_notice_sent', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('staff_contracts', 'renewal_notice_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_contracts', 'renewal_status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('staff_contracts', 'new_contract_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_contracts', 'probation_end_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_contracts', 'probation_status', 'varchar(50) NOT NULL DEFAULT \'\\\'not_applicable\\\'\'');
CALL `tich_ensure_column`('staff_contracts', 'contract_document_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_contracts', 'is_signed', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('staff_contracts', 'signed_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_contracts', 'witnessed_by', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_contracts', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('staff_contracts', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `staff_contracts` (add only if missing)
CALL `tich_ensure_index`('staff_contracts', 'staff_contracts_campus_id_index', '`campus_id`');
CALL `tich_ensure_unique`('staff_contracts', 'staff_contracts_contract_number_unique', '`contract_number`');
CALL `tich_ensure_index`('staff_contracts', 'staff_contracts_department_id_foreign', '`department_id`');
CALL `tich_ensure_index`('staff_contracts', 'staff_contracts_end_date_index', '`end_date`');
CALL `tich_ensure_index`('staff_contracts', 'staff_contracts_line_manager_id_index', '`line_manager_id`');
CALL `tich_ensure_index`('staff_contracts', 'staff_contracts_new_contract_id_foreign', '`new_contract_id`');
CALL `tich_ensure_index`('staff_contracts', 'staff_contracts_staff_id_index', '`staff_id`');

-- -----------------------------------------------------------------------------
-- Table: `staff_disciplinary_cases`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_disciplinary_cases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `case_number` varchar(50) NOT NULL,
  `staff_id` bigint(20) unsigned NOT NULL,
  `incident_date` date NOT NULL,
  `incident_description` text NOT NULL,
  `violation_type` varchar(50) NOT NULL,
  `reported_by` bigint(20) unsigned NOT NULL,
  `assigned_investigator_id` bigint(20) unsigned DEFAULT NULL,
  `case_status` varchar(50) NOT NULL DEFAULT 'open',
  `hearing_date` datetime DEFAULT NULL,
  `hearing_outcome` text DEFAULT NULL,
  `sanction_type` varchar(50) DEFAULT NULL,
  `sanction_start_date` date DEFAULT NULL,
  `sanction_end_date` date DEFAULT NULL,
  `staff_statement` text DEFAULT NULL,
  `witness_statements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`witness_statements`)),
  `decision_date` date DEFAULT NULL,
  `decision_by` bigint(20) unsigned DEFAULT NULL,
  `is_appeal` tinyint(4) NOT NULL DEFAULT 0,
  `appeal_outcome` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_disciplinary_cases_case_number_unique` (`case_number`),
  KEY `staff_disciplinary_cases_staff_id_foreign` (`staff_id`),
  KEY `staff_disciplinary_cases_reported_by_foreign` (`reported_by`),
  KEY `staff_disciplinary_cases_assigned_investigator_id_foreign` (`assigned_investigator_id`),
  KEY `staff_disciplinary_cases_decision_by_foreign` (`decision_by`),
  CONSTRAINT `staff_disciplinary_cases_assigned_investigator_id_foreign` FOREIGN KEY (`assigned_investigator_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_disciplinary_cases_decision_by_foreign` FOREIGN KEY (`decision_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_disciplinary_cases_reported_by_foreign` FOREIGN KEY (`reported_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `staff_disciplinary_cases_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `staff_disciplinary_cases` (add only if missing)
CALL `tich_ensure_column`('staff_disciplinary_cases', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('staff_disciplinary_cases', 'case_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('staff_disciplinary_cases', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('staff_disciplinary_cases', 'incident_date', 'date NOT NULL');
CALL `tich_ensure_column`('staff_disciplinary_cases', 'incident_description', 'text NOT NULL');
CALL `tich_ensure_column`('staff_disciplinary_cases', 'violation_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('staff_disciplinary_cases', 'reported_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('staff_disciplinary_cases', 'assigned_investigator_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_disciplinary_cases', 'case_status', 'varchar(50) NOT NULL DEFAULT \'\\\'open\\\'\'');
CALL `tich_ensure_column`('staff_disciplinary_cases', 'hearing_date', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_disciplinary_cases', 'hearing_outcome', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_disciplinary_cases', 'sanction_type', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_disciplinary_cases', 'sanction_start_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_disciplinary_cases', 'sanction_end_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_disciplinary_cases', 'staff_statement', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_disciplinary_cases', 'witness_statements', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_disciplinary_cases', 'decision_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_disciplinary_cases', 'decision_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_disciplinary_cases', 'is_appeal', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('staff_disciplinary_cases', 'appeal_outcome', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_disciplinary_cases', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('staff_disciplinary_cases', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `staff_disciplinary_cases` (add only if missing)
CALL `tich_ensure_index`('staff_disciplinary_cases', 'staff_disciplinary_cases_assigned_investigator_id_foreign', '`assigned_investigator_id`');
CALL `tich_ensure_unique`('staff_disciplinary_cases', 'staff_disciplinary_cases_case_number_unique', '`case_number`');
CALL `tich_ensure_index`('staff_disciplinary_cases', 'staff_disciplinary_cases_decision_by_foreign', '`decision_by`');
CALL `tich_ensure_index`('staff_disciplinary_cases', 'staff_disciplinary_cases_reported_by_foreign', '`reported_by`');
CALL `tich_ensure_index`('staff_disciplinary_cases', 'staff_disciplinary_cases_staff_id_foreign', '`staff_id`');

-- -----------------------------------------------------------------------------
-- Table: `staff_documents`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `document_name` varchar(300) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `is_verified` tinyint(4) NOT NULL DEFAULT 0,
  `status` varchar(50) DEFAULT 'pending',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `rejected_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `version` varchar(20) NOT NULL DEFAULT '1',
  `replaced_by_id` bigint(20) unsigned DEFAULT NULL,
  `is_missing` tinyint(4) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `staff_documents_verified_by_foreign` (`verified_by`),
  KEY `staff_documents_replaced_by_id_foreign` (`replaced_by_id`),
  KEY `staff_documents_staff_id_document_type_index` (`staff_id`,`document_type`),
  KEY `staff_documents_expiry_date_index` (`expiry_date`),
  KEY `staff_documents_approved_by_foreign` (`approved_by`),
  KEY `staff_documents_rejected_by_foreign` (`rejected_by`),
  CONSTRAINT `staff_documents_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_documents_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_documents_replaced_by_id_foreign` FOREIGN KEY (`replaced_by_id`) REFERENCES `staff_documents` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_documents_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`),
  CONSTRAINT `staff_documents_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `staff_documents` (add only if missing)
CALL `tich_ensure_column`('staff_documents', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('staff_documents', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('staff_documents', 'document_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('staff_documents', 'document_name', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('staff_documents', 'file_path', 'varchar(500) NOT NULL');
CALL `tich_ensure_column`('staff_documents', 'original_filename', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('staff_documents', 'mime_type', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('staff_documents', 'file_size', 'int(11) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_documents', 'issue_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_documents', 'expiry_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_documents', 'is_verified', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('staff_documents', 'status', 'varchar(50) NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('staff_documents', 'approved_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_documents', 'rejected_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_documents', 'approved_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_documents', 'rejected_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_documents', 'rejection_reason', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_documents', 'verified_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_documents', 'verified_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_documents', 'version', 'varchar(20) NOT NULL DEFAULT \'\\\'1\\\'\'');
CALL `tich_ensure_column`('staff_documents', 'replaced_by_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_documents', 'is_missing', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('staff_documents', 'notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_documents', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('staff_documents', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `staff_documents` (add only if missing)
CALL `tich_ensure_index`('staff_documents', 'staff_documents_approved_by_foreign', '`approved_by`');
CALL `tich_ensure_index`('staff_documents', 'staff_documents_expiry_date_index', '`expiry_date`');
CALL `tich_ensure_index`('staff_documents', 'staff_documents_rejected_by_foreign', '`rejected_by`');
CALL `tich_ensure_index`('staff_documents', 'staff_documents_replaced_by_id_foreign', '`replaced_by_id`');
CALL `tich_ensure_index`('staff_documents', 'staff_documents_staff_id_document_type_index', '`staff_id`, `document_type`');
CALL `tich_ensure_index`('staff_documents', 'staff_documents_verified_by_foreign', '`verified_by`');

-- -----------------------------------------------------------------------------
-- Table: `staff_document_templates`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_document_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `type` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `format` varchar(20) NOT NULL DEFAULT 'html' COMMENT 'html or docx',
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `staff_document_templates_created_by_foreign` (`created_by`),
  KEY `staff_document_templates_type_index` (`type`),
  CONSTRAINT `staff_document_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `staff_document_templates` (add only if missing)
CALL `tich_ensure_column`('staff_document_templates', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('staff_document_templates', 'name', 'varchar(200) NOT NULL');
CALL `tich_ensure_column`('staff_document_templates', 'type', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('staff_document_templates', 'content', 'text NOT NULL');
CALL `tich_ensure_column`('staff_document_templates', 'format', 'varchar(20) NOT NULL DEFAULT \'\\\'html\\\'\' COMMENT \'html or docx\'');
CALL `tich_ensure_column`('staff_document_templates', 'variables', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_document_templates', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('staff_document_templates', 'created_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('staff_document_templates', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('staff_document_templates', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `staff_document_templates` (add only if missing)
CALL `tich_ensure_index`('staff_document_templates', 'staff_document_templates_created_by_foreign', '`created_by`');
CALL `tich_ensure_index`('staff_document_templates', 'staff_document_templates_type_index', '`type`');

-- -----------------------------------------------------------------------------
-- Table: `staff_next_of_kin`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_next_of_kin` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned NOT NULL,
  `full_name` varchar(300) NOT NULL,
  `relationship` varchar(100) NOT NULL,
  `phone_number` varchar(30) NOT NULL,
  `alt_phone` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `physical_address` varchar(500) DEFAULT NULL,
  `occupation` varchar(200) DEFAULT NULL,
  `is_primary` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `staff_next_of_kin_staff_id_index` (`staff_id`),
  CONSTRAINT `staff_next_of_kin_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `staff_next_of_kin` (add only if missing)
CALL `tich_ensure_column`('staff_next_of_kin', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('staff_next_of_kin', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('staff_next_of_kin', 'full_name', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('staff_next_of_kin', 'relationship', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('staff_next_of_kin', 'phone_number', 'varchar(30) NOT NULL');
CALL `tich_ensure_column`('staff_next_of_kin', 'alt_phone', 'varchar(30) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_next_of_kin', 'email', 'varchar(255) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_next_of_kin', 'physical_address', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_next_of_kin', 'occupation', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_next_of_kin', 'is_primary', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('staff_next_of_kin', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `staff_next_of_kin` (add only if missing)
CALL `tich_ensure_index`('staff_next_of_kin', 'staff_next_of_kin_staff_id_index', '`staff_id`');

-- -----------------------------------------------------------------------------
-- Table: `staff_onboarding`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_onboarding` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned DEFAULT NULL,
  `applicant_id` bigint(20) unsigned DEFAULT NULL,
  `onboarding_number` varchar(50) NOT NULL,
  `current_step` varchar(50) NOT NULL DEFAULT 'biodata',
  `status` varchar(50) NOT NULL DEFAULT 'in_progress',
  `rejection_reason` text DEFAULT NULL,
  `completed_steps` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`completed_steps`)),
  `missing_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`missing_documents`)),
  `is_biodata_locked` tinyint(4) NOT NULL DEFAULT 0,
  `locked_by` bigint(20) unsigned DEFAULT NULL,
  `locked_at` datetime DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_onboarding_onboarding_number_unique` (`onboarding_number`),
  KEY `staff_onboarding_staff_id_foreign` (`staff_id`),
  KEY `staff_onboarding_applicant_id_foreign` (`applicant_id`),
  KEY `staff_onboarding_locked_by_foreign` (`locked_by`),
  KEY `staff_onboarding_reviewed_by_foreign` (`reviewed_by`),
  KEY `staff_onboarding_status_index` (`status`),
  KEY `staff_onboarding_current_step_index` (`current_step`),
  CONSTRAINT `staff_onboarding_applicant_id_foreign` FOREIGN KEY (`applicant_id`) REFERENCES `recruitment_applications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_onboarding_locked_by_foreign` FOREIGN KEY (`locked_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_onboarding_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_onboarding_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `staff_onboarding` (add only if missing)
CALL `tich_ensure_column`('staff_onboarding', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('staff_onboarding', 'staff_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_onboarding', 'applicant_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_onboarding', 'onboarding_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('staff_onboarding', 'current_step', 'varchar(50) NOT NULL DEFAULT \'\\\'biodata\\\'\'');
CALL `tich_ensure_column`('staff_onboarding', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'in_progress\\\'\'');
CALL `tich_ensure_column`('staff_onboarding', 'rejection_reason', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_onboarding', 'completed_steps', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_onboarding', 'missing_documents', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_onboarding', 'is_biodata_locked', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('staff_onboarding', 'locked_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_onboarding', 'locked_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_onboarding', 'reviewed_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_onboarding', 'reviewed_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_onboarding', 'completed_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_onboarding', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('staff_onboarding', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `staff_onboarding` (add only if missing)
CALL `tich_ensure_index`('staff_onboarding', 'staff_onboarding_applicant_id_foreign', '`applicant_id`');
CALL `tich_ensure_index`('staff_onboarding', 'staff_onboarding_current_step_index', '`current_step`');
CALL `tich_ensure_index`('staff_onboarding', 'staff_onboarding_locked_by_foreign', '`locked_by`');
CALL `tich_ensure_unique`('staff_onboarding', 'staff_onboarding_onboarding_number_unique', '`onboarding_number`');
CALL `tich_ensure_index`('staff_onboarding', 'staff_onboarding_reviewed_by_foreign', '`reviewed_by`');
CALL `tich_ensure_index`('staff_onboarding', 'staff_onboarding_staff_id_foreign', '`staff_id`');
CALL `tich_ensure_index`('staff_onboarding', 'staff_onboarding_status_index', '`status`');

-- -----------------------------------------------------------------------------
-- Table: `staff_professional_licenses`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_professional_licenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned NOT NULL,
  `license_type` varchar(50) NOT NULL,
  `issuing_body` varchar(300) NOT NULL,
  `license_number` varchar(100) NOT NULL,
  `issue_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `is_expired` tinyint(4) NOT NULL DEFAULT 0,
  `days_to_expiry` int(11) DEFAULT NULL,
  `alert_sent_30_days` tinyint(4) NOT NULL DEFAULT 0,
  `alert_sent_60_days` tinyint(4) NOT NULL DEFAULT 0,
  `document_path` varchar(500) DEFAULT NULL,
  `is_verified` tinyint(4) NOT NULL DEFAULT 0,
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `staff_professional_licenses_staff_id_foreign` (`staff_id`),
  KEY `staff_professional_licenses_verified_by_foreign` (`verified_by`),
  KEY `staff_professional_licenses_expiry_date_index` (`expiry_date`),
  CONSTRAINT `staff_professional_licenses_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`),
  CONSTRAINT `staff_professional_licenses_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `staff_professional_licenses` (add only if missing)
CALL `tich_ensure_column`('staff_professional_licenses', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('staff_professional_licenses', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('staff_professional_licenses', 'license_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('staff_professional_licenses', 'issuing_body', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('staff_professional_licenses', 'license_number', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('staff_professional_licenses', 'issue_date', 'date NOT NULL');
CALL `tich_ensure_column`('staff_professional_licenses', 'expiry_date', 'date NOT NULL');
CALL `tich_ensure_column`('staff_professional_licenses', 'is_expired', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('staff_professional_licenses', 'days_to_expiry', 'int(11) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_professional_licenses', 'alert_sent_30_days', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('staff_professional_licenses', 'alert_sent_60_days', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('staff_professional_licenses', 'document_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_professional_licenses', 'is_verified', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('staff_professional_licenses', 'verified_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_professional_licenses', 'verified_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_professional_licenses', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('staff_professional_licenses', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `staff_professional_licenses` (add only if missing)
CALL `tich_ensure_index`('staff_professional_licenses', 'staff_professional_licenses_expiry_date_index', '`expiry_date`');
CALL `tich_ensure_index`('staff_professional_licenses', 'staff_professional_licenses_staff_id_foreign', '`staff_id`');
CALL `tich_ensure_index`('staff_professional_licenses', 'staff_professional_licenses_verified_by_foreign', '`verified_by`');

-- -----------------------------------------------------------------------------
-- Table: `staff_profile_change_requests`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_profile_change_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned NOT NULL,
  `requested_by_user_id` bigint(20) unsigned NOT NULL,
  `request_type` varchar(50) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `current_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`current_snapshot`)),
  `proposed_changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`proposed_changes`)),
  `attachment_path` varchar(500) DEFAULT NULL,
  `employee_notes` text DEFAULT NULL,
  `hr_notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `reviewed_by_staff_id` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_profile_change_requests_requested_by_user_id_foreign` (`requested_by_user_id`),
  KEY `staff_profile_change_requests_reviewed_by_staff_id_foreign` (`reviewed_by_staff_id`),
  KEY `staff_profile_change_requests_staff_id_status_index` (`staff_id`,`status`),
  KEY `staff_profile_change_requests_status_created_at_index` (`status`,`created_at`),
  CONSTRAINT `staff_profile_change_requests_requested_by_user_id_foreign` FOREIGN KEY (`requested_by_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `staff_profile_change_requests_reviewed_by_staff_id_foreign` FOREIGN KEY (`reviewed_by_staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_profile_change_requests_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `staff_profile_change_requests` (add only if missing)
CALL `tich_ensure_column`('staff_profile_change_requests', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('staff_profile_change_requests', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('staff_profile_change_requests', 'requested_by_user_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('staff_profile_change_requests', 'request_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('staff_profile_change_requests', 'status', 'varchar(30) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('staff_profile_change_requests', 'current_snapshot', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_profile_change_requests', 'proposed_changes', 'longtext NOT NULL');
CALL `tich_ensure_column`('staff_profile_change_requests', 'attachment_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_profile_change_requests', 'employee_notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_profile_change_requests', 'hr_notes', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_profile_change_requests', 'rejection_reason', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_profile_change_requests', 'reviewed_by_staff_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_profile_change_requests', 'reviewed_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_profile_change_requests', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_profile_change_requests', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `staff_profile_change_requests` (add only if missing)
CALL `tich_ensure_index`('staff_profile_change_requests', 'staff_profile_change_requests_requested_by_user_id_foreign', '`requested_by_user_id`');
CALL `tich_ensure_index`('staff_profile_change_requests', 'staff_profile_change_requests_reviewed_by_staff_id_foreign', '`reviewed_by_staff_id`');
CALL `tich_ensure_index`('staff_profile_change_requests', 'staff_profile_change_requests_staff_id_status_index', '`staff_id`, `status`');
CALL `tich_ensure_index`('staff_profile_change_requests', 'staff_profile_change_requests_status_created_at_index', '`status`, `created_at`');

-- -----------------------------------------------------------------------------
-- Table: `staff_qualifications`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_qualifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned NOT NULL,
  `qualification_type` varchar(50) NOT NULL,
  `qualification_name` varchar(300) NOT NULL,
  `institution` varchar(300) NOT NULL,
  `country` varchar(100) NOT NULL DEFAULT 'Kenya',
  `year_completed` int(11) NOT NULL,
  `grade_or_class` varchar(50) DEFAULT NULL,
  `certificate_number` varchar(50) DEFAULT NULL,
  `document_path` varchar(500) DEFAULT NULL,
  `is_verified` tinyint(4) NOT NULL DEFAULT 0,
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `staff_qualifications_staff_id_foreign` (`staff_id`),
  KEY `staff_qualifications_verified_by_foreign` (`verified_by`),
  CONSTRAINT `staff_qualifications_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`),
  CONSTRAINT `staff_qualifications_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `staff_qualifications` (add only if missing)
CALL `tich_ensure_column`('staff_qualifications', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('staff_qualifications', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('staff_qualifications', 'qualification_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('staff_qualifications', 'qualification_name', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('staff_qualifications', 'institution', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('staff_qualifications', 'country', 'varchar(100) NOT NULL DEFAULT \'\\\'Kenya\\\'\'');
CALL `tich_ensure_column`('staff_qualifications', 'year_completed', 'int(11) NOT NULL');
CALL `tich_ensure_column`('staff_qualifications', 'grade_or_class', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_qualifications', 'certificate_number', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_qualifications', 'document_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_qualifications', 'is_verified', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('staff_qualifications', 'verified_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_qualifications', 'verified_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_qualifications', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `staff_qualifications` (add only if missing)
CALL `tich_ensure_index`('staff_qualifications', 'staff_qualifications_staff_id_foreign', '`staff_id`');
CALL `tich_ensure_index`('staff_qualifications', 'staff_qualifications_verified_by_foreign', '`verified_by`');

-- -----------------------------------------------------------------------------
-- Table: `staff_status_history`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_status_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned NOT NULL,
  `change_type` varchar(50) NOT NULL,
  `previous_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approval_reference` varchar(100) DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `staff_status_history_approved_by_foreign` (`approved_by`),
  KEY `staff_status_history_staff_id_change_type_index` (`staff_id`,`change_type`),
  KEY `staff_status_history_effective_date_index` (`effective_date`),
  CONSTRAINT `staff_status_history_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `staff_status_history_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `staff_status_history` (add only if missing)
CALL `tich_ensure_column`('staff_status_history', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('staff_status_history', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('staff_status_history', 'change_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('staff_status_history', 'previous_status', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_status_history', 'new_status', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_status_history', 'reason', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_status_history', 'metadata', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_status_history', 'approved_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_status_history', 'approval_reference', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_status_history', 'effective_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('staff_status_history', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `staff_status_history` (add only if missing)
CALL `tich_ensure_index`('staff_status_history', 'staff_status_history_approved_by_foreign', '`approved_by`');
CALL `tich_ensure_index`('staff_status_history', 'staff_status_history_effective_date_index', '`effective_date`');
CALL `tich_ensure_index`('staff_status_history', 'staff_status_history_staff_id_change_type_index', '`staff_id`, `change_type`');

-- -----------------------------------------------------------------------------
-- Table: `statutory_deductions`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `statutory_deductions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payroll_item_id` bigint(20) unsigned NOT NULL,
  `staff_id` bigint(20) unsigned NOT NULL,
  `deduction_type` varchar(50) NOT NULL,
  `gross_salary_for_deduction` decimal(12,2) NOT NULL,
  `deduction_rate` decimal(5,2) DEFAULT NULL,
  `employer_contribution` decimal(12,2) NOT NULL DEFAULT 0.00,
  `employee_amount` decimal(12,2) NOT NULL,
  `employer_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `is_remitted` tinyint(4) NOT NULL DEFAULT 0,
  `remittance_date` date DEFAULT NULL,
  `remittance_reference` varchar(100) DEFAULT NULL,
  `kra_reference` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `statutory_deductions_payroll_item_id_foreign` (`payroll_item_id`),
  KEY `statutory_deductions_staff_id_foreign` (`staff_id`),
  CONSTRAINT `statutory_deductions_payroll_item_id_foreign` FOREIGN KEY (`payroll_item_id`) REFERENCES `payroll_items` (`id`),
  CONSTRAINT `statutory_deductions_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `statutory_deductions` (add only if missing)
CALL `tich_ensure_column`('statutory_deductions', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('statutory_deductions', 'payroll_item_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('statutory_deductions', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('statutory_deductions', 'deduction_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('statutory_deductions', 'gross_salary_for_deduction', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('statutory_deductions', 'deduction_rate', 'decimal(5,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('statutory_deductions', 'employer_contribution', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('statutory_deductions', 'employee_amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('statutory_deductions', 'employer_amount', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('statutory_deductions', 'is_remitted', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('statutory_deductions', 'remittance_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('statutory_deductions', 'remittance_reference', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('statutory_deductions', 'kra_reference', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('statutory_deductions', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `statutory_deductions` (add only if missing)
CALL `tich_ensure_index`('statutory_deductions', 'statutory_deductions_payroll_item_id_foreign', '`payroll_item_id`');
CALL `tich_ensure_index`('statutory_deductions', 'statutory_deductions_staff_id_foreign', '`staff_id`');

-- -----------------------------------------------------------------------------
-- Table: `students`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `students` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `registration_number` varchar(50) NOT NULL,
  `application_id` bigint(20) unsigned NOT NULL,
  `program_id` bigint(20) unsigned NOT NULL,
  `cohort_intake` varchar(20) NOT NULL,
  `enrollment_campus_id` bigint(20) unsigned NOT NULL,
  `current_semester_id` bigint(20) unsigned DEFAULT NULL,
  `current_nursing_block_id` bigint(20) unsigned DEFAULT NULL,
  `enrollment_status` varchar(50) NOT NULL DEFAULT 'pending',
  `entry_pathway` varchar(50) NOT NULL DEFAULT 'regular',
  `admission_letter_id` bigint(20) unsigned DEFAULT NULL,
  `photo_path` varchar(500) DEFAULT NULL,
  `date_of_admission` date NOT NULL,
  `is_nursing_student` tinyint(4) NOT NULL DEFAULT 0,
  `kcse_english_grade` varchar(5) DEFAULT NULL,
  `kcse_biology_grade` varchar(5) DEFAULT NULL,
  `kcse_science_grade` varchar(5) DEFAULT NULL,
  `fee_clearance_status` varchar(50) NOT NULL DEFAULT 'pending',
  `academic_clearance_status` varchar(50) NOT NULL DEFAULT 'pending',
  `academically_cleared_at` datetime DEFAULT NULL,
  `academically_cleared_by` bigint(20) unsigned DEFAULT NULL,
  `overall_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `portal_invite_token` varchar(64) DEFAULT NULL,
  `portal_invite_expires_at` datetime DEFAULT NULL,
  `portal_activated_at` datetime DEFAULT NULL,
  `is_hostel_seeker` tinyint(4) NOT NULL DEFAULT 0,
  `hostel_allocation_id` bigint(20) unsigned DEFAULT NULL,
  `emergency_contact_name` varchar(300) DEFAULT NULL,
  `emergency_contact_phone` varchar(30) DEFAULT NULL,
  `emergency_contact_relationship` varchar(50) DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `students_registration_number_unique` (`registration_number`),
  UNIQUE KEY `students_portal_invite_token_unique` (`portal_invite_token`),
  KEY `students_application_id_foreign` (`application_id`),
  KEY `students_enrollment_campus_id_foreign` (`enrollment_campus_id`),
  KEY `students_current_semester_id_foreign` (`current_semester_id`),
  KEY `students_current_nursing_block_id_foreign` (`current_nursing_block_id`),
  KEY `students_user_id_foreign` (`user_id`),
  KEY `students_admission_letter_id_foreign` (`admission_letter_id`),
  KEY `students_enrollment_status_index` (`enrollment_status`),
  KEY `students_program_enrollment_status_index` (`program_id`,`enrollment_status`),
  CONSTRAINT `students_admission_letter_id_foreign` FOREIGN KEY (`admission_letter_id`) REFERENCES `admission_letters` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applicants` (`id`),
  CONSTRAINT `students_current_nursing_block_id_foreign` FOREIGN KEY (`current_nursing_block_id`) REFERENCES `nursing_blocks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_current_semester_id_foreign` FOREIGN KEY (`current_semester_id`) REFERENCES `semesters` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_enrollment_campus_id_foreign` FOREIGN KEY (`enrollment_campus_id`) REFERENCES `campuses` (`id`),
  CONSTRAINT `students_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `academic_programs` (`id`),
  CONSTRAINT `students_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `students` (add only if missing)
CALL `tich_ensure_column`('students', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('students', 'registration_number', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('students', 'application_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('students', 'program_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('students', 'cohort_intake', 'varchar(20) NOT NULL');
CALL `tich_ensure_column`('students', 'enrollment_campus_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('students', 'current_semester_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('students', 'current_nursing_block_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('students', 'enrollment_status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('students', 'entry_pathway', 'varchar(50) NOT NULL DEFAULT \'\\\'regular\\\'\'');
CALL `tich_ensure_column`('students', 'admission_letter_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('students', 'photo_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('students', 'date_of_admission', 'date NOT NULL');
CALL `tich_ensure_column`('students', 'is_nursing_student', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('students', 'kcse_english_grade', 'varchar(5) NULL DEFAULT NULL');
CALL `tich_ensure_column`('students', 'kcse_biology_grade', 'varchar(5) NULL DEFAULT NULL');
CALL `tich_ensure_column`('students', 'kcse_science_grade', 'varchar(5) NULL DEFAULT NULL');
CALL `tich_ensure_column`('students', 'fee_clearance_status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('students', 'academic_clearance_status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('students', 'academically_cleared_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('students', 'academically_cleared_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('students', 'overall_balance', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('students', 'user_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('students', 'portal_invite_token', 'varchar(64) NULL DEFAULT NULL');
CALL `tich_ensure_column`('students', 'portal_invite_expires_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('students', 'portal_activated_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('students', 'is_hostel_seeker', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('students', 'hostel_allocation_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('students', 'emergency_contact_name', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('students', 'emergency_contact_phone', 'varchar(30) NULL DEFAULT NULL');
CALL `tich_ensure_column`('students', 'emergency_contact_relationship', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('students', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('students', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('students', 'updated_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('students', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `students` (add only if missing)
CALL `tich_ensure_index`('students', 'students_admission_letter_id_foreign', '`admission_letter_id`');
CALL `tich_ensure_index`('students', 'students_application_id_foreign', '`application_id`');
CALL `tich_ensure_index`('students', 'students_current_nursing_block_id_foreign', '`current_nursing_block_id`');
CALL `tich_ensure_index`('students', 'students_current_semester_id_foreign', '`current_semester_id`');
CALL `tich_ensure_index`('students', 'students_enrollment_campus_id_foreign', '`enrollment_campus_id`');
CALL `tich_ensure_index`('students', 'students_enrollment_status_index', '`enrollment_status`');
CALL `tich_ensure_unique`('students', 'students_portal_invite_token_unique', '`portal_invite_token`');
CALL `tich_ensure_index`('students', 'students_program_enrollment_status_index', '`program_id`, `enrollment_status`');
CALL `tich_ensure_unique`('students', 'students_registration_number_unique', '`registration_number`');
CALL `tich_ensure_index`('students', 'students_user_id_foreign', '`user_id`');

-- -----------------------------------------------------------------------------
-- Table: `student_accounts`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `academic_year_id` bigint(20) unsigned NOT NULL,
  `total_chargeable` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `outstanding_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `work_study_credit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `scholarship_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `helb_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sponsor_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `credit_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `is_cleared` tinyint(4) NOT NULL DEFAULT 0,
  `cleared_at` datetime DEFAULT NULL,
  `last_payment_date` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_accounts_student_id_academic_year_id_unique` (`student_id`,`academic_year_id`),
  KEY `student_accounts_academic_year_id_foreign` (`academic_year_id`),
  CONSTRAINT `student_accounts_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`),
  CONSTRAINT `student_accounts_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `student_accounts` (add only if missing)
CALL `tich_ensure_column`('student_accounts', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('student_accounts', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('student_accounts', 'academic_year_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('student_accounts', 'total_chargeable', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('student_accounts', 'total_paid', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('student_accounts', 'outstanding_balance', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('student_accounts', 'work_study_credit', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('student_accounts', 'scholarship_amount', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('student_accounts', 'helb_amount', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('student_accounts', 'sponsor_amount', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('student_accounts', 'credit_balance', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('student_accounts', 'is_cleared', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('student_accounts', 'cleared_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('student_accounts', 'last_payment_date', 'date NULL DEFAULT NULL');
CALL `tich_ensure_column`('student_accounts', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('student_accounts', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `student_accounts` (add only if missing)
CALL `tich_ensure_index`('student_accounts', 'student_accounts_academic_year_id_foreign', '`academic_year_id`');
CALL `tich_ensure_unique`('student_accounts', 'student_accounts_student_id_academic_year_id_unique', '`student_id`, `academic_year_id`');

-- -----------------------------------------------------------------------------
-- Table: `student_addresses`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_addresses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `address_type` varchar(50) NOT NULL,
  `postal_address` varchar(300) DEFAULT NULL,
  `physical_address` varchar(500) DEFAULT NULL,
  `county` varchar(100) DEFAULT NULL,
  `sub_county` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_addresses_student_id_foreign` (`student_id`),
  CONSTRAINT `student_addresses_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `student_addresses` (add only if missing)
CALL `tich_ensure_column`('student_addresses', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('student_addresses', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('student_addresses', 'address_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('student_addresses', 'postal_address', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('student_addresses', 'physical_address', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('student_addresses', 'county', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('student_addresses', 'sub_county', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('student_addresses', 'phone', 'varchar(30) NULL DEFAULT NULL');
CALL `tich_ensure_column`('student_addresses', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('student_addresses', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `student_addresses` (add only if missing)
CALL `tich_ensure_index`('student_addresses', 'student_addresses_student_id_foreign', '`student_id`');

-- -----------------------------------------------------------------------------
-- Table: `student_next_of_kin`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_next_of_kin` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `full_name` varchar(300) NOT NULL,
  `relationship` varchar(100) NOT NULL,
  `phone_number` varchar(30) NOT NULL,
  `alt_phone` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `physical_address` varchar(500) DEFAULT NULL,
  `occupation` varchar(200) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_next_of_kin_student_id_foreign` (`student_id`),
  CONSTRAINT `student_next_of_kin_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `student_next_of_kin` (add only if missing)
CALL `tich_ensure_column`('student_next_of_kin', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('student_next_of_kin', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('student_next_of_kin', 'full_name', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('student_next_of_kin', 'relationship', 'varchar(100) NOT NULL');
CALL `tich_ensure_column`('student_next_of_kin', 'phone_number', 'varchar(30) NOT NULL');
CALL `tich_ensure_column`('student_next_of_kin', 'alt_phone', 'varchar(30) NULL DEFAULT NULL');
CALL `tich_ensure_column`('student_next_of_kin', 'email', 'varchar(255) NULL DEFAULT NULL');
CALL `tich_ensure_column`('student_next_of_kin', 'physical_address', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('student_next_of_kin', 'occupation', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('student_next_of_kin', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('student_next_of_kin', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `student_next_of_kin` (add only if missing)
CALL `tich_ensure_index`('student_next_of_kin', 'student_next_of_kin_student_id_foreign', '`student_id`');

-- -----------------------------------------------------------------------------
-- Table: `student_semester_registrations`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_semester_registrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `semester_id` bigint(20) unsigned NOT NULL,
  `registration_date` date NOT NULL,
  `registered_by` bigint(20) unsigned DEFAULT NULL,
  `registration_type` varchar(50) NOT NULL DEFAULT 'self',
  `unit_count` int(11) NOT NULL DEFAULT 0,
  `status` varchar(50) NOT NULL DEFAULT 'registered',
  `is_fee_cleared` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sem_reg_unique` (`student_id`,`semester_id`),
  KEY `student_semester_registrations_semester_id_foreign` (`semester_id`),
  KEY `student_semester_registrations_registered_by_foreign` (`registered_by`),
  CONSTRAINT `student_semester_registrations_registered_by_foreign` FOREIGN KEY (`registered_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_semester_registrations_semester_id_foreign` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`),
  CONSTRAINT `student_semester_registrations_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `student_semester_registrations` (add only if missing)
CALL `tich_ensure_column`('student_semester_registrations', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('student_semester_registrations', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('student_semester_registrations', 'semester_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('student_semester_registrations', 'registration_date', 'date NOT NULL');
CALL `tich_ensure_column`('student_semester_registrations', 'registered_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('student_semester_registrations', 'registration_type', 'varchar(50) NOT NULL DEFAULT \'\\\'self\\\'\'');
CALL `tich_ensure_column`('student_semester_registrations', 'unit_count', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('student_semester_registrations', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'registered\\\'\'');
CALL `tich_ensure_column`('student_semester_registrations', 'is_fee_cleared', 'tinyint(4) NOT NULL DEFAULT \'0\'');

-- Indexes for `student_semester_registrations` (add only if missing)
CALL `tich_ensure_unique`('student_semester_registrations', 'sem_reg_unique', '`student_id`, `semester_id`');
CALL `tich_ensure_index`('student_semester_registrations', 'student_semester_registrations_registered_by_foreign', '`registered_by`');
CALL `tich_ensure_index`('student_semester_registrations', 'student_semester_registrations_semester_id_foreign', '`semester_id`');

-- -----------------------------------------------------------------------------
-- Table: `student_suggestions`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_suggestions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `category` varchar(40) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` text NOT NULL,
  `status` enum('open','under_review','resolved','closed') NOT NULL DEFAULT 'open',
  `response` text DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_suggestions_reviewed_by_foreign` (`reviewed_by`),
  KEY `student_suggestions_student_id_status_index` (`student_id`,`status`),
  KEY `student_suggestions_status_created_at_index` (`status`,`created_at`),
  CONSTRAINT `student_suggestions_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_suggestions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `student_suggestions` (add only if missing)
CALL `tich_ensure_column`('student_suggestions', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('student_suggestions', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('student_suggestions', 'category', 'varchar(40) NOT NULL');
CALL `tich_ensure_column`('student_suggestions', 'subject', 'varchar(255) NULL DEFAULT NULL');
CALL `tich_ensure_column`('student_suggestions', 'body', 'text NOT NULL');
CALL `tich_ensure_column`('student_suggestions', 'status', 'enum(\'open\',\'under_review\',\'resolved\',\'closed\') NOT NULL DEFAULT \'\\\'open\\\'\'');
CALL `tich_ensure_column`('student_suggestions', 'response', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('student_suggestions', 'reviewed_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('student_suggestions', 'resolved_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('student_suggestions', 'metadata', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('student_suggestions', 'created_at', 'timestamp NULL DEFAULT NULL');
CALL `tich_ensure_column`('student_suggestions', 'updated_at', 'timestamp NULL DEFAULT NULL');

-- Indexes for `student_suggestions` (add only if missing)
CALL `tich_ensure_index`('student_suggestions', 'student_suggestions_reviewed_by_foreign', '`reviewed_by`');
CALL `tich_ensure_index`('student_suggestions', 'student_suggestions_status_created_at_index', '`status`, `created_at`');
CALL `tich_ensure_index`('student_suggestions', 'student_suggestions_student_id_status_index', '`student_id`, `status`');

-- -----------------------------------------------------------------------------
-- Table: `supplementary_requests`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `supplementary_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `exam_result_id` bigint(20) unsigned NOT NULL,
  `supplementary_type` varchar(50) NOT NULL,
  `fee_amount` decimal(12,2) NOT NULL,
  `fee_paid` tinyint(4) NOT NULL DEFAULT 0,
  `fee_payment_ref` varchar(100) DEFAULT NULL,
  `fee_paid_at` datetime DEFAULT NULL,
  `application_status` varchar(50) NOT NULL DEFAULT 'pending_fee',
  `scheduled_exam_id` bigint(20) unsigned DEFAULT NULL,
  `new_score` decimal(5,2) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `supplementary_requests_student_id_foreign` (`student_id`),
  KEY `supplementary_requests_exam_result_id_foreign` (`exam_result_id`),
  KEY `supplementary_requests_scheduled_exam_id_foreign` (`scheduled_exam_id`),
  CONSTRAINT `supplementary_requests_exam_result_id_foreign` FOREIGN KEY (`exam_result_id`) REFERENCES `exam_results` (`id`),
  CONSTRAINT `supplementary_requests_scheduled_exam_id_foreign` FOREIGN KEY (`scheduled_exam_id`) REFERENCES `exam_schedules` (`id`) ON DELETE SET NULL,
  CONSTRAINT `supplementary_requests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `supplementary_requests` (add only if missing)
CALL `tich_ensure_column`('supplementary_requests', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('supplementary_requests', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('supplementary_requests', 'exam_result_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('supplementary_requests', 'supplementary_type', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('supplementary_requests', 'fee_amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('supplementary_requests', 'fee_paid', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('supplementary_requests', 'fee_payment_ref', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('supplementary_requests', 'fee_paid_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('supplementary_requests', 'application_status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending_fee\\\'\'');
CALL `tich_ensure_column`('supplementary_requests', 'scheduled_exam_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('supplementary_requests', 'new_score', 'decimal(5,2) NULL DEFAULT NULL');
CALL `tich_ensure_column`('supplementary_requests', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `supplementary_requests` (add only if missing)
CALL `tich_ensure_index`('supplementary_requests', 'supplementary_requests_exam_result_id_foreign', '`exam_result_id`');
CALL `tich_ensure_index`('supplementary_requests', 'supplementary_requests_scheduled_exam_id_foreign', '`scheduled_exam_id`');
CALL `tich_ensure_index`('supplementary_requests', 'supplementary_requests_student_id_foreign', '`student_id`');

-- -----------------------------------------------------------------------------
-- Table: `suppliers`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_code` varchar(30) NOT NULL,
  `supplier_name` varchar(300) NOT NULL,
  `contact_person` varchar(200) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `postal_address` varchar(300) DEFAULT NULL,
  `physical_address` varchar(500) DEFAULT NULL,
  `kra_pin` varchar(50) DEFAULT NULL,
  `tax_compliance_status` varchar(50) NOT NULL DEFAULT 'pending_review',
  `compliance_doc_path` varchar(500) DEFAULT NULL,
  `bank_name` varchar(200) DEFAULT NULL,
  `bank_account_name` varchar(300) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `bank_branch` varchar(200) DEFAULT NULL,
  `bank_code` varchar(20) DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `suppliers_supplier_code_unique` (`supplier_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `suppliers` (add only if missing)
CALL `tich_ensure_column`('suppliers', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('suppliers', 'supplier_code', 'varchar(30) NOT NULL');
CALL `tich_ensure_column`('suppliers', 'supplier_name', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('suppliers', 'contact_person', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('suppliers', 'email', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('suppliers', 'phone', 'varchar(30) NOT NULL');
CALL `tich_ensure_column`('suppliers', 'postal_address', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('suppliers', 'physical_address', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('suppliers', 'kra_pin', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('suppliers', 'tax_compliance_status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending_review\\\'\'');
CALL `tich_ensure_column`('suppliers', 'compliance_doc_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('suppliers', 'bank_name', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('suppliers', 'bank_account_name', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('suppliers', 'bank_account_number', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('suppliers', 'bank_branch', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('suppliers', 'bank_code', 'varchar(20) NULL DEFAULT NULL');
CALL `tich_ensure_column`('suppliers', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('suppliers', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `suppliers` (add only if missing)
CALL `tich_ensure_unique`('suppliers', 'suppliers_supplier_code_unique', '`supplier_code`');

-- -----------------------------------------------------------------------------
-- Table: `testimonials`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `testimonials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `author_name` varchar(300) NOT NULL,
  `author_role` varchar(50) NOT NULL,
  `author_program` varchar(300) DEFAULT NULL,
  `quote` text NOT NULL,
  `photo_path` varchar(500) DEFAULT NULL,
  `is_featured` tinyint(4) NOT NULL DEFAULT 0,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `consented` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `testimonials` (add only if missing)
CALL `tich_ensure_column`('testimonials', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('testimonials', 'author_name', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('testimonials', 'author_role', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('testimonials', 'author_program', 'varchar(300) NULL DEFAULT NULL');
CALL `tich_ensure_column`('testimonials', 'quote', 'text NOT NULL');
CALL `tich_ensure_column`('testimonials', 'photo_path', 'varchar(500) NULL DEFAULT NULL');
CALL `tich_ensure_column`('testimonials', 'is_featured', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('testimonials', 'display_order', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('testimonials', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('testimonials', 'consented', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('testimonials', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('testimonials', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `testimonials` (add only if missing)
-- (no secondary indexes)

-- -----------------------------------------------------------------------------
-- Table: `three_way_matches`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `three_way_matches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `accounts_payable_id` bigint(20) unsigned NOT NULL,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `goods_received_note_id` bigint(20) unsigned DEFAULT NULL,
  `match_result` varchar(50) NOT NULL,
  `po_amount` decimal(12,2) NOT NULL,
  `invoice_amount` decimal(12,2) NOT NULL,
  `grn_amount` decimal(12,2) NOT NULL,
  `variance_amount` decimal(12,2) NOT NULL,
  `matched_by` bigint(20) unsigned NOT NULL,
  `matched_at` datetime NOT NULL,
  `is_accepted` tinyint(4) NOT NULL DEFAULT 0,
  `accepted_by` bigint(20) unsigned DEFAULT NULL,
  `accepted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `three_way_matches_accounts_payable_id_foreign` (`accounts_payable_id`),
  KEY `three_way_matches_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `three_way_matches_goods_received_note_id_foreign` (`goods_received_note_id`),
  KEY `three_way_matches_matched_by_foreign` (`matched_by`),
  KEY `three_way_matches_accepted_by_foreign` (`accepted_by`),
  CONSTRAINT `three_way_matches_accepted_by_foreign` FOREIGN KEY (`accepted_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `three_way_matches_accounts_payable_id_foreign` FOREIGN KEY (`accounts_payable_id`) REFERENCES `accounts_payable` (`id`),
  CONSTRAINT `three_way_matches_goods_received_note_id_foreign` FOREIGN KEY (`goods_received_note_id`) REFERENCES `goods_received_notes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `three_way_matches_matched_by_foreign` FOREIGN KEY (`matched_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `three_way_matches_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `three_way_matches` (add only if missing)
CALL `tich_ensure_column`('three_way_matches', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('three_way_matches', 'accounts_payable_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('three_way_matches', 'purchase_order_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('three_way_matches', 'goods_received_note_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('three_way_matches', 'match_result', 'varchar(50) NOT NULL');
CALL `tich_ensure_column`('three_way_matches', 'po_amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('three_way_matches', 'invoice_amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('three_way_matches', 'grn_amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('three_way_matches', 'variance_amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('three_way_matches', 'matched_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('three_way_matches', 'matched_at', 'datetime NOT NULL');
CALL `tich_ensure_column`('three_way_matches', 'is_accepted', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('three_way_matches', 'accepted_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('three_way_matches', 'accepted_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `three_way_matches` (add only if missing)
CALL `tich_ensure_index`('three_way_matches', 'three_way_matches_accepted_by_foreign', '`accepted_by`');
CALL `tich_ensure_index`('three_way_matches', 'three_way_matches_accounts_payable_id_foreign', '`accounts_payable_id`');
CALL `tich_ensure_index`('three_way_matches', 'three_way_matches_goods_received_note_id_foreign', '`goods_received_note_id`');
CALL `tich_ensure_index`('three_way_matches', 'three_way_matches_matched_by_foreign', '`matched_by`');
CALL `tich_ensure_index`('three_way_matches', 'three_way_matches_purchase_order_id_foreign', '`purchase_order_id`');

-- -----------------------------------------------------------------------------
-- Table: `timetable_entries`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `timetable_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `unit_allocation_id` bigint(20) unsigned NOT NULL,
  `semester_id` bigint(20) unsigned NOT NULL,
  `day_of_week` int(11) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `venue` varchar(200) DEFAULT NULL,
  `timetable_type` varchar(50) NOT NULL DEFAULT 'class',
  `class_group` varchar(100) DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `timetable_entries_unit_allocation_id_foreign` (`unit_allocation_id`),
  KEY `timetable_entries_semester_id_foreign` (`semester_id`),
  CONSTRAINT `timetable_entries_semester_id_foreign` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`),
  CONSTRAINT `timetable_entries_unit_allocation_id_foreign` FOREIGN KEY (`unit_allocation_id`) REFERENCES `unit_allocations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `timetable_entries` (add only if missing)
CALL `tich_ensure_column`('timetable_entries', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('timetable_entries', 'unit_allocation_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('timetable_entries', 'semester_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('timetable_entries', 'day_of_week', 'int(11) NOT NULL');
CALL `tich_ensure_column`('timetable_entries', 'start_time', 'time NOT NULL');
CALL `tich_ensure_column`('timetable_entries', 'end_time', 'time NOT NULL');
CALL `tich_ensure_column`('timetable_entries', 'venue', 'varchar(200) NULL DEFAULT NULL');
CALL `tich_ensure_column`('timetable_entries', 'timetable_type', 'varchar(50) NOT NULL DEFAULT \'\\\'class\\\'\'');
CALL `tich_ensure_column`('timetable_entries', 'class_group', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('timetable_entries', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('timetable_entries', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('timetable_entries', 'updated_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `timetable_entries` (add only if missing)
CALL `tich_ensure_index`('timetable_entries', 'timetable_entries_semester_id_foreign', '`semester_id`');
CALL `tich_ensure_index`('timetable_entries', 'timetable_entries_unit_allocation_id_foreign', '`unit_allocation_id`');

-- -----------------------------------------------------------------------------
-- Table: `units`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `unit_code` varchar(30) NOT NULL,
  `unit_name` varchar(300) NOT NULL,
  `description` text DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `program_id` bigint(20) unsigned DEFAULT NULL,
  `semester` int(11) NOT NULL DEFAULT 1,
  `block` int(11) DEFAULT NULL,
  `credit_hours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `contact_hours` int(11) NOT NULL DEFAULT 0,
  `total_learning_hours` int(11) NOT NULL DEFAULT 0,
  `display_priority` int(11) NOT NULL DEFAULT 0,
  `is_core` tinyint(4) NOT NULL DEFAULT 1,
  `is_practical` tinyint(4) NOT NULL DEFAULT 0,
  `prerequisite_unit_id` bigint(20) unsigned DEFAULT NULL,
  `co_requisite_unit_id` bigint(20) unsigned DEFAULT NULL,
  `assessment_weight_attendance_pct` decimal(5,2) NOT NULL DEFAULT 5.00,
  `assessment_weight_cat_pct` decimal(5,2) NOT NULL DEFAULT 30.00,
  `assessment_weight_practical_pct` decimal(5,2) NOT NULL DEFAULT 0.00,
  `assessment_weight_exam_pct` decimal(5,2) NOT NULL DEFAULT 60.00,
  `status` varchar(50) NOT NULL DEFAULT 'pending_registry',
  `submitted_at` datetime DEFAULT NULL,
  `submitted_by` bigint(20) unsigned DEFAULT NULL,
  `registrar_approved_at` datetime DEFAULT NULL,
  `registrar_approved_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `units_unit_code_unique` (`unit_code`),
  KEY `units_program_id_foreign` (`program_id`),
  KEY `units_prerequisite_unit_id_foreign` (`prerequisite_unit_id`),
  KEY `units_co_requisite_unit_id_foreign` (`co_requisite_unit_id`),
  KEY `units_status_index` (`status`),
  KEY `units_department_status_index` (`department_id`,`status`),
  CONSTRAINT `units_co_requisite_unit_id_foreign` FOREIGN KEY (`co_requisite_unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL,
  CONSTRAINT `units_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `units_prerequisite_unit_id_foreign` FOREIGN KEY (`prerequisite_unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL,
  CONSTRAINT `units_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `academic_programs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `units` (add only if missing)
CALL `tich_ensure_column`('units', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('units', 'unit_code', 'varchar(30) NOT NULL');
CALL `tich_ensure_column`('units', 'unit_name', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('units', 'description', 'text NULL DEFAULT NULL');
CALL `tich_ensure_column`('units', 'department_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('units', 'program_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('units', 'semester', 'int(11) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('units', 'block', 'int(11) NULL DEFAULT NULL');
CALL `tich_ensure_column`('units', 'credit_hours', 'decimal(5,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('units', 'contact_hours', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('units', 'total_learning_hours', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('units', 'display_priority', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('units', 'is_core', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('units', 'is_practical', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('units', 'prerequisite_unit_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('units', 'co_requisite_unit_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('units', 'assessment_weight_attendance_pct', 'decimal(5,2) NOT NULL DEFAULT \'5.00\'');
CALL `tich_ensure_column`('units', 'assessment_weight_cat_pct', 'decimal(5,2) NOT NULL DEFAULT \'30.00\'');
CALL `tich_ensure_column`('units', 'assessment_weight_practical_pct', 'decimal(5,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('units', 'assessment_weight_exam_pct', 'decimal(5,2) NOT NULL DEFAULT \'60.00\'');
CALL `tich_ensure_column`('units', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending_registry\\\'\'');
CALL `tich_ensure_column`('units', 'submitted_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('units', 'submitted_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('units', 'registrar_approved_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('units', 'registrar_approved_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('units', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('units', 'updated_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('units', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');

-- Indexes for `units` (add only if missing)
CALL `tich_ensure_index`('units', 'units_co_requisite_unit_id_foreign', '`co_requisite_unit_id`');
CALL `tich_ensure_index`('units', 'units_department_status_index', '`department_id`, `status`');
CALL `tich_ensure_index`('units', 'units_prerequisite_unit_id_foreign', '`prerequisite_unit_id`');
CALL `tich_ensure_index`('units', 'units_program_id_foreign', '`program_id`');
CALL `tich_ensure_index`('units', 'units_status_index', '`status`');
CALL `tich_ensure_unique`('units', 'units_unit_code_unique', '`unit_code`');

-- -----------------------------------------------------------------------------
-- Table: `unit_allocations`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `unit_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `unit_id` bigint(20) unsigned NOT NULL,
  `staff_id` bigint(20) unsigned NOT NULL,
  `semester_id` bigint(20) unsigned NOT NULL,
  `campus_id` bigint(20) unsigned NOT NULL,
  `is_coordinator` tinyint(4) NOT NULL DEFAULT 0,
  `contact_hours_assigned` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `unit_allocations_semester_id_foreign` (`semester_id`),
  KEY `unit_allocations_campus_id_foreign` (`campus_id`),
  KEY `unit_allocations_unit_id_semester_id_index` (`unit_id`,`semester_id`),
  KEY `unit_allocations_staff_id_index` (`staff_id`),
  CONSTRAINT `unit_allocations_campus_id_foreign` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`),
  CONSTRAINT `unit_allocations_semester_id_foreign` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`),
  CONSTRAINT `unit_allocations_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`),
  CONSTRAINT `unit_allocations_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `unit_allocations` (add only if missing)
CALL `tich_ensure_column`('unit_allocations', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('unit_allocations', 'unit_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('unit_allocations', 'staff_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('unit_allocations', 'semester_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('unit_allocations', 'campus_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('unit_allocations', 'is_coordinator', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('unit_allocations', 'contact_hours_assigned', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('unit_allocations', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');

-- Indexes for `unit_allocations` (add only if missing)
CALL `tich_ensure_index`('unit_allocations', 'unit_allocations_campus_id_foreign', '`campus_id`');
CALL `tich_ensure_index`('unit_allocations', 'unit_allocations_semester_id_foreign', '`semester_id`');
CALL `tich_ensure_index`('unit_allocations', 'unit_allocations_staff_id_index', '`staff_id`');
CALL `tich_ensure_index`('unit_allocations', 'unit_allocations_unit_id_semester_id_index', '`unit_id`, `semester_id`');

-- -----------------------------------------------------------------------------
-- Table: `users`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_type` enum('student','staff','admin','external') NOT NULL DEFAULT 'student',
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `staff_id` bigint(20) unsigned DEFAULT NULL,
  `student_id` bigint(20) unsigned DEFAULT NULL,
  `mfa_enabled` tinyint(4) NOT NULL DEFAULT 0,
  `mfa_method` enum('sms','email','auth_app','whatsapp') DEFAULT NULL,
  `mfa_verified` tinyint(4) NOT NULL DEFAULT 0,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `email_verified_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `failed_login_attempts` int(11) NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `mfa_secret` varchar(100) DEFAULT NULL,
  `mfa_secret_temp` varchar(100) DEFAULT NULL,
  `mfa_backup_codes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`mfa_backup_codes`)),
  `mfa_enabled_at` datetime DEFAULT NULL,
  `mfa_last_verified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_staff_id_foreign` (`staff_id`),
  KEY `users_student_id_foreign` (`student_id`),
  KEY `users_created_by_foreign` (`created_by`),
  KEY `users_is_active_index` (`is_active`),
  CONSTRAINT `users_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `users` (add only if missing)
CALL `tich_ensure_column`('users', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('users', 'user_type', 'enum(\'student\',\'staff\',\'admin\',\'external\') NOT NULL DEFAULT \'\\\'student\\\'\'');
CALL `tich_ensure_column`('users', 'email', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('users', 'password_hash', 'varchar(255) NOT NULL');
CALL `tich_ensure_column`('users', 'remember_token', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('users', 'staff_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('users', 'student_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('users', 'mfa_enabled', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('users', 'mfa_method', 'enum(\'sms\',\'email\',\'auth_app\',\'whatsapp\') NULL DEFAULT NULL');
CALL `tich_ensure_column`('users', 'mfa_verified', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('users', 'is_active', 'tinyint(4) NOT NULL DEFAULT \'1\'');
CALL `tich_ensure_column`('users', 'email_verified_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('users', 'last_login_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('users', 'failed_login_attempts', 'int(11) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('users', 'locked_until', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('users', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('users', 'updated_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('users', 'created_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('users', 'mfa_secret', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('users', 'mfa_secret_temp', 'varchar(100) NULL DEFAULT NULL');
CALL `tich_ensure_column`('users', 'mfa_backup_codes', 'longtext NULL DEFAULT NULL');
CALL `tich_ensure_column`('users', 'mfa_enabled_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('users', 'mfa_last_verified_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `users` (add only if missing)
CALL `tich_ensure_index`('users', 'users_created_by_foreign', '`created_by`');
CALL `tich_ensure_unique`('users', 'users_email_unique', '`email`');
CALL `tich_ensure_index`('users', 'users_is_active_index', '`is_active`');
CALL `tich_ensure_index`('users', 'users_staff_id_foreign', '`staff_id`');
CALL `tich_ensure_index`('users', 'users_student_id_foreign', '`student_id`');

-- -----------------------------------------------------------------------------
-- Table: `user_roles`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  `campus_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_roles_user_id_role_id_campus_id_department_id_unique` (`user_id`,`role_id`,`campus_id`,`department_id`),
  KEY `user_roles_role_id_foreign` (`role_id`),
  KEY `user_roles_campus_id_foreign` (`campus_id`),
  KEY `user_roles_department_id_foreign` (`department_id`),
  KEY `user_roles_assigned_by_foreign` (`assigned_by`),
  CONSTRAINT `user_roles_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_roles_campus_id_foreign` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_roles_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  CONSTRAINT `user_roles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `user_roles` (add only if missing)
CALL `tich_ensure_column`('user_roles', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('user_roles', 'user_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('user_roles', 'role_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('user_roles', 'campus_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('user_roles', 'department_id', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('user_roles', 'assigned_at', 'datetime NOT NULL DEFAULT current_timestamp()');
CALL `tich_ensure_column`('user_roles', 'assigned_by', 'bigint(20) unsigned NULL DEFAULT NULL');
CALL `tich_ensure_column`('user_roles', 'expires_at', 'datetime NULL DEFAULT NULL');

-- Indexes for `user_roles` (add only if missing)
CALL `tich_ensure_index`('user_roles', 'user_roles_assigned_by_foreign', '`assigned_by`');
CALL `tich_ensure_index`('user_roles', 'user_roles_campus_id_foreign', '`campus_id`');
CALL `tich_ensure_index`('user_roles', 'user_roles_department_id_foreign', '`department_id`');
CALL `tich_ensure_index`('user_roles', 'user_roles_role_id_foreign', '`role_id`');
CALL `tich_ensure_unique`('user_roles', 'user_roles_user_id_role_id_campus_id_department_id_unique', '`user_id`, `role_id`, `campus_id`, `department_id`');

-- -----------------------------------------------------------------------------
-- Table: `waitlist_entries`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `waitlist_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `applicant_id` bigint(20) unsigned NOT NULL,
  `program_id` bigint(20) unsigned NOT NULL,
  `cohort_preference` varchar(20) DEFAULT NULL,
  `position` int(11) NOT NULL,
  `enrolled_from_waitlist` tinyint(4) NOT NULL DEFAULT 0,
  `enrolled_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `waitlist_entries_applicant_id_foreign` (`applicant_id`),
  KEY `waitlist_entries_program_id_foreign` (`program_id`),
  CONSTRAINT `waitlist_entries_applicant_id_foreign` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`),
  CONSTRAINT `waitlist_entries_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `academic_programs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `waitlist_entries` (add only if missing)
CALL `tich_ensure_column`('waitlist_entries', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('waitlist_entries', 'applicant_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('waitlist_entries', 'program_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('waitlist_entries', 'cohort_preference', 'varchar(20) NULL DEFAULT NULL');
CALL `tich_ensure_column`('waitlist_entries', 'position', 'int(11) NOT NULL');
CALL `tich_ensure_column`('waitlist_entries', 'enrolled_from_waitlist', 'tinyint(4) NOT NULL DEFAULT \'0\'');
CALL `tich_ensure_column`('waitlist_entries', 'enrolled_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('waitlist_entries', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `waitlist_entries` (add only if missing)
CALL `tich_ensure_index`('waitlist_entries', 'waitlist_entries_applicant_id_foreign', '`applicant_id`');
CALL `tich_ensure_index`('waitlist_entries', 'waitlist_entries_program_id_foreign', '`program_id`');

-- -----------------------------------------------------------------------------
-- Table: `work_study_ledger`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `work_study_ledger` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `project_name` varchar(300) NOT NULL,
  `hours_logged` decimal(8,2) NOT NULL,
  `hourly_rate` decimal(10,2) NOT NULL,
  `total_earnings` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tuition_offset_amount` decimal(12,2) NOT NULL,
  `offset_reference` varchar(50) DEFAULT NULL,
  `work_date` date NOT NULL,
  `verified_by` bigint(20) unsigned NOT NULL,
  `verified_at` datetime DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `work_study_ledger_student_id_foreign` (`student_id`),
  KEY `work_study_ledger_verified_by_foreign` (`verified_by`),
  CONSTRAINT `work_study_ledger_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `work_study_ledger_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columns for `work_study_ledger` (add only if missing)
CALL `tich_ensure_column`('work_study_ledger', 'id', 'bigint(20) unsigned NOT NULL AUTO_INCREMENT');
CALL `tich_ensure_column`('work_study_ledger', 'student_id', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('work_study_ledger', 'project_name', 'varchar(300) NOT NULL');
CALL `tich_ensure_column`('work_study_ledger', 'hours_logged', 'decimal(8,2) NOT NULL');
CALL `tich_ensure_column`('work_study_ledger', 'hourly_rate', 'decimal(10,2) NOT NULL');
CALL `tich_ensure_column`('work_study_ledger', 'total_earnings', 'decimal(12,2) NOT NULL DEFAULT \'0.00\'');
CALL `tich_ensure_column`('work_study_ledger', 'tuition_offset_amount', 'decimal(12,2) NOT NULL');
CALL `tich_ensure_column`('work_study_ledger', 'offset_reference', 'varchar(50) NULL DEFAULT NULL');
CALL `tich_ensure_column`('work_study_ledger', 'work_date', 'date NOT NULL');
CALL `tich_ensure_column`('work_study_ledger', 'verified_by', 'bigint(20) unsigned NOT NULL');
CALL `tich_ensure_column`('work_study_ledger', 'verified_at', 'datetime NULL DEFAULT NULL');
CALL `tich_ensure_column`('work_study_ledger', 'status', 'varchar(50) NOT NULL DEFAULT \'\\\'pending\\\'\'');
CALL `tich_ensure_column`('work_study_ledger', 'created_at', 'datetime NOT NULL DEFAULT current_timestamp()');

-- Indexes for `work_study_ledger` (add only if missing)
CALL `tich_ensure_index`('work_study_ledger', 'work_study_ledger_student_id_foreign', '`student_id`');
CALL `tich_ensure_index`('work_study_ledger', 'work_study_ledger_verified_by_foreign', '`verified_by`');

-- =============================================================================
-- Foreign keys (added only when missing; never drops existing FKs)
-- =============================================================================
SET FOREIGN_KEY_CHECKS = 1;

-- Foreign keys for `about_content_blocks`
CALL `tich_ensure_fk`('about_content_blocks', 'about_content_blocks_created_by_foreign', '`created_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('about_content_blocks', 'about_content_blocks_updated_by_foreign', '`updated_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `academic_programs`
CALL `tich_ensure_fk`('academic_programs', 'academic_programs_approved_by_ceo_id_foreign', '`approved_by_ceo_id`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('academic_programs', 'academic_programs_department_id_foreign', '`department_id`', 'departments', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `accounts_payable`
CALL `tich_ensure_fk`('accounts_payable', 'accounts_payable_finance_approved_by_foreign', '`finance_approved_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('accounts_payable', 'accounts_payable_purchase_order_id_foreign', '`purchase_order_id`', 'purchase_orders', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('accounts_payable', 'accounts_payable_requisition_id_foreign', '`requisition_id`', 'procurement_requisitions', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('accounts_payable', 'accounts_payable_supplier_id_foreign', '`supplier_id`', 'suppliers', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('accounts_payable', 'accounts_payable_three_way_match_by_foreign', '`three_way_match_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `account_ledger`
CALL `tich_ensure_fk`('account_ledger', 'account_ledger_recorded_by_foreign', '`recorded_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `admin_budget_requests`
CALL `tich_ensure_fk`('admin_budget_requests', 'admin_budget_requests_department_id_foreign', '`department_id`', 'departments', '`id`', 'RESTRICT', 'CASCADE');
CALL `tich_ensure_fk`('admin_budget_requests', 'admin_budget_requests_planning_cycle_id_foreign', '`planning_cycle_id`', 'admin_planning_cycles', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `admin_fund_allocations`
CALL `tich_ensure_fk`('admin_fund_allocations', 'admin_fund_allocations_budget_request_id_foreign', '`budget_request_id`', 'admin_budget_requests', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('admin_fund_allocations', 'admin_fund_allocations_department_id_foreign', '`department_id`', 'departments', '`id`', 'RESTRICT', 'CASCADE');

-- Foreign keys for `admin_tasks`
CALL `tich_ensure_fk`('admin_tasks', 'admin_tasks_department_id_foreign', '`department_id`', 'departments', '`id`', 'RESTRICT', 'CASCADE');
CALL `tich_ensure_fk`('admin_tasks', 'admin_tasks_planning_cycle_id_foreign', '`planning_cycle_id`', 'admin_planning_cycles', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `admin_variances`
CALL `tich_ensure_fk`('admin_variances', 'admin_variances_department_id_foreign', '`department_id`', 'departments', '`id`', 'RESTRICT', 'CASCADE');
CALL `tich_ensure_fk`('admin_variances', 'admin_variances_planning_cycle_id_foreign', '`planning_cycle_id`', 'admin_planning_cycles', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `admission_letters`
CALL `tich_ensure_fk`('admission_letters', 'admission_letters_enrollment_campus_id_foreign', '`enrollment_campus_id`', 'campuses', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('admission_letters', 'admission_letters_generated_by_foreign', '`generated_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('admission_letters', 'admission_letters_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `applicants`
CALL `tich_ensure_fk`('applicants', 'applicants_academic_reviewer_id_foreign', '`academic_reviewer_id`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('applicants', 'applicants_handling_department_id_foreign', '`handling_department_id`', 'departments', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('applicants', 'applicants_preferred_campus_id_foreign', '`preferred_campus_id`', 'campuses', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('applicants', 'applicants_program_id_foreign', '`program_id`', 'academic_programs', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('applicants', 'applicants_rpl_application_id_foreign', '`rpl_application_id`', 'rpl_applications', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `application_documents`
CALL `tich_ensure_fk`('application_documents', 'application_documents_applicant_id_foreign', '`applicant_id`', 'applicants', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('application_documents', 'application_documents_verified_by_foreign', '`verified_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `assets`
CALL `tich_ensure_fk`('assets', 'assets_purchase_order_id_foreign', '`purchase_order_id`', 'purchase_orders', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('assets', 'assets_supplier_id_foreign', '`supplier_id`', 'suppliers', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `asset_assignments`
CALL `tich_ensure_fk`('asset_assignments', 'asset_assignments_asset_id_foreign', '`asset_id`', 'assets', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('asset_assignments', 'asset_assignments_assigned_by_foreign', '`assigned_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('asset_assignments', 'asset_assignments_returned_to_foreign', '`returned_to`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `attendance_records`
CALL `tich_ensure_fk`('attendance_records', 'attendance_records_session_id_foreign', '`session_id`', 'attendance_sessions', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('attendance_records', 'attendance_records_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `attendance_sessions`
CALL `tich_ensure_fk`('attendance_sessions', 'attendance_sessions_hod_verified_by_foreign', '`hod_verified_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('attendance_sessions', 'attendance_sessions_recorded_by_foreign', '`recorded_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('attendance_sessions', 'attendance_sessions_registrar_verified_by_foreign', '`registrar_verified_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('attendance_sessions', 'attendance_sessions_unit_allocation_id_foreign', '`unit_allocation_id`', 'unit_allocations', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('attendance_sessions', 'att_sess_timetable_slot_fk', '`program_timetable_session_id`', 'program_timetable_sessions', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `attendance_summaries`
CALL `tich_ensure_fk`('attendance_summaries', 'attendance_summaries_semester_id_foreign', '`semester_id`', 'semesters', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('attendance_summaries', 'attendance_summaries_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('attendance_summaries', 'attendance_summaries_unit_id_foreign', '`unit_id`', 'units', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `blog_categories`
CALL `tich_ensure_fk`('blog_categories', 'blog_categories_created_by_foreign', '`created_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('blog_categories', 'blog_categories_updated_by_foreign', '`updated_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `blog_posts`
CALL `tich_ensure_fk`('blog_posts', 'blog_posts_author_staff_id_foreign', '`author_staff_id`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('blog_posts', 'blog_posts_created_by_foreign', '`created_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('blog_posts', 'blog_posts_updated_by_foreign', '`updated_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `blog_post_categories`
CALL `tich_ensure_fk`('blog_post_categories', 'blog_post_categories_category_id_foreign', '`category_id`', 'blog_categories', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('blog_post_categories', 'blog_post_categories_created_by_foreign', '`created_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('blog_post_categories', 'blog_post_categories_post_id_foreign', '`post_id`', 'blog_posts', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `blog_post_revisions`
CALL `tich_ensure_fk`('blog_post_revisions', 'blog_post_revisions_created_by_foreign', '`created_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('blog_post_revisions', 'blog_post_revisions_edited_by_foreign', '`edited_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('blog_post_revisions', 'blog_post_revisions_post_id_foreign', '`post_id`', 'blog_posts', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `cafeteria_staff_memberships`
CALL `tich_ensure_fk`('cafeteria_staff_memberships', 'cafeteria_staff_memberships_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `campuses`
CALL `tich_ensure_fk`('campuses', 'campuses_parent_campus_id_foreign', '`parent_campus_id`', 'campuses', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `cat_scores`
CALL `tich_ensure_fk`('cat_scores', 'cat_scores_recorded_by_foreign', '`recorded_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('cat_scores', 'cat_scores_semester_id_foreign', '`semester_id`', 'semesters', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('cat_scores', 'cat_scores_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('cat_scores', 'cat_scores_unit_id_foreign', '`unit_id`', 'units', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `chatbot_conversations`
CALL `tich_ensure_fk`('chatbot_conversations', 'chatbot_conversations_escalated_to_user_id_foreign', '`escalated_to_user_id`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('chatbot_conversations', 'chatbot_conversations_user_id_foreign', '`user_id`', 'users', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `chatbot_messages`
CALL `tich_ensure_fk`('chatbot_messages', 'chatbot_messages_conversation_id_foreign', '`conversation_id`', 'chatbot_conversations', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('chatbot_messages', 'chatbot_messages_human_agent_id_foreign', '`human_agent_id`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `clearance_checklist_items`
CALL `tich_ensure_fk`('clearance_checklist_items', 'clearance_checklist_items_completed_by_foreign', '`completed_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('clearance_checklist_items', 'clearance_checklist_items_offboarding_request_id_foreign', '`offboarding_request_id`', 'offboarding_requests', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `competency_assessments`
CALL `tich_ensure_fk`('competency_assessments', 'competency_assessments_assessed_by_foreign', '`assessed_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('competency_assessments', 'competency_assessments_competency_checklist_id_foreign', '`competency_checklist_id`', 'competency_checklists', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('competency_assessments', 'competency_assessments_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `competency_checklists`
CALL `tich_ensure_fk`('competency_checklists', 'competency_checklists_mentor_id_foreign', '`mentor_id`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('competency_checklists', 'competency_checklists_sub_county_hub_id_foreign', '`sub_county_hub_id`', 'campuses', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('competency_checklists', 'competency_checklists_unit_id_foreign', '`unit_id`', 'units', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `credit_memos`
CALL `tich_ensure_fk`('credit_memos', 'credit_memos_invoice_id_foreign', '`invoice_id`', 'invoices', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('credit_memos', 'credit_memos_issued_by_foreign', '`issued_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('credit_memos', 'credit_memos_student_account_id_foreign', '`student_account_id`', 'student_accounts', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('credit_memos', 'credit_memos_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `curriculum_versions`
CALL `tich_ensure_fk`('curriculum_versions', 'curriculum_versions_academic_year_id_foreign', '`academic_year_id`', 'academic_years', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('curriculum_versions', 'curriculum_versions_program_id_foreign', '`program_id`', 'academic_programs', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `curriculum_version_periods`
CALL `tich_ensure_fk`('curriculum_version_periods', 'curriculum_version_periods_block_id_foreign', '`block_id`', 'nursing_blocks', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('curriculum_version_periods', 'curriculum_version_periods_curriculum_version_id_foreign', '`curriculum_version_id`', 'curriculum_versions', '`id`', 'RESTRICT', 'CASCADE');

-- Foreign keys for `curriculum_version_units`
CALL `tich_ensure_fk`('curriculum_version_units', 'curriculum_version_units_block_id_foreign', '`block_id`', 'nursing_blocks', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('curriculum_version_units', 'curriculum_version_units_curriculum_version_id_foreign', '`curriculum_version_id`', 'curriculum_versions', '`id`', 'RESTRICT', 'CASCADE');
CALL `tich_ensure_fk`('curriculum_version_units', 'curriculum_version_units_unit_id_foreign', '`unit_id`', 'units', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `deferral_requests`
CALL `tich_ensure_fk`('deferral_requests', 'deferral_requests_effective_from_semester_id_foreign', '`effective_from_semester_id`', 'semesters', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('deferral_requests', 'deferral_requests_requested_semester_id_foreign', '`requested_semester_id`', 'semesters', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('deferral_requests', 'deferral_requests_reviewed_by_foreign', '`reviewed_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('deferral_requests', 'deferral_requests_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `departments`
CALL `tich_ensure_fk`('departments', 'departments_campus_id_foreign', '`campus_id`', 'campuses', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('departments', 'departments_department_group_id_foreign', '`department_group_id`', 'department_groups', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('departments', 'departments_hod_id_foreign', '`hod_id`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('departments', 'departments_parent_dept_id_foreign', '`parent_dept_id`', 'departments', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `department_modules`
CALL `tich_ensure_fk`('department_modules', 'department_modules_assigned_by_foreign', '`assigned_by`', 'users', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('department_modules', 'department_modules_department_id_foreign', '`department_id`', 'departments', '`id`', 'RESTRICT', 'CASCADE');

-- Foreign keys for `device_tokens`
CALL `tich_ensure_fk`('device_tokens', 'device_tokens_user_id_foreign', '`user_id`', 'users', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `disciplinary_cases`
CALL `tich_ensure_fk`('disciplinary_cases', 'disciplinary_cases_assigned_to_foreign', '`assigned_to`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('disciplinary_cases', 'disciplinary_cases_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'CASCADE');

-- Foreign keys for `disciplinary_documents`
CALL `tich_ensure_fk`('disciplinary_documents', 'disciplinary_documents_disciplinary_case_id_foreign', '`disciplinary_case_id`', 'disciplinary_cases', '`id`', 'RESTRICT', 'CASCADE');

-- Foreign keys for `disciplinary_records`
CALL `tich_ensure_fk`('disciplinary_records', 'disciplinary_records_assigned_officer_id_foreign', '`assigned_officer_id`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('disciplinary_records', 'disciplinary_records_reported_by_foreign', '`reported_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('disciplinary_records', 'disciplinary_records_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `donations`
CALL `tich_ensure_fk`('donations', 'donations_campaign_id_foreign', '`campaign_id`', 'donation_campaigns', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('donations', 'donations_reconciled_by_foreign', '`reconciled_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `donor_disbursements`
CALL `tich_ensure_fk`('donor_disbursements', 'donor_disbursements_account_ledger_id_foreign', '`account_ledger_id`', 'account_ledger', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('donor_disbursements', 'donor_disbursements_donor_project_id_foreign', '`donor_project_id`', 'donor_projects', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `donor_projects`
CALL `tich_ensure_fk`('donor_projects', 'donor_projects_project_leader_id_foreign', '`project_leader_id`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `email_gateway_logs`
CALL `tich_ensure_fk`('email_gateway_logs', 'email_gateway_logs_notification_id_foreign', '`notification_id`', 'notifications', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `erp_registration_invitations`
CALL `tich_ensure_fk`('erp_registration_invitations', 'erp_registration_invitations_invited_by_foreign', '`invited_by`', 'users', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('erp_registration_invitations', 'erp_registration_invitations_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `events`
CALL `tich_ensure_fk`('events', 'events_created_by_foreign', '`created_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('events', 'events_updated_by_foreign', '`updated_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `examination_papers`
CALL `tich_ensure_fk`('examination_papers', 'examination_papers_approved_by_foreign', '`approved_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('examination_papers', 'examination_papers_prepared_by_foreign', '`prepared_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('examination_papers', 'examination_papers_semester_id_foreign', '`semester_id`', 'semesters', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('examination_papers', 'examination_papers_unit_id_foreign', '`unit_id`', 'units', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `exam_cards`
CALL `tich_ensure_fk`('exam_cards', 'exam_cards_semester_id_foreign', '`semester_id`', 'semesters', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('exam_cards', 'exam_cards_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('exam_cards', 'exam_cards_voided_by_foreign', '`voided_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `exam_eligibility_matrix`
CALL `tich_ensure_fk`('exam_eligibility_matrix', 'exam_eligibility_matrix_semester_id_foreign', '`semester_id`', 'semesters', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('exam_eligibility_matrix', 'exam_eligibility_matrix_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('exam_eligibility_matrix', 'exam_eligibility_matrix_unit_id_foreign', '`unit_id`', 'units', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `exam_results`
CALL `tich_ensure_fk`('exam_results', 'exam_results_entered_by_foreign', '`entered_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('exam_results', 'exam_results_exam_card_id_foreign', '`exam_card_id`', 'exam_cards', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('exam_results', 'exam_results_moderator_id_foreign', '`moderator_id`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('exam_results', 'exam_results_semester_id_foreign', '`semester_id`', 'semesters', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('exam_results', 'exam_results_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('exam_results', 'exam_results_unit_id_foreign', '`unit_id`', 'units', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `exam_schedules`
CALL `tich_ensure_fk`('exam_schedules', 'exam_schedules_invigilator_id_foreign', '`invigilator_id`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('exam_schedules', 'exam_schedules_pts_fk', '`program_timetable_session_id`', 'program_timetable_sessions', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('exam_schedules', 'exam_schedules_second_invigilator_id_foreign', '`second_invigilator_id`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('exam_schedules', 'exam_schedules_semester_id_foreign', '`semester_id`', 'semesters', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('exam_schedules', 'exam_schedules_unit_id_foreign', '`unit_id`', 'units', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `faqs`
CALL `tich_ensure_fk`('faqs', 'faqs_created_by_foreign', '`created_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('faqs', 'faqs_updated_by_foreign', '`updated_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `feedback`
CALL `tich_ensure_fk`('feedback', 'feedback_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'CASCADE');

-- Foreign keys for `fee_structures`
CALL `tich_ensure_fk`('fee_structures', 'fee_structures_academic_year_id_foreign', '`academic_year_id`', 'academic_years', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('fee_structures', 'fee_structures_approved_by_foreign', '`approved_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('fee_structures', 'fee_structures_program_id_foreign', '`program_id`', 'academic_programs', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `finance_budgets`
CALL `tich_ensure_fk`('finance_budgets', 'finance_budgets_approved_by_foreign', '`approved_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('finance_budgets', 'finance_budgets_department_id_foreign', '`department_id`', 'departments', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `finance_budget_cycles`
CALL `tich_ensure_fk`('finance_budget_cycles', 'finance_budget_cycles_budget_id_foreign', '`budget_id`', 'finance_budgets', '`id`', 'RESTRICT', 'CASCADE');

-- Foreign keys for `financial_adjustments`
CALL `tich_ensure_fk`('financial_adjustments', 'financial_adjustments_approved_by_foreign', '`approved_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('financial_adjustments', 'financial_adjustments_invoice_id_foreign', '`invoice_id`', 'invoices', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('financial_adjustments', 'financial_adjustments_invoice_item_id_foreign', '`invoice_item_id`', 'invoice_items', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('financial_adjustments', 'financial_adjustments_requested_by_foreign', '`requested_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('financial_adjustments', 'financial_adjustments_student_account_id_foreign', '`student_account_id`', 'student_accounts', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('financial_adjustments', 'financial_adjustments_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `gallery_albums`
CALL `tich_ensure_fk`('gallery_albums', 'gallery_albums_created_by_foreign', '`created_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('gallery_albums', 'gallery_albums_updated_by_foreign', '`updated_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `gallery_images`
CALL `tich_ensure_fk`('gallery_images', 'gallery_images_album_id_foreign', '`album_id`', 'gallery_albums', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('gallery_images', 'gallery_images_created_by_foreign', '`created_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('gallery_images', 'gallery_images_uploaded_by_foreign', '`uploaded_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `goods_received_notes`
CALL `tich_ensure_fk`('goods_received_notes', 'goods_received_notes_purchase_order_id_foreign', '`purchase_order_id`', 'purchase_orders', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('goods_received_notes', 'goods_received_notes_received_by_foreign', '`received_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `grade_records`
CALL `tich_ensure_fk`('grade_records', 'grade_records_exam_result_id_foreign', '`exam_result_id`', 'exam_results', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('grade_records', 'grade_records_nursing_block_id_foreign', '`nursing_block_id`', 'nursing_blocks', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('grade_records', 'grade_records_semester_id_foreign', '`semester_id`', 'semesters', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('grade_records', 'grade_records_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('grade_records', 'grade_records_unit_id_foreign', '`unit_id`', 'units', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `grievances`
CALL `tich_ensure_fk`('grievances', 'grievances_assigned_to_foreign', '`assigned_to`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('grievances', 'grievances_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'CASCADE');

-- Foreign keys for `homepage_carousel_slides`
CALL `tich_ensure_fk`('homepage_carousel_slides', 'homepage_carousel_slides_event_id_foreign', '`event_id`', 'events', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('homepage_carousel_slides', 'homepage_carousel_slides_program_id_foreign', '`program_id`', 'academic_programs', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `hr_policies`
CALL `tich_ensure_fk`('hr_policies', 'hr_policies_uploaded_by_foreign', '`uploaded_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `installment_plans`
CALL `tich_ensure_fk`('installment_plans', 'installment_plans_invoice_id_foreign', '`invoice_id`', 'invoices', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('installment_plans', 'installment_plans_student_account_id_foreign', '`student_account_id`', 'student_accounts', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('installment_plans', 'installment_plans_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `installment_plan_items`
CALL `tich_ensure_fk`('installment_plan_items', 'installment_plan_items_installment_plan_id_foreign', '`installment_plan_id`', 'installment_plans', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `institutional_timeline_events`
CALL `tich_ensure_fk`('institutional_timeline_events', 'institutional_timeline_events_created_by_foreign', '`created_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('institutional_timeline_events', 'institutional_timeline_events_updated_by_foreign', '`updated_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `inventory_items`
CALL `tich_ensure_fk`('inventory_items', 'inventory_items_supplier_id_foreign', '`supplier_id`', 'suppliers', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `inventory_transactions`
CALL `tich_ensure_fk`('inventory_transactions', 'inventory_transactions_department_id_foreign', '`department_id`', 'departments', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('inventory_transactions', 'inventory_transactions_inventory_item_id_foreign', '`inventory_item_id`', 'inventory_items', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('inventory_transactions', 'inventory_transactions_recorded_by_foreign', '`recorded_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `invoices`
CALL `tich_ensure_fk`('invoices', 'invoices_fee_structure_id_foreign', '`fee_structure_id`', 'fee_structures', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('invoices', 'invoices_semester_id_foreign', '`semester_id`', 'semesters', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('invoices', 'invoices_student_account_id_foreign', '`student_account_id`', 'student_accounts', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('invoices', 'invoices_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('invoices', 'invoices_waived_by_foreign', '`waived_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `invoice_items`
CALL `tich_ensure_fk`('invoice_items', 'invoice_items_invoice_id_foreign', '`invoice_id`', 'invoices', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `job_vacancies`
CALL `tich_ensure_fk`('job_vacancies', 'job_vacancies_created_by_foreign', '`created_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('job_vacancies', 'job_vacancies_department_id_foreign', '`department_id`', 'departments', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `leave_balances`
CALL `tich_ensure_fk`('leave_balances', 'leave_balances_leave_type_id_foreign', '`leave_type_id`', 'leave_types', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('leave_balances', 'leave_balances_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `leave_carry_forward_requests`
CALL `tich_ensure_fk`('leave_carry_forward_requests', 'leave_carry_forward_requests_leave_type_id_foreign', '`leave_type_id`', 'leave_types', '`id`', 'RESTRICT', 'CASCADE');
CALL `tich_ensure_fk`('leave_carry_forward_requests', 'leave_carry_forward_requests_reviewed_by_foreign', '`reviewed_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('leave_carry_forward_requests', 'leave_carry_forward_requests_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'CASCADE');

-- Foreign keys for `leave_requests`
CALL `tich_ensure_fk`('leave_requests', 'leave_requests_hod_approved_by_foreign', '`hod_approved_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('leave_requests', 'leave_requests_hr_approved_by_foreign', '`hr_approved_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('leave_requests', 'leave_requests_leave_type_id_foreign', '`leave_type_id`', 'leave_types', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('leave_requests', 'leave_requests_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `lesson_plans`
CALL `tich_ensure_fk`('lesson_plans', 'lesson_plans_hod_id_foreign', '`hod_id`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('lesson_plans', 'lesson_plans_prepared_by_foreign', '`prepared_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('lesson_plans', 'lesson_plans_unit_allocation_id_foreign', '`unit_allocation_id`', 'unit_allocations', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `lesson_plan_approvals`
CALL `tich_ensure_fk`('lesson_plan_approvals', 'lesson_plan_approvals_approver_id_foreign', '`approver_id`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('lesson_plan_approvals', 'lesson_plan_approvals_lesson_plan_id_foreign', '`lesson_plan_id`', 'lesson_plans', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `media_attachments`
CALL `tich_ensure_fk`('media_attachments', 'media_attachments_created_by_foreign', '`created_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('media_attachments', 'media_attachments_uploaded_by_foreign', '`uploaded_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `medical_records`
CALL `tich_ensure_fk`('medical_records', 'medical_records_reviewed_by_foreign', '`reviewed_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('medical_records', 'medical_records_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `mpesa_stk_requests`
CALL `tich_ensure_fk`('mpesa_stk_requests', 'mpesa_stk_requests_applicant_id_foreign', '`applicant_id`', 'applicants', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('mpesa_stk_requests', 'mpesa_stk_requests_invoice_id_foreign', '`invoice_id`', 'invoices', '`id`', 'RESTRICT', 'CASCADE');
CALL `tich_ensure_fk`('mpesa_stk_requests', 'mpesa_stk_requests_payment_id_foreign', '`payment_id`', 'payments', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('mpesa_stk_requests', 'mpesa_stk_requests_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'CASCADE');

-- Foreign keys for `navigation_menu_items`
CALL `tich_ensure_fk`('navigation_menu_items', 'navigation_menu_items_menu_id_foreign', '`menu_id`', 'navigation_menus', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('navigation_menu_items', 'navigation_menu_items_parent_item_id_foreign', '`parent_item_id`', 'navigation_menu_items', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `newsletter_campaigns`
CALL `tich_ensure_fk`('newsletter_campaigns', 'newsletter_campaigns_sent_by_foreign', '`sent_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `newsletter_campaign_recipients`
CALL `tich_ensure_fk`('newsletter_campaign_recipients', 'newsletter_campaign_recipients_campaign_id_foreign', '`campaign_id`', 'newsletter_campaigns', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('newsletter_campaign_recipients', 'newsletter_campaign_recipients_subscriber_id_foreign', '`subscriber_id`', 'newsletter_subscribers', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `newsletter_subscribers`
CALL `tich_ensure_fk`('newsletter_subscribers', 'newsletter_subscribers_linked_user_id_foreign', '`linked_user_id`', 'users', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `notifications`
CALL `tich_ensure_fk`('notifications', 'notifications_template_id_foreign', '`template_id`', 'notification_templates', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('notifications', 'notifications_user_id_foreign', '`user_id`', 'users', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `nursing_blocks`
CALL `tich_ensure_fk`('nursing_blocks', 'nursing_blocks_program_id_foreign', '`program_id`', 'academic_programs', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `nursing_block_progress`
CALL `tich_ensure_fk`('nursing_block_progress', 'nursing_block_progress_block_id_foreign', '`block_id`', 'nursing_blocks', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('nursing_block_progress', 'nursing_block_progress_program_id_foreign', '`program_id`', 'academic_programs', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('nursing_block_progress', 'nursing_block_progress_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `objective_assessments`
CALL `tich_ensure_fk`('objective_assessments', 'objective_assessments_created_by_foreign', '`created_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('objective_assessments', 'objective_assessments_semester_id_foreign', '`semester_id`', 'semesters', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('objective_assessments', 'objective_assessments_unit_allocation_id_foreign', '`unit_allocation_id`', 'unit_allocations', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('objective_assessments', 'objective_assessments_unit_id_foreign', '`unit_id`', 'units', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `objective_questions`
CALL `tich_ensure_fk`('objective_questions', 'objective_questions_objective_assessment_id_foreign', '`objective_assessment_id`', 'objective_assessments', '`id`', 'RESTRICT', 'CASCADE');

-- Foreign keys for `objective_submissions`
CALL `tich_ensure_fk`('objective_submissions', 'objective_submissions_objective_assessment_id_foreign', '`objective_assessment_id`', 'objective_assessments', '`id`', 'RESTRICT', 'CASCADE');
CALL `tich_ensure_fk`('objective_submissions', 'objective_submissions_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `offboarding_requests`
CALL `tich_ensure_fk`('offboarding_requests', 'offboarding_requests_approved_by_foreign', '`approved_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('offboarding_requests', 'offboarding_requests_initiated_by_foreign', '`initiated_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('offboarding_requests', 'offboarding_requests_processed_by_foreign', '`processed_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('offboarding_requests', 'offboarding_requests_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `partnership_requests`
CALL `tich_ensure_fk`('partnership_requests', 'partnership_requests_created_by_foreign', '`created_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('partnership_requests', 'partnership_requests_reviewed_by_foreign', '`reviewed_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('partnership_requests', 'partnership_requests_updated_by_foreign', '`updated_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `partner_logos`
CALL `tich_ensure_fk`('partner_logos', 'partner_logos_created_by_foreign', '`created_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('partner_logos', 'partner_logos_updated_by_foreign', '`updated_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `payments`
CALL `tich_ensure_fk`('payments', 'payments_invoice_id_foreign', '`invoice_id`', 'invoices', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('payments', 'payments_reconciled_by_foreign', '`reconciled_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('payments', 'payments_recorded_by_foreign', '`recorded_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('payments', 'payments_student_account_id_foreign', '`student_account_id`', 'student_accounts', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('payments', 'payments_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `payment_allocations`
CALL `tich_ensure_fk`('payment_allocations', 'payment_allocations_invoice_id_foreign', '`invoice_id`', 'invoices', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('payment_allocations', 'payment_allocations_payment_id_foreign', '`payment_id`', 'payments', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `payment_milestones`
CALL `tich_ensure_fk`('payment_milestones', 'payment_milestones_invoice_id_foreign', '`invoice_id`', 'invoices', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('payment_milestones', 'payment_milestones_recorded_by_foreign', '`recorded_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('payment_milestones', 'payment_milestones_student_account_id_foreign', '`student_account_id`', 'student_accounts', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('payment_milestones', 'payment_milestones_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `payroll_band_deduction_rates`
CALL `tich_ensure_fk`('payroll_band_deduction_rates', 'payroll_band_deduction_rates_payroll_deduction_type_id_foreign', '`payroll_deduction_type_id`', 'payroll_deduction_types', '`id`', 'RESTRICT', 'CASCADE');
CALL `tich_ensure_fk`('payroll_band_deduction_rates', 'payroll_band_deduction_rates_payroll_tax_band_id_foreign', '`payroll_tax_band_id`', 'payroll_tax_bands', '`id`', 'RESTRICT', 'CASCADE');

-- Foreign keys for `payroll_items`
CALL `tich_ensure_fk`('payroll_items', 'payroll_items_approved_by_foreign', '`approved_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('payroll_items', 'payroll_items_bank_transaction_id_foreign', '`bank_transaction_id`', 'bank_transactions', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('payroll_items', 'payroll_items_payroll_run_id_foreign', '`payroll_run_id`', 'payroll_runs', '`id`', 'RESTRICT', 'CASCADE');
CALL `tich_ensure_fk`('payroll_items', 'payroll_items_processed_by_foreign', '`processed_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('payroll_items', 'payroll_items_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `payroll_runs`
CALL `tich_ensure_fk`('payroll_runs', 'payroll_runs_approved_by_foreign', '`approved_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('payroll_runs', 'payroll_runs_created_by_foreign', '`created_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('payroll_runs', 'payroll_runs_posted_by_foreign', '`posted_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `performance_reviews`
CALL `tich_ensure_fk`('performance_reviews', 'performance_reviews_reviewer_id_foreign', '`reviewer_id`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('performance_reviews', 'performance_reviews_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `policy_acknowledgements`
CALL `tich_ensure_fk`('policy_acknowledgements', 'policy_acknowledgements_policy_id_foreign', '`policy_id`', 'hr_policies', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('policy_acknowledgements', 'policy_acknowledgements_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `procurement_requisitions`
CALL `tich_ensure_fk`('procurement_requisitions', 'procurement_requisitions_ceo_approved_by_foreign', '`ceo_approved_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('procurement_requisitions', 'procurement_requisitions_finance_approved_by_foreign', '`finance_approved_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('procurement_requisitions', 'procurement_requisitions_hod_approved_by_foreign', '`hod_approved_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('procurement_requisitions', 'procurement_requisitions_requested_by_foreign', '`requested_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('procurement_requisitions', 'procurement_requisitions_requesting_department_id_foreign', '`requesting_department_id`', 'departments', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `professional_development`
CALL `tich_ensure_fk`('professional_development', 'professional_development_approved_by_foreign', '`approved_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('professional_development', 'professional_development_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `program_timetables`
CALL `tich_ensure_fk`('program_timetables', 'program_timetables_campus_id_foreign', '`campus_id`', 'campuses', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('program_timetables', 'program_timetables_curriculum_version_id_foreign', '`curriculum_version_id`', 'curriculum_versions', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('program_timetables', 'program_timetables_program_id_foreign', '`program_id`', 'academic_programs', '`id`', 'RESTRICT', 'CASCADE');
CALL `tich_ensure_fk`('program_timetables', 'program_timetables_template_id_foreign', '`template_id`', 'program_timetable_templates', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `program_timetable_segments`
CALL `tich_ensure_fk`('program_timetable_segments', 'program_timetable_segments_template_id_foreign', '`template_id`', 'program_timetable_templates', '`id`', 'RESTRICT', 'CASCADE');

-- Foreign keys for `program_timetable_sessions`
CALL `tich_ensure_fk`('program_timetable_sessions', 'program_timetable_sessions_lesson_plan_id_foreign', '`lesson_plan_id`', 'lesson_plans', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('program_timetable_sessions', 'program_timetable_sessions_program_timetable_id_foreign', '`program_timetable_id`', 'program_timetables', '`id`', 'RESTRICT', 'CASCADE');
CALL `tich_ensure_fk`('program_timetable_sessions', 'program_timetable_sessions_room_id_foreign', '`room_id`', 'rooms', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('program_timetable_sessions', 'program_timetable_sessions_segment_id_foreign', '`segment_id`', 'program_timetable_segments', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('program_timetable_sessions', 'program_timetable_sessions_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('program_timetable_sessions', 'program_timetable_sessions_unit_id_foreign', '`unit_id`', 'units', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `program_timetable_templates`
CALL `tich_ensure_fk`('program_timetable_templates', 'program_timetable_templates_program_id_foreign', '`program_id`', 'academic_programs', '`id`', 'RESTRICT', 'CASCADE');

-- Foreign keys for `program_timetable_template_days`
CALL `tich_ensure_fk`('program_timetable_template_days', 'program_timetable_template_days_template_id_foreign', '`template_id`', 'program_timetable_templates', '`id`', 'RESTRICT', 'CASCADE');

-- Foreign keys for `program_units`
CALL `tich_ensure_fk`('program_units', 'program_units_block_id_foreign', '`block_id`', 'nursing_blocks', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('program_units', 'program_units_program_id_foreign', '`program_id`', 'academic_programs', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('program_units', 'program_units_unit_id_foreign', '`unit_id`', 'units', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `purchase_orders`
CALL `tich_ensure_fk`('purchase_orders', 'purchase_orders_requisition_id_foreign', '`requisition_id`', 'procurement_requisitions', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('purchase_orders', 'purchase_orders_supplier_id_foreign', '`supplier_id`', 'suppliers', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `qa_audit_checklists`
CALL `tich_ensure_fk`('qa_audit_checklists', 'qa_audit_checklists_applies_to_department_id_foreign', '`applies_to_department_id`', 'departments', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('qa_audit_checklists', 'qa_audit_checklists_qa_plan_id_foreign', '`qa_plan_id`', 'qa_plans', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `qa_compliance_scores`
CALL `tich_ensure_fk`('qa_compliance_scores', 'qa_compliance_scores_department_id_foreign', '`department_id`', 'departments', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('qa_compliance_scores', 'qa_compliance_scores_qa_plan_id_foreign', '`qa_plan_id`', 'qa_plans', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `qa_corrective_actions`
CALL `tich_ensure_fk`('qa_corrective_actions', 'qa_corrective_actions_checklist_item_id_foreign', '`checklist_item_id`', 'qa_audit_checklists', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('qa_corrective_actions', 'qa_corrective_actions_department_id_foreign', '`department_id`', 'departments', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('qa_corrective_actions', 'qa_corrective_actions_qa_plan_id_foreign', '`qa_plan_id`', 'qa_plans', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('qa_corrective_actions', 'qa_corrective_actions_resolved_by_foreign', '`resolved_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('qa_corrective_actions', 'qa_corrective_actions_responsible_officer_id_foreign', '`responsible_officer_id`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `qa_department_submissions`
CALL `tich_ensure_fk`('qa_department_submissions', 'qa_department_submissions_checklist_item_id_foreign', '`checklist_item_id`', 'qa_audit_checklists', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('qa_department_submissions', 'qa_department_submissions_department_id_foreign', '`department_id`', 'departments', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('qa_department_submissions', 'qa_department_submissions_qa_plan_id_foreign', '`qa_plan_id`', 'qa_plans', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('qa_department_submissions', 'qa_department_submissions_submitted_by_foreign', '`submitted_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('qa_department_submissions', 'qa_department_submissions_verified_by_foreign', '`verified_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `qa_evidence_attachments`
CALL `tich_ensure_fk`('qa_evidence_attachments', 'qa_evidence_attachments_uploaded_by_foreign', '`uploaded_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `qa_plans`
CALL `tich_ensure_fk`('qa_plans', 'qa_plans_deployed_by_foreign', '`deployed_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `receipts`
CALL `tich_ensure_fk`('receipts', 'receipts_invoice_id_foreign', '`invoice_id`', 'invoices', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('receipts', 'receipts_issued_by_foreign', '`issued_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('receipts', 'receipts_payment_id_foreign', '`payment_id`', 'payments', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('receipts', 'receipts_student_account_id_foreign', '`student_account_id`', 'student_accounts', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('receipts', 'receipts_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `recruitment_applications`
CALL `tich_ensure_fk`('recruitment_applications', 'recruitment_applications_new_staff_id_foreign', '`new_staff_id`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('recruitment_applications', 'recruitment_applications_reviewed_by_foreign', '`reviewed_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('recruitment_applications', 'recruitment_applications_vacancy_id_foreign', '`vacancy_id`', 'job_vacancies', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `refunds`
CALL `tich_ensure_fk`('refunds', 'refunds_approved_by_foreign', '`approved_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('refunds', 'refunds_invoice_id_foreign', '`invoice_id`', 'invoices', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('refunds', 'refunds_payment_id_foreign', '`payment_id`', 'payments', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('refunds', 'refunds_processed_by_foreign', '`processed_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('refunds', 'refunds_requested_by_foreign', '`requested_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('refunds', 'refunds_student_account_id_foreign', '`student_account_id`', 'student_accounts', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('refunds', 'refunds_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `registered_units`
CALL `tich_ensure_fk`('registered_units', 'registered_units_semester_registration_id_foreign', '`semester_registration_id`', 'student_semester_registrations', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('registered_units', 'registered_units_unit_id_foreign', '`unit_id`', 'units', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `remark_requests`
CALL `tich_ensure_fk`('remark_requests', 'remark_requests_assigned_examiner_id_foreign', '`assigned_examiner_id`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('remark_requests', 'remark_requests_exam_result_id_foreign', '`exam_result_id`', 'exam_results', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('remark_requests', 'remark_requests_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `research_focus_areas`
CALL `tich_ensure_fk`('research_focus_areas', 'research_focus_areas_created_by_foreign', '`created_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('research_focus_areas', 'research_focus_areas_updated_by_foreign', '`updated_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `research_projects`
CALL `tich_ensure_fk`('research_projects', 'research_projects_created_by_foreign', '`created_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('research_projects', 'research_projects_focus_area_id_foreign', '`focus_area_id`', 'research_focus_areas', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('research_projects', 'research_projects_lead_researcher_id_foreign', '`lead_researcher_id`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('research_projects', 'research_projects_updated_by_foreign', '`updated_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `research_publications`
CALL `tich_ensure_fk`('research_publications', 'research_publications_created_by_foreign', '`created_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('research_publications', 'research_publications_updated_by_foreign', '`updated_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `rooms`
CALL `tich_ensure_fk`('rooms', 'rooms_campus_id_foreign', '`campus_id`', 'campuses', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `rpl_applications`
CALL `tich_ensure_fk`('rpl_applications', 'rpl_applications_applicant_id_foreign', '`applicant_id`', 'applicants', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('rpl_applications', 'rpl_applications_assessed_by_foreign', '`assessed_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('rpl_applications', 'rpl_applications_program_id_foreign', '`program_id`', 'academic_programs', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `sacco_loans`
CALL `tich_ensure_fk`('sacco_loans', 'sacco_loans_approved_by_foreign', '`approved_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('sacco_loans', 'sacco_loans_guarantor_1_id_foreign', '`guarantor_1_id`', 'sacco_members', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('sacco_loans', 'sacco_loans_guarantor_2_id_foreign', '`guarantor_2_id`', 'sacco_members', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('sacco_loans', 'sacco_loans_member_id_foreign', '`member_id`', 'sacco_members', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `sacco_loan_repayments`
CALL `tich_ensure_fk`('sacco_loan_repayments', 'sacco_loan_repayments_loan_id_foreign', '`loan_id`', 'sacco_loans', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `sacco_members`
CALL `tich_ensure_fk`('sacco_members', 'sacco_members_guarantor_1_id_foreign', '`guarantor_1_id`', 'sacco_members', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('sacco_members', 'sacco_members_guarantor_2_id_foreign', '`guarantor_2_id`', 'sacco_members', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('sacco_members', 'sacco_members_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `sacco_savings`
CALL `tich_ensure_fk`('sacco_savings', 'sacco_savings_member_id_foreign', '`member_id`', 'sacco_members', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('sacco_savings', 'sacco_savings_processed_by_foreign', '`processed_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `semesters`
CALL `tich_ensure_fk`('semesters', 'semesters_academic_year_id_foreign', '`academic_year_id`', 'academic_years', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `session_tokens`
CALL `tich_ensure_fk`('session_tokens', 'session_tokens_user_id_foreign', '`user_id`', 'users', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `sms_gateway_logs`
CALL `tich_ensure_fk`('sms_gateway_logs', 'sms_gateway_logs_notification_id_foreign', '`notification_id`', 'notifications', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `special_exam_requests`
CALL `tich_ensure_fk`('special_exam_requests', 'special_exam_requests_exam_result_id_foreign', '`exam_result_id`', 'exam_results', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('special_exam_requests', 'special_exam_requests_reviewed_by_foreign', '`reviewed_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('special_exam_requests', 'special_exam_requests_scheduled_exam_id_foreign', '`scheduled_exam_id`', 'exam_schedules', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('special_exam_requests', 'special_exam_requests_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `staff`
CALL `tich_ensure_fk`('staff', 'staff_bank_id_foreign', '`bank_id`', 'staff_bank_accounts', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('staff', 'staff_campus_id_foreign', '`campus_id`', 'campuses', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('staff', 'staff_department_id_foreign', '`department_id`', 'departments', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('staff', 'staff_line_manager_id_foreign', '`line_manager_id`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('staff', 'staff_pension_scheme_id_foreign', '`pension_scheme_id`', 'pension_schemes', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('staff', 'staff_user_id_foreign', '`user_id`', 'users', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `staff_allowances`
CALL `tich_ensure_fk`('staff_allowances', 'staff_allowances_approved_by_foreign', '`approved_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('staff_allowances', 'staff_allowances_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `staff_attendance`
CALL `tich_ensure_fk`('staff_attendance', 'staff_attendance_leave_request_id_foreign', '`leave_request_id`', 'leave_requests', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('staff_attendance', 'staff_attendance_recorded_by_foreign', '`recorded_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('staff_attendance', 'staff_attendance_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `staff_attendance_summary`
CALL `tich_ensure_fk`('staff_attendance_summary', 'staff_attendance_summary_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `staff_bank_accounts`
CALL `tich_ensure_fk`('staff_bank_accounts', 'staff_bank_accounts_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `staff_contracts`
CALL `tich_ensure_fk`('staff_contracts', 'staff_contracts_campus_id_foreign', '`campus_id`', 'campuses', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('staff_contracts', 'staff_contracts_department_id_foreign', '`department_id`', 'departments', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('staff_contracts', 'staff_contracts_line_manager_id_foreign', '`line_manager_id`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('staff_contracts', 'staff_contracts_new_contract_id_foreign', '`new_contract_id`', 'staff_contracts', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('staff_contracts', 'staff_contracts_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `staff_disciplinary_cases`
CALL `tich_ensure_fk`('staff_disciplinary_cases', 'staff_disciplinary_cases_assigned_investigator_id_foreign', '`assigned_investigator_id`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('staff_disciplinary_cases', 'staff_disciplinary_cases_decision_by_foreign', '`decision_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('staff_disciplinary_cases', 'staff_disciplinary_cases_reported_by_foreign', '`reported_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('staff_disciplinary_cases', 'staff_disciplinary_cases_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `staff_documents`
CALL `tich_ensure_fk`('staff_documents', 'staff_documents_approved_by_foreign', '`approved_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('staff_documents', 'staff_documents_rejected_by_foreign', '`rejected_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('staff_documents', 'staff_documents_replaced_by_id_foreign', '`replaced_by_id`', 'staff_documents', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('staff_documents', 'staff_documents_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('staff_documents', 'staff_documents_verified_by_foreign', '`verified_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `staff_document_templates`
CALL `tich_ensure_fk`('staff_document_templates', 'staff_document_templates_created_by_foreign', '`created_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `staff_next_of_kin`
CALL `tich_ensure_fk`('staff_next_of_kin', 'staff_next_of_kin_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `staff_onboarding`
CALL `tich_ensure_fk`('staff_onboarding', 'staff_onboarding_applicant_id_foreign', '`applicant_id`', 'recruitment_applications', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('staff_onboarding', 'staff_onboarding_locked_by_foreign', '`locked_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('staff_onboarding', 'staff_onboarding_reviewed_by_foreign', '`reviewed_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('staff_onboarding', 'staff_onboarding_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `staff_professional_licenses`
CALL `tich_ensure_fk`('staff_professional_licenses', 'staff_professional_licenses_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('staff_professional_licenses', 'staff_professional_licenses_verified_by_foreign', '`verified_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `staff_profile_change_requests`
CALL `tich_ensure_fk`('staff_profile_change_requests', 'staff_profile_change_requests_requested_by_user_id_foreign', '`requested_by_user_id`', 'users', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('staff_profile_change_requests', 'staff_profile_change_requests_reviewed_by_staff_id_foreign', '`reviewed_by_staff_id`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('staff_profile_change_requests', 'staff_profile_change_requests_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'CASCADE');

-- Foreign keys for `staff_qualifications`
CALL `tich_ensure_fk`('staff_qualifications', 'staff_qualifications_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('staff_qualifications', 'staff_qualifications_verified_by_foreign', '`verified_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `staff_status_history`
CALL `tich_ensure_fk`('staff_status_history', 'staff_status_history_approved_by_foreign', '`approved_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('staff_status_history', 'staff_status_history_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `statutory_deductions`
CALL `tich_ensure_fk`('statutory_deductions', 'statutory_deductions_payroll_item_id_foreign', '`payroll_item_id`', 'payroll_items', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('statutory_deductions', 'statutory_deductions_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `students`
CALL `tich_ensure_fk`('students', 'students_admission_letter_id_foreign', '`admission_letter_id`', 'admission_letters', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('students', 'students_application_id_foreign', '`application_id`', 'applicants', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('students', 'students_current_nursing_block_id_foreign', '`current_nursing_block_id`', 'nursing_blocks', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('students', 'students_current_semester_id_foreign', '`current_semester_id`', 'semesters', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('students', 'students_enrollment_campus_id_foreign', '`enrollment_campus_id`', 'campuses', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('students', 'students_program_id_foreign', '`program_id`', 'academic_programs', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('students', 'students_user_id_foreign', '`user_id`', 'users', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `student_accounts`
CALL `tich_ensure_fk`('student_accounts', 'student_accounts_academic_year_id_foreign', '`academic_year_id`', 'academic_years', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('student_accounts', 'student_accounts_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `student_addresses`
CALL `tich_ensure_fk`('student_addresses', 'student_addresses_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `student_next_of_kin`
CALL `tich_ensure_fk`('student_next_of_kin', 'student_next_of_kin_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `student_semester_registrations`
CALL `tich_ensure_fk`('student_semester_registrations', 'student_semester_registrations_registered_by_foreign', '`registered_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('student_semester_registrations', 'student_semester_registrations_semester_id_foreign', '`semester_id`', 'semesters', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('student_semester_registrations', 'student_semester_registrations_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `student_suggestions`
CALL `tich_ensure_fk`('student_suggestions', 'student_suggestions_reviewed_by_foreign', '`reviewed_by`', 'users', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('student_suggestions', 'student_suggestions_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'CASCADE');

-- Foreign keys for `supplementary_requests`
CALL `tich_ensure_fk`('supplementary_requests', 'supplementary_requests_exam_result_id_foreign', '`exam_result_id`', 'exam_results', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('supplementary_requests', 'supplementary_requests_scheduled_exam_id_foreign', '`scheduled_exam_id`', 'exam_schedules', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('supplementary_requests', 'supplementary_requests_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `three_way_matches`
CALL `tich_ensure_fk`('three_way_matches', 'three_way_matches_accepted_by_foreign', '`accepted_by`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('three_way_matches', 'three_way_matches_accounts_payable_id_foreign', '`accounts_payable_id`', 'accounts_payable', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('three_way_matches', 'three_way_matches_goods_received_note_id_foreign', '`goods_received_note_id`', 'goods_received_notes', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('three_way_matches', 'three_way_matches_matched_by_foreign', '`matched_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('three_way_matches', 'three_way_matches_purchase_order_id_foreign', '`purchase_order_id`', 'purchase_orders', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `timetable_entries`
CALL `tich_ensure_fk`('timetable_entries', 'timetable_entries_semester_id_foreign', '`semester_id`', 'semesters', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('timetable_entries', 'timetable_entries_unit_allocation_id_foreign', '`unit_allocation_id`', 'unit_allocations', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `units`
CALL `tich_ensure_fk`('units', 'units_co_requisite_unit_id_foreign', '`co_requisite_unit_id`', 'units', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('units', 'units_department_id_foreign', '`department_id`', 'departments', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('units', 'units_prerequisite_unit_id_foreign', '`prerequisite_unit_id`', 'units', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('units', 'units_program_id_foreign', '`program_id`', 'academic_programs', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `unit_allocations`
CALL `tich_ensure_fk`('unit_allocations', 'unit_allocations_campus_id_foreign', '`campus_id`', 'campuses', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('unit_allocations', 'unit_allocations_semester_id_foreign', '`semester_id`', 'semesters', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('unit_allocations', 'unit_allocations_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('unit_allocations', 'unit_allocations_unit_id_foreign', '`unit_id`', 'units', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `users`
CALL `tich_ensure_fk`('users', 'users_created_by_foreign', '`created_by`', 'users', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('users', 'users_staff_id_foreign', '`staff_id`', 'staff', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('users', 'users_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'SET NULL');

-- Foreign keys for `user_roles`
CALL `tich_ensure_fk`('user_roles', 'user_roles_assigned_by_foreign', '`assigned_by`', 'users', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('user_roles', 'user_roles_campus_id_foreign', '`campus_id`', 'campuses', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('user_roles', 'user_roles_department_id_foreign', '`department_id`', 'departments', '`id`', 'RESTRICT', 'SET NULL');
CALL `tich_ensure_fk`('user_roles', 'user_roles_role_id_foreign', '`role_id`', 'roles', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('user_roles', 'user_roles_user_id_foreign', '`user_id`', 'users', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `waitlist_entries`
CALL `tich_ensure_fk`('waitlist_entries', 'waitlist_entries_applicant_id_foreign', '`applicant_id`', 'applicants', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('waitlist_entries', 'waitlist_entries_program_id_foreign', '`program_id`', 'academic_programs', '`id`', 'RESTRICT', 'RESTRICT');

-- Foreign keys for `work_study_ledger`
CALL `tich_ensure_fk`('work_study_ledger', 'work_study_ledger_student_id_foreign', '`student_id`', 'students', '`id`', 'RESTRICT', 'RESTRICT');
CALL `tich_ensure_fk`('work_study_ledger', 'work_study_ledger_verified_by_foreign', '`verified_by`', 'staff', '`id`', 'RESTRICT', 'RESTRICT');


SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET time_zone = '+03:00';

-- Done. Existing row data was not modified.
