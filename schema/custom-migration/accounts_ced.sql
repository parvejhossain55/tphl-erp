-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 07, 2026 at 07:34 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tphl_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `lead_payments`
--

CREATE TABLE `lead_payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `payment_type` enum('booking_money','registration','emi','land_share','development_charge') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `land_share_amount` decimal(12,2) DEFAULT 0.00,
  `payment_method` enum('cash','bank','online','cheque') DEFAULT 'cash',
  `payment_status` enum('paid','partially_paid','pending','failed') DEFAULT 'paid',
  `payment_date` date NOT NULL,
  `emi_no` int(11) DEFAULT NULL,
  `total_emi_no` int(11) DEFAULT NULL,
  `transaction_ref` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_no` varchar(50) DEFAULT NULL,
  `cheque_no` varchar(50) DEFAULT NULL,
  `service_fee` float DEFAULT NULL,
  `online_type` varchar(50) DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Indexes for dumped tables
--

--
-- Indexes for table `lead_payments`
--
ALTER TABLE `lead_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_payment_booking` (`booking_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `lead_payments`
--
ALTER TABLE `lead_payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


--
-- Cashout Requests table changes for multiple project and office selection`
--
ALTER TABLE `cashout_requests`
 MODIFY project_id VARCHAR(255) DEFAULT NULL;

ALTER TABLE `cashout_requests`
 MODIFY office VARCHAR(255) DEFAULT NULL;

ALTER TABLE `cashout_payments`
 MODIFY project_id VARCHAR(255) DEFAULT NULL;


> -- BATCH: Client Refund System | 25 April 2026
  -- =====================================================================
  
  -- 7. client_refund_requests â€” master table for flat refund requests
  CREATE TABLE IF NOT EXISTS `client_refund_requests` (
      `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `booking_id` INT(10) UNSIGNED NOT NULL COMMENT 'FK â†’ lead_bookings.id',
      `lead_id` INT(10) UNSIGNED NOT NULL COMMENT 'FK â†’ leads.id',
      `project_id` INT(11) NOT NULL COMMENT 'FK â†’ projects.id',
      `flat_id` INT(11) NOT NULL COMMENT 'FK â†’ flats.id',
      `total_paid` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Sum of all verified lead_payments for this booking',
      `retention_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Company charges that cannot be refunded',
      `refund_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'total_paid - retention_amount',
      `reason` TEXT DEFAULT NULL COMMENT 'Free-text refund reason',
      `status` ENUM('pending','approved','rejected','refunded') NOT NULL DEFAULT 'pending',
      `cashout_request_id` INT(11) DEFAULT NULL COMMENT 'FK â†’ cashout_requests.id (created on approval)',
      `requested_by` INT(11) NOT NULL COMMENT 'User who initiated the refund (Accounts)',
      `approved_by` INT(11) DEFAULT NULL COMMENT 'User who approved/rejected (Management)',
      `approved_at` DATETIME DEFAULT NULL,
      `refunded_at` DATETIME DEFAULT NULL COMMENT 'Timestamp when cashout was fully disbursed',
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_booking` (`booking_id`),
      KEY `idx_lead` (`lead_id`),
      KEY `idx_status` (`status`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  
  -- 8. lead_bookings â€” expand booking_status to include 'refunded'
  ALTER TABLE `lead_bookings`
      MODIFY COLUMN `booking_status`
          ENUM('booked','cancelled','completed','refunded')
          DEFAULT 'booked';
  
  -- 9. emi_schedules â€” expand status to include 'cancelled'
  ALTER TABLE `emi_schedules`
      MODIFY COLUMN `status`
          ENUM('pending','paid','missed','cancelled')
          DEFAULT 'pending';
  
  -- 10. lead_payments â€” add missing due_emi_amount column
  ALTER TABLE `lead_payments`
      ADD COLUMN IF NOT EXISTS `due_emi_amount` DECIMAL(12,2) DEFAULT 0.00 AFTER `bulk_emi_discount`;


