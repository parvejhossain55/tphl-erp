-- ============================================================================
-- Migration: HR ATS Tables (Master Dropdowns & Candidate Tracker)
-- Optimized for MySQL 8.0+ (InnoDB, UTF8MB4, Case-sensitive safe)
-- Date: 2026-10-08
-- ============================================================================

-- 1. Master Dropdown Options Table (Self-service for HR)
CREATE TABLE IF NOT EXISTS `ats_dropdown_options` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category` VARCHAR(50) NOT NULL COMMENT 'department, position, source, invitation_status, interview_location',
    `option_value` VARCHAR(150) NOT NULL,
    `display_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1 COMMENT '1=Active, 0=Inactive/Retired',
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_category_active` (`category`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Candidates Tracker Table
CREATE TABLE IF NOT EXISTS `ats_candidates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `position` VARCHAR(100) NOT NULL,
    `department` VARCHAR(100) NOT NULL,
    `source` VARCHAR(100) DEFAULT NULL,
    `invitation_status` VARCHAR(50) DEFAULT 'Pending',
    `referred_by` VARCHAR(150) DEFAULT NULL,
    `remarks` TEXT DEFAULT NULL,
    `interview_call_date` DATE DEFAULT NULL,
    `interview_date` DATE DEFAULT NULL,
    `interview_time` TIME DEFAULT NULL,
    `interview_location` VARCHAR(150) DEFAULT NULL,
    `email_sent` TINYINT(1) DEFAULT 0,
    `sms_sent` TINYINT(1) DEFAULT 0,
    `interview_attendance` ENUM('pending','present','absent') DEFAULT 'pending',
    `cv_source` ENUM('file','drive_link') DEFAULT 'file',
    `cv_file_path` VARCHAR(255) DEFAULT NULL,
    `cv_drive_link` TEXT DEFAULT NULL,
    `hired` ENUM('pending','yes','no') DEFAULT 'pending',
    `joining_date` DATE DEFAULT NULL,
    `onboarding_salary` DECIMAL(12,2) DEFAULT NULL,
    `entry_mode` ENUM('single','csv','ai') DEFAULT 'single',
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_phone` (`phone`),
    INDEX `idx_email` (`email`),
    INDEX `idx_dept_pos` (`department`, `position`),
    INDEX `idx_interview_date` (`interview_date`),
    INDEX `idx_invitation_status` (`invitation_status`),
    INDEX `idx_attendance` (`interview_attendance`),
    INDEX `idx_hired` (`hired`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Initial Seed Options for Dropdowns
INSERT INTO `ats_dropdown_options` (`category`, `option_value`, `display_order`) VALUES
-- Departments
('department', 'Finance & Accounts', 1),
('department', 'Engineering, Construction & Design', 2),
('department', 'Sales & Marketing', 3),
('department', 'Human Resources', 4),
('department', 'Supply Chain & Procurement', 5),
('department', 'IT & Systems', 6),

-- Positions
('position', 'Site Engineer', 1),
('position', 'Project Engineer', 2),
('position', 'Accountant', 3),
('position', 'Sales Executive', 4),
('position', 'HR Executive', 5),
('position', 'Intern', 6),

-- Sources
('source', 'Company Career Page', 1),
('source', 'Referral', 2),
('source', 'Walk-in', 3),
('source', 'LinkedIn', 4),
('source', 'Bdjobs', 5),
('source', 'Agency', 6),

-- Invitation Statuses
('invitation_status', 'Yes', 1),
('invitation_status', 'No', 2),
('invitation_status', 'Not Sure', 3),
('invitation_status', 'Didn\'t Pick', 4),
('invitation_status', 'Busy', 5),
('invitation_status', 'Number Off', 6),
('invitation_status', 'Will Inform Later', 7),

-- Locations
('interview_location', 'Head Office', 1),
('interview_location', 'Gulshan Office', 2),
('interview_location', 'Ashulia Site', 3),
('interview_location', 'Ati Site', 4),
('interview_location', 'Online', 5);
