-- Table: employee_meal_opt_outs
-- Tracks daily employee opt-outs and opt-ins for subsidized lunch before 12:00 PM cutoff

CREATE TABLE IF NOT EXISTS `employee_meal_opt_outs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT UNSIGNED NOT NULL,
  `opt_out_date` DATE NOT NULL,
  `status` ENUM('opt_out', 'opt_in') NOT NULL DEFAULT 'opt_out',
  `opt_out_time` DATETIME NOT NULL,
  `reason` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_emp_opt_date` (`employee_id`, `opt_out_date`),
  KEY `idx_opt_date` (`opt_out_date`),
  KEY `idx_emp_status` (`employee_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
