-- ==========================================================================
-- TPHL ERP - Construction & Engineering Modules Table Schema Export
-- Generated at: 2026-06-13 07:34:43
-- ==========================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- Table structure for table `architect_engineering`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `architect_engineering`;
CREATE TABLE `architect_engineering` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Internal architect FK users.id',
  `full_architecture_design_link` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('draft','partially','fully') DEFAULT 'draft',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ae_project` (`project_id`),
  KEY `idx_ae_user` (`user_id`),
  KEY `idx_ae_created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `project_drawings`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `project_drawings`;
CREATE TABLE `project_drawings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `phase_id` int(10) unsigned DEFAULT NULL COMMENT 'Optional: which construction phase this drawing belongs to',
  `design_type_id` int(10) unsigned NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `soil_tests`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `soil_tests`;
CREATE TABLE `soil_tests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `project_legal_sheets`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `project_legal_sheets`;
CREATE TABLE `project_legal_sheets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `sheet_type` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `version` varchar(50) DEFAULT 'V1',
  `link` text NOT NULL,
  `submitted_by` bigint(20) unsigned NOT NULL,
  `remark` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_sheet_type` (`sheet_type`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table structure for table `design_types`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `design_types`;
CREATE TABLE `design_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_design_type_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `work_types`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `work_types`;
CREATE TABLE `work_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `design_type_id` bigint(20) unsigned NOT NULL,
  `default_phase_name` varchar(150) DEFAULT NULL COMMENT 'Suggested construction phase for this work type',
  `work_type_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `project_phases`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `project_phases`;
CREATE TABLE `project_phases` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `phase_name` varchar(150) NOT NULL,
  `phase_order` tinyint(4) NOT NULL DEFAULT 1,
  `weight_percentage` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'All phases must sum to 100%',
  `planned_start_date` date DEFAULT NULL,
  `planned_end_date` date DEFAULT NULL,
  `actual_start_date` date DEFAULT NULL,
  `actual_end_date` date DEFAULT NULL,
  `planned_progress` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT '% within this phase planned',
  `actual_progress` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT '% within this phase achieved',
  `status` enum('Not Started','In Progress','Completed','At Risk','Delayed') NOT NULL DEFAULT 'Not Started',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `phase_type` enum('pre_construction','construction','finishing') DEFAULT 'construction',
  PRIMARY KEY (`id`),
  KEY `idx_pp_project` (`project_id`),
  KEY `idx_pp_order` (`project_id`,`phase_order`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Module 2: Per-project phase definitions cloned from phase_template_defaults';

-- -----------------------------------------------------
-- Table structure for table `project_phase_items`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `project_phase_items`;
CREATE TABLE `project_phase_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `phase_id` int(10) unsigned NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `item_order` tinyint(4) NOT NULL DEFAULT 1,
  `weight_pct` decimal(5,2) NOT NULL DEFAULT 0.00,
  `planned_progress` decimal(5,2) NOT NULL DEFAULT 0.00,
  `actual_progress` decimal(5,2) NOT NULL DEFAULT 0.00,
  `design_type_id` int(10) unsigned DEFAULT NULL,
  `work_type_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('Not Started','In Progress','Completed','At Risk') NOT NULL DEFAULT 'Not Started',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_ppi_phase` (`phase_id`),
  KEY `fk_ppi_wtype` (`work_type_id`),
  KEY `fk_ppi_dtype` (`design_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table structure for table `project_timelines`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `project_timelines`;
CREATE TABLE `project_timelines` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `project_code` varchar(50) DEFAULT NULL,
  `project_type` varchar(100) DEFAULT NULL COMMENT 'Residential / Commercial / Industrial',
  `project_category` varchar(100) DEFAULT NULL COMMENT 'High Rise / Low Rise / Industrial Shed',
  `handover_months` int(11) NOT NULL COMMENT 'Total timeline in months e.g. 48',
  `construction_months` int(11) NOT NULL COMMENT 'Construction period in months e.g. 32',
  `post_construction_months` int(11) NOT NULL DEFAULT 6 COMMENT 'Post construction months e.g. 6',
  `project_start_date` date NOT NULL COMMENT 'Project kickoff date',
  `construction_start_date` date NOT NULL COMMENT 'On-site construction start',
  `construction_end_date` date NOT NULL COMMENT 'Construction completion date',
  `expected_handover_date` date NOT NULL COMMENT 'Final handover to client',
  `current_status` enum('Planning','Pre-Construction','Construction','Post-Construction','Handover','Completed') NOT NULL DEFAULT 'Planning',
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pt_project` (`project_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Module 1: Project timeline setup — one row per project';

-- -----------------------------------------------------
-- Table structure for table `initial_estimate_budgets`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `initial_estimate_budgets`;
CREATE TABLE `initial_estimate_budgets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `architecture_id` int(10) unsigned NOT NULL,
  `work_type_id` int(11) DEFAULT NULL,
  `total_area_sqft` decimal(12,2) DEFAULT NULL,
  `material_cost_total` decimal(15,2) DEFAULT 0.00,
  `labor_cost_total` decimal(15,2) DEFAULT 0.00,
  `other_costs` decimal(15,2) DEFAULT 0.00,
  `budget_link` varchar(255) DEFAULT NULL,
  `work_details` text DEFAULT NULL,
  `other_cost_detail` text DEFAULT NULL,
  `total_estimated_cost` decimal(15,2) DEFAULT 0.00,
  `weight_percentage` decimal(5,2) NOT NULL DEFAULT 100.00,
  `status` enum('draft','pending','pm_approved','approved','cancelled') NOT NULL DEFAULT 'draft',
  `approved_by` int(11) DEFAULT NULL,
  `approval_date` timestamp NULL DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `drawing_id` int(11) DEFAULT NULL COMMENT 'Links to project_drawings.id',
  `status_reason` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `initial_estimate_items`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `initial_estimate_items`;
CREATE TABLE `initial_estimate_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `budget_id` int(10) unsigned NOT NULL,
  `design_type_id` int(10) unsigned NOT NULL,
  `material_id` int(10) unsigned NOT NULL,
  `material_unit` varchar(50) NOT NULL,
  `estimated_qty` decimal(12,3) NOT NULL DEFAULT 0.000,
  `unit_cost_approx` decimal(12,2) NOT NULL DEFAULT 0.00,
  `remarks` varchar(255) DEFAULT NULL,
  `work_type_id` bigint(20) unsigned DEFAULT NULL,
  `phase_item_id` int(10) unsigned DEFAULT NULL,
  `sl_no` smallint(6) DEFAULT NULL,
  `location_desc` varchar(500) DEFAULT NULL,
  `nos` decimal(10,3) DEFAULT NULL,
  `length_ft` decimal(10,3) DEFAULT NULL,
  `width_ft` decimal(10,3) DEFAULT NULL,
  `height_ft` decimal(10,3) DEFAULT NULL,
  `volume_qty` decimal(15,3) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_iei_budget` (`budget_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `initial_estimate_labors`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `initial_estimate_labors`;
CREATE TABLE `initial_estimate_labors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `budget_id` int(10) unsigned NOT NULL,
  `design_type_id` int(10) unsigned NOT NULL,
  `labor_type_name` varchar(100) NOT NULL,
  `rate_per_sqft` decimal(10,2) NOT NULL DEFAULT 0.00,
  `area_sqft` decimal(12,2) NOT NULL,
  `description` text DEFAULT NULL,
  `work_type_id` bigint(20) unsigned DEFAULT NULL,
  `phase_item_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_iel_budget` (`budget_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `purchase_requests`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `purchase_requests`;
CREATE TABLE `purchase_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `bom_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `status` enum('Draft','Submitted','Approved','Rejected','PO Issued') NOT NULL DEFAULT 'Draft',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `approved_by_id` bigint(20) unsigned DEFAULT NULL,
  `qc_approved_by_id` int(11) DEFAULT NULL,
  `qc_approved_at` datetime DEFAULT NULL,
  `qc_note` text DEFAULT NULL,
  `management_note` text DEFAULT NULL,
  `management_approved_amount` decimal(15,2) DEFAULT NULL,
  `assigned_executive_id` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pr_project` (`project_id`),
  KEY `idx_pr_created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `purchase_request_items`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `purchase_request_items`;
CREATE TABLE `purchase_request_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `request_id` int(10) unsigned NOT NULL,
  `material_id` int(10) unsigned NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pri_request` (`request_id`),
  KEY `idx_pri_material` (`material_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `quotations`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `quotations`;
CREATE TABLE `quotations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `request_id` int(10) unsigned NOT NULL,
  `vendor_id` int(10) unsigned NOT NULL,
  `validity_date` date DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `attachment` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_q_request` (`request_id`),
  KEY `idx_q_vendor` (`vendor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `quotation_items`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `quotation_items`;
CREATE TABLE `quotation_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `quotation_id` int(10) unsigned NOT NULL,
  `material_id` int(10) unsigned NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `unit_price` decimal(14,4) NOT NULL,
  `total_price` decimal(15,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_qi_quotation` (`quotation_id`),
  KEY `idx_qi_material` (`material_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `quotation_comparisons`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `quotation_comparisons`;
CREATE TABLE `quotation_comparisons` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `request_id` int(10) unsigned NOT NULL,
  `prepared_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_qc_request` (`request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `comparison_items`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `comparison_items`;
CREATE TABLE `comparison_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `comparison_id` int(10) unsigned NOT NULL,
  `material_id` int(10) unsigned NOT NULL,
  `vendor_id` int(10) unsigned NOT NULL,
  `unit_price` decimal(14,4) NOT NULL,
  `is_selected` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ci_comparison` (`comparison_id`),
  KEY `idx_ci_material` (`material_id`),
  KEY `idx_ci_vendor` (`vendor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `purchase_orders`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE `purchase_orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `request_id` int(10) unsigned NOT NULL,
  `created_by` int(11) NOT NULL,
  `status` enum('Draft','Issued','Partially Received','Fully Received','Cancelled','PM Approved','Approved') NOT NULL DEFAULT 'Draft',
  `order_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `approved_by_se` int(11) DEFAULT 0,
  `approved_by_pm` int(11) DEFAULT 0,
  `se_approved_at` datetime DEFAULT NULL,
  `pm_approved_at` datetime DEFAULT NULL,
  `qc_approved_by_id` int(11) DEFAULT NULL,
  `qc_approved_at` datetime DEFAULT NULL,
  `qc_note` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_po_request` (`request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `purchase_order_items`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `purchase_order_items`;
CREATE TABLE `purchase_order_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int(10) unsigned NOT NULL,
  `material_id` int(10) unsigned NOT NULL,
  `selected_vendor_id` int(10) unsigned NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `unit_price` decimal(14,4) NOT NULL,
  `total_price` decimal(16,4) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_poi_po` (`purchase_order_id`),
  KEY `idx_poi_material` (`material_id`),
  KEY `idx_poi_vendor` (`selected_vendor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `boq_master`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `boq_master`;
CREATE TABLE `boq_master` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `boq_items`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `boq_items`;
CREATE TABLE `boq_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `boq_id` int(10) unsigned NOT NULL,
  `material_id` int(10) unsigned NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `unit_cost` decimal(14,4) NOT NULL,
  `total_cost` decimal(16,4) GENERATED ALWAYS AS (`quantity` * `unit_cost`) STORED,
  `required_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_boqi_boq` (`boq_id`),
  KEY `idx_boqi_material` (`material_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `con_materials`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `con_materials`;
CREATE TABLE `con_materials` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `min_stock_level` decimal(12,3) NOT NULL DEFAULT 0.000,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_material_name` (`name`),
  KEY `fk_con_materials_category_id` (`category_id`),
  CONSTRAINT `fk_con_materials_category_id` FOREIGN KEY (`category_id`) REFERENCES `con_material_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `con_warehouses`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `con_warehouses`;
CREATE TABLE `con_warehouses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `con_stocks`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `con_stocks`;
CREATE TABLE `con_stocks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `material_id` int(10) unsigned NOT NULL,
  `warehouse_id` int(10) unsigned NOT NULL,
  `current_quantity` decimal(12,3) NOT NULL DEFAULT 0.000,
  `unit_price` decimal(15,2) DEFAULT 0.00,
  `total_value` decimal(15,2) DEFAULT 0.00,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stock_material_warehouse` (`material_id`,`warehouse_id`),
  KEY `idx_stock_warehouse` (`warehouse_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `con_stock_movements`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `con_stock_movements`;
CREATE TABLE `con_stock_movements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `material_id` int(10) unsigned NOT NULL,
  `warehouse_id` int(10) unsigned NOT NULL,
  `type` enum('IN','OUT') NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `ref_type` varchar(100) DEFAULT NULL,
  `ref_id` int(10) unsigned DEFAULT NULL,
  `moved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sm_material` (`material_id`),
  KEY `idx_sm_warehouse` (`warehouse_id`),
  KEY `idx_sm_ref` (`ref_type`,`ref_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `material_receiving`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `material_receiving`;
CREATE TABLE `material_receiving` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int(10) unsigned NOT NULL,
  `received_by` int(11) NOT NULL,
  `receiving_date` date NOT NULL,
  `received_status` enum('Full','Partial','Rejected') NOT NULL DEFAULT 'Partial',
  `site_eng_note` text DEFAULT NULL,
  `site_eng_photo` varchar(500) DEFAULT NULL,
  `qc_checked_by` int(11) DEFAULT NULL,
  `qc_note` text DEFAULT NULL,
  `qc_photo` varchar(500) DEFAULT NULL,
  `overall_status` enum('Pending QC','Pending PM','Accepted','Partially Accepted','Rejected') NOT NULL DEFAULT 'Pending QC',
  `created_by` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `pm_checked_by` int(11) DEFAULT NULL,
  `pm_note` text DEFAULT NULL,
  `pm_approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mr_po` (`purchase_order_id`),
  KEY `idx_mr_received_by` (`received_by`),
  KEY `idx_mr_qc_checked_by` (`qc_checked_by`),
  KEY `idx_mr_created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `material_receiving_items`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `material_receiving_items`;
CREATE TABLE `material_receiving_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `receiving_id` int(10) unsigned NOT NULL,
  `purchase_order_item_id` int(10) unsigned NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `con_material_issues`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `con_material_issues`;
CREATE TABLE `con_material_issues` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `warehouse_id` int(10) unsigned NOT NULL,
  `material_id` int(10) unsigned NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `issued_to` int(11) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `work_plan_id` int(11) DEFAULT NULL,
  `issued_by` int(11) NOT NULL,
  `issue_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `material_returns`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `material_returns`;
CREATE TABLE `material_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `receiving_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `return_date` date NOT NULL,
  `return_reason` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `status` enum('pending','sent','acknowledged') DEFAULT 'pending',
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `material_return_items`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `material_return_items`;
CREATE TABLE `material_return_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `return_id` bigint(20) unsigned NOT NULL,
  `material_item_id` bigint(20) unsigned NOT NULL,
  `return_qty` decimal(15,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `contractors`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `contractors`;
CREATE TABLE `contractors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `con_work_orders`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `con_work_orders`;
CREATE TABLE `con_work_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `work_type_id` int(11) DEFAULT NULL,
  `design_id` int(11) DEFAULT NULL,
  `boq_id` int(11) DEFAULT NULL,
  `wo_number` varchar(50) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `scope_of_work` text DEFAULT NULL,
  `document_link` varchar(500) DEFAULT NULL COMMENT 'External document/BOQ link',
  `work_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Rate schedule items JSON (ক-১ to ক-১০)' CHECK (json_valid(`work_items`)),
  `notes` text DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `unit_rate` decimal(15,2) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(15,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `advance_percent` decimal(5,2) DEFAULT 0.00,
  `running_percent` decimal(5,2) DEFAULT 70.00,
  `retention_percent` decimal(5,2) DEFAULT 10.00,
  `advance_amount` decimal(15,2) DEFAULT 0.00,
  `retention_amount` decimal(15,2) DEFAULT 0.00,
  `status` enum('draft','approved','active','work_completed','qc_approved','closed','rejected','cancelled') DEFAULT 'draft',
  `rejection_reason` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `qc_approved_by` int(11) DEFAULT NULL,
  `qc_approved_at` datetime DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `target_id` int(11) DEFAULT NULL,
  `work_plan_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wo_number` (`wo_number`),
  KEY `idx_project_design` (`project_id`,`design_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `contractor_bill_requests`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `contractor_bill_requests`;
CREATE TABLE `contractor_bill_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `operator_id` int(10) unsigned NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `bill_type` enum('Advance','Progress','Retention') NOT NULL DEFAULT 'Progress',
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
  `qc_approved_by_id` int(11) DEFAULT NULL,
  `qc_approved_at` datetime DEFAULT NULL,
  `qc_note` text DEFAULT NULL,
  `management_note` text DEFAULT NULL,
  `status` enum('draft','submitted','verified','qc_approved','approved','rejected','paid','partially_paid','fully_paid') DEFAULT 'draft',
  `due_amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `work_order_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cbr_project` (`project_id`),
  KEY `idx_cbr_operator` (`operator_id`),
  KEY `idx_cbr_created_by` (`created_by`),
  KEY `idx_cbr_verified_by` (`verified_by`),
  KEY `idx_cbr_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `vendor_payment_requests`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `vendor_payment_requests`;
CREATE TABLE `vendor_payment_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` int(10) unsigned NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `purchase_order_id` int(10) unsigned NOT NULL,
  `payment_type` enum('Advance','Partial','Final') NOT NULL DEFAULT 'Final',
  `created_by` int(11) NOT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `invoice_amount` decimal(16,4) NOT NULL,
  `delivery_verified` tinyint(1) NOT NULL DEFAULT 0,
  `penalty_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_payable_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `qc_verified` tinyint(1) NOT NULL DEFAULT 0,
  `qc_approved_by_id` int(11) DEFAULT NULL,
  `qc_approved_at` datetime DEFAULT NULL,
  `qc_note` text DEFAULT NULL,
  `management_note` text DEFAULT NULL,
  `status` enum('Draft','Submitted','QC Approved','Approved','Rejected','Paid','Partially_Paid','Fully_Paid') DEFAULT 'Draft',
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `vendor_penalties`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `vendor_penalties`;
CREATE TABLE `vendor_penalties` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` int(10) unsigned NOT NULL,
  `is_deducted_from_bill` tinyint(1) NOT NULL DEFAULT 0,
  `purchase_order_id` int(10) unsigned NOT NULL,
  `receiving_id` int(10) unsigned DEFAULT NULL,
  `payment_request_id` int(10) unsigned DEFAULT NULL,
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

-- -----------------------------------------------------
-- Table structure for table `con_financial_transactions`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `con_financial_transactions`;
CREATE TABLE `con_financial_transactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ref_type` enum('contractor_bill','operational_fund','vendor_payment') NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `ref_id` int(10) unsigned NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `con_approval_logs`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `con_approval_logs`;
CREATE TABLE `con_approval_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ref_table` varchar(100) NOT NULL,
  `ref_id` int(10) unsigned NOT NULL,
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

-- -----------------------------------------------------
-- Table structure for table `phase_monthly_targets`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `phase_monthly_targets`;
CREATE TABLE `phase_monthly_targets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `phase_id` int(10) unsigned NOT NULL,
  `target_month` date NOT NULL COMMENT 'First day of month e.g. 2026-08-01',
  `target_month_number` tinyint(4) NOT NULL COMMENT '1-12',
  `target_year` year(4) NOT NULL,
  `total_target_weight_pct` decimal(5,2) DEFAULT NULL COMMENT '% of total project this month contributes',
  `planning_status` enum('Draft','In Review','Approved') NOT NULL DEFAULT 'Draft',
  `responsible_team` varchar(150) DEFAULT NULL,
  `contractor_id` int(10) unsigned DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pmt_month` (`project_id`,`phase_id`,`target_month`),
  KEY `idx_pmt_phase` (`phase_id`),
  KEY `fk_pmt_contractor` (`contractor_id`),
  CONSTRAINT `fk_pmt_contractor` FOREIGN KEY (`contractor_id`) REFERENCES `contractors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pmt_phase` FOREIGN KEY (`phase_id`) REFERENCES `project_phases` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Module 3: Monthly target plan header — one per project+phase+month';

-- -----------------------------------------------------
-- Table structure for table `phase_monthly_target_items`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `phase_monthly_target_items`;
CREATE TABLE `phase_monthly_target_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `target_id` int(10) unsigned NOT NULL,
  `work_type_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Optional ref to work_types for categorization',
  `work_item_name` varchar(200) NOT NULL,
  `phase_item_id` int(11) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL COMMENT 'CFT / MT / SFT / Bags / Nos / Days',
  `monthly_target_qty` decimal(12,2) DEFAULT NULL,
  `actual_qty` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Actual quantity achieved',
  `planned_progress_pct` decimal(5,2) NOT NULL DEFAULT 0.00,
  `actual_progress_pct` decimal(5,2) NOT NULL DEFAULT 0.00,
  `recovery_notes` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `responsible_team` varchar(150) DEFAULT NULL,
  `contractor_id` int(10) unsigned DEFAULT NULL,
  `priority` enum('High','Medium','Low') NOT NULL DEFAULT 'Medium',
  `status` enum('Draft','Ready','Approved','At Risk','Critical') NOT NULL DEFAULT 'Draft',
  `week_1_qty` decimal(12,2) NOT NULL DEFAULT 0.00,
  `week_2_qty` decimal(12,2) NOT NULL DEFAULT 0.00,
  `week_3_qty` decimal(12,2) NOT NULL DEFAULT 0.00,
  `week_4_qty` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pmti_target` (`target_id`),
  KEY `fk_pmti_contractor` (`contractor_id`),
  CONSTRAINT `fk_pmti_contractor` FOREIGN KEY (`contractor_id`) REFERENCES `contractors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pmti_target` FOREIGN KEY (`target_id`) REFERENCES `phase_monthly_targets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Module 3: Work items under each monthly target plan';

-- -----------------------------------------------------
-- Table structure for table `phase_weekly_progress`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `phase_weekly_progress`;
CREATE TABLE `phase_weekly_progress` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `phase_id` int(10) unsigned NOT NULL,
  `target_id` int(10) unsigned NOT NULL COMMENT 'FK phase_monthly_targets',
  `week_number` tinyint(4) NOT NULL COMMENT '1, 2, 3, or 4 within the month',
  `week_start_date` date NOT NULL,
  `week_end_date` date NOT NULL,
  `overall_progress_pct` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Overall % for this week',
  `total_planned_qty` decimal(12,2) DEFAULT NULL,
  `total_achieved_qty` decimal(12,2) DEFAULT NULL,
  `labour_used` int(11) NOT NULL DEFAULT 0,
  `cement_used_bags` int(11) NOT NULL DEFAULT 0,
  `concrete_poured_mt` decimal(8,2) NOT NULL DEFAULT 0.00,
  `concrete_poured_cft` decimal(8,2) NOT NULL DEFAULT 0.00,
  `materials_used` int(11) NOT NULL DEFAULT 0,
  `equipment_active` int(11) NOT NULL DEFAULT 0,
  `weather_condition` varchar(100) DEFAULT NULL,
  `temperature_celsius` decimal(4,1) DEFAULT NULL,
  `site_engineer_remarks` text DEFAULT NULL,
  `safety_ok` tinyint(1) NOT NULL DEFAULT 0,
  `qc_checked` tinyint(1) NOT NULL DEFAULT 0,
  `material_received` tinyint(1) NOT NULL DEFAULT 0,
  `qc_approved_id` int(10) unsigned DEFAULT NULL,
  `qc_status` varchar(50) NOT NULL DEFAULT 'Pending',
  `qc_image` varchar(255) DEFAULT NULL,
  `qc_comment` text DEFAULT NULL,
  `pm_approved_id` int(10) unsigned DEFAULT NULL,
  `pm_status` varchar(50) NOT NULL DEFAULT 'Pending',
  `pm_comment` text DEFAULT NULL,
  `entry_status` enum('Draft','Submitted','Approved') NOT NULL DEFAULT 'Draft',
  `submitted_by` int(11) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pwp_week` (`project_id`,`phase_id`,`target_id`,`week_number`),
  KEY `idx_pwp_target` (`target_id`),
  KEY `fk_pwp_phase` (`phase_id`),
  CONSTRAINT `fk_pwp_phase` FOREIGN KEY (`phase_id`) REFERENCES `project_phases` (`id`),
  CONSTRAINT `fk_pwp_target` FOREIGN KEY (`target_id`) REFERENCES `phase_monthly_targets` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Module 4: Weekly progress entry header — one per week per monthly target';

-- -----------------------------------------------------
-- Table structure for table `phase_weekly_progress_items`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `phase_weekly_progress_items`;
CREATE TABLE `phase_weekly_progress_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `weekly_progress_id` int(10) unsigned NOT NULL,
  `target_item_id` int(10) unsigned NOT NULL,
  `planned_qty` decimal(12,2) DEFAULT NULL,
  `achieved_qty` decimal(12,2) NOT NULL DEFAULT 0.00,
  `1st_day_qty` decimal(12,2) NOT NULL DEFAULT 0.00,
  `2nd_day_qty` decimal(12,2) NOT NULL DEFAULT 0.00,
  `3rd_day_qty` decimal(12,2) NOT NULL DEFAULT 0.00,
  `4th_day_qty` decimal(12,2) NOT NULL DEFAULT 0.00,
  `5th_day_qty` decimal(12,2) NOT NULL DEFAULT 0.00,
  `6_th_day_qty` decimal(12,2) NOT NULL DEFAULT 0.00,
  `7th_day_qty` decimal(12,2) NOT NULL DEFAULT 0.00,
  `today_progress_qty` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Qty done on latest entry day',
  `percent_complete` decimal(5,2) NOT NULL DEFAULT 0.00,
  `responsible_team` varchar(150) DEFAULT NULL,
  `contractor_id` int(10) unsigned DEFAULT NULL,
  `status` enum('Not Started','In Progress','Completed','Delayed','Pending QA') NOT NULL DEFAULT 'Not Started',
  `has_issue_flag` tinyint(1) NOT NULL DEFAULT 0,
  `photo_path` varchar(500) DEFAULT NULL,
  `remarks_action` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pwpi_weekly` (`weekly_progress_id`),
  KEY `idx_pwpi_item` (`target_item_id`),
  KEY `fk_pwpi_contractor` (`contractor_id`),
  CONSTRAINT `fk_pwpi_contractor` FOREIGN KEY (`contractor_id`) REFERENCES `contractors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pwpi_item` FOREIGN KEY (`target_item_id`) REFERENCES `phase_monthly_target_items` (`id`),
  CONSTRAINT `fk_pwpi_progress` FOREIGN KEY (`weekly_progress_id`) REFERENCES `phase_weekly_progress` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Module 4: Item-level weekly progress update';

-- -----------------------------------------------------
-- Table structure for table `daily_progress_logs`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `daily_progress_logs`;
CREATE TABLE `daily_progress_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `weekly_progress_id` int(10) unsigned NOT NULL,
  `project_id` int(11) NOT NULL,
  `log_date` date NOT NULL COMMENT 'Specific site day e.g. 2026-08-16',
  `labour_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Workers on site',
  `cement_bags` int(11) NOT NULL DEFAULT 0,
  `concrete_mt` decimal(8,2) NOT NULL DEFAULT 0.00,
  `concrete_cft` decimal(8,2) NOT NULL DEFAULT 0.00,
  `materials_used` int(11) NOT NULL DEFAULT 0,
  `equipment_active` int(11) NOT NULL DEFAULT 0,
  `weather` varchar(100) DEFAULT NULL,
  `temperature_celsius` decimal(4,1) DEFAULT NULL,
  `safety_ok` tinyint(1) NOT NULL DEFAULT 0,
  `qc_checked` tinyint(1) NOT NULL DEFAULT 0,
  `site_remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `qc_approved_id` int(11) DEFAULT NULL,
  `qc_status` varchar(30) DEFAULT 'Pending',
  `qc_image` varchar(255) DEFAULT NULL,
  `qc_comment` text DEFAULT NULL,
  `pm_approved_id` int(11) DEFAULT NULL,
  `pm_status` varchar(30) DEFAULT 'Pending',
  `pm_comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_dpl_day` (`weekly_progress_id`,`log_date`),
  CONSTRAINT `fk_dpl_week` FOREIGN KEY (`weekly_progress_id`) REFERENCES `phase_weekly_progress` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Module 4: Daily work report — one row per calendar day within a week';

-- -----------------------------------------------------
-- Table structure for table `daily_work_item_logs`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `daily_work_item_logs`;
CREATE TABLE `daily_work_item_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `daily_log_id` int(10) unsigned NOT NULL,
  `target_item_id` int(10) unsigned NOT NULL,
  `phase_item_detail_id` bigint(20) unsigned DEFAULT NULL,
  `work_type_id` bigint(20) unsigned DEFAULT NULL,
  `qty_done_today` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Qty done in item unit (CFT / MT / SFT...)',
  `unit` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `item_status` varchar(30) NOT NULL DEFAULT 'In Progress',
  `responsible_team` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dwil_log` (`daily_log_id`),
  KEY `idx_dwil_item` (`target_item_id`),
  KEY `fk_daily_work_item_logs_detail` (`phase_item_detail_id`),
  CONSTRAINT `fk_daily_work_item_logs_detail` FOREIGN KEY (`phase_item_detail_id`) REFERENCES `project_phase_item_details` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_dwil_item` FOREIGN KEY (`target_item_id`) REFERENCES `phase_monthly_target_items` (`id`),
  CONSTRAINT `fk_dwil_log` FOREIGN KEY (`daily_log_id`) REFERENCES `daily_progress_logs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Module 4: Daily qty log per work item';

-- -----------------------------------------------------
-- Table structure for table `qc_checklists`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `qc_checklists`;
CREATE TABLE `qc_checklists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `work_type_id` bigint(20) unsigned NOT NULL,
  `material_item_id` bigint(20) unsigned DEFAULT NULL,
  `checklist_item_name` varchar(255) NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_qc_work_type_id` (`work_type_id`),
  CONSTRAINT `fk_qc_work_type_id` FOREIGN KEY (`work_type_id`) REFERENCES `work_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `qc_inspections`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `qc_inspections`;
CREATE TABLE `qc_inspections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `work_type_id` bigint(20) unsigned NOT NULL,
  `inspector_id` bigint(20) unsigned NOT NULL,
  `inspection_date` date NOT NULL,
  `overall_status` enum('passed','failed','rework_needed') NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_qc_project` (`project_id`),
  KEY `fk_qc_work_type` (`work_type_id`),
  CONSTRAINT `fk_qc_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `fk_qc_work_type` FOREIGN KEY (`work_type_id`) REFERENCES `work_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------
-- Table structure for table `qc_inspection_items`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `qc_inspection_items`;
CREATE TABLE `qc_inspection_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `qc_inspection_id` bigint(20) unsigned NOT NULL,
  `qc_checklist_id` bigint(20) unsigned NOT NULL,
  `is_passed` tinyint(1) DEFAULT 1,
  `observation_note` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_ins_master` (`qc_inspection_id`),
  KEY `fk_ins_checklist` (`qc_checklist_id`),
  CONSTRAINT `fk_ins_checklist` FOREIGN KEY (`qc_checklist_id`) REFERENCES `qc_checklists` (`id`),
  CONSTRAINT `fk_ins_master` FOREIGN KEY (`qc_inspection_id`) REFERENCES `qc_inspections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
