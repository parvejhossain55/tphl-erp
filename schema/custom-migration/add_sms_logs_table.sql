CREATE TABLE IF NOT EXISTS `sms_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT NULL,
  `booking_id` INT NULL,
  `payment_id` INT NULL,
  `mobile_no` VARCHAR(25) NOT NULL,
  `sms_type` VARCHAR(50) NOT NULL,
  `message` TEXT NOT NULL,
  `is_testing` TINYINT(1) DEFAULT 0,
  `gateway_response` TEXT NULL,
  `status` VARCHAR(30) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (`payment_id`),
  INDEX (`booking_id`),
  INDEX (`mobile_no`),
  INDEX (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
