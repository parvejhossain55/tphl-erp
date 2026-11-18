-- ATS Job Postings & Website Career Page Integration
-- Date: 2026-11-17

CREATE TABLE IF NOT EXISTS `ats_job_postings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) DEFAULT NULL,
    `department_id` INT DEFAULT NULL,
    `department_name` VARCHAR(150) DEFAULT NULL,
    `employment_type` VARCHAR(50) NOT NULL DEFAULT 'Full Time' COMMENT 'Full Time, Part Time, Contract, Internship',
    `location` VARCHAR(150) NOT NULL DEFAULT 'Gulshan, Dhaka',
    `salary_range` VARCHAR(100) DEFAULT NULL COMMENT 'e.g. 100k - 150k or Negotiable',
    `deadline` DATE DEFAULT NULL,
    `vacancies` INT NOT NULL DEFAULT 1,
    `description` TEXT DEFAULT NULL,
    `requirements` TEXT DEFAULT NULL,
    `status` ENUM('Draft', 'Published', 'Closed') NOT NULL DEFAULT 'Draft',
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status_deadline` (`status`, `deadline`),
    INDEX `idx_department` (`department_id`),
    INDEX `idx_employment_type` (`employment_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add job_id column to ats_candidates if not exists
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'ats_candidates'
      AND COLUMN_NAME = 'job_id'
);

SET @alter_sql = IF(
    @col_exists = 0,
    'ALTER TABLE `ats_candidates` ADD COLUMN `job_id` INT DEFAULT NULL AFTER `id`, ADD INDEX `idx_ats_job_id` (`job_id`)',
    'SELECT "job_id column already exists" AS info'
);

PREPARE stmt FROM @alter_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
