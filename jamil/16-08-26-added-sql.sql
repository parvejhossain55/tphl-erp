-- =====================================================================
-- Advanced Salary Implementation SQL Additions & Migrations
-- Generated automatically based on the models update
-- =====================================================================

-- 1. Create the base table for tracking advance salaries
CREATE TABLE IF NOT EXISTS `advance_salaries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT NOT NULL,
    `request_date` DATE NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `reason` TEXT NULL,
    `status` ENUM('pending', 'pending_hr', 'pending_accounts', 'paid', 'deducted', 'rejected') DEFAULT 'pending_hr',
    `approved_date` DATETIME NULL,
    `approval_request_id` INT NULL,
    `deducted_payslip_id` INT NULL,
    `created_by` INT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);



-- Add per_meal_amount to employees table
ALTER TABLE employees ADD COLUMN per_meal_amount DECIMAL(10,2) DEFAULT 0.00 NULL;
