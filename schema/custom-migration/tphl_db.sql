-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 14, 2026 at 04:54 PM
-- Server version: 10.11.15-MariaDB
-- PHP Version: 8.4.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dvalleyb_tphl_db`
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

--
-- Dumping data for table `approval_requests`
--

INSERT INTO `approval_requests` (`id`, `department`, `request_type`, `title`, `source_table`, `source_id`, `request_amount`, `approved_amount`, `requested_days`, `approved_days`, `approved_by`, `approved_at`, `status`, `requested_by`, `approve_role`, `meta_data`, `created_at`, `updated_at`, `approval_note`) VALUES
(1, 'Sales', 'discount', 'Lead Booking Discount Approval', 'lead_bookings', 12, 40000.00, NULL, NULL, NULL, 2, '2026-01-29 15:59:15', 'approved', 12, 'management', '{\"lead_id\":\"10\",\"flat_id\":\"6\"}', '2026-01-27 10:55:45', '2026-01-29 15:59:15', ''),
(2, 'HR', 'salary', 'Salary Approval', 'payslips', 3, 31000.00, NULL, NULL, NULL, 12, '2026-01-30 04:33:17', 'approved', 12, 'management', '{\"employee_id\":\"32\",\"month\":\"27 January 2026\"}', '2026-01-27 11:18:58', '2026-01-30 04:33:17', ''),
(3, 'HR', 'leave', 'Leave Approval Request', 'employee_leaves', 1, NULL, NULL, 3, 2, 2, '2026-01-29 15:54:07', 'partial', 12, 'management', '{\"employee_id\":\"7\",\"leave_type_id\":\"1\",\"requested_days\":\"3\"}', '2026-01-27 11:27:56', '2026-01-29 15:54:07', ''),
(4, 'Accounts', 'cashout', 'Cashout Request: sdfgfsdf', 'cashout_requests', 0, 1161.66, NULL, NULL, NULL, 2, '2026-01-29 16:15:00', 'approved', 4, 'management', '{\"purpose\":\"sdfgfsdf\",\"payment_method\":\"cash\",\"requester_name\":\"Mr.X\"}', '2026-01-29 16:14:38', '2026-01-29 16:15:00', ''),
(5, 'Accounts', 'cashout', 'Cashout Request: new request', 'cashout_requests', 0, 1561.66, NULL, NULL, NULL, 2, '2026-01-29 16:46:39', 'approved', 4, 'management', '{\"purpose\":\"new request\",\"payment_method\":\"cash\",\"requester_name\":\"Mr.X\"}', '2026-01-29 16:19:43', '2026-01-29 16:46:39', ''),
(6, 'Accounts', 'cashout', 'Cashout Request: new test request', 'cashout_requests', 0, 5000.00, NULL, NULL, NULL, 2, '2026-01-29 17:48:08', 'approved', 4, 'management', '{\"purpose\":\"new test request\",\"payment_method\":\"cash\",\"description\":\"sadfasdfc\"}', '2026-01-29 16:48:05', '2026-01-29 17:48:08', ''),
(7, 'Accounts', 'cashout', 'Cashout Request: 2nd test request', 'cashout_requests', 0, 1163.66, NULL, NULL, NULL, 2, '2026-01-29 17:33:43', 'approved', 4, 'management', '{\"purpose\":\"2nd test request\",\"payment_method\":\"cash\",\"description\":\"2nd test request detail\"}', '2026-01-29 17:32:58', '2026-01-29 17:33:43', ''),
(8, 'Accounts', 'cashout', 'Cashout Request: new request', 'cashout_requests', 5, 33334.00, NULL, NULL, NULL, 2, '2026-01-29 18:22:32', '', 4, 'management', '{\"purpose\":\"new request\",\"payment_method\":\"cash\",\"requester_name\":\"Mr.X\",\"description\":\"sadfsadf\"}', '2026-01-29 18:21:35', '2026-01-29 18:22:32', ''),
(10, 'HR', 'leave', 'Leave Approval Request', 'employee_leaves', 2, 0.00, NULL, 15, NULL, 2, '2026-01-31 06:12:41', 'rejected', 2, 'management', '{\"employee_id\":\"8\",\"leave_type_id\":\"2\",\"requested_days\":\"15\"}', '2026-01-30 06:50:05', '2026-01-31 06:12:41', ''),
(11, 'Accounts', 'cashout', 'Cashout Request: Glass Bill', 'cashout_requests', 7, 200000.00, NULL, NULL, NULL, 2, '2026-01-31 06:12:20', 'rejected', 16, 'management', '{\"purpose\":\"Glass Bill\",\"payment_method\":\"online\",\"requester_name\":\"Arif Hossain\",\"description\":\"\"}', '2026-01-30 10:23:38', '2026-01-31 06:12:20', ''),
(12, 'Sales', 'discount', 'Lead Booking Discount Approval', 'lead_bookings', 13, 10000.00, NULL, NULL, NULL, 2, '2026-01-31 06:12:25', 'approved', 12, 'management', '{\"lead_id\":\"12\",\"flat_id\":\"6\"}', '2026-01-30 11:15:04', '2026-01-31 06:12:25', ''),
(13, 'Sales', 'discount', 'Lead Booking Discount Approval', 'lead_bookings', 14, 10000.00, 10000.00, NULL, NULL, 12, '2026-02-05 06:37:28', 'approved', 12, 'management', '{\"lead_id\":\"25\",\"flat_id\":\"6\"}', '2026-02-02 11:57:33', '2026-02-05 06:37:28', NULL),
(14, 'Sales', 'discount', 'Lead Booking Discount Approval', 'lead_bookings', 15, 10000.00, NULL, NULL, NULL, NULL, NULL, 'pending', 12, 'management', '{\"lead_id\":\"26\",\"flat_id\":\"6\"}', '2026-02-04 08:55:34', '2026-02-04 08:55:34', ''),
(15, 'HR', 'salary', 'Salary Approval', 'payslips', 4, 30000.00, NULL, NULL, NULL, NULL, NULL, 'pending', 14, 'management', '{\"employee_id\":\"32\",\"month\":\"04 February 2026\"}', '2026-02-04 09:40:20', '2026-02-04 09:40:20', ''),
(16, 'Accounts', 'cashout', 'Cashout Request: Glass Bill', 'cashout_requests', 1, 500000.00, NULL, NULL, NULL, NULL, NULL, 'pending', 16, 'management', '{\"purpose\":\"Glass Bill\",\"payment_method\":\"cash\",\"requester_name\":\"Arif Hossain\",\"description\":\"The Glass House\"}', '2026-02-04 09:59:36', '2026-02-04 09:59:36', ''),
(17, 'HR', 'leave', 'Leave Approval Request', 'employee_leaves', 3, 0.00, NULL, 1, NULL, NULL, NULL, 'pending', 19, 'management', '{\"user_id\":19,\"leave_type_id\":\"2\",\"requested_days\":1}', '2026-02-08 12:06:01', '2026-02-08 12:06:01', NULL),
(18, 'HR', 'leave', 'Leave Approval Request', 'employee_leaves', 4, 0.00, NULL, 2, NULL, NULL, NULL, 'pending', 17, 'management', '{\"user_id\":17,\"leave_type_id\":\"2\",\"requested_days\":2}', '2026-02-08 12:12:10', '2026-02-08 12:12:10', NULL),
(19, 'HR', 'leave', 'Leave Approval Request', 'employee_leaves', 5, 0.00, NULL, 1, NULL, NULL, NULL, 'pending', 24, 'management', '{\"employee_id\":35,\"leave_type_id\":1,\"requested_days\":1}', '2026-02-13 16:57:03', '2026-02-13 16:57:03', NULL);

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

--
-- Dumping data for table `blogs`
--

INSERT INTO `blogs` (`id`, `title`, `slug`, `excerpt`, `content`, `image`, `author`, `published_date`, `comments`, `read_time`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'From Vision to Venture, The Entrepreneurial Journey2', 'from-vision-to-venture-the-entrepreneurial-journey2', 'চবিতে “From Vision to Venture; The Entrepreneurial Journey” অনুপ্রেরণামূলক সেশন অনুষ্ঠিত', 'চিটাগং ইউনিভার্সিটি ইন্ট্রাপেনার এন্ড স্টার্টআপ সোসাইটি (সিইউইএসএস) এর উদ্যোগে “From Vision to Venture; The Entrepreneurial Journey” শীর্ষক এক অনুপ্রেরণামূলক সেশন বুধবার (৫ নভেম্বর ২০২৫) দুপুর ১টায় চবি ব্যবসায় প্রশাসন অনুষদ অডিটোরিয়ামে অনুষ্ঠিত হয়। অনুষ্ঠানে বিশেষ অতিথি হিসেবে উপস্থিত ছিলেন চট্টগ্রাম বিশ্ববিদ্যালয়ের মাননীয় উপ-উপাচার্য (একাডেমিক) প্রফেসর ড. মোহাম্মদ শামীম উদ্দিন খান ও ব্যবসায় প্রশাসন অনুষদের ডিন প্রফেসর ড. মোহাম্মদ তৈয়ব চৌধুরী। অনুষ্ঠানে সম্মানিত অতিথি ছিলেন চবি প্রক্টর প্রফেসর ড. হোসেন শহীদ সরওয়ার্দী ও চবি মানবসম্পদ ব্যবস্থাপনা বিভাগের সভাপতি প্রফেসর ড. মো. আফতাব উদ্দীন। বক্তব্যে চবি উপ-উপাচার্য (একাডেমিক) প্রফেসর ড. মোহাম্মদ শামীম উদ্দিন খান এমন উদ্যােগের জন্য আয়োজকদের ধন্যবাদ জানান। উপ-উপাচার্য (একাডেমিক) তাঁর বক্তৃতায় শিক্ষার্থীদের উদ্ভাবনী চিন্তা ও নেতৃত্বের পথে এগিয়ে যেতে উৎসাহিত করেন। অনুষ্ঠানে মূল বক্তা হিসেবে উপস্থিত ছিলেন দ্য প্রিমিয়াম হোমস লি. এর ম্যানেজিং ডিরেক্টর ও সিইও মো. মাইনুল হাসান দোলন এবং ইন্টেরিয়র স্টুডিও এর ফাউন্ডার ও সিইও মারুফ লিয়াকত। তারা বাস্তব উদ্যোক্তা জীবন, নেতৃত্বগুণ এবং সাহসী সিদ্ধান্ত গ্রহণের জন্য অনুপ্রেরণামূলক বক্তব্য রাখেন। এসময় অতিথিদের সম্মাননা ক্রেস্ট প্রদান করা হয়। পরবর্তীতে আয়োজিত কুইজ প্রতিযোগিতাতে ১০টি প্রশ্নে বিজয়ীদের পুরস্কার দেওয়া হয়। CUESS এর সভাপতি মুশফিকুর রহমান হৃদয় ও সাধারণ সম্পাদক সাহিব আব্বাস বাহার চৌধুরী সমাপনী বক্তব্যে অতিথি ও অংশগ্রহণকারীদের প্রতি ধন্যবাদ ও কৃতজ্ঞতা জানান।', '694265d5d7d91_1765959125.webp', 'Admin', '2025-01-01', 0, '2 min read', 1, 12, '2025-12-17 06:45:34', '2026-02-10 05:37:46');

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
('tphl-erp-cache-356a192b7913b04c54574d18c28d46e6395428ab:timer', 'i:1770701568;', 1770701568),
('tphl-erp-cache-356a192b7913b04c54574d18c28d46e6395428ab', 'i:1;', 1770701568),
('tphl-erp-cache-6794961354c11716b810580e44dcd7f3136ca276:timer', 'i:1770706125;', 1770706125),
('tphl-erp-cache-6794961354c11716b810580e44dcd7f3136ca276', 'i:3;', 1770706125);

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
  `cashout_request_id` int(11) NOT NULL,
  `office` varchar(200) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
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
(1, 'The Premium Smart City', 1, NULL, '2025-12-18 06:41:23', '2025-12-18 06:44:31'),
(2, 'The Ati Society', 1, NULL, '2025-12-28 11:07:43', '2025-12-28 11:07:43'),
(3, 'The Premium Smart City2', 1, NULL, '2025-12-28 11:08:04', '2025-12-28 11:08:04'),
(4, 'ati society', 1, NULL, '2025-12-28 11:09:36', '2025-12-28 11:09:36'),
(5, 'The Premium Royal City', 1, 17, '2026-02-04 09:17:24', '2026-02-04 09:17:24');

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
(1, 'BRAC Bank', 'TPHL ', '11223342', 1, 'active', 12, '2026-01-03 13:36:02'),
(2, 'City Bank', 'TPHL', '3274894326', 0, 'active', 12, '2026-01-13 17:12:52'),
(3, 'Dhaka Bank', 'Tphl', '2345566', 0, 'active', 12, '2026-01-13 17:14:01');

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
(1, 'Jubair Khan', 'khan@example.com', '+8801712345678', 'Property Inquiry', 'I am interested in your Gulshan apartment. Please contact me.', 'pending', '2026-02-09 02:03:40', '2026-02-09 02:03:40');

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
(1, 'Sales', 1, NULL, '2025-12-07 11:24:44', '2025-12-07 11:24:44'),
(2, '  Marketing  ', 1, NULL, '2025-12-07 11:24:58', '2025-12-07 11:24:58'),
(3, 'Business Development ', 1, NULL, '2025-12-07 11:25:08', '2025-12-07 11:25:08'),
(4, ' Customer Relations', 1, NULL, '2025-12-07 11:25:13', '2025-12-07 11:25:13'),
(5, 'Project Management', 1, NULL, '2025-12-07 11:25:25', '2025-12-07 11:25:25'),
(6, '  Site Operations ', 1, NULL, '2025-12-07 11:25:32', '2025-12-07 11:25:32'),
(8, ' Construction Management', 1, NULL, '2025-12-07 11:25:45', '2025-12-07 11:25:45'),
(9, 'Human Resources', 1, NULL, '2026-01-04 01:26:46', '2026-01-04 01:26:46'),
(10, 'IT', 1, NULL, '2026-01-04 01:27:01', '2026-01-04 01:27:01'),
(11, 'Finance', 1, NULL, '2026-01-04 01:27:37', '2026-01-04 01:27:37'),
(12, 'Administration', 1, NULL, '2026-01-04 01:28:49', '2026-01-04 01:28:49'),
(13, 'Management', 1, NULL, '2026-01-04 01:33:13', '2026-01-04 01:33:13'),
(14, 'NO Department', 1, NULL, '2026-01-04 04:38:14', '2026-01-04 04:38:14');

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
(1, 'Project Manager', 5, NULL, '2025-12-07 11:26:14', '2025-12-07 11:26:14'),
(2, 'Site Engineer', 8, NULL, '2025-12-28 11:13:42', '2025-12-28 11:13:42'),
(3, 'HR Executive', 9, NULL, '2026-01-04 01:27:22', '2026-01-04 01:27:22'),
(4, 'Senior Accountant', 11, NULL, '2026-01-04 01:27:54', '2026-01-04 01:41:23'),
(5, 'Marketing Manager', 2, NULL, '2026-01-04 01:28:06', '2026-01-04 01:28:06'),
(6, 'Sales & Marketing', 1, NULL, '2026-01-04 01:29:07', '2026-02-01 12:16:05'),
(7, 'Marketing Coordinator', 2, NULL, '2026-01-04 01:30:26', '2026-01-04 01:30:26'),
(8, 'Senior Sales Executive', 2, NULL, '2026-01-04 01:33:39', '2026-01-04 01:33:39'),
(9, 'System Administrator', 12, NULL, '2026-01-04 01:39:33', '2026-01-04 01:39:33'),
(10, 'Real Estate Executive', 2, NULL, '2026-01-04 01:39:54', '2026-01-04 01:39:54'),
(11, 'Human Resources Manager', 9, NULL, '2026-01-04 01:40:15', '2026-01-04 01:40:15'),
(12, 'Head of Sales', 2, NULL, '2026-01-04 01:40:45', '2026-01-04 01:40:45'),
(13, 'No Designation', 14, NULL, '2026-01-04 04:38:32', '2026-01-04 04:38:32');

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

--
-- Dumping data for table `emi_schedules`
--

INSERT INTO `emi_schedules` (`id`, `lead_booking_id`, `emi_no`, `emi_amount`, `due_date`, `status`, `lead_payment_id`, `created_at`, `due_emi_amount`, `service_fee`) VALUES
(9, 5, 1, 5555.00, '2025-12-27', 'paid', 29, '2025-12-27 10:40:50', 55.00, NULL),
(10, 5, 2, 54555.00, '2025-12-27', 'paid', 30, '2025-12-27 10:41:30', 33.00, NULL),
(37, 6, 1, 56000.00, '2026-01-30', 'paid', 12, '2026-01-30 14:52:01', 0.00, NULL);

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
(7, NULL, 'Hasan ', '1', 'hasan', 'hasan@gmail.com', '', '01700000000', '0171000000', 'BBA', '<p>gyjhasdt ikf ujsdfy7u hdsa8fy</p>', 'Dhaka', '', NULL, 5, 1, '1999-12-01', '2025-01-01', 25000, 'Hasan', '1111111111111', 0, '', 'Dhaka Bank', 'Dhaka', '123', '123', '', '693d3e8496d75_1765621380.jpg', NULL, 'active', NULL, NULL, '2025-12-08 05:06:42', '2025-12-30 11:30:17'),
(8, NULL, 'Shihan2', '12', 'shihan', 'shihan@gmail.com', 'Shahid', '01805997399', '01805997399', 'MBA', '<p>iuydsr</p>', 'Dhaka', '', NULL, 1, 1, '2026-01-08', '2026-01-08', 30000, 'Abc', '01983274', 1, '', 'abc', '', '832764', '87436', '', '693d40fdf0513_1765622013.png', NULL, 'active', 12, NULL, '2025-12-10 07:28:56', '2026-01-08 04:26:45'),
(13, 4, 'Mr.X', '90', 'X', 'x@gmail.com', 'Y', '01712000000', '01712000000', 'MBA', '<p>weqrewtr</p>', 'Dhaka', '', NULL, 3, 1, '0000-00-00', '0000-00-00', 20000, 'Abc', 'qwer', 1, '123456', 'weq', '', '832764', '87436', '', '693d4070c78b1_1765621872.png', NULL, 'active', NULL, NULL, '2025-12-10 09:09:59', '2025-12-30 11:30:26'),
(14, 5, 'Hamid', '04', 'hamid', 'hamid@gmail.com', 'hamim', '01809999999', '01809999999', 'MBA', '<p>jdfgur kjdf kjdfhg8uer</p>', 'Dhaka', '', NULL, 1, 1, '2000-12-14', '0000-00-00', NULL, 'Abc', '01983274', 1, '123456', 'abc', '', '832764', '87436', '', '693d3fe2c332d_1765621730.png', NULL, 'active', NULL, NULL, '2025-12-10 09:42:03', '2025-12-13 10:28:50'),
(26, NULL, 'Farhan', '13', 'Farhan', 'farhan@gmail.com', 'aziz', '01790120923', '01805997399', 'MBA', '<p>fsdygiii</p>', 'Dhaka', '', NULL, 1, 1, '1990-01-01', '2025-01-01', 20000, 'Farhan', '98234734', 1, '$2y$10$96BGmKUmreuE95rOQ8T6NehOqMlmOT0IkpoUBQuaHkEb9wiWfJvIC', 'Dhaka', 'Dhaka', 'www.google.com', 'www.google.com', '', '693d3fc682ed5_1765621702.png', NULL, 'active', 12, NULL, '2025-12-10 12:28:53', '2026-01-08 11:33:57'),
(27, NULL, 'Mehedi Hasan Jony', '123', '@jony', 'jony@gmail.com', 'Unknown', '01786777777', '01786777777', 'MBA', '<p>No description</p>', '23\r\nSEL HUQ SKYPARK\r\n2 Mirpur Rd', '', NULL, 3, 1, '2025-01-01', '2025-01-01', 700000, 'Mehedi H. Jony', '615234', 0, '', 'City Bank', '', 'www.facebbok.com', 'www.linkedin.com', '', '693d4f012d0cd_1765625601.jpg', NULL, 'active', 12, NULL, '2025-12-13 11:33:01', '2026-01-08 04:28:24'),
(29, 13, 'Nusrat', '189', 'Nusrat', 'nusrat@gmail.com', 'Nur', '01789000000', '01789000000', 'MBA', '<p>jkfdh kdf</p>', '23\r\nSEL HUQ SKYPARK\r\n2 Mirpur Rd', '', NULL, 1, 1, '1999-01-01', '2026-01-07', 25000, 'Nusrat', '1263214324', 1, '$2y$10$..eFW2QctwO7qH3KucZSfuRQEeYudFY.citf5I0FJewqOsJWRAQ2W', 'Dhaka Bank', '', 'www.google.com', 'www.linkedin.com', '', '693e5a6b64a45_1765694059.png', NULL, 'active', 12, NULL, '2025-12-14 06:34:19', '2026-01-08 04:28:02'),
(32, 14, 'Farhana', '1827', 'Farhana', 'farhana@gmail.com', 'hamim', '01700000000', '0171000000', 'BSC', '<p>rjeiouort</p>', 'Dhaka', '', '2345676', 1, 1, '1999-01-07', '2026-01-01', 30000, 'Abc', '1111111111111', 1, '$2y$10$lxw8JSnTFc75E7i0QyuKEuxgedIf2l7E/y94jxlnZzG2EjloA2TYu', 'Dhaka Bank', '', 'www.google.com', 'www.linkedin.com', '', '693e781139293_1765701649.jpg', 'permanent', 'active', 12, NULL, '2025-12-14 08:40:51', '2026-01-13 11:16:03'),
(34, NULL, 'Sadia', 'EMP-00034', 'Sadia', 'sadia@gmail.com', 'Nur', '01790128473', '01790128473', 'MBA', '', 'Mohammadpur, Dhaka-1207\r\nMohammadpur, Dhaka-1207', '', '2345676', 2, 8, '2026-02-10', '1990-02-13', 40000, '', '', 0, '', '', '', '', '', '', '', 'permanent', 'active', 12, NULL, '2026-02-10 12:30:08', '2026-02-10 12:30:08'),
(35, NULL, 'Himu', 'EMP-00035', '', 'himu@gmail.com', 'Himel', '03289547834', '', 'CSE', '', '', '', '', 1, 2, NULL, '2026-02-13', 40000, '', '', 1, '$2y$10$1ez21kMQqHNIrh4SfAMaFOgzSLvUKfgrLPlSVE7Dar459c8d2zcui', 'Dhaka Bank', 'Dhaka', '', '', '', '', 'permanent', 'active', 12, NULL, '2026-02-13 16:54:10', '2026-02-13 16:54:10');

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

--
-- Dumping data for table `employee_leaves`
--

INSERT INTO `employee_leaves` (`id`, `employee_id`, `leave_type_id`, `start_date`, `end_date`, `requested_days`, `approved_days`, `approval_status`, `reason`, `created_at`, `updated_at`) VALUES
(1, 7, 1, '2026-01-28', '2026-01-30', 3, 2, 'partial', 'Personal work', '2026-01-27 11:27:56', '2026-01-29 15:54:07'),
(2, 8, 2, '2026-01-01', '2026-01-31', 15, 0, 'rejected', 'hhh', '2026-01-30 06:50:05', '2026-01-31 06:12:41'),
(3, 19, 2, '2026-02-08', '2026-02-08', 1, 0, 'pending', 'test', '2026-02-08 12:06:01', '2026-02-08 12:06:01'),
(4, 17, 2, '2026-02-08', '2026-02-09', 2, 0, 'pending', 'test', '2026-02-08 12:12:10', '2026-02-08 12:12:10'),
(5, 35, 1, '2026-02-14', '2026-02-14', 1, 0, 'pending', 'test', '2026-02-13 16:57:03', '2026-02-13 16:57:03');

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
(1, 7, 1, 18, '2026-01-29 15:54:07', '2026-01-29 15:54:07'),
(5, 35, 1, 10, '2026-02-13 16:54:10', '2026-02-13 16:54:10'),
(6, 35, 2, 15, '2026-02-13 16:54:10', '2026-02-13 16:54:10'),
(7, 35, 3, 0, '2026-02-13 16:54:10', '2026-02-13 16:54:10');

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

--
-- Dumping data for table `emp_attendances`
--

INSERT INTO `emp_attendances` (`id`, `employee_id`, `attendance_date`, `punch_in_time`, `punch_out_time`, `today_status`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 7, '2026-01-21', '2026-01-21 03:02:00', '2026-01-21 11:30:00', 'Present', 12, '2026-01-21 04:15:50', '2026-01-21 07:13:28', NULL),
(2, 13, '2026-01-21', NULL, NULL, 'Absent', 12, '2026-01-21 07:41:03', '2026-01-21 08:02:24', '2026-01-21 03:02:24'),
(3, 8, '2026-01-21', '2026-01-21 03:00:00', NULL, 'Present', 12, '2026-01-21 10:59:52', '2026-01-21 10:59:52', NULL),
(4, 7, '2026-01-22', '2026-01-22 03:00:00', NULL, 'Present', 12, '2026-01-21 11:00:20', '2026-01-21 11:00:20', NULL),
(5, 14, '2026-01-22', '2026-01-22 03:01:00', NULL, 'Present', 12, '2026-01-21 11:00:41', '2026-01-21 11:00:41', NULL),
(6, 14, '2026-01-31', '2026-01-31 03:05:00', NULL, 'Present', 12, '2026-01-31 04:25:47', '2026-01-31 04:25:47', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `flats`
--

CREATE TABLE `flats` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `unit_id` int(11) DEFAULT NULL,
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

INSERT INTO `flats` (`id`, `project_id`, `unit_id`, `sqft`, `bhk`, `land_share_value`, `bedroom`, `bathroom`, `balcony`, `drawing_dining_kitchen`, `common_dining`, `floor_no`, `flat_name`, `facing_site`, `price_value`, `facilities`, `created_by`, `status`, `created_at`, `deleted_at`, `updated_at`, `is_booked`) VALUES
(1, 1, 1, 240, 2, NULL, 3, 2, 2, 0, 0, 5, 'Unit-A5', '0', 40000000.00, 'Gym', NULL, 'booked', '2025-12-18 11:58:43', NULL, '2025-12-18 12:06:55', 1),
(2, 2, 2, 200, 3, NULL, 2, 2, 1, 1, 1, 3, 'Unit-A3', '0', 4000000.00, 'Gym', NULL, 'booked', '2025-12-20 07:21:57', NULL, '2025-12-20 07:21:57', 1),
(3, 2, 7, 1200, 3, NULL, 1, 2, 1, 1, 1, 1, 'Unit-A', '0', 6000000.00, 'Gym', NULL, 'available', '2025-12-22 08:06:06', NULL, '2025-12-22 08:06:06', 0),
(4, 2, 5, 1200, 12, NULL, 3, 2, 2, 1, 1, 2, 'Unit-B2', '0', 50000000.00, 'Gym', NULL, 'available', '2025-12-23 10:53:59', NULL, '2025-12-23 10:53:59', 0),
(5, 1, 8, 1400, 2, NULL, 3, 3, 2, 1, 1, 2, 'Unit-A2', '0', 2000000.00, 'Gym', NULL, 'available', '2025-12-23 10:56:45', NULL, '2025-12-23 10:56:45', 0),
(6, 3, 4, 1300, 3, NULL, 3, 3, 3, 1, 1, 1, 'Unit-A', '0', 5000000.00, 'Gym', NULL, 'booked', '2025-12-23 11:00:45', NULL, '2025-12-23 11:00:45', 1),
(7, 3, NULL, 1200, 2, 3000000, 2, 1, 1, 1, 1, 4, 'Unit - B4', 'south', 5000000.00, '', 12, 'available', '2026-01-14 06:10:19', NULL, '2026-01-14 06:10:19', 0),
(8, 4, NULL, 1200, 3, NULL, 3, 2, 1, 1, 1, 1, 'unit-3', '0', 1.00, 'Gym', 12, 'available', '2026-01-14 11:25:14', '2026-01-14 11:33:12', '2026-01-14 11:25:14', 0);

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

--
-- Dumping data for table `flat_purchase_rules`
--

INSERT INTO `flat_purchase_rules` (`id`, `rules_description`, `declearation`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 'The whole construction cost will be paid by all the shareholders in a step-by-step manner as per the decision.', NULL, 1, NULL, '2025-12-31 13:12:21', '2025-12-31 13:12:21'),
(3, 'If someone is unable to pay the full price of the land within the stipulated time, his flat allotment will be canceled and the remaining amount will be refunded after deducting 15% of the office service charge. The flat may be allotted elsewhere. (Negotiable).', NULL, 1, NULL, '2025-12-31 13:12:21', '2025-12-31 13:12:21'),
(4, 'Shareholders shall transfer the power and authority to construct the building to The Premium Homes Ltd.', NULL, 1, NULL, '2025-12-31 13:12:21', '2025-12-31 13:12:21'),
(5, '% of the construction cost will be paid as a service charge to the authority for implementation and supervision of construction work. The said percentage will be accepted as logistic support only.', NULL, 1, NULL, '2025-12-31 13:12:21', '2025-12-31 13:12:21'),
(6, 'The authority reserves the right to make necessary changes in the design and layout of the project at any time due to unavoidable reasons.', NULL, 1, NULL, '2025-12-31 13:12:21', '2025-12-31 13:12:21'),
(7, 'Both parties shall endeavor to complete the whole project within months from the commencement of construction work.', NULL, 1, NULL, '2025-12-31 13:12:21', '2025-12-31 13:12:21'),
(8, 'If the transfer of flats is delayed due to political, natural calamities, government restrictions, it should be accepted in the larger interest of the project.', NULL, 1, NULL, '2025-12-31 13:12:21', '2025-12-31 13:20:16'),
(9, 'Shareholders can book flats according to their choice on a first-come, first-served basis. The proposed building will have a basement and ground floor parking, and parking will be allocated for each flat. Additional Tk. 00 will be paid by the shareholder during the construction period for each parking space with the same installment', NULL, 1, NULL, '2025-12-31 13:24:38', '2025-12-31 13:24:38'),
(10, 'Both parties shall endeavor to complete the whole project within months from the commencement of construction work.', NULL, 1, NULL, '2025-12-31 13:24:38', '2025-12-31 13:24:38'),
(11, 'If the transfer of flats is delayed due to political, natural calamities, government restrictions, it should be accepted in the larger interest of the project.', NULL, 1, NULL, '2025-12-31 13:24:38', '2025-12-31 13:24:38'),
(12, 'If 03 (three) installments or equivalent amount of 03 (three) installments are outstanding during the construction period, the flat allotted to the shareholder shall be deemed to be canceled, and the flat shall be transferred to The Premium Homes Ltd. You will be obliged to register elsewhere under the supervision of the authorities. No objection will be accepted in this regard.', NULL, 1, NULL, '2025-12-31 13:24:38', '2025-12-31 13:24:38'),
(13, 'The allocated flat can be sold to anyone. If preferred, it may be sold through the authority, in which case a service charge will be applicable. The cost of ownership transfer and Mutation must be borne by the seller.', NULL, 1, NULL, '2025-12-31 13:24:38', '2025-12-31 13:24:38'),
(14, 'The decision of the authority in any matter shall be final.', NULL, 1, NULL, '2025-12-31 13:24:38', '2025-12-31 13:24:38'),
(15, 'If the flat cannot be handed over within the stipulated months due to political issues, natural calamities, government restrictions, unforeseen accidents, unexpected delays, or the Client’s failure to make timely installment payments, the next course of action shall be mutually determined by both parties; however, if the delay occurs due to negligence on the part of The Premium Homes Ltd, and the Company fails to complete the construction and hand over the Project within months, the completion period may be extended by an additional 6 months, and if the Company still fails to deliver the Project within a total of months (+ 6 months), The Premium Homes Ltd shall be obliged to compensate the Client with rent for the flat based on the number of flats and prevailing market value at the time.', NULL, 1, NULL, '2025-12-31 13:24:38', '2025-12-31 13:24:38');

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
  `id` int(10) UNSIGNED NOT NULL,
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
  `project_type` enum('ATI Society','ASHU Society') DEFAULT NULL,
  `pref_flat_size` varchar(250) DEFAULT NULL,
  `communication_type` enum('WA Messaging','WA Conversion') DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `visibility` enum('public','private','selected') DEFAULT 'public',
  `status` enum('active','inactive') DEFAULT 'active',
  `last_lead_action` enum('not_called','calling','called','visited') DEFAULT NULL,
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
  `has_pending_followup` tinyint(1) DEFAULT 0 COMMENT '1 = follow-up pending, 0 = no pending follow-up'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leads`
--

INSERT INTO `leads` (`campaign_name`, `platform`, `which_apartment_size_are_you_interested_in`, `which_floor_are_you_for_shop`, `when_are_you_planning_to_buy`, `would_you_like_to_schedule_a_site_visit`, `when_is_the_best_time_to_contact_you`, `full_name`, `job_title`, `company_name`, `phone_number`, `call_status`, `call_note`, `city`, `id`, `lead_name`, `lead_type`, `company`, `value_amount`, `currency`, `phone`, `alt_phone_number`, `email`, `source_type_id`, `assigned_id`, `address`, `father_name`, `mother_name`, `dob`, `nid_or_passport`, `present_add_bn`, `permanent_add_bn`, `identity_type`, `permanent_address`, `pref_location_id`, `designation`, `project_type`, `pref_flat_size`, `communication_type`, `tags`, `description`, `visibility`, `status`, `last_lead_action`, `deleted_at`, `lead_quality`, `lead_image`, `lead_name_bn`, `father_name_bn`, `mother_name_bn`, `profession_bn`, `nationality`, `religion`, `nominee_name`, `nomi_father_name`, `nomi_mother_name`, `nomi_profession`, `relation_with_lead`, `nomi_dob`, `nomi_nid_passport`, `nomi_phone`, `nomi_email`, `nomi_present_add`, `nomi_permanent_address`, `nomi_image`, `created_by`, `created_at`, `updated_at`, `residency_type`, `country`, `preferred_contact_time`, `has_pending_followup`) VALUES
(NULL, NULL, NULL, NULL, NULL, '0', NULL, 'Hasin', NULL, NULL, NULL, 'not_called', NULL, NULL, 2, 'Hasin', 'Hot', 'Company X', 10000000.00, 'BDT', '01700000000', '0180000000', 'hasan@gmail.com', 1, 13, 'Dhaka', NULL, NULL, NULL, NULL, NULL, NULL, 'NID', NULL, 1, 'Project Manager', 'ATI Society', '345 sqf', 'WA Messaging', 'No nedd', 'fghyt uiy6we', 'public', 'active', NULL, NULL, 'Prospect', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-11 07:07:51', '2026-02-02 11:52:22', 'local', NULL, NULL, 0),
(NULL, NULL, NULL, NULL, NULL, '0', NULL, 'Hasan', NULL, NULL, NULL, 'not_called', NULL, NULL, 4, 'Hasin2', 'Hot', 'Company X', 10000000.00, 'BDT', '01700000000', '0180000000', 'hasan@gmail.com', 1, 13, '0', NULL, NULL, NULL, NULL, NULL, NULL, 'NID', NULL, 1, 'Project Manager', 'ATI Society', '345 sqf', 'WA Messaging', 'No nedd', '', 'public', 'active', NULL, NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-11 09:56:45', '2026-02-02 11:52:26', 'local', NULL, NULL, 0),
(NULL, NULL, NULL, NULL, NULL, '0', NULL, 'MH. Jony', NULL, NULL, NULL, 'not_called', NULL, NULL, 6, 'MH. Jony', 'Hot', 'Company X', NULL, 'BDT', '01700000000', '0180000000', 'hasan@gmail.com', 1, 12, 'Dhaka', NULL, NULL, NULL, NULL, NULL, NULL, 'NID', NULL, 1, 'Project Manager', 'ATI Society', '345 sqf', 'WA Messaging', 'No nedd', '', 'public', 'active', NULL, NULL, 'Not Interested', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-20 08:11:43', '2026-02-02 11:52:30', 'local', NULL, NULL, 0),
(NULL, NULL, NULL, NULL, NULL, '0', NULL, 'Rana', NULL, NULL, NULL, 'not_called', NULL, NULL, 7, 'Rana', 'Cold', '', NULL, 'BDT', '01944667441', '01944667441', 'abcd@gmail.com', 1, 3, 'fasdf ', NULL, NULL, NULL, NULL, NULL, NULL, 'NID', NULL, 1, 'afd', 'ATI Society', '3a', 'WA Messaging', 'asdf', '', 'public', 'active', NULL, NULL, 'Prospect', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-28 11:18:31', '2026-02-02 11:52:33', 'local', NULL, NULL, 0),
(NULL, NULL, NULL, NULL, NULL, '0', NULL, 'ABDUL AZIZ', NULL, NULL, NULL, 'not_called', NULL, NULL, 8, 'ABDUL AZIZ', 'Hot', '', NULL, 'BDT', '+393295745292', '', 'azizabdulspid@gmail.com', 1, 15, '0', 'NURUL ISLAM', 'KULSUM BEGUM', '1998-01-28', 'ej0991174', NULL, NULL, 'NID', 'VILLAGE:DANNAPUR, WARD:06,DUTTAPARA,LAKSHIMIPUR SADAR,LAKSHIMPUR', NULL, 'JOB', 'ATI Society', '', 'WA Messaging', '', NULL, 'public', 'active', 'visited', NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-31 11:47:12', '2026-02-02 11:54:24', 'local', NULL, NULL, 0),
(NULL, NULL, NULL, NULL, NULL, '0', NULL, 'Rumman', NULL, NULL, NULL, 'not_called', NULL, NULL, 9, 'Rumman', 'Hot', NULL, NULL, 'BDT', '01743579987', NULL, 'aziz@gmail.com', 1, 12, 'Itali', 'NURUL ISLAM', 'KULSUM', '2025-12-23', '98233728', '????? ???????, ????? ?/?, ???????, ?????', '?????? ???????, ??????? ??, ??????????, ?????????? ???, ??????????', 'NID', 'Loxipur', 2, 'Job', 'ATI Society', '1230', 'WA Messaging', '', NULL, 'public', 'active', NULL, NULL, 'Prospect', 'leads_695632097012e.png', '?????? ????', '????? ?????', '?????? ????', '?????', 'Bangladeshi', 'Islam', 'EVA ALAM', 'KHURSHED', 'AYSHA ALAM', 'housewife', 'Wife', '2026-01-02', '12364743', '0187643875', '', '', 'Dhaka', 'leads_69563209746dc.jpg', 12, '2025-12-31 02:43:03', '2026-02-02 11:52:38', 'local', NULL, NULL, 0),
(NULL, NULL, NULL, NULL, NULL, '0', NULL, 'Mr.X', NULL, NULL, NULL, 'not_called', NULL, NULL, 10, 'Mr.X', 'Hot', NULL, NULL, 'BDT', '0171212323', NULL, 'x2@gmail.com', 1, 3, 'Dhaka', 'Mr.Y', 'Mst.X', '1998-01-01', '1234345', '', '', 'NID', 'Dhaka', 1, 'Job', 'ASHU Society', '1250px', 'WA Messaging', 'No Notes', NULL, 'public', 'active', 'visited', NULL, 'Prospect', NULL, '', '', '', '', 'Bangladeshi', 'Islam', '', '', '', '', '', NULL, '', '', '', '', '', NULL, 12, '2026-01-12 11:12:01', '2026-02-02 11:52:44', 'local', NULL, NULL, 0),
(NULL, NULL, NULL, NULL, NULL, '0', NULL, 'Taimul', NULL, NULL, NULL, 'not_called', NULL, NULL, 11, 'Taimur', 'Hot', NULL, NULL, 'BDT', '01790128473', '01790128473', 'test23@gmail.com', 1, 13, '0', 'Test Lead 2', 'Test Lead 2', '1990-01-03', '98233728', NULL, NULL, 'NID', 'Mohammadpur, Dhaka-1207', 1, 'Project Manager', 'ATI Society', '1230', 'WA Messaging', 'No Need', NULL, 'public', 'inactive', NULL, '2026-01-14 11:31:18', 'Prospect', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 12, '2026-01-14 11:14:59', '2026-02-02 11:52:51', 'local', NULL, NULL, 0),
('P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025', 'fb', '1550_sqft', '4', '6-12_months', 'yes!', 'afternoon_(12_pm_-_5_pm)', 'Sohel Khan', 'propriter', 'beauty sportes and net company', 'p:+8801840990890', 'not_called', NULL, 'Dhaka', 12, NULL, NULL, NULL, NULL, 'BDT', '0171212323', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'public', '', NULL, NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-30 04:36:45', '2026-01-30 11:19:51', 'local', NULL, NULL, 0),
('P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025', 'fb', '1550_sqft', '5', '6-12_months', 'yes!', 'afternoon_(12_pm_-_5_pm)', 'Jahirul Islam', 'job', 'xyz', 'p:+8801711949142', 'not_called', NULL, 'Dhaka', 13, NULL, NULL, NULL, NULL, 'BDT', '0171212345', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'public', '', NULL, NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-30 04:36:45', '2026-01-30 11:20:00', 'local', NULL, NULL, 0),
('P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025', 'fb', '3100_sqft', '6', '6-12_months', 'yes!', 'evening_(5_pm_-_8_pm)', 'Soykot Islam Shihab', 'Owner', 'Amin Mistanno Vhander', 'p:+8801752697272', 'called', NULL, 'Uttarati', 14, NULL, NULL, NULL, NULL, 'BDT', '0171212089', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'public', '', NULL, NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-30 04:36:45', '2026-01-30 11:20:07', 'local', NULL, NULL, 0),
('P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025', 'fb', '1550_sqft', '7', '3-6_months', 'yes!', 'afternoon_(12_pm_-_5_pm)', 'Mahfuzur Rahman', 'MD, MR Income Tax Consultancy Ltd', 'M R Income Tax Consultancy Ltd.', 'p:+8801916158171', 'not_called', NULL, 'Dhaka', 15, NULL, NULL, NULL, NULL, 'BDT', '0171212323', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'public', '', NULL, NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-30 04:36:45', '2026-01-30 11:20:09', 'local', NULL, NULL, 0),
('P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025', 'fb', '1550_sqft', '8', '3-6_months', 'yes!', 'afternoon_(12_pm_-_5_pm)', 'Shahadat ', 'Business management', '72 mollertack, ashkona,dokhinkhan,dhaka', 'p:+8801781860471', 'not_called', NULL, 'Dhaka', 16, NULL, NULL, NULL, NULL, 'BDT', '0171212323', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'public', '', NULL, NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-30 04:36:45', '2026-01-30 11:20:15', 'local', NULL, NULL, 0),
('P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025', 'fb', '1550_sqft', '1', '1-3_months', 'no!', 'evening_(5_pm_-_8_pm)', 'Raihan', 'a', 'a', 'p:+8801736936727', 'not_called', NULL, 'Dhaka', 17, NULL, NULL, NULL, NULL, 'BDT', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'public', '', NULL, NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-31 12:42:01', '2026-01-31 12:42:01', 'local', NULL, NULL, 0),
('P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025', 'fb', '1550_sqft', '2', '6–12_months', 'yes!', 'afternoon_(12_pm_-_5_pm)', 'Enam Pathan', 'owner', 'Jet Sports', 'p:+8801717221212', 'not_called', NULL, 'Jatramura', 18, NULL, NULL, NULL, NULL, 'BDT', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'public', '', NULL, NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-31 12:42:01', '2026-01-31 12:42:01', 'local', NULL, NULL, 0),
('P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025', 'fb', '1550_sqft', '3', '1-3_months', 'yes!', 'afternoon_(12_pm_-_5_pm)', 'Forhad Ali', 'AGM', 'Flamingo Ltd', 'p:+8801730440638', 'not_called', NULL, 'Dhaka', 19, NULL, NULL, NULL, NULL, 'BDT', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'public', '', NULL, NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-31 12:42:01', '2026-01-31 12:42:01', 'local', NULL, NULL, 0),
('P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025', 'fb', '1550_sqft', '4', '6–12_months', 'yes!', 'afternoon_(12_pm_-_5_pm)', 'Sohel Khan', 'propriter', 'beauty sportes and net company', 'p:+8801840990890', 'not_called', NULL, 'Dhaka', 20, NULL, NULL, NULL, NULL, 'BDT', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'public', '', NULL, NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-31 12:42:01', '2026-01-31 12:42:01', 'local', NULL, NULL, 0),
('P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025', 'fb', '1550_sqft', '5', '6–12_months', 'yes!', 'afternoon_(12_pm_-_5_pm)', 'Jahirul Islam', 'job', 'xyz', 'p:+8801711949142', 'not_called', NULL, 'Dhaka', 21, NULL, NULL, NULL, NULL, 'BDT', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'public', '', NULL, NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-31 12:42:01', '2026-01-31 12:42:01', 'local', NULL, NULL, 0),
('P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025', 'fb', '3100_sqft', '6', '6–12_months', 'yes!', 'evening_(5_pm_-_8_pm)', 'Soykot Islam Shihab', 'Owner', 'Amin Mistanno Vhander', 'p:+8801752697272', 'not_called', NULL, 'Uttarati', 22, NULL, NULL, NULL, NULL, 'BDT', NULL, NULL, NULL, 0, 19, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'public', '', NULL, NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-31 12:42:01', '2026-01-31 12:47:13', 'local', NULL, NULL, 0),
('P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025', 'fb', '1550_sqft', '7', '3–6_months', 'yes!', 'afternoon_(12_pm_-_5_pm)', 'Mahfuzur Rahman', 'MD, MR Income Tax Consultancy Ltd', 'M R Income Tax Consultancy Ltd.', 'p:+8801916158171', 'not_called', NULL, 'Dhaka', 23, NULL, NULL, NULL, NULL, 'BDT', NULL, NULL, NULL, 0, 19, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'public', '', NULL, NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-31 12:42:01', '2026-01-31 12:47:13', 'local', NULL, NULL, 0),
('P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025', 'fb', '1550_sqft', '8', '3–6_months', 'yes!', 'afternoon_(12_pm_-_5_pm)', 'Shariful Islam Sharif', 'Business management', '72 mollertack, ashkona,dokhinkhan,dhaka', 'p:+8801781860471', 'not_called', NULL, 'Dhaka', 24, NULL, NULL, NULL, NULL, 'BDT', NULL, NULL, NULL, 0, 19, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'public', '', NULL, NULL, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-31 12:42:01', '2026-01-31 12:47:13', 'local', NULL, NULL, 0),
('', 'fb', '1500', '', '', '', '', 'Merin', 'business', '', '01790126554', 'not_called', NULL, 'Dhaka', 25, NULL, 'Hot', NULL, NULL, 'BDT', NULL, '01790126554', '', 1, 19, 'Mohammadpur, Dhaka-1207', 'Sadik', 'sadika', '2026-02-02', '', NULL, NULL, NULL, 'Mohammadpur, Dhaka-1207', NULL, '', 'ATI Society', '', 'WA Messaging', '', NULL, 'public', 'active', NULL, NULL, 'Prospect', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 12, '2026-02-02 11:54:44', '2026-02-02 11:54:44', 'local', NULL, NULL, 0),
('', '', 'Ati Royal City', '3', '', '1', '', 'Lima Jahan', '', '', '01790128422', 'not_called', NULL, 'Dhaka', 26, NULL, 'Hot', NULL, NULL, 'BDT', NULL, '01790128422', 'lima3@gmail.com', 1, 19, 'Mohammadpur, Dhaka-1207', '', '', '1990-02-01', '', NULL, NULL, NULL, 'Mohammadpur, Dhaka-1207', NULL, '', 'ATI Society', '1500', 'WA Messaging', '', NULL, 'public', 'active', NULL, NULL, 'Not Interested', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 12, '2026-02-04 08:54:56', '2026-02-04 08:54:56', 'local', NULL, NULL, 0),
('', '', '', '', '2026-02-13', '1', '', 'M Rahman', '', '', '01977666525', 'called', 'kotha hoyeche \n\n\n', '', 27, NULL, 'Hot', NULL, NULL, 'BDT', NULL, '', '', 0, 19, '', 'F Rahaman', 'M Rahman', '1995-02-02', '87654322334', NULL, NULL, NULL, '', NULL, '', 'ATI Society', '', 'WA Messaging', '', NULL, 'public', 'active', 'called', NULL, 'Prospect', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 17, '2026-02-04 09:21:36', '2026-02-04 09:34:48', 'local', NULL, NULL, 0);

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

--
-- Dumping data for table `lead_bookings`
--

INSERT INTO `lead_bookings` (`id`, `lead_id`, `project_id`, `flat_id`, `agreement_value`, `discount_amount`, `booking_date`, `booking_status`, `created_by`, `sales_by_id`, `deleted_at`, `created_at`, `updated_at`, `approval_status`, `approved_amount`, `service_fee_balance`, `landshare_status`) VALUES
(1, 2, 2, 2, 2000000.00, 0, '2025-12-21', 'completed', 12, 19, NULL, '2025-12-21 10:13:37', '2025-12-21 10:13:37', 'not_required', NULL, NULL, 'active'),
(2, 4, 1, 1, 3000000.00, 0, '2025-12-21', 'completed', 12, 19, NULL, '2025-12-21 11:25:54', '2025-12-21 11:25:54', 'not_required', NULL, NULL, 'active'),
(5, 6, 2, 3, 2000000.00, 0, '2025-12-23', 'completed', 12, NULL, NULL, '2025-12-23 08:29:43', '2025-12-23 08:29:43', 'not_required', NULL, NULL, 'active'),
(6, 8, 2, 4, 7000000.00, 0, '2025-12-31', 'completed', 12, NULL, NULL, '2025-12-31 11:51:30', '2025-12-31 11:51:30', 'not_required', NULL, NULL, 'active'),
(7, 9, 3, 6, 8000000.00, 0, '2026-01-01', 'completed', 12, NULL, NULL, '2026-01-01 11:33:59', '2026-01-01 11:33:59', 'not_required', NULL, NULL, 'active'),
(12, 10, 3, 6, 600000.00, 40000, '2026-01-27', 'booked', 12, NULL, NULL, '2026-01-27 10:55:45', '2026-01-27 10:55:45', 'approved', NULL, NULL, 'active'),
(13, 12, 3, 6, 400000.00, 10000, '2026-01-30', 'booked', 12, NULL, NULL, '2026-01-30 11:15:04', '2026-01-30 11:15:04', 'approved', NULL, NULL, 'active'),
(14, 25, 3, 6, 300000.00, 10000, '2026-02-02', 'booked', 12, 19, NULL, '2026-02-02 11:57:33', '2026-02-02 11:57:33', 'approved', 10000.00, NULL, 'active'),
(15, 26, 3, 6, 200000.00, 10000, '2026-02-04', 'booked', 12, 19, NULL, '2026-02-04 08:55:34', '2026-02-04 08:55:34', 'pending', NULL, NULL, 'active'),
(16, 23, 3, 6, 3000000.00, NULL, '2026-02-04', 'booked', 12, 19, NULL, '2026-02-04 09:20:25', '2026-02-04 09:20:25', 'not_required', NULL, NULL, 'active'),
(17, 27, 3, 6, 1200000.00, NULL, '2026-02-04', 'completed', 17, 19, NULL, '2026-02-04 09:26:03', '2026-02-04 09:26:03', 'not_required', NULL, NULL, 'active');

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
  `status` enum('pending','completed','missed') DEFAULT 'pending',
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
(1, 4, 1, 1, '2025-12-20', '16:10:00', 'visit', 'pending', 'hjysdfure', '2025-12-20', NULL, 'booking', 1, NULL, '2025-12-20 07:10:42', '2025-12-20 09:42:04'),
(2, 6, 2, 2, '2025-12-19', '15:45:00', 'call', 'pending', '', '2025-12-18', NULL, 'site visit', 1, NULL, '2025-12-20 07:45:18', '2025-12-20 09:42:13'),
(3, 6, 2, 2, '2025-12-20', '04:13:00', 'call', 'pending', '', '2025-12-20', NULL, 'site visit', 1, NULL, '2025-12-20 08:13:09', '2025-12-20 08:13:09'),
(4, 2, NULL, 2, '2025-12-20', '17:24:00', 'visit', 'pending', '', '2025-12-20', NULL, 'booking', 1, NULL, '2025-12-20 11:24:43', '2025-12-20 11:24:43'),
(5, 7, NULL, 6, '2025-12-28', '17:26:00', 'call', 'pending', 'No decision', '2026-01-28', NULL, 'call_back', 1, NULL, '2025-12-28 11:26:35', '2025-12-28 11:26:35'),
(8, 10, 3, 6, '2026-01-13', '17:35:00', 'call', 'completed', '', '2026-01-14', NULL, 'site visit', 12, NULL, '2026-01-13 11:36:03', '2026-01-13 11:36:03'),
(9, 11, 4, 8, '2026-01-13', '17:26:00', 'call', 'completed', 'no need', '2026-01-14', NULL, 'site visit', 12, '2026-01-14 11:33:12', '2026-01-14 11:26:54', '2026-01-14 11:33:12'),
(10, 16, 2, 3, '2026-01-29', '13:01:00', 'call', 'completed', '', '2026-01-30', NULL, 'site visit', 17, NULL, '2026-01-30 05:01:22', '2026-01-30 05:01:22'),
(11, 25, 3, 7, '2026-02-02', '17:56:00', 'call', 'completed', 'No', '2026-02-03', 19, 'booking', 12, NULL, '2026-02-02 11:56:40', '2026-02-02 11:56:40'),
(12, 27, 3, 6, '2026-02-04', '15:22:00', 'call', 'completed', '', '2026-02-12', 19, 'site visit', 17, NULL, '2026-02-04 09:23:07', '2026-02-04 09:23:07');

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

--
-- Dumping data for table `lead_payments`
--

INSERT INTO `lead_payments` (`id`, `booking_id`, `payment_type`, `amount`, `land_share_amount`, `payment_method`, `payment_status`, `payment_date`, `emi_no`, `total_emi_no`, `transaction_ref`, `bank_name`, `bank_account_no`, `cheque_no`, `service_fee`, `online_type`, `created_by`, `created_at`) VALUES
(1, 1, 'booking_money', 20000000.00, 0.00, 'bank', 'paid', '2025-12-21', NULL, NULL, '12345', NULL, NULL, NULL, NULL, NULL, 12, '2025-12-21 11:01:25'),
(5, 2, 'booking_money', 3000000.00, 0.00, 'cash', 'paid', '2025-12-22', NULL, NULL, '123456', NULL, NULL, NULL, NULL, NULL, 12, '2025-12-22 08:56:21'),
(7, 5, 'booking_money', 2000000.00, 0.00, 'bank', 'paid', '2025-12-23', NULL, NULL, '122334', NULL, NULL, NULL, NULL, NULL, 12, '2025-12-23 08:32:22'),
(9, 6, 'booking_money', 6000000.00, 0.00, 'cheque', 'paid', '2025-12-31', NULL, 48, '122334', 'BRAC Bank', '', '24343465', NULL, 'bKash', 12, '2025-12-31 11:52:21'),
(10, 7, 'booking_money', 8000000.00, 0.00, 'bank', 'paid', '2026-01-01', NULL, 45, '122334', 'BRAC Bank', '783246435', '', NULL, '', 12, '2026-01-01 11:34:39'),
(11, 6, 'registration', 45000.00, 0.00, 'cash', 'paid', '2026-01-30', NULL, NULL, '', '', '', '', NULL, 'bKash', 1, '2026-01-30 14:51:46'),
(12, 6, 'emi', 56000.00, 0.00, 'cash', 'paid', '2026-01-30', NULL, NULL, '', '', '', '', NULL, 'bKash', 1, '2026-01-30 14:52:01'),
(13, 14, 'registration', 22222.00, 0.00, 'cash', 'paid', '2026-02-04', NULL, NULL, '', '', '', '', NULL, 'bKash', 1, '2026-02-04 09:15:29'),
(14, 17, 'booking_money', 500000.00, 0.00, 'bank', 'paid', '2026-02-04', NULL, 0, '', 'brac', '987654433', '', NULL, '', 17, '2026-02-04 09:26:39'),
(15, 16, 'registration', 899990.00, 0.00, 'bank', 'paid', '2026-02-04', NULL, NULL, '', 'brac', '987654', '', NULL, 'bKash', 1, '2026-02-04 09:55:35');

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
(1, 'Facebook', 'facebook', NULL, 1, '2025-12-11 12:04:47', '2025-12-11 12:04:47');

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
(1, 'Casual Leave', 10, 1, 1, 12, '2025-12-15 06:53:05', '2026-01-27 11:20:55'),
(2, 'Sick Leave', 15, 1, 1, 12, '2025-12-28 11:21:52', '2026-01-27 11:21:08'),
(3, 'Unpaid Leave', 0, 1, 1, 12, '2026-01-27 11:21:17', '2026-01-27 11:21:17');

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
(1, 'Atibazar ', NULL, '2025-12-18 04:35:38', '2025-12-18 04:59:51'),
(2, 'Ashulia ,Dhaka', 2, '2025-12-22 06:16:20', '2026-01-12 17:39:34');

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

--
-- Dumping data for table `management_cashin`
--

INSERT INTO `management_cashin` (`id`, `role_name`, `role_id`, `person_name`, `transaction_date`, `source`, `description`, `amount`, `payment_method`, `payment_status`, `transaction_ref`, `bank_name`, `bank_account_no`, `cheque_no`, `online_type`, `created_by`, `created_at`, `updated_at`) VALUES
(4, 'CEO', 4, 'Mainul Hassan Dulon', '2025-12-30', 'construction fees', 'from construction return', 5555555.00, 'cash', 'paid', '', '', '', '', '', 5, '2025-12-30 07:38:07', '2025-12-30 07:38:07'),
(5, 'Chairman', 8, 'Mr. Mustafizul Huda', '2025-12-30', 'fdfdas', 'Investment from D-Smart Group', 85555999.00, 'cash', 'paid', '', '', '', '', '', 5, '2025-12-30 07:56:04', '2025-12-30 07:56:04'),
(6, 'CEO', 4, 'Mainul Hassan Dulon', '2026-01-29', 'aa', 'ss', 5000.00, 'cash', 'paid', '', '', '', '', '', 2, '2026-01-29 16:00:25', '2026-01-29 16:00:25'),
(8, 'DCEO', 5, 'Ejajur Rahaman', '2026-02-01', 'BANK', '', 100000000.00, 'bank', 'paid', 'S582412C', 'DBBL', '015257526541', '', '', 16, '2026-02-01 04:01:15', '2026-02-01 04:01:15');

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

--
-- Dumping data for table `management_cashout`
--

INSERT INTO `management_cashout` (`id`, `category_id`, `role_name`, `role_id`, `person_name`, `transaction_date`, `paid_to`, `description`, `amount`, `payment_method`, `payment_status`, `transaction_ref`, `bank_name`, `bank_account_no`, `cheque_no`, `online_type`, `created_by`, `created_at`, `updated_at`) VALUES
(4, 1, '', NULL, NULL, '2026-01-01', 'for office', '', 55555.00, 'cash', 'paid', '', '', '', '', 'bKash', 5, '2026-01-01 05:53:09', '2026-01-01 05:53:09');

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

--
-- Dumping data for table `management_categories`
--

INSERT INTO `management_categories` (`id`, `category_name`, `description`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Utility Bill', 'Current bill', 1, 5, '2026-01-01 05:42:48', '2026-01-01 05:42:48'),
(2, 'construction', '', 1, 5, '2026-01-03 03:31:51', '2026-01-03 03:31:51');

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
(4, 'CEO', 'Mainul Hassan Dulon', 1, 5, '2025-12-30 07:26:34', '2025-12-30 07:26:34'),
(5, 'DCEO', 'Ejajur Rahaman', 1, 5, '2025-12-30 07:39:53', '2025-12-30 07:39:53'),
(6, 'COO', 'Md. Rayhan Bhuiyan', 1, 5, '2025-12-30 07:41:34', '2025-12-30 07:41:34'),
(7, 'Director', 'Md. Monsur Alam Munna', 1, 5, '2025-12-30 07:42:11', '2025-12-30 07:42:11'),
(8, 'Chairman', 'Mr. Mustafizul Huda', 1, 5, '2025-12-30 07:42:37', '2025-12-30 07:42:37'),
(9, 'Director', 'Naznin Akhtar', 1, 5, '2025-12-30 07:47:34', '2025-12-30 07:47:34');

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
(2, 19, 7, 12, 'Task reassigned: Test Task', 0, '2025-12-28 03:28:21'),
(3, 17, 8, 12, 'New task assigned: Test Task 2', 1, '2026-02-01 12:12:09'),
(4, 19, 9, 23, 'New task assigned: meeting with rafi', 0, '2026-02-04 09:14:57');

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

--
-- Dumping data for table `onboard_clients`
--

INSERT INTO `onboard_clients` (`id`, `lead_booking_id`, `email`, `password`, `cameraLink1`, `cameraLink2`, `moneyReceiptLink`, `purchaseDetailsLink`, `created_by`, `created_at`, `updated_at`, `lead_id`, `project_id`, `flat_id`) VALUES
(1, 1, 'hasin@gmail.com', '$2y$10$yPPTAALOTRK2lVuxbW9stuMqATKoRmY51D/R45Elq6DpcjpppIXwK', 'https://rtsp.me/embed/zkYhn9Hs/#', 'https://rtsp.me/embed/zkYhn9Hs/#', 'https://docs.google.com/spreadsheets/d/14JlevxCW0M3Y85_akQB6xPLvIB0WNmAEq0KIA_0rgB4/edit?gid=504284871#gid=504284871', 'https://drive.google.com/drive/folders/17UKM-WodSKGrYmyVMevJeq1LgqBFivKg', 12, '2026-02-03 10:55:30', '2026-02-08 10:18:39', 2, 2, 2);

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
(1, 26, 1, '2025-12-01', 22000.00, 1000.00, 21000.00, NULL, 'pending', 'bank', NULL, '2025-12-30 17:35:57', '2025-12-30 17:35:57'),
(2, 27, 1, '2026-01-01', 704000.00, 2000.00, 702000.00, 2, 'paid', 'bank', 12, '2026-01-13 17:18:23', '2026-01-13 17:24:34'),
(3, 32, 1, '2026-01-01', 32000.00, 1000.00, 31000.00, NULL, 'pending', 'bank', 12, '2026-01-27 17:18:58', '2026-01-27 17:18:58'),
(4, 32, 1, '2026-02-01', 30000.00, 0.00, 30000.00, 1, 'paid', 'bank', 14, '2026-02-04 15:40:20', '2026-02-04 15:41:58');

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
(1, 1, 1, 2000.00, NULL, '2026-01-05 10:26:59', '2026-01-05 10:26:59'),
(2, 1, 2, 1000.00, NULL, '2026-01-05 10:26:59', '2026-01-05 10:26:59'),
(3, 2, 1, 4000.00, NULL, '2026-01-13 11:18:23', '2026-01-13 11:18:23'),
(4, 2, 2, 2000.00, NULL, '2026-01-13 11:18:23', '2026-01-13 11:18:23'),
(5, 3, 1, 2000.00, NULL, '2026-01-27 11:18:58', '2026-01-27 11:18:58'),
(6, 3, 2, 1000.00, NULL, '2026-01-27 11:18:58', '2026-01-27 11:18:58');

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
(1, 'Medical Allowance', 'earning', NULL, NULL, '2025-12-30 17:31:55'),
(2, 'Loan / Advance Deduction ', 'deduction', NULL, NULL, '2025-12-30 17:31:55'),
(3, 'House Rent Allowance', 'earning', NULL, 12, '2026-01-28 13:47:15'),
(4, 'Transport Allowance', 'earning', NULL, 12, '2026-01-28 13:48:21'),
(5, 'Other Allowances', 'earning', NULL, 12, '2026-01-28 13:48:54'),
(6, 'Tax (Income Tax / TDS) ', 'deduction', NULL, 12, '2026-01-28 13:49:07');

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
(20, 'My Leave Request', 1, 12, '2026-02-08 12:00:34', '2026-02-08 12:00:34');

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
(4, 'App\\Models\\OnboardClient', 1, 'onboard-client', '4561e3242fb548617bc199392d02366d885d02efbdbdfd5a5df6288142b2f49d', '[\"*\"]', '2026-02-09 23:31:48', NULL, '2026-02-09 23:31:22', '2026-02-09 23:31:48');

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
(1, 'The Premium Nobel Court', 'the-premium-nobel-court', 1, 1, '', 40, 4, NULL, 'STUDIO, 1, 2 BEDROOMS & PENTHOUSE', '6943d73330f2d_1766053683.png', '6943d733450c3_1766053683.png', '6943d7335c2f2_1766053683.png', '', 'Under 50 Lac BDT', NULL, 'SOLD OUT', 0, '', '', 'https://drive.google.com/file/d/1Shb6eNMOd6KtKCO4aMYMVhIsVJn_qf7n/view?usp=sharing', '<p>descriptions</p>', 1, '<div class=\"group relative bg-white  rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 cursor-pointer overflow-hidden border border-emerald-100/50 hover:border-emerald-400 animate-scale-in\">\r\n<h3 class=\"relative p-4 font-semibold text-center text-xs sm:text-sm md:text-base text-neutral-800 group-hover:text-[#0d3d2d] transition-colors duration-300\">Daffodil International University</h3>\r\n<div class=\"absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-500 rounded-b-2xl\"> </div>\r\n</div>\r\n<div class=\"group relative bg-white  rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 cursor-pointer overflow-hidden border border-emerald-100/50 hover:border-emerald-400 animate-scale-in\">\r\n<div class=\"absolute inset-0 bg-gradient-to-br from-[#0d3d2d]/5 to-emerald-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500 rounded-2xl\"> </div>\r\n<div class=\"absolute top-0 right-0 w-16 h-16 bg-gradient-to-br from-emerald-400/20 to-transparent rounded-bl-full opacity-0 group-hover:opacity-100 transition-opacity duration-500\"> </div>\r\n<div class=\"relative aspect-square bg-gradient-to-br from-neutral-100 to-neutral-200 rounded-t-xl mb-3 sm:mb-4 overflow-hidden ring-1 ring-neutral-200 group-hover:ring-emerald-400 transition-all duration-500\"><img class=\"w-full h-full object-cover group-hover:scale-110 transition-transform duration-700\" src=\"https://www.dpremiumhomes.com/assets/Brac-Dt9jTuCp.png\" alt=\"Brac University (Tarc)\">\r\n<div class=\"absolute inset-0 bg-gradient-to-t from-[#0d3d2d]/30 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500\"> </div>\r\n<div class=\"absolute top-2 right-2 w-8 h-8 bg-white rounded-full shadow-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transform scale-50 group-hover:scale-100 transition-all duration-500\"> </div>\r\n</div>\r\n<h3 class=\"relative p-4 font-semibold text-center text-xs sm:text-sm md:text-base text-neutral-800 group-hover:text-[#0d3d2d] transition-colors duration-300\">Brac University (Tarc)</h3>\r\n<div class=\"absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-500 rounded-b-2xl\"> </div>\r\n</div>\r\n<div class=\"group relative bg-white  rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 cursor-pointer overflow-hidden border border-emerald-100/50 hover:border-emerald-400 animate-scale-in\">\r\n<div class=\"absolute inset-0 bg-gradient-to-br from-[#0d3d2d]/5 to-emerald-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500 rounded-2xl\"> </div>\r\n<div class=\"absolute top-0 right-0 w-16 h-16 bg-gradient-to-br from-emerald-400/20 to-transparent rounded-bl-full opacity-0 group-hover:opacity-100 transition-opacity duration-500\"> </div>\r\n<div class=\"relative aspect-square bg-gradient-to-br from-neutral-100 to-neutral-200 rounded-t-xl mb-3 sm:mb-4 overflow-hidden ring-1 ring-neutral-200 group-hover:ring-emerald-400 transition-all duration-500\"><img class=\"w-full h-full object-cover group-hover:scale-110 transition-transform duration-700\" src=\"https://www.dpremiumhomes.com/assets/Manarat-BBRfHwvp.png\" alt=\"Manarat International University\">\r\n<div class=\"absolute inset-0 bg-gradient-to-t from-[#0d3d2d]/30 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500\"> </div>\r\n<div class=\"absolute top-2 right-2 w-8 h-8 bg-white rounded-full shadow-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transform scale-50 group-hover:scale-100 transition-all duration-500\"> </div>\r\n</div>\r\n<h3 class=\"relative p-4 font-semibold text-center text-xs sm:text-sm md:text-base text-neutral-800 group-hover:text-[#0d3d2d] transition-colors duration-300\">Manarat International University</h3>\r\n<div class=\"absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-500 rounded-b-2xl\"> </div>\r\n</div>\r\n<div class=\"group relative bg-white  rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 cursor-pointer overflow-hidden border border-emerald-100/50 hover:border-emerald-400 animate-scale-in\">\r\n<div class=\"absolute inset-0 bg-gradient-to-br from-[#0d3d2d]/5 to-emerald-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500 rounded-2xl\"> </div>\r\n<div class=\"absolute top-0 right-0 w-16 h-16 bg-gradient-to-br from-emerald-400/20 to-transparent rounded-bl-full opacity-0 group-hover:opacity-100 transition-opacity duration-500\"> </div>\r\n<div class=\"relative aspect-square bg-gradient-to-br from-neutral-100 to-neutral-200 rounded-t-xl mb-3 sm:mb-4 overflow-hidden ring-1 ring-neutral-200 group-hover:ring-emerald-400 transition-all duration-500\"><img class=\"w-full h-full object-cover group-hover:scale-110 transition-transform duration-700\" src=\"https://www.dpremiumhomes.com/assets/mosque-ClDhUgQu.jpg\" alt=\"Masjid\">\r\n<div class=\"absolute inset-0 bg-gradient-to-t from-[#0d3d2d]/30 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500\"> </div>\r\n<div class=\"absolute top-2 right-2 w-8 h-8 bg-white rounded-full shadow-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transform scale-50 group-hover:scale-100 transition-all duration-500\"> </div>\r\n</div>\r\n<h3 class=\"relative p-4 font-semibold text-center text-xs sm:text-sm md:text-base text-neutral-800 group-hover:text-[#0d3d2d] transition-colors duration-300\">Masjid</h3>\r\n</div>', '6943d733818a7_1766053683.png', '6943d73372ce5_1766053683.png', 'https://drive.google.com/file/d/1Shb6eNMOd6KtKCO4aMYMVhIsVJn_qf7n/view?usp=sharing', '', 2, 1, 12, '2025-12-18 10:28:03', '2026-02-10 05:30:12', 1, NULL, 1, 1, 0, 'On Sale', NULL),
(2, 'The Premium Legacy Residence', 'the-premium-legacy-residence', 1, 1, 'B2+B1+G+14', 10, 4, NULL, 'D Block, Amin Mohammad Model Town, Ashulia', '69464dc0c7cb0_1766215104.png', '69464dc0ded7d_1766215104.png', '', '40 Feet', '50 Lac - 1 Crore BDT', NULL, 'SOLD OUT', NULL, '', '', 'https://drive.google.com/file/d/1Shb6eNMOd6KtKCO4aMYMVhIsVJn_qf7n/view?usp=sharing', '', 2, '', '69464dc110ce1_1766215105.png', '69464dc0f3103_1766215104.png', 'https://drive.google.com/file/d/1Shb6eNMOd6KtKCO4aMYMVhIsVJn_qf7n/view?usp=sharing', '', 2, 1, 12, '2025-12-20 07:18:25', '2026-02-10 05:30:00', 1, NULL, 1, 1, 0, 'On Sale', NULL),
(3, 'Ashuliya Model Town', 'ashuliya-model-town', 2, 1, 'B2+B1+G+15', 20, 6, NULL, 'STUDIO, 1, 2 BEDROOMS & PENTHOUSE', '6948e37ac4a10_1766384506.png', '6948e37ad734f_1766384506.png', '6948e37aebfa1_1766384506.png', '40 Feet', 'Under 50 Lac BDT', NULL, NULL, 3.5, '12', '', 'https://drive.google.com/file/d/1Shb6eNMOd6KtKCO4aMYMVhIsVJn_qf7n/view', '<p>trgfrt</p>', 1, '<p>oiet</p>', '6948e37b1526d_1766384507.png', '6948e37b06859_1766384507.png', 'https://drive.google.com/file/d/1Shb6eNMOd6KtKCO4aMYMVhIsVJn_qf7n/view?usp=sharing', '', 1, 1, 12, '2025-12-22 06:21:47', '2026-02-10 05:23:36', 1, NULL, 1, 1, 0, 'On Sale', NULL),
(4, 'Project Test', NULL, 1, 4, 'B2+B1+G+12', 3, 5, NULL, 'STUDIO, 1, 2 BEDROOMS & PENTHOUSE', 'projects_69677cdad7c5b.png', '', '', '40', 'Under 50 Lac BDT', 'The Premium Nobel Court', NULL, 3.5, '3', NULL, 'https://drive.google.com/file/d/1Shb6eNMOd6KtKCO4aMYMVhIsVJn_qf7n/view?usp=sharing', '<p>r8dt7</p>', 4, '<p>iudysitr</p>', 'projects_69677cdb17c82.png', 'projects_69677cdaf3582.png', 'https://drive.google.com/file/d/1Shb6eNMOd6KtKCO4aMYMVhIsVJn_qf7n/view?usp=sharing', NULL, 4, 3, 12, '2026-01-14 11:24:11', '2026-01-14 11:33:12', 1, NULL, 1, 0, 1, 'On Sale', '2026-01-14 11:33:12');

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

--
-- Dumping data for table `property_reviews`
--

INSERT INTO `property_reviews` (`id`, `reviewer_name`, `reviewer_role`, `project_id`, `reviewer_image`, `review_video_url`, `rating`, `review_text`, `review_date`, `status`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Rj Kebria', 'Property Investor', NULL, 'reviewers_696f36042a2a4.webp', 'https://youtu.be/qM5_UKR-vzk', 5, '\"The Premium Homes exceeded all our expectations. The quality of construction and a....', '2026-01-20', 'active', 12, '2026-01-20 07:37:53', '2026-01-20 08:00:04', NULL);

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
(99, 1, 10, '2026-02-02 03:58:05', '2026-02-02 03:58:05'),
(100, 1, 3, '2026-02-02 03:58:05', '2026-02-02 03:58:05'),
(101, 1, 1, '2026-02-02 03:58:05', '2026-02-02 03:58:05'),
(102, 1, 5, '2026-02-02 03:58:05', '2026-02-02 03:58:05'),
(103, 1, 12, '2026-02-02 03:58:05', '2026-02-02 03:58:05'),
(104, 1, 6, '2026-02-02 03:58:05', '2026-02-02 03:58:05'),
(105, 1, 14, '2026-02-02 03:58:05', '2026-02-02 03:58:05'),
(106, 1, 11, '2026-02-02 03:58:05', '2026-02-02 03:58:05'),
(107, 1, 7, '2026-02-02 03:58:05', '2026-02-02 03:58:05'),
(108, 1, 4, '2026-02-02 03:58:05', '2026-02-02 03:58:05'),
(109, 1, 13, '2026-02-02 03:58:05', '2026-02-02 03:58:05'),
(110, 1, 2, '2026-02-02 03:58:05', '2026-02-02 03:58:05'),
(111, 3, 10, '2026-02-02 03:58:18', '2026-02-02 03:58:18'),
(112, 3, 3, '2026-02-02 03:58:18', '2026-02-02 03:58:18'),
(113, 3, 1, '2026-02-02 03:58:18', '2026-02-02 03:58:18'),
(114, 3, 5, '2026-02-02 03:58:18', '2026-02-02 03:58:18'),
(115, 3, 12, '2026-02-02 03:58:18', '2026-02-02 03:58:18'),
(116, 3, 6, '2026-02-02 03:58:18', '2026-02-02 03:58:18'),
(117, 3, 14, '2026-02-02 03:58:18', '2026-02-02 03:58:18'),
(118, 3, 8, '2026-02-02 03:58:18', '2026-02-02 03:58:18'),
(119, 3, 11, '2026-02-02 03:58:18', '2026-02-02 03:58:18'),
(120, 3, 7, '2026-02-02 03:58:18', '2026-02-02 03:58:18'),
(121, 3, 4, '2026-02-02 03:58:18', '2026-02-02 03:58:18'),
(122, 3, 13, '2026-02-02 03:58:18', '2026-02-02 03:58:18'),
(123, 3, 2, '2026-02-02 03:58:18', '2026-02-02 03:58:18'),
(124, 7, 12, '2026-02-05 07:06:27', '2026-02-05 07:06:27'),
(125, 7, 13, '2026-02-05 07:06:27', '2026-02-05 07:06:27'),
(126, 8, 15, '2026-02-08 12:00:57', '2026-02-08 12:00:57'),
(127, 8, 20, '2026-02-08 12:00:57', '2026-02-08 12:00:57'),
(128, 9, 20, '2026-02-08 12:01:09', '2026-02-08 12:01:09'),
(129, 9, 13, '2026-02-08 12:01:09', '2026-02-08 12:01:09'),
(130, 9, 19, '2026-02-08 12:01:09', '2026-02-08 12:01:09'),
(131, 6, 10, '2026-02-08 12:01:16', '2026-02-08 12:01:16'),
(132, 6, 20, '2026-02-08 12:01:16', '2026-02-08 12:01:16'),
(133, 6, 13, '2026-02-08 12:01:16', '2026-02-08 12:01:16'),
(134, 5, 17, '2026-02-08 12:01:26', '2026-02-08 12:01:26'),
(135, 5, 20, '2026-02-08 12:01:26', '2026-02-08 12:01:26'),
(136, 5, 7, '2026-02-08 12:01:26', '2026-02-08 12:01:26'),
(137, 5, 13, '2026-02-08 12:01:26', '2026-02-08 12:01:26'),
(138, 4, 6, '2026-02-08 12:01:42', '2026-02-08 12:01:42'),
(139, 4, 20, '2026-02-08 12:01:42', '2026-02-08 12:01:42'),
(140, 4, 8, '2026-02-08 12:01:42', '2026-02-08 12:01:42'),
(141, 4, 13, '2026-02-08 12:01:42', '2026-02-08 12:01:42'),
(142, 4, 16, '2026-02-08 12:01:42', '2026-02-08 12:01:42');

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
(1, 'The Premium Home Limited', NULL, 'fxdgrdgth', '6948f14a9fb8c_1766388042.png', 'settings_69662931a55a4.png', '+8801958253300', '+8801958253300', 'info@thepremiumhomes.com', NULL, 'Dhaka', 'ï¿½ 2025 The Premium Homes Ltd. All rights reserved.', 'uidysfb re', 'kuysret87', 'uidsfyter', 'dsilfu67rt', 'iureyt87e', 'ids7rt89', '', '', 'I hereby declare that I have applied for the purchase of the above-mentioned flat under the terms and conditions set by the developer. I confirm that all the information provided in this document is true, correct, and complete to the best of my knowledge and belief.\r\n\r\nI further agree that I have carefully read, understood, and accepted all the terms and conditions, rules, and regulations related to the purchase of the said flat. I undertake to abide by all decisions, policies, and future regulations imposed by the developer in connection with this property.\r\n\r\nI also acknowledge that any false information or violation of the agreed terms may result in cancellation of the booking in accordance with the developerï¿½s policy.', 12, '2025-12-15 12:14:07', '2026-01-13 11:14:57');

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
(3, 7, 1, 'Task 1', '<p>Please do first</p>', '2025-12-02', '2025-12-27 10:38:07', 0, 'pending', 0, 'high', '2025-12-08 11:33:52', '2025-12-09 08:46:53'),
(4, 19, 2, 'Task 2', '<p>test description</p>', '2025-12-02', '2025-12-18 00:00:00', 1, 'in_progress', 20, 'high', '2025-12-08 11:35:32', '2026-02-14 07:37:32'),
(5, 13, 1, 'Task 2', '<p>do first</p>', '2025-12-11', '2025-12-11 00:00:00', 0, '', 0, '', '2025-12-10 09:15:05', NULL),
(6, 13, 1, 'Task 2', '<p>do first</p>', '2025-12-11', '2025-12-11 00:00:00', 0, '', 0, '', '2025-12-10 09:19:52', NULL),
(7, 19, 12, 'Test Task', '<p>test</p>', '2025-12-29', '2026-02-06 00:00:00', 0, 'pending', 0, 'high', '2025-12-28 03:28:21', '2026-02-01 12:11:30'),
(8, 17, 12, 'Test Task 2', '<p>Task test</p>', '2026-02-01', '2026-02-20 00:00:00', 1, 'in_progress', 0, 'high', '2026-02-01 12:12:09', '2026-02-04 09:15:52'),
(9, 19, 23, 'meeting with rafi', '<p>kkkkjjj</p>', '2026-02-05', '2026-02-06 00:00:00', 0, 'pending', 0, 'high', '2026-02-04 09:14:57', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Unit A', '2025-12-18 10:55:04', '2025-12-18 10:55:04'),
(2, 'Unit - B', '2025-12-18 10:59:55', '2025-12-18 10:59:55'),
(3, 'Unit C', '2025-12-18 11:04:55', '2025-12-18 11:04:55'),
(4, 'Unit -A1', '2025-12-22 08:02:40', '2025-12-22 08:02:40'),
(5, 'Unit - A2', '2025-12-22 08:02:55', '2025-12-22 08:02:55'),
(6, 'Unit - A3', '2025-12-22 08:03:13', '2025-12-22 08:03:13'),
(7, 'Unit - B1', '2025-12-22 08:03:26', '2025-12-22 08:03:26'),
(8, 'Unit - B2', '2025-12-22 08:03:39', '2025-12-22 08:03:39'),
(9, 'Unit - B3', '2025-12-22 08:03:54', '2025-12-22 08:03:54'),
(10, 'Unit - C1', '2025-12-22 08:04:07', '2025-12-22 08:04:07'),
(11, 'Unit - C3', '2025-12-22 08:04:40', '2025-12-22 08:04:40'),
(12, 'Unit - C2', '2025-12-22 08:04:59', '2025-12-22 08:04:59');

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
(2, NULL, 'Mehedi Hasan Jony', '', '01710000000', 'jony@gmail.com', 1, '0', '908927387', 3, '$2y$10$tyGb6XsaCzqrB5/fmTtTEualGz9EEsCTS4F0mAz7b/ITjBISkKirS', '693e8e3dac751_1765707325.jpg', '', '', '2026-02-04 16:01:42', NULL, '2025-12-09 07:53:41', '2026-02-04 10:01:42'),
(3, NULL, 'employee-1', '', '01800000000', 'employee1@gmail.com', 1, '0', '1230000', 2, '$2y$10$xzoToZ2.n3Zfyj11JmM6n.TNtAmP0WArM.OioGwNQeJenGCtxCY3q', '693e8cdbbdb7f_1765706971.jpg', '', '', '2025-12-10 15:43:28', NULL, '2025-12-10 04:23:16', '2025-12-14 10:09:31'),
(4, NULL, 'Mr.X', 'X', '01712000000', 'x@gmail.com', 1, 'Dhaka', '234534', 2, '$2y$10$d3U.lBocwb.btXLSBAKVqunPPux7hVEZIaW1CnlzTDhyd5T61l2r.', NULL, '', '', '2025-12-10 15:09:59', 2, '2025-12-10 09:09:59', '2026-01-29 16:09:41'),
(5, NULL, 'Admin', 'admin', '01809999999', 'admin@gmail.com', 1, 'Dhaka', '123456', 3, '$2y$10$hLMUJs3VQjIEbZMEbR55luQiO/WWNP8EV.eCq9hhwyni3pet.FJXC', '', '', '', '2026-01-05 12:56:33', NULL, '2025-12-10 09:42:03', '2026-01-05 06:56:33'),
(12, NULL, 'Farzana', 'Farzana', '01711111111', 'farz@gmail.com', 1, 'Dhaka', '12345678', 3, '$2y$10$E0Yhq.1IcOfIhUepelWIieBg7O9L6XhEKWNmRhD1v2rT0WRH5Q7T6', '693e4b6b1f340_1765690219.jpg', 'https://business.facebook.com/', 'https://www.linkedin.com/', '2026-02-08 17:59:25', NULL, '2025-12-14 05:30:19', '2026-02-08 11:59:25'),
(13, NULL, 'Nusrat', '', '01789000000', 'nusrat@gmail.com', 1, '23', '123456', 2, '$2y$10$3CN1C.iRuSmfS4qjtmGuZupZjaBqCLCTBsDEacL5yBX.TDoInJxRW', '', '', '', '2025-12-14 17:34:07', NULL, '2025-12-14 06:34:19', '2025-12-14 11:34:07'),
(14, NULL, 'Farhana', 'Farhana', '01700000000', 'hr@gmail.com', 11, 'Dhaka', '2345676', 4, '$2y$10$qrV0q12qYEoSAwQxskU10esNyxBjXA3RDfVO3AAzip46EFIoHP852', NULL, '', '', '2026-02-04 15:42:19', NULL, '2025-12-14 08:40:51', '2026-02-04 09:42:19'),
(15, NULL, 'employee test', 'employee', '01944667441', 'employee@gmail.com', 2, 'asdf', '55555534434', 2, '$2y$10$LCKTY4RVpLEquIsnepEexOhF3DtRxNA8H6mdnvizhD4oTG0PFBpXu', 'users_695f96920705e.png', '', '', '2025-12-28 17:13:40', 12, '2025-12-28 11:13:40', '2026-01-08 11:35:46'),
(16, NULL, 'Arif Hossain', 'accounts', '01345467766', 'accounts@gmail.com', 4, 'Dhaka', '36543547', 6, '$2y$10$r66n89lxUbvL5iAuweQHEOI4NOLUvIECrbvBxTVvNZzh2kqHN9wsq', 'users_695a187e0fe0c.png', '', '', '2026-02-04 16:05:12', NULL, '2026-01-04 01:36:30', '2026-02-04 10:05:12'),
(17, NULL, 'Sajib Roy', 'sojib', '01734385489', 'sales@gmail.com', 12, 'Dhaka', '21343455', 5, '$2y$10$HV8.JJkD07AJL5sfcwJGsudHY2TqItZhS6PPYGBEFRpaKzmTlAtAG', 'users_695a19d4f324b.jpg', '', '', '2026-02-08 18:05:36', NULL, '2026-01-04 01:42:13', '2026-02-08 12:05:36'),
(18, NULL, 'CED User', 'CED', '01784389326', 'ced@gmail.com', 13, 'Dhaka', '1232435', 7, '$2y$10$.jNcHQIkEuFalVSKO//OquGqF5eodD26IRLp7kcuVDY9oMeUd6HMq', 'users_695a436436fcd.png', 'https://business.facebook.com/', 'https://www.google.com/', '2026-02-05 18:58:51', NULL, '2026-01-04 04:39:32', '2026-02-05 12:58:51'),
(19, NULL, 'Fahim', 'fahim', '01732879443', 'fahim@gmail.com', 8, 'Mohammadpur, Dhaka-1207', '', 8, '$2y$10$HzkI/.03ixwVuyk2uW79tOq6IvsHosydThBtWSTIJuofBNKj.KUly', NULL, '', '', '2026-02-14 14:16:57', 12, '2026-01-30 04:38:50', '2026-02-14 08:16:57'),
(20, NULL, 'Roman', 'Roman', '01872364445', 'roman@gmail.com', 6, 'Mohammadpur, Dhaka-1207', '', 8, '$2y$10$BwNhEesxZZxeVNdlzCglGOYpe4s/coSVFzJluKH55Y8FTLk1eiQ1W', NULL, '', '', '2026-02-02 18:09:37', 12, '2026-01-30 04:39:44', '2026-02-02 12:09:37'),
(22, NULL, 'Farhan', 'farhan', '01283984423', 'farhan@gmail.com', 6, 'Dhaka', '', 8, '$2y$10$Im6G1lWoiJ/6p0q8noZYP.38c.ISZFndyVk2.Sl9WuXkTn4bynAp6', NULL, '', '', '2026-01-31 18:42:40', 17, '2026-01-31 12:42:40', '2026-01-31 12:42:40'),
(23, NULL, 'Rumin ', 'Rumin', '01876234444', 'sales-head@gmail.com', 12, 'Mohammadpur, Dhaka-1207', '', 9, '$2y$10$Jg8v5pepqBCk6YHvr7gQO.NoihRmf5kIpNilKd//fW5vlPmfxiR16', NULL, '', '', '2026-02-04 15:15:10', 12, '2026-02-01 12:08:14', '2026-02-04 09:15:10'),
(24, 35, 'Himu', '', '03289547834', 'himu@gmail.com', 2, '', '', 8, '$2y$10$pNTZGjm1hI/toH8nAsbvYO5QczudAhCZbzzNcxCdQGNYOOMNLdtuG', NULL, '', '', '2026-02-13 22:55:30', 0, '2026-02-13 16:54:10', '2026-02-13 16:55:30');

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
  ADD KEY `project_id` (`project_id`),
  ADD KEY `unit_id` (`unit_id`);

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
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `units`
--
ALTER TABLE `units`
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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `approval_requests`
--
ALTER TABLE `approval_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `blogs`
--
ALTER TABLE `blogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cameras`
--
ALTER TABLE `cameras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `company_bank_accounts`
--
ALTER TABLE `company_bank_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `designations`
--
ALTER TABLE `designations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `emi_schedules`
--
ALTER TABLE `emi_schedules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `employee_leaves`
--
ALTER TABLE `employee_leaves`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `employee_leave_balances`
--
ALTER TABLE `employee_leave_balances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `emp_attendances`
--
ALTER TABLE `emp_attendances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `flats`
--
ALTER TABLE `flats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `flat_purchase_rules`
--
ALTER TABLE `flat_purchase_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `head_notifications`
--
ALTER TABLE `head_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `lead_bookings`
--
ALTER TABLE `lead_bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `lead_follow_ups`
--
ALTER TABLE `lead_follow_ups`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `lead_payments`
--
ALTER TABLE `lead_payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `lead_sources`
--
ALTER TABLE `lead_sources`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `management_cashin`
--
ALTER TABLE `management_cashin`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `management_cashout`
--
ALTER TABLE `management_cashout`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `management_categories`
--
ALTER TABLE `management_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `management_roles`
--
ALTER TABLE `management_roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `onboard_clients`
--
ALTER TABLE `onboard_clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payslips`
--
ALTER TABLE `payslips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payslip_details`
--
ALTER TABLE `payslip_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payslip_items`
--
ALTER TABLE `payslip_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `property_reviews`
--
ALTER TABLE `property_reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=143;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cameras`
--
ALTER TABLE `cameras`
  ADD CONSTRAINT `cameras_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cashout_payments`
--
ALTER TABLE `cashout_payments`
  ADD CONSTRAINT `fk_cashout_payments_request` FOREIGN KEY (`cashout_request_id`) REFERENCES `cashout_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `cashout_requests`
--
ALTER TABLE `cashout_requests`
  ADD CONSTRAINT `fk_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rejected_by` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `designations`
--
ALTER TABLE `designations`
  ADD CONSTRAINT `designations_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `emi_schedules`
--
ALTER TABLE `emi_schedules`
  ADD CONSTRAINT `fk_emi_booking` FOREIGN KEY (`lead_booking_id`) REFERENCES `lead_bookings` (`id`);

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employees_ibfk_2` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_employee_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `emp_attendances`
--
ALTER TABLE `emp_attendances`
  ADD CONSTRAINT `fk_attendance_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `flats`
--
ALTER TABLE `flats`
  ADD CONSTRAINT `flats_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `flats_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`);

--
-- Constraints for table `leads`
--
ALTER TABLE `leads`
  ADD CONSTRAINT `fk_leads_owner` FOREIGN KEY (`assigned_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `lead_bookings`
--
ALTER TABLE `lead_bookings`
  ADD CONSTRAINT `lead_bookings_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lead_bookings_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  ADD CONSTRAINT `lead_bookings_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `lead_bookings_ibfk_4` FOREIGN KEY (`flat_id`) REFERENCES `flats` (`id`);

--
-- Constraints for table `lead_follow_ups`
--
ALTER TABLE `lead_follow_ups`
  ADD CONSTRAINT `lead_follow_ups_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lead_follow_ups_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lead_follow_ups_ibfk_3` FOREIGN KEY (`flat_id`) REFERENCES `flats` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `lead_payments`
--
ALTER TABLE `lead_payments`
  ADD CONSTRAINT `fk_payment_booking` FOREIGN KEY (`booking_id`) REFERENCES `lead_bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `management_categories`
--
ALTER TABLE `management_categories`
  ADD CONSTRAINT `fk_category_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `management_roles`
--
ALTER TABLE `management_roles`
  ADD CONSTRAINT `management_roles_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payslips`
--
ALTER TABLE `payslips`
  ADD CONSTRAINT `payslips_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payslips_ibfk_2` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payslip_details`
--
ALTER TABLE `payslip_details`
  ADD CONSTRAINT `payslip_details_ibfk_1` FOREIGN KEY (`payslip_id`) REFERENCES `payslips` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payslip_details_ibfk_2` FOREIGN KEY (`payslip_item_id`) REFERENCES `payslip_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `projects_ibfk_2` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`);

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `site_visits`
--
ALTER TABLE `site_visits`
  ADD CONSTRAINT `site_visits_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `site_visits_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `site_visits_ibfk_3` FOREIGN KEY (`flat_id`) REFERENCES `flats` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


--- 16/02/26
CREATE TABLE sales_targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    sales_user_id INT NOT NULL,
    
    month DATE NOT NULL COMMENT 'Use first day of month e.g. 2026-02-01',
    
    site_visit_target INT DEFAULT 0,
    booking_target INT DEFAULT 0,
    booking_value_target DECIMAL(15,2) DEFAULT 0.00,
    
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_sales_month (sales_user_id, month),
    
    INDEX idx_sales_month (sales_user_id, month),
    
    CONSTRAINT fk_sales_target_user
        FOREIGN KEY (sales_user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One's sales executive 
-- In one month has give one time set official target  tai month unique
