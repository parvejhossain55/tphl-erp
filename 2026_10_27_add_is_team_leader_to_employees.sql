-- Migration: Add is_team_leader to employees table
ALTER TABLE `employees` ADD COLUMN `is_team_leader` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_sales_co_leader`;
