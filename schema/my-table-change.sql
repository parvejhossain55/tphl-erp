
-- 25/4/26



-- projects টেবিলে অনুপস্থিত কলাম যোগ করার স্ক্রিপ্ট
-- লোকাল থেকে লাইভে সিঙ্ক করার জন্য

ALTER TABLE `projects` 
ADD COLUMN `service_charge_per_emi` decimal(8,2) DEFAULT NULL AFTER `project_sale_status`;

ALTER TABLE `projects` 
ADD COLUMN `project_status` enum('Planning', 'In Progress', 'On Hold', 'Completed') COLLATE utf8mb4_general_ci DEFAULT NULL AFTER `service_charge_per_emi`;

ALTER TABLE `projects` 
ADD COLUMN `pm_id` int(11) DEFAULT NULL AFTER `project_status`;

ALTER TABLE `projects` 
ADD COLUMN `site_engineer_id` int(11) DEFAULT NULL AFTER `pm_id`;

ALTER TABLE `projects` 
ADD COLUMN `landman_id` int(11) DEFAULT NULL AFTER `site_engineer_id`;

ALTER TABLE `projects` 
ADD COLUMN `struct_engineer_id` int(11) DEFAULT NULL AFTER `landman_id`;

ALTER TABLE `projects` 
ADD COLUMN `operation_head_id` int(11) DEFAULT NULL AFTER `struct_engineer_id`;

ALTER TABLE `projects` 
ADD COLUMN `draftsman_id` int(11) DEFAULT NULL AFTER `operation_head_id`;

ALTER TABLE `projects` 
ADD COLUMN `architect_id` int(11) DEFAULT NULL AFTER `draftsman_id`;

ALTER TABLE `projects` 
ADD COLUMN `estimator_id` int(11) DEFAULT NULL AFTER `architect_id`;

ALTER TABLE `projects` 
ADD COLUMN `qc_id` int(11) DEFAULT NULL AFTER `estimator_id`;




ALTER TABLE `leads` 
MODIFY COLUMN `lead_type` ENUM(
    'No Response', 'Pending', 'Prospect', 'High Prospect', 
    'Will Visit', 'Visited', 'Sold', 'Lead Transferred', 
    'Project Transferred', 'Not Interested', 'Don\'t Call Again', 
    'Refund', 'Future', 'Cold'
) DEFAULT 'Pending';

ALTER TABLE `lead_follow_ups` 
MODIFY COLUMN `follow_up_type` ENUM(
    'call', 'visit', 'meeting', 'whatsapp', 'email', 
    'Inbound Call', 'Phone Call', 'WA Call', 'Imo Call', 
    'Messenger Call', 'Messenger Conversesion', 'WA Conversesion', 
    'Imo Message', 'Phone Massaging', 'Email', 'Number Off', 
    'Wrong Number', 'Invalid Number', 'Face To Face'
) DEFAULT NULL;

-- নতুন কলাম যুক্ত করা
ALTER TABLE `lead_follow_ups`
ADD COLUMN `follow_up_agent_id` INT(11) NULL DEFAULT NULL AFTER `assigned_with`,
ADD COLUMN `next_follow_up_plan_notes` TEXT NULL AFTER `next_action`,
ADD COLUMN `lead_status` VARCHAR(50) NULL AFTER `discussion_note`;



-- for phase2 database 



-- =====================================================
-- 40টি টেবিল লাইভ ডাটাবেজে তৈরি করার স্ক্রিপ্ট
-- Database: tphl_db (Live Server)
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. architect_engineering
CREATE TABLE IF NOT EXISTS `architect_engineering` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Internal architect FK users.id',
  `full_architecture_design_link` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ae_project` (`project_id`),
  KEY `idx_ae_user` (`user_id`),
  KEY `idx_ae_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. banks
CREATE TABLE IF NOT EXISTS `banks` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `bank_name` varchar(255) NOT NULL,
  `branch_name` varchar(255) NOT NULL,
  `account_number` varchar(255) NOT NULL,
  `routing_number` varchar(150) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Dynamic bank / branch registry used across all payment forms';

-- 3. boq_items
CREATE TABLE IF NOT EXISTS `boq_items` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `boq_id` int(11) UNSIGNED NOT NULL,
  `material_id` int(11) UNSIGNED NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `unit_cost` decimal(14,4) NOT NULL,
  `total_cost` decimal(16,4) GENERATED ALWAYS AS (`quantity` * `unit_cost`) STORED,
  PRIMARY KEY (`id`),
  KEY `idx_boqi_boq` (`boq_id`),
  KEY `idx_boqi_material` (`material_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. boq_master
CREATE TABLE IF NOT EXISTS `boq_master` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `design_type_id` int(11) DEFAULT NULL,
  `version` varchar(20) NOT NULL DEFAULT '1.0',
  `status` enum('Draft','Submitted','Approved','Rejected') NOT NULL DEFAULT 'Draft',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_boq_project` (`project_id`),
  KEY `idx_boq_approved_by` (`approved_by`),
  KEY `idx_target_id` (`target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. budget_extend_requests
CREATE TABLE IF NOT EXISTS `budget_extend_requests` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `initial_budget_id` int(11) UNSIGNED NOT NULL,
  `requested_by` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `amount` decimal(16,4) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `attachment_file` varchar(255) DEFAULT NULL COMMENT 'File path for uploaded evidence (PDF/Images)',
  `docs_link` varchar(500) DEFAULT NULL COMMENT 'External cloud link for supporting docs',
  PRIMARY KEY (`id`),
  KEY `idx_ber_project` (`project_id`),
  KEY `idx_ber_requested_by` (`requested_by`),
  KEY `idx_ber_approved_by` (`approved_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. client_refund_requests
CREATE TABLE IF NOT EXISTS `client_refund_requests` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` int(10) UNSIGNED NOT NULL COMMENT 'FK → lead_bookings.id',
  `lead_id` int(10) UNSIGNED NOT NULL COMMENT 'FK → leads.id',
  `project_id` int(11) NOT NULL COMMENT 'FK → projects.id',
  `flat_id` int(11) NOT NULL COMMENT 'FK → flats.id',
  `total_paid` decimal(15,2) NOT NULL DEFAULT 0.00,
  `retention_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `refund_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','refunded') NOT NULL DEFAULT 'pending',
  `cashout_request_id` int(11) DEFAULT NULL,
  `requested_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `refunded_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_booking` (`booking_id`),
  KEY `idx_lead` (`lead_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `vendors` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `company_name` varchar(200) DEFAULT NULL,
  `rating` decimal(3,2) NOT NULL DEFAULT 5.00,
  `status` enum('active','blacklisted') NOT NULL DEFAULT 'active',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vendor_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 7. comparison_items
CREATE TABLE IF NOT EXISTS `comparison_items` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `comparison_id` int(11) UNSIGNED NOT NULL,
  `material_id` int(11) UNSIGNED NOT NULL,
  `vendor_id` int(11) UNSIGNED NOT NULL,
  `unit_price` decimal(14,4) NOT NULL,
  `is_selected` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ci_comparison` (`comparison_id`),
  KEY `idx_ci_material` (`material_id`),
  KEY `idx_ci_vendor` (`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 8. contractors
CREATE TABLE IF NOT EXISTS `contractors` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `role` enum('Contractor','Subcontractor','Supervisor','Worker') NOT NULL DEFAULT 'Contractor',
  `trade` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `nid_number` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `daily_rate` decimal(10,2) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_oo_role` (`role`),
  KEY `idx_oo_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 9. contractor_bill_requests
CREATE TABLE IF NOT EXISTS `contractor_bill_requests` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `operator_id` int(11) UNSIGNED NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `bill_date` date NOT NULL,
  `work_description` text NOT NULL,
  `bill_amount` decimal(15,2) NOT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `verification_note` text DEFAULT NULL,
  `approved_amount` decimal(15,2) DEFAULT NULL,
  `status` enum('draft','submitted','verified','approved','rejected','paid','partially_paid','fully_paid') NOT NULL DEFAULT 'draft',
  `due_amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cbr_project` (`project_id`),
  KEY `idx_cbr_operator` (`operator_id`),
  KEY `idx_cbr_created_by` (`created_by`),
  KEY `idx_cbr_verified_by` (`verified_by`),
  KEY `idx_cbr_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 10. con_approval_logs
CREATE TABLE IF NOT EXISTS `con_approval_logs` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ref_table` varchar(100) NOT NULL,
  `ref_id` int(11) UNSIGNED NOT NULL,
  `step_name` varchar(100) NOT NULL,
  `actioned_by` int(11) NOT NULL,
  `status` enum('approved','rejected') NOT NULL,
  `remarks` text DEFAULT NULL,
  `actioned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cal_ref` (`ref_table`,`ref_id`),
  KEY `idx_cal_actioned_by` (`actioned_by`),
  KEY `idx_cal_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 11. con_financial_transactions
CREATE TABLE IF NOT EXISTS `con_financial_transactions` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ref_type` enum('contractor_bill','operational_fund','vendor_payment') NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `ref_id` int(11) UNSIGNED NOT NULL,
  `payment_type` enum('Advance','Partial','Full','Refund') NOT NULL,
  `payment_method` enum('Cash','Cheque','BankTransfer','MobileWallet','Other') NOT NULL,
  `amount_paid` decimal(15,2) NOT NULL,
  `reference_number` varchar(150) DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `transaction_date_str` date DEFAULT NULL,
  `update_status` enum('Completed','Failed','Pending') NOT NULL DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `disbursement_proof` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cft_ref` (`ref_type`,`ref_id`),
  KEY `idx_cft_processed` (`processed_by`),
  KEY `idx_cft_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 12. con_materials
CREATE TABLE IF NOT EXISTS `con_materials` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `min_stock_level` decimal(12,3) NOT NULL DEFAULT 0.000,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_material_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 13. con_material_issues
CREATE TABLE IF NOT EXISTS `con_material_issues` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `warehouse_id` int(10) UNSIGNED NOT NULL,
  `material_id` int(10) UNSIGNED NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `issued_to` int(11) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `work_plan_id` int(11) DEFAULT NULL,
  `issued_by` int(11) NOT NULL,
  `issue_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 14. con_material_issue_items
CREATE TABLE IF NOT EXISTS `con_material_issue_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `quantity` decimal(15,3) NOT NULL DEFAULT 0.000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 15. con_stocks
CREATE TABLE IF NOT EXISTS `con_stocks` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `material_id` int(11) UNSIGNED NOT NULL,
  `warehouse_id` int(11) UNSIGNED NOT NULL,
  `current_quantity` decimal(12,3) NOT NULL DEFAULT 0.000,
  `unit_price` decimal(15,2) DEFAULT 0.00,
  `total_value` decimal(15,2) DEFAULT 0.00,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stock_material_warehouse` (`material_id`,`warehouse_id`),
  KEY `idx_stock_warehouse` (`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 16. con_stock_movements
CREATE TABLE IF NOT EXISTS `con_stock_movements` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `material_id` int(11) UNSIGNED NOT NULL,
  `warehouse_id` int(11) UNSIGNED NOT NULL,
  `type` enum('IN','OUT') NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `ref_type` varchar(100) DEFAULT NULL,
  `ref_id` int(11) UNSIGNED DEFAULT NULL,
  `moved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sm_material` (`material_id`),
  KEY `idx_sm_warehouse` (`warehouse_id`),
  KEY `idx_sm_ref` (`ref_type`,`ref_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 17. con_warehouses
CREATE TABLE IF NOT EXISTS `con_warehouses` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 18. design_types
CREATE TABLE IF NOT EXISTS `design_types` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_design_type_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 19. initial_estimate_budgets
CREATE TABLE IF NOT EXISTS `initial_estimate_budgets` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `architecture_id` int(11) UNSIGNED NOT NULL,
  `total_area_sqft` decimal(12,2) DEFAULT NULL,
  `material_cost_total` decimal(15,2) DEFAULT 0.00,
  `labor_cost_total` decimal(15,2) DEFAULT 0.00,
  `other_costs` decimal(15,2) DEFAULT 0.00,
  `total_estimated_cost` decimal(15,2) DEFAULT 0.00,
  `status` enum('pending','approved','cancelled') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approval_date` timestamp NULL DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 20. initial_estimate_items
CREATE TABLE IF NOT EXISTS `initial_estimate_items` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `budget_id` int(11) UNSIGNED NOT NULL,
  `design_type_id` int(11) UNSIGNED NOT NULL,
  `material_id` int(11) UNSIGNED NOT NULL,
  `material_unit` varchar(50) NOT NULL,
  `estimated_qty` decimal(12,3) NOT NULL DEFAULT 0.000,
  `unit_cost_approx` decimal(12,2) NOT NULL DEFAULT 0.00,
  `remarks` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_iei_budget` (`budget_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 21. initial_estimate_labors
CREATE TABLE IF NOT EXISTS `initial_estimate_labors` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `budget_id` int(11) UNSIGNED NOT NULL,
  `design_type_id` int(11) UNSIGNED NOT NULL,
  `labor_type_name` varchar(100) NOT NULL,
  `rate_per_sqft` decimal(10,2) NOT NULL DEFAULT 0.00,
  `area_sqft` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_iel_budget` (`budget_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 22. land_visibility_study
CREATE TABLE IF NOT EXISTS `land_visibility_study` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `land_legal_doc_status` enum('Pending','Verified','Rejected') NOT NULL DEFAULT 'Pending',
  `digital_survey_report_link` varchar(500) DEFAULT NULL,
  `govt_approval_doc_link` varchar(500) DEFAULT NULL,
  `others_doc_link` varchar(500) DEFAULT NULL,
  `qc_reviewed_by_id` int(11) DEFAULT NULL,
  `qc_approval_status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `qc_reviewed_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lvs_project` (`project_id`),
  KEY `idx_lvs_qc` (`qc_reviewed_by_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 23. material_receiving
CREATE TABLE IF NOT EXISTS `material_receiving` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int(11) UNSIGNED NOT NULL,
  `received_by` int(11) NOT NULL,
  `receiving_date` date NOT NULL,
  `received_status` enum('Full','Partial','Rejected') NOT NULL DEFAULT 'Partial',
  `site_eng_note` text DEFAULT NULL,
  `site_eng_photo` varchar(500) DEFAULT NULL,
  `qc_checked_by` int(11) DEFAULT NULL,
  `qc_note` text DEFAULT NULL,
  `qc_photo` varchar(500) DEFAULT NULL,
  `overall_status` enum('Pending QC','Accepted','Partially Accepted','Rejected') NOT NULL DEFAULT 'Pending QC',
  `created_by` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_mr_po` (`purchase_order_id`),
  KEY `idx_mr_received_by` (`received_by`),
  KEY `idx_mr_qc_checked_by` (`qc_checked_by`),
  KEY `idx_mr_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 24. material_receiving_items
CREATE TABLE IF NOT EXISTS `material_receiving_items` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `receiving_id` int(11) UNSIGNED NOT NULL,
  `purchase_order_item_id` int(11) UNSIGNED NOT NULL,
  `ordered_quantity` decimal(12,3) NOT NULL,
  `received_quantity` decimal(12,3) NOT NULL DEFAULT 0.000,
  `status` enum('Accepted','Partially Accepted','Rejected','Pending QC') NOT NULL DEFAULT 'Pending QC',
  `return_quantity` decimal(12,3) NOT NULL DEFAULT 0.000,
  `penalty_applied` tinyint(1) NOT NULL DEFAULT 0,
  `site_engineer_checked` tinyint(1) NOT NULL DEFAULT 0,
  `qc_checked` tinyint(1) NOT NULL DEFAULT 0,
  `issue_reason` text DEFAULT NULL,
  `attachment` varchar(500) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mri_receiving` (`receiving_id`),
  KEY `idx_mri_poi` (`purchase_order_item_id`),
  KEY `idx_mri_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 25. operational_fund_requests
CREATE TABLE IF NOT EXISTS `operational_fund_requests` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `operator_id` int(11) UNSIGNED DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `purpose` text NOT NULL,
  `fund_date` date NOT NULL,
  `approved_amount` decimal(15,2) DEFAULT NULL,
  `status` enum('pending','approved','rejected','paid','partially_paid','fully_paid') NOT NULL DEFAULT 'pending',
  `due_amount` decimal(10,2) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ofr_project` (`project_id`),
  KEY `idx_ofr_requester` (`requester_id`),
  KEY `idx_ofr_operator` (`operator_id`),
  KEY `idx_ofr_created_by` (`created_by`),
  KEY `idx_ofr_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 26. project_drawings
CREATE TABLE IF NOT EXISTS `project_drawings` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `design_type_id` int(11) UNSIGNED NOT NULL,
  `is_in_house` tinyint(1) NOT NULL DEFAULT 1,
  `user_id` int(11) DEFAULT NULL,
  `designer_name` varchar(150) DEFAULT NULL,
  `designer_designation` varchar(150) DEFAULT NULL,
  `designer_company` varchar(150) DEFAULT NULL,
  `approved_by_id` int(11) DEFAULT NULL,
  `approval_date` date DEFAULT NULL,
  `design_link` varchar(500) DEFAULT NULL,
  `version` varchar(20) DEFAULT NULL,
  `status` enum('Draft','Submitted','Approved','Rejected') NOT NULL DEFAULT 'Draft',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pd_project` (`project_id`),
  KEY `idx_pd_design_type` (`design_type_id`),
  KEY `idx_pd_user` (`user_id`),
  KEY `idx_pd_approved_by` (`approved_by_id`),
  KEY `idx_pd_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 27. project_working_plans
CREATE TABLE IF NOT EXISTS `project_working_plans` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `initial_budget_id` int(11) UNSIGNED NOT NULL,
  `design_type_id` int(11) UNSIGNED NOT NULL,
  `year` smallint(5) UNSIGNED NOT NULL,
  `month` tinyint(3) UNSIGNED NOT NULL,
  `work_description` text NOT NULL,
  `planned_quantity` decimal(12,3) DEFAULT 0.000,
  `estimated_cost` decimal(16,2) NOT NULL DEFAULT 0.00,
  `created_by` int(11) NOT NULL,
  `approved_status` enum('Draft','Approved','Pending','Cancel') NOT NULL DEFAULT 'Draft',
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_pwp_budget` (`initial_budget_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 28. purchase_orders
CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` int(11) UNSIGNED NOT NULL,
  `created_by` int(11) NOT NULL,
  `status` enum('Draft','Issued','Partially Received','Fully Received','Cancelled') NOT NULL DEFAULT 'Draft',
  `order_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `approved_by_se` int(11) DEFAULT 0,
  `approved_by_pm` int(11) DEFAULT 0,
  `se_approved_at` datetime DEFAULT NULL,
  `pm_approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_po_request` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 29. purchase_order_items
CREATE TABLE IF NOT EXISTS `purchase_order_items` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int(11) UNSIGNED NOT NULL,
  `material_id` int(11) UNSIGNED NOT NULL,
  `selected_vendor_id` int(11) UNSIGNED NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `unit_price` decimal(14,4) NOT NULL,
  `total_price` decimal(16,4) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_poi_po` (`purchase_order_id`),
  KEY `idx_poi_material` (`material_id`),
  KEY `idx_poi_vendor` (`selected_vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 30. purchase_requests
CREATE TABLE IF NOT EXISTS `purchase_requests` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `bom_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `status` enum('Draft','Submitted','Approved','Rejected','PO Issued') NOT NULL DEFAULT 'Draft',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pr_project` (`project_id`),
  KEY `idx_pr_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 31. purchase_request_items
CREATE TABLE IF NOT EXISTS `purchase_request_items` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` int(11) UNSIGNED NOT NULL,
  `material_id` int(11) UNSIGNED NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pri_request` (`request_id`),
  KEY `idx_pri_material` (`material_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 32. quotations
CREATE TABLE IF NOT EXISTS `quotations` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` int(11) UNSIGNED NOT NULL,
  `vendor_id` int(11) UNSIGNED NOT NULL,
  `validity_date` date DEFAULT NULL,
  `status` enum('Received','Shortlisted','Rejected') NOT NULL DEFAULT 'Received',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_q_request` (`request_id`),
  KEY `idx_q_vendor` (`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 33. quotation_comparisons
CREATE TABLE IF NOT EXISTS `quotation_comparisons` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` int(11) UNSIGNED NOT NULL,
  `prepared_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_qc_request` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 34. quotation_items
CREATE TABLE IF NOT EXISTS `quotation_items` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `quotation_id` int(11) UNSIGNED NOT NULL,
  `material_id` int(11) UNSIGNED NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `unit_price` decimal(14,4) NOT NULL,
  `total_price` decimal(16,4) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_qi_quotation` (`quotation_id`),
  KEY `idx_qi_material` (`material_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 35. soil_tests
CREATE TABLE IF NOT EXISTS `soil_tests` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `site_engineer_id` int(11) NOT NULL,
  `field_data` text DEFAULT NULL,
  `field_test_doc_link` varchar(255) DEFAULT NULL,
  `lab_test_document_link` varchar(500) DEFAULT NULL,
  `test_date` date DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_st_project` (`project_id`),
  KEY `idx_st_engineer` (`site_engineer_id`),
  KEY `idx_st_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 36. target_fixed
CREATE TABLE IF NOT EXISTS `target_fixed` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `work_plan_id` int(11) DEFAULT NULL,
  `target_title` varchar(200) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `year` smallint(5) UNSIGNED NOT NULL,
  `month` tinyint(3) UNSIGNED NOT NULL,
  `week_number` tinyint(3) UNSIGNED DEFAULT NULL,
  `target_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `gain_percentage` decimal(5,2) DEFAULT 0.00,
  `photo_link` varchar(500) DEFAULT NULL,
  `field_test_details` text DEFAULT NULL,
  `lab_test_result` text DEFAULT NULL,
  `approved_by_site_eng` tinyint(1) NOT NULL DEFAULT 0,
  `approved_by_qc` tinyint(1) NOT NULL DEFAULT 0,
  `approved_by_pc` tinyint(1) NOT NULL DEFAULT 0,
  `approved_by_pd` tinyint(1) NOT NULL DEFAULT 0,
  `approved_by_manager` tinyint(1) NOT NULL DEFAULT 0,
  `qc_reviewed_by_id` int(11) DEFAULT NULL,
  `is_lab_test` int(11) NOT NULL DEFAULT 0,
  `lab_test_date` date DEFAULT NULL,
  `target_details` text DEFAULT NULL,
  `qc_reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tf_project` (`project_id`),
  KEY `idx_tf_user` (`user_id`),
  KEY `idx_tf_qc_reviewed_by` (`qc_reviewed_by_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 37. vendor_payment_requests
CREATE TABLE IF NOT EXISTS `vendor_payment_requests` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) UNSIGNED NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `purchase_order_id` int(11) UNSIGNED NOT NULL,
  `payment_type` enum('Advance','Partial','Final') NOT NULL DEFAULT 'Final',
  `created_by` int(11) NOT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `invoice_amount` decimal(16,4) NOT NULL,
  `delivery_verified` tinyint(1) NOT NULL DEFAULT 0,
  `penalty_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_payable_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `qc_verified` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('Draft','Submitted','Approved','Rejected','Paid','Partially_Paid','Fully_Paid') NOT NULL DEFAULT 'Draft',
  `approved_amount` decimal(16,4) DEFAULT NULL,
  `net_payable` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_attachment` varchar(255) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `due_amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vpr_vendor` (`vendor_id`),
  KEY `idx_vpr_po` (`purchase_order_id`),
  KEY `idx_vpr_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 38. vendor_penalties
CREATE TABLE IF NOT EXISTS `vendor_penalties` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) UNSIGNED NOT NULL,
  `is_deducted_from_bill` tinyint(1) NOT NULL DEFAULT 0,
  `purchase_order_id` int(11) UNSIGNED NOT NULL,
  `receiving_id` int(11) UNSIGNED DEFAULT NULL,
  `payment_request_id` int(11) UNSIGNED DEFAULT NULL,
  `penalty_category` enum('Late Delivery','Quality Issue','Shortage','Others') NOT NULL,
  `penalty_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `is_fixed_amount` tinyint(1) DEFAULT 1,
  `reason` text NOT NULL,
  `action_taken` enum('Deduct from Bill','Replacement Taken','Fine Paid') DEFAULT 'Deduct from Bill',
  `penalty_status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `attachment` varchar(500) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_vp_vendor` (`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 39. vendor_ratings
CREATE TABLE IF NOT EXISTS `vendor_ratings` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) UNSIGNED NOT NULL,
  `rated_by` int(11) NOT NULL,
  `rating` decimal(2,1) NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vr_vendor` (`vendor_id`),
  KEY `idx_vr_rated_by` (`rated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 40. weekly_progress_report
CREATE TABLE IF NOT EXISTS `weekly_progress_report` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `site_engineer_id` int(11) NOT NULL,
  `work_plan_id` int(11) DEFAULT NULL,
  `week_no` int(11) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `work_done` text NOT NULL,
  `progress_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `report_date` date NOT NULL,
  `progress_image` varchar(255) DEFAULT NULL,
  `weather_condition` varchar(100) DEFAULT NULL,
  `approved_by_pm` int(11) DEFAULT NULL,
  `manpower_count` smallint(5) UNSIGNED DEFAULT NULL,
  `approved_by_qc` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dpr_project` (`project_id`),
  KEY `idx_dpr_engineer` (`site_engineer_id`),
  KEY `idx_dpr_date` (`report_date`),
  KEY `idx_dpr_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- সব টেবিল সফলভাবে তৈরি হয়েছে (40 টি)
-- =====================================================