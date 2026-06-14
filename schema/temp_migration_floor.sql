SET FOREIGN_KEY_CHECKS = 0;

-- A. Floor Configuration Table (New)
CREATE TABLE IF NOT EXISTS `project_floors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT NOT NULL,
  `floor_no` INT NOT NULL COMMENT 'e.g., -1 = Basement, 0 = Ground Floor, 1 = 1st Floor',
  `floor_type` ENUM('Basement', 'Ground_Floor', 'Typical_Floor', 'Roof') NOT NULL DEFAULT 'Typical_Floor',
  `slab_area_sqft` DECIMAL(10,2) NOT NULL COMMENT 'Actual construction area for this floor',
  `height_ft` DECIMAL(5,2) DEFAULT 10.00 COMMENT 'Height of this floor in feet',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_project_floor` (`project_id`, `floor_no`),
  CONSTRAINT `fk_pf_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- B. Adding Columns to Existing Tables (5 Tables Modified)
-- 1. Initial Estimate Items (BOM budget)
ALTER TABLE `initial_estimate_items` 
  ADD COLUMN `floor_type` ENUM('Basement', 'Ground_Floor', 'Typical_Floor', 'Roof') NOT NULL DEFAULT 'Typical_Floor' AFTER `phase_item_id`,
  ADD COLUMN `floor_no` INT(11) DEFAULT NULL AFTER `floor_type`,
  ADD INDEX `idx_iei_floor` (`floor_no`);

-- 2. Initial Estimate Labors (Labor budget)
ALTER TABLE `initial_estimate_labors` 
  ADD COLUMN `floor_type` ENUM('Basement', 'Ground_Floor', 'Typical_Floor', 'Roof') NOT NULL DEFAULT 'Typical_Floor' AFTER `phase_item_id`,
  ADD COLUMN `floor_no` INT(11) DEFAULT NULL AFTER `floor_type`,
  ADD INDEX `idx_iel_floor` (`floor_no`);

-- 3. Monthly Target Work Items
ALTER TABLE `phase_monthly_target_items`
  ADD COLUMN `floor_type` ENUM('Basement', 'Ground_Floor', 'Typical_Floor', 'Roof') NOT NULL DEFAULT 'Typical_Floor' AFTER `phase_item_id`,
  ADD COLUMN `floor_no` INT(11) DEFAULT NULL AFTER `floor_type`,
  ADD INDEX `idx_pmti_floor` (`floor_no`);

-- 4. BOQ Requisition Items (Site requests)
ALTER TABLE `boq_items` 
  ADD COLUMN `floor_type` ENUM('Basement', 'Ground_Floor', 'Typical_Floor', 'Roof') NOT NULL DEFAULT 'Typical_Floor' AFTER `material_id`,
  ADD COLUMN `floor_no` INT(11) DEFAULT NULL AFTER `floor_type`,
  ADD INDEX `idx_boqi_floor` (`floor_no`);

-- 5. Material Issues (Store dispatch / stock out)
ALTER TABLE `con_material_issues`
  ADD COLUMN `floor_type` ENUM('Basement', 'Ground_Floor', 'Typical_Floor', 'Roof') NOT NULL DEFAULT 'Typical_Floor' AFTER `target_id`,
  ADD COLUMN `floor_no` INT(11) DEFAULT NULL AFTER `floor_type`,
  ADD INDEX `idx_cmi_floor` (`floor_no`);

SET FOREIGN_KEY_CHECKS = 1;
