-- =================================================================================
-- easy-nirmanX: Client Refund Module SQL Reference
-- This file documents the SQL schema and queries created/updated for the refund workflow.
-- =================================================================================

-- 1. Main Client Refunds Table Structure
CREATE TABLE IF NOT EXISTS `client_refund_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` int(10) unsigned NOT NULL COMMENT 'FK → lead_bookings.id',
  `lead_id` int(10) unsigned NOT NULL COMMENT 'FK → leads.id',
  `project_id` int(11) NOT NULL COMMENT 'FK → projects.id',
  `flat_id` int(11) NOT NULL COMMENT 'FK → flats.id',
  `total_paid` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Sum of all verified lead_payments for this booking',
  `retention_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Company charges that cannot be refunded',
  `refund_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'total_paid - retention_amount',
  `reason` text DEFAULT NULL COMMENT 'Free-text refund reason',
  `document` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','refunded') NOT NULL DEFAULT 'pending',
  `cashout_request_id` int(11) DEFAULT NULL COMMENT 'FK → cashout_requests.id (created on approval)',
  `requested_by` int(11) NOT NULL COMMENT 'User who initiated the refund (Accounts)',
  `approved_by` int(11) DEFAULT NULL COMMENT 'User who approved/rejected (Management)',
  `approved_at` datetime DEFAULT NULL,
  `refunded_at` datetime DEFAULT NULL COMMENT 'Timestamp when cashout was fully disbursed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_booking` (`booking_id`),
  KEY `idx_lead` (`lead_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- 2. Query to Auto-Approve Cashout Approvals (Added Today)
-- Run inside ClientRefundController.php upon Refund Approval to bypass redundant cashout approval
UPDATE approval_requests
SET status = 'approved', approved_by = ?, approved_at = NOW()
WHERE source_table = 'cashout_requests' AND source_id = ? AND status = 'pending';


-- 3. Query to fix legacy stuck cashouts (Ran Directly on DB Today)
-- This synced the existing cashouts that were stuck in "pending" approval state
UPDATE approval_requests 
SET status = 'approved' 
WHERE source_table = 'cashout_requests' AND status = 'pending';


-- 4. Corrected Payment History Fetch (Fixed bug with u.name vs u.full_name)
-- Used in ClientRefundController.php to accurately display who added the payment
SELECT cp.*, u.full_name as added_by_name, r.name as added_by_role
FROM cashout_payments cp
LEFT JOIN users u ON cp.created_by = u.id
LEFT JOIN roles r ON u.role_id = r.id
WHERE cp.cashout_request_id = ?
ORDER BY cp.payment_date DESC, cp.id DESC;
