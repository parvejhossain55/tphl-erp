-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 18, 2025 at 01:29 PM
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
-- Table structure for table `blogs`
--

CREATE TABLE `blogs` (
  `id` int(11) NOT NULL,
  `title` text NOT NULL,
  `excerpt` longtext DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `author` varchar(100) DEFAULT NULL,
  `published_date` date DEFAULT NULL,
  `comments` int(11) DEFAULT 0,
  `read_time` varchar(50) DEFAULT NULL,
  `status` tinyint(4) DEFAULT 1 COMMENT '1=Published, 0=Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blogs`
--

INSERT INTO `blogs` (`id`, `title`, `excerpt`, `content`, `image`, `author`, `published_date`, `comments`, `read_time`, `status`, `created_at`, `updated_at`) VALUES
(1, 'From Vision to Venture; The Entrepreneurial Journey2', 'চবিতে “From Vision to Venture; The Entrepreneurial Journey” অনুপ্রেরণামূলক সেশন অনুষ্ঠিত', 'চিটাগং ইউনিভার্সিটি ইন্ট্রাপেনার এন্ড স্টার্টআপ সোসাইটি (সিইউইএসএস) এর উদ্যোগে “From Vision to Venture; The Entrepreneurial Journey” শীর্ষক এক অনুপ্রেরণামূলক সেশন বুধবার (৫ নভেম্বর ২০২৫) দুপুর ১টায় চবি ব্যবসায় প্রশাসন অনুষদ অডিটোরিয়ামে অনুষ্ঠিত হয়। অনুষ্ঠানে বিশেষ অতিথি হিসেবে উপস্থিত ছিলেন চট্টগ্রাম বিশ্ববিদ্যালয়ের মাননীয় উপ-উপাচার্য (একাডেমিক) প্রফেসর ড. মোহাম্মদ শামীম উদ্দিন খান ও ব্যবসায় প্রশাসন অনুষদের ডিন প্রফেসর ড. মোহাম্মদ তৈয়ব চৌধুরী। অনুষ্ঠানে সম্মানিত অতিথি ছিলেন চবি প্রক্টর প্রফেসর ড. হোসেন শহীদ সরওয়ার্দী ও চবি মানবসম্পদ ব্যবস্থাপনা বিভাগের সভাপতি প্রফেসর ড. মো. আফতাব উদ্দীন। বক্তব্যে চবি উপ-উপাচার্য (একাডেমিক) প্রফেসর ড. মোহাম্মদ শামীম উদ্দিন খান এমন উদ্যােগের জন্য আয়োজকদের ধন্যবাদ জানান। উপ-উপাচার্য (একাডেমিক) তাঁর বক্তৃতায় শিক্ষার্থীদের উদ্ভাবনী চিন্তা ও নেতৃত্বের পথে এগিয়ে যেতে উৎসাহিত করেন। অনুষ্ঠানে মূল বক্তা হিসেবে উপস্থিত ছিলেন দ্য প্রিমিয়াম হোমস লি. এর ম্যানেজিং ডিরেক্টর ও সিইও মো. মাইনুল হাসান দোলন এবং ইন্টেরিয়র স্টুডিও এর ফাউন্ডার ও সিইও মারুফ লিয়াকত। তারা বাস্তব উদ্যোক্তা জীবন, নেতৃত্বগুণ এবং সাহসী সিদ্ধান্ত গ্রহণের জন্য অনুপ্রেরণামূলক বক্তব্য রাখেন। এসময় অতিথিদের সম্মাননা ক্রেস্ট প্রদান করা হয়। পরবর্তীতে আয়োজিত কুইজ প্রতিযোগিতাতে ১০টি প্রশ্নে বিজয়ীদের পুরস্কার দেওয়া হয়। CUESS এর সভাপতি মুশফিকুর রহমান হৃদয় ও সাধারণ সম্পাদক সাহিব আব্বাস বাহার চৌধুরী সমাপনী বক্তব্যে অতিথি ও অংশগ্রহণকারীদের প্রতি ধন্যবাদ ও কৃতজ্ঞতা জানান।', '694265d5d7d91_1765959125.webp', 'Admin', '2025-01-01', 0, '2 min read', 1, '2025-12-17 06:45:34', '2025-12-17 09:04:58');

-- --------------------------------------------------------

--
-- Table structure for table `communities`
--

CREATE TABLE `communities` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `communities`
--

INSERT INTO `communities` (`id`, `name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'The Premium Smart City', 1, '2025-12-18 06:41:23', '2025-12-18 06:44:31');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Sales', 1, '2025-12-07 11:24:44', '2025-12-07 11:24:44'),
(2, '  Marketing  ', 1, '2025-12-07 11:24:58', '2025-12-07 11:24:58'),
(3, 'Business Development ', 1, '2025-12-07 11:25:08', '2025-12-07 11:25:08'),
(4, ' Customer Relations', 1, '2025-12-07 11:25:13', '2025-12-07 11:25:13'),
(5, 'Project Management', 1, '2025-12-07 11:25:25', '2025-12-07 11:25:25'),
(6, '  Site Operations ', 1, '2025-12-07 11:25:32', '2025-12-07 11:25:32'),
(8, ' Construction Management', 1, '2025-12-07 11:25:45', '2025-12-07 11:25:45');

-- --------------------------------------------------------

--
-- Table structure for table `designations`
--

CREATE TABLE `designations` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `designations`
--

INSERT INTO `designations` (`id`, `name`, `department_id`, `created_at`, `updated_at`) VALUES
(1, 'Project Manager', 5, '2025-12-07 11:26:14', '2025-12-07 11:26:14');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `user_id`, `full_name`, `employee_id`, `username`, `email`, `father_name`, `phone`, `emergency_contact`, `qualification`, `experience`, `address`, `pass_num`, `nid`, `department_id`, `designation_id`, `dob`, `joining_date`, `account_holder_name`, `account_number`, `make_user`, `password`, `bank_name`, `branch_name`, `social_media1`, `social_media2`, `social_media3`, `photo`, `created_at`, `updated_at`) VALUES
(7, NULL, 'Hasan ', '1', 'hasan', 'hasan@gmail.com', '', '01700000000', '0171000000', 'BBA', '<p>gyjhasdt ikf ujsdfy7u hdsa8fy</p>', 'Dhaka', '', NULL, 5, 1, '1999-12-01', '2025-01-01', 'Hasan', '1111111111111', 0, '', 'Dhaka Bank', 'Dhaka', '123', '123', '', '693d3e8496d75_1765621380.jpg', '2025-12-08 05:06:42', '2025-12-13 10:23:00'),
(8, NULL, 'Shihan', '12', 'shihan', 'shihan@gmail.com', 'Shahid', '01805997399', '01805997399', 'MBA', '<p>iuydsr</p>', 'Dhaka', '', NULL, 1, 1, '0000-00-00', '0000-00-00', 'Abc', '01983274', 1, '', 'abc', '', '832764', '87436', '', '693d40fdf0513_1765622013.png', '2025-12-10 07:28:56', '2025-12-13 10:33:34'),
(13, 4, 'Mr.X', '90', 'X', 'x@gmail.com', 'Y', '01712000000', '01712000000', 'MBA', '<p>weqrewtr</p>', 'Dhaka', '', NULL, 3, 1, '0000-00-00', '0000-00-00', 'Abc', 'qwer', 1, '123456', 'weq', '', '832764', '87436', '', '693d4070c78b1_1765621872.png', '2025-12-10 09:09:59', '2025-12-13 10:31:12'),
(14, 5, 'Hamid', '04', 'hamid', 'hamid@gmail.com', 'hamim', '01809999999', '01809999999', 'MBA', '<p>jdfgur kjdf kjdfhg8uer</p>', 'Dhaka', '', NULL, 1, 1, '2000-12-14', '0000-00-00', 'Abc', '01983274', 1, '123456', 'abc', '', '832764', '87436', '', '693d3fe2c332d_1765621730.png', '2025-12-10 09:42:03', '2025-12-13 10:28:50'),
(26, NULL, 'Farhan', '13', 'Farhan', 'farhan@gmail.com', 'aziz', '01790120923', '01805997399', 'MBA', '<p>fsdygiii</p>', 'Dhaka', '', NULL, 1, 1, '1990-01-01', '2025-01-01', 'Farhan', '98234734', 1, '$2y$10$96BGmKUmreuE95rOQ8T6NehOqMlmOT0IkpoUBQuaHkEb9wiWfJvIC', 'Dhaka', 'Dhaka', 'www.google.com', 'www.google.com', '', '693d3fc682ed5_1765621702.png', '2025-12-10 12:28:53', '2025-12-13 10:28:22'),
(27, NULL, 'Mehedi Hasan Jony', '123', '@jony', 'jony@gmail.com', 'Unknown', '01786777777', '01786777777', 'MBA', '<p>No description</p>', '23\r\nSEL HUQ SKYPARK\r\n2 Mirpur Rd', '', NULL, 3, 1, '2025-01-01', '2025-01-01', 'Mehedi H. Jony', '615234', 0, '', 'City Bank', '', 'www.facebbok.com', 'www.linkedin.com', '', '693d4f012d0cd_1765625601.jpg', '2025-12-13 11:33:01', '2025-12-13 11:33:22'),
(29, 13, 'Nusrat', '189', 'Nusrat', 'nusrat@gmail.com', 'Nur', '01789000000', '01789000000', 'MBA', '<p>jkfdh kdf</p>', '23\r\nSEL HUQ SKYPARK\r\n2 Mirpur Rd', '', NULL, 1, 1, '0000-00-00', '0000-00-00', 'Nusrat', '1263214324', 1, '$2y$10$..eFW2QctwO7qH3KucZSfuRQEeYudFY.citf5I0FJewqOsJWRAQ2W', 'Dhaka Bank', '', 'www.google.com', 'www.linkedin.com', '', '693e5a6b64a45_1765694059.png', '2025-12-14 06:34:19', '2025-12-14 06:34:19'),
(32, 14, 'Farhana', '1827', 'Farhana', 'farhana@gmail.com', 'hamim', '01700000000', '0171000000', 'BSC', '<p>rjeiouort</p>', 'Dhaka', '', '2345676', 1, 1, '0000-00-00', '0000-00-00', 'Abc', '1111111111111', 1, '$2y$10$lxw8JSnTFc75E7i0QyuKEuxgedIf2l7E/y94jxlnZzG2EjloA2TYu', 'Dhaka Bank', '', 'www.google.com', 'www.linkedin.com', '', '693e781139293_1765701649.jpg', '2025-12-14 08:40:51', '2025-12-14 08:40:51');

-- --------------------------------------------------------

--
-- Table structure for table `flats`
--

CREATE TABLE `flats` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `sqft` int(11) DEFAULT NULL,
  `bhk` int(11) DEFAULT NULL,
  `bedroom` int(11) DEFAULT NULL,
  `bathroom` int(11) DEFAULT NULL,
  `balcony` int(11) DEFAULT NULL,
  `drawing_dining_kitchen` tinyint(1) DEFAULT NULL,
  `common_dining` tinyint(1) DEFAULT NULL,
  `floor_no` int(11) DEFAULT NULL,
  `facing_site` varchar(50) DEFAULT NULL,
  `price_value` decimal(12,2) DEFAULT NULL,
  `facilities` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flats`
--

INSERT INTO `flats` (`id`, `project_id`, `unit_id`, `sqft`, `bhk`, `bedroom`, `bathroom`, `balcony`, `drawing_dining_kitchen`, `common_dining`, `floor_no`, `facing_site`, `price_value`, `facilities`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 240, 2, 3, 2, 2, 0, 0, 5, '0', 40000000.00, 'Gym', '2025-12-18 11:58:43', '2025-12-18 12:06:55');

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `id` int(10) UNSIGNED NOT NULL,
  `lead_name` varchar(255) NOT NULL,
  `lead_type` enum('Hot','Warm','Cold') NOT NULL DEFAULT 'Hot',
  `company` varchar(255) DEFAULT NULL,
  `value_amount` decimal(15,2) DEFAULT NULL,
  `currency` enum('USD','EURO','RUBLE','POUND','BDT') DEFAULT 'BDT',
  `phone` varchar(30) NOT NULL,
  `alt_phone_number` varchar(30) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `source_type_id` int(11) DEFAULT NULL,
  `owner_id` int(11) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `pref_location` varchar(255) DEFAULT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `project_type` enum('ATI Society','ASHU Society') NOT NULL,
  `interest_on` varchar(250) NOT NULL,
  `pref_flat_size` varchar(250) NOT NULL,
  `communication_type` enum('WA Messaging','WA Conversion') DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `visibility` enum('public','private','selected') DEFAULT 'public',
  `status` enum('active','inactive') DEFAULT 'active',
  `lead_quality` enum('Not Interested','Prospect','Pending') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leads`
--

INSERT INTO `leads` (`id`, `lead_name`, `lead_type`, `company`, `value_amount`, `currency`, `phone`, `alt_phone_number`, `email`, `source_type_id`, `owner_id`, `address`, `pref_location`, `designation`, `project_type`, `interest_on`, `pref_flat_size`, `communication_type`, `tags`, `description`, `visibility`, `status`, `lead_quality`, `created_at`, `updated_at`) VALUES
(2, 'Hasin', 'Hot', 'Company X', 10000000.00, 'BDT', '01700000000', '0180000000', 'hasan@gmail.com', 1, 2, 'Dhaka', 'Dhaka', 'Project Manager', 'ATI Society', 'Project Wise', '345 sqf', 'WA Messaging', 'No nedd', 'fghyt uiy6we', 'public', 'active', 'Not Interested', '2025-12-11 07:07:51', '2025-12-14 11:09:22'),
(4, 'Hasin2', 'Hot', 'Company X', 10000000.00, 'BDT', '01700000000', '0180000000', 'hasan@gmail.com', 0, 2, '0', 'Dhaka', 'Project Manager', 'ATI Society', 'Project Wise', '345 sqf', 'WA Messaging', 'No nedd', '', 'public', 'active', 'Not Interested', '2025-12-11 09:56:45', '2025-12-11 10:29:28');

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
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_types`
--

INSERT INTO `leave_types` (`id`, `name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Casual Leave', 1, '2025-12-15 06:53:05', '2025-12-15 06:53:05');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Atibazar ', '2025-12-18 04:35:38', '2025-12-18 04:59:51');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `assigned_by` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Add Role', 1, '2025-12-04 12:12:58', '2025-12-04 12:12:58'),
(2, 'View Role', 1, '2025-12-04 12:17:47', '2025-12-04 12:17:47'),
(3, 'Add Permission', 1, '2025-12-06 05:51:07', '2025-12-06 05:51:07'),
(4, 'Show Permission', 1, '2025-12-06 05:51:25', '2025-12-06 05:51:25'),
(5, 'Add Task', 1, '2025-12-10 04:26:45', '2025-12-10 04:26:45'),
(6, 'HRM Manage ', 1, '2025-12-17 09:52:36', '2025-12-17 09:52:36');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `community_id` int(11) NOT NULL,
  `building_type` varchar(50) DEFAULT NULL,
  `total_share` int(11) DEFAULT NULL,
  `unit_per_floor` int(11) DEFAULT NULL,
  `types` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `image2` varchar(255) DEFAULT NULL,
  `image3` varchar(255) DEFAULT NULL,
  `front_road` varchar(50) DEFAULT NULL,
  `price_range` enum('Under 50 Lac BDT','50 Lac - 1 Crore BDT','1 Crore - 2 Crore BDT','2 Crore - 5 Crore BDT','Above 5 Crore BDT') DEFAULT NULL,
  `hover_text` varchar(255) DEFAULT NULL,
  `tag` varchar(50) DEFAULT NULL,
  `brochure_link` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `apartment_count` int(11) DEFAULT NULL,
  `amenities_details` longtext DEFAULT NULL,
  `amenities_image` varchar(255) DEFAULT NULL,
  `map_image` varchar(255) DEFAULT NULL,
  `map_link` varchar(255) DEFAULT NULL,
  `passenger_lift` int(11) DEFAULT NULL,
  `cargo_lift` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `rooftop_gardening` tinyint(1) DEFAULT 0 COMMENT '1=Yes, 0=No',
  `swimming_pool` tinyint(1) DEFAULT 0 COMMENT '1=Yes, 0=No',
  `convention` tinyint(1) DEFAULT 0 COMMENT '1=Yes, 0=No',
  `electricity_backup` tinyint(1) DEFAULT 0 COMMENT '1=Yes, 0=No'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `name`, `location_id`, `community_id`, `building_type`, `total_share`, `unit_per_floor`, `types`, `image`, `image2`, `image3`, `front_road`, `price_range`, `hover_text`, `tag`, `brochure_link`, `description`, `apartment_count`, `amenities_details`, `amenities_image`, `map_image`, `map_link`, `passenger_lift`, `cargo_lift`, `created_at`, `updated_at`, `rooftop_gardening`, `swimming_pool`, `convention`, `electricity_backup`) VALUES
(1, 'The Premium Nobel Court', 1, 1, '', 40, 4, 'STUDIO, 1, 2 BEDROOMS & PENTHOUSE', '6943d73330f2d_1766053683.png', '6943d733450c3_1766053683.png', '6943d7335c2f2_1766053683.png', '', 'Under 50 Lac BDT', 'The Premium Nobel Court', 'SOLD OUT', 'https://drive.google.com/file/d/1Shb6eNMOd6KtKCO4aMYMVhIsVJn_qf7n/view?usp=sharing', '', 1, '<div class=\"group relative bg-white  rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 cursor-pointer overflow-hidden border border-emerald-100/50 hover:border-emerald-400 animate-scale-in\">\r\n<h3 class=\"relative p-4 font-semibold text-center text-xs sm:text-sm md:text-base text-neutral-800 group-hover:text-[#0d3d2d] transition-colors duration-300\">Daffodil International University</h3>\r\n<div class=\"absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-500 rounded-b-2xl\"> </div>\r\n</div>\r\n<div class=\"group relative bg-white  rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 cursor-pointer overflow-hidden border border-emerald-100/50 hover:border-emerald-400 animate-scale-in\">\r\n<div class=\"absolute inset-0 bg-gradient-to-br from-[#0d3d2d]/5 to-emerald-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500 rounded-2xl\"> </div>\r\n<div class=\"absolute top-0 right-0 w-16 h-16 bg-gradient-to-br from-emerald-400/20 to-transparent rounded-bl-full opacity-0 group-hover:opacity-100 transition-opacity duration-500\"> </div>\r\n<div class=\"relative aspect-square bg-gradient-to-br from-neutral-100 to-neutral-200 rounded-t-xl mb-3 sm:mb-4 overflow-hidden ring-1 ring-neutral-200 group-hover:ring-emerald-400 transition-all duration-500\"><img class=\"w-full h-full object-cover group-hover:scale-110 transition-transform duration-700\" src=\"https://www.dpremiumhomes.com/assets/Brac-Dt9jTuCp.png\" alt=\"Brac University (Tarc)\">\r\n<div class=\"absolute inset-0 bg-gradient-to-t from-[#0d3d2d]/30 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500\"> </div>\r\n<div class=\"absolute top-2 right-2 w-8 h-8 bg-white rounded-full shadow-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transform scale-50 group-hover:scale-100 transition-all duration-500\"> </div>\r\n</div>\r\n<h3 class=\"relative p-4 font-semibold text-center text-xs sm:text-sm md:text-base text-neutral-800 group-hover:text-[#0d3d2d] transition-colors duration-300\">Brac University (Tarc)</h3>\r\n<div class=\"absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-500 rounded-b-2xl\"> </div>\r\n</div>\r\n<div class=\"group relative bg-white  rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 cursor-pointer overflow-hidden border border-emerald-100/50 hover:border-emerald-400 animate-scale-in\">\r\n<div class=\"absolute inset-0 bg-gradient-to-br from-[#0d3d2d]/5 to-emerald-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500 rounded-2xl\"> </div>\r\n<div class=\"absolute top-0 right-0 w-16 h-16 bg-gradient-to-br from-emerald-400/20 to-transparent rounded-bl-full opacity-0 group-hover:opacity-100 transition-opacity duration-500\"> </div>\r\n<div class=\"relative aspect-square bg-gradient-to-br from-neutral-100 to-neutral-200 rounded-t-xl mb-3 sm:mb-4 overflow-hidden ring-1 ring-neutral-200 group-hover:ring-emerald-400 transition-all duration-500\"><img class=\"w-full h-full object-cover group-hover:scale-110 transition-transform duration-700\" src=\"https://www.dpremiumhomes.com/assets/Manarat-BBRfHwvp.png\" alt=\"Manarat International University\">\r\n<div class=\"absolute inset-0 bg-gradient-to-t from-[#0d3d2d]/30 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500\"> </div>\r\n<div class=\"absolute top-2 right-2 w-8 h-8 bg-white rounded-full shadow-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transform scale-50 group-hover:scale-100 transition-all duration-500\"> </div>\r\n</div>\r\n<h3 class=\"relative p-4 font-semibold text-center text-xs sm:text-sm md:text-base text-neutral-800 group-hover:text-[#0d3d2d] transition-colors duration-300\">Manarat International University</h3>\r\n<div class=\"absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-500 rounded-b-2xl\"> </div>\r\n</div>\r\n<div class=\"group relative bg-white  rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 cursor-pointer overflow-hidden border border-emerald-100/50 hover:border-emerald-400 animate-scale-in\">\r\n<div class=\"absolute inset-0 bg-gradient-to-br from-[#0d3d2d]/5 to-emerald-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500 rounded-2xl\"> </div>\r\n<div class=\"absolute top-0 right-0 w-16 h-16 bg-gradient-to-br from-emerald-400/20 to-transparent rounded-bl-full opacity-0 group-hover:opacity-100 transition-opacity duration-500\"> </div>\r\n<div class=\"relative aspect-square bg-gradient-to-br from-neutral-100 to-neutral-200 rounded-t-xl mb-3 sm:mb-4 overflow-hidden ring-1 ring-neutral-200 group-hover:ring-emerald-400 transition-all duration-500\"><img class=\"w-full h-full object-cover group-hover:scale-110 transition-transform duration-700\" src=\"https://www.dpremiumhomes.com/assets/mosque-ClDhUgQu.jpg\" alt=\"Masjid\">\r\n<div class=\"absolute inset-0 bg-gradient-to-t from-[#0d3d2d]/30 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500\"> </div>\r\n<div class=\"absolute top-2 right-2 w-8 h-8 bg-white rounded-full shadow-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transform scale-50 group-hover:scale-100 transition-all duration-500\"> </div>\r\n</div>\r\n<h3 class=\"relative p-4 font-semibold text-center text-xs sm:text-sm md:text-base text-neutral-800 group-hover:text-[#0d3d2d] transition-colors duration-300\">Masjid</h3>\r\n</div>', '6943d733818a7_1766053683.png', '6943d73372ce5_1766053683.png', 'https://drive.google.com/file/d/1Shb6eNMOd6KtKCO4aMYMVhIsVJn_qf7n/view?usp=sharing', 2, 1, '2025-12-18 10:28:03', '2025-12-18 10:28:03', 1, 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Admin', '2025-12-06 04:14:57', '2025-12-06 04:14:57'),
(2, 'Employee', '2025-12-06 08:25:53', '2025-12-06 08:25:53'),
(3, 'super Admin', '2025-12-06 11:09:07', '2025-12-06 11:09:07'),
(4, 'HR', '2025-12-17 06:46:21', '2025-12-17 06:46:21');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(3, 1),
(3, 2),
(3, 3),
(3, 4),
(3, 5),
(4, 1),
(4, 3),
(4, 5);

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `website_title` varchar(255) DEFAULT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `footer_text` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `website_title`, `website_url`, `footer_text`, `logo`, `phone`, `alt_phone`, `email`, `alt_email`, `address`, `copyright`, `facebook_link`, `messenger_link`, `instagram_link`, `tiktok_link`, `x_link`, `youtube_link`, `header_code`, `body_code`, `created_at`, `updated_at`) VALUES
(1, 'the premium home limited', NULL, 'fxdgrdgth', '693ffb8fc1e73_1765800847.png', '+8801958253300', '+8801958253300', 'info@thepremiumhomes.com', NULL, 'Dhaka', '© 2025 The Premium Homes Ltd. All rights reserved.', 'uidysfb re', 'kuysret87', 'uidsfyter', 'dsilfu67rt', 'iureyt87e', 'ids7rt89', '', '', '2025-12-15 12:14:07', '2025-12-15 12:23:21');

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
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sliders`
--

INSERT INTO `sliders` (`id`, `title`, `subtitle`, `link`, `image`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Slider 1', 'no sub title', '', '693fc40ad67f6_1765786634.jpg', 1, '2025-12-15 14:17:14', '2025-12-15 14:17:14'),
(2, 'slider 2', 'not title', '', '693fd0f16af19_1765789937.webp', 1, '2025-12-15 15:12:17', '2025-12-15 15:12:17');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
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

INSERT INTO `tasks` (`id`, `employee_id`, `assigned_by`, `title`, `description`, `start_date`, `deadline`, `employee_accept`, `status`, `progress`, `priority`, `created_at`, `updated_at`) VALUES
(3, 7, 1, 'Task 1', '<p>Please do first</p>', '2025-12-02', '2025-12-27 10:38:07', 0, 'pending', 0, 'high', '2025-12-08 11:33:52', '2025-12-09 08:46:53'),
(4, 7, 2, 'Task 2', '<p>test description</p>', '2025-12-02', '2025-12-18 10:38:13', 0, 'pending', 0, 'high', '2025-12-08 11:35:32', '2025-12-09 08:46:58'),
(5, 13, 1, 'Task 2', '<p>do first</p>', '2025-12-11', '2025-12-11 00:00:00', 0, '', 0, '', '2025-12-10 09:15:05', NULL),
(6, 13, 1, 'Task 2', '<p>do first</p>', '2025-12-11', '2025-12-11 00:00:00', 0, '', 0, '', '2025-12-10 09:19:52', NULL);

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
(3, 'Unit C', '2025-12-18 11:04:55', '2025-12-18 11:04:55');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `user_name`, `phone`, `email`, `designation_id`, `address`, `nid`, `role_id`, `password`, `photo`, `media_link1`, `media_link2`, `last_logout`, `created_at`, `updated_at`) VALUES
(2, 'Mehedi Hasan Jony', '', '01710000000', 'jony@gmail.com', 1, '0', '908927387', 3, '$2y$10$tyGb6XsaCzqrB5/fmTtTEualGz9EEsCTS4F0mAz7b/ITjBISkKirS', '693e8e3dac751_1765707325.jpg', '', '', '2025-12-14 12:35:32', '2025-12-09 07:53:41', '2025-12-14 10:15:25'),
(3, 'employee-1', '', '01800000000', 'employee1@gmail.com', 1, '0', '1230000', 2, '$2y$10$xzoToZ2.n3Zfyj11JmM6n.TNtAmP0WArM.OioGwNQeJenGCtxCY3q', '693e8cdbbdb7f_1765706971.jpg', '', '', '2025-12-10 15:43:28', '2025-12-10 04:23:16', '2025-12-14 10:09:31'),
(4, 'Mr.X', 'X', '01712000000', 'x@gmail.com', 1, 'Dhaka', '', 2, '$2y$10$hJW2yO/zHIo90adHDAzHNub1LhaMY/28k3rLDuejUYgEYKkD.ZbSC', NULL, '832764', '87436', '2025-12-10 15:09:59', '2025-12-10 09:09:59', '2025-12-14 05:18:11'),
(5, 'Admin', '', '01809999999', 'admin@admin.com', 1, 'Dhaka', '123456', 1, '$2y$10$.sBkFmSga6GfHwV9DZaKFeJv.BeShxh63EjLJ45wIqk1C0V8iQDGi', '', '', '', '2025-12-18 10:11:50', '2025-12-10 09:42:03', '2025-12-18 04:12:54'),
(12, 'Farzana', 'Farzana', '01711111111', 'farz@gmail.com', 1, 'Dhaka', '12345678', 3, '$2y$10$E0Yhq.1IcOfIhUepelWIieBg7O9L6XhEKWNmRhD1v2rT0WRH5Q7T6', '693e4b6b1f340_1765690219.jpg', 'https://business.facebook.com/', 'https://www.linkedin.com/', '2025-12-18 10:13:06', '2025-12-14 05:30:19', '2025-12-18 04:13:06'),
(13, 'Nusrat', '', '01789000000', 'nusrat@gmail.com', 1, '23', '123456', 2, '$2y$10$3CN1C.iRuSmfS4qjtmGuZupZjaBqCLCTBsDEacL5yBX.TDoInJxRW', '', '', '', '2025-12-14 17:34:07', '2025-12-14 06:34:19', '2025-12-14 11:34:07'),
(14, 'Farhana', 'Farhana', '01700000000', 'farhana@gmail.com', 1, 'Dhaka', '2345676', 2, '$2y$10$lxw8JSnTFc75E7i0QyuKEuxgedIf2l7E/y94jxlnZzG2EjloA2TYu', NULL, 'www.google.com', 'www.linkedin.com', '2025-12-14 14:40:51', '2025-12-14 08:40:51', '2025-12-14 08:40:51');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `blogs`
--
ALTER TABLE `blogs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `communities`
--
ALTER TABLE `communities`
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
-- Indexes for table `flats`
--
ALTER TABLE `flats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `unit_id` (`unit_id`);

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_leads_owner` (`owner_id`);

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
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `location_id` (`location_id`),
  ADD KEY `community_id` (`community_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `idx_employee_id` (`employee_id`);

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
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `blogs`
--
ALTER TABLE `blogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `communities`
--
ALTER TABLE `communities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `designations`
--
ALTER TABLE `designations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `flats`
--
ALTER TABLE `flats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `lead_sources`
--
ALTER TABLE `lead_sources`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sliders`
--
ALTER TABLE `sliders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `designations`
--
ALTER TABLE `designations`
  ADD CONSTRAINT `designations_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employees_ibfk_2` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_employee_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
  ADD CONSTRAINT `fk_leads_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
