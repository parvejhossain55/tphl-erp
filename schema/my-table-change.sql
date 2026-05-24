-- 1. QC Master Checklist (Template)
CREATE TABLE `qc_checklists` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `work_type_id` BIGINT UNSIGNED NOT NULL,
    `material_item_id` BIGINT UNSIGNED NULL, -- Jodi material specific hoy
    `checklist_item_name` VARCHAR(255) NOT NULL, 
    `created_by` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `fk_qc_work_type_id` FOREIGN KEY (`work_type_id`) REFERENCES `work_types`(`id`) ON DELETE CASCADE
);

-- 2. Actual Inspection Entry (Header)
-- Ekhane overall project ba work site-er attachment (e.g. Master Approval Copy)
CREATE TABLE `qc_inspections` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `work_type_id` BIGINT UNSIGNED NOT NULL,
    `inspector_id` BIGINT UNSIGNED NOT NULL,
    `inspection_date` DATE NOT NULL,
    `overall_status` ENUM('passed', 'failed', 'rework_needed') NOT NULL,
    `attachment` VARCHAR(255) NULL, -- Overall site photo ba report attachment
    `comments` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT `fk_qc_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`),
    CONSTRAINT `fk_qc_work_type` FOREIGN KEY (`work_type_id`) REFERENCES `work_types`(`id`)
);

-- 3. Actual Inspection Items (Details)
-- Ekhane protiti checklist item-er specific chobi thakbe (e.g. Rod spacing photo)
CREATE TABLE `qc_inspection_items` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `qc_inspection_id` BIGINT UNSIGNED NOT NULL,
    `qc_checklist_id` BIGINT UNSIGNED NOT NULL,
    `is_passed` BOOLEAN DEFAULT TRUE,
    `observation_note` TEXT NULL,
    `attachment` VARCHAR(255) NULL, -- Protiti individual check-er attachment
    
    CONSTRAINT `fk_ins_master` FOREIGN KEY (`qc_inspection_id`) REFERENCES `qc_inspections`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ins_checklist` FOREIGN KEY (`qc_checklist_id`) REFERENCES `qc_checklists`(`id`)
);

-- 4. Material Lab Tests
CREATE TABLE `qc_material_tests` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `material_receiving_id`  UNSIGNED NOT NULL,
    `test_name` VARCHAR(255),
    `result_value` VARCHAR(100),
     `attachment` VARCHAR(255) NULL, -- Overall site photo ba report attachment
    `test_status` ENUM('pass','fail','partially_passed') NOT NULL,
    `lab_report_link` TEXT NULL, -- Ekhane lab report-er PDF/Image link thakbe
    `created_by` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);



CREATE TABLE `material_returns` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `receiving_id` INT NOT NULL, -- কোন চালানের মাল রিটার্ন যাচ্ছে
    `vendor_id` INT NOT NULL,
    `project_id` INT NOT NULL,
    `return_date` DATE NOT NULL,
    `return_reason` TEXT NULL,
    `attachment` VARCHAR(255) NULL, -- রিটার্ন চালানের ছবি
    `status` ENUM('pending', 'sent', 'acknowledged') DEFAULT 'pending',
    `created_by` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);
CREATE TABLE `material_return_items` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `return_id` BIGINT UNSIGNED NOT NULL,
    `material_item_id` BIGINT UNSIGNED NOT NULL,
    `return_qty` DECIMAL(15,2) NOT NULL,
    `unit_price` DECIMAL(15,2) NOT NULL-- রিটার্ন ভ্যালু ট্র্যাক করার জন্য
  
);

-- 15/may/2026 run in terminal live
ALTER TABLE con_work_orders 
  ADD COLUMN description TEXT NULL AFTER title,
  ADD COLUMN notes TEXT NULL AFTER scope_of_work,
  ADD COLUMN rejection_reason TEXT NULL AFTER status,
  ADD COLUMN qc_approved_by INT NULL AFTER approved_at,
  ADD COLUMN qc_approved_at DATETIME NULL AFTER qc_approved_by,
  ADD COLUMN completed_by INT NULL AFTER qc_approved_at,
  ADD COLUMN completed_at DATETIME NULL AFTER completed_by;



===21-05
ALTER TABLE initial_estimate_budgets 
  MODIFY COLUMN status ENUM('draft','pending','pm_approved','approved','cancelled') 
  NOT NULL DEFAULT 'draft';
-- ② con_work_orders: document link + rate schedule JSON যোগ
ALTER TABLE con_work_orders 
  ADD COLUMN document_link VARCHAR(500) NULL DEFAULT NULL 
    COMMENT 'External document/BOQ link' 
    AFTER scope_of_work,
  ADD COLUMN work_items JSON NULL DEFAULT NULL 
    COMMENT 'Rate schedule items JSON (ক-১ to ক-১০)' 
    AFTER document_link;