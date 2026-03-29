-- MySQL dump 10.13  Distrib 8.0.45, for Linux (x86_64)
--
-- Host: localhost    Database: final_erp_db
-- ------------------------------------------------------
-- Server version	8.0.45-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `approval_requests`
--

DROP TABLE IF EXISTS `approval_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approval_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `department` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `request_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `source_table` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `source_id` int unsigned NOT NULL,
  `request_amount` decimal(12,2) DEFAULT NULL,
  `approved_amount` decimal(12,2) DEFAULT NULL,
  `requested_days` int DEFAULT NULL,
  `approved_days` int DEFAULT NULL,
  `approved_by` int unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','approved','rejected','partial') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `requested_by` int unsigned NOT NULL,
  `approve_role` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `approval_note` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  CONSTRAINT `approval_requests_chk_1` CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_requests`
--

LOCK TABLES `approval_requests` WRITE;
/*!40000 ALTER TABLE `approval_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `approval_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blogs`
--

DROP TABLE IF EXISTS `blogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blogs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` text COLLATE utf8mb4_general_ci NOT NULL,
  `slug` text COLLATE utf8mb4_general_ci,
  `excerpt` longtext COLLATE utf8mb4_general_ci,
  `content` longtext COLLATE utf8mb4_general_ci,
  `image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `author` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `published_date` date DEFAULT NULL,
  `comments` int DEFAULT '0',
  `read_time` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` tinyint DEFAULT '1' COMMENT '1=Published, 0=Draft',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blogs`
--

LOCK TABLES `blogs` WRITE;
/*!40000 ALTER TABLE `blogs` DISABLE KEYS */;
/*!40000 ALTER TABLE `blogs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('laravel-cache-5c785c036466adea360111aa28563bfd556b5fba','i:1;',1771691443),('laravel-cache-5c785c036466adea360111aa28563bfd556b5fba:timer','i:1771691443;',1771691443),('laravel-cache-77de68daecd823babbb58edb1c8e14d7106e83bb','i:1;',1771695229),('laravel-cache-77de68daecd823babbb58edb1c8e14d7106e83bb:timer','i:1771695228;',1771695229),('tphl-erp-cache-5c785c036466adea360111aa28563bfd556b5fba','i:1;',1771733365),('tphl-erp-cache-5c785c036466adea360111aa28563bfd556b5fba:timer','i:1771733365;',1771733365),('tphl-erp-cache-77de68daecd823babbb58edb1c8e14d7106e83bb','i:1;',1771731675),('tphl-erp-cache-77de68daecd823babbb58edb1c8e14d7106e83bb:timer','i:1771731675;',1771731675);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `camera_logs`
--

DROP TABLE IF EXISTS `camera_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `camera_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `camera_id` int DEFAULT NULL,
  `event_type` varchar(50) DEFAULT NULL,
  `message` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `camera_logs`
--

LOCK TABLES `camera_logs` WRITE;
/*!40000 ALTER TABLE `camera_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `camera_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cameras`
--

DROP TABLE IF EXISTS `cameras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cameras` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int DEFAULT NULL,
  `camera_name` varchar(255) NOT NULL,
  `camera_slug` varchar(255) NOT NULL,
  `rtsp_url` text NOT NULL,
  `hls_path` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `last_heartbeat` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cameras`
--

LOCK TABLES `cameras` WRITE;
/*!40000 ALTER TABLE `cameras` DISABLE KEYS */;
INSERT INTO `cameras` VALUES (1,1,'Cam-1','cam_1','cam01,rtsp://admin:Hik2025S@103.139.144.106:8555/stream-path','hls/the_premium_nobel_court/cam_1.m3u8','active',NULL,'2026-02-21 17:39:56');
/*!40000 ALTER TABLE `cameras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cashout_payments`
--

DROP TABLE IF EXISTS `cashout_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cashout_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cashout_request_id` int DEFAULT NULL,
  `office` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `project_id` int DEFAULT NULL,
  `purpose_paid_to` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'cash',
  `transaction_ref` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_date` date NOT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_account_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cheque_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `online_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cashout_request_id` (`cashout_request_id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cashout_payments`
--

LOCK TABLES `cashout_payments` WRITE;
/*!40000 ALTER TABLE `cashout_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `cashout_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cashout_requests`
--

DROP TABLE IF EXISTS `cashout_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cashout_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int DEFAULT NULL,
  `office` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `requested_by` int NOT NULL COMMENT 'User who requested cashout',
  `purpose_paid_to` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Purpose or payee name',
  `amount` decimal(15,2) NOT NULL COMMENT 'Total requested amount',
  `approved_amount` decimal(15,2) DEFAULT NULL,
  `due_amount` decimal(15,2) NOT NULL COMMENT 'Remaining unpaid amount',
  `description` text COLLATE utf8mb4_general_ci,
  `payment_method` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'cash',
  `transaction_ref` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `attachment_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected','partially_paid','fully_paid') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_by` int DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_general_ci,
  `approval_note` text COLLATE utf8mb4_general_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_requested_by` (`requested_by`),
  KEY `fk_approved_by` (`approved_by`),
  KEY `fk_rejected_by` (`rejected_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Cashout requests with single-step approval';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cashout_requests`
--

LOCK TABLES `cashout_requests` WRITE;
/*!40000 ALTER TABLE `cashout_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `cashout_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_role_id` int NOT NULL,
  `message` text NOT NULL,
  `reply` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_messages`
--

LOCK TABLES `chat_messages` WRITE;
/*!40000 ALTER TABLE `chat_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `communities`
--

DROP TABLE IF EXISTS `communities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `communities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `communities`
--

LOCK TABLES `communities` WRITE;
/*!40000 ALTER TABLE `communities` DISABLE KEYS */;
INSERT INTO `communities` VALUES (1,'The Premium Royal City',1,3,'2026-02-19 17:00:30','2026-02-19 17:00:30');
/*!40000 ALTER TABLE `communities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_bank_accounts`
--

DROP TABLE IF EXISTS `company_bank_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_bank_accounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bank_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `account_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `account_number` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `is_default` tinyint(1) DEFAULT '0',
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_bank_accounts`
--

LOCK TABLES `company_bank_accounts` WRITE;
/*!40000 ALTER TABLE `company_bank_accounts` DISABLE KEYS */;
INSERT INTO `company_bank_accounts` VALUES (1,'City Bank,Shaymoli','TPHL','11221334',0,'active',1,'2026-02-23 14:13:36');
/*!40000 ALTER TABLE `company_bank_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contacts`
--

DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_status` enum('pending','contacted','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'Track follow-up status',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contacts`
--

LOCK TABLES `contacts` WRITE;
/*!40000 ALTER TABLE `contacts` DISABLE KEYS */;
INSERT INTO `contacts` VALUES (1,'Jubair Khan','khan@example.com','+8801712345678','Property Inquiry','I am interested in your Gulshan apartment. Please contact me.','pending','2026-02-21 22:08:25','2026-02-22 04:15:00');
/*!40000 ALTER TABLE `contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` (`id`, `name`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Business Development', 1, 2, '2026-02-19 16:17:29', '2026-02-19 16:17:29'),
(2, 'Human Resources', 1, 1, '2026-02-23 05:42:59', '2026-02-23 05:42:59'),
(3, 'Finance & Accounts', 1, 4, '2026-02-23 06:06:59', '2026-02-23 06:06:59'),
(4, 'CED', 1, 1, '2026-03-29 04:58:55', '2026-03-29 04:58:55'),
(5, 'Sales & Marketing', 1, 1, '2026-03-29 05:00:28', '2026-03-29 05:00:28'),
(6, 'Admin & Operation', 1, 1, '2026-03-29 05:00:53', '2026-03-29 05:00:53');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `designations`
--

DROP TABLE IF EXISTS `designations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `designations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `department_id` int unsigned NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `designations`
--

LOCK TABLES `designations` WRITE;
/*!40000 ALTER TABLE `designations` DISABLE KEYS */;
INSERT INTO `designations` (`id`, `name`, `department_id`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'BD Executive', 1, NULL, '2026-03-29 05:21:14', '2026-03-29 05:21:14'),
(2, 'Sales Manager', 5, NULL, '2026-03-29 05:21:14', '2026-03-29 05:26:07'),
(3, 'HR Executive', 2, NULL, '2026-03-29 05:21:14', '2026-03-29 05:21:14'),
(4, 'HR Manager', 2, NULL, '2026-03-29 05:21:14', '2026-03-29 05:21:14'),
(5, 'Accountant', 3, NULL, '2026-03-29 05:21:14', '2026-03-29 05:21:14'),
(6, 'Finance Manager', 3, NULL, '2026-03-29 05:21:14', '2026-03-29 05:21:14'),
(7, 'Engineer', 4, NULL, '2026-03-29 05:21:14', '2026-03-29 05:21:14'),
(8, 'Project Manager', 4, NULL, '2026-03-29 05:21:14', '2026-03-29 05:21:14'),
(9, 'Sales Executive', 5, NULL, '2026-03-29 05:21:14', '2026-03-29 05:21:14'),
(10, 'Marketing Manager', 5, NULL, '2026-03-29 05:21:14', '2026-03-29 05:21:14'),
(11, 'Admin Officer', 6, NULL, '2026-03-29 05:21:14', '2026-03-29 05:21:14'),
(12, 'Operation Manager', 6, NULL, '2026-03-29 05:21:14', '2026-03-29 05:21:14');
/*!40000 ALTER TABLE `designations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `emi_schedules`
--

DROP TABLE IF EXISTS `emi_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emi_schedules` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `lead_booking_id` int unsigned NOT NULL,
  `emi_no` int DEFAULT NULL,
  `emi_amount` decimal(12,2) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('pending','paid','missed') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `lead_payment_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `due_emi_amount` decimal(12,2) DEFAULT NULL,
  `service_fee` float DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_booking_id` (`lead_booking_id`),
  KEY `lead_payment_id` (`lead_payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `emi_schedules`
--

LOCK TABLES `emi_schedules` WRITE;
/*!40000 ALTER TABLE `emi_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `emi_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `emp_attendances`
--

DROP TABLE IF EXISTS `emp_attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emp_attendances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int unsigned NOT NULL,
  `attendance_date` date NOT NULL,
  `punch_in_time` timestamp NULL DEFAULT NULL,
  `punch_out_time` timestamp NULL DEFAULT NULL,
  `today_status` enum('Present','Late','Absent','On Leave','Half-Day') COLLATE utf8mb4_general_ci DEFAULT 'Absent',
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_date` (`employee_id`,`attendance_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `emp_attendances`
--

LOCK TABLES `emp_attendances` WRITE;
/*!40000 ALTER TABLE `emp_attendances` DISABLE KEYS */;
/*!40000 ALTER TABLE `emp_attendances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `emp_weekly_day_off`
--

DROP TABLE IF EXISTS `emp_weekly_day_off`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emp_weekly_day_off` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `day_off_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `emp_weekly_day_off`
--

LOCK TABLES `emp_weekly_day_off` WRITE;
/*!40000 ALTER TABLE `emp_weekly_day_off` DISABLE KEYS */;
/*!40000 ALTER TABLE `emp_weekly_day_off` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_leave_balances`
--

DROP TABLE IF EXISTS `employee_leave_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_leave_balances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int unsigned NOT NULL,
  `leave_type_id` int unsigned NOT NULL,
  `remaining_days` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_leave_balances`
--

LOCK TABLES `employee_leave_balances` WRITE;
/*!40000 ALTER TABLE `employee_leave_balances` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_leave_balances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_leaves`
--

DROP TABLE IF EXISTS `employee_leaves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_leaves` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int unsigned NOT NULL,
  `leave_type_id` int unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `requested_days` int NOT NULL,
  `approved_days` int DEFAULT '0',
  `approval_status` enum('pending','approved','partial','rejected') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `reason` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_leaves`
--

LOCK TABLES `employee_leaves` WRITE;
/*!40000 ALTER TABLE `employee_leaves` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_leaves` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_tasks`
--

DROP TABLE IF EXISTS `employee_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_tasks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int unsigned NOT NULL,
  `work_date` date NOT NULL,
  `task_description` text COLLATE utf8mb4_general_ci NOT NULL,
  `hours_spent` decimal(4,2) NOT NULL DEFAULT '0.00',
  `status` enum('Pending','In Progress','Done') COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `remarks` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_tasks`
--

LOCK TABLES `employee_tasks` WRITE;
/*!40000 ALTER TABLE `employee_tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `full_name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `employee_id` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `father_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `emergency_contact` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `qualification` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `experience` text COLLATE utf8mb4_general_ci,
  `address` text COLLATE utf8mb4_general_ci NOT NULL,
  `pass_num` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nid` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department_id` int unsigned NOT NULL,
  `designation_id` int unsigned DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `workplace` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `basic_salary` float DEFAULT NULL,
  `account_holder_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `make_user` int DEFAULT '0',
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `branch_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `social_media1` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `social_media2` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `social_media3` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employee_type` enum('permanent','contract','director') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employee_status` enum('active','inactive','resigned','terminated','on_leave') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `responsibilities` text COLLATE utf8mb4_general_ci,
  `created_by` int DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `department_id` (`department_id`),
  KEY `designation_id` (`designation_id`),
  KEY `fk_employee_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,NULL,'Fahim','EMP-00001','fahim','fahim@gmail.com','Forhad','01734555783','01734555783','BBA','','Dhaka','','23784',1,1,'1998-02-04','Gulshan','2026-01-01',30000,'Fahim','7523423478',1,'$2y$10$JphYkjE9NT3MdD93lmgv7uZ2uu4nWUubfKZHjdFvqN6rq6KTk8UIC','City Bank','Shaymoli','','','','','permanent','active',NULL,3,NULL,'2026-03-29 03:42:23','2026-03-29 03:42:23');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `flat_purchase_rules`
--

DROP TABLE IF EXISTS `flat_purchase_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flat_purchase_rules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `rules_description` text COLLATE utf8mb4_general_ci,
  `declearation` text COLLATE utf8mb4_general_ci,
  `status` int NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `flat_purchase_rules`
--

LOCK TABLES `flat_purchase_rules` WRITE;
/*!40000 ALTER TABLE `flat_purchase_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `flat_purchase_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `flats`
--

DROP TABLE IF EXISTS `flats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flats` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `sqft` int DEFAULT NULL,
  `bhk` int DEFAULT NULL,
  `land_share_value` float DEFAULT NULL,
  `bedroom` int DEFAULT NULL,
  `bathroom` int DEFAULT NULL,
  `balcony` int DEFAULT NULL,
  `drawing_dining_kitchen` tinyint(1) DEFAULT NULL,
  `common_dining` tinyint(1) DEFAULT NULL,
  `floor_no` int DEFAULT NULL,
  `flat_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `facing_site` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `price_value` decimal(12,2) DEFAULT NULL,
  `facilities` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `status` enum('available','booked','sold') COLLATE utf8mb4_general_ci DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_booked` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `flats`
--

LOCK TABLES `flats` WRITE;
/*!40000 ALTER TABLE `flats` DISABLE KEYS */;
INSERT INTO `flats` VALUES (1,1,1200,3,1000000,3,2,2,1,1,1,'Unit A1','north',7000000.00,'Gym',3,'available','2026-02-19 17:17:08',NULL,'2026-02-19 17:17:08',0),(2,1,1200,2,1200000,2,1,1,1,1,1,'Unit B1','South',5000000.00,'',2,'available','2026-02-21 13:20:06',NULL,'2026-02-21 13:20:06',0);
/*!40000 ALTER TABLE `flats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `head_notifications`
--

DROP TABLE IF EXISTS `head_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `head_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `type` enum('lead','booking','task','activity') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_read` (`user_id`,`is_read`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `head_notifications`
--

LOCK TABLES `head_notifications` WRITE;
/*!40000 ALTER TABLE `head_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `head_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `holidays`
--

DROP TABLE IF EXISTS `holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `holidays` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holidays`
--

LOCK TABLES `holidays` WRITE;
/*!40000 ALTER TABLE `holidays` DISABLE KEYS */;
/*!40000 ALTER TABLE `holidays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_bookings`
--

DROP TABLE IF EXISTS `lead_bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_bookings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` int unsigned NOT NULL,
  `project_id` int NOT NULL,
  `flat_id` int NOT NULL,
  `agreement_value` decimal(12,2) NOT NULL,
  `discount_amount` float DEFAULT '0',
  `booking_date` date NOT NULL,
  `booking_status` enum('booked','cancelled','completed') COLLATE utf8mb4_general_ci DEFAULT 'booked',
  `created_by` int NOT NULL,
  `sales_by_id` int DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approval_status` enum('not_required','pending','approved','rejected','partial') COLLATE utf8mb4_general_ci DEFAULT 'not_required',
  `approved_amount` decimal(12,2) DEFAULT NULL,
  `service_fee_balance` float DEFAULT NULL,
  `landshare_status` enum('active','completed','cancelled') COLLATE utf8mb4_general_ci DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `lead_id` (`lead_id`),
  KEY `project_id` (`project_id`),
  KEY `created_by` (`created_by`),
  KEY `flat_id` (`flat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_bookings`
--

LOCK TABLES `lead_bookings` WRITE;
/*!40000 ALTER TABLE `lead_bookings` DISABLE KEYS */;
/*!40000 ALTER TABLE `lead_bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_follow_ups`
--

DROP TABLE IF EXISTS `lead_follow_ups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_follow_ups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` int unsigned NOT NULL,
  `project_id` int DEFAULT NULL,
  `flat_id` int DEFAULT NULL,
  `follow_up_date` date NOT NULL,
  `follow_up_time` time DEFAULT NULL,
  `follow_up_type` enum('call','visit','meeting','whatsapp','email') COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('pending','completed','missed','canceled') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `discussion_note` text COLLATE utf8mb4_general_ci,
  `next_follow_up_date` date DEFAULT NULL,
  `assigned_with` int DEFAULT NULL,
  `next_action` enum('site visit','booking','call_back','take_time') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int unsigned NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `lead_id` (`lead_id`),
  KEY `project_id` (`project_id`),
  KEY `flat_id` (`flat_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_follow_ups`
--

LOCK TABLES `lead_follow_ups` WRITE;
/*!40000 ALTER TABLE `lead_follow_ups` DISABLE KEYS */;
INSERT INTO `lead_follow_ups` VALUES (1,8,1,1,'2026-02-22','11:36:00','call','pending','Test','2026-02-23',3,NULL,3,NULL,'2026-02-23 05:36:49','2026-02-23 05:38:52');
/*!40000 ALTER TABLE `lead_follow_ups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_payments`
--

DROP TABLE IF EXISTS `lead_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_payments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` int unsigned NOT NULL,
  `payment_type` enum('booking_money','registration','emi','land_share') COLLATE utf8mb4_general_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `land_share_amount` decimal(12,2) DEFAULT '0.00',
  `payment_method` enum('cash','bank','online','cheque') COLLATE utf8mb4_general_ci DEFAULT 'cash',
  `payment_status` enum('paid','partially_paid','pending','failed') COLLATE utf8mb4_general_ci DEFAULT 'paid',
  `payment_date` date NOT NULL,
  `emi_no` int DEFAULT NULL,
  `total_emi_no` int DEFAULT NULL,
  `transaction_ref` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_account_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cheque_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `service_fee` float DEFAULT NULL,
  `online_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_payment_booking` (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_payments`
--

LOCK TABLES `lead_payments` WRITE;
/*!40000 ALTER TABLE `lead_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `lead_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_sources`
--

DROP TABLE IF EXISTS `lead_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_sources` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(191) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_sources`
--

LOCK TABLES `lead_sources` WRITE;
/*!40000 ALTER TABLE `lead_sources` DISABLE KEYS */;
/*!40000 ALTER TABLE `lead_sources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leads`
--

DROP TABLE IF EXISTS `leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leads` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `campaign_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `platform` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `which_apartment_size_are_you_interested_in` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `which_floor_are_you_for_shop` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `when_are_you_planning_to_buy` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `would_you_like_to_schedule_a_site_visit` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `when_is_the_best_time_to_contact_you` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `full_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `job_title` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `company_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `call_status` enum('not_called','called','received','not_received') COLLATE utf8mb4_general_ci DEFAULT 'not_called',
  `call_note` text COLLATE utf8mb4_general_ci,
  `city` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lead_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lead_type` enum('Hot','Warm','Cold') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `company` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `value_amount` decimal(15,2) DEFAULT NULL,
  `currency` enum('USD','EURO','RUBLE','POUND','BDT') COLLATE utf8mb4_general_ci DEFAULT 'BDT',
  `phone` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alt_phone_number` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `source_type_id` int DEFAULT NULL,
  `assigned_id` int DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `father_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mother_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `nid_or_passport` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `present_add_bn` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `permanent_add_bn` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `identity_type` enum('NID','Passport') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `permanent_address` varchar(250) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pref_location_id` int DEFAULT NULL,
  `designation` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `project_id` int DEFAULT NULL,
  `pref_flat_size` varchar(250) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `communication_type` enum('WA Messaging','WA Conversion') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tags` text COLLATE utf8mb4_general_ci,
  `description` text COLLATE utf8mb4_general_ci,
  `visibility` enum('public','private','selected') COLLATE utf8mb4_general_ci DEFAULT 'public',
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `last_lead_action` enum('new','booked','follow up','visited','cancel') COLLATE utf8mb4_general_ci DEFAULT 'new',
  `deleted_at` datetime DEFAULT NULL,
  `lead_quality` enum('Not Interested','Prospect','Pending') COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `lead_image` varchar(250) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lead_name_bn` varchar(250) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `father_name_bn` varchar(250) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mother_name_bn` varchar(250) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `profession_bn` varchar(250) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nationality` varchar(250) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `religion` enum('Islam','Hinduism','Buddhism','Christianity','Other') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nominee_name` varchar(250) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nomi_father_name` varchar(250) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nomi_mother_name` varchar(250) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nomi_profession` varchar(250) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `relation_with_lead` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nomi_dob` date DEFAULT NULL,
  `nomi_nid_passport` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nomi_phone` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nomi_email` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nomi_present_add` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nomi_permanent_address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nomi_image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `residency_type` enum('local','expatriate') COLLATE utf8mb4_general_ci DEFAULT 'local',
  `country` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `preferred_contact_time` enum('morning','afternoon','evening','night') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `has_pending_followup` tinyint(1) DEFAULT '0' COMMENT '1 = follow-up pending, 0 = no pending follow-up',
  PRIMARY KEY (`id`),
  KEY `fk_leads_owner` (`assigned_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leads`
--

LOCK TABLES `leads` WRITE;
/*!40000 ALTER TABLE `leads` DISABLE KEYS */;
INSERT INTO `leads` VALUES (1,'P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025','fb',NULL,'1',NULL,NULL,'evening_(5_pm_-_8_pm)','Raihan','a','a','p:+8801736936727','not_called',NULL,'Dhaka',NULL,NULL,NULL,NULL,'BDT',NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'public','','new',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-02-22 09:48:11','2026-02-22 09:48:11','local',NULL,NULL,0),(2,'P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025','fb','1550_sqft','2',NULL,NULL,'afternoon_(12_pm_-_5_pm)','Enam Pathan','owner','Jet Sports','p:+8801717221212','not_called',NULL,'Jatramura',NULL,NULL,NULL,NULL,'BDT',NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'public','inactive','new','2026-02-23 04:39:20','Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-02-22 09:48:11','2026-02-23 05:15:34','local',NULL,NULL,0),(3,'P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025','fb','1450_sqft','3',NULL,NULL,'afternoon_(12_pm_-_5_pm)','Forhad Ali','AGM','Flamingo Ltd','p:+8801730440638','not_called',NULL,'Dhaka',NULL,NULL,NULL,NULL,'BDT',NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'public','','new',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-02-22 09:48:11','2026-02-23 05:15:39','local',NULL,NULL,0),(4,'P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025','fb','1250_sqft','4',NULL,NULL,'afternoon_(12_pm_-_5_pm)','Sohel Khan','propriter','beauty sportes and net company','p:+8801840990890','not_called',NULL,'Dhaka',NULL,NULL,NULL,NULL,'BDT',NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'public','','new',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-02-22 09:48:11','2026-02-23 05:15:50','local',NULL,NULL,0),(5,'P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025','fb','1550_sqft','5',NULL,NULL,'afternoon_(12_pm_-_5_pm)','Jahirul Islam','job','xyz','p:+8801711949142','not_called',NULL,'Dhaka',NULL,NULL,NULL,NULL,'BDT',NULL,NULL,NULL,0,3,'2026-02-23 05:22:04',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'public','','new',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'2026-02-22 09:48:11','2026-02-23 05:15:53','local',NULL,NULL,0),(6,'P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025','fb','1350_sqft','6',NULL,NULL,'evening_(5_pm_-_8_pm)','Soykot Islam Shihab','Owner','Amin Mistanno Vhander','p:+8801752697272','not_called',NULL,'Uttarati',NULL,NULL,NULL,NULL,'BDT',NULL,NULL,NULL,0,3,'2026-02-23 05:22:04',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'public','','new',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'2026-02-22 09:48:11','2026-02-23 05:15:59','local',NULL,NULL,0),(7,'P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025','fb','1550_sqft','7',NULL,NULL,'afternoon_(12_pm_-_5_pm)','Mahfuzur Rahman','MD, MR Income Tax Consultancy Ltd','M R Income Tax Consultancy Ltd.','p:+8801916158171','not_called',NULL,'Dhaka',NULL,NULL,NULL,NULL,'BDT',NULL,NULL,NULL,0,3,'2026-02-23 05:22:04',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'public','','new',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'2026-02-22 09:48:11','2026-02-23 05:16:17','local',NULL,NULL,0),(8,'P. Homes ll Velinor Leads ll per day 10$ Ongoing ll Nov 03, 2025','fb',NULL,'8',NULL,NULL,'afternoon_(12_pm_-_5_pm)','Shariful Islam Sharif','Business management','72 mollertack, ashkona,dokhinkhan,dhaka','p:+8801781860471','called',NULL,'Dhaka',NULL,NULL,NULL,NULL,'BDT',NULL,NULL,NULL,0,3,'2026-02-23 05:22:04',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'public','','follow up',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,'2026-02-22 09:48:11','2026-02-23 05:36:49','local',NULL,NULL,0);
/*!40000 ALTER TABLE `leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_types`
--

DROP TABLE IF EXISTS `leave_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_general_ci NOT NULL,
  `total_days` int NOT NULL DEFAULT '0',
  `is_paid` int NOT NULL DEFAULT '1',
  `is_active` tinyint(1) DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_types`
--

LOCK TABLES `leave_types` WRITE;
/*!40000 ALTER TABLE `leave_types` DISABLE KEYS */;
INSERT INTO `leave_types` VALUES (1,'Medical Leave',20,1,1,4,'2026-02-23 07:02:09','2026-02-23 07:02:09'),(2,'Sick Leave',12,1,1,4,'2026-02-23 07:02:30','2026-02-23 07:02:30');
/*!40000 ALTER TABLE `leave_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `letter_templates`
--

DROP TABLE IF EXISTS `letter_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `letter_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` enum('appointment','joining','promotion','increment','experience','noc') NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `letter_templates`
--

LOCK TABLES `letter_templates` WRITE;
/*!40000 ALTER TABLE `letter_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `letter_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `letters`
--

DROP TABLE IF EXISTS `letters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `letters` (
  `id` int NOT NULL,
  `employee_id` int unsigned NOT NULL,
  `department_id` int unsigned NOT NULL,
  `designation_id` int unsigned DEFAULT NULL,
  `letter_type` enum('appointment','joining','promotion','increment','experience','noc') COLLATE utf8mb4_general_ci NOT NULL,
  `template_id` int DEFAULT NULL,
  `subject` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `body` longtext COLLATE utf8mb4_general_ci NOT NULL,
  `letter_date` date NOT NULL,
  `ref_number` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `current_salary` decimal(10,2) DEFAULT NULL,
  `revised_salary` decimal(10,2) DEFAULT NULL,
  `new_designation` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `signatory_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `signatory_title` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `cc_email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `send_to_email` tinyint(1) DEFAULT '0',
  `status` enum('draft','issued') COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `letters`
--

LOCK TABLES `letters` WRITE;
/*!40000 ALTER TABLE `letters` DISABLE KEYS */;
/*!40000 ALTER TABLE `letters` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `manpower_requisitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `letters`
--

LOCK TABLES `manpower_requisitions` WRITE;
UNLOCK TABLES;

--
-- Table structure for table `locations`
--

DROP TABLE IF EXISTS `locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `locations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `locations`
--

LOCK TABLES `locations` WRITE;
/*!40000 ALTER TABLE `locations` DISABLE KEYS */;
INSERT INTO `locations` VALUES (1,'Bashundhora Model Town',3,'2026-02-19 16:57:02','2026-02-19 16:57:02');
/*!40000 ALTER TABLE `locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `management_cashin`
--

DROP TABLE IF EXISTS `management_cashin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `management_cashin` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `role_name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `role_id` int unsigned DEFAULT NULL,
  `person_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `source` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','bank','cheque','online') COLLATE utf8mb4_general_ci DEFAULT 'cash',
  `payment_status` enum('paid','pending','failed') COLLATE utf8mb4_general_ci DEFAULT 'paid',
  `transaction_ref` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_account_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cheque_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `online_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_role_name` (`role_name`),
  KEY `idx_transaction_date` (`transaction_date`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `management_cashin`
--

LOCK TABLES `management_cashin` WRITE;
/*!40000 ALTER TABLE `management_cashin` DISABLE KEYS */;
/*!40000 ALTER TABLE `management_cashin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `management_cashout`
--

DROP TABLE IF EXISTS `management_cashout`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `management_cashout` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int DEFAULT NULL,
  `role_name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `role_id` int unsigned DEFAULT NULL,
  `person_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `paid_to` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Recipient of the payment',
  `description` text COLLATE utf8mb4_general_ci,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','bank','cheque','online') COLLATE utf8mb4_general_ci DEFAULT 'cash',
  `payment_status` enum('paid','pending','failed') COLLATE utf8mb4_general_ci DEFAULT 'paid',
  `transaction_ref` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_account_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cheque_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `online_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_role_name` (`role_name`),
  KEY `idx_transaction_date` (`transaction_date`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_role_id` (`role_id`),
  KEY `idx_category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `management_cashout`
--

LOCK TABLES `management_cashout` WRITE;
/*!40000 ALTER TABLE `management_cashout` DISABLE KEYS */;
/*!40000 ALTER TABLE `management_cashout` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `management_categories`
--

DROP TABLE IF EXISTS `management_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `management_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` tinyint(1) DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_name` (`category_name`),
  KEY `idx_status` (`status`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `management_categories`
--

LOCK TABLES `management_categories` WRITE;
/*!40000 ALTER TABLE `management_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `management_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `management_roles`
--

DROP TABLE IF EXISTS `management_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `management_roles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `role_name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `person_name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `management_roles`
--

LOCK TABLES `management_roles` WRITE;
/*!40000 ALTER TABLE `management_roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `management_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `task_id` int DEFAULT NULL,
  `assigned_by` int NOT NULL,
  `message` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`user_id`),
  KEY `task_id` (`task_id`),
  KEY `assigned_by` (`assigned_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `onboard_clients`
--

DROP TABLE IF EXISTS `onboard_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `onboard_clients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lead_booking_id` int unsigned DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `cameraLink1` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cameraLink2` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `moneyReceiptLink` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `purchaseDetailsLink` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `lead_id` int unsigned DEFAULT NULL,
  `project_id` int unsigned DEFAULT NULL,
  `flat_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `onboard_clients`
--

LOCK TABLES `onboard_clients` WRITE;
/*!40000 ALTER TABLE `onboard_clients` DISABLE KEYS */;
/*!40000 ALTER TABLE `onboard_clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payslip_details`
--

DROP TABLE IF EXISTS `payslip_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payslip_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payslip_id` int NOT NULL,
  `payslip_item_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `payslip_id` (`payslip_id`),
  KEY `payslip_item_id` (`payslip_item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payslip_details`
--

LOCK TABLES `payslip_details` WRITE;
/*!40000 ALTER TABLE `payslip_details` DISABLE KEYS */;
INSERT INTO `payslip_details` VALUES (1,1,1,2000.00,NULL,'2026-02-23 08:11:48','2026-02-23 08:11:48'),(2,1,2,1000.00,NULL,'2026-02-23 08:11:48','2026-02-23 08:11:48'),(3,1,3,1000.00,NULL,'2026-02-23 08:11:48','2026-02-23 08:11:48');
/*!40000 ALTER TABLE `payslip_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payslip_items`
--

DROP TABLE IF EXISTS `payslip_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payslip_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('earning','deduction') NOT NULL,
  `description` text,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payslip_items`
--

LOCK TABLES `payslip_items` WRITE;
/*!40000 ALTER TABLE `payslip_items` DISABLE KEYS */;
INSERT INTO `payslip_items` VALUES (1,'House Rent Allowance','earning',NULL,4,'2026-02-23 13:58:58'),(2,'Medical  Allowance','earning',NULL,4,'2026-02-23 14:04:36'),(3,'Unpaid Leave','deduction',NULL,4,'2026-02-23 14:05:02');
/*!40000 ALTER TABLE `payslip_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payslips`
--

DROP TABLE IF EXISTS `payslips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payslips` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int unsigned NOT NULL,
  `designation_id` int unsigned DEFAULT NULL,
  `payslip_month` date NOT NULL,
  `total_earning` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_deduction` decimal(10,2) NOT NULL DEFAULT '0.00',
  `net_salary` decimal(10,2) NOT NULL DEFAULT '0.00',
  `company_bank_id` int DEFAULT NULL,
  `status` enum('pending','approved','paid') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `payment_method` enum('bank','cash','mobile_banking') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'bank',
  `created_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `designation_id` (`designation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payslips`
--

LOCK TABLES `payslips` WRITE;
/*!40000 ALTER TABLE `payslips` DISABLE KEYS */;
/*!40000 ALTER TABLE `payslips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `status` int NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'Add Role',1,NULL,'2025-12-04 12:12:58','2025-12-04 12:12:58'),(2,'View Role',1,NULL,'2025-12-04 12:17:47','2025-12-04 12:17:47'),(3,'Add Permission',1,NULL,'2025-12-06 05:51:07','2025-12-06 05:51:07'),(4,'Show Permission',1,NULL,'2025-12-06 05:51:25','2025-12-06 05:51:25'),(5,'Add Task',1,NULL,'2025-12-10 04:26:45','2025-12-10 04:26:45'),(6,'Hr Manage',1,12,'2025-12-17 09:52:36','2025-12-17 09:52:36'),(7,'Sales & Business',1,NULL,'2026-01-04 01:08:25','2026-01-04 01:08:25'),(8,'Payroll Manage',1,NULL,'2026-01-04 01:11:05','2026-01-04 01:11:05'),(10,'Accounts Manage',1,NULL,'2026-01-04 01:11:51','2026-01-04 01:11:51'),(11,'Role & Permission',1,NULL,'2026-01-04 01:17:03','2026-01-04 01:17:03'),(12,'CED Manage',1,NULL,'2026-01-04 04:29:51','2026-01-04 04:29:51'),(13,'Task Manage',1,NULL,'2026-01-04 04:32:50','2026-01-04 04:32:50'),(14,'Others',1,NULL,'2026-01-04 05:04:47','2026-01-04 05:04:47'),(15,'Add Lead',1,12,'2026-01-31 05:23:20','2026-01-31 05:23:20'),(16,'User Manage',1,12,'2026-01-31 12:22:53','2026-01-31 12:22:53'),(17,'Add User',1,12,'2026-01-31 12:23:02','2026-01-31 12:23:02'),(19,'View Lead',1,12,'2026-02-01 12:06:45','2026-02-01 12:06:45'),(20,'My Leave Request',1,12,'2026-02-08 12:00:34','2026-02-08 12:00:34'),(21,'Camera Manage',1,2,'2026-02-21 17:24:42','2026-02-21 17:24:42');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\OnboardClient',1,'onboard-client','7e24226295485ceac5b2f5d81d02c236b5a160f988449891f39e42cdae87e72d','[\"*\"]','2026-02-08 04:28:21',NULL,'2026-02-08 04:20:34','2026-02-08 04:28:21'),(2,'App\\Models\\OnboardClient',1,'onboard-client','903f420ab84ed7fefe35bb26f11ab0f683c3bcde22b8cbd787bf03e613fa8d98','[\"*\"]','2026-02-08 04:31:41',NULL,'2026-02-08 04:28:39','2026-02-08 04:31:41'),(3,'App\\Models\\OnboardClient',1,'onboard-client','13aab24d14d21706763b5fa533c05d4a310b76acdcb2ac433578794223a04582','[\"*\"]','2026-02-08 04:39:06',NULL,'2026-02-08 04:36:40','2026-02-08 04:39:06'),(4,'App\\Models\\OnboardClient',1,'onboard-client','4561e3242fb548617bc199392d02366d885d02efbdbdfd5a5df6288142b2f49d','[\"*\"]','2026-02-09 23:31:48',NULL,'2026-02-09 23:31:22','2026-02-09 23:31:48'),(5,'App\\Models\\OnboardClient',2,'onboard-client','ce3cafbff5660c8fea29fb233264fe87cc30a35e29ab0cabc50e629dcdb8ccb9','[\"*\"]','2026-02-17 03:52:04',NULL,'2026-02-17 03:51:52','2026-02-17 03:52:04'),(6,'App\\Models\\OnboardClient',3,'onboard-client','e92286bbd37589f387f496582e3aacafb03063a89ae49f9143b5f62df1ab4d7c','[\"*\"]','2026-02-21 11:32:47',NULL,'2026-02-21 10:29:47','2026-02-21 11:32:47'),(7,'App\\Models\\OnboardClient',3,'onboard-client','b23fc29f72107b0178d8fa4d62c3e20edb0f69d69a22585ae301885a174a21b6','[\"*\"]','2026-02-21 21:40:15',NULL,'2026-02-21 21:40:04','2026-02-21 21:40:15');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `projects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` text COLLATE utf8mb4_general_ci,
  `location_id` int DEFAULT NULL,
  `community_id` int NOT NULL,
  `building_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total_share` int DEFAULT NULL,
  `unit_per_floor` int DEFAULT NULL,
  `floor_wise_unit_meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `types` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image2` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image3` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `front_road` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `price_range` enum('Under 50 Lac BDT','50 Lac - 1 Crore BDT','1 Crore - 2 Crore BDT','2 Crore - 5 Crore BDT','Above 5 Crore BDT') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hover_text` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tag` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `plot_size` float DEFAULT NULL,
  `plot_no` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `block_no` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `brochure_link` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `apartment_count` int DEFAULT NULL,
  `amenities_details` longtext COLLATE utf8mb4_general_ci,
  `amenities_image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `map_image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `map_link` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `project_map_location` varchar(250) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `passenger_lift` int DEFAULT NULL,
  `cargo_lift` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `rooftop_gardening` tinyint(1) DEFAULT '0' COMMENT '1=Yes, 0=No',
  `swimming_pool` tinyint(1) DEFAULT '0' COMMENT '1=Yes, 0=No',
  `convention` tinyint(1) DEFAULT '0' COMMENT '1=Yes, 0=No',
  `electricity_backup` tinyint(1) DEFAULT '0' COMMENT '1=Yes, 0=No',
  `car_parking` int DEFAULT NULL,
  `project_sale_status` enum('Sold Out','On Sale','Upcoming') COLLATE utf8mb4_general_ci DEFAULT 'On Sale',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `location_id` (`location_id`),
  KEY `community_id` (`community_id`),
  CONSTRAINT `projects_chk_1` CHECK (json_valid(`floor_wise_unit_meta`))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
INSERT INTO `projects` VALUES (1,'The Premium Nobel Court','the-premium-nobel-court',1,1,'STUDIO, 1, 2 BEDROOMS & PENTHOUSE',20,4,'{\"typical_floor\":[{\"unit_name\":\"Unit - A\",\"sqft\":1200},{\"unit_name\":\"Unit - B\",\"sqft\":1100},{\"unit_name\":\"Unit - C\",\"sqft\":1400},{\"unit_name\":\"Unit - D\",\"sqft\":1300}]}','Test','','','','40','Under 50 Lac BDT','The Premium Nobel Court',NULL,70,'5',NULL,'https://drive.google.com/file/d/1Shb6eNMOd6KtKCO4aMYMVhIsVJn_qf7n/view?usp=sharing','',2,'','','','https://drive.google.com/file/d/1Shb6eNMOd6KtKCO4aMYMVhIsVJn_qf7n/view?usp=sharing','https://drive.google.com/file/d/1Shb6eNMOd6KtKCO4aMYMVhIsVJn_qf7n/view?usp=sharing',1,1,3,'2026-02-19 17:05:16','2026-02-19 17:05:16',1,NULL,0,0,1,'On Sale',NULL);
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `property_reviews`
--

DROP TABLE IF EXISTS `property_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reviewer_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `reviewer_role` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `project_id` int DEFAULT NULL,
  `reviewer_image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `review_video_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rating` tinyint unsigned NOT NULL,
  `review_text` text COLLATE utf8mb4_general_ci NOT NULL,
  `review_date` date DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_by` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `property_reviews_chk_1` CHECK ((`rating` between 1 and 5))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property_reviews`
--

LOCK TABLES `property_reviews` WRITE;
/*!40000 ALTER TABLE `property_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `property_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_id` int NOT NULL,
  `permission_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `permission_id` (`permission_id`),
  KEY `role_id` (`role_id`,`permission_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (26,7,12,'2026-02-05 07:06:27','2026-02-05 07:06:27'),(27,7,13,'2026-02-05 07:06:27','2026-02-05 07:06:27'),(30,9,19,'0000-00-00 00:00:00','2026-02-19 16:13:53'),(31,6,10,'2026-02-08 12:01:16','2026-02-08 12:01:16'),(32,6,20,'2026-02-08 12:01:16','2026-02-08 12:01:16'),(33,6,13,'2026-02-08 12:01:16','2026-02-08 12:01:16'),(34,5,17,'2026-02-08 12:01:26','2026-02-08 12:01:26'),(35,5,20,'2026-02-08 12:01:26','2026-02-08 12:01:26'),(36,5,7,'2026-02-08 12:01:26','2026-02-08 12:01:26'),(37,5,13,'2026-02-08 12:01:26','2026-02-08 12:01:26'),(38,4,6,'2026-02-08 12:01:42','2026-02-08 12:01:42'),(39,4,20,'0000-00-00 00:00:00','2026-02-19 16:13:53'),(40,4,8,'0000-00-00 00:00:00','2026-02-19 16:13:53'),(41,4,13,'0000-00-00 00:00:00','2026-02-19 16:13:53'),(43,4,16,'0000-00-00 00:00:00','2026-02-19 16:13:53'),(45,1,10,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(46,1,3,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(47,1,1,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(48,1,5,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(49,1,21,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(50,1,12,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(51,1,6,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(52,1,14,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(53,1,11,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(54,1,7,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(55,1,4,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(56,1,13,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(57,1,2,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(58,3,10,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(59,3,3,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(60,3,1,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(61,3,5,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(62,3,21,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(63,3,12,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(64,3,6,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(65,3,14,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(66,3,8,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(67,3,11,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(68,3,7,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(69,3,4,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(70,3,13,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(71,3,2,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(72,8,15,'2026-02-23 07:40:38','2026-02-23 07:40:38'),(73,8,20,'2026-02-23 07:40:38','2026-02-23 07:40:38');
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Admin',NULL,'2025-12-06 04:14:57','2025-12-06 04:14:57'),(2,'Employee',NULL,'2025-12-06 08:25:53','2025-12-06 08:25:53'),(3,'super Admin',NULL,'2025-12-06 11:09:07','2025-12-06 11:09:07'),(4,'HR',NULL,'2025-12-17 06:46:21','2025-12-17 06:46:21'),(5,'Sales Manager',NULL,'2026-01-04 01:14:34','2026-01-04 01:14:34'),(6,'Accounts ',NULL,'2026-01-04 01:15:42','2026-01-04 01:15:42'),(7,'Clients Experiment',NULL,'2026-01-04 04:34:42','2026-01-04 04:34:42'),(8,'Sales Executive',NULL,'2026-01-30 04:37:41','2026-01-30 04:37:41'),(9,'Sales Team Head',NULL,'2026-02-01 12:06:29','2026-02-01 12:06:29');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_targets`
--

DROP TABLE IF EXISTS `sales_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_targets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sales_user_id` int NOT NULL,
  `month` date NOT NULL COMMENT 'Use first day of month e.g. 2026-02-01',
  `site_visit_target` int DEFAULT '0',
  `booking_target` int DEFAULT '0',
  `booking_value_target` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_sales_month` (`sales_user_id`,`month`),
  KEY `idx_sales_month` (`sales_user_id`,`month`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_targets`
--

LOCK TABLES `sales_targets` WRITE;
/*!40000 ALTER TABLE `sales_targets` DISABLE KEYS */;
INSERT INTO `sales_targets` VALUES (1,3,'2026-02-01',20,10,0.00,'2026-02-22 03:55:03','2026-02-22 03:55:03');
/*!40000 ALTER TABLE `sales_targets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `website_title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `website_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `footer_text` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `white_logo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dark_logo` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alt_phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alt_email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci,
  `copyright` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `facebook_link` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `messenger_link` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `instagram_link` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tiktok_link` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `x_link` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `youtube_link` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `header_code` text COLLATE utf8mb4_general_ci,
  `body_code` text COLLATE utf8mb4_general_ci,
  `client_declaration` text COLLATE utf8mb4_general_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
INSERT INTO `site_settings` VALUES (1,'The Premium Home Limited',NULL,'fxdgrdgth','6948f14a9fb8c_1766388042.png','settings_699c0cd0c3306.png','+8801958253300','+8801958253300','info@thepremiumhomes.com',NULL,'Dhaka','ï¿½ 2025 The Premium Homes Ltd. All rights reserved.','uidysfb re','kuysret87','uidsfyter','dsilfu67rt','iureyt87e','ids7rt89','','','I hereby declare that I have applied for the purchase of the above-mentioned flat under the terms and conditions set by the developer. I confirm that all the information provided in this document is true, correct, and complete to the best of my knowledge and belief.\r\n\r\nI further agree that I have carefully read, understood, and accepted all the terms and conditions, rules, and regulations related to the purchase of the said flat. I undertake to abide by all decisions, policies, and future regulations imposed by the developer in connection with this property.\r\n\r\nI also acknowledge that any false information or violation of the agreed terms may result in cancellation of the booking in accordance with the developerï¿½s policy.',1,'2025-12-15 12:14:07','2026-02-23 08:16:16');
/*!40000 ALTER TABLE `site_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_visits`
--

DROP TABLE IF EXISTS `site_visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_visits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lead_id` int unsigned NOT NULL,
  `project_id` int NOT NULL,
  `flat_id` int DEFAULT NULL,
  `visit_date` date NOT NULL,
  `visit_time` time DEFAULT NULL,
  `visit_status` enum('scheduled','completed','cancelled','no_show') COLLATE utf8mb4_general_ci DEFAULT 'scheduled',
  `feedback` text COLLATE utf8mb4_general_ci,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `lead_id` (`lead_id`),
  KEY `project_id` (`project_id`),
  KEY `flat_id` (`flat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_visits`
--

LOCK TABLES `site_visits` WRITE;
/*!40000 ALTER TABLE `site_visits` DISABLE KEYS */;
/*!40000 ALTER TABLE `site_visits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sliders`
--

DROP TABLE IF EXISTS `sliders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sliders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `subtitle` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `link` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '1 = Active, 0 = Inactive',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sliders`
--

LOCK TABLES `sliders` WRITE;
/*!40000 ALTER TABLE `sliders` DISABLE KEYS */;
INSERT INTO `sliders` VALUES (1,'Slider 1','no sub title','','693fc40ad67f6_1765786634.jpg',1,NULL,'2025-12-15 14:17:14','2025-12-15 14:17:14'),(2,'slider 2','not title','','693fd0f16af19_1765789937.webp',1,NULL,'2025-12-15 15:12:17','2025-12-15 15:12:17');
/*!40000 ALTER TABLE `sliders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `assigned_by` int DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `start_date` date DEFAULT NULL,
  `deadline` datetime DEFAULT NULL,
  `employee_accept` tinyint(1) DEFAULT '0',
  `status` enum('pending','in_progress','completed','expired') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `progress` int DEFAULT '0',
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_general_ci DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `units`
--

DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `units` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `units`
--

LOCK TABLES `units` WRITE;
/*!40000 ALTER TABLE `units` DISABLE KEYS */;
INSERT INTO `units` VALUES (1,'Unit A','2025-12-18 10:55:04','2025-12-18 10:55:04'),(2,'Unit - B','2025-12-18 10:59:55','2025-12-18 10:59:55'),(3,'Unit C','2025-12-18 11:04:55','2025-12-18 11:04:55'),(4,'Unit -A1','2025-12-22 08:02:40','2025-12-22 08:02:40'),(5,'Unit - A2','2025-12-22 08:02:55','2025-12-22 08:02:55'),(6,'Unit - A3','2025-12-22 08:03:13','2025-12-22 08:03:13'),(7,'Unit - B1','2025-12-22 08:03:26','2025-12-22 08:03:26'),(8,'Unit - B2','2025-12-22 08:03:39','2025-12-22 08:03:39'),(9,'Unit - B3','2025-12-22 08:03:54','2025-12-22 08:03:54'),(10,'Unit - C1','2025-12-22 08:04:07','2025-12-22 08:04:07'),(11,'Unit - C3','2025-12-22 08:04:40','2025-12-22 08:04:40'),(12,'Unit - C2','2025-12-22 08:04:59','2025-12-22 08:04:59');
/*!40000 ALTER TABLE `units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int unsigned DEFAULT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `user_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `designation_id` int DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci,
  `nid` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role_id` int NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `photo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `media_link1` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `media_link2` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_logout` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  KEY `fk_user_employee` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,NULL,'Farzana','farzana','01873244236','farz@gmail.com',1,'Dhaka',NULL,1,'$2y$10$tyGb6XsaCzqrB5/fmTtTEualGz9EEsCTS4F0mAz7b/ITjBISkKirS',NULL,NULL,NULL,'2026-03-29 04:16:54',NULL,'2026-02-22 06:47:08','2026-03-29 04:16:54'),(2,NULL,'Sojib','sojib','01798342755','sales@gmail.com',1,'Dhaka',NULL,5,'$2y$10$tyGb6XsaCzqrB5/fmTtTEualGz9EEsCTS4F0mAz7b/ITjBISkKirS',NULL,NULL,NULL,'2026-03-29 04:05:39',NULL,'2026-02-22 06:47:08','2026-03-29 04:05:39'),(3,1,'Fahim','Fahim','01790128471','fahim@gmail.com',1,'Mohammadpur, Dhaka-1207','23445',8,'$2y$10$Jy1aK9a5d9P7mA0TqHEkT.RS/7kPh0x/fxpmRaOR1JrLJXEV0ObsG',NULL,'','','2026-02-23 13:55:43',4,'2026-02-23 03:24:25','2026-02-23 07:55:43'),(4,NULL,'Mr. Joshim','Mr. Joshim','01762013007','hr@gmail.com',2,'Dhaka','1289234',4,'$2y$10$bhU084TG9E1hi2gpC3g4nODNLbG5lzKoo7vj/Uv6KeYWXtzduE0Ry',NULL,'aiosdiau','','2026-03-29 04:04:01',1,'2026-02-23 05:44:19','2026-03-29 04:04:01'),(5,0,'Mr. Jisan','Mr. Jisan','01390128223','accounts@gmail.com',3,'Mohammadpur, Dhaka-1207','2345676',6,'$2y$10$cIIGEotRMUt4NdR3BelTA.wRCL7CDcC.s.1UY0ncO7lbttcKda2C6',NULL,'','','2026-03-29 04:03:41',6,'2026-02-23 06:11:06','2026-03-29 04:03:41'),(6,NULL,'Management','Management','01394378234','management@gmail.com',1,'dhaka','32453',3,'$2y$10$BNGc9RJClg5hlb4tqTasqebYBq3PmZCLGw4yiWjiPN.Gh89QF9JoW',NULL,'','','2026-03-29 04:02:53',1,'2026-03-29 03:44:44','2026-03-29 04:02:53'),(7,NULL,'CED','CED','01872364346','ced@gmail.com',1,'Dhaka','823445',7,'$2y$10$a51psvUGsp/WsSE5cY2xNuKpuKSNoG/QZNNxxfWs0I.eSPM1KPAX.',NULL,'','','2026-03-29 04:01:38',6,'2026-03-29 04:01:38','2026-03-29 04:01:38'),(8,NULL,'Sales Team Head','Sales Head','01327488234','sales-head@gmail.com',1,'Dhaka','09231943',9,'$2y$10$Hr8gjcm14bLK/Ns6ws/pt.jz0PA5ObSc.jo4Z7epS23UsEkVDUXn.',NULL,'','','2026-03-29 04:16:47',1,'2026-03-29 04:16:47','2026-03-29 04:16:47');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `whatsapp_campaigns`
--

DROP TABLE IF EXISTS `whatsapp_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `whatsapp_campaigns` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `target_project_id` int DEFAULT NULL,
  `message_type` enum('text','image','pdf','button','brocure') COLLATE utf8mb4_general_ci DEFAULT 'text',
  `message_content` text COLLATE utf8mb4_general_ci NOT NULL,
  `schedule_time` datetime NOT NULL,
  `device` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Auto Select',
  `status` enum('Active','Scheduled','Paused','Completed','Failed') COLLATE utf8mb4_general_ci DEFAULT 'Scheduled',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `whatsapp_campaigns`
--

LOCK TABLES `whatsapp_campaigns` WRITE;
/*!40000 ALTER TABLE `whatsapp_campaigns` DISABLE KEYS */;
/*!40000 ALTER TABLE `whatsapp_campaigns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `whatsapp_messages`
--

DROP TABLE IF EXISTS `whatsapp_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `whatsapp_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `campaign_id` int NOT NULL,
  `lead_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('pending','sent','delivered','failed') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `campaign_id` (`campaign_id`),
  KEY `lead_id` (`lead_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `whatsapp_messages`
--

LOCK TABLES `whatsapp_messages` WRITE;
/*!40000 ALTER TABLE `whatsapp_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `whatsapp_messages` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-29  5:29:17
