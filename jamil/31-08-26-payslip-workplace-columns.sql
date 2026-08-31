-- ============================================================
-- Date       : 2026-08-31
-- Author     : Jamil
-- Description: Direct columns in `payslips` table for payroll workplace
-- ============================================================

-- 1. Add dedicated columns directly to `payslips` table (if not already added)
ALTER TABLE `payslips`
    ADD COLUMN IF NOT EXISTS `recreation_allowance` DECIMAL(10,2) DEFAULT 0.00 AFTER `is_excluded`,
    ADD COLUMN IF NOT EXISTS `conveyance_allowance` DECIMAL(10,2) DEFAULT 0.00 AFTER `recreation_allowance`,
    ADD COLUMN IF NOT EXISTS `medical_allowance` DECIMAL(10,2) DEFAULT 0.00 AFTER `conveyance_allowance`,
    ADD COLUMN IF NOT EXISTS `house_rent_allowance` DECIMAL(10,2) DEFAULT 0.00 AFTER `medical_allowance`,
    ADD COLUMN IF NOT EXISTS `advance_salary` DECIMAL(10,2) DEFAULT 0.00 AFTER `house_rent_allowance`,
    ADD COLUMN IF NOT EXISTS `meal_count` DECIMAL(10,2) DEFAULT 0.00 AFTER `advance_salary`,
    ADD COLUMN IF NOT EXISTS `subsidized_lunch` DECIMAL(10,2) DEFAULT 0.00 AFTER `meal_count`,
    ADD COLUMN IF NOT EXISTS `unpaid_absent_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `subsidized_lunch`,
    ADD COLUMN IF NOT EXISTS `arrears_deduction` DECIMAL(10,2) DEFAULT 0.00 AFTER `unpaid_absent_amount`,
    ADD COLUMN IF NOT EXISTS `others_deduction` DECIMAL(10,2) DEFAULT 0.00 AFTER `arrears_deduction`,
    ADD COLUMN IF NOT EXISTS `punctuality_bonus` DECIMAL(10,2) DEFAULT 0.00 AFTER `others_deduction`,
    ADD COLUMN IF NOT EXISTS `overtime` DECIMAL(10,2) DEFAULT 0.00 AFTER `punctuality_bonus`,
    ADD COLUMN IF NOT EXISTS `arrears_earning` DECIMAL(10,2) DEFAULT 0.00 AFTER `overtime`,
    ADD COLUMN IF NOT EXISTS `duty_allowance` DECIMAL(10,2) DEFAULT 0.00 AFTER `arrears_earning`,
    ADD COLUMN IF NOT EXISTS `tax` DECIMAL(10,2) DEFAULT 0.00 AFTER `duty_allowance`;

-- 2. Clean up any duplicate payslips (keeps only the latest record per employee per project/month)
DELETE p1 FROM `payslips` p1
INNER JOIN `payslips` p2 
WHERE p1.employee_id = p2.employee_id 
  AND p1.payslip_month = p2.payslip_month 
  AND p1.payroll_project_id = p2.payroll_project_id 
  AND p1.id < p2.id;

