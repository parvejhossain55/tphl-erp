-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 03, 2026 at 07:57 AM
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
(1, 'The Premium Smart City', 1, '2025-12-18 06:41:23', '2025-12-18 06:44:31'),
(2, 'Test2', 1, '2025-12-28 11:08:39', '2025-12-28 11:08:39');

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
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'Medical Leave', 0, '2025-12-07 11:24:44', '2025-12-30 03:58:19'),
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
  `due_emi_amount` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `emi_schedules`
--

INSERT INTO `emi_schedules` (`id`, `lead_booking_id`, `emi_no`, `emi_amount`, `due_date`, `status`, `lead_payment_id`, `created_at`, `due_emi_amount`) VALUES
(9, 5, 1, 5555.00, '2025-12-27', 'paid', 29, '2025-12-27 10:40:50', 55.00),
(10, 5, 2, 54555.00, '2025-12-27', 'paid', 30, '2025-12-27 10:41:30', 33.00),
(34, 3, 1, 100000.00, '2025-12-30', 'paid', 66, '2025-12-30 05:55:25', 0.00),
(35, 3, 2, 200000.00, '2025-12-30', 'paid', 67, '2025-12-30 06:00:45', 0.00),
(36, 3, 3, 500000.00, '2025-12-30', 'paid', 68, '2025-12-30 06:02:24', 0.00);

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
  `basic_salary` float DEFAULT 0,
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

INSERT INTO `employees` (`id`, `user_id`, `full_name`, `employee_id`, `username`, `email`, `father_name`, `phone`, `emergency_contact`, `qualification`, `experience`, `address`, `pass_num`, `nid`, `department_id`, `designation_id`, `dob`, `joining_date`, `basic_salary`, `account_holder_name`, `account_number`, `make_user`, `password`, `bank_name`, `branch_name`, `social_media1`, `social_media2`, `social_media3`, `photo`, `created_at`, `updated_at`) VALUES
(7, NULL, 'Hasan ', '1', 'hasan', 'hasan@gmail.com', '', '01700000000', '0171000000', 'BBA', '<p>gyjhasdt ikf ujsdfy7u hdsa8fy</p>', 'Dhaka', '', NULL, 5, 1, '1999-12-01', '2025-01-01', 0, 'Hasan', '1111111111111', 0, '', 'Dhaka Bank', 'Dhaka', '123', '123', '', '693d3e8496d75_1765621380.jpg', '2025-12-08 05:06:42', '2025-12-13 10:23:00'),
(8, NULL, 'Shihan', '12', 'shihan', 'shihan@gmail.com', 'Shahid', '01805997399', '01805997399', 'MBA', '<p>iuydsr</p>', 'Dhaka', '', NULL, 1, 1, '0000-00-00', '0000-00-00', 0, 'Abc', '01983274', 1, '', 'abc', '', '832764', '87436', '', '693d40fdf0513_1765622013.png', '2025-12-10 07:28:56', '2025-12-13 10:33:34'),
(13, 4, 'Mr.X', '90', 'X', 'x@gmail.com', 'Y', '01712000000', '01712000000', 'MBA', '<p>weqrewtr</p>', 'Dhaka', '', NULL, 3, 1, '0000-00-00', '0000-00-00', 0, 'Abc', 'qwer', 1, '123456', 'weq', '', '832764', '87436', '', '693d4070c78b1_1765621872.png', '2025-12-10 09:09:59', '2025-12-13 10:31:12'),
(14, 5, 'Hamid', '04', 'hamid', 'hamid@gmail.com', 'hamim', '01809999999', '01809999999', 'MBA', '<p>jdfgur kjdf kjdfhg8uer</p>', 'Dhaka', '', NULL, 1, 1, '2000-12-14', '0000-00-00', 0, 'Abc', '01983274', 1, '123456', 'abc', '', '832764', '87436', '', '693d3fe2c332d_1765621730.png', '2025-12-10 09:42:03', '2025-12-13 10:28:50'),
(26, NULL, 'Farhan', '13', 'Farhan', 'farhan@gmail.com', 'aziz', '01790120923', '01805997399', 'MBA', '<p>fsdygiii</p>', 'Dhaka', '', NULL, 1, 1, '1990-01-01', '2025-01-01', 0, 'Farhan', '98234734', 1, '$2y$10$96BGmKUmreuE95rOQ8T6NehOqMlmOT0IkpoUBQuaHkEb9wiWfJvIC', 'Dhaka', 'Dhaka', 'www.google.com', 'www.google.com', '', '693d3fc682ed5_1765621702.png', '2025-12-10 12:28:53', '2025-12-13 10:28:22'),
(27, NULL, 'Mehedi Hasan Jony', '123', '@jony', 'jony@gmail.com', 'Unknown', '01786777777', '01786777777', 'MBA', '<p>No description</p>', '23\r\nSEL HUQ SKYPARK\r\n2 Mirpur Rd', '', NULL, 3, 1, '2025-01-01', '2025-01-01', 0, 'Mehedi H. Jony', '615234', 0, '', 'City Bank', '', 'www.facebbok.com', 'www.linkedin.com', '', '693d4f012d0cd_1765625601.jpg', '2025-12-13 11:33:01', '2025-12-13 11:33:22'),
(29, 13, 'Nusrat', '189', 'Nusrat', 'nusrat@gmail.com', 'Nur', '01789000000', '01789000000', 'MBA', '<p>jkfdh kdf</p>', '23\r\nSEL HUQ SKYPARK\r\n2 Mirpur Rd', '', NULL, 1, 1, '0000-00-00', '2025-01-01', 0, 'Nusrat', '1263214324', 1, '$2y$10$..eFW2QctwO7qH3KucZSfuRQEeYudFY.citf5I0FJewqOsJWRAQ2W', 'Dhaka Bank', '', 'www.google.com', 'www.linkedin.com', '', '693e5a6b64a45_1765694059.png', '2025-12-14 06:34:19', '2025-12-28 10:16:20'),
(32, 14, 'Farhana', '1827', 'Farhana', 'farhana@gmail.com', 'hamim', '01700000000', '0171000000', 'BSC', '<p>rjeiouort</p>', 'Dhaka', '', '2345676', 1, 1, '0000-00-00', '0000-00-00', 200000, 'Abc', '1111111111111', 1, '$2y$10$lxw8JSnTFc75E7i0QyuKEuxgedIf2l7E/y94jxlnZzG2EjloA2TYu', 'Dhaka Bank', '', 'www.google.com', 'www.linkedin.com', '', '693e781139293_1765701649.jpg', '2025-12-14 08:40:51', '2025-12-29 05:38:45'),
(33, NULL, 'Employee Test', 'EMP-00033', 'test2', 'test2@gmail.com', 'None', '01711000000', '01711000000', 'Mba', '<p>fgg</p>', 'Dhaka', '', '7612356324', 3, 1, '0000-00-00', '2025-01-01', 25000, 'Test', '22222222', 0, '', 'Brack', '', 'www.google.com', 'www.linkedin.com', '', 'employees_695210bf5d099.jpg', '2025-12-29 05:25:20', '2025-12-29 05:43:01');

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
  `bedroom` int(11) DEFAULT NULL,
  `bathroom` int(11) DEFAULT NULL,
  `balcony` int(11) DEFAULT NULL,
  `flat_name` varchar(150) DEFAULT NULL,
  `drawing_dining_kitchen` tinyint(1) DEFAULT NULL,
  `common_dining` tinyint(1) DEFAULT NULL,
  `floor_no` int(11) DEFAULT NULL,
  `facing_site` varchar(50) DEFAULT NULL,
  `price_value` decimal(12,2) DEFAULT NULL,
  `facilities` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_booked` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flats`
--

INSERT INTO `flats` (`id`, `project_id`, `unit_id`, `sqft`, `bhk`, `bedroom`, `bathroom`, `balcony`, `flat_name`, `drawing_dining_kitchen`, `common_dining`, `floor_no`, `facing_site`, `price_value`, `facilities`, `created_at`, `updated_at`, `is_booked`) VALUES
(1, 1, 1, 240, 2, 3, 2, 2, 'Unit - A5', 0, 0, 5, '0', 40000000.00, 'Gym', '2025-12-18 11:58:43', '2025-12-18 12:06:55', 1),
(2, 2, 2, 200, 3, 2, 2, 1, 'Unit - B3', 1, 1, 3, 'south', 4000000.00, 'Gym', '2025-12-20 07:21:57', '2025-12-20 07:21:57', 1),
(3, 2, 7, 1200, 3, 1, 2, 1, 'Unit - A1', 1, 1, 1, '0', 6000000.00, 'Gym', '2025-12-22 08:06:06', '2025-12-22 08:06:06', 0),
(4, 2, 5, 1200, 12, 3, 2, 2, 'Unit - A2', 1, 1, 2, '0', 50000000.00, 'Gym', '2025-12-23 10:53:59', '2025-12-23 10:53:59', 0),
(5, 1, 8, 1400, 2, 3, 3, 2, 'Unit - B3', 1, 1, 3, 'east', 2000000.00, 'Gym', '2025-12-23 10:56:45', '2025-12-23 10:56:45', 0),
(6, 3, 4, 1300, 3, 3, 3, 3, 'Unit - A2', 1, 1, 1, 'west', 5000000.00, 'Gym', '2025-12-23 11:00:45', '2025-12-23 11:00:45', 0),
(7, 2, NULL, 1700, 3, 3, 3, 2, 'Unit - A1', 1, 1, 1, '0', 5000000.00, 'Gym', '2025-12-28 06:41:39', '2025-12-28 06:41:39', 0);

-- --------------------------------------------------------

--
-- Table structure for table `flat_purchase_rules`
--

CREATE TABLE `flat_purchase_rules` (
  `id` int(11) NOT NULL,
  `rules_description` text DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flat_purchase_rules`
--

INSERT INTO `flat_purchase_rules` (`id`, `rules_description`, `status`, `created_at`, `updated_at`) VALUES
(2, 'The whole construction cost will be paid by all the shareholders in a step-by-step manner as per the decision.', 1, '2025-12-31 13:12:21', '2025-12-31 13:12:21'),
(3, 'If someone is unable to pay the full price of the land within the stipulated time, his flat allotment will be canceled and the remaining amount will be refunded after deducting 15% of the office service charge. The flat may be allotted elsewhere. (Negotiable).', 1, '2025-12-31 13:12:21', '2025-12-31 13:12:21'),
(4, 'Shareholders shall transfer the power and authority to construct the building to The Premium Homes Ltd.', 1, '2025-12-31 13:12:21', '2025-12-31 13:12:21'),
(5, '% of the construction cost will be paid as a service charge to the authority for implementation and supervision of construction work. The said percentage will be accepted as logistic support only.', 1, '2025-12-31 13:12:21', '2025-12-31 13:12:21'),
(6, 'The authority reserves the right to make necessary changes in the design and layout of the project at any time due to unavoidable reasons.', 1, '2025-12-31 13:12:21', '2025-12-31 13:12:21'),
(7, 'Both parties shall endeavor to complete the whole project within months from the commencement of construction work.', 1, '2025-12-31 13:12:21', '2025-12-31 13:12:21'),
(8, 'If the transfer of flats is delayed due to political, natural calamities, government restrictions, it should be accepted in the larger interest of the project.', 1, '2025-12-31 13:12:21', '2025-12-31 13:20:16'),
(9, 'Shareholders can book flats according to their choice on a first-come, first-served basis. The proposed building will have a basement and ground floor parking, and parking will be allocated for each flat. Additional Tk. 00 will be paid by the shareholder during the construction period for each parking space with the same installment', 1, '2025-12-31 13:24:38', '2025-12-31 13:24:38'),
(10, 'Both parties shall endeavor to complete the whole project within months from the commencement of construction work.', 1, '2025-12-31 13:24:38', '2025-12-31 13:24:38'),
(11, 'If the transfer of flats is delayed due to political, natural calamities, government restrictions, it should be accepted in the larger interest of the project.', 1, '2025-12-31 13:24:38', '2025-12-31 13:24:38'),
(12, 'If 03 (three) installments or equivalent amount of 03 (three) installments are outstanding during the construction period, the flat allotted to the shareholder shall be deemed to be canceled, and the flat shall be transferred to The Premium Homes Ltd. You will be obliged to register elsewhere under the supervision of the authorities. No objection will be accepted in this regard.', 1, '2025-12-31 13:24:38', '2025-12-31 13:24:38'),
(13, 'The allocated flat can be sold to anyone. If preferred, it may be sold through the authority, in which case a service charge will be applicable. The cost of ownership transfer and Mutation must be borne by the seller.', 1, '2025-12-31 13:24:38', '2025-12-31 13:24:38'),
(14, 'The decision of the authority in any matter shall be final.', 1, '2025-12-31 13:24:38', '2025-12-31 13:24:38');

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `lead_type` enum('Hot','Warm','Cold') NOT NULL DEFAULT 'Hot',
  `present_add_bn` varchar(255) DEFAULT NULL,
  `phone` varchar(30) NOT NULL,
  `alt_phone_number` varchar(30) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `source_type_id` int(11) DEFAULT NULL,
  `assigned_id` int(11) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `mother_name` varchar(100) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `nid_or_passport` varchar(100) DEFAULT NULL,
  `identity_type` enum('NID','Passport') NOT NULL DEFAULT 'NID',
  `permanent_address` varchar(250) DEFAULT NULL,
  `pref_location` varchar(255) DEFAULT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `project_type` enum('ATI Society','ASHU Society') NOT NULL,
  `permanent_add_bn` varchar(250) DEFAULT NULL,
  `pref_flat_size` varchar(250) NOT NULL,
  `communication_type` enum('WA Messaging','WA Conversion') DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `visibility` enum('public','private','selected') DEFAULT 'public',
  `status` enum('active','inactive') DEFAULT 'active',
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

INSERT INTO `leads` (`id`, `full_name`, `lead_type`, `present_add_bn`, `phone`, `alt_phone_number`, `email`, `source_type_id`, `assigned_id`, `address`, `father_name`, `mother_name`, `dob`, `nid_or_passport`, `identity_type`, `permanent_address`, `pref_location`, `designation`, `project_type`, `permanent_add_bn`, `pref_flat_size`, `communication_type`, `tags`, `description`, `visibility`, `status`, `lead_quality`, `lead_image`, `lead_name_bn`, `father_name_bn`, `mother_name_bn`, `profession_bn`, `nationality`, `religion`, `nominee_name`, `nomi_father_name`, `nomi_mother_name`, `nomi_profession`, `relation_with_lead`, `nomi_dob`, `nomi_nid_passport`, `nomi_phone`, `nomi_email`, `nomi_present_add`, `nomi_permanent_address`, `nomi_image`, `created_at`, `updated_at`, `residency_type`, `country`, `preferred_contact_time`, `has_pending_followup`) VALUES
(2, 'Hasin', 'Hot', 'Company X', '01700000000', '0180000000', 'hasan@gmail.com', 1, 13, 'Dhaka', NULL, NULL, NULL, NULL, 'NID', NULL, 'Dhaka', 'Project Manager', 'ATI Society', 'Project Wise', '345 sqf', 'WA Messaging', 'No nedd', 'fghyt uiy6we', 'public', 'active', 'Prospect', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-11 07:07:51', '2025-12-23 03:26:45', 'local', NULL, NULL, 0),
(4, 'Hasin2', 'Hot', 'Company X', '01700000000', '0180000000', 'hasan@gmail.com', 1, 13, '0', NULL, NULL, NULL, NULL, 'NID', NULL, 'Dhaka', 'Project Manager', 'ATI Society', 'Project Wise', '345 sqf', 'WA Messaging', 'No nedd', '', 'public', 'active', 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-11 09:56:45', '2025-12-22 07:41:30', 'local', NULL, NULL, 0),
(6, 'MH. Jony', 'Hot', 'Company X', '01700000000', '0180000000', 'hasan@gmail.com', 1, 12, 'Dhaka', NULL, NULL, NULL, NULL, 'NID', NULL, 'Dhaka', 'Project Manager', 'ATI Society', 'Project Wise', '345 sqf', 'WA Messaging', 'No nedd', '', 'public', 'active', 'Not Interested', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-20 08:11:43', '2025-12-20 08:11:43', 'local', NULL, NULL, 0),
(7, 'Mr - Y', 'Warm', '', '01600000000', '01600000000', 'y@gmail.com', 1, 12, 'Dhaka', NULL, NULL, NULL, NULL, 'NID', NULL, 'Dhaka', 'Business Man', 'ATI Society', '', '1760', 'WA Messaging', 'no node', 'Test', 'public', 'active', 'Prospect', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-27 11:24:29', '2025-12-29 04:56:17', 'local', NULL, NULL, 0),
(8, 'ABDUL AZIZ', 'Hot', 'ভিয়া রাইমনডো, ভিগনা ৪/৭, জেনোয়া, ইতালী', '01743579987', NULL, 'aziz@gmail.com', 1, 12, 'Itali', 'NURUL ISLAM', 'KULSUM', '2025-12-23', '98233728', 'NID', 'Loxipur', 'Dhaka', 'Job', 'ATI Society', 'গ্রামঃ দাননপুর, ওয়ার্ড ০৬, দত্তাপাড়া, লক্ষ্মীপুর সদর, লক্ষ্মীপুর', '1230', 'WA Messaging', '', NULL, 'public', 'active', 'Prospect', 'leads_695632097012e.png', 'আব্দুল আজিজ', 'নুরুল ইসলাম', 'কুলসুম বেগম', 'চাকরী', 'Bangladeshi', 'Islam', 'EVA ALAM', 'KHURSHED', 'AYSHA ALAM', 'housewife', 'Wife', '2026-01-02', '12364743', '0187643875', '', 'Dhaka', 'Dhaka', 'leads_69563209746dc.jpg', '2025-12-31 08:43:03', '2026-01-01 09:36:42', 'local', NULL, NULL, 0);

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lead_bookings`
--

INSERT INTO `lead_bookings` (`id`, `lead_id`, `project_id`, `flat_id`, `agreement_value`, `discount_amount`, `booking_date`, `booking_status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 2, 2, 2, 2000000.00, NULL, '2025-12-21', '', 12, '2025-12-21 10:13:37', '2025-12-21 10:13:37'),
(2, 4, 1, 1, 3000000.00, NULL, '2025-12-21', 'booked', 12, '2025-12-21 11:25:54', '2025-12-21 11:25:54'),
(3, 4, 3, 3, 10000000.00, NULL, '2025-12-22', 'booked', 12, '2025-12-22 08:55:27', '2025-12-22 08:55:27'),
(4, 2, 3, 3, 2000000.00, NULL, '2025-12-23', 'booked', 12, '2025-12-23 04:18:00', '2025-12-23 04:18:00'),
(5, 6, 2, 3, 2000000.00, NULL, '2025-12-23', 'booked', 12, '2025-12-23 08:29:43', '2025-12-23 08:29:43'),
(6, 8, 3, 6, 1000000.00, NULL, '2025-12-31', 'booked', 12, '2025-12-31 08:51:19', '2025-12-31 08:51:19');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lead_follow_ups`
--

INSERT INTO `lead_follow_ups` (`id`, `lead_id`, `project_id`, `flat_id`, `follow_up_date`, `follow_up_time`, `follow_up_type`, `status`, `discussion_note`, `next_follow_up_date`, `assigned_with`, `next_action`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 4, 1, 1, '2025-12-20', '16:10:00', 'visit', 'completed', 'hjysdfure', '2025-12-20', 7, 'booking', 1, '2025-12-20 07:10:42', '2026-01-03 06:14:58'),
(2, 6, 2, 2, '2025-12-19', '15:45:00', 'call', 'pending', '', '2025-12-18', 8, 'site visit', 1, '2025-12-20 07:45:18', '2026-01-03 05:25:40'),
(3, 6, 2, 2, '2025-12-20', '04:13:00', 'call', 'pending', '', '2025-12-20', 13, 'site visit', 1, '2025-12-20 08:13:09', '2026-01-03 05:25:46'),
(4, 2, 1, 2, '2025-12-20', '17:24:00', 'visit', 'pending', '', '2025-12-20', 14, 'booking', 1, '2025-12-20 11:24:43', '2026-01-03 05:25:50'),
(5, 7, 1, 5, '2026-01-01', '11:37:00', 'call', 'pending', 'No Decision Take', '2026-01-03', 14, 'call_back', 1, '2026-01-03 05:38:28', '2026-01-03 05:51:40');

-- --------------------------------------------------------

--
-- Table structure for table `lead_payments`
--

CREATE TABLE `lead_payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `payment_type` enum('booking_money','registration','emi') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','bank','online','cheque') DEFAULT 'cash',
  `payment_status` enum('paid','pending','failed') DEFAULT 'paid',
  `payment_date` date NOT NULL,
  `total_emi_no` int(11) DEFAULT NULL,
  `emi_total` int(11) DEFAULT NULL,
  `transaction_ref` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_no` varchar(50) DEFAULT NULL,
  `cheque_no` varchar(50) DEFAULT NULL,
  `online_type` varchar(50) DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lead_payments`
--

INSERT INTO `lead_payments` (`id`, `booking_id`, `payment_type`, `amount`, `payment_method`, `payment_status`, `payment_date`, `total_emi_no`, `emi_total`, `transaction_ref`, `bank_name`, `bank_account_no`, `cheque_no`, `online_type`, `created_by`, `created_at`) VALUES
(1, 1, 'booking_money', 20000000.00, 'bank', 'paid', '2025-12-21', NULL, NULL, '12345', NULL, NULL, NULL, NULL, 12, '2025-12-21 11:01:25'),
(5, 2, 'booking_money', 3000000.00, 'cash', 'paid', '2025-12-22', NULL, NULL, '123456', NULL, NULL, NULL, NULL, 12, '2025-12-22 08:56:21'),
(7, 5, 'booking_money', 2000000.00, 'bank', 'paid', '2025-12-23', NULL, NULL, '122334', NULL, NULL, NULL, NULL, 12, '2025-12-23 08:32:22'),
(8, 3, 'booking_money', 10000000.00, 'bank', 'paid', '2025-12-24', NULL, NULL, '123', 'City Bank', '123454667', '', '', 5, '2025-12-24 06:07:19'),
(9, 6, 'booking_money', 1000000.00, 'cheque', 'paid', '2025-12-31', 48, NULL, 'Eva', 'BRAC Bank', '', '24343465', '', 12, '2025-12-31 09:57:06');

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
(1, 'Casual Leave', 1, '2025-12-15 06:53:05', '2025-12-29 03:54:13');

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
(1, 'Atibazar ', '2025-12-29 04:21:52', '2025-12-29 04:22:46'),
(2, 'Ashuliya ,Dhaka', '2025-12-22 06:16:20', '2025-12-22 06:16:20');

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
(5, 'Chairman', 8, 'Mr. Mustafizul Huda', '2025-12-30', 'fdfdas', 'Investment from D-Smart Group', 85555999.00, 'cash', 'paid', '', '', '', '', '', 5, '2025-12-30 07:56:04', '2025-12-30 07:56:04');

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
  `employee_id` int(10) UNSIGNED NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `assigned_by` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `employee_id`, `task_id`, `assigned_by`, `message`, `is_read`, `created_at`) VALUES
(2, 32, 7, 12, 'New task assigned: Task-2', 1, '2025-12-27 07:00:12'),
(3, 32, 8, 12, 'Task reassigned: Test Task', 0, '2025-12-28 03:20:28'),
(4, 32, 8, 12, 'Task reassigned: Test Task', 0, '2025-12-28 03:39:24');

-- --------------------------------------------------------

--
-- Table structure for table `payslips`
--

CREATE TABLE `payslips` (
  `id` int(11) NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `designation_id` int(11) DEFAULT NULL,
  `payslip_month` date NOT NULL,
  `total_earning` decimal(10,2) DEFAULT 0.00,
  `total_deduction` decimal(10,2) DEFAULT 0.00,
  `net_salary` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','approved','paid') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_method` enum('bank','cash','mobile_banking') NOT NULL DEFAULT 'bank',
  `company_bank_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payslips`
--

INSERT INTO `payslips` (`id`, `employee_id`, `designation_id`, `payslip_month`, `total_earning`, `total_deduction`, `net_salary`, `status`, `created_at`, `updated_at`, `payment_method`, `company_bank_id`) VALUES
(1, 33, 1, '2025-12-01', 26000.00, 1000.00, 25000.00, 'pending', '2025-12-30 17:18:10', '2025-12-30 17:18:10', 'bank', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payslip_details`
--

CREATE TABLE `payslip_details` (
  `id` int(11) NOT NULL,
  `payslip_id` int(11) NOT NULL,
  `payslip_item_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payslip_details`
--

INSERT INTO `payslip_details` (`id`, `payslip_id`, `payslip_item_id`, `amount`) VALUES
(1, 1, 2, 1000.00),
(2, 1, 7, 1000.00);

-- --------------------------------------------------------

--
-- Table structure for table `payslip_items`
--

CREATE TABLE `payslip_items` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('earning','deduction') NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payslip_items`
--

INSERT INTO `payslip_items` (`id`, `name`, `type`, `description`, `created_at`) VALUES
(2, 'House Rent Allowance', 'earning', NULL, '2025-12-30 13:35:46'),
(3, 'Medical Allowance', 'earning', NULL, '2025-12-30 13:36:01'),
(4, '   Transport Allowance', 'earning', NULL, '2025-12-30 13:36:25'),
(5, '  Other Allowances', 'earning', NULL, '2025-12-30 13:36:33'),
(6, 'Tax (Income Tax / TDS) ', 'deduction', NULL, '2025-12-30 13:37:06'),
(7, 'Loan / Advance Deduction ', 'deduction', NULL, '2025-12-30 13:37:41'),
(8, ' Absence / Leave Deduction ', 'deduction', NULL, '2025-12-30 13:37:54'),
(9, ' Other Deductions ', 'deduction', NULL, '2025-12-30 13:38:09');

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
  `passenger_lift` int(11) DEFAULT NULL,
  `cargo_lift` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `rooftop_gardening` tinyint(1) DEFAULT 0 COMMENT '1=Yes, 0=No',
  `swimming_pool` tinyint(1) DEFAULT 0 COMMENT '1=Yes, 0=No',
  `car_parking` int(11) DEFAULT NULL,
  `convention` tinyint(1) DEFAULT 0 COMMENT '1=Yes, 0=No',
  `electricity_backup` tinyint(1) DEFAULT 0 COMMENT '1=Yes, 0=No'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `name`, `location_id`, `community_id`, `building_type`, `total_share`, `unit_per_floor`, `types`, `image`, `image2`, `image3`, `front_road`, `price_range`, `hover_text`, `tag`, `plot_size`, `plot_no`, `block_no`, `brochure_link`, `description`, `apartment_count`, `amenities_details`, `amenities_image`, `map_image`, `map_link`, `passenger_lift`, `cargo_lift`, `created_at`, `updated_at`, `rooftop_gardening`, `swimming_pool`, `car_parking`, `convention`, `electricity_backup`) VALUES
(1, 'The Premium Nobel Court', 1, 1, '', 40, 4, 'STUDIO, 1, 2 BEDROOMS & PENTHOUSE', '6943d73330f2d_1766053683.png', '6943d733450c3_1766053683.png', '6943d7335c2f2_1766053683.png', '', 'Under 50 Lac BDT', NULL, 'SOLD OUT', 0, '', '', 'https://drive.google.com/file/d/1Shb6eNMOd6KtKCO4aMYMVhIsVJn_qf7n/view?usp=sharing', '<p>descriptions</p>', 1, '<div class=\"group relative bg-white  rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 cursor-pointer overflow-hidden border border-emerald-100/50 hover:border-emerald-400 animate-scale-in\">\r\n<h3 class=\"relative p-4 font-semibold text-center text-xs sm:text-sm md:text-base text-neutral-800 group-hover:text-[#0d3d2d] transition-colors duration-300\">Daffodil International University</h3>\r\n<div class=\"absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-500 rounded-b-2xl\"> </div>\r\n</div>\r\n<div class=\"group relative bg-white  rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 cursor-pointer overflow-hidden border border-emerald-100/50 hover:border-emerald-400 animate-scale-in\">\r\n<div class=\"absolute inset-0 bg-gradient-to-br from-[#0d3d2d]/5 to-emerald-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500 rounded-2xl\"> </div>\r\n<div class=\"absolute top-0 right-0 w-16 h-16 bg-gradient-to-br from-emerald-400/20 to-transparent rounded-bl-full opacity-0 group-hover:opacity-100 transition-opacity duration-500\"> </div>\r\n<div class=\"relative aspect-square bg-gradient-to-br from-neutral-100 to-neutral-200 rounded-t-xl mb-3 sm:mb-4 overflow-hidden ring-1 ring-neutral-200 group-hover:ring-emerald-400 transition-all duration-500\"><img class=\"w-full h-full object-cover group-hover:scale-110 transition-transform duration-700\" src=\"https://www.dpremiumhomes.com/assets/Brac-Dt9jTuCp.png\" alt=\"Brac University (Tarc)\">\r\n<div class=\"absolute inset-0 bg-gradient-to-t from-[#0d3d2d]/30 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500\"> </div>\r\n<div class=\"absolute top-2 right-2 w-8 h-8 bg-white rounded-full shadow-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transform scale-50 group-hover:scale-100 transition-all duration-500\"> </div>\r\n</div>\r\n<h3 class=\"relative p-4 font-semibold text-center text-xs sm:text-sm md:text-base text-neutral-800 group-hover:text-[#0d3d2d] transition-colors duration-300\">Brac University (Tarc)</h3>\r\n<div class=\"absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-500 rounded-b-2xl\"> </div>\r\n</div>\r\n<div class=\"group relative bg-white  rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 cursor-pointer overflow-hidden border border-emerald-100/50 hover:border-emerald-400 animate-scale-in\">\r\n<div class=\"absolute inset-0 bg-gradient-to-br from-[#0d3d2d]/5 to-emerald-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500 rounded-2xl\"> </div>\r\n<div class=\"absolute top-0 right-0 w-16 h-16 bg-gradient-to-br from-emerald-400/20 to-transparent rounded-bl-full opacity-0 group-hover:opacity-100 transition-opacity duration-500\"> </div>\r\n<div class=\"relative aspect-square bg-gradient-to-br from-neutral-100 to-neutral-200 rounded-t-xl mb-3 sm:mb-4 overflow-hidden ring-1 ring-neutral-200 group-hover:ring-emerald-400 transition-all duration-500\"><img class=\"w-full h-full object-cover group-hover:scale-110 transition-transform duration-700\" src=\"https://www.dpremiumhomes.com/assets/Manarat-BBRfHwvp.png\" alt=\"Manarat International University\">\r\n<div class=\"absolute inset-0 bg-gradient-to-t from-[#0d3d2d]/30 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500\"> </div>\r\n<div class=\"absolute top-2 right-2 w-8 h-8 bg-white rounded-full shadow-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transform scale-50 group-hover:scale-100 transition-all duration-500\"> </div>\r\n</div>\r\n<h3 class=\"relative p-4 font-semibold text-center text-xs sm:text-sm md:text-base text-neutral-800 group-hover:text-[#0d3d2d] transition-colors duration-300\">Manarat International University</h3>\r\n<div class=\"absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-500 rounded-b-2xl\"> </div>\r\n</div>\r\n<div class=\"group relative bg-white  rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 cursor-pointer overflow-hidden border border-emerald-100/50 hover:border-emerald-400 animate-scale-in\">\r\n<div class=\"absolute inset-0 bg-gradient-to-br from-[#0d3d2d]/5 to-emerald-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500 rounded-2xl\"> </div>\r\n<div class=\"absolute top-0 right-0 w-16 h-16 bg-gradient-to-br from-emerald-400/20 to-transparent rounded-bl-full opacity-0 group-hover:opacity-100 transition-opacity duration-500\"> </div>\r\n<div class=\"relative aspect-square bg-gradient-to-br from-neutral-100 to-neutral-200 rounded-t-xl mb-3 sm:mb-4 overflow-hidden ring-1 ring-neutral-200 group-hover:ring-emerald-400 transition-all duration-500\"><img class=\"w-full h-full object-cover group-hover:scale-110 transition-transform duration-700\" src=\"https://www.dpremiumhomes.com/assets/mosque-ClDhUgQu.jpg\" alt=\"Masjid\">\r\n<div class=\"absolute inset-0 bg-gradient-to-t from-[#0d3d2d]/30 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500\"> </div>\r\n<div class=\"absolute top-2 right-2 w-8 h-8 bg-white rounded-full shadow-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transform scale-50 group-hover:scale-100 transition-all duration-500\"> </div>\r\n</div>\r\n<h3 class=\"relative p-4 font-semibold text-center text-xs sm:text-sm md:text-base text-neutral-800 group-hover:text-[#0d3d2d] transition-colors duration-300\">Masjid</h3>\r\n</div>', '6943d733818a7_1766053683.png', '6943d73372ce5_1766053683.png', 'https://drive.google.com/file/d/1Shb6eNMOd6KtKCO4aMYMVhIsVJn_qf7n/view?usp=sharing', 2, 1, '2025-12-18 10:28:03', '2026-01-01 09:55:49', 1, NULL, 1, 1, 1),
(2, 'The Premium Legacy Residence', NULL, 1, 'B2+B1+G+14', 10, 4, 'D Block, Amin Mohammad Model Town, Ashulia', '69464dc0c7cb0_1766215104.png', '69464dc0ded7d_1766215104.png', '', '40 Feet', '50 Lac - 1 Crore BDT', 'The Premium Legacy Residence', 'SOLD OUT', NULL, NULL, NULL, 'https://drive.google.com/file/d/1Shb6eNMOd6KtKCO4aMYMVhIsVJn_qf7n/view?usp=sharing', '', 2, '', '69464dc110ce1_1766215105.png', '69464dc0f3103_1766215104.png', 'https://drive.google.com/file/d/1Shb6eNMOd6KtKCO4aMYMVhIsVJn_qf7n/view?usp=sharing', 2, 1, '2025-12-20 07:18:25', '2025-12-20 07:18:25', 1, 1, NULL, 1, 1),
(3, 'Ashuliya Model Town', 2, 1, 'B2+B1+G+15', 20, 6, 'STUDIO, 1, 2 BEDROOMS & PENTHOUSE', '6948e37ac4a10_1766384506.png', '6948e37ad734f_1766384506.png', '6948e37aebfa1_1766384506.png', '40 Feet', 'Under 50 Lac BDT', NULL, NULL, 3.5, '12', NULL, 'https://drive.google.com/file/d/1Shb6eNMOd6KtKCO4aMYMVhIsVJn_qf7n/view', '<p>trgfrt</p>', 1, '<p>oiet</p>', '6948e37b1526d_1766384507.png', '6948e37b06859_1766384507.png', 'https://drive.google.com/file/d/1Shb6eNMOd6KtKCO4aMYMVhIsVJn_qf7n/view?usp=sharing', 1, 1, '2025-12-22 06:21:47', '2026-01-01 09:45:47', 1, NULL, 1, 1, 1);

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
  `client_declaration` text DEFAULT NULL,
  `header_code` text DEFAULT NULL,
  `body_code` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `website_title`, `website_url`, `footer_text`, `logo`, `phone`, `alt_phone`, `email`, `alt_email`, `address`, `copyright`, `facebook_link`, `messenger_link`, `instagram_link`, `tiktok_link`, `x_link`, `youtube_link`, `client_declaration`, `header_code`, `body_code`, `created_at`, `updated_at`) VALUES
(1, 'The Premium Home Limited', NULL, 'fxdgrdgth', '6948f14a9fb8c_1766388042.png', '+8801958253300', '+8801958253300', 'info@thepremiumhomes.com', NULL, 'Dhaka', '© 2025 The Premium Homes Ltd. All rights reserved.', 'uidysfb re', 'kuysret87', 'uidsfyter', 'dsilfu67rt', 'iureyt87e', 'ids7rt89', 'I hereby declare that I have applied for the purchase of the above-mentioned flat under the terms and conditions set by the developer. I confirm that all the information provided in this document is true, correct, and complete to the best of my knowledge and belief. I further agree that I have carefully read, understood, and accepted all the terms and conditions, rules, and regulations related to the purchase of the said flat.', '', '', '2025-12-15 12:14:07', '2026-01-01 04:07:54');

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
(4, 14, 2, 'Task 2', '<p>test description</p>', '2025-12-02', '2026-01-08 00:00:00', 0, 'pending', 0, 'high', '2025-12-08 11:35:32', '2025-12-28 03:48:34'),
(5, 13, 1, 'Task 2', '<p>do first</p>', '2025-12-11', '2025-12-11 00:00:00', 0, '', 0, '', '2025-12-10 09:15:05', NULL),
(6, 13, 1, 'Task 2', '<p>do first</p>', '2025-12-11', '2025-12-11 00:00:00', 0, '', 0, '', '2025-12-10 09:19:52', NULL),
(7, 32, 12, 'Task-2', '<p>udisf kjdfsah8i hjsdfoiu kjsdfo9werhjdfsncvj fjkgr8e  </p>', '2025-12-27', '2026-06-24 00:00:00', 1, 'in_progress', 20, 'low', '2025-12-27 07:00:12', '2025-12-27 10:25:06'),
(8, 32, 12, 'Test Task', '<p>test task</p>', '2025-12-28', '2026-01-16 00:00:00', 0, 'pending', 0, 'low', '2025-12-28 03:20:28', '2025-12-28 03:47:55');

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
(12, 'Farzana', 'Farzana', '01711111111', 'farz@gmail.com', 1, 'Dhaka', '12345678', 3, '$2y$10$E0Yhq.1IcOfIhUepelWIieBg7O9L6XhEKWNmRhD1v2rT0WRH5Q7T6', '693e4b6b1f340_1765690219.jpg', 'https://business.facebook.com/', 'https://www.linkedin.com/', '2025-12-30 10:22:04', '2025-12-14 05:30:19', '2025-12-30 04:22:04'),
(13, 'Nusrat', '', '01789000000', 'nusrat@gmail.com', 1, '23\r\nSEL HUQ SKYPARK\r\n2 Mirpur Rd', '12345', 2, '$2y$10$m40Fzqo/Sovfjice0lOMYOjRtCfehCzGnfTaO..0obmwMKIxsIrdi', '', '', '', '2025-12-28 16:37:17', '2025-12-14 06:34:19', '2025-12-28 10:37:17'),
(14, 'Farhana', 'Farhana', '01700000000', 'farhana@gmail.com', 1, 'Dhaka', '2345676', 2, '$2y$10$lxw8JSnTFc75E7i0QyuKEuxgedIf2l7E/y94jxlnZzG2EjloA2TYu', NULL, 'www.google.com', 'www.linkedin.com', '2025-12-27 14:19:34', '2025-12-14 08:40:51', '2025-12-27 08:19:34');

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
-- Indexes for table `company_bank_accounts`
--
ALTER TABLE `company_bank_accounts`
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
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `payslips`
--
ALTER TABLE `payslips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `fk_payslip_company_bank` (`company_bank_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `company_bank_accounts`
--
ALTER TABLE `company_bank_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `emi_schedules`
--
ALTER TABLE `emi_schedules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `flats`
--
ALTER TABLE `flats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `flat_purchase_rules`
--
ALTER TABLE `flat_purchase_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `lead_bookings`
--
ALTER TABLE `lead_bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `lead_follow_ups`
--
ALTER TABLE `lead_follow_ups`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `lead_payments`
--
ALTER TABLE `lead_payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `management_cashin`
--
ALTER TABLE `management_cashin`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- AUTO_INCREMENT for table `payslips`
--
ALTER TABLE `payslips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payslip_details`
--
ALTER TABLE `payslip_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payslip_items`
--
ALTER TABLE `payslip_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
  ADD CONSTRAINT `fk_leads_owner` FOREIGN KEY (`assigned_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `lead_bookings`
--
ALTER TABLE `lead_bookings`
  ADD CONSTRAINT `lead_bookings_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`),
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
-- Constraints for table `management_cashout`
--
ALTER TABLE `management_cashout`
  ADD CONSTRAINT `fk_cashout_category` FOREIGN KEY (`category_id`) REFERENCES `management_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_management_cashout_role` FOREIGN KEY (`role_id`) REFERENCES `management_roles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `management_cashout_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payslips`
--
ALTER TABLE `payslips`
  ADD CONSTRAINT `fk_payslip_company_bank` FOREIGN KEY (`company_bank_id`) REFERENCES `company_bank_accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payslips_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

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




-- ========================================
-- CASHOUT REQUEST SYSTEM DATABASE SCHEMA
-- ========================================

-- Table: cashout_requests
-- Main table for storing cashout/payment requests
CREATE TABLE IF NOT EXISTS `cashout_requests` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_number` VARCHAR(50) DEFAULT NULL COMMENT 'Auto-generated request number (e.g., CR-000001)',
  `requested_by` INT(11) UNSIGNED NOT NULL COMMENT 'User ID from users table',
  `purpose` VARCHAR(255) NOT NULL COMMENT 'Purpose or description of payment',
  `paid_to` VARCHAR(255) NOT NULL COMMENT 'Recipient name/entity',
  `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Total requested amount',
  `paid_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Amount already paid',
  `due_amount` DECIMAL(15,2) GENERATED ALWAYS AS (`amount` - `paid_amount`) STORED COMMENT 'Calculated due amount',
  `description` TEXT DEFAULT NULL COMMENT 'Detailed description',
  `category_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'Category from management_categories',
  `payment_method` ENUM('cash', 'bank', 'cheque', 'online') DEFAULT 'cash',
  `transaction_ref` VARCHAR(255) DEFAULT NULL COMMENT 'Transaction reference number',
  `bank_name` VARCHAR(255) DEFAULT NULL,
  `bank_account_no` VARCHAR(100) DEFAULT NULL,
  `cheque_no` VARCHAR(100) DEFAULT NULL,
  `online_type` VARCHAR(50) DEFAULT NULL COMMENT 'bKash, Nagad, Rocket, etc.',
  `attachment_path` VARCHAR(255) DEFAULT NULL COMMENT 'Path to uploaded file',
  `attachment_name` VARCHAR(255) DEFAULT NULL COMMENT 'Original filename',
  `attachment_size` INT(11) DEFAULT NULL COMMENT 'File size in bytes',
  `status` ENUM(
    'pending',
    'approved_by_hr',
    'approved_by_admin', 
    'approved_by_super_admin',
    'partially_paid',
    'fully_paid',
    'rejected'
  ) NOT NULL DEFAULT 'pending',
  `approved_by_hr` INT(11) UNSIGNED DEFAULT NULL COMMENT 'HR user who approved',
  `approved_by_hr_at` DATETIME DEFAULT NULL,
  `approved_by_admin` INT(11) UNSIGNED DEFAULT NULL COMMENT 'Admin user who approved',
  `approved_by_admin_at` DATETIME DEFAULT NULL,
  `approved_by_super_admin` INT(11) UNSIGNED DEFAULT NULL COMMENT 'Super Admin who approved',
  `approved_by_super_admin_at` DATETIME DEFAULT NULL,
  `rejected_by` INT(11) UNSIGNED DEFAULT NULL COMMENT 'User who rejected',
  `rejected_at` DATETIME DEFAULT NULL,
  `rejection_reason` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft delete timestamp',
  PRIMARY KEY (`id`),
  UNIQUE KEY `request_number` (`request_number`),
  KEY `requested_by` (`requested_by`),
  KEY `category_id` (`category_id`),
  KEY `status` (`status`),
  KEY `deleted_at` (`deleted_at`),
  CONSTRAINT `fk_cashout_requests_user` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cashout_requests_category` FOREIGN KEY (`category_id`) REFERENCES `management_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cashout/Payment requests with approval workflow';

-- Table: cashout_request_payments
-- Track partial payments made against cashout requests
CREATE TABLE IF NOT EXISTS `cashout_request_payments` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cashout_request_id` INT(11) UNSIGNED NOT NULL,
  `payment_date` DATE NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('cash', 'bank', 'cheque', 'online') DEFAULT 'cash',
  `transaction_ref` VARCHAR(255) DEFAULT NULL,
  `bank_name` VARCHAR(255) DEFAULT NULL,
  `bank_account_no` VARCHAR(100) DEFAULT NULL,
  `cheque_no` VARCHAR(100) DEFAULT NULL,
  `online_type` VARCHAR(50) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `paid_by` INT(11) UNSIGNED DEFAULT NULL COMMENT 'User who processed payment',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cashout_request_id` (`cashout_request_id`),
  KEY `payment_date` (`payment_date`),
  KEY `paid_by` (`paid_by`),
  CONSTRAINT `fk_cashout_payments_request` FOREIGN KEY (`cashout_request_id`) REFERENCES `cashout_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cashout_payments_user` FOREIGN KEY (`paid_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Partial payment tracking for cashout requests';

-- Table: cashout_request_history
-- Audit trail for status changes and approvals
CREATE TABLE IF NOT EXISTS `cashout_request_history` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cashout_request_id` INT(11) UNSIGNED NOT NULL,
  `action` VARCHAR(100) NOT NULL COMMENT 'created, approved_hr, approved_admin, rejected, payment_added, etc.',
  `old_status` VARCHAR(50) DEFAULT NULL,
  `new_status` VARCHAR(50) DEFAULT NULL,
  `amount` DECIMAL(15,2) DEFAULT NULL COMMENT 'For payment actions',
  `notes` TEXT DEFAULT NULL,
  `performed_by` INT(11) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cashout_request_id` (`cashout_request_id`),
  KEY `performed_by` (`performed_by`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_cashout_history_request` FOREIGN KEY (`cashout_request_id`) REFERENCES `cashout_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cashout_history_user` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit trail for cashout requests';

-- ========================================
-- INDEXES FOR PERFORMANCE
-- ========================================

-- Additional indexes for common queries
CREATE INDEX idx_status_created ON cashout_requests(status, created_at DESC);
CREATE INDEX idx_user_status ON cashout_requests(requested_by, status);
CREATE INDEX idx_due_amount ON cashout_requests(due_amount);

-- ========================================
-- TRIGGER: Auto-generate request number
-- ========================================

DELIMITER $$

CREATE TRIGGER `before_insert_cashout_request`
BEFORE INSERT ON `cashout_requests`
FOR EACH ROW
BEGIN
  IF NEW.request_number IS NULL THEN
    SET NEW.request_number = CONCAT('CR-', LPAD((SELECT IFNULL(MAX(id), 0) + 1 FROM cashout_requests), 6, '0'));
  END IF;
END$$

DELIMITER ;

-- ========================================
-- SAMPLE DATA (Optional - for testing)
-- ========================================

-- Insert sample categories if they don't exist
INSERT IGNORE INTO management_categories (id, category_name, description, status, created_by) VALUES
(1, 'Office Expenses', 'General office and administrative expenses', 1, 1),
(2, 'Salary & Wages', 'Employee salaries and wages', 1, 1),
(3, 'Utilities', 'Electricity, water, internet bills', 1, 1),
(4, 'Marketing', 'Marketing and advertising expenses', 1, 1),
(5, 'Travel', 'Travel and transportation expenses', 1, 1),
(6, 'Supplies', 'Office supplies and materials', 1, 1);

-- ========================================
-- VIEWS FOR REPORTING
-- ========================================

-- View: Pending requests with user details
CREATE OR REPLACE VIEW `v_pending_cashout_requests` AS
SELECT 
    cr.id,
    cr.request_number,
    cr.requested_by,
    u.full_name AS requested_by_name,
    u.email AS requested_by_email,
    cr.purpose,
    cr.paid_to,
    cr.amount,
    cr.paid_amount,
    cr.due_amount,
    cr.status,
    mc.category_name,
    cr.created_at,
    DATEDIFF(NOW(), cr.created_at) AS days_pending
FROM 
    cashout_requests cr
    INNER JOIN users u ON cr.requested_by = u.id
    LEFT JOIN management_categories mc ON cr.category_id = mc.id
WHERE 
    cr.deleted_at IS NULL
    AND cr.status IN ('pending', 'approved_by_hr', 'approved_by_admin', 'partially_paid')
ORDER BY 
    cr.created_at DESC;

-- View: Fully paid requests
CREATE OR REPLACE VIEW `v_completed_cashout_requests` AS
SELECT 
    cr.id,
    cr.request_number,
    cr.requested_by,
    u.full_name AS requested_by_name,
    cr.purpose,
    cr.paid_to,
    cr.amount,
    cr.paid_amount,
    cr.status,
    mc.category_name,
    cr.created_at,
    cr.updated_at
FROM 
    cashout_requests cr
    INNER JOIN users u ON cr.requested_by = u.id
    LEFT JOIN management_categories mc ON cr.category_id = mc.id
WHERE 
    cr.deleted_at IS NULL
    AND cr.status = 'fully_paid'
ORDER BY 
    cr.updated_at DESC;

-- ========================================
-- STORED PROCEDURES (Optional)
-- ========================================

-- Procedure: Get cashout request statistics
DELIMITER $$

CREATE PROCEDURE `sp_get_cashout_statistics`()
BEGIN
    SELECT 
        COUNT(*) AS total_requests,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_requests,
        SUM(CASE WHEN status = 'fully_paid' THEN 1 ELSE 0 END) AS completed_requests,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_requests,
        SUM(amount) AS total_requested_amount,
        SUM(paid_amount) AS total_paid_amount,
        SUM(due_amount) AS total_due_amount
    FROM 
        cashout_requests
    WHERE 
        deleted_at IS NULL;
END$$

DELIMITER ;

-- ========================================
-- PERMISSIONS AND GRANTS (Adjust as needed)
-- ========================================

-- Grant permissions for the application user
-- GRANT SELECT, INSERT, UPDATE, DELETE ON cashout_requests TO 'your_app_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON cashout_request_payments TO 'your_app_user'@'localhost';
-- GRANT SELECT, INSERT ON cashout_request_history TO 'your_app_user'@'localhost';

-- ========================================
-- NOTES FOR IMPLEMENTATION
-- ========================================

/*
1. File Upload Configuration:
   - Max file size: 5MB recommended
   - Allowed extensions: .docx, .pdf, .jpg, .png
   - Storage path: /uploads/cashout_requests/
   
2. Approval Workflow:
   - Pending → Approved by HR → Approved by Admin → Approved by Super Admin
   - Any approver can reject
   - Once approved by all required levels, payment can be processed
   
3. Payment Processing:
   - Partial payments update paid_amount
   - When paid_amount = amount, status becomes fully_paid
   - All payments recorded in cashout_request_payments table
   
4. Integration with approval_requests table:
   - Create entry in approval_requests when cashout_request is created
   - Link via source_table='cashout_requests' and source_id=cashout_request.id
   
5. Status Transitions:
   pending → approved_by_hr → approved_by_admin → approved_by_super_admin → partially_paid → fully_paid
   Any status can → rejected
*/