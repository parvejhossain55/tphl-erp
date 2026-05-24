-- =================================================================================
-- TPHL-ERP: Expense Adjustments Module (Return & Extra Requisitions)
-- =================================================================================

CREATE TABLE IF NOT EXISTS `expense_adjustments` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'User who is making the adjustment request',
  `cashout_request_id` int(11) NOT NULL COMMENT 'FK to cashout_requests.id',
  `adjustment_type` enum('return', 'extra') NOT NULL COMMENT 'Return unused money or request extra money',
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `approved_amount` decimal(15,2) DEFAULT NULL,
  `due_amount` decimal(15,2) DEFAULT NULL COMMENT 'Amount remaining to be paid/received',
  `document` text DEFAULT NULL COMMENT 'JSON array of paths',
  `note` text DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `office` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','partially_paid','fully_paid') NOT NULL DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_cashout` (`cashout_request_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `adjustment_transactions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `adjustment_id` int(11) unsigned NOT NULL COMMENT 'FK to expense_adjustments.id',
  `transaction_type` enum('cashin', 'cashout') NOT NULL COMMENT 'Cashin for Returns, Cashout for Extras',
  `amount` decimal(15,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `transaction_ref` varchar(255) DEFAULT NULL,
  `bank_id` int(11) DEFAULT NULL,
  `bank_account_no` varchar(100) DEFAULT NULL,
  `cheque_no` varchar(100) DEFAULT NULL,
  `online_type` varchar(50) DEFAULT NULL,
  `disbursement_proof` text DEFAULT NULL COMMENT 'JSON array of paths',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_adjustment` (`adjustment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
