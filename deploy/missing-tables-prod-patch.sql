-- Missing production tables (excluding academic_records + student_financial_records already applied)
-- Ordered by FK dependency (parents before children).
-- Generated from local tich_erp 2026-09-30 17:49:41
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- Table: `asset_audits`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `asset_audits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint(20) unsigned NOT NULL,
  `auditor_id` bigint(20) unsigned NOT NULL,
  `verification_status` varchar(50) NOT NULL DEFAULT 'pending',
  `location_verified` tinyint(1) NOT NULL DEFAULT 0,
  `custodian_verified` tinyint(1) NOT NULL DEFAULT 0,
  `condition` varchar(50) NOT NULL DEFAULT 'unknown',
  `notes` text DEFAULT NULL,
  `photo_paths` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`photo_paths`)),
  `submitted_at` date DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` date DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_audits_asset_id_foreign` (`asset_id`),
  KEY `asset_audits_auditor_id_foreign` (`auditor_id`),
  KEY `asset_audits_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `asset_audits_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asset_audits_auditor_id_foreign` FOREIGN KEY (`auditor_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asset_audits_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: `asset_disposals`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `asset_disposals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint(20) unsigned NOT NULL,
  `disposal_type` varchar(50) NOT NULL DEFAULT 'write_off',
  `disposal_date` date DEFAULT NULL,
  `disposed_value` decimal(12,2) DEFAULT 0.00,
  `reason` text NOT NULL,
  `disposal_details` text DEFAULT NULL,
  `requested_by` bigint(20) unsigned NOT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approval_status` varchar(50) NOT NULL DEFAULT 'pending',
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `approved_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_disposals_asset_id_foreign` (`asset_id`),
  KEY `asset_disposals_requested_by_foreign` (`requested_by`),
  KEY `asset_disposals_approved_by_foreign` (`approved_by`),
  CONSTRAINT `asset_disposals_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `asset_disposals_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asset_disposals_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: `asset_maintenance`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `asset_maintenance` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint(20) unsigned NOT NULL,
  `maintenance_type` varchar(50) NOT NULL DEFAULT 'repair',
  `fault_description` text NOT NULL,
  `priority` varchar(50) NOT NULL DEFAULT 'medium',
  `scheduled_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `work_done` text DEFAULT NULL,
  `parts_used` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parts_used`)),
  `parts_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `labour_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `technician_name` varchar(200) DEFAULT NULL,
  `technician_phone` varchar(30) DEFAULT NULL,
  `attachment_paths` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachment_paths`)),
  `completed_by` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_maintenance_asset_id_foreign` (`asset_id`),
  KEY `asset_maintenance_completed_by_foreign` (`completed_by`),
  CONSTRAINT `asset_maintenance_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asset_maintenance_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: `asset_movements`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `asset_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint(20) unsigned NOT NULL,
  `from_location` varchar(255) DEFAULT NULL,
  `to_location` varchar(255) DEFAULT NULL,
  `reason` text NOT NULL,
  `movement_type` varchar(50) NOT NULL DEFAULT 'transfer',
  `requested_by` bigint(20) unsigned NOT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approval_status` varchar(50) NOT NULL DEFAULT 'pending',
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `movement_date` date DEFAULT NULL,
  `approved_at` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_movements_asset_id_foreign` (`asset_id`),
  KEY `asset_movements_requested_by_foreign` (`requested_by`),
  KEY `asset_movements_approved_by_foreign` (`approved_by`),
  CONSTRAINT `asset_movements_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `asset_movements_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asset_movements_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: `marketing_leads`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketing_leads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(300) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `stage` varchar(255) NOT NULL DEFAULT 'new',
  `program_id` bigint(20) unsigned DEFAULT NULL,
  `intake` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `next_followup` date DEFAULT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `marketing_leads_program_id_foreign` (`program_id`),
  KEY `marketing_leads_assigned_to_foreign` (`assigned_to`),
  KEY `marketing_leads_created_by_foreign` (`created_by`),
  KEY `marketing_leads_updated_by_foreign` (`updated_by`),
  CONSTRAINT `marketing_leads_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `marketing_leads_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `marketing_leads_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `academic_programs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `marketing_leads_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: `marketing_lead_activities`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketing_lead_activities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) unsigned NOT NULL,
  `activity_type` varchar(255) NOT NULL,
  `scheduled_date` date NOT NULL,
  `scheduled_time` time DEFAULT NULL,
  `description` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_date` date DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `marketing_lead_activities_lead_id_foreign` (`lead_id`),
  KEY `marketing_lead_activities_created_by_foreign` (`created_by`),
  KEY `marketing_lead_activities_updated_by_foreign` (`updated_by`),
  CONSTRAINT `marketing_lead_activities_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `marketing_lead_activities_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `marketing_leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `marketing_lead_activities_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: `marketing_reports`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketing_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `report_type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `report_date` date NOT NULL,
  `summary` text DEFAULT NULL,
  `commentary` text DEFAULT NULL,
  `anomalies` text DEFAULT NULL,
  `status` enum('draft','submitted','approved','distributed','archived') NOT NULL DEFAULT 'draft',
  `prepared_by` bigint(20) unsigned NOT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `distributed_by` bigint(20) unsigned DEFAULT NULL,
  `distribution_list` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `marketing_reports_prepared_by_foreign` (`prepared_by`),
  KEY `marketing_reports_reviewed_by_foreign` (`reviewed_by`),
  KEY `marketing_reports_approved_by_foreign` (`approved_by`),
  KEY `marketing_reports_distributed_by_foreign` (`distributed_by`),
  KEY `marketing_reports_created_by_foreign` (`created_by`),
  KEY `marketing_reports_updated_by_foreign` (`updated_by`),
  CONSTRAINT `marketing_reports_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `marketing_reports_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `marketing_reports_distributed_by_foreign` FOREIGN KEY (`distributed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `marketing_reports_prepared_by_foreign` FOREIGN KEY (`prepared_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  CONSTRAINT `marketing_reports_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `marketing_reports_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: `marketing_report_attachments`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketing_report_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint(20) unsigned NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(255) NOT NULL,
  `size` bigint(20) unsigned NOT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `marketing_report_attachments_report_id_foreign` (`report_id`),
  KEY `marketing_report_attachments_uploaded_by_foreign` (`uploaded_by`),
  CONSTRAINT `marketing_report_attachments_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `marketing_reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `marketing_report_attachments_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: `qa_certification_reminders`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qa_certification_reminders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned NOT NULL,
  `certification_name` varchar(200) NOT NULL,
  `expiry_date` date NOT NULL,
  `reminder_sent_at` date DEFAULT NULL,
  `escalation_sent_at` date DEFAULT NULL,
  `is_expired` tinyint(1) NOT NULL DEFAULT 0,
  `raised_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `qa_certification_reminders_staff_id_foreign` (`staff_id`),
  KEY `qa_certification_reminders_raised_by_foreign` (`raised_by`),
  KEY `qa_certification_reminders_expiry_date_is_expired_index` (`expiry_date`,`is_expired`),
  CONSTRAINT `qa_certification_reminders_raised_by_foreign` FOREIGN KEY (`raised_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `qa_certification_reminders_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: `qa_training_credits`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qa_training_credits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned NOT NULL,
  `credit_type` varchar(30) NOT NULL,
  `credit_value` int(10) unsigned NOT NULL,
  `event_id` bigint(20) unsigned DEFAULT NULL,
  `awarded_at` date NOT NULL,
  `expires_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `qa_training_credits_event_id_foreign` (`event_id`),
  KEY `qa_training_credits_staff_id_credit_type_index` (`staff_id`,`credit_type`),
  KEY `qa_training_credits_expires_at_index` (`expires_at`),
  CONSTRAINT `qa_training_credits_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `qa_capacity_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `qa_training_credits_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: `qa_training_event_enrolments`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qa_training_event_enrolments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `qa_capacity_session_id` bigint(20) unsigned NOT NULL,
  `staff_id` bigint(20) unsigned NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `decline_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `qa_enrolment_unique` (`qa_capacity_session_id`,`staff_id`),
  KEY `qa_training_event_enrolments_staff_id_foreign` (`staff_id`),
  CONSTRAINT `qa_training_event_enrolments_qa_capacity_session_id_foreign` FOREIGN KEY (`qa_capacity_session_id`) REFERENCES `qa_capacity_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `qa_training_event_enrolments_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: `qa_training_event_registrations`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qa_training_event_registrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `qa_capacity_session_id` bigint(20) unsigned NOT NULL,
  `title` varchar(300) NOT NULL,
  `description` text DEFAULT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime DEFAULT NULL,
  `provider` varchar(200) DEFAULT NULL,
  `location` varchar(300) DEFAULT NULL,
  `credit_type` varchar(30) DEFAULT NULL,
  `credit_value` int(10) unsigned DEFAULT NULL,
  `target_audience` text DEFAULT NULL,
  `max_attendees` int(10) unsigned DEFAULT NULL,
  `enrolled_count` int(10) unsigned NOT NULL DEFAULT 0,
  `status` varchar(30) NOT NULL DEFAULT 'open',
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `qa_training_event_registrations_created_by_foreign` (`created_by`),
  KEY `qa_training_event_registrations_status_start_at_index` (`status`,`start_at`),
  CONSTRAINT `qa_training_event_registrations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: `rfqs`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rfqs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rfq_number` varchar(50) NOT NULL,
  `requisition_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `item_description` text NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `specifications` text DEFAULT NULL,
  `delivery_timeline` varchar(100) DEFAULT NULL,
  `delivery_location` varchar(500) DEFAULT NULL,
  `submission_deadline` date DEFAULT NULL,
  `minimum_categories` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`minimum_categories`)),
  `preferred_categories` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferred_categories`)),
  `minimum_suppliers` int(11) NOT NULL DEFAULT 3,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `award_decision` varchar(50) DEFAULT NULL,
  `awarded_supplier_id` bigint(20) unsigned DEFAULT NULL,
  `awarded_amount` decimal(12,2) DEFAULT NULL,
  `approval_level` varchar(50) DEFAULT NULL,
  `approval_status` varchar(50) NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `award_letter_path` text DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rfqs_rfq_number_unique` (`rfq_number`),
  KEY `rfqs_requisition_id_foreign` (`requisition_id`),
  KEY `rfqs_created_by_foreign` (`created_by`),
  KEY `rfqs_awarded_supplier_id_foreign` (`awarded_supplier_id`),
  KEY `rfqs_approved_by_foreign` (`approved_by`),
  CONSTRAINT `rfqs_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rfqs_awarded_supplier_id_foreign` FOREIGN KEY (`awarded_supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rfqs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `rfqs_requisition_id_foreign` FOREIGN KEY (`requisition_id`) REFERENCES `procurement_requisitions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: `rfq_clarifications`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rfq_clarifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rfq_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `asked_by` bigint(20) unsigned DEFAULT NULL,
  `question` text NOT NULL,
  `answer` text DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `asked_at` datetime NOT NULL DEFAULT current_timestamp(),
  `answered_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rfq_clarifications_rfq_id_foreign` (`rfq_id`),
  KEY `rfq_clarifications_supplier_id_foreign` (`supplier_id`),
  KEY `rfq_clarifications_asked_by_foreign` (`asked_by`),
  CONSTRAINT `rfq_clarifications_asked_by_foreign` FOREIGN KEY (`asked_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rfq_clarifications_rfq_id_foreign` FOREIGN KEY (`rfq_id`) REFERENCES `rfqs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rfq_clarifications_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: `rfq_evaluations`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rfq_evaluations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rfq_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `evaluated_by` bigint(20) unsigned NOT NULL,
  `price_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `technical_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `delivery_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `payment_terms_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `performance_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `total_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `rank` int(11) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `has_conflict_of_interest` tinyint(1) NOT NULL DEFAULT 0,
  `conflict_of_interest_details` text DEFAULT NULL,
  `is_recused` tinyint(1) NOT NULL DEFAULT 0,
  `evaluated_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `rfq_evaluations_rfq_id_supplier_id_evaluated_by_unique` (`rfq_id`,`supplier_id`,`evaluated_by`),
  KEY `rfq_evaluations_supplier_id_foreign` (`supplier_id`),
  KEY `rfq_evaluations_evaluated_by_foreign` (`evaluated_by`),
  CONSTRAINT `rfq_evaluations_evaluated_by_foreign` FOREIGN KEY (`evaluated_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `rfq_evaluations_rfq_id_foreign` FOREIGN KEY (`rfq_id`) REFERENCES `rfqs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rfq_evaluations_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: `rfq_quotations`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rfq_quotations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rfq_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `submitted_by` bigint(20) unsigned DEFAULT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `total_price` decimal(12,2) NOT NULL,
  `delivery_period` varchar(100) DEFAULT NULL,
  `payment_terms` varchar(500) DEFAULT NULL,
  `warranty_terms` varchar(500) DEFAULT NULL,
  `validity_period` varchar(100) DEFAULT NULL,
  `technical_notes` text DEFAULT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `status` varchar(50) NOT NULL DEFAULT 'submitted',
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `revised_at` datetime DEFAULT NULL,
  `locked_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rfq_quotations_rfq_id_supplier_id_unique` (`rfq_id`,`supplier_id`),
  KEY `rfq_quotations_supplier_id_foreign` (`supplier_id`),
  KEY `rfq_quotations_submitted_by_foreign` (`submitted_by`),
  CONSTRAINT `rfq_quotations_rfq_id_foreign` FOREIGN KEY (`rfq_id`) REFERENCES `rfqs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rfq_quotations_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rfq_quotations_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: `rfq_suppliers`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rfq_suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rfq_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `invitation_status` varchar(50) NOT NULL DEFAULT 'invited',
  `decline_reason` text DEFAULT NULL,
  `invited_at` datetime NOT NULL DEFAULT current_timestamp(),
  `responded_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rfq_suppliers_rfq_id_supplier_id_unique` (`rfq_id`,`supplier_id`),
  KEY `rfq_suppliers_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `rfq_suppliers_rfq_id_foreign` FOREIGN KEY (`rfq_id`) REFERENCES `rfqs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rfq_suppliers_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: `procurement_invoices`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `procurement_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `rfq_id` bigint(20) unsigned DEFAULT NULL,
  `requisition_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `retention_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `retention_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `released_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_certificate` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `matched_by` bigint(20) unsigned DEFAULT NULL,
  `matched_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `procurement_invoices_invoice_number_unique` (`invoice_number`),
  KEY `procurement_invoices_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `procurement_invoices_rfq_id_foreign` (`rfq_id`),
  KEY `procurement_invoices_requisition_id_foreign` (`requisition_id`),
  KEY `procurement_invoices_created_by_foreign` (`created_by`),
  KEY `procurement_invoices_matched_by_foreign` (`matched_by`),
  KEY `procurement_invoices_supplier_id_status_index` (`supplier_id`,`status`),
  KEY `procurement_invoices_status_due_date_index` (`status`,`due_date`),
  CONSTRAINT `procurement_invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procurement_invoices_matched_by_foreign` FOREIGN KEY (`matched_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procurement_invoices_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`),
  CONSTRAINT `procurement_invoices_requisition_id_foreign` FOREIGN KEY (`requisition_id`) REFERENCES `procurement_requisitions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procurement_invoices_rfq_id_foreign` FOREIGN KEY (`rfq_id`) REFERENCES `rfqs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procurement_invoices_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: `discrepancies`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `discrepancies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `three_way_match_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `discrepancy_type` varchar(50) NOT NULL,
  `field_name` varchar(100) DEFAULT NULL,
  `po_value` text DEFAULT NULL,
  `quotation_value` text DEFAULT NULL,
  `invoice_value` text DEFAULT NULL,
  `deviation_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) NOT NULL DEFAULT 'open',
  `raised_by` bigint(20) unsigned NOT NULL,
  `resolved_by` bigint(20) unsigned DEFAULT NULL,
  `resolution_note` text DEFAULT NULL,
  `resolution_type` varchar(50) DEFAULT NULL,
  `credit_note_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_response_at` datetime DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `escalated_at` datetime DEFAULT NULL,
  `supplier_response` text DEFAULT NULL,
  `is_escalated` tinyint(1) NOT NULL DEFAULT 0,
  `is_fraud_suspected` tinyint(1) NOT NULL DEFAULT 0,
  `escalation_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `discrepancies_three_way_match_id_foreign` (`three_way_match_id`),
  KEY `discrepancies_raised_by_foreign` (`raised_by`),
  KEY `discrepancies_resolved_by_foreign` (`resolved_by`),
  KEY `discrepancies_invoice_id_status_index` (`invoice_id`,`status`),
  KEY `discrepancies_supplier_id_status_index` (`supplier_id`,`status`),
  KEY `discrepancies_status_is_escalated_index` (`status`,`is_escalated`),
  CONSTRAINT `discrepancies_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `procurement_invoices` (`id`),
  CONSTRAINT `discrepancies_raised_by_foreign` FOREIGN KEY (`raised_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `discrepancies_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `discrepancies_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `discrepancies_three_way_match_id_foreign` FOREIGN KEY (`three_way_match_id`) REFERENCES `three_way_matches` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: `credit_notes`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `credit_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `discrepancy_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `credit_note_number` varchar(50) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reason` text NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `applied_to_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `applied_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `credit_notes_credit_note_number_unique` (`credit_note_number`),
  KEY `credit_notes_discrepancy_id_foreign` (`discrepancy_id`),
  KEY `credit_notes_invoice_id_foreign` (`invoice_id`),
  KEY `credit_notes_applied_to_invoice_id_foreign` (`applied_to_invoice_id`),
  KEY `credit_notes_created_by_foreign` (`created_by`),
  KEY `credit_notes_supplier_id_status_index` (`supplier_id`,`status`),
  CONSTRAINT `credit_notes_applied_to_invoice_id_foreign` FOREIGN KEY (`applied_to_invoice_id`) REFERENCES `procurement_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `credit_notes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `credit_notes_discrepancy_id_foreign` FOREIGN KEY (`discrepancy_id`) REFERENCES `discrepancies` (`id`),
  CONSTRAINT `credit_notes_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `procurement_invoices` (`id`),
  CONSTRAINT `credit_notes_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: `payment_audits`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_audits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `actor` varchar(100) NOT NULL,
  `action` varchar(200) NOT NULL,
  `result` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_audits_invoice_id_created_at_index` (`invoice_id`,`created_at`),
  CONSTRAINT `payment_audits_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `procurement_invoices` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: `procurement_payments`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `procurement_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `payment_number` varchar(50) NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `retention_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `released_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) NOT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `transaction_channel_ref` varchar(100) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `mpesa_stk_request_id` bigint(20) unsigned DEFAULT NULL,
  `recorded_by` bigint(20) unsigned NOT NULL,
  `payment_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `procurement_payments_payment_number_unique` (`payment_number`),
  KEY `procurement_payments_recorded_by_foreign` (`recorded_by`),
  KEY `procurement_payments_mpesa_stk_request_id_foreign` (`mpesa_stk_request_id`),
  KEY `procurement_payments_invoice_id_status_index` (`invoice_id`,`status`),
  KEY `procurement_payments_supplier_id_payment_date_index` (`supplier_id`,`payment_date`),
  CONSTRAINT `procurement_payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `procurement_invoices` (`id`),
  CONSTRAINT `procurement_payments_mpesa_stk_request_id_foreign` FOREIGN KEY (`mpesa_stk_request_id`) REFERENCES `mpesa_stk_requests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `procurement_payments_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `staff` (`id`),
  CONSTRAINT `procurement_payments_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: `grn_items`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `grn_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `grn_id` bigint(20) unsigned NOT NULL,
  `purchase_order_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `item_name` varchar(300) NOT NULL,
  `item_description` varchar(500) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `classification` varchar(50) NOT NULL DEFAULT 'consumable',
  `quantity_ordered` decimal(12,2) NOT NULL DEFAULT 0.00,
  `quantity_received` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `condition` varchar(50) NOT NULL DEFAULT 'good',
  `received_date` date DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grn_items_grn_id_foreign` (`grn_id`),
  KEY `grn_items_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `grn_items_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `grn_items_grn_id_foreign` FOREIGN KEY (`grn_id`) REFERENCES `goods_received_notes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `grn_items_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `grn_items_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: `stock_alerts`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stock_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inventory_item_id` bigint(20) unsigned NOT NULL,
  `alert_type` varchar(50) NOT NULL DEFAULT 'low_stock',
  `current_stock` int(11) NOT NULL DEFAULT 0,
  `reorder_level` int(11) NOT NULL DEFAULT 0,
  `recommended_quantity` int(11) NOT NULL DEFAULT 0,
  `triggered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `closed_at` timestamp NULL DEFAULT NULL,
  `channels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`channels`)),
  `sent_to` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sent_to`)),
  `requisition_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_alerts_inventory_item_id_foreign` (`inventory_item_id`),
  KEY `stock_alerts_requisition_id_foreign` (`requisition_id`),
  CONSTRAINT `stock_alerts_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_alerts_requisition_id_foreign` FOREIGN KEY (`requisition_id`) REFERENCES `procurement_requisitions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: `stock_issues`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stock_issues` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inventory_item_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned NOT NULL,
  `requested_by` bigint(20) unsigned NOT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reason` text NOT NULL,
  `approval_status` varchar(50) NOT NULL DEFAULT 'pending',
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `approved_at` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_issues_inventory_item_id_foreign` (`inventory_item_id`),
  KEY `stock_issues_department_id_foreign` (`department_id`),
  KEY `stock_issues_requested_by_foreign` (`requested_by`),
  KEY `stock_issues_approved_by_foreign` (`approved_by`),
  CONSTRAINT `stock_issues_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_issues_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_issues_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_issues_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
