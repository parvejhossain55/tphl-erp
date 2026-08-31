-- ============================================================
-- Date       : 2026-08-31
-- Author     : Jamil
-- Description: Payroll Workplace & Payslip List fixes
-- ============================================================

-- NO NEW SCHEMA CHANGES REQUIRED FOR TODAY'S FIXES.
-- Both required columns already exist from previous migrations:
--   - `payslips`.`is_excluded`         => added in 25-08-26-add-payslip-disbursement-columns.sql
--   - `payslips`.`payroll_project_id`  => added in 22-08-26-added-sql.sql

-- ---------------------------------------------------------------
-- FIX 1: Payroll Workplace - checkbox deselect not persisting
-- ---------------------------------------------------------------
-- Root cause: $isExcluded variable was never assigned from the
--   incoming request payload in saveWorkspacePayslips().
-- Fix applied in: app/controllers/PayrollController.php (~line 1702)
--   Added: $isExcluded = isset($row['is_excluded']) ? (int) $row['is_excluded'] : 0;

-- ---------------------------------------------------------------
-- FIX 2: Payslip List - Payroll Project filter added
-- ---------------------------------------------------------------
-- Added a "Payroll Project" dropdown to the payslip list filter.
-- Filtering by project works via p.payroll_project_id.
-- Files changed:
--   app/controllers/PayrollController.php  (index method)
--   app/models/Payslip.php                 (getAllWithEmployeeFiltered)
--   app/views/payrolls/payslips/payslip-lists.php

-- ---------------------------------------------------------------
-- OPTIONAL: Performance index (run if payslips table has many rows)
-- ---------------------------------------------------------------
-- ALTER TABLE `payslips`
--     ADD INDEX `idx_payslips_project_month` (`payroll_project_id`, `payslip_month`);
