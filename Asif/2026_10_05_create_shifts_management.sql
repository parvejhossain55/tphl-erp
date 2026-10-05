-- ============================================================================
-- Migration: Create Shifts Table and Add shift_id to Employees
-- Date: 2026-10-05
-- Purpose: Shift Management for Attendance and Roster Engine
-- ============================================================================

-- 1. Create shifts table
CREATE TABLE IF NOT EXISTS `shifts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT 'e.g. IT Team Regular, Site Engineer, Morning Shift',
    `start_time` TIME NOT NULL COMMENT 'e.g. 09:30:00, 08:30:00',
    `end_time` TIME NOT NULL COMMENT 'e.g. 18:30:00, 17:30:00',
    `grace_minutes` INT NOT NULL DEFAULT 10 COMMENT 'Late threshold in minutes',
    `half_day_threshold_time` TIME NULL DEFAULT '13:00:00' COMMENT 'After this time marked as Half-Day',
    `min_working_hours` DECIMAL(4,2) DEFAULT 8.00,
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Insert initial standard shifts
INSERT INTO `shifts` (`id`, `name`, `start_time`, `end_time`, `grace_minutes`, `half_day_threshold_time`) 
VALUES 
(1, 'IT / Head Office Regular', '09:30:00', '18:30:00', 10, '13:00:00'),
(2, 'Site Engineer Shift', '08:30:00', '17:30:00', 10, '12:30:00'),
(3, 'General Shift', '09:00:00', '18:00:00', 10, '13:00:00')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 3. Add shift_id to employees table
ALTER TABLE `employees` 
ADD COLUMN IF NOT EXISTS `shift_id` INT NULL DEFAULT 1 AFTER `office_branch_id`;
