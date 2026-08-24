-- Payroll Projects Table
CREATE TABLE IF NOT EXISTS `payroll_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Month/Year formatting like YYYY-MM',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attendance Monthly Update Table (for specific manual adjustments)
CREATE TABLE IF NOT EXISTS `attendance_monthly_update` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `payroll_project_id` int(11) NOT NULL,
  `working_days` int(11) DEFAULT 0,
  `present` int(11) DEFAULT 0,
  `absent` int(11) DEFAULT 0,
  `leave_days` int(11) DEFAULT 0,
  `dayoff` int(11) DEFAULT 0,
  `late` int(11) DEFAULT 0,
  `approved_lates` int(11) DEFAULT 0,
  `final_late` int(11) DEFAULT 0,
  `deduction` decimal(10,2) DEFAULT '0.00',
  `late_unpaid_absent_days` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `update_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_employee_project` (`employee_id`,`payroll_project_id`),
  CONSTRAINT `fk_att_monthly_proj` FOREIGN KEY (`payroll_project_id`) REFERENCES `payroll_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alter Payslips Table
ALTER TABLE `payslips` ADD COLUMN `payroll_project_id` INT(11) DEFAULT NULL AFTER `employee_id`;
ALTER TABLE `payslips` ADD COLUMN `salary_hold` VARCHAR(50) DEFAULT NULL AFTER `payment_method`;
