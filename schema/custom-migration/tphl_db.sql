-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 23, 2026 at 09:17 AM
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
-- Table structure for table `approval_requests`
--

CREATE TABLE `approval_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `department` varchar(50) NOT NULL,
  `request_type` varchar(50) NOT NULL,
  `title` varchar(150) NOT NULL,
  `source_table` varchar(50) NOT NULL,
  `source_id` int(10) UNSIGNED NOT NULL,
  `request_amount` decimal(12,2) DEFAULT NULL,
  `approved_amount` decimal(12,2) DEFAULT NULL,
  `requested_days` int(11) DEFAULT NULL,
  `approved_days` int(11) DEFAULT NULL,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','approved','rejected','partial') DEFAULT 'pending',
  `requested_by` int(10) UNSIGNED NOT NULL,
  `approve_role` varchar(50) NOT NULL,
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approval_note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blogs`
--

CREATE TABLE `blogs` (
  `id` int(11) NOT NULL,
  `title` text NOT NULL,
  `slug` text DEFAULT NULL,
  `excerpt` longtext DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `author` varchar(100) DEFAULT NULL,
  `published_date` date DEFAULT NULL,
  `comments` int(11) DEFAULT 0,
  `read_time` varchar(50) DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1 COMMENT '1=Published, 0=Draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-5c785c036466adea360111aa28563bfd556b5fba', 'i:1;', 1771691443),
('laravel-cache-5c785c036466adea360111aa28563bfd556b5fba:timer', 'i:1771691443;', 1771691443),
('laravel-cache-77de68daecd823babbb58edb1c8e14d7106e83bb', 'i:1;', 1771695229),
('laravel-cache-77de68daecd823babbb58edb1c8e14d7106e83bb:timer', 'i:1771695228;', 1771695229),
('tphl-erp-cache-5c785c036466adea360111aa28563bfd556b5fba', 'i:1;', 1771733365),
('tphl-erp-cache-5c785c036466adea360111aa28563bfd556b5fba:timer', 'i:1771733365;', 1771733365),
('tphl-erp-cache-77de68daecd823babbb58edb1c8e14d7106e83bb', 'i:1;', 1771731675),
('tphl-erp-cache-77de68daecd823babbb58edb1c8e14d7106e83bb:timer', 'i:1771731675;', 1771731675);

-- --------------------------------------------------------

--
-- Table structure for table `cameras`
--

CREATE TABLE `cameras` (
  `id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `camera_name` varchar(255) NOT NULL,
  `camera_slug` varchar(255) NOT NULL,
  `rtsp_url` text NOT NULL,
  `hls_path` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `last_heartbeat` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `cameras`
--

INSERT INTO `cameras` (`id`, `project_id`, `camera_name`, `camera_slug`, `rtsp_url`, `hls_path`, `status`, `last_heartbeat`, `created_at`) VALUES
(1, 1, 'Cam-1', 'cam_1', 'cam01,rtsp://admin:Hik2025S@103.139.144.106:8555/stream-path', 'hls/the_premium_nobel_court/cam_1.m3u8', 'active', NULL, '2026-02-21 17:39:56');

-- --------------------------------------------------------

--
-- Table structure for table `camera_logs`
--

CREATE TABLE `camera_logs` (
  `id` int(11) NOT NULL,
  `camera_id` int(11) DEFAULT NULL,
  `event_type` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cashout_payments`
--

CREATE TABLE `cashout_payments` (
  `id` int(11) NOT NULL,
  `cashout_request_id` int(11) DEFAULT NULL,
  `office` varchar(200) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `purpose_paid_to` varchar(255) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'cash',
  `transaction_ref` varchar(100) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_no` varchar(50) DEFAULT NULL,
  `cheque_no` varchar(50) DEFAULT NULL,
  `online_type` varchar(50) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cashout_requests`
--

CREATE TABLE `cashout_requests` (
  `id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `office` varchar(255) DEFAULT NULL,
  `requested_by` int(11) NOT NULL COMMENT 'User who requested cashout',
  `purpose_paid_to` varchar(255) NOT NULL COMMENT 'Purpose or payee name',
  `amount` decimal(15,2) NOT NULL COMMENT 'Total requested amount',
  `approved_amount` decimal(15,2) DEFAULT NULL,
  `due_amount` decimal(15,2) NOT NULL COMMENT 'Remaining unpaid amount',
  `description` text DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'cash',
  `transaction_ref` varchar(100) DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','partially_paid','fully_paid') NOT NULL DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `approval_note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Cashout requests with single-step approval';

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `user_role_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `reply` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `communities`
--

CREATE TABLE `communities` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `communities`
--

INSERT INTO `communities` (`id`, `name`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'The Premium Royal City', 1, 3, '2026-02-19 17:00:30', '2026-02-19 17:00:30');

-- --------------------------------------------------------

--
-- Table structure for table `company_bank_accounts`
--

CREATE TABLE `company_bank_accounts` (
  `id` int(11) NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_number` varchar(100) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company_bank_accounts`
--

INSERT INTO `company_bank_accounts` (`id`, `bank_name`, `account_name`, `account_number`, `is_default`, `status`, `created_by`, `created_at`) VALUES
(1, 'City Bank,Shaymoli', 'TPHL', '11221334', 0, 'active', 1, '2026-02-23 14:13:36');

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `contact_status` enum('pending','contacted','closed') NOT NULL DEFAULT 'pending' COMMENT 'Track follow-up status',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `name`, `email`, `phone`, `subject`, `message`, `contact_status`, `created_at`, `updated_at`) VALUES
(1, 'Jubair Khan', 'khan@example.com', '+8801712345678', 'Property Inquiry', 'I am interested in your Gulshan apartment. Please contact me.', 'pending', '2026-02-21 22:08:25', '2026-02-22 04:15:00');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(15, 'Business Development', 1, 2, '2026-02-19 16:17:29', '2026-02-19 16:17:29'),
(16, 'Human Resources', 1, 1, '2026-02-23 05:42:59', '2026-02-23 05:42:59'),
(17, 'Finance & Accounts', 1, 4, '2026-02-23 06:06:59', '2026-02-23 06:06:59');

-- --------------------------------------------------------

--
-- Table structure for table `designations`
--

CREATE TABLE `designations` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `designations`
--

INSERT INTO `designations` (`id`, `name`, `department_id`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Project Manager', 15, 2, '2025-12-07 11:26:14', '2026-02-23 06:07:55'),
(2, 'HR Admin', 16, NULL, '2026-02-23 05:43:43', '2026-02-23 05:43:43'),
(3, 'Accounts Head', 17, NULL, '2026-02-23 06:07:14', '2026-02-23 06:07:14');

-- --------------------------------------------------------

--
-- Table structure for table `emi_schedules`
--

CREATE TABLE `emi_schedules` (
  `id` int(10) UNSIGNED NOT NULL,
  `lead_booking_id` int(10) UNSIGNED NOT NULL,
  `emi_no` int(11) DEFAULT NULL,
  `emi_amount` decimal(12,2) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('pending','paid','missed') DEFAULT 'pending',
  `lead_payment_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `due_emi_amount` decimal(12,2) DEFAULT NULL,
  `service_fee` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `full_name` varchar(200) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `emergency_contact` varchar(20) DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `experience` text DEFAULT NULL,
  `address` text NOT NULL,
  `pass_num` varchar(100) DEFAULT NULL,
  `nid` varchar(200) DEFAULT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `designation_id` int(10) UNSIGNED DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `workplace` varchar(255) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `basic_salary` float DEFAULT NULL,
  `account_holder_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `make_user` int(11) DEFAULT 0,
  `password` varchar(255) NOT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `branch_name` varchar(100) DEFAULT NULL,
  `social_media1` varchar(255) DEFAULT NULL,
  `social_media2` varchar(255) DEFAULT NULL,
  `social_media3` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `employee_type` enum('permanent','contract','director') DEFAULT NULL,
  `employee_status` enum('active','inactive','resigned','terminated','on_leave') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `user_id`, `full_name`, `employee_id`, `username`, `email`, `father_name`, `phone`, `emergency_contact`, `qualification`, `experience`, `address`, `pass_num`, `nid`, `department_id`, `designation_id`, `dob`, `joining_date`, `basic_salary`, `account_holder_name`, `account_number`, `make_user`, `password`, `bank_name`, `branch_name`, `social_media1`, `social_media2`, `social_media3`, `photo`, `employee_type`, `employee_status`, `created_by`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Fahim', 'EMP-00001', '', 'fahim@gmail.com', 'Forhad', '01734555783', '01734555783', 'BBA', '', 'Dhaka', '', '23784', 15, 1, '1998-02-04', '2026-01-01', 30000, 'Fahim', '7523423478', 1, '$2y$10$JphYkjE9NT3MdD93lmgv7uZ2uu4nWUubfKZHjdFvqN6rq6KTk8UIC', 'City Bank', 'Shaymoli', '', '', '', '', 'permanent', 'active', 3, NULL, '2026-02-19 16:54:13', '2026-02-19 16:54:13');

-- --------------------------------------------------------

--
-- Table structure for table `employee_leaves`
--

CREATE TABLE `employee_leaves` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `leave_type_id` int(10) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `requested_days` int(11) NOT NULL,
  `approved_days` int(11) DEFAULT 0,
  `approval_status` enum('pending','approved','partial','rejected') DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_leave_balances`
--

CREATE TABLE `employee_leave_balances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `leave_type_id` int(10) UNSIGNED NOT NULL,
  `remaining_days` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emp_attendances`
--

CREATE TABLE `emp_attendances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `attendance_date` date NOT NULL,
  `punch_in_time` timestamp NULL DEFAULT NULL,
  `punch_out_time` timestamp NULL DEFAULT NULL,
  `today_status` varchar(20) NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `flats`
--

CREATE TABLE `flats` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `sqft` int(11) DEFAULT NULL,
  `bhk` int(11) DEFAULT NULL,
  `land_share_value` float DEFAULT NULL,
  `bedroom` int(11) DEFAULT NULL,
  `bathroom` int(11) DEFAULT NULL,
  `balcony` int(11) DEFAULT NULL,
  `drawing_dining_kitchen` tinyint(1) DEFAULT NULL,
  `common_dining` tinyint(1) DEFAULT NULL,
  `floor_no` int(11) DEFAULT NULL,
  `flat_name` varchar(150) DEFAULT NULL,
  `facing_site` varchar(50) DEFAULT NULL,
  `price_value` decimal(12,2) DEFAULT NULL,
  `facilities` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `status` enum('available','booked','sold') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_booked` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flats`
--

INSERT INTO `flats` (`id`, `project_id`, `sqft`, `bhk`, `land_share_value`, `bedroom`, `bathroom`, `balcony`, `drawing_dining_kitchen`, `common_dining`, `floor_no`, `flat_name`, `facing_site`, `price_value`, `facilities`, `created_by`, `status`, `created_at`, `deleted_at`, `updated_at`, `is_booked`) VALUES
(1, 1, 1200, 3, 1000000, 3, 2, 2, 1, 1, 1, 'Unit A1', 'north', 7000000.00, 'Gym', 3, 'available', '2026-02-19 17:17:08', NULL, '2026-02-19 17:17:08', 0),
(2, 1, 1200, 2, 1200000, 2, 1, 1, 1, 1, 1, 'Unit B1', 'South', 5000000.00, '', 2, 'available', '2026-02-21 13:20:06', NULL, '2026-02-21 13:20:06', 0);

-- --------------------------------------------------------

--
-- Table structure for table `flat_purchase_rules`
--

CREATE TABLE `flat_purchase_rules` (
  `id` int(11) NOT NULL,
  `rules_description` text DEFAULT NULL,
  `declearation` text DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `head_notifications`
--

CREATE TABLE `head_notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('lead','booking','task','activity') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `id` int(10) UNSIGNED NOT NULL,
  `campaign_name` varchar(255) DEFAULT NULL,
  `platform` varchar(100) DEFAULT NULL,
  `which_apartment_size_are_you_interested_in` varchar(100) DEFAULT NULL,
  `which_floor_are_you_for_shop` varchar(50) DEFAULT NULL,
  `when_are_you_planning_to_buy` varchar(200) DEFAULT NULL,
  `would_you_like_to_schedule_a_site_visit` varchar(200) DEFAULT NULL,
  `when_is_the_best_time_to_contact_you` varchar(50) DEFAULT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `job_title` varchar(100) DEFAULT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `call_status` enum('not_called','called','received','not_received') DEFAULT 'not_called',
  `call_note` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `lead_name` varchar(255) DEFAULT NULL,
  `lead_type` enum('Hot','Warm','Cold') DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `value_amount` decimal(15,2) DEFAULT NULL,
  `currency` enum('USD','EURO','RUBLE','POUND','BDT') DEFAULT 'BDT',
  `phone` varchar(30) DEFAULT NULL,
  `alt_phone_number` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `source_type_id` int(11) DEFAULT NULL,
  `assigned_id` int(11) DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `mother_name` varchar(100) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `nid_or_passport` varchar(100) DEFAULT NULL,
  `present_add_bn` varchar(255) DEFAULT NULL,
  `permanent_add_bn` varchar(255) DEFAULT NULL,
  `identity_type` enum('NID','Passport') DEFAULT NULL,
  `permanent_address` varchar(250) DEFAULT NULL,
  `pref_location_id` int(11) DEFAULT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `pref_flat_size` varchar(250) DEFAULT NULL,
  `communication_type` enum('WA Messaging','WA Conversion') DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `visibility` enum('public','private','selected') DEFAULT 'public',
  `status` enum('active','inactive') DEFAULT 'active',
  `last_lead_action` enum('new','booked','follow up','visited','cancel') DEFAULT 'new',
  `deleted_at` datetime DEFAULT NULL,
  `lead_quality` enum('Not Interested','Prospect','Pending') DEFAULT 'Pending',
  `lead_image` varchar(250) DEFAULT NULL,
  `lead_name_bn` varchar(250) DEFAULT NULL,
  `father_name_bn` varchar(250) DEFAULT NULL,
  `mother_name_bn` varchar(250) DEFAULT NULL,
  `profession_bn` varchar(250) DEFAULT NULL,
  `nationality` varchar(250) DEFAULT NULL,
  `religion` enum('Islam','Hinduism','Buddhism','Christianity','Other') DEFAULT NULL,
  `nominee_name` varchar(250) DEFAULT NULL,
  `nomi_father_name` varchar(250) DEFAULT NULL,
  `nomi_mother_name` varchar(250) DEFAULT NULL,
  `nomi_profession` varchar(250) DEFAULT NULL,
  `relation_with_lead` varchar(200) DEFAULT NULL,
  `nomi_dob` date DEFAULT NULL,
  `nomi_nid_passport` varchar(200) DEFAULT NULL,
  `nomi_phone` varchar(100) DEFAULT NULL,
  `nomi_email` varchar(200) DEFAULT NULL,
  `nomi_present_add` varchar(200) DEFAULT NULL,
  `nomi_permanent_address` varchar(255) DEFAULT NULL,
  `nomi_image` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `residency_type` enum('local','expatriate') DEFAULT 'local',
  `country` varchar(200) DEFAULT NULL,
  `preferred_contact_time` enum('morning','afternoon','evening','night') DEFAULT NULL,
  `is_booked` tinyint(1) DEFAULT 0 COMMENT '1 = booked, 0 = no booking',
  `is_send_message` tinyint(1) DEFAULT 0 ,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leads`
--

INSERT INTO `leads` (`id`, `campaign_name`, `platform`, `which_apartment_size_are_you_interested_in`, `which_floor_are_you_for_shop`, `when_are_you_planning_to_buy`, `would_you_like_to_schedule_a_site_visit`, `when_is_the_best_time_to_contact_you`, `full_name`, `job_title`, `company_name`, `phone_number`, `call_status`, `call_note`, `city`, `lead_name`, `lead_type`, `company`, `value_amount`, `currency`, `phone`, `alt_phone_number`, `email`, `source_type_id`, `assigned_id`, `assigned_at`, `address`, `father_name`, `mother_name`, `dob`, `nid_or_passport`, `present_add_bn`, `permanent_add_bn`, `identity_type`, `permanent_address`, `pref_location_id`, `designation`, `project_id`, `pref_flat_size`, `communication_type`, `tags`, `description`, `visibility`, `status`, `last_lead_action`, `deleted_at`, `lead_quality`, `lead_image`, `lead_name_bn`, `father_name_bn`, `mother_name_bn`, `profession_bn`, `nationality`, `religion`, `nominee_name`, `nomi_father_name`, `nomi_mother_name`, `nomi_profession`, `relation_with_lead`, `nomi_dob`, `nomi_nid_passport`, `nomi_phone`, `nomi_email`, `nomi_present_add`, `nomi_permanent_address`, `nomi_image`, `created_by`, `created_at`, `updated_at`, `residency_type`, `country`, `preferred_contact_time`, `is_booked`,`is_send_message`) VALUES
(1, 'P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025', 'fb', NULL, '1', NULL, NULL, 'evening_(5_pm_-_8_pm)', 'Raihan', 'a', 'a', 'p:+8801736936727', 'not_called', NULL, 'Dhaka', NULL, NULL, NULL, NULL, 'BDT', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'public', '', 'new', NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-22 09:48:11', '2026-02-22 09:48:11', 'local', NULL, NULL, 0,0),
(2, 'P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025', 'fb', '1550_sqft', '2', NULL, NULL, 'afternoon_(12_pm_-_5_pm)', 'Enam Pathan', 'owner', 'Jet Sports', 'p:+8801717221212', 'not_called', NULL, 'Jatramura', NULL, NULL, NULL, NULL, 'BDT', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'public', 'inactive', 'new', '2026-02-23 04:39:20', 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-22 09:48:11', '2026-02-23 05:15:34', 'local', NULL, NULL, 0,0),
(3, 'P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025', 'fb', '1450_sqft', '3', NULL, NULL, 'afternoon_(12_pm_-_5_pm)', 'Forhad Ali', 'AGM', 'Flamingo Ltd', 'p:+8801730440638', 'not_called', NULL, 'Dhaka', NULL, NULL, NULL, NULL, 'BDT', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'public', '', 'new', NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-22 09:48:11', '2026-02-23 05:15:39', 'local', NULL, NULL, 0,0),
(4, 'P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025', 'fb', '1250_sqft', '4', NULL, NULL, 'afternoon_(12_pm_-_5_pm)', 'Sohel Khan', 'propriter', 'beauty sportes and net company', 'p:+8801840990890', 'not_called', NULL, 'Dhaka', NULL, NULL, NULL, NULL, 'BDT', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'public', '', 'new', NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-22 09:48:11', '2026-02-23 05:15:50', 'local', NULL, NULL, 0,0),
(5, 'P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025', 'fb', '1550_sqft', '5', NULL, NULL, 'afternoon_(12_pm_-_5_pm)', 'Jahirul Islam', 'job', 'xyz', 'p:+8801711949142', 'not_called', NULL, 'Dhaka', NULL, NULL, NULL, NULL, 'BDT', NULL, NULL, NULL, 0, 3, '2026-02-23 05:22:04', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'public', '', 'new', NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, '2026-02-22 09:48:11', '2026-02-23 05:15:53', 'local', NULL, NULL, 0,0),
(6, 'P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025', 'fb', '1350_sqft', '6', NULL, NULL, 'evening_(5_pm_-_8_pm)', 'Soykot Islam Shihab', 'Owner', 'Amin Mistanno Vhander', 'p:+8801752697272', 'not_called', NULL, 'Uttarati', NULL, NULL, NULL, NULL, 'BDT', NULL, NULL, NULL, 0, 3, '2026-02-23 05:22:04', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'public', '', 'new', NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, '2026-02-22 09:48:11', '2026-02-23 05:15:59', 'local', NULL, NULL, 0,0),
(7, 'P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025', 'fb', '1550_sqft', '7', NULL, NULL, 'afternoon_(12_pm_-_5_pm)', 'Mahfuzur Rahman', 'MD, MR Income Tax Consultancy Ltd', 'M R Income Tax Consultancy Ltd.', 'p:+8801916158171', 'not_called', NULL, 'Dhaka', NULL, NULL, NULL, NULL, 'BDT', NULL, NULL, NULL, 0, 3, '2026-02-23 05:22:04', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'public', '', 'new', NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, '2026-02-22 09:48:11', '2026-02-23 05:16:17', 'local', NULL, NULL, 0,0),
(8, 'P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025', 'fb', NULL, '8', NULL, NULL, 'afternoon_(12_pm_-_5_pm)', 'Shariful Islam Sharif', 'Business management', '72 mollertack, ashkona,dokhinkhan,dhaka', 'p:+8801781860471', 'called', NULL, 'Dhaka', NULL, NULL, NULL, NULL, 'BDT', NULL, NULL, NULL, 0, 3, '2026-02-23 05:22:04', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'public', '', 'follow up', NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, '2026-02-22 09:48:11', '2026-02-23 05:36:49', 'local', NULL, NULL, 0,0);

-- --------------------------------------------------------

--
-- Table structure for table `lead_bookings`
--

CREATE TABLE `lead_bookings` (
  `id` int(10) UNSIGNED NOT NULL,
  `lead_id` int(10) UNSIGNED NOT NULL,
  `project_id` int(11) NOT NULL,
  `flat_id` int(11) NOT NULL,
  `agreement_value` decimal(12,2) NOT NULL,
  `discount_amount` float DEFAULT 0,
  `booking_date` date NOT NULL,
  `booking_status` enum('booked','cancelled','completed') DEFAULT 'booked',
  `created_by` int(11) NOT NULL,
  `sales_by_id` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approval_status` enum('not_required','pending','approved','rejected','partial') DEFAULT 'not_required',
  `approved_amount` decimal(12,2) DEFAULT NULL,
  `service_fee_balance` float DEFAULT NULL,
  `landshare_status` enum('active','completed','cancelled') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lead_follow_ups`
--

CREATE TABLE `lead_follow_ups` (
  `id` int(10) UNSIGNED NOT NULL,
  `lead_id` int(10) UNSIGNED NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `flat_id` int(11) DEFAULT NULL,
  `follow_up_date` date NOT NULL,
  `follow_up_time` time DEFAULT NULL,
  `follow_up_type` enum('call','visit','meeting','whatsapp','email') NOT NULL,
  `status` enum('pending','completed','missed','canceled') DEFAULT 'pending',
  `discussion_note` text DEFAULT NULL,
  `next_follow_up_date` date DEFAULT NULL,
  `assigned_with` int(11) DEFAULT NULL,
  `next_action` enum('site visit','booking','call_back','take_time') DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lead_follow_ups`
--

INSERT INTO `lead_follow_ups` (`id`, `lead_id`, `project_id`, `flat_id`, `follow_up_date`, `follow_up_time`, `follow_up_type`, `status`, `discussion_note`, `next_follow_up_date`, `assigned_with`, `next_action`, `created_by`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 8, 1, 1, '2026-02-22', '11:36:00', 'call', 'pending', 'Test', '2026-02-23', 3, NULL, 3, NULL, '2026-02-23 05:36:49', '2026-02-23 05:38:52');

-- --------------------------------------------------------

--
-- Table structure for table `lead_payments`
--

CREATE TABLE `lead_payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `payment_type` enum('booking_money','registration','emi','land_share') NOT NULL,
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

-- --------------------------------------------------------

--
-- Table structure for table `lead_sources`
--

CREATE TABLE `lead_sources` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

CREATE TABLE `leave_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `total_days` int(11) NOT NULL DEFAULT 0,
  `is_paid` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_types`
--

INSERT INTO `leave_types` (`id`, `name`, `total_days`, `is_paid`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Medical Leave', 20, 1, 1, 4, '2026-02-23 07:02:09', '2026-02-23 07:02:09'),
(2, 'Sick Leave', 12, 1, 1, 4, '2026-02-23 07:02:30', '2026-02-23 07:02:30');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `name`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Bashundhora Model Town', 3, '2026-02-19 16:57:02', '2026-02-19 16:57:02');

-- --------------------------------------------------------

--
-- Table structure for table `management_cashin`
--

CREATE TABLE `management_cashin` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_name` varchar(200) NOT NULL,
  `role_id` int(10) UNSIGNED DEFAULT NULL,
  `person_name` varchar(200) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `source` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','bank','cheque','online') DEFAULT 'cash',
  `payment_status` enum('paid','pending','failed') DEFAULT 'paid',
  `transaction_ref` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_no` varchar(50) DEFAULT NULL,
  `cheque_no` varchar(50) DEFAULT NULL,
  `online_type` varchar(50) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `management_cashout`
--

CREATE TABLE `management_cashout` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `role_name` varchar(200) NOT NULL,
  `role_id` int(10) UNSIGNED DEFAULT NULL,
  `person_name` varchar(200) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `paid_to` varchar(255) NOT NULL COMMENT 'Recipient of the payment',
  `description` text DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','bank','cheque','online') DEFAULT 'cash',
  `payment_status` enum('paid','pending','failed') DEFAULT 'paid',
  `transaction_ref` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_no` varchar(50) DEFAULT NULL,
  `cheque_no` varchar(50) DEFAULT NULL,
  `online_type` varchar(50) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `management_categories`
--

CREATE TABLE `management_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `management_roles`
--

CREATE TABLE `management_roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_name` varchar(200) NOT NULL,
  `person_name` varchar(200) NOT NULL,
  `status` tinyint(1) DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `assigned_by` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `onboard_clients`
--

CREATE TABLE `onboard_clients` (
  `id` int(11) NOT NULL,
  `lead_booking_id` int(10) UNSIGNED DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `cameraLink1` varchar(255) DEFAULT NULL,
  `cameraLink2` varchar(255) DEFAULT NULL,
  `moneyReceiptLink` varchar(255) DEFAULT NULL,
  `purchaseDetailsLink` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `lead_id` int(10) UNSIGNED DEFAULT NULL,
  `project_id` int(10) UNSIGNED DEFAULT NULL,
  `flat_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payslips`
--

CREATE TABLE `payslips` (
  `id` int(11) NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `designation_id` int(10) UNSIGNED DEFAULT NULL,
  `payslip_month` date NOT NULL,
  `total_earning` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_deduction` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `company_bank_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','paid') NOT NULL DEFAULT 'pending',
  `payment_method` enum('bank','cash','mobile_banking') NOT NULL DEFAULT 'bank',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payslip_details`
--

CREATE TABLE `payslip_details` (
  `id` int(11) NOT NULL,
  `payslip_id` int(11) NOT NULL,
  `payslip_item_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `payslip_details`
--

INSERT INTO `payslip_details` (`id`, `payslip_id`, `payslip_item_id`, `amount`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2000.00, NULL, '2026-02-23 08:11:48', '2026-02-23 08:11:48'),
(2, 1, 2, 1000.00, NULL, '2026-02-23 08:11:48', '2026-02-23 08:11:48'),
(3, 1, 3, 1000.00, NULL, '2026-02-23 08:11:48', '2026-02-23 08:11:48');

-- --------------------------------------------------------

--
-- Table structure for table `payslip_items`
--

CREATE TABLE `payslip_items` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('earning','deduction') NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `payslip_items`
--

INSERT INTO `payslip_items` (`id`, `name`, `type`, `description`, `created_by`, `created_at`) VALUES
(1, 'House Rent Allowance', 'earning', NULL, 4, '2026-02-23 13:58:58'),
(2, 'Medical  Allowance', 'earning', NULL, 4, '2026-02-23 14:04:36'),
(3, 'Unpaid Leave', 'deduction', NULL, 4, '2026-02-23 14:05:02');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Add Role', 1, NULL, '2025-12-04 12:12:58', '2025-12-04 12:12:58'),
(2, 'View Role', 1, NULL, '2025-12-04 12:17:47', '2025-12-04 12:17:47'),
(3, 'Add Permission', 1, NULL, '2025-12-06 05:51:07', '2025-12-06 05:51:07'),
(4, 'Show Permission', 1, NULL, '2025-12-06 05:51:25', '2025-12-06 05:51:25'),
(5, 'Add Task', 1, NULL, '2025-12-10 04:26:45', '2025-12-10 04:26:45'),
(6, 'Hr Manage', 1, 12, '2025-12-17 09:52:36', '2025-12-17 09:52:36'),
(7, 'Sales & Business', 1, NULL, '2026-01-04 01:08:25', '2026-01-04 01:08:25'),
(8, 'Payroll Manage', 1, NULL, '2026-01-04 01:11:05', '2026-01-04 01:11:05'),
(10, 'Accounts Manage', 1, NULL, '2026-01-04 01:11:51', '2026-01-04 01:11:51'),
(11, 'Role & Permission', 1, NULL, '2026-01-04 01:17:03', '2026-01-04 01:17:03'),
(12, 'CED Manage', 1, NULL, '2026-01-04 04:29:51', '2026-01-04 04:29:51'),
(13, 'Task Manage', 1, NULL, '2026-01-04 04:32:50', '2026-01-04 04:32:50'),
(14, 'Others', 1, NULL, '2026-01-04 05:04:47', '2026-01-04 05:04:47'),
(15, 'Add Lead', 1, 12, '2026-01-31 05:23:20', '2026-01-31 05:23:20'),
(16, 'User Manage', 1, 12, '2026-01-31 12:22:53', '2026-01-31 12:22:53'),
(17, 'Add User', 1, 12, '2026-01-31 12:23:02', '2026-01-31 12:23:02'),
(19, 'View Lead', 1, 12, '2026-02-01 12:06:45', '2026-02-01 12:06:45'),
(20, 'My Leave Request', 1, 12, '2026-02-08 12:00:34', '2026-02-08 12:00:34'),
(21, 'Camera Manage', 1, 2, '2026-02-21 17:24:42', '2026-02-21 17:24:42');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\OnboardClient', 1, 'onboard-client', '7e24226295485ceac5b2f5d81d02c236b5a160f988449891f39e42cdae87e72d', '[\"*\"]', '2026-02-08 04:28:21', NULL, '2026-02-08 04:20:34', '2026-02-08 04:28:21'),
(2, 'App\\Models\\OnboardClient', 1, 'onboard-client', '903f420ab84ed7fefe35bb26f11ab0f683c3bcde22b8cbd787bf03e613fa8d98', '[\"*\"]', '2026-02-08 04:31:41', NULL, '2026-02-08 04:28:39', '2026-02-08 04:31:41'),
(3, 'App\\Models\\OnboardClient', 1, 'onboard-client', '13aab24d14d21706763b5fa533c05d4a310b76acdcb2ac433578794223a04582', '[\"*\"]', '2026-02-08 04:39:06', NULL, '2026-02-08 04:36:40', '2026-02-08 04:39:06'),
(4, 'App\\Models\\OnboardClient', 1, 'onboard-client', '4561e3242fb548617bc199392d02366d885d02efbdbdfd5a5df6288142b2f49d', '[\"*\"]', '2026-02-09 23:31:48', NULL, '2026-02-09 23:31:22', '2026-02-09 23:31:48'),
(5, 'App\\Models\\OnboardClient', 2, 'onboard-client', 'ce3cafbff5660c8fea29fb233264fe87cc30a35e29ab0cabc50e629dcdb8ccb9', '[\"*\"]', '2026-02-17 03:52:04', NULL, '2026-02-17 03:51:52', '2026-02-17 03:52:04'),
(6, 'App\\Models\\OnboardClient', 3, 'onboard-client', 'e92286bbd37589f387f496582e3aacafb03063a89ae49f9143b5f62df1ab4d7c', '[\"*\"]', '2026-02-21 11:32:47', NULL, '2026-02-21 10:29:47', '2026-02-21 11:32:47'),
(7, 'App\\Models\\OnboardClient', 3, 'onboard-client', 'b23fc29f72107b0178d8fa4d62c3e20edb0f69d69a22585ae301885a174a21b6', '[\"*\"]', '2026-02-21 21:40:15', NULL, '2026-02-21 21:40:04', '2026-02-21 21:40:15');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` text DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `community_id` int(11) NOT NULL,
  `building_type` varchar(50) DEFAULT NULL,
  `total_share` int(11) DEFAULT NULL,
  `unit_per_floor` int(11) DEFAULT NULL,
  `floor_wise_unit_meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`floor_wise_unit_meta`)),
  `types` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `image2` varchar(255) DEFAULT NULL,
  `image3` varchar(255) DEFAULT NULL,
  `front_road` varchar(50) DEFAULT NULL,
  `price_range` enum('Under 50 Lac BDT','50 Lac - 1 Crore BDT','1 Crore - 2 Crore BDT','2 Crore - 5 Crore BDT','Above 5 Crore BDT') DEFAULT NULL,
  `hover_text` varchar(255) DEFAULT NULL,
  `tag` varchar(50) DEFAULT NULL,
  `plot_size` float DEFAULT NULL,
  `plot_no` varchar(100) DEFAULT NULL,
  `block_no` varchar(100) DEFAULT NULL,
  `brochure_link` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `apartment_count` int(11) DEFAULT NULL,
  `amenities_details` longtext DEFAULT NULL,
  `amenities_image` varchar(255) DEFAULT NULL,
  `map_image` varchar(255) DEFAULT NULL,
  `map_link` varchar(255) DEFAULT NULL,
  `project_map_location` varchar(250) DEFAULT NULL,
  `passenger_lift` int(11) DEFAULT NULL,
  `cargo_lift` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `rooftop_gardening` tinyint(1) DEFAULT 0 COMMENT '1=Yes, 0=No',
  `swimming_pool` tinyint(1) DEFAULT 0 COMMENT '1=Yes, 0=No',
  `convention` tinyint(1) DEFAULT 0 COMMENT '1=Yes, 0=No',
  `electricity_backup` tinyint(1) DEFAULT 0 COMMENT '1=Yes, 0=No',
  `car_parking` int(11) DEFAULT NULL,
  `project_sale_status` enum('Sold Out','On Sale','Upcoming') DEFAULT 'On Sale',
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `name`, `slug`, `location_id`, `community_id`, `building_type`, `total_share`, `unit_per_floor`, `floor_wise_unit_meta`, `types`, `image`, `image2`, `image3`, `front_road`, `price_range`, `hover_text`, `tag`, `plot_size`, `plot_no`, `block_no`, `brochure_link`, `description`, `apartment_count`, `amenities_details`, `amenities_image`, `map_image`, `map_link`, `project_map_location`, `passenger_lift`, `cargo_lift`, `created_by`, `created_at`, `updated_at`, `rooftop_gardening`, `swimming_pool`, `convention`, `electricity_backup`, `car_parking`, `project_sale_status`, `deleted_at`) VALUES
(1, 'The Premium Nobel Court', 'the-premium-nobel-court', 1, 1, 'STUDIO, 1, 2 BEDROOMS & PENTHOUSE', 20, 4, '{\"typical_floor\":[{\"unit_name\":\"Unit - A\",\"sqft\":1200},{\"unit_name\":\"Unit - B\",\"sqft\":1100},{\"unit_name\":\"Unit - C\",\"sqft\":1400},{\"unit_name\":\"Unit - D\",\"sqft\":1300}]}', 'Test', '', '', '', '40', 'Under 50 Lac BDT', 'The Premium Nobel Court', NULL, 70, '5', NULL, 'https://drive.google.com/file/d/1Shb6eNMOd6KtKCO4aMYMVhIsVJn_qf7n/view?usp=sharing', '', 2, '', '', '', 'https://drive.google.com/file/d/1Shb6eNMOd6KtKCO4aMYMVhIsVJn_qf7n/view?usp=sharing', 'https://drive.google.com/file/d/1Shb6eNMOd6KtKCO4aMYMVhIsVJn_qf7n/view?usp=sharing', 1, 1, 3, '2026-02-19 17:05:16', '2026-02-19 17:05:16', 1, NULL, 0, 0, 1, 'On Sale', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `property_reviews`
--

CREATE TABLE `property_reviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `reviewer_name` varchar(150) NOT NULL,
  `reviewer_role` varchar(100) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `reviewer_image` varchar(255) DEFAULT NULL,
  `review_video_url` varchar(255) DEFAULT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL CHECK (`rating` between 1 and 5),
  `review_text` text NOT NULL,
  `review_date` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Admin', NULL, '2025-12-06 04:14:57', '2025-12-06 04:14:57'),
(2, 'Employee', NULL, '2025-12-06 08:25:53', '2025-12-06 08:25:53'),
(3, 'super Admin', NULL, '2025-12-06 11:09:07', '2025-12-06 11:09:07'),
(4, 'HR', NULL, '2025-12-17 06:46:21', '2025-12-17 06:46:21'),
(5, 'Sales Manager', NULL, '2026-01-04 01:14:34', '2026-01-04 01:14:34'),
(6, 'Accounts ', NULL, '2026-01-04 01:15:42', '2026-01-04 01:15:42'),
(7, 'Clients Experiment', NULL, '2026-01-04 04:34:42', '2026-01-04 04:34:42'),
(8, 'Sales Executive', NULL, '2026-01-30 04:37:41', '2026-01-30 04:37:41'),
(9, 'Sales Team Head', NULL, '2026-02-01 12:06:29', '2026-02-01 12:06:29');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`, `created_at`, `updated_at`) VALUES
(26, 7, 12, '2026-02-05 07:06:27', '2026-02-05 07:06:27'),
(27, 7, 13, '2026-02-05 07:06:27', '2026-02-05 07:06:27'),
(30, 9, 19, '0000-00-00 00:00:00', '2026-02-19 16:13:53'),
(31, 6, 10, '2026-02-08 12:01:16', '2026-02-08 12:01:16'),
(32, 6, 20, '2026-02-08 12:01:16', '2026-02-08 12:01:16'),
(33, 6, 13, '2026-02-08 12:01:16', '2026-02-08 12:01:16'),
(34, 5, 17, '2026-02-08 12:01:26', '2026-02-08 12:01:26'),
(35, 5, 20, '2026-02-08 12:01:26', '2026-02-08 12:01:26'),
(36, 5, 7, '2026-02-08 12:01:26', '2026-02-08 12:01:26'),
(37, 5, 13, '2026-02-08 12:01:26', '2026-02-08 12:01:26'),
(38, 4, 6, '2026-02-08 12:01:42', '2026-02-08 12:01:42'),
(39, 4, 20, '0000-00-00 00:00:00', '2026-02-19 16:13:53'),
(40, 4, 8, '0000-00-00 00:00:00', '2026-02-19 16:13:53'),
(41, 4, 13, '0000-00-00 00:00:00', '2026-02-19 16:13:53'),
(43, 4, 16, '0000-00-00 00:00:00', '2026-02-19 16:13:53'),
(45, 1, 10, '2026-02-21 17:25:08', '2026-02-21 17:25:08'),
(46, 1, 3, '2026-02-21 17:25:08', '2026-02-21 17:25:08'),
(47, 1, 1, '2026-02-21 17:25:08', '2026-02-21 17:25:08'),
(48, 1, 5, '2026-02-21 17:25:08', '2026-02-21 17:25:08'),
(49, 1, 21, '2026-02-21 17:25:08', '2026-02-21 17:25:08'),
(50, 1, 12, '2026-02-21 17:25:08', '2026-02-21 17:25:08'),
(51, 1, 6, '2026-02-21 17:25:08', '2026-02-21 17:25:08'),
(52, 1, 14, '2026-02-21 17:25:08', '2026-02-21 17:25:08'),
(53, 1, 11, '2026-02-21 17:25:08', '2026-02-21 17:25:08'),
(54, 1, 7, '2026-02-21 17:25:08', '2026-02-21 17:25:08'),
(55, 1, 4, '2026-02-21 17:25:08', '2026-02-21 17:25:08'),
(56, 1, 13, '2026-02-21 17:25:08', '2026-02-21 17:25:08'),
(57, 1, 2, '2026-02-21 17:25:08', '2026-02-21 17:25:08'),
(58, 3, 10, '2026-02-21 17:25:21', '2026-02-21 17:25:21'),
(59, 3, 3, '2026-02-21 17:25:21', '2026-02-21 17:25:21'),
(60, 3, 1, '2026-02-21 17:25:21', '2026-02-21 17:25:21'),
(61, 3, 5, '2026-02-21 17:25:21', '2026-02-21 17:25:21'),
(62, 3, 21, '2026-02-21 17:25:21', '2026-02-21 17:25:21'),
(63, 3, 12, '2026-02-21 17:25:21', '2026-02-21 17:25:21'),
(64, 3, 6, '2026-02-21 17:25:21', '2026-02-21 17:25:21'),
(65, 3, 14, '2026-02-21 17:25:21', '2026-02-21 17:25:21'),
(66, 3, 8, '2026-02-21 17:25:21', '2026-02-21 17:25:21'),
(67, 3, 11, '2026-02-21 17:25:21', '2026-02-21 17:25:21'),
(68, 3, 7, '2026-02-21 17:25:21', '2026-02-21 17:25:21'),
(69, 3, 4, '2026-02-21 17:25:21', '2026-02-21 17:25:21'),
(70, 3, 13, '2026-02-21 17:25:21', '2026-02-21 17:25:21'),
(71, 3, 2, '2026-02-21 17:25:21', '2026-02-21 17:25:21'),
(72, 8, 15, '2026-02-23 07:40:38', '2026-02-23 07:40:38'),
(73, 8, 20, '2026-02-23 07:40:38', '2026-02-23 07:40:38');

-- --------------------------------------------------------

--
-- Table structure for table `sales_targets`
--

CREATE TABLE `sales_targets` (
  `id` int(11) NOT NULL,
  `sales_user_id` int(11) NOT NULL,
  `month` date NOT NULL COMMENT 'Use first day of month e.g. 2026-02-01',
  `site_visit_target` int(11) DEFAULT 0,
  `booking_target` int(11) DEFAULT 0,
  `booking_value_target` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_targets`
--

INSERT INTO `sales_targets` (`id`, `sales_user_id`, `month`, `site_visit_target`, `booking_target`, `booking_value_target`, `created_at`, `updated_at`) VALUES
(1, 3, '2026-02-01', 20, 10, 0.00, '2026-02-22 03:55:03', '2026-02-22 03:55:03');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `website_title` varchar(255) DEFAULT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `footer_text` varchar(255) DEFAULT NULL,
  `white_logo` varchar(255) DEFAULT NULL,
  `dark_logo` varchar(200) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `alt_phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `alt_email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `copyright` varchar(255) DEFAULT NULL,
  `facebook_link` varchar(255) DEFAULT NULL,
  `messenger_link` varchar(255) DEFAULT NULL,
  `instagram_link` varchar(255) DEFAULT NULL,
  `tiktok_link` varchar(255) DEFAULT NULL,
  `x_link` varchar(255) DEFAULT NULL,
  `youtube_link` varchar(255) DEFAULT NULL,
  `header_code` text DEFAULT NULL,
  `body_code` text DEFAULT NULL,
  `client_declaration` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `website_title`, `website_url`, `footer_text`, `white_logo`, `dark_logo`, `phone`, `alt_phone`, `email`, `alt_email`, `address`, `copyright`, `facebook_link`, `messenger_link`, `instagram_link`, `tiktok_link`, `x_link`, `youtube_link`, `header_code`, `body_code`, `client_declaration`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'The Premium Home Limited', NULL, 'fxdgrdgth', '6948f14a9fb8c_1766388042.png', 'settings_699c0cd0c3306.png', '+8801958253300', '+8801958253300', 'info@thepremiumhomes.com', NULL, 'Dhaka', 'ï¿½ 2025 The Premium Homes Ltd. All rights reserved.', 'uidysfb re', 'kuysret87', 'uidsfyter', 'dsilfu67rt', 'iureyt87e', 'ids7rt89', '', '', 'I hereby declare that I have applied for the purchase of the above-mentioned flat under the terms and conditions set by the developer. I confirm that all the information provided in this document is true, correct, and complete to the best of my knowledge and belief.\r\n\r\nI further agree that I have carefully read, understood, and accepted all the terms and conditions, rules, and regulations related to the purchase of the said flat. I undertake to abide by all decisions, policies, and future regulations imposed by the developer in connection with this property.\r\n\r\nI also acknowledge that any false information or violation of the agreed terms may result in cancellation of the booking in accordance with the developerï¿½s policy.', 1, '2025-12-15 12:14:07', '2026-02-23 08:16:16');

-- --------------------------------------------------------

--
-- Table structure for table `site_visits`
--

CREATE TABLE `site_visits` (
  `id` int(11) NOT NULL,
  `lead_id` int(10) UNSIGNED NOT NULL,
  `project_id` int(11) NOT NULL,
  `flat_id` int(11) DEFAULT NULL,
  `visit_date` date NOT NULL,
  `visit_time` time DEFAULT NULL,
  `visit_status` enum('scheduled','completed','cancelled','no_show') DEFAULT 'scheduled',
  `feedback` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sliders`
--

CREATE TABLE `sliders` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1 COMMENT '1 = Active, 0 = Inactive',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sliders`
--

INSERT INTO `sliders` (`id`, `title`, `subtitle`, `link`, `image`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Slider 1', 'no sub title', '', '693fc40ad67f6_1765786634.jpg', 1, NULL, '2025-12-15 14:17:14', '2025-12-15 14:17:14'),
(2, 'slider 2', 'not title', '', '693fd0f16af19_1765789937.webp', 1, NULL, '2025-12-15 15:12:17', '2025-12-15 15:12:17');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `deadline` datetime DEFAULT NULL,
  `employee_accept` tinyint(1) DEFAULT 0,
  `status` enum('pending','in_progress','completed','expired') DEFAULT 'pending',
  `progress` int(11) DEFAULT 0,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `employee_id` int(10) UNSIGNED DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `designation_id` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `nid` varchar(20) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `password` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `media_link1` varchar(255) DEFAULT NULL,
  `media_link2` varchar(255) DEFAULT NULL,
  `last_logout` datetime DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `employee_id`, `full_name`, `user_name`, `phone`, `email`, `designation_id`, `address`, `nid`, `role_id`, `password`, `photo`, `media_link1`, `media_link2`, `last_logout`, `created_by`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Farzana', 'farzana', '01873244236', 'farz@gmail.com', 1, 'Dhaka', NULL, 1, '$2y$10$tyGb6XsaCzqrB5/fmTtTEualGz9EEsCTS4F0mAz7b/ITjBISkKirS', NULL, NULL, NULL, '2026-03-05 14:12:34', NULL, '2026-02-22 06:47:08', '2026-03-05 08:12:34'),
(2, NULL, 'Sojib', 'sojib', '01798342755', 'sales@gmail.com', 1, 'Dhaka', NULL, 5, '$2y$10$tyGb6XsaCzqrB5/fmTtTEualGz9EEsCTS4F0mAz7b/ITjBISkKirS', NULL, NULL, NULL, '2026-02-23 11:39:50', NULL, '2026-02-22 06:47:08', '2026-02-23 05:39:50'),
(3, 1, 'Fahim', 'Fahim', '01790128471', 'fahim@gmail.com', 1, 'Mohammadpur, Dhaka-1207', '23445', 8, '$2y$10$Jy1aK9a5d9P7mA0TqHEkT.RS/7kPh0x/fxpmRaOR1JrLJXEV0ObsG', NULL, '', '', '2026-02-24 11:09:41', 4, '2026-02-23 03:24:25', '2026-02-24 05:09:41'),
(4, 1, 'Mr. Joshim', 'Mr. Joshim', '01762013007', 'hr@gmail.com', 2, 'Dhaka', '1289234', 4, '$2y$10$bhU084TG9E1hi2gpC3g4nODNLbG5lzKoo7vj/Uv6KeYWXtzduE0Ry', NULL, '', '', '2026-02-24 11:31:24', 4, '2026-02-23 05:44:19', '2026-02-24 05:31:24'),
(5, 0, 'Mr. Jisan', 'Mr. Jisan', '01390128223', 'accounts@gmail.com', 3, 'Mohammadpur, Dhaka-1207', '2345676', 6, '$2y$10$cIIGEotRMUt4NdR3BelTA.wRCL7CDcC.s.1UY0ncO7lbttcKda2C6', NULL, '', '', '2026-02-23 12:11:06', 3, '2026-02-23 06:11:06', '2026-02-23 09:11:44'),
(6, NULL, 'Cleint Overview', 'CED', '01298347434', 'ced@gmail.com', 3, 'Dhaka', '', 7, '$2y$10$HS7cpDVkohrFN39yxfWwCeVM5TQ9qjbEEYP3k8lwZjQkphNKF65me', NULL, '', '', '2026-02-24 10:16:39', 1, '2026-02-24 03:47:00', '2026-02-24 04:16:39'),
(7, NULL, 'Rimi', 'Rimi', '01723745324', 'rimi@gmail.com', 1, 'dhaka', '23143245', 7, '$2y$10$0Q8rZkzw0IwFdGW24zUGX.M350th/ZpEYRPSr9xhA5PRz.D4tPg2a', NULL, '', '', '2026-03-05 14:36:27', 1, '2026-03-05 08:11:47', '2026-03-05 08:36:27');




CREATE TABLE `whatsapp_campaigns` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `target_project_id` int(11) DEFAULT NULL,
  `message_type` enum('text','image','pdf','button','brocure') DEFAULT 'text',
  `message_content` text NOT NULL,
  `schedule_time` datetime NOT NULL,
  `device` varchar(50) DEFAULT 'Auto Select',
  `status` enum('Active','Scheduled','Paused','Completed','Failed') DEFAULT 'Scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `whatsapp_messages` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `lead_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('pending','sent','delivered','failed') DEFAULT 'pending'
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
--
-- Indexes for dumped tables
--

CREATE TABLE holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    holiday_date DATE NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
);

--
-- Indexes for table `approval_requests`
--
ALTER TABLE `approval_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blogs`
--
ALTER TABLE `blogs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cameras`
--
ALTER TABLE `cameras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `camera_logs`
--
ALTER TABLE `camera_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cashout_payments`
--
ALTER TABLE `cashout_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cashout_request_id` (`cashout_request_id`),
  ADD KEY `idx_project_id` (`project_id`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `cashout_requests`
--
ALTER TABLE `cashout_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_requested_by` (`requested_by`),
  ADD KEY `fk_approved_by` (`approved_by`),
  ADD KEY `fk_rejected_by` (`rejected_by`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `communities`
--
ALTER TABLE `communities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `company_bank_accounts`
--
ALTER TABLE `company_bank_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `designations`
--
ALTER TABLE `designations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `emi_schedules`
--
ALTER TABLE `emi_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lead_booking_id` (`lead_booking_id`),
  ADD KEY `lead_payment_id` (`lead_payment_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `designation_id` (`designation_id`),
  ADD KEY `fk_employee_user` (`user_id`);

--
-- Indexes for table `employee_leaves`
--
ALTER TABLE `employee_leaves`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_leave_balances`
--
ALTER TABLE `employee_leave_balances`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `emp_attendances`
--
ALTER TABLE `emp_attendances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_date` (`employee_id`,`attendance_date`);

--
-- Indexes for table `flats`
--
ALTER TABLE `flats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `flat_purchase_rules`
--
ALTER TABLE `flat_purchase_rules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `head_notifications`
--
ALTER TABLE `head_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`),
  ADD KEY `idx_type` (`type`);

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_leads_owner` (`assigned_id`);

--
-- Indexes for table `lead_bookings`
--
ALTER TABLE `lead_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lead_id` (`lead_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `flat_id` (`flat_id`);

--
-- Indexes for table `lead_follow_ups`
--
ALTER TABLE `lead_follow_ups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lead_id` (`lead_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `flat_id` (`flat_id`);

--
-- Indexes for table `lead_payments`
--
ALTER TABLE `lead_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_payment_booking` (`booking_id`);

--
-- Indexes for table `lead_sources`
--
ALTER TABLE `lead_sources`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `management_cashin`
--
ALTER TABLE `management_cashin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_role_name` (`role_name`),
  ADD KEY `idx_transaction_date` (`transaction_date`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_role_id` (`role_id`);

--
-- Indexes for table `management_cashout`
--
ALTER TABLE `management_cashout`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_role_name` (`role_name`),
  ADD KEY `idx_transaction_date` (`transaction_date`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_role_id` (`role_id`),
  ADD KEY `idx_category_id` (`category_id`);

--
-- Indexes for table `management_categories`
--
ALTER TABLE `management_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `management_roles`
--
ALTER TABLE `management_roles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`user_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `onboard_clients`
--
ALTER TABLE `onboard_clients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payslips`
--
ALTER TABLE `payslips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `designation_id` (`designation_id`);

--
-- Indexes for table `payslip_details`
--
ALTER TABLE `payslip_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payslip_id` (`payslip_id`),
  ADD KEY `payslip_item_id` (`payslip_item_id`);

--
-- Indexes for table `payslip_items`
--
ALTER TABLE `payslip_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `location_id` (`location_id`),
  ADD KEY `community_id` (`community_id`);

--
-- Indexes for table `property_reviews`
--
ALTER TABLE `property_reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `permission_id` (`permission_id`),
  ADD KEY `role_id` (`role_id`,`permission_id`) USING BTREE;

--
-- Indexes for table `sales_targets`
--
ALTER TABLE `sales_targets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_sales_month` (`sales_user_id`,`month`),
  ADD KEY `idx_sales_month` (`sales_user_id`,`month`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `site_visits`
--
ALTER TABLE `site_visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lead_id` (`lead_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `flat_id` (`flat_id`);

--
-- Indexes for table `sliders`
--
ALTER TABLE `sliders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `fk_user_employee` (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `approval_requests`
--
ALTER TABLE `approval_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blogs`
--
ALTER TABLE `blogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cameras`
--
ALTER TABLE `cameras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `camera_logs`
--
ALTER TABLE `camera_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cashout_payments`
--
ALTER TABLE `cashout_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cashout_requests`
--
ALTER TABLE `cashout_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `communities`
--
ALTER TABLE `communities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `company_bank_accounts`
--
ALTER TABLE `company_bank_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `designations`
--
ALTER TABLE `designations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `emi_schedules`
--
ALTER TABLE `emi_schedules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employee_leaves`
--
ALTER TABLE `employee_leaves`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_leave_balances`
--
ALTER TABLE `employee_leave_balances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emp_attendances`
--
ALTER TABLE `emp_attendances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `flats`
--
ALTER TABLE `flats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `flat_purchase_rules`
--
ALTER TABLE `flat_purchase_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `head_notifications`
--
ALTER TABLE `head_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `lead_bookings`
--
ALTER TABLE `lead_bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lead_follow_ups`
--
ALTER TABLE `lead_follow_ups`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `lead_payments`
--
ALTER TABLE `lead_payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lead_sources`
--
ALTER TABLE `lead_sources`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `management_cashin`
--
ALTER TABLE `management_cashin`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `management_cashout`
--
ALTER TABLE `management_cashout`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `management_categories`
--
ALTER TABLE `management_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `management_roles`
--
ALTER TABLE `management_roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `onboard_clients`
--
ALTER TABLE `onboard_clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payslips`
--
ALTER TABLE `payslips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payslip_details`
--
ALTER TABLE `payslip_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payslip_items`
--
ALTER TABLE `payslip_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `property_reviews`
--
ALTER TABLE `property_reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `sales_targets`
--
ALTER TABLE `sales_targets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `site_visits`
--
ALTER TABLE `site_visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sliders`
--
ALTER TABLE `sliders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--
ALTER TABLE `whatsapp_campaigns`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `whatsapp_campaigns`
--
ALTER TABLE `whatsapp_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
COMMIT;



ALTER TABLE `whatsapp_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `lead_id` (`lead_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `whatsapp_messages`
--
ALTER TABLE `whatsapp_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- Constraints for table `cameras`
--
ALTER TABLE `cameras`
  ADD CONSTRAINT `cameras_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cashout_requests`
--
ALTER TABLE `cashout_requests`
  ADD CONSTRAINT `fk_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rejected_by` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `emi_schedules`
--
ALTER TABLE `emi_schedules`
  ADD CONSTRAINT `fk_emi_booking` FOREIGN KEY (`lead_booking_id`) REFERENCES `lead_bookings` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;