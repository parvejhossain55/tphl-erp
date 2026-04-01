-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 01, 2026 at 07:20 AM
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
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approval_note` text DEFAULT NULL
) ;

--
-- Dumping data for table `approval_requests`
--

INSERT INTO `approval_requests` (`id`, `department`, `request_type`, `title`, `source_table`, `source_id`, `request_amount`, `approved_amount`, `requested_days`, `approved_days`, `approved_by`, `approved_at`, `status`, `requested_by`, `approve_role`, `meta_data`, `created_at`, `updated_at`, `approval_note`) VALUES
(1, 'Accounts', 'cashout', '[Project: The Premium Nobel Court] Cashout Request: test request bill', 'cashout_requests', 1, 10000.00, NULL, NULL, NULL, 6, '2026-03-31 11:26:04', 'approved', 5, 'management', '{\"purpose\":\"test request bill\",\"payment_method\":\"cash\",\"requester_name\":\"Mr. Jisan\",\"description\":\"test bill\",\"project_id\":1,\"project_name\":\"The Premium Nobel Court\",\"office\":null}', '2026-03-31 11:24:58', '2026-03-31 11:26:04', NULL),
(2, 'HR', 'salary', 'Salary Approval', 'payslips', 1, 21100.00, NULL, NULL, NULL, NULL, NULL, 'pending', 4, 'management', '{\"employee_id\":\"1\",\"month\":\"31 March 2026\"}', '2026-03-31 11:47:19', '2026-03-31 11:47:19', NULL);

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

--
-- Dumping data for table `cashout_payments`
--

INSERT INTO `cashout_payments` (`id`, `cashout_request_id`, `office`, `project_id`, `purpose_paid_to`, `amount`, `payment_method`, `transaction_ref`, `payment_date`, `bank_name`, `bank_account_no`, `cheque_no`, `online_type`, `created_by`, `created_at`) VALUES
(1, 1, NULL, 1, 'test request bill', 10000.00, 'cash', '', '2026-03-31', NULL, NULL, NULL, NULL, 5, '2026-03-31 11:26:42');

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

--
-- Dumping data for table `cashout_requests`
--

INSERT INTO `cashout_requests` (`id`, `project_id`, `office`, `requested_by`, `purpose_paid_to`, `amount`, `approved_amount`, `due_amount`, `description`, `payment_method`, `transaction_ref`, `attachment_path`, `status`, `approved_by`, `approved_at`, `rejected_by`, `rejected_at`, `rejection_reason`, `approval_note`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 5, 'test request bill', 10000.00, NULL, 0.00, 'test bill', 'cash', '', NULL, 'approved', 6, '2026-03-31 11:26:04', NULL, NULL, NULL, NULL, '2026-03-31 11:24:58', '2026-03-31 11:27:58');

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
(1, 'The Premium Royal City', 1, 3, '2026-02-19 17:00:30', '2026-02-19 17:00:30'),
(2, 'The Premium Smart City', 1, 2, '2026-03-31 10:32:34', '2026-03-31 10:32:34');

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
(1, 'Business Development', 1, 2, '2026-02-19 16:17:29', '2026-02-19 16:17:29'),
(2, 'Human Resources', 1, 1, '2026-02-23 05:42:59', '2026-02-23 05:42:59'),
(3, 'Finance & Accounts', 1, 4, '2026-02-23 06:06:59', '2026-02-23 06:06:59'),
(4, 'Client Experience ', 1, 4, '2026-03-29 04:58:55', '2026-03-29 09:52:04'),
(5, 'Marketing, Branding, & PR', 1, 4, '2026-03-29 05:00:28', '2026-03-29 09:57:14'),
(6, 'Operations & Admin', 1, 4, '2026-03-29 05:00:53', '2026-03-29 09:57:36'),
(18, 'Legal & Land', 1, 4, '2026-03-29 12:00:58', '2026-03-29 12:01:26'),
(19, 'Management', 1, 4, '2026-03-29 12:01:49', '2026-03-29 12:01:49'),
(20, 'Purchase & Procurement', 1, 4, '2026-03-29 12:02:29', '2026-03-29 12:02:29'),
(21, 'Engineering, Construction, & Design', 1, 4, '2026-03-29 12:02:57', '2026-03-29 12:02:57'),
(22, 'IT & Development', 1, 4, '2026-03-29 12:03:15', '2026-03-29 12:03:15'),
(23, 'Internal Audit & Quality Control', 1, 4, '2026-03-29 12:03:41', '2026-03-29 12:03:41'),
(24, 'Design & Creative ', 1, 4, '2026-03-29 12:03:59', '2026-03-29 12:03:59'),
(25, 'Planning & Development', 1, 4, '2026-03-29 12:04:47', '2026-03-29 12:04:47');

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
(13, 'Executive', 2, NULL, '2026-03-29 12:04:17', '2026-03-29 12:04:17'),
(14, 'Assistant General Manager (AGM)', 2, NULL, '2026-03-29 12:04:42', '2026-03-29 12:04:42'),
(15, 'Executive', 1, NULL, '2026-03-29 12:05:07', '2026-03-29 12:05:07'),
(16, 'Intern', 1, NULL, '2026-03-29 12:05:25', '2026-03-29 12:05:25'),
(17, 'Senior Executive', 1, NULL, '2026-03-29 12:05:53', '2026-03-29 12:05:53'),
(18, 'Deputy General Manager (DGM)', 25, NULL, '2026-03-29 12:07:32', '2026-03-29 12:07:32'),
(19, 'Deputy Manager', 4, NULL, '2026-03-29 12:10:17', '2026-03-29 12:10:17'),
(20, 'Executive', 4, NULL, '2026-03-29 12:10:43', '2026-03-29 12:10:43'),
(21, 'Senior Executive', 4, NULL, '2026-03-29 12:18:02', '2026-03-29 12:18:02'),
(22, 'Junior Executive', 4, NULL, '2026-03-29 12:19:38', '2026-03-29 12:19:38'),
(23, 'Intern', 4, NULL, '2026-03-29 12:19:53', '2026-03-29 12:19:53'),
(24, 'Junior Executive', 1, NULL, '2026-03-29 12:25:10', '2026-03-29 12:25:10'),
(25, 'Deputy Manager', 1, NULL, '2026-03-29 12:26:20', '2026-03-29 12:26:20'),
(26, 'Management Trainee Officer (MTO)', 1, NULL, '2026-03-29 12:29:35', '2026-03-29 12:29:35'),
(27, 'Assistant Manager', 0, NULL, '2026-03-30 11:12:20', '2026-03-30 11:12:20'),
(28, 'Head Of Digital Marketing Team', 22, NULL, '2026-03-30 16:39:40', '2026-03-30 16:39:40');

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
  `gross_salary` float DEFAULT NULL,
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
  `employee_type` enum('permanent','contract','director','intern','remote','probation','management') DEFAULT NULL,
  `employee_status` enum('active','inactive','resigned','terminated','on_leave') DEFAULT 'active',
  `responsibilities` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `team_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `user_id`, `full_name`, `employee_id`, `username`, `email`, `father_name`, `phone`, `emergency_contact`, `qualification`, `experience`, `address`, `pass_num`, `nid`, `department_id`, `designation_id`, `dob`, `workplace`, `joining_date`, `gross_salary`, `basic_salary`, `account_holder_name`, `account_number`, `make_user`, `password`, `bank_name`, `branch_name`, `social_media1`, `social_media2`, `social_media3`, `photo`, `employee_type`, `employee_status`, `responsibilities`, `created_by`, `deleted_at`, `created_at`, `updated_at`, `team_id`) VALUES
(1, NULL, 'Fahim', '1', 'fahim', 'fahim@gmail.com', 'Forhad', '01734555783', '01734555783', 'BBA', '', 'Dhaka', '', '23784', 1, 0, NULL, 'Gulshan', '2026-01-01', 30000, 20000, 'Fahim', '7523423478', 1, '$2y$10$JphYkjE9NT3MdD93lmgv7uZ2uu4nWUubfKZHjdFvqN6rq6KTk8UIC', 'City Bank', 'Shaymoli', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-29 03:42:23', '2026-03-30 05:14:18', 0),
(2, 16, 'Ashfaque Bhuiyan Omi', '1078', 'Ashfaque Omi', 'ashfaqueomi.tphl25@gmail.com', 'Md. Alamgir Bhuiyan (Late)', '01958253303', '01729699900', 'BBA', '', 'Darul Aman Housing, Mirpur-2, Dhaka - 1206', '', '2410888859', 2, 20, '2002-01-01', 'Site Office', '2025-01-01', 20000, 10000, 'Ashfaque Bhuiyan Omi', '1341260016760', 0, '', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-29 09:40:23', '2026-03-30 11:58:30', 0),
(5, NULL, 'Bornali Haque', '1157', 'Bornali Haque', 'bornalihaque.tphl@gmail.com', 'Late Md. Sanaul Haque', '01335258340', '01673002949', '', '', 'RUAP, Uttara 18, Dhaka', '', '', 2, 13, '2000-01-01', 'Ashulia Site Office', '2026-01-01', 17000, 8500, 'BORNALI HAQUE', '1041260066908', 1, '$2y$10$eH.KzQlN0pmDgObgrOSMwuGlHSCjxghQAbXzmamc/Y6lPw89nK9aa', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-29 10:31:45', '2026-03-31 08:48:18', 0),
(6, 15, 'Md. Kowsar Ahammed', '1024', 'kowsar ahammed', 'kawsar.tphl@gmail.com', 'Md. Bellal Hossain', '01901310845', '01724423987', 'BBA', '', 'Vill: Bhubangati, Post: Biswarigacha, Thana: Dhunat, Zilla: Bogura', '', '7813436693', 1, 21, '2001-01-01', 'Site Office', '2025-01-01', 31200, 15600, '', '', 0, '', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 04:29:15', '2026-03-30 05:19:07', 0),
(7, 14, 'Saleh Ahamed', '1023', 'saleh ahmed', 'saleh.tphl@gmail.com', '', '0 1901-3650', '01816757355', 'Bsc', '', '', '', '3755061037', 1, 21, '1998-01-01', 'Site Office', '2025-01-01', 24000, 12000, '', '', 0, '', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 04:34:26', '2026-03-31 07:05:40', 2),
(8, 12, 'Sanjana Hossain Moure', '1025', 'sanjana moure', 'moure.tphl@gmail.com', 'Md.Mobarak Hossain', '01901310848', '01819203553', 'BBA', '', 'Mirpur-2 water tank, Block - F, road -3, Dhaka -1216', '', '', 1, 15, '2001-01-01', 'Site Office', '2025-01-01', 17000, 8500, '', '', 0, '', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 04:40:43', '2026-03-30 05:15:10', 0),
(9, 13, 'Md. Rabbi Hossain', '1028', 'rabbi hossain', 'rabbihossain.tphl@gmail.com', 'Md. Jahangir', '0 1901-3108', '01610874166', 'BBA', '', 'Baro Bazar, Ashulia Model Town, Savar, Dhaka.', '', '2864699331', 1, 20, '2026-01-01', 'Site Office', '2025-01-01', 17000, 8500, '', '', 0, '', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 04:56:54', '2026-03-30 05:15:44', 0),
(10, NULL, 'Mehnaz Ahmed', '1034', 'mehnaz ahmed', 'mehnaz.tphl@gmail.com', 'Miftah Uddin Ahmed', '0 1901-3650', '01732339816', 'Bsc', '', 'South chayabithi, Joydebpur, Gazipur', '', '9160336244', 1, 20, '2001-07-16', 'Corporate Office', '2025-06-03', 17000, 8500, '', '', 1, '$2y$10$/gk19RzHLf0yn6e2COmZle1S.NRl/Crc.r2O/fAjimhroA/OoO4Q2', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 05:26:05', '2026-03-30 05:26:05', 0),
(11, NULL, 'Mr. Ibrahim Khalil', '1035', 'ibrahim khalil', 'ibrahim.tphl@gmail.com', 'MD. MONTAZ ALI', '01958253319', '01557020388', 'Bachelor  of Real Estate', '', '5/9, Dakkhin Rajashon, Savar, Dhaka', '', '2805006299', 1, 20, '1988-03-05', 'Site Office', '2025-06-17', 20000, 10000, '', '', 1, '$2y$10$Az8vEYQztfIOz0whKTPObeMPsCVRaDP5OD3jXqSzt/yQ5dAnMwunO', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 05:30:21', '2026-03-30 05:30:21', 0),
(12, NULL, 'Shammi Akther', '1040', 'shammi akhter', 'shammi.tphl@gmail.com', 'Md.Shahjahan', '0 1901-3108', '01763469727', 'B.Sc in  Nutrition & Food Engineering', '', '13 no sector ,uttara.', '', '6923766643', 1, 20, '2001-01-01', 'Corporate Office', '2025-01-01', 17000, 8500, '', '', 1, '$2y$10$O9e/Nt2SkTCAwh4YgMzJtunLoKoBM/4Gqmv9hIycUzVIQKpDvsdxG', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 05:35:10', '2026-03-31 07:06:40', 1),
(13, NULL, 'Rejwan Ahmed', '1041', 'rejwan ahmad', 'rejwantphl@gmail.com', 'Md.Johur Ali', '01901365040', '01716468874', 'Bachelor of Business Administration (BBA)', '', 'Sreemangal, Moulvibazar.', '', '4660653587', 1, 21, '2002-01-01', 'Corporate Office', '2025-01-01', 24000, 12000, '', '', 1, '$2y$10$ZxUMDVmiZC3tIobTcQFzmeMZmGPxW3rViNd89KHxG8L/d52bxXbAu', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 05:40:37', '2026-03-31 07:06:58', 1),
(14, NULL, 'MD. Feroz Ahamed', '1048', 'feroz ahamed', 'feroz.tphl@gmail.com', 'MD. Gaziur Rahman', '01958253327', '01646627903', 'Bsc in Civil', '', 'Savar, Dhaka', '', '3761348154', 1, 20, '2000-08-06', 'Site Office', '2025-08-15', 17000, 8500, '', '', 1, '$2y$10$syN/VJQVFPODQvFA5ACBg.FLdlS1teYz7nryro/YJE6A.2a7mGKo6', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 05:47:14', '2026-03-30 05:47:14', 0),
(15, NULL, 'Mojammel Haque', '1049', 'mojammel haque', 'mojammel.tphl@gmail.com', 'Md. Habibur Rahman', '01958253324', '01646627903', 'BSc Honors', '', 'B-4, G-1, Sher-E-Bangla Nagar Staff Quarter, Dhaka-1207', '', '865 729 7449', 1, 21, '1993-12-20', 'ATI Model Town (Site Office)', '2025-08-16', 24000, 12000, '', '', 1, '$2y$10$zJwGI7jw8UlxRk/NeeL20OeEQoLdPfjBRbs93Ch0l9oe5yCBEWJJ6', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 05:51:15', '2026-03-30 05:51:15', 0),
(16, NULL, 'Mehedi Hasan Shuvo', '1062', 'hasan shuvo', 'shuvo.tphl@gmail.com', '', '01901365048', '', '', '', '', '', '', 1, 20, '1997-01-01', 'Site Office', '2025-01-01', 17000, 8500, '', '', 1, '$2y$10$2oZs8Bb0f1pKj23AGczXRuf2p6sFMymnDRgO4lIWPvr5XLpxzfEpa', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 05:59:32', '2026-03-31 08:21:31', 0),
(17, NULL, 'Rakib Al Hasan', '1051', 'rakibal hasan', 'rakib.tphl@gmail.com', 'Abu Bakar Siddik', '01958253321', '01723919590', 'B.sc', '', 'Daffodil smart city, Birulia savar Dhaka', '', '4654750571', 1, 20, '1999-01-01', 'Site Office', '2025-01-01', 17000, 8500, '', '', 1, '$2y$10$9Qycqk8Oy51JvZm6r1.p8OXymPDXfGbM.5hhqkkF4pCCeQmjHJcfq', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 06:43:56', '2026-03-31 07:04:56', 2),
(18, NULL, 'Md. Nazmul Khan Shihab', '1050', 'khan Shihab', 'shihab.tphl@gmail.com', 'Md. Serajul Haque', '01958253326', '01715394514', 'Bachelor  of Real Estate', '', '2450/11,East Dandabor, Palashbari, Savar cantonment -1344, Savar, Dhaka', '', '1968492643', 1, 20, '2003-12-10', 'Corporate Office', '2025-07-30', 17000, 8500, '', '', 1, '$2y$10$WXxrN0DWThf1fPmnvLNaj.dCQ9vOPq.WiWQwLDjER00tevsxemmrq', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 06:50:40', '2026-03-30 06:50:40', 0),
(19, NULL, 'Munshi Aiman Kabir', '1058', 'aiman kabir', 'aiman.tphl@gmail.com', 'Munshi Humayun Kabir', '01958253318', '01837041564', 'BBA', '', 'Savar, Dhaka', '', '6915659434', 1, 20, '2002-01-20', 'Site Office', '2025-07-29', 17000, 8500, '', '', 1, '$2y$10$K06cmrzOAagZ333rxWiP4OLRwWk753LtlPaZEvvuXl2R6bZQwJiVK', '', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 07:01:11', '2026-03-30 07:01:11', 0),
(20, NULL, 'Md. Tariqul Islam Illion', '1055', 'tariqul islam', 'illion.tphl@gmail.com', 'MD ABUL KALAM AZAD', '0 1958-2533', '01723051437', 'BBA', '', 'Ashulia Model Town, Khagan Bazar, Birulia, Savar, Dhaka', '', '1968356707', 1, 20, '2001-05-10', 'Corporate Office', '2025-09-01', 17000, 8500, '', '', 1, '$2y$10$dm0B1E/O/sjxKrtmet2LCudA0Zro/Ckf3yjWNq.fbrGvN.Je9ud5i', '', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 07:06:02', '2026-03-30 07:06:02', 0),
(21, NULL, 'Shafia Afrin Shahana', '1197', 'Shafia Afrin Shahana', 'shafia.tphl@gmail.com', '', '01335258332', '01580903690', '', '', 'Apon Bazar Samiti Market, Rayerbag, Jatrabari, Dhaka', '', '1954806822', 1, 15, NULL, 'Corporate Office', '2026-01-01', 23000, 11500, 'SHAFIA AFRIN SHAHANA', '1041260067467', 1, '$2y$10$I4jV1fxJ96DT6fAQnIU2pu.7/eC1R1.cJy0a.oxEPGEJKHRQ7edkq', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 07:56:24', '2026-03-30 08:29:48', 0),
(22, NULL, 'Abir Ahmed', '1196', 'Abir Ahmed', 'abir0.tphl@gmail.com', '', '01335258328', '01835407105', '', '', 'Poshu Hospital Para, Magura Sadar,Magura', '', '8703180417', 1, 15, '1997-12-28', 'Corporate Office', '2026-02-18', 17000, 8500, 'ABIR AHMED', '1041260067536', 1, '$2y$10$eejMPFd5ERwDC0PHtoRxUuTKwV8JqxmRINQrK5.rfPEtOvIarJ.8i', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 08:29:14', '2026-03-30 08:29:14', 0),
(23, NULL, 'Shanawaz Khan', '1195', 'Shanawaz Khan', 'shanawaz.tphl@gmail.com', '', '01335258330', '01763963205', '', '', 'House-12,Road-4,Block-J,Banasree Meradia Khilgaon Dhaka 1219', '', '5115227323', 1, 15, '2002-10-09', 'Corporate Office', '2026-02-18', 17000, 8500, 'SHANAWAZ KHAN', '1041260067875', 1, '$2y$10$rD9P.B.PqmQnB0djvOhSVe2f4l.rkmLHV4bf.oiWOXhApnrN8jT.i', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 08:42:17', '2026-03-30 08:42:17', 0),
(24, NULL, 'Md. Saiful Islam Jony', '1067', 'saiful jony', 'saif.thpl@gmail.com', 'Md.Badsha Mia Bacchu', '01958253340', '01911318557', 'BBA', '', '104/A/9 Buddhibi Road,Mohammadpur,Dhaka-1207', '', '5557674677', 1, 23, '1996-09-30', 'ATI Model Town (Site Office)', '2025-10-01', 27000, 13500, 'MD. SAIFUL ISLAM JONY', '1041260063477', 1, '$2y$10$Z8fQ2qPgsv8Ud27YlwHLI.49bjKKqmIE8RPupwEx/NzdpP0YfybmW', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 08:46:37', '2026-03-30 08:46:37', 0),
(25, NULL, 'Aranya Bhowmick Dhrubo', '1194', 'Aranya Bhowmick Dhrubo', 'dhrubo.tphl@gmail.com', '', '01335258329', '01782482200', '', '', '15 Shah-Shaheb Lane Narinda, Dhaka', '', '3315312474', 1, 20, '2001-04-06', 'Corporate Office', '2026-02-18', 17000, 8500, '', '', 1, '$2y$10$lx1vYYIursBz/Orbh19WOuopN5uG5/WhMKDKqjOJPLdVkPcGRLQZm', '', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 08:47:19', '2026-03-30 08:47:19', 0),
(26, NULL, 'Tabassum Akter Soma', '1063', 'tabassum soma', 'soma.tphl@gmail.com', 'Md. Munsur Ali', '01958253374', '01568437121', 'Bachelor  of Real Estate', '', 'Ashulia Model Town, Savar, Dhaka', '', '3758694214', 1, 23, NULL, 'Site Office', '2025-12-07', 8000, 4000, '', '', 1, '$2y$10$GXg86V45d3zrBFl2trwz.OAlB3QWCuo85YjgLONzOYRUntWtsEYym', '', '', '', '', '', '', 'contract', 'active', '', 4, NULL, '2026-03-30 08:50:26', '2026-03-30 08:50:26', 0),
(27, NULL, 'Sadat Hossain', '1193', 'Sadat Hossain', 'sadathossain11@gmail.com', '', '01335258331', '01302767082', '', '', '116, Jagannath Saha Road, Lalbagh, Dhaka-1211', '', '2852429642', 1, 15, '1997-01-01', 'Ati Model Town (Site Office)', '2026-01-01', 17000, 8500, 'SADAT HOSSAIN', '1041260067478', 1, '$2y$10$66MIG6bb/Sto95wo3Uo97OERTs127Gpq9GrrMtH4uLPcz0fJKUu0y', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 08:52:52', '2026-03-30 09:08:08', 0),
(28, NULL, 'Md. Ajizul Islam Manik', '1075', 'ajizul manik', 'manik.tphl@gmail.com', 'Late Md. Nazrul Islam', '01958253338', '01903606903', 'BBA', '', '1/B , Nondipara, Dhaka ,1219', '', '3255221362', 1, 20, '1994-01-01', 'Corporate Office', '2025-01-01', 17000, 8500, 'MD. AJIZUL ISLAM', '1041260063262', 1, '$2y$10$GK43JFERDmmRU1Ql3wQfdusKHg1QUKfyzFj9NcBu1JkGfKH/PVBmO', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 08:56:43', '2026-03-31 11:08:00', 0),
(29, NULL, 'Tasnur Aman Satu', '1082', 'aman satu', 'satu.tphl@gmail.com', 'Md. Aman Ullah', '01958253363', '01319919871', 'Bachelor  of Real Estate', '', 'kumkumari- Midha bari', '', '7361471282', 1, 20, '2003-12-02', 'Site Office', '2025-10-22', 17000, 8500, 'TANSUR AMAN SATU', '1041260062816', 1, '$2y$10$bX2dYIO2SxfBZvmOyXInqeIOYNy3cVKKKTaKCJQhz4Qei.hLmBTCW', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 09:00:39', '2026-03-30 09:00:39', 0),
(30, NULL, 'Md. Tasdikul Hasan Sikder', '1187', 'Md. Tasdikul Hasan Sikder', 'tasdik.tphl@gmail.com', '', '01335258317', '01704555164', '', '', '523 East Kazipara, Dhaka, Mirpur-1216', '', '2863855777', 1, 13, '2003-12-24', 'Corporate Office', '2026-02-07', 17000, 8500, 'MD. TASDIKUL HASAN SIKDER', '1041260066792', 1, '$2y$10$IYtgw5w6cjWl9NPeYe7dwewxOd/Y/9BxkVbkAOZVV5RiA5LRPqYn2', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 09:03:57', '2026-03-30 09:03:57', 0),
(31, NULL, 'Qazi Nadeem Javed', '1084', 'Qazi Nadeem Javed', 'nadeem.tphl@gmail.com', 'Qazi Qadir Alam', '‪0195825333', '01673089031', 'Masters', '', 'Sector 10 Road 7 uttara', '', '2350020026', 1, 25, '1989-03-05', 'Corporate Office', '2025-11-19', 35000, 17500, 'QAZI NADEEM JAVED', '1041260064718', 1, '$2y$10$dWmsFrPYBGJkCfJfBXnUjOcdXE6ZNqK9XZFKl5ko3ZteQw0nNoQsO', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 09:09:53', '2026-03-30 09:09:53', 0),
(32, NULL, 'Nayeemur Rahman', '1186', 'Nayeemur Rahman', 'nayeemtonmoytphl@gmail.com', '', '01335258318', '01711942180', '', '', 'Road No. 4, F Block, Mirpur-2', '', '1960680278', 1, 15, '2026-05-22', 'Corporate Office', '2026-02-07', 17000, 8500, 'NAYEEMUR RAHMAN', '1041260066817', 1, '$2y$10$.VVBuN5Lq1XY4Z5BKVG8WeFHCmc3o0GN3rcjUYKFXYCzJXoi6do82', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 09:12:53', '2026-03-30 09:12:53', 0),
(33, NULL, 'Mahin - Al Beruni', '1120', 'Mahin - Al Beruni', 'tphl.mahin@gmail.com', 'MD. Maimul Hoque', '01958253328', '01715155844', 'BBA', '', 'House 40/3, Varalipara, Boro Bangram, Sopura, Shah Mokhdum, Rajshahi - 6203', '', '8710606925', 1, 26, '2001-01-01', 'Site Office', '2026-01-01', 17000, 8500, 'MAHIN-AL BERUNI', '1041260065053', 1, '$2y$10$R0KUXo2kgutor4KlwwcBcOwju3DLkoyowWOYIXTuTQriN4TcjVKBq', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 09:16:00', '2026-03-31 08:10:37', 0),
(35, NULL, 'Md. Momidul Islam', '1100', 'Md. Momidul Islam', 'momidul.tphl@gmail.com', 'Md Amir Hossain', '01958253335', '01792700012', '', '', 'Nolbata, Hatiandaha, Singra, Natore', '', '6453089770', 1, 20, '1997-06-06', 'ATI Model Town (Site Office)', '2025-12-15', 17000, 8500, 'MD MOMIDUL ISLAM', '1041260064742', 1, '$2y$10$YaXdcRxYQc0gSdmkDYc7BeZtQqZdun2r.DPqDAycXrRmiv8EQ4yLS', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 09:23:02', '2026-03-30 09:23:02', 0),
(36, NULL, 'Rifat Ahsan', '1123', 'Rifat Ahsan', 'rifat.tphl@gmail.com', 'Azizul Haque Rashid', '01958583350', '01615580624', '', '', 'Khilkhet kha para', '', '4213633714', 1, 20, '1998-01-20', 'Corporate Office', '2026-01-06', 17000, 8500, 'RIFAT AHSAN', '1041260065288', 1, '$2y$10$/rt4WJ/SidkYyjQg8QNHP.xA1a.8d50zARylQ6lurhAngF69XeEIq', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 09:30:56', '2026-03-30 09:30:56', 0),
(38, NULL, 'Sudipto Azad Hriday', '1124', 'Sudipto Azad Hriday', 'sudiptoazad.tphl@gmail.com', 'Md. Abdus Samad Azad', '01901365046', '01755088763', '', '', '38, Road 3B, Mohammadia Housing Limited, Mohammadpur, Dhaka-1207', '', '9151890663', 1, 17, '1997-01-01', 'ATI Model Town (Site Office)', '2025-01-01', 24000, 12000, 'SUDIP AZAD HRIDAY', '1041260065370', 1, '$2y$10$Jsi.ZZuzQqLX0Vg.MVrRIeg57NM1gCV09L/feEf6ON0zVRJ5QnrH2', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'inactive', '', 4, NULL, '2026-03-30 09:38:33', '2026-03-30 09:39:38', 0),
(39, NULL, 'Al Mubin Shuvo', '1182', 'Al Mubin Shuvo', 'almubintphl@gmail.com', '', '01335258302', '01964464537', '', '', 'Kumarkhali,Kushtia', '', '5564288271', 1, 15, '2003-08-14', 'Ati Model Town (Site Office)', '2026-02-05', 17000, 8500, 'AL MUBIN SHUVO', '1041260066996', 1, '$2y$10$QqxgPrxtpxFXZYvnDTg6I.lNuuCvXYplWWBCMTOJB.Z.DM0bnos9u', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 09:40:37', '2026-03-30 09:40:37', 0),
(40, NULL, 'France Roy', '1184', 'France Roy', 'franceroy.tphl26@gmail.com', '', '01335258303', '', '', '', '', '', '', 1, 13, '1999-01-01', 'Gulshan', '2026-01-01', 17000, 8500, '', '', 1, '$2y$10$mUUFoDw1AKsBakNt8MbosunF.OhpBBvTK0x0gaK/JCJFM65GpNyIy', '', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 09:41:04', '2026-03-30 09:41:04', 0),
(41, NULL, 'Md. Anwar Parvez', '1181', 'Md. Anwar Parvez', 'anwar.tphl@gmail.com', '', '01335258305', '01879474485', '', '', '', '', '6004033616', 1, 13, '1999-01-25', 'Ati Model Town (Site Office)', '2026-02-05', 17000, 8500, 'MD ANWAR PARVEZ', '1041260066781', 1, '$2y$10$nf10I.VE0dkfoIo4jVnRvuWOu7WNqfn8gryV8eDSoFzJpfRwhNu0y', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 09:44:21', '2026-03-30 09:44:21', 0),
(42, NULL, 'Nafisa Anjum', '1180', 'Nafisa Anjum', 'nafisaanjum.tphl@gmail.com', '', '01335258319', '01724038041', '', '', 'Ilisha Road, Ward No-1, Bhola Pourashava.\r\nBhola Sadar, District- Bhola.', '', '8727836291', 1, 23, '2003-04-26', 'Ashulia Site Office', '2026-02-05', 5000, 2500, '', '', 1, '$2y$10$1Z5wtTHRwfXuYTG.6fg9Re/mZDPzS9RXGGiw88mkc.zbS0p/Udxdq', '', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 09:48:38', '2026-03-30 09:48:38', 0),
(43, NULL, 'Md.Minhazul Islam Sumon', '1126', 'Md. Minhazul Islam Sumon', 'sumon.tphl@gmail.com', 'Md. Dawoud hossen', '01607037251', '01797482215', '', '', 'Datta para, birulia,Savar,Dhaka', '', '6445593269', 1, 20, '1998-08-21', 'Site Office', '2026-01-11', 17000, 8500, '', '', 1, '$2y$10$q2nYfG02WCa.ctQ.1tXrP.OnXUph7SqkK5hNh7UXOQJ5Id2GWFE6i', '', '', '', '', '', '', 'permanent', 'inactive', '', 4, NULL, '2026-03-30 09:56:21', '2026-03-30 09:56:21', 0),
(44, NULL, 'Md Tarekul Islam', '1169', 'Md Tarekul Islam', 'tarekul.tphl@gmail.com', '', '01335258316', '01768856806', '', '', 'Kalai, Joypurhat', '', '7811570162', 1, 16, '2001-01-01', 'Ati Model Town (Site Office)', '2026-02-01', 8000, 4000, 'MD. TAREKUL ISLAM', '1041260067498', 1, '$2y$10$8y9fMWFX.KAphn.dH8jCY.6pZjZouf8dT41BBDXndK5Z/Dbp/3/ym', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 09:59:28', '2026-03-30 09:59:28', 0),
(45, NULL, 'Kazi Jahid Hasan', '1127', 'Kazi Jahid Hasan', 'jahid.tphl@gmail.com', 'Kazi Lutfur Rahaman', '01335258339', '01746601981', '', '', 'Shah Ali Thana, Mirpur 1, Dhaka, 1229', '', '4218961284', 1, 23, '2002-01-05', 'ATI Model Town (Site Office)', '2026-01-11', 8000, 4000, 'KAZI JAHID HASAN', '1041260067514', 1, '$2y$10$edIQfajR6szw6CFBKQeqeuzdqCTHTugjgYc6nKaPpe6r8sBBPNOzS', 'EBL', 'Gulshan- 2', '', '', '', '', 'contract', 'active', '', 4, NULL, '2026-03-30 10:03:44', '2026-03-30 10:03:44', 0),
(46, NULL, 'H M Nazmul Alam', '1167', 'H M Nazmul Alam', 'nazmulshakib.tphl@gmail.com', '', '01335258313', '01625886520', '', '', 'Tulashar, Shilpakala Academy, Aminbag, Shariatpur Sadar', '', '511-057-9504', 1, 15, '2001-07-13', 'Corporate Office', '2026-02-01', 17000, 8500, 'H M NAZMUL ALAM', '1041260066850', 1, '$2y$10$NDit2rtUrhTgQTGUxo3MWekt9mKvlxDSdxM9Od6WZXaO/j2mRijiC', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 10:05:23', '2026-03-30 10:05:23', 0),
(47, NULL, 'Mst. Tamanna Aktar Meghna', '1131', 'Mst. Tamanna Aktar Meghna', 'tamanna.tphl@gmail.com', 'Md. Mostafizur Rahman', '01958253348', '01718166746', '', '', 'Khagan, Birulia, savar, 1340', '', '7365082259', 1, 23, '2026-11-17', 'Site Office', '2026-01-15', 8000, 4000, '', '', 1, '$2y$10$cUXu.yhPyUZ9chJqTTKUoeCjsqWbkT5cXuYmajMl7BvspSJTGjyCm', 'EBL', 'Gulshan- 2', '', '', '', '', 'contract', 'active', '', 4, NULL, '2026-03-30 10:07:57', '2026-03-30 10:07:57', 0),
(48, NULL, 'Md. Rayhan Kabir Roni', '1161', 'Md. Rayhan Kabir Roni', 'rayhan.tphl@gmail.com', '', '01335-25832', '01829-06823', '', '', 'Palashbari,Gaibandha', '', '6023825901', 1, 15, '2001-12-10', 'Corporate Office', '2026-01-26', 17000, 8500, 'MD RAYHAN KABIR RONI', '1041260067208', 1, '$2y$10$bQe5ZKp8XZZFXIKrbP0k1OVW2pMOWZcmp7SrtKjpzp7IrtmLYqCYC', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 10:09:50', '2026-03-30 10:09:50', 0),
(49, NULL, 'Sayed Al Shahab', '1160', 'Sayed Al Shahab', 'Assayed1937@gamail.com', '', '01335258322', '01611847945', '', '', '\" Village/Street: Rouha, Post Office:Niamatpur - 2310, Karimganj, Kishoreganj\"', '', '1030789414', 1, 15, '2000-02-18', 'Ati Model Town (Site Office)', '2026-01-26', 17000, 8500, 'SAYED AL SAHAB', '1041260067558', 1, '$2y$10$yCMUC7MWGJ4sNZ/xdcCjB.4cvKf5J2cf9ecvp3fnxTWz1azqF29yC', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 10:16:52', '2026-03-30 10:16:52', 0),
(50, NULL, 'Rafiqul Islam', '1132', 'Rafiqul Islam', 'rafiqul.tphl@gmail.com', 'Khayer', '01958253345', '01766919594', '', '', 'Khalpar,Tarabo,Rupganj,Narayanganj, post code:1460', '', '5563671634', 1, 20, '2000-09-01', 'ATI Model Town (Site Office)', '2026-01-15', 17000, 8500, 'RAFIQUL ISLAM', '1041260065224', 1, '$2y$10$KCfFJ1KFGvHUdeIGaii76.3ifuPZfLldfH/iXtA51iqACzGwMAbEa', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 10:20:00', '2026-03-30 10:20:00', 0),
(51, NULL, 'Md. Mahabubur Rahman Mehedee', '1158', 'Md. Mahabubur Rahman Mehedee', 'mehedee.tphl@gmail.com', '', '01335258307', '01922674249', '', '', '', '', '4665275352', 1, 21, '2001-11-07', 'Ati Model Town (Site Office)', '2026-01-25', 24000, 12000, 'MD. MAHABUBUR RAHMAN MEHEDEE', '1041260068388', 1, '$2y$10$zskY9SmTBZ/WkjwTWR.zTOH/jONrfSbrC/rmGXqXrPf1RJsjJY1Ge', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 10:24:57', '2026-03-30 10:24:57', 0),
(52, NULL, 'Md. Sakib Al Hasan', '1133', 'Md. Sakib Al Hasan', 'sakibhasan.tphl@gmail.com', 'Abdul Gaffar', '01335258325', '01993241381', '', '', 'Changone, Ahulia, Dhaka 1341', '', '8268549592', 1, 17, '2002-09-20', 'ATI Model Town (Site Office)', '2026-01-17', 24000, 12000, 'MD SAKIB AL HASAN', '1041260065202', 1, '$2y$10$cIING7bVqG7T6LRoDUJhyOIiBVKghhilZyp59OoNaZljMARRnBk7y', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 10:25:00', '2026-03-30 10:25:00', 0),
(53, NULL, 'Md Asim Akram', '1156', 'Md Asim Akram', 'akram.tphl@gmail.com', '', '01335258308', '01737896035', '', '', '', '', '2412933208', 1, 15, '2001-11-26', 'Ati Model Town (Site Office)', '2026-01-24', 17000, 8500, 'MD. ASIM AKRAM', '1041260066839', 1, '$2y$10$bR96JB1JNRBpe2mOX2.OZedyfGx4X710sgklzknJE8cw1GekBB6Xy', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 10:31:00', '2026-03-30 10:31:00', 0),
(54, NULL, 'Rakib Hasan Rubai', '1135', 'Rakib Hasan Rubai', 'rubai.tphl@gmail.com', 'Abdul Gaffar', '01335258309', '01721289043', '', '', 'Nobabgonj Bazar, Lalbagh , Dhaka -1205', '', '8268549592', 1, 13, '2003-01-01', 'ATI Model Town (Site Office)', '2026-01-01', 17000, 8500, 'RAKIB HASAN RUBAI', '1041260065246', 1, '$2y$10$K9UIWPodonWCg0Nfg7ti/.53KhSCQbw52Uw0KQEB3tM4z46JXz60m', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 10:31:59', '2026-03-31 10:29:22', 0),
(55, NULL, 'Md. Rawnak Hassan Seyam', '1154', 'Md. Rawnak Hassan Seyam', 'rawnak.tphl@gmail.com', '', '01335-25832', '01719141196', '', '', '', '', '2414491106', 1, 15, '2001-11-19', 'Corporate Office', '2026-01-22', 17000, 8500, 'MD. RAWNAK HASSAN SEYAM', '1041260066768', 1, '$2y$10$Rcurnj2w71MPIBLZ5zT3Ke754CHQUQlCREszsc6kFlGOI6H59WugG', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 10:35:13', '2026-03-30 10:35:13', 0),
(56, NULL, 'Zannatul Ferdous', '1136', 'Zannatul Ferdous', 'zannatul.tphl@gmail.com', 'Toaz uddin', '01335258315', '01674201198', '', '', 'Ghoshbagh,Ashulia, Dhaka', '', '4672259688', 1, 20, '2000-12-02', 'Site Office', '2025-12-28', 17000, 85000, 'ZANNATUL FERDOUS', '1041260065235', 1, '$2y$10$a8Z3vRtCJwTssChroQmP8.b9tvSrS0EaG.fb525TLXTcfz0JH.T32', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 10:35:17', '2026-03-30 10:35:17', 0),
(57, NULL, 'Mst. Mahzabin Reza', '1139', 'MST. MAHZABIN REZA', 'mahzabin.tphl@gmail.com', 'MD Rasel Reza', '01335258311', '01926865061', '', '', '', '', '5114927907', 1, 23, '2001-09-25', 'ATI Model Town (Site Office)', '2026-01-20', 8000, 4000, 'MST. MAHZABIN REZA', '1041260067489', 1, '$2y$10$AjaQjDS0XRUJJyQlkmwfqe6eY/FkrXSgS6J5OILgtdTF4ta.dK9ga', 'EBL', 'Gulshan- 2', '', '', '', '', 'contract', 'active', '', 4, NULL, '2026-03-30 10:40:34', '2026-03-30 10:40:34', 0),
(58, NULL, 'Md. Ali Haider Abdullah', '1153', 'Md. Ali Haider Abdullah', 'tphl.alihaider@gmail.com', '', '0 1958-2533', '01837234709', '', '', '', '', '1028428793', 1, 17, '1999-09-29', 'Ashulia Site Office', '2026-01-22', 25000, 12500, '', '', 1, '$2y$10$wVPZJu/YTJjJPVZIpz8YdeWRAszLKM8JqY9tpCtjNutUgq8/gY4vq', '', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 10:41:19', '2026-03-30 10:41:19', 0),
(59, NULL, 'Tanvir Hossain Jian', '1140', 'Tanvir Hossain Jian', 'tanvir.tphl@gmail.com', 'Alomgir Hossain', '01335258314', '01712231068', '', '', 'Uttor badda, Abdullah bag masjid', '', '4205166475', 1, 20, '1999-10-13', 'Corporate Office', '2026-01-20', 17000, 8500, 'Md.Tanvir Hossain', '1161260056064', 1, '$2y$10$PT5Uca3qXiPMcZc76OZPb.G9qWf0lKf3qIMmb.B49eZVvByRHe0ly', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 10:44:55', '2026-03-30 10:44:55', 0),
(60, NULL, 'Ahnaf Adil Roxy', '1151', 'Ahnaf Adil Roxy', 'roxy.tphl@gmail.com', '', '01335-25832', '01816729135', '', '', '', '', '4213421763', 1, 13, '2002-09-20', 'Corporate Office', '2026-01-20', 17000, 8500, 'AHNAF ADIL ROXY', '1041260066872', 1, '$2y$10$v0ZeRyQOrjRMflJjY1out.mC75ZbRpYS47E6okHQJ1QvcyCBwA9zy', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 10:45:59', '2026-03-30 10:45:59', 0),
(61, NULL, 'Md.Tamim', '1143', 'Md.Tamim', 'tamim.tphl@gmail.com', 'Md.Mizanur Rahman', '01308212749', '01410800069', '', '', 'Dattapara, Birulia,Savar, Dhaka 1216', '', '', 1, 23, '2001-01-19', 'Site Office', '2025-12-29', 5000, 2500, '', '', 1, '$2y$10$iIMh11dO/cPEeOTABrcB/eec3cs0eA1lQ3arTL6KuVUb7Ivt9P2Ce', '', '', '', '', '', '', 'contract', 'active', '', 4, NULL, '2026-03-30 10:49:04', '2026-03-30 10:49:04', 0),
(62, NULL, 'Md. Abul Hasan Hasib', '1150', 'Md. Abul Hasan Hasib', 'hasib.tphl@gmail.com', '', '01335258320', '', '', '', '', '', '4654241225', 1, 21, '1999-05-11', 'Ati Model Town (Site Office)', '2026-01-20', 25000, 12500, 'MD. ABUL HASAN HASIB', '1041260067569', 0, '', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 10:49:23', '2026-03-30 10:49:23', 0),
(63, NULL, 'Akhoy kumer Nibir Roy', '1149', 'Akhoy kumer Nibir Roy', 'akhoy.tphl@gmail.com', '', '01335258301', '01720673414', '', '', '', '', '6913904170', 1, 15, '2001-09-13', 'Corporate Office', '2026-01-20', 17000, 8500, 'AKHOY KUMER NIBIR ROY', '1041260067423', 1, '$2y$10$M7PNzLRweWOUZUWd6iD/oeAYsF7HSGappmPdqY7wvG42BAw2GAduq', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 10:54:37', '2026-03-30 10:54:37', 0),
(64, NULL, 'Md. Amanullah Aman', '1145', 'Md. Amanullah Aman', 'aman.tphl@gmail.com', 'MD. ASHRAF ALI', '01335258306', '01711129544', '', '', 'South Bhandaria-8550, Bhandaria, Pirojpur', '', '6010834478', 1, 20, '1999-01-22', 'Site Office', '2026-01-01', 17000, 8500, 'MD. AMANULLAH AMAN', '1041260067547', 1, '$2y$10$PBHeLM63oT63W5iSr14tHuJVpPZHSsrY29OOD.dUHFvvq2E62oVzC', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 10:56:23', '2026-03-30 10:56:23', 0),
(65, NULL, 'Abu Naym', '1146', 'Abu Naym', 'naymtphl@gmail.com', 'MD.Dulal Chaprasy', '01335258312', '01712343549', '', '', 'Ati-1312, Keraniganj, Dhaka', '', '4204089967', 1, 20, '1999-06-28', 'ATI Model Town (Site Office)', '2026-01-20', 17000, 8500, 'ABU NAYM', '1041260068038', 1, '$2y$10$xoRCNgUALmHcwCio.1FhMOgV.J/N7bbKSyABX.hQxJst1f3qprA6u', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 10:59:42', '2026-03-30 10:59:42', 0),
(66, NULL, 'Md. Sakib Inam', '1148', 'Md. Sakib Inam', 'sakibinam.tphl@gmail.com', '', '01335258310', '01772441727', '', '', '', '', '1962602841', 1, 16, '2003-01-22', 'Ati Model Town (Site Office)', '2026-01-20', 5000, 2500, '', '', 1, '$2y$10$6Wu0Y3LRAw4NpoRsrcAFlOK5Gg36RlUcw/fUFzR40dyNAW2ecGQnq', '', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 11:00:09', '2026-03-30 11:00:09', 0),
(67, NULL, 'Md Bakhtiar Hasan', '1147', 'Md Bakhtiar Hasan', 'bakhtiar.tphl@gmail.com', '', '01335258300', '01736627147', '', '', '', '', '4602140743', 1, 13, '1996-12-01', 'Ati Model Town (Site Office)', '2026-01-20', 17000, 8500, '', '', 1, '$2y$10$n85C5ClROdqgoLGgj9/J1O7bsgZR4ZCQwAniy0vQFkSHai8NPLHXG', '', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 11:10:48', '2026-03-30 11:10:48', 0),
(68, NULL, 'Md Lutfor Rahaman', '1183', 'Md Lutfor Rahaman', 'lutfor.tphl@gmail.com', '', '01335258334', '01913375972', '', '', 'Vill: Asapur, PO: Phancherkanda, Thana: Ghior, Dist: Manikganj, Dhaka', '', '7350928797', 3, 27, '1990-11-11', 'Ashulia Site Office', '2026-02-20', 40000, 20000, 'MD. LUTFOR RAHAMAN', '1041260066770', 1, '$2y$10$/pf0DzZLyuTXUl9vY3q0fuzaeIZ2F.gwO6iQjFGAaJ560oavPPyky', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 11:17:42', '2026-03-30 11:17:42', 0),
(69, NULL, 'Dipto Kumar Roy', '1021', 'Dipto Kumar Roy', 'dipto.tphl@gmail.com', 'Dulal Chandra Roy', '01958253358', '01765904892', '', '', 'Khagan,Ashulia,Saver Dhaka', '', '2851653333', 3, 20, '1996-04-14', 'Site Office', '2025-09-18', 17000, 8500, 'DIPTO KUMAR ROY', '1041260063670', 1, '$2y$10$haij5JtC8w5RdSd1GW60G.QfOtOp0hPe4JsVQMuvnD3.VIjwEWT.i', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 11:19:42', '2026-03-30 11:19:42', 0),
(70, NULL, 'B. M Shaharear Hasan', '1170', 'B. M Shaharear Hasan', 'shaharear.tphl@gmail.com', '', '01901365041', '01914206266', '', '', 'Korotoa road, latifpur kolony, Bogra', '', '7318135568', 3, 27, '1994-01-01', 'Ashulia Site Office', '2026-01-01', 45000, 22500, 'B.M SHAHAREAR HASAN', '1041260066883', 1, '$2y$10$0B.avL0U76y5XCeZLE4I8eEJyJwf5jiaL/k/esXDofYhpT08NnrAy', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 11:22:17', '2026-03-31 09:55:47', 0),
(71, NULL, 'MD. Sakhawat Hossain Emon', '1122', 'MD. Sakhawat Hossain Emon', 'emon.tphl@gmail.com', 'Md. Ismail Hossain Emon', '01335258335', '01314010972', '', '', 'Block-A, Lane-6, Mirpur-6, Dhaka 1216', '', '1962405062', 3, 23, '2001-05-28', 'Site Office', '2026-01-04', 8000, 4000, '', '', 1, '$2y$10$yRioRoy7IFtTpdLAKr6QFuTsqng0pIdDnYycVcXxZ6r1CF5juG45W', '', '', '', '', '', '', 'contract', 'active', '', 4, NULL, '2026-03-30 11:22:57', '2026-03-30 11:22:57', 0),
(72, NULL, 'MD. Zamiul Islam', '1166', 'MD. Zamiul Islam', 'zamiul.tphl@gmail.com', '', '01335258338', '01822650468', '', '', '130/6/A Baganbari,  Madatek, Basabo, Dhaka -1214.', '', '5086466165', 3, 17, '1993-11-09', 'Ashulia Site Office', '2026-02-01', 28000, 14000, 'MD. JAMIUL ISLAM', '1041260066894', 1, '$2y$10$RbSxKjCb/hYfFm37QJHu7.6r5Ycfo74lefP1sQvrboL9mlFPacGE6', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 11:25:48', '2026-03-30 11:25:48', 0),
(73, NULL, 'MD. Ahadul Islam', '1155', 'MD. Ahadul Islam', 'asif.tphl@gmail.com', 'MD Nurul Islam', '01314010972', '01761-69676', '', '', 'Amula Dahabharatta, Nikrail, Bhuapur.', '', '3314775515', 3, 23, '2002-01-01', 'Site Office', '2026-01-20', 5000, 2500, '', '', 1, '$2y$10$8McF5NcYB.R/29fcKKiRZeGrtjih5dLNLjBGu4LXeHUAsIz1BQe8G', '', '', '', '', '', '', 'contract', 'active', '', 4, NULL, '2026-03-30 11:26:09', '2026-03-30 11:26:09', 0),
(74, NULL, 'Sadman Islam Sakib', '1061', 'Sadman Islam Sakib', 'sakib.tphl@gmail.com', 'Saiful Islam', '01958253344', '01912102775', '', '', 'H-12, Kusumbag Residential Area, North Bisil  Road, Mirpur-1,Dhaka-1216', '', '7817318327', 1, 21, '2025-01-01', 'Corporate Office', '2025-01-01', 24000, 12000, 'S.M Sadman Islam Sakib', '1041260062827', 1, '$2y$10$EoGOv9SYmo5nySrmVmKn7e8GXHkr4lfKnswOVruz5TcpD/PFjCxty', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 11:33:39', '2026-03-31 10:03:46', 1),
(75, NULL, 'Mahaananda Kumar Saha', '1185', 'Mahaananda Kumar Saha', 'mahaanandakumarsaha@gmail.com', '', '01335258327', '01765904892', '', '', 'Faridpur, sadarpur,hatkrishnapur', '', '5506251882', 4, 22, '1996-07-21', 'Ashulia Site Office', '2026-02-05', 16000, 8000, '', '', 1, '$2y$10$AZDF78LnqVjp6.dGcENEYux6zhAmmSvwv2LyaNPjnIHEzncBt4ugS', '', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 11:34:33', '2026-03-30 11:34:33', 0),
(76, 91, 'Rubina Islam', '1113', 'Rubina Islam', 'rubinatphl49@gmail.com', '', '01958253349', '', '', '', '', '', '1900939719', 4, 15, '1992-01-01', 'Ashulia Site Office', '2025-01-01', 20000, 10000, 'RUBINA ISLAM', '1041260065326', 0, '', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 11:38:41', '2026-03-30 11:44:43', 0),
(79, NULL, 'Moonmoon Akter Oishy', '1074', 'Moonmoon Akter Oishy', 'oishytphl@gmail.com', 'MD. Mukter Hossain', '01958253355', '01403720777', '', '', 'Road No -1,  B - 45 Bank Town,  Savar', '', '1047316906', 4, 24, '2001-10-19', 'Site Office', '2025-09-30', 10000, 5000, 'MOONMOON AKTER OISHY', '1041260063659', 1, '$2y$10$wO3G5peIbD4GDSce1ENfX.lk6LgFzygmt09TCwXkn4gjnCrtlq6R2', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 11:43:09', '2026-03-30 11:43:09', 0),
(80, NULL, 'Afrin Jahan Anika', '1077', 'Afrin Jahan Anika', 'anika.tphl@gmail.com', '', '01958253335', '01623273346', '', '', '39/b senpara porbota mirpur 10', '', '1032936708', 4, 13, '2002-10-23', 'Ashulia Site Office', '2025-10-03', 17000, 8500, 'AFRIN JAHAN ANIKA', '1041260062962', 1, '$2y$10$QpRMiyOu97GfYgByNJg5feF7W8pjRyi0FJsby5n9KhGzmnTufIe1K', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-30 11:52:36', '2026-03-30 11:52:36', 0),
(81, NULL, 'Riya', '12', 'Riya', 'riya@gmail.com', '', '01778234546', '', '', '', '', '', '', 1, 28, NULL, 'Corporate Office', NULL, 30000, 20000, '', '', 1, '$2y$10$LYrTIE9qefvdeAgDUJeRpeUd4r6eqbpr3Ej73m.3wzZBVbGSs5Qdu', '', '', '', '', '', '', 'permanent', 'active', '', 2, NULL, '2026-03-31 06:40:46', '2026-03-31 08:23:09', 1),
(82, NULL, 'S.A Arnob', '1054', 'S.A Arnob', 'arnob.tphl@gmail.com', '', '01958253347', '01767131014', 'BBA', '', 'Dottopara, Asuliya, Dhaka', '', '7373986467', 1, 20, '2001-11-26', 'Site Office', '2025-09-01', 17000, 8500, 'S. A. Arnob', '1041260062805', 1, '$2y$10$YXYst2kmkBg3RSUGdpIaY.w5wTksKr8HU4gXCavzVdrzy/Wf.zzdW', 'EBL', 'Gulshan- 2', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-31 08:18:16', '2026-03-31 08:18:16', 2),
(83, NULL, 'Md. Mamunur Rahman', '1070', 'Md. Mamunur Rahman', 'mamunur.tphl@gmail.com', '', '01958253342', '01913986617', '', '', '', '', '1947418776', 4, 19, '1992-07-19', 'Site Office', '2025-10-01', 35000, 17500, 'MD MAMUNUR RAHMAN', '1041260062984', 1, '$2y$10$0Fawr8b89qpVPyUj/KqqgumIXVR3lCtzkWG2vaMGiLjTKIEjQSI8O', 'EBL', '', '', '', '', '', 'permanent', 'active', '', 4, NULL, '2026-03-31 08:58:36', '2026-03-31 08:58:36', 2);

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

--
-- Dumping data for table `employee_leave_balances`
--

INSERT INTO `employee_leave_balances` (`id`, `employee_id`, `leave_type_id`, `remaining_days`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 20, '2026-03-29 09:40:23', '2026-03-29 09:40:23'),
(2, 2, 2, 12, '2026-03-29 09:40:23', '2026-03-29 09:40:23'),
(3, 3, 1, 20, '2026-03-29 10:23:39', '2026-03-29 10:23:39'),
(4, 3, 2, 12, '2026-03-29 10:23:39', '2026-03-29 10:23:39'),
(5, 4, 1, 20, '2026-03-29 10:28:20', '2026-03-29 10:28:20'),
(6, 4, 2, 12, '2026-03-29 10:28:20', '2026-03-29 10:28:20'),
(7, 5, 1, 20, '2026-03-29 10:31:45', '2026-03-29 10:31:45'),
(8, 5, 2, 12, '2026-03-29 10:31:45', '2026-03-29 10:31:45'),
(9, 6, 1, 20, '2026-03-30 04:29:15', '2026-03-30 04:29:15'),
(10, 6, 2, 12, '2026-03-30 04:29:15', '2026-03-30 04:29:15'),
(11, 7, 1, 20, '2026-03-30 04:34:26', '2026-03-30 04:34:26'),
(12, 7, 2, 12, '2026-03-30 04:34:26', '2026-03-30 04:34:26'),
(13, 8, 1, 20, '2026-03-30 04:40:43', '2026-03-30 04:40:43'),
(14, 8, 2, 12, '2026-03-30 04:40:43', '2026-03-30 04:40:43'),
(15, 9, 1, 20, '2026-03-30 04:56:54', '2026-03-30 04:56:54'),
(16, 9, 2, 12, '2026-03-30 04:56:54', '2026-03-30 04:56:54'),
(17, 10, 1, 20, '2026-03-30 05:26:05', '2026-03-30 05:26:05'),
(18, 10, 2, 12, '2026-03-30 05:26:05', '2026-03-30 05:26:05'),
(19, 11, 1, 20, '2026-03-30 05:30:21', '2026-03-30 05:30:21'),
(20, 11, 2, 12, '2026-03-30 05:30:21', '2026-03-30 05:30:21'),
(21, 12, 1, 20, '2026-03-30 05:35:10', '2026-03-30 05:35:10'),
(22, 12, 2, 12, '2026-03-30 05:35:10', '2026-03-30 05:35:10'),
(23, 13, 1, 20, '2026-03-30 05:40:37', '2026-03-30 05:40:37'),
(24, 13, 2, 12, '2026-03-30 05:40:37', '2026-03-30 05:40:37'),
(25, 14, 1, 20, '2026-03-30 05:47:14', '2026-03-30 05:47:14'),
(26, 14, 2, 12, '2026-03-30 05:47:14', '2026-03-30 05:47:14'),
(27, 15, 1, 20, '2026-03-30 05:51:15', '2026-03-30 05:51:15'),
(28, 15, 2, 12, '2026-03-30 05:51:15', '2026-03-30 05:51:15'),
(29, 16, 1, 20, '2026-03-30 05:59:32', '2026-03-30 05:59:32'),
(30, 16, 2, 12, '2026-03-30 05:59:32', '2026-03-30 05:59:32'),
(31, 17, 1, 20, '2026-03-30 06:43:56', '2026-03-30 06:43:56'),
(32, 17, 2, 12, '2026-03-30 06:43:56', '2026-03-30 06:43:56'),
(33, 18, 1, 20, '2026-03-30 06:50:40', '2026-03-30 06:50:40'),
(34, 18, 2, 12, '2026-03-30 06:50:40', '2026-03-30 06:50:40'),
(35, 19, 1, 20, '2026-03-30 07:01:11', '2026-03-30 07:01:11'),
(36, 19, 2, 12, '2026-03-30 07:01:11', '2026-03-30 07:01:11'),
(37, 20, 1, 20, '2026-03-30 07:06:02', '2026-03-30 07:06:02'),
(38, 20, 2, 12, '2026-03-30 07:06:02', '2026-03-30 07:06:02'),
(39, 21, 1, 20, '2026-03-30 07:56:24', '2026-03-30 07:56:24'),
(40, 21, 2, 12, '2026-03-30 07:56:24', '2026-03-30 07:56:24'),
(41, 22, 1, 20, '2026-03-30 08:29:14', '2026-03-30 08:29:14'),
(42, 22, 2, 12, '2026-03-30 08:29:14', '2026-03-30 08:29:14'),
(43, 23, 1, 20, '2026-03-30 08:42:17', '2026-03-30 08:42:17'),
(44, 23, 2, 12, '2026-03-30 08:42:17', '2026-03-30 08:42:17'),
(45, 24, 1, 20, '2026-03-30 08:46:37', '2026-03-30 08:46:37'),
(46, 24, 2, 12, '2026-03-30 08:46:37', '2026-03-30 08:46:37'),
(47, 25, 1, 20, '2026-03-30 08:47:19', '2026-03-30 08:47:19'),
(48, 25, 2, 12, '2026-03-30 08:47:19', '2026-03-30 08:47:19'),
(49, 26, 1, 20, '2026-03-30 08:50:26', '2026-03-30 08:50:26'),
(50, 26, 2, 12, '2026-03-30 08:50:26', '2026-03-30 08:50:26'),
(51, 27, 1, 20, '2026-03-30 08:52:52', '2026-03-30 08:52:52'),
(52, 27, 2, 12, '2026-03-30 08:52:52', '2026-03-30 08:52:52'),
(53, 28, 1, 20, '2026-03-30 08:56:43', '2026-03-30 08:56:43'),
(54, 28, 2, 12, '2026-03-30 08:56:43', '2026-03-30 08:56:43'),
(55, 29, 1, 20, '2026-03-30 09:00:39', '2026-03-30 09:00:39'),
(56, 29, 2, 12, '2026-03-30 09:00:39', '2026-03-30 09:00:39'),
(57, 30, 1, 20, '2026-03-30 09:03:57', '2026-03-30 09:03:57'),
(58, 30, 2, 12, '2026-03-30 09:03:57', '2026-03-30 09:03:57'),
(59, 31, 1, 20, '2026-03-30 09:09:53', '2026-03-30 09:09:53'),
(60, 31, 2, 12, '2026-03-30 09:09:53', '2026-03-30 09:09:53'),
(61, 32, 1, 20, '2026-03-30 09:12:53', '2026-03-30 09:12:53'),
(62, 32, 2, 12, '2026-03-30 09:12:53', '2026-03-30 09:12:53'),
(63, 33, 1, 20, '2026-03-30 09:16:00', '2026-03-30 09:16:00'),
(64, 33, 2, 12, '2026-03-30 09:16:00', '2026-03-30 09:16:00'),
(65, 34, 1, 20, '2026-03-30 09:19:00', '2026-03-30 09:19:00'),
(66, 34, 2, 12, '2026-03-30 09:19:00', '2026-03-30 09:19:00'),
(67, 35, 1, 20, '2026-03-30 09:23:02', '2026-03-30 09:23:02'),
(68, 35, 2, 12, '2026-03-30 09:23:02', '2026-03-30 09:23:02'),
(69, 36, 1, 20, '2026-03-30 09:30:56', '2026-03-30 09:30:56'),
(70, 36, 2, 12, '2026-03-30 09:30:56', '2026-03-30 09:30:56'),
(71, 37, 1, 20, '2026-03-30 09:38:06', '2026-03-30 09:38:06'),
(72, 37, 2, 12, '2026-03-30 09:38:06', '2026-03-30 09:38:06'),
(73, 38, 1, 20, '2026-03-30 09:38:33', '2026-03-30 09:38:33'),
(74, 38, 2, 12, '2026-03-30 09:38:33', '2026-03-30 09:38:33'),
(75, 39, 1, 20, '2026-03-30 09:40:37', '2026-03-30 09:40:37'),
(76, 39, 2, 12, '2026-03-30 09:40:37', '2026-03-30 09:40:37'),
(77, 40, 1, 20, '2026-03-30 09:41:04', '2026-03-30 09:41:04'),
(78, 40, 2, 12, '2026-03-30 09:41:04', '2026-03-30 09:41:04'),
(79, 41, 1, 20, '2026-03-30 09:44:21', '2026-03-30 09:44:21'),
(80, 41, 2, 12, '2026-03-30 09:44:21', '2026-03-30 09:44:21'),
(81, 42, 1, 20, '2026-03-30 09:48:38', '2026-03-30 09:48:38'),
(82, 42, 2, 12, '2026-03-30 09:48:38', '2026-03-30 09:48:38'),
(83, 43, 1, 20, '2026-03-30 09:56:21', '2026-03-30 09:56:21'),
(84, 43, 2, 12, '2026-03-30 09:56:21', '2026-03-30 09:56:21'),
(85, 44, 1, 20, '2026-03-30 09:59:28', '2026-03-30 09:59:28'),
(86, 44, 2, 12, '2026-03-30 09:59:28', '2026-03-30 09:59:28'),
(87, 45, 1, 20, '2026-03-30 10:03:44', '2026-03-30 10:03:44'),
(88, 45, 2, 12, '2026-03-30 10:03:44', '2026-03-30 10:03:44'),
(89, 46, 1, 20, '2026-03-30 10:05:23', '2026-03-30 10:05:23'),
(90, 46, 2, 12, '2026-03-30 10:05:23', '2026-03-30 10:05:23'),
(91, 47, 1, 20, '2026-03-30 10:07:57', '2026-03-30 10:07:57'),
(92, 47, 2, 12, '2026-03-30 10:07:57', '2026-03-30 10:07:57'),
(93, 48, 1, 20, '2026-03-30 10:09:50', '2026-03-30 10:09:50'),
(94, 48, 2, 12, '2026-03-30 10:09:50', '2026-03-30 10:09:50'),
(95, 49, 1, 20, '2026-03-30 10:16:53', '2026-03-30 10:16:53'),
(96, 49, 2, 12, '2026-03-30 10:16:53', '2026-03-30 10:16:53'),
(97, 50, 1, 20, '2026-03-30 10:20:00', '2026-03-30 10:20:00'),
(98, 50, 2, 12, '2026-03-30 10:20:00', '2026-03-30 10:20:00'),
(99, 51, 1, 20, '2026-03-30 10:24:57', '2026-03-30 10:24:57'),
(100, 51, 2, 12, '2026-03-30 10:24:57', '2026-03-30 10:24:57'),
(101, 52, 1, 20, '2026-03-30 10:25:00', '2026-03-30 10:25:00'),
(102, 52, 2, 12, '2026-03-30 10:25:00', '2026-03-30 10:25:00'),
(103, 53, 1, 20, '2026-03-30 10:31:00', '2026-03-30 10:31:00'),
(104, 53, 2, 12, '2026-03-30 10:31:00', '2026-03-30 10:31:00'),
(105, 54, 1, 20, '2026-03-30 10:31:59', '2026-03-30 10:31:59'),
(106, 54, 2, 12, '2026-03-30 10:31:59', '2026-03-30 10:31:59'),
(107, 55, 1, 20, '2026-03-30 10:35:13', '2026-03-30 10:35:13'),
(108, 55, 2, 12, '2026-03-30 10:35:13', '2026-03-30 10:35:13'),
(109, 56, 1, 20, '2026-03-30 10:35:17', '2026-03-30 10:35:17'),
(110, 56, 2, 12, '2026-03-30 10:35:17', '2026-03-30 10:35:17'),
(111, 57, 1, 20, '2026-03-30 10:40:34', '2026-03-30 10:40:34'),
(112, 57, 2, 12, '2026-03-30 10:40:34', '2026-03-30 10:40:34'),
(113, 58, 1, 20, '2026-03-30 10:41:19', '2026-03-30 10:41:19'),
(114, 58, 2, 12, '2026-03-30 10:41:19', '2026-03-30 10:41:19'),
(115, 59, 1, 20, '2026-03-30 10:44:55', '2026-03-30 10:44:55'),
(116, 59, 2, 12, '2026-03-30 10:44:55', '2026-03-30 10:44:55'),
(117, 60, 1, 20, '2026-03-30 10:45:59', '2026-03-30 10:45:59'),
(118, 60, 2, 12, '2026-03-30 10:45:59', '2026-03-30 10:45:59'),
(119, 61, 1, 20, '2026-03-30 10:49:04', '2026-03-30 10:49:04'),
(120, 61, 2, 12, '2026-03-30 10:49:04', '2026-03-30 10:49:04'),
(121, 62, 1, 20, '2026-03-30 10:49:23', '2026-03-30 10:49:23'),
(122, 62, 2, 12, '2026-03-30 10:49:23', '2026-03-30 10:49:23'),
(123, 63, 1, 20, '2026-03-30 10:54:37', '2026-03-30 10:54:37'),
(124, 63, 2, 12, '2026-03-30 10:54:37', '2026-03-30 10:54:37'),
(125, 64, 1, 20, '2026-03-30 10:56:23', '2026-03-30 10:56:23'),
(126, 64, 2, 12, '2026-03-30 10:56:23', '2026-03-30 10:56:23'),
(127, 65, 1, 20, '2026-03-30 10:59:42', '2026-03-30 10:59:42'),
(128, 65, 2, 12, '2026-03-30 10:59:42', '2026-03-30 10:59:42'),
(129, 66, 1, 20, '2026-03-30 11:00:09', '2026-03-30 11:00:09'),
(130, 66, 2, 12, '2026-03-30 11:00:09', '2026-03-30 11:00:09'),
(131, 67, 1, 20, '2026-03-30 11:10:48', '2026-03-30 11:10:48'),
(132, 67, 2, 12, '2026-03-30 11:10:48', '2026-03-30 11:10:48'),
(133, 68, 1, 20, '2026-03-30 11:17:42', '2026-03-30 11:17:42'),
(134, 68, 2, 12, '2026-03-30 11:17:42', '2026-03-30 11:17:42'),
(135, 69, 1, 20, '2026-03-30 11:19:42', '2026-03-30 11:19:42'),
(136, 69, 2, 12, '2026-03-30 11:19:42', '2026-03-30 11:19:42'),
(137, 70, 1, 20, '2026-03-30 11:22:17', '2026-03-30 11:22:17'),
(138, 70, 2, 12, '2026-03-30 11:22:17', '2026-03-30 11:22:17'),
(139, 71, 1, 20, '2026-03-30 11:22:57', '2026-03-30 11:22:57'),
(140, 71, 2, 12, '2026-03-30 11:22:57', '2026-03-30 11:22:57'),
(141, 72, 1, 20, '2026-03-30 11:25:48', '2026-03-30 11:25:48'),
(142, 72, 2, 12, '2026-03-30 11:25:48', '2026-03-30 11:25:48'),
(143, 73, 1, 20, '2026-03-30 11:26:09', '2026-03-30 11:26:09'),
(144, 73, 2, 12, '2026-03-30 11:26:09', '2026-03-30 11:26:09'),
(145, 74, 1, 20, '2026-03-30 11:33:39', '2026-03-30 11:33:39'),
(146, 74, 2, 12, '2026-03-30 11:33:39', '2026-03-30 11:33:39'),
(147, 75, 1, 20, '2026-03-30 11:34:33', '2026-03-30 11:34:33'),
(148, 75, 2, 12, '2026-03-30 11:34:33', '2026-03-30 11:34:33'),
(149, 76, 1, 20, '2026-03-30 11:38:41', '2026-03-30 11:38:41'),
(150, 76, 2, 12, '2026-03-30 11:38:41', '2026-03-30 11:38:41'),
(151, 77, 1, 20, '2026-03-30 11:38:48', '2026-03-30 11:38:48'),
(152, 77, 2, 12, '2026-03-30 11:38:48', '2026-03-30 11:38:48'),
(153, 78, 1, 20, '2026-03-30 11:42:56', '2026-03-30 11:42:56'),
(154, 78, 2, 12, '2026-03-30 11:42:56', '2026-03-30 11:42:56'),
(155, 79, 1, 20, '2026-03-30 11:43:09', '2026-03-30 11:43:09'),
(156, 79, 2, 12, '2026-03-30 11:43:09', '2026-03-30 11:43:09'),
(157, 80, 1, 20, '2026-03-30 11:52:36', '2026-03-30 11:52:36'),
(158, 80, 2, 12, '2026-03-30 11:52:36', '2026-03-30 11:52:36'),
(159, 81, 1, 20, '2026-03-31 06:40:46', '2026-03-31 06:40:46'),
(160, 81, 2, 12, '2026-03-31 06:40:46', '2026-03-31 06:40:46'),
(161, 82, 1, 20, '2026-03-31 08:18:16', '2026-03-31 08:18:16'),
(162, 82, 2, 12, '2026-03-31 08:18:16', '2026-03-31 08:18:16'),
(163, 83, 1, 20, '2026-03-31 08:58:36', '2026-03-31 08:58:36'),
(164, 83, 2, 12, '2026-03-31 08:58:36', '2026-03-31 08:58:36');

-- --------------------------------------------------------

--
-- Table structure for table `employee_tasks`
--

CREATE TABLE `employee_tasks` (
  `id` int(11) NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `work_date` date NOT NULL,
  `task_description` text NOT NULL,
  `hours_spent` decimal(4,2) NOT NULL DEFAULT 0.00,
  `status` enum('Pending','In Progress','Done') DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_tasks`
--

INSERT INTO `employee_tasks` (`id`, `employee_id`, `work_date`, `task_description`, `hours_spent`, `status`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 1008, '2026-03-31', 'Task -3 ', 4.00, 'In Progress', 'Test', '2026-03-31 12:21:35', '2026-03-31 12:21:35');

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
  `today_status` enum('Present','Late','Absent','On Leave','Half-Day') DEFAULT 'Absent',
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emp_weekly_day_off`
--

CREATE TABLE `emp_weekly_day_off` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `day_off_name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `emp_weekly_day_off`
--

INSERT INTO `emp_weekly_day_off` (`id`, `employee_id`, `day_off_name`, `created_at`, `updated_at`) VALUES
(1, 1, 'Monday', '2026-03-31 11:40:46', '2026-03-31 11:40:46');

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
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`id`, `title`, `start_date`, `end_date`, `created_at`, `updated_at`) VALUES
(1, 'Indenpence Day', '2026-03-26', '2026-03-26', '2026-03-31 11:39:57', '2026-03-31 11:39:57');

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
  `team_id` int(11) DEFAULT NULL,
  `country` varchar(200) DEFAULT NULL,
  `preferred_contact_time` enum('morning','afternoon','evening','night') DEFAULT NULL,
  `has_pending_followup` tinyint(1) DEFAULT 0 COMMENT '1 = follow-up pending, 0 = no pending follow-up'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

--
-- Dumping data for table `lead_sources`
--

INSERT INTO `lead_sources` (`id`, `name`, `slug`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Bashundhara Residencial Area', 'bashundhara-residencial-area', NULL, 1, '2026-03-31 04:24:40', '2026-03-31 04:24:40');

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
-- Table structure for table `letters`
--

CREATE TABLE `letters` (
  `id` int(11) NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `designation_id` int(10) UNSIGNED DEFAULT NULL,
  `letter_type` enum('appointment','joining','promotion','increment','experience','noc') NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `body` longtext NOT NULL,
  `letter_date` date NOT NULL,
  `ref_number` varchar(100) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `current_salary` decimal(10,2) DEFAULT NULL,
  `revised_salary` decimal(10,2) DEFAULT NULL,
  `new_designation` varchar(150) DEFAULT NULL,
  `signatory_name` varchar(150) NOT NULL,
  `signatory_title` varchar(150) NOT NULL,
  `cc_email` varchar(150) DEFAULT NULL,
  `send_to_email` tinyint(1) DEFAULT 0,
  `status` enum('draft','issued') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `letter_templates`
--

CREATE TABLE `letter_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` enum('appointment','joining','promotion','increment','experience','noc') NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'Bashundhora Model Town', 3, '2026-02-19 16:57:02', '2026-02-19 16:57:02'),
(2, 'Ashuliya Model Town', 2, '2026-03-31 10:31:56', '2026-03-31 10:31:56');

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

--
-- Dumping data for table `management_roles`
--

INSERT INTO `management_roles` (`id`, `role_name`, `person_name`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'CEO', 'Mainul Hassan Dulon', 1, 5, '2026-03-31 11:23:07', '2026-03-31 11:23:07');

-- --------------------------------------------------------

--
-- Table structure for table `manpower_requisitions`
--

CREATE TABLE `manpower_requisitions` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `department_head_id` int(11) NOT NULL,
  `designation_id` int(11) NOT NULL,
  `job_title` varchar(255) NOT NULL,
  `no_of_vacancies` int(11) NOT NULL,
  `job_experience` varchar(100) DEFAULT NULL,
  `to_be_filled_before` date DEFAULT NULL,
  `age_limit` varchar(50) DEFAULT NULL,
  `report_to` int(11) DEFAULT NULL,
  `requirement_type` enum('project','replacement','new') NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `replacement_reason` text DEFAULT NULL,
  `job_description` text DEFAULT NULL,
  `education_qualification` text DEFAULT NULL,
  `special_skills` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
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

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `task_id`, `assigned_by`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 1, 6, 'New task assigned: Task-1', 0, '2026-03-31 04:42:24'),
(2, 1, 2, 4, 'New task assigned: Test Task', 0, '2026-03-31 09:02:09'),
(3, 95, 3, 4, 'New task assigned: Task -2', 0, '2026-03-31 09:04:29');

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

--
-- Dumping data for table `payslips`
--

INSERT INTO `payslips` (`id`, `employee_id`, `designation_id`, `payslip_month`, `total_earning`, `total_deduction`, `net_salary`, `company_bank_id`, `status`, `payment_method`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 0, '2026-03-01', 22100.00, 1000.00, 21100.00, 1, 'paid', 'bank', 4, '2026-03-31 11:47:19', '2026-03-31 11:48:20');

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
(3, 1, 3, 1000.00, NULL, '2026-02-23 08:11:48', '2026-02-23 08:11:48'),
(4, 1, 1, 2000.00, NULL, '2026-03-31 11:47:19', '2026-03-31 11:47:19'),
(5, 1, 2, 100.00, NULL, '2026-03-31 11:47:19', '2026-03-31 11:47:19'),
(6, 1, 3, 1000.00, NULL, '2026-03-31 11:47:19', '2026-03-31 11:47:19');

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
(21, 'Camera Manage', 1, 2, '2026-02-21 17:24:42', '2026-02-21 17:24:42'),
(22, 'Upload CSV', 1, 6, '2026-03-30 16:41:45', '2026-03-30 16:41:45');

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
  `floor_wise_unit_meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
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
) ;

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
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `review_text` text NOT NULL,
  `review_date` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ;

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
(9, 'Sales Team Head', NULL, '2026-02-01 12:06:29', '2026-02-01 12:06:29'),
(10, 'Head Of Digital Marketing Team', NULL, '2026-03-30 16:42:44', '2026-03-30 16:42:44');

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
(73, 8, 20, '2026-02-23 07:40:38', '2026-02-23 07:40:38'),
(74, 4, 6, '2026-03-29 10:25:40', '2026-03-29 10:25:40'),
(75, 4, 20, '2026-03-29 10:25:40', '2026-03-29 10:25:40'),
(76, 4, 8, '2026-03-29 10:25:40', '2026-03-29 10:25:40'),
(77, 4, 13, '2026-03-29 10:25:40', '2026-03-29 10:25:40'),
(78, 10, 22, '2026-03-30 16:42:44', '2026-03-30 16:42:44');

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
(1, 3, '2026-02-01', 20, 10, 0.00, '2026-02-22 03:55:03', '2026-02-22 03:55:03'),
(2, 3, '2026-03-01', 5, 2, 0.00, '2026-03-31 10:47:21', '2026-03-31 10:47:21');

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

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `user_id`, `assigned_by`, `title`, `description`, `start_date`, `deadline`, `employee_accept`, `status`, `progress`, `priority`, `created_at`, `updated_at`) VALUES
(1, 1, 6, 'Task-1', '<p>do it</p>', '2026-03-31', '2026-04-01 00:00:00', 0, 'pending', 0, 'high', '2026-03-31 04:42:24', NULL),
(2, 1, 4, 'Test Task', '<p>Please Do</p>', '2026-03-31', '2026-03-31 00:00:00', 0, 'pending', 0, 'high', '2026-03-31 09:02:09', NULL),
(3, 95, 4, 'Task -2', '<p>test</p>', '2026-03-31', '2026-04-01 00:00:00', 0, 'pending', 0, 'high', '2026-03-31 09:04:29', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`id`, `name`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Gulshan Team', 1, 4, '2026-03-30 16:36:44', NULL),
(2, 'Ashuliya Team', 1, 4, '2026-03-31 06:52:47', NULL);

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `team_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `employee_id`, `full_name`, `user_name`, `phone`, `email`, `designation_id`, `address`, `nid`, `role_id`, `password`, `photo`, `media_link1`, `media_link2`, `last_logout`, `created_by`, `created_at`, `updated_at`, `team_id`) VALUES
(1, 0, 'Farzana', 'farzana', '01873244236', 'farzana.tphl13@gmail.com', 28, 'Dhaka', '123', 1, '$2y$10$tyGb6XsaCzqrB5/fmTtTEualGz9EEsCTS4F0mAz7b/ITjBISkKirS', NULL, '', '', '2026-03-30 09:32:43', 6, '2026-02-22 06:47:08', '2026-03-31 04:41:59', 0),
(2, NULL, 'Sojib', 'sojib', '01798342755', 'sales@gmail.com', 1, 'Dhaka', NULL, 5, '$2y$10$tyGb6XsaCzqrB5/fmTtTEualGz9EEsCTS4F0mAz7b/ITjBISkKirS', NULL, NULL, NULL, '2026-03-31 10:49:58', NULL, '2026-02-22 06:47:08', '2026-03-31 10:49:58', NULL),
(3, 1, 'Fahim', 'Fahim', '01790128471', 'fahim@gmail.com', 0, 'Mohammadpur, Dhaka-1207', '23445', 8, '$2y$10$Jy1aK9a5d9P7mA0TqHEkT.RS/7kPh0x/fxpmRaOR1JrLJXEV0ObsG', NULL, '', '', '2026-03-31 11:07:58', 4, '2026-02-23 03:24:25', '2026-03-31 11:07:58', 0),
(4, 1008, 'Md. Jasim Uddin', 'Jasim Uddin', '01762013007', 'hr@gmail.com', 14, 'House:01, Road:03, Block: B, Section:02, Mirpur, Dhaka-1216.', '1903663282', 4, '$2y$10$bhU084TG9E1hi2gpC3g4nODNLbG5lzKoo7vj/Uv6KeYWXtzduE0Ry', NULL, '', '', '2026-03-31 12:08:47', 4, '2026-02-23 05:44:19', '2026-03-31 12:08:47', NULL),
(5, 0, 'Mr. Jisan', 'Mr. Jisan', '01390128223', 'accounts@gmail.com', 3, 'Mohammadpur, Dhaka-1207', '2345676', 6, '$2y$10$cIIGEotRMUt4NdR3BelTA.wRCL7CDcC.s.1UY0ncO7lbttcKda2C6', NULL, '', '', '2026-03-31 12:01:03', 6, '2026-02-23 06:11:06', '2026-03-31 12:01:03', NULL),
(6, NULL, 'Management', 'Management', '01394378234', 'management@gmail.com', 1, 'dhaka', '32453', 3, '$2y$10$BNGc9RJClg5hlb4tqTasqebYBq3PmZCLGw4yiWjiPN.Gh89QF9JoW', NULL, '', '', '2026-03-31 06:23:51', 1, '2026-03-29 03:44:44', '2026-03-31 06:23:51', NULL),
(7, NULL, 'CED', 'CED', '01872364346', 'ced@gmail.com', 1, 'Dhaka', '823445', 7, '$2y$10$a51psvUGsp/WsSE5cY2xNuKpuKSNoG/QZNNxxfWs0I.eSPM1KPAX.', NULL, '', '', '2026-03-31 12:05:54', 6, '2026-03-29 04:01:38', '2026-03-31 12:05:54', NULL),
(8, NULL, 'Sales Team Head', 'Sales Head', '01327488234', 'sales-head@gmail.com', 1, 'Dhaka', '09231943', 9, '$2y$10$Hr8gjcm14bLK/Ns6ws/pt.jz0PA5ObSc.jo4Z7epS23UsEkVDUXn.', NULL, '', '', '2026-03-31 10:06:30', 1, '2026-03-29 04:16:47', '2026-03-31 10:06:30', NULL),
(9, 3, 'Md. rafi', 'admin_39_', '01234567890', 'rafi@easytechx.com', 9, 'ADDL Tower, Dhanmondi 27/A', '', 8, '$2y$10$nId2sBtusb0qFr2136IMrOZVDVkHq8RTjbsJIzgZ8GpaDXDTMhhHG', NULL, '', '', '2026-03-29 10:24:18', 0, '2026-03-29 10:23:39', '2026-03-30 16:36:23', NULL),
(11, 5, 'Bornali Haque', 'bornali', '01335258340', 'bornalihaque.tphl@gmail.com', 3, 'RUAP, Uttara 18, Dhaka', '', 4, '$2y$10$R9YSZd3L1v262q20VEtdG.dyT9xPRWmHFtjeTvwao5i8i24T5Hpm.', NULL, '', '', '2026-03-31 08:48:42', 0, '2026-03-29 10:31:45', '2026-03-31 08:48:42', NULL),
(12, 8, 'Sanjana Hossain Moure', 'sanjana moure', '01901310848', 'moure.tphl@gmail.com', 15, 'Mirpur-2 water tank, Block - F, road -3, Dhaka -1216', '', 8, '$2y$10$2J2ciVq5whQHb80.zq0zYuQcH.eb0B3pagwhnmx9TEsy.elK3J1ry', NULL, '', '', '2026-03-30 05:15:10', 0, '2026-03-30 05:15:10', '2026-03-30 16:36:23', NULL),
(13, 9, 'Md. Rabbi Hossain', 'rabbi hossain', '0 1901-3108', 'rabbihossain.tphl@gmail.com', 20, 'Baro Bazar, Ashulia Model Town, Savar, Dhaka.', '', 0, '$2y$10$J8kru0oQ5ejljqcWLEOJZe76keE/DV.K03Su3ics.G8vASjgJaCpi', NULL, '', '', '2026-03-30 05:15:44', 0, '2026-03-30 05:15:44', '2026-03-30 16:36:23', NULL),
(14, 0, 'Saleh Ahamed', 'saleh ahmed', '0 1901-3650', 'saleh.tphl@gmail.com', 21, '', '', 0, '$2y$10$fsUbevYAvi9OQguKgaiyEOzj/63NgEbIFqoqshjTVDdjkBrNhQ.Sq', NULL, '', '', '2026-03-30 05:18:44', 0, '2026-03-30 05:18:44', '2026-03-31 07:05:40', 2),
(15, 6, 'Md. Kowsar Ahammed', 'kowsar ahammed', '01901310845', 'kawsar.tphl@gmail.com', 21, 'Vill: Bhubangati, Post: Biswarigacha, Thana: Dhunat, Zilla: Bogura', '', 0, '$2y$10$zLe.p.NR/m2yuzovc3hSsO7UbYYNiY4wreK0kSFksNG7pUL0KbMa.', NULL, '', '', '2026-03-30 05:19:07', 0, '2026-03-30 05:19:07', '2026-03-30 16:36:23', NULL),
(16, 0, 'Ashfaque Bhuiyan Omi', 'Ashfaque Omi', '01958253303', 'ashfaqueomi.tphl25@gmail.com', 20, 'Darul Aman Housing, Mirpur-2, Dhaka - 1206', '', 2, '$2y$10$29JSW4M8FbD3VNZnoUD8AOXB5LRrqo7vS22WMNfWezI11UlsObVCa', NULL, '', '', '2026-03-30 05:19:52', 0, '2026-03-30 05:19:52', '2026-03-30 16:36:23', NULL),
(18, 10, 'Mehnaz Ahmed', 'mehnaz ahmed', '0 1901-3650', 'mehnaz.tphl@gmail.com', 20, 'South chayabithi, Joydebpur, Gazipur', '9160336244', 8, '$2y$10$edszxcW7aAhMpXKVGUIZZePVtP.RhKwPuC8vA2baFfd8jmrYS8mEG', NULL, '', '', '2026-03-30 05:26:05', 0, '2026-03-30 05:26:05', '2026-03-30 16:36:23', NULL),
(19, 11, 'Mr. Ibrahim Khalil', 'ibrahim khalil', '01958253319', 'ibrahim.tphl@gmail.com', 20, '5/9, Dakkhin Rajashon, Savar, Dhaka', '2805006299', 8, '$2y$10$iSTZaE4q9PCH.qM.53lFp.4EuEh2KoQKQRkD/q4SGrvV9Un84T1Pa', NULL, '', '', '2026-03-30 05:30:21', 0, '2026-03-30 05:30:21', '2026-03-30 16:36:23', NULL),
(20, 12, 'Shammi Akther', 'shammi akhter', '0 1901-3108', 'shammi.tphl@gmail.com', 20, '13 no sector ,uttara.', '6923766643', 5, '$2y$10$bOb3g/W82j7pq3Jk9s520uIK1KOulFlbRWN4NVT55EC/1NxigzSJm', NULL, '', '', '2026-03-30 05:35:10', 0, '2026-03-30 05:35:10', '2026-03-30 16:36:23', NULL),
(21, 13, 'Rejwan Ahmed', 'rejwan ahmad', '01901365040', 'rejwantphl@gmail.com', 21, 'Sreemangal, Moulvibazar.', '4660653587', 5, '$2y$10$kQcMOXqxvt92qxB0nnSFtexGzH.iIcEQMC66.kOAasjnr943R9lBG', NULL, '', '', '2026-03-30 05:40:37', 0, '2026-03-30 05:40:37', '2026-03-30 16:36:23', NULL),
(22, 14, 'MD. Feroz Ahamed', 'feroz ahamed', '01958253327', 'feroz.tphl@gmail.com', 20, 'Savar, Dhaka', '3761348154', 8, '$2y$10$7hG94FPqPG5HzIWpCm/8QufyocDjtDn.onUagAcqBZEMNRvMX6Z/e', NULL, '', '', '2026-03-30 05:47:14', 0, '2026-03-30 05:47:14', '2026-03-30 16:36:23', NULL),
(23, 15, 'Mojammel Haque', 'mojammel haque', '01958253324', 'mojammel.tphl@gmail.com', 21, 'B-4, G-1, Sher-E-Bangla Nagar Staff Quarter, Dhaka-1207', '865 729 7449', 5, '$2y$10$.IhiGn25wWUqKu9YuC38xeNy6hpeAwnWuiGLT0fK7SuhS.40f8FS6', NULL, '', '', '2026-03-30 05:51:15', 0, '2026-03-30 05:51:15', '2026-03-30 16:36:23', NULL),
(24, 16, 'Mehedi Hasan Shuvo', 'hasan shuvo', '01901365048', 'shuvo.tphl@gmail.com', 26, '', '', 0, '$2y$10$tZXN9MJD2Gvg0/yDAw1chuU1qYmoivutQfpNYdIEWIDLHqQKoaEHq', NULL, '', '', '2026-03-30 05:59:32', 0, '2026-03-30 05:59:32', '2026-03-30 16:36:23', NULL),
(25, 17, 'Rakib Al Hasan', 'rakibal hasan', '01958253321', 'rakib.tphl@gmail.com', 20, 'Daffodil smart city, Birulia savar Dhaka', '4654750571', 5, '$2y$10$yBQjX3SvmWQXO2cA.ol4B.zPmjIqbyUiqysRN7HTZfXLlo4Z95qfq', NULL, '', '', '2026-03-30 06:43:56', 0, '2026-03-30 06:43:56', '2026-03-30 16:36:23', NULL),
(26, 18, 'Md. Nazmul Khan Shihab', 'khan Shihab', '01958253326', 'shihab.tphl@gmail.com', 20, '2450/11,East Dandabor, Palashbari, Savar cantonment -1344, Savar, Dhaka', '1968492643', 8, '$2y$10$b/geGkDCcdjnJY4j931Ia.YyXu1/FBrONUVr8jCr9csPFZXJ8BYMi', NULL, '', '', '2026-03-30 06:50:40', 0, '2026-03-30 06:50:40', '2026-03-30 16:36:23', NULL),
(27, 19, 'Munshi Aiman Kabir', 'aiman kabir', '01958253318', 'aiman.tphl@gmail.com', 20, 'Savar, Dhaka', '6915659434', 8, '$2y$10$lxze0zOCDKCiYZanahnfO.8tgO6v4MZiK8OBHsMxiXr7/4qH3s2FO', NULL, '', '', '2026-03-30 07:01:11', 0, '2026-03-30 07:01:11', '2026-03-30 16:36:23', NULL),
(28, 20, 'Md. Tariqul Islam Illion', 'tariqul islam', '0 1958-2533', 'illion.tphl@gmail.com', 20, 'Ashulia Model Town, Khagan Bazar, Birulia, Savar, Dhaka', '1968356707', 8, '$2y$10$Sj/S3IIUnt4EttdRVs/lXOCGvn9i/funVCadYEXd0ENgLdmEXSZF6', NULL, '', '', '2026-03-30 07:06:02', 0, '2026-03-30 07:06:02', '2026-03-30 16:36:23', NULL),
(29, 21, 'Shafia Afrin Shahana', 'Shafia Afrin Shahana', '01335258332', 'shafia.tphl@gmail.com', 15, 'Apon Bazar Samiti Market, Rayerbag, Jatrabari, Dhaka', '1954806822', 2, '$2y$10$jg9yoH8rHsH2A7wrOb6D8urj1iUv889LWfvhL3dU95RDg8t7HIYpK', NULL, '', '', '2026-03-30 07:56:24', 0, '2026-03-30 07:56:24', '2026-03-30 16:36:23', NULL),
(30, 22, 'Abir Ahmed', 'Abir Ahmed', '01335258328', 'abir0.tphl@gmail.com', 15, 'Poshu Hospital Para, Magura Sadar,Magura', '8703180417', 8, '$2y$10$IU8I8sJvyYl06UUHgy8XHuzHv66J7mAfVdPACHogEpVjTZ8Wuq2bG', NULL, '', '', '2026-03-30 08:29:14', 0, '2026-03-30 08:29:14', '2026-03-30 16:36:23', NULL),
(31, 23, 'Shanawaz Khan', 'Shanawaz Khan', '01335258330', 'shanawaz.tphl@gmail.com', 15, 'House-12,Road-4,Block-J,Banasree Meradia Khilgaon Dhaka 1219', '5115227323', 8, '$2y$10$Px7TN7veOADIqwYdPfISbOLfxqwEkSq9S1R8sTPWDUpAYRo4hb4uS', NULL, '', '', '2026-03-30 08:42:17', 0, '2026-03-30 08:42:17', '2026-03-30 16:36:23', NULL),
(32, 24, 'Md. Saiful Islam Jony', 'saiful jony', '01958253340', 'saif.thpl@gmail.com', 23, '104/A/9 Buddhibi Road,Mohammadpur,Dhaka-1207', '5557674677', 8, '$2y$10$iJlvDYNOYQ5.AFtGpin74uRVIvXn//mKEgfeHXBvVTXXvqLEteiJa', NULL, '', '', '2026-03-30 08:46:37', 0, '2026-03-30 08:46:37', '2026-03-30 16:36:23', NULL),
(33, 25, 'Aranya Bhowmick Dhrubo', 'Aranya Bhowmick Dhrubo', '01335258329', 'dhrubo.tphl@gmail.com', 20, '15 Shah-Shaheb Lane Narinda, Dhaka', '3315312474', 8, '$2y$10$U/nrFg9Eyc.ss5Oi2gFWV.BgzE8NXmjGHyhD9aY2VLUaGv9dr8KwO', NULL, '', '', '2026-03-30 08:47:19', 0, '2026-03-30 08:47:19', '2026-03-30 16:36:23', NULL),
(34, 26, 'Tabassum Akter Soma', 'tabassum soma', '01958253374', 'soma.tphl@gmail.com', 23, 'Ashulia Model Town, Savar, Dhaka', '3758694214', 8, '$2y$10$tfmvA.ksOc8S9hmYTwU5Xut8ERUg7YztJfCydw1f9.K4jRF.HKBom', NULL, '', '', '2026-03-30 08:50:26', 0, '2026-03-30 08:50:26', '2026-03-30 16:36:23', NULL),
(35, 27, 'SADAT HOSSAIN', 'SADAT HOSSAIN', '01335258331', 'sadathossain11@gmail.com', 15, '116, Jagannath Saha Road, Lalbagh, Dhaka-1211', '2852429642', 8, '$2y$10$cRtbvkGvyjShTPIsq0egPO4PYdXmFT3jJ5NzOBGAKeu823cA2CA7K', NULL, '', '', '2026-03-30 08:52:52', 0, '2026-03-30 08:52:52', '2026-03-30 16:36:23', NULL),
(36, 28, 'Md. Ajizul Islam Manik', 'ajizul manik', '01958253338', 'manik.tphl@gmail.com', 20, '1/B , Nondipara, Dhaka ,1219', '3255221362', 8, '$2y$10$NNOzDnndIUhK14eqejmpUOjoCepUEZJxkSPK6/PwQxFH3qwUsb5sC', NULL, '', '', '2026-03-30 08:56:43', 0, '2026-03-30 08:56:43', '2026-03-30 16:36:23', NULL),
(37, 29, 'Tasnur Aman Satu', 'aman satu', '01958253363', 'satu.tphl@gmail.com', 20, 'kumkumari- Midha bari', '7361471282', 8, '$2y$10$B8RxH6k07S5uVYeOQdAySusGhyguSq1rwA5ZNa7DcFbo.kd7jwNCe', NULL, '', '', '2026-03-30 09:00:39', 0, '2026-03-30 09:00:39', '2026-03-30 16:36:23', NULL),
(38, 30, 'Md. Tasdikul Hasan Sikder', 'Md. Tasdikul Hasan Sikder', '01335258317', 'tasdik.tphl@gmail.com', 13, '523 East Kazipara, Dhaka, Mirpur-1216', '2863855777', 8, '$2y$10$hvyLcozNhFKHKyzSHob2LurUMAn1cka7WeuKy6354xGTxHOCEFMv.', NULL, '', '', '2026-03-30 09:03:57', 0, '2026-03-30 09:03:57', '2026-03-30 16:36:23', NULL),
(40, 31, 'Qazi Nadeem Javed', 'Qazi Nadeem Javed', '‪0195825333', 'nadeem.tphl@gmail.com', 25, 'Sector 10 Road 7 uttara', '2350020026', 2, '$2y$10$HwTS4uTRRrfpsjH0iIssnO2q8R1aJb/JKRdGmr8vK3Od7JyC8RaU.', NULL, '', '', '2026-03-30 09:09:53', 0, '2026-03-30 09:09:53', '2026-03-30 16:36:23', NULL),
(41, 32, 'Nayeemur Rahman', 'Nayeemur Rahman', '01335258318', 'nayeemtonmoytphl@gmail.com', 15, 'Road No. 4, F Block, Mirpur-2', '1960680278', 8, '$2y$10$vNpgHQwRP33k2ftwwnzuuO8mJSGtb2K6MSgQyyzBm5hlu1pxQKt0.', NULL, '', '', '2026-03-30 09:12:53', 0, '2026-03-30 09:12:53', '2026-03-30 16:36:23', NULL),
(42, 33, 'Mahin - Al Beruni', 'Mahin - Al Beruni', '01958253328', 'tphl.mahin@gmail.com', 26, 'House 40/3, Varalipara, Boro Bangram, Sopura, Shah Mokhdum, Rajshahi - 6203', '8710606925', 2, '$2y$10$w0GwPk82B7c/775XmUq6V.XOJnByBk5qE3Ux5I6nJB.0m2d0NhGWW', NULL, '', '', '2026-03-30 09:16:00', 0, '2026-03-30 09:16:00', '2026-03-30 16:36:23', NULL),
(44, 35, 'Md. Momidul Islam', 'Md. Momidul Islam', '01958253335', 'momidul.tphl@gmail.com', 20, 'Nolbata, Hatiandaha, Singra, Natore', '6453089770', 8, '$2y$10$735.t6axd8CKpzTxu40BE.kPj2RKdYQNoq1l293e1MpkZwCP62cUm', NULL, '', '', '2026-03-30 09:33:28', 1, '2026-03-30 09:23:02', '2026-03-30 16:36:23', NULL),
(48, 36, 'Rifat Ahsan', 'Rifat Ahsan', '01958583350', 'rifat.tphl@gmail.com', 20, 'Khilkhet kha para', '4213633714', 8, '$2y$10$O3uQB5h/6KwsPWex8ESVzee39365moqUtNr9DIoqhxeJJM1JDLB4e', NULL, '', '', '2026-03-30 09:32:02', 0, '2026-03-30 09:30:56', '2026-03-30 16:36:23', NULL),
(51, 38, 'Sudipto Azad Hriday', 'Sudipto Azad Hriday', '01901365046', 'sudiptoazad.tphl@gmail.com', 17, '38, Road 3B, Mohammadia Housing Limited, Mohammadpur, Dhaka-1207', '9151890663', 8, '$2y$10$BMBC8TMV0h.nDrahyrmUO.dhGkDQwII8Ov.UT/Hg.JVRBA6wTMUY2', NULL, '', '', '2026-03-30 09:38:33', 0, '2026-03-30 09:38:33', '2026-03-30 16:36:23', NULL),
(53, 39, 'Al Mubin Shuvo', 'Al Mubin Shuvo', '01335258302', 'almubintphl@gmail.com', 15, 'Kumarkhali,Kushtia', '5564288271', 8, '$2y$10$mXwL/qloJHAWt2lal/Y9HO4yO0sCjGHKGgr1/AckNXExlPEy1iaKS', NULL, '', '', '2026-03-30 09:40:37', 0, '2026-03-30 09:40:37', '2026-03-30 16:36:23', NULL),
(54, 40, 'France Roy', 'France Roy', '01335258303', 'franceroy.tphl26@gmail.com', 13, '', '', 8, '$2y$10$WhAVna/AwmbasRb18o4Qi.TaQytrObLPCqFQ8WcErTuhoAGj0qAf2', NULL, '', '', '2026-03-30 09:41:04', 0, '2026-03-30 09:41:04', '2026-03-30 16:36:23', NULL),
(55, 41, 'Md. Anwar Parvez', 'Md. Anwar Parvez', '01335258305', 'anwar.tphl@gmail.com', 13, '', '6004033616', 8, '$2y$10$nD6apWdkQA9A4pxQ2WK.TuXjrF./xCNaUOMtnxEXIhYBUEm6ec5v.', NULL, '', '', '2026-03-30 09:44:21', 0, '2026-03-30 09:44:21', '2026-03-30 16:36:23', NULL),
(56, 42, 'Nafisa Anjum', 'Nafisa Anjum', '01335258319', 'nafisaanjum.tphl@gmail.com', 23, 'Ilisha Road, Ward No-1, Bhola Pourashava.\r\nBhola Sadar, District- Bhola.', '8727836291', 8, '$2y$10$Njcwsx0qybiXNWr6I4xU6OXrnNZ0Gez0PTeiI9mymsLEByvmFtFau', NULL, '', '', '2026-03-30 09:48:38', 0, '2026-03-30 09:48:38', '2026-03-30 16:36:23', NULL),
(57, 43, 'Md.Minhazul Islam Sumon', 'Md. Minhazul Islam Sumon', '01607037251', 'sumon.tphl@gmail.com', 20, 'Datta para, birulia,Savar,Dhaka', '6445593269', 0, '$2y$10$Vxxg305sx5c9QTdS1nZe5Ow1VTzQGDXu6Vo101UVl3iRJTGWLHPmq', NULL, '', '', '2026-03-30 09:56:21', 0, '2026-03-30 09:56:21', '2026-03-30 16:36:23', NULL),
(58, 44, 'Md Tarekul Islam', 'Md Tarekul Islam', '01335258316', 'tarekul.tphl@gmail.com', 16, 'Kalai, Joypurhat', '7811570162', 8, '$2y$10$FgfGch01R5MmOmV06QbSE.Ztcu6ohd1xekC57B9SwWwuMmnbs1KSq', NULL, '', '', '2026-03-30 09:59:28', 0, '2026-03-30 09:59:28', '2026-03-30 16:36:23', NULL),
(59, 45, 'Kazi Jahid Hasan', 'Kazi Jahid Hasan', '01335258339', 'jahid.tphl@gmail.com', 23, 'Shah Ali Thana, Mirpur 1, Dhaka, 1229', '4218961284', 8, '$2y$10$o1c8bwaEWyTgxCs8OKnmy.155Q2E1XV.Y5Zt2/N.AwJB9HhD39PbC', NULL, '', '', '2026-03-30 10:03:44', 0, '2026-03-30 10:03:44', '2026-03-30 16:36:23', NULL),
(60, 46, 'H M Nazmul Alam', 'H M Nazmul Alam', '01335258313', 'nazmulshakib.tphl@gmail.com', 15, 'Flat No: 4-A, Dreamland, 43/A Hazi Abdul Hai Road, Zigatola, Dhanmondi, Dhaka 1209', '511-057-9504', 8, '$2y$10$j8oSuPOI0BarG10VQLdlgu3XQLs9u4HXgl7l1S1GgnNFTSjDm5X2C', 'users_69cb86772a604.jpg', '', '', '2026-03-30 10:05:23', 60, '2026-03-30 10:05:23', '2026-03-31 08:31:51', 0),
(61, 47, 'Mst. Tamanna Aktar Meghna', 'Mst. Tamanna Aktar Meghna', '01958253348', 'tamanna.tphl@gmail.com', 23, 'Khagan, Birulia, savar, 1340', '7365082259', 8, '$2y$10$GAromZnC1DJDwjgu1qW4.eU0/aoDJbqvJPr3oyFS3HflvESXRJTwi', NULL, '', '', '2026-03-30 10:07:57', 0, '2026-03-30 10:07:57', '2026-03-30 16:36:23', NULL),
(62, 48, 'Md. Rayhan Kabir Roni', 'Md. Rayhan Kabir Roni', '01335-25832', 'rayhan.tphl@gmail.com', 15, 'Palashbari,Gaibandha', '6023825901', 8, '$2y$10$nN8hAqbsVXHTLsh.zHew2.nqBcRmvzezxII7SAQl3DPS8vx9/0NHy', NULL, '', '', '2026-03-30 10:09:50', 0, '2026-03-30 10:09:50', '2026-03-30 16:36:23', NULL),
(63, 49, 'Sayed Al Shahab', 'Sayed Al Shahab', '01335258322', 'Assayed1937@gamail.com', 15, '\" Village/Street: Rouha, Post Office:Niamatpur - 2310, Karimganj, Kishoreganj\"', '1030789414', 8, '$2y$10$rv0RL4khHntFcChFrOYqI.kCHW6ydlwvqCzbfxm/YVRyMNo0OFrou', NULL, '', '', '2026-03-30 10:16:53', 0, '2026-03-30 10:16:53', '2026-03-30 16:36:23', NULL),
(64, 50, 'Rafiqul Islam', 'Rafiqul Islam', '01958253345', 'rafiqul.tphl@gmail.com', 20, 'Khalpar,Tarabo,Rupganj,Narayanganj, post code:1460', '5563671634', 8, '$2y$10$2N5KDT5gZ0UCL6S8SeRxWuZ3fxTOOWUgw3LABYD4NHzmf06UsJGNS', NULL, '', '', '2026-03-30 10:20:00', 64, '2026-03-30 10:20:00', '2026-03-31 07:43:42', 0),
(65, 51, 'Md. Mahabubur Rahman Mehedee', 'Md. Mahabubur Rahman Mehedee', '01335258307', 'mehedee.tphl@gmail.com', 21, '', '4665275352', 8, '$2y$10$8RKSqAv7Ir8wx2sEjdhPR.h.toEB4e5Xhoj1IGdstx2f89Hd/gKse', NULL, '', '', '2026-03-30 10:24:57', 0, '2026-03-30 10:24:57', '2026-03-30 16:36:23', NULL),
(66, 52, 'Md. Sakib Al Hasan', 'Md. Sakib Al Hasan', '01335258325', 'sakibhasan.tphl@gmail.com', 17, 'Changone, Ahulia, Dhaka 1341', '8268549592', 8, '$2y$10$A/mGm65CzDyqbYojepkTpOWidQpnLF4HkAhx0vkylEh38peYQuX26', NULL, '', '', '2026-03-30 10:25:00', 0, '2026-03-30 10:25:00', '2026-03-30 16:36:23', NULL),
(67, 53, 'Md Asim Akram', 'Md Asim Akram', '01335258308', 'akram.tphl@gmail.com', 15, '', '2412933208', 8, '$2y$10$FYOiBtICro0TkZQIb4VfhOwX5a0nUwvvFVxi2oZD3gp6ZDK75ngoW', NULL, '', '', '2026-03-30 10:31:00', 0, '2026-03-30 10:31:00', '2026-03-30 16:36:23', NULL),
(68, 54, 'Rakib Hasan Rubai', 'Rakib Hasan Rubai', '01335258309', 'rubai.tphl@gmail.com', 26, 'Nobabgonj Bazar, Lalbagh , Dhaka -1205', '8268549592', 0, '$2y$10$gGBH11phPzR08MAiTN7tee63vhPaYDyoHa3ANBGhuqJwgOKqRPM2e', NULL, '', '', '2026-03-30 10:31:59', 0, '2026-03-30 10:31:59', '2026-03-30 16:36:23', NULL),
(69, 55, 'Md. Rawnak Hassan Seyam', 'Md. Rawnak Hassan Seyam', '01335-25832', 'rawnak.tphl@gmail.com', 15, '', '2414491106', 8, '$2y$10$GKoc1DcZdbOmsLGSBPsJU.6NaH/06m1BMsff0OFZGr05BZYXR5BgS', NULL, '', '', '2026-03-30 10:35:13', 0, '2026-03-30 10:35:13', '2026-03-30 16:36:23', NULL),
(70, 56, 'Zannatul Ferdous', 'Zannatul Ferdous', '01335258315', 'zannatul.tphl@gmail.com', 20, 'Ghoshbagh,Ashulia, Dhaka', '4672259688', 8, '$2y$10$N5VFRRAr6NPeO2sNHAv6hOhir6DidyOkV8l6C54kSifQZw/MVhYiG', NULL, '', '', '2026-03-30 10:35:17', 0, '2026-03-30 10:35:17', '2026-03-30 16:36:23', NULL),
(71, 57, 'Mst. Mahzabin Reza', 'MST. MAHZABIN REZA', '01335258311', 'mahzabin.tphl@gmail.com', 23, '', '5114927907', 8, '$2y$10$Y1Zl3QED4PDxFZhq9szMzOObIZ2wSz/pd0Dqy2e9zB8zJi4Yy4E4y', NULL, '', '', '2026-03-30 10:40:34', 0, '2026-03-30 10:40:34', '2026-03-30 16:36:23', NULL),
(72, 58, 'Md. Ali Haider Abdullah', 'Md. Ali Haider Abdullah', '0 1958-2533', 'tphl.alihaider@gmail.com', 17, '', '1028428793', 8, '$2y$10$/T5eDgeej3P.JoXuhChGceCnDHOceBwixNyIGehU4lHJ0xHtdqDFe', NULL, '', '', '2026-03-30 10:41:19', 0, '2026-03-30 10:41:19', '2026-03-30 16:36:23', NULL),
(73, 59, 'Tanvir Hossain Jian', 'Tanvir Hossain Jian', '01335258314', 'tanvir.tphl@gmail.com', 20, 'Uttor badda, Abdullah bag masjid', '4205166475', 8, '$2y$10$8YxvgfxnkLTwgnksEVIeRu3dLINjXElLIbY6LUvy4.t6K3HzQ3b2a', NULL, '', '', '2026-03-30 10:44:55', 0, '2026-03-30 10:44:55', '2026-03-30 16:36:23', NULL),
(74, 60, 'Ahnaf Adil Roxy', 'Ahnaf Adil Roxy', '01335-25832', 'roxy.tphl@gmail.com', 13, '', '4213421763', 8, '$2y$10$EidwlET76JrkaxbXZWi1b.WHbW2IdKsfzevS5cbMDnLM3pJPk/arK', NULL, '', '', '2026-03-30 10:45:59', 0, '2026-03-30 10:45:59', '2026-03-30 16:36:23', NULL),
(75, 61, 'Md.Tamim', 'Md.Tamim', '01308212749', 'tamim.tphl@gmail.com', 23, 'Dattapara, Birulia,Savar, Dhaka 1216', '', 8, '$2y$10$vP/q95cSabGHgVL9h3sBw.MWwc8JGYQiRXLarqEx6zl8p6A43uwk2', NULL, '', '', '2026-03-30 10:49:04', 0, '2026-03-30 10:49:04', '2026-03-30 16:36:23', NULL),
(76, 63, 'Akhoy kumer Nibir Roy', 'Akhoy kumer Nibir Roy', '01335258301', 'akhoy.tphl@gmail.com', 15, '', '6913904170', 8, '$2y$10$/A2yZtrb7UELabmGdPX.IuVuX/LcgmoxPoe0CV56vCoxJGAfGOUie', NULL, '', '', '2026-03-30 10:54:37', 0, '2026-03-30 10:54:37', '2026-03-30 16:36:23', NULL),
(77, 64, 'Md. Amanullah Aman', 'Md. Amanullah Aman', '01335258306', 'aman.tphl@gmail.com', 20, 'South Bhandaria-8550, Bhandaria, Pirojpur', '6010834478', 8, '$2y$10$GvrxHKdWRY2m3tvvUsFMRuAgqeM6L993ISeHJG/42UBRYVYI6UnQ6', NULL, '', '', '2026-03-30 10:56:23', 0, '2026-03-30 10:56:23', '2026-03-30 16:36:23', NULL),
(78, 65, 'Abu Naym', 'Abu Naym', '01335258312', 'naymtphl@gmail.com', 20, 'Ati-1312, Keraniganj, Dhaka', '4204089967', 8, '$2y$10$CojmU9mTBwgDjkx4CfkdYeaQ57vvBBDxV/seIXX4jy.25RgK19WMG', 'users_69cb90acdaad5.jpg', '', '', '2026-03-30 10:59:42', 78, '2026-03-30 10:59:42', '2026-03-31 09:15:24', 0),
(79, 66, 'Md. Sakib Inam', 'Md. Sakib Inam', '01335258310', 'sakibinam.tphl@gmail.com', 16, '', '1962602841', 8, '$2y$10$uhKw9sTOb03dEHxMvZb/KekSOvQ6RjfanioES3ZW5pOa4m.VkexNO', NULL, '', '', '2026-03-30 11:00:09', 0, '2026-03-30 11:00:09', '2026-03-30 16:36:23', NULL),
(80, 67, 'Md Bakhtiar Hasan', 'Md Bakhtiar Hasan', '01335258300', 'bakhtiar.tphl@gmail.com', 13, '', '4602140743', 8, '$2y$10$3KjxMU.PqmWBXo77Kd3nbO4vYx55T4pByMR7U.kHE91Sro2ch8sLe', NULL, '', '', '2026-03-30 11:10:48', 0, '2026-03-30 11:10:48', '2026-03-30 16:36:23', NULL),
(81, 68, 'Md Lutfor Rahaman', 'Md Lutfor Rahaman', '01335258334', 'lutfor.tphl@gmail.com', 27, 'Vill: Asapur, PO: Phancherkanda, Thana: Ghior, Dist: Manikganj, Dhaka', '7350928797', 2, '$2y$10$SiSPnIWXvfbAy0DznRqjNebZp6fjgF0LGkHEVG7NPoNy8.J2fdc9O', NULL, '', '', '2026-03-30 11:17:42', 0, '2026-03-30 11:17:42', '2026-03-30 16:36:23', NULL),
(82, 69, 'Dipto Kumar Roy', 'Dipto Kumar Roy', '01958253358', 'dipto.tphl@gmail.com', 20, 'Khagan,Ashulia,Saver Dhaka', '2851653333', 2, '$2y$10$pB9uAIYgkG6tt/yMZQzVUuiQzSe3shbNwKlu45kAtQqlTrNGZ/JPu', NULL, '', '', '2026-03-30 11:19:42', 0, '2026-03-30 11:19:42', '2026-03-30 16:36:23', NULL),
(83, 70, 'B. M Shaharear Hasan', 'B. M Shaharear Hasan', '01917331397', 'shaharear.tphl@gmail.com', 27, 'Korotoa road, latifpur kolony, Bogra', '7318135568', 6, '$2y$10$IR.UVy4pARa366NVJj3DGejR6j1MB0KlvUqEi5kvhJeZSLVd3RenW', NULL, '', '', '2026-03-30 11:22:17', 0, '2026-03-30 11:22:17', '2026-03-30 16:36:23', NULL),
(84, 71, 'MD. Sakhawat Hossain Emon', 'MD. Sakhawat Hossain Emon', '01335258335', 'emon.tphl@gmail.com', 23, 'Block-A, Lane-6, Mirpur-6, Dhaka 1216', '1962405062', 2, '$2y$10$zbxbowLRq8arKWPKWtXlmutMu0HgAD/L0jo0QOuiV5v1tzE/5MBoK', NULL, '', '', '2026-03-30 11:22:57', 0, '2026-03-30 11:22:57', '2026-03-30 16:36:23', NULL),
(85, 72, 'MD. Zamiul Islam', 'MD. Zamiul Islam', '01335258338', 'zamiul.tphl@gmail.com', 17, '130/6/A Baganbari,  Madatek, Basabo, Dhaka -1214.', '5086466165', 2, '$2y$10$JNlsOeAu7vANay8HvhgzTu8qrFZTw42wEfb9hvza857xBIdxSE5kq', NULL, '', '', '2026-03-30 11:25:48', 0, '2026-03-30 11:25:48', '2026-03-30 16:36:23', NULL),
(86, 73, 'MD. Ahadul Islam', 'MD. Ahadul Islam', '01314010972', 'asif.tphl@gmail.com', 23, 'Amula Dahabharatta, Nikrail, Bhuapur.', '3314775515', 2, '$2y$10$YlJmkkdgPI02YYTHw5G/o.tJE.7k/v6D34A6bHyNdlPuQztkA1IFe', NULL, '', '', '2026-03-30 11:26:09', 0, '2026-03-30 11:26:09', '2026-03-30 16:36:23', NULL),
(87, 74, 'Sadman Islam Sakib', 'Sadman Islam Sakib', '01958253344', 'sakib.tphl@gmail.com', 21, 'H-12, Kusumbag Residential Area, North Bisil  Road, Mirpur-1,Dhaka-1216', '7817318327', 8, '$2y$10$EZYoIwrFc/RwFFaj299PeOTVK1Jjf4pVV8dFhN0apKOxd6.BoIP9C', NULL, '', '', '2026-03-30 11:33:39', 0, '2026-03-30 11:33:39', '2026-03-30 16:36:23', NULL),
(88, 75, 'Mahaananda Kumar Saha', 'Mahaananda Kumar Saha', '01335258327', 'mahaanandakumarsaha@gmail.com', 22, 'Faridpur, sadarpur,hatkrishnapur', '5506251882', 2, '$2y$10$IawCX1UOcrjxps1aO4QTWeip4b8tKdN3JGDyduTJtnqLNYVuIzYXu', NULL, '', '', '2026-03-30 11:34:33', 0, '2026-03-30 11:34:33', '2026-03-30 16:36:23', NULL),
(90, 79, 'Moonmoon Akter Oishy', 'Moonmoon Akter Oishy', '01958253355', 'oishytphl@gmail.com', 24, 'Road No -1,  B - 45 Bank Town,  Savar', '1047316906', 2, '$2y$10$Sdqb84cXh8GKU9Okw2rRSe7k3N6a0riQJVPBDqnRhqa9px28WsDY.', NULL, '', '', '2026-03-30 11:43:09', 0, '2026-03-30 11:43:09', '2026-03-30 16:36:23', NULL),
(91, 76, 'Rubina Islam', 'Rubina Islam', '01958253349', 'rubinatphl49@gmail.com', 15, '', '', 2, '$2y$10$r8GdWRKSpeZjr24W1pjGn.uaye6s/.clnO7G8ESZI3NnV844NrnCS', NULL, '', '', '2026-03-30 11:44:43', 0, '2026-03-30 11:44:43', '2026-03-30 16:36:23', NULL),
(92, 80, 'Afrin Jahan Anika', 'Afrin Jahan Anika', '01958253335', 'anika.tphl@gmail.com', 13, '39/b senpara porbota mirpur 10', '1032936708', 2, '$2y$10$nBTOuqyG5vmEhBfJADloguSRdoA383Hrd.mFIuseKxOXG3bkfZYZG', NULL, '', '', '2026-03-30 11:52:36', 0, '2026-03-30 11:52:36', '2026-03-30 16:36:23', NULL),
(94, NULL, 'Tamim Rahman', 'Tamim', '01309871687', 'tamim@gmail.com', 28, 'Dhaka', '', 10, '$2y$10$3IMCW.xuhhG7bV7kHAWZc.J9C2XpEVG0lBjZ83H8.IYgxaZjmw3Ka', NULL, '', '', '2026-04-01 05:01:18', 6, '2026-03-30 16:43:54', '2026-04-01 05:01:18', 0),
(95, 81, 'Riya', 'Riya', '01778234546', 'riya@gmail.com', 28, 'Dhaka', '34', 5, '$2y$10$cUYgCjvi4yiNDYS8mTGWsOkEvOJR32hiN/5Dsku7ULURP.tcviqpK', NULL, '', '', '2026-03-31 06:50:14', 4, '2026-03-31 06:40:46', '2026-03-31 09:28:57', 0),
(98, 82, 'S.A Arnob', 'S.A Arnob', '01958253347', 'arnob.tphl@gmail.com', 20, 'Dottopara, Asuliya, Dhaka', '7373986467', 8, '$2y$10$aaooqfFW63prXcheowgGMeuZAHV88qpV1JQMljfQiHbOv2qCKtFdy', NULL, '', '', '2026-03-31 08:18:16', 0, '2026-03-31 08:18:16', '2026-03-31 08:18:16', 2),
(100, 83, 'Md. Mamunur Rahman', 'Md. Mamunur Rahman', '01958253342', 'mamunur.tphl@gmail.com', 19, '', '1947418776', 7, '$2y$10$ADpw3aS2xygSq6zySs.GaOoDAhv9XTdf3jXLxpZeyQ8fJinfXiZwG', NULL, '', '', '2026-03-31 08:58:36', 0, '2026-03-31 08:58:36', '2026-03-31 08:58:36', 2);

-- --------------------------------------------------------

--
-- Table structure for table `whatsapp_campaigns`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `whatsapp_messages`
--

CREATE TABLE `whatsapp_messages` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `lead_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('pending','sent','delivered','failed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

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
-- Indexes for table `employee_tasks`
--
ALTER TABLE `employee_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `emp_attendances`
--
ALTER TABLE `emp_attendances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_date` (`employee_id`,`attendance_date`);

--
-- Indexes for table `emp_weekly_day_off`
--
ALTER TABLE `emp_weekly_day_off`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`);


--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `fk_user_employee` (`employee_id`);

--
-- Indexes for table `whatsapp_campaigns`
--
ALTER TABLE `whatsapp_campaigns`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `whatsapp_messages`
--
ALTER TABLE `whatsapp_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `lead_id` (`lead_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cashout_requests`
--
ALTER TABLE `cashout_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `communities`
--
ALTER TABLE `communities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `designations`
--
ALTER TABLE `designations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `emi_schedules`
--
ALTER TABLE `emi_schedules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `employee_leaves`
--
ALTER TABLE `employee_leaves`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_leave_balances`
--
ALTER TABLE `employee_leave_balances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=165;

--
-- AUTO_INCREMENT for table `employee_tasks`
--
ALTER TABLE `employee_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `emp_attendances`
--
ALTER TABLE `emp_attendances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emp_weekly_day_off`
--
ALTER TABLE `emp_weekly_day_off`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `flats`
--
ALTER TABLE `flats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lead_bookings`
--
ALTER TABLE `lead_bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lead_follow_ups`
--
ALTER TABLE `lead_follow_ups`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lead_payments`
--
ALTER TABLE `lead_payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lead_sources`
--
ALTER TABLE `lead_sources`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `onboard_clients`
--
ALTER TABLE `onboard_clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payslips`
--
ALTER TABLE `payslips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payslip_details`
--
ALTER TABLE `payslip_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payslip_items`
--
ALTER TABLE `payslip_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `property_reviews`
--
ALTER TABLE `property_reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `sales_targets`
--
ALTER TABLE `sales_targets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;


--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT for table `whatsapp_campaigns`
--
ALTER TABLE `whatsapp_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `whatsapp_messages`
--
ALTER TABLE `whatsapp_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
