-- Migration: Create client_emi_fixed_dates table for CED EMI Due Date Scheduling
-- Allows Client Experience (CED) team to set and track custom due dates for EMIs
-- Includes audit tracking (fixed_by, fixed_at) and supports lock validation

CREATE TABLE IF NOT EXISTS `client_emi_fixed_dates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT UNSIGNED NOT NULL,
    `emi_no` INT NOT NULL,
    `due_date` DATE NOT NULL,
    `notes` VARCHAR(255) NULL,
    `fixed_by` INT UNSIGNED NOT NULL,
    `fixed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_booking_emi` (`booking_id`, `emi_no`),
    KEY `idx_booking` (`booking_id`),
    KEY `idx_fixed_by` (`fixed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

