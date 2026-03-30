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
  `department` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `request_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `source_table` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `source_id` int unsigned NOT NULL,
  `request_amount` decimal(12,2) DEFAULT NULL,
  `approved_amount` decimal(12,2) DEFAULT NULL,
  `requested_days` int DEFAULT NULL,
  `approved_days` int DEFAULT NULL,
  `approved_by` int unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','approved','rejected','partial') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `requested_by` int unsigned NOT NULL,
  `approve_role` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `approval_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
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
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `slug` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `excerpt` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `author` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `published_date` date DEFAULT NULL,
  `comments` int DEFAULT '0',
  `read_time` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `office` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `project_id` int DEFAULT NULL,
  `purpose_paid_to` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'cash',
  `transaction_ref` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_date` date NOT NULL,
  `bank_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_account_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cheque_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `online_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `office` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `requested_by` int NOT NULL COMMENT 'User who requested cashout',
  `purpose_paid_to` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Purpose or payee name',
  `amount` decimal(15,2) NOT NULL COMMENT 'Total requested amount',
  `approved_amount` decimal(15,2) DEFAULT NULL,
  `due_amount` decimal(15,2) NOT NULL COMMENT 'Remaining unpaid amount',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'cash',
  `transaction_ref` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `attachment_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected','partially_paid','fully_paid') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_by` int DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `approval_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
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
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
  `bank_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `account_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `account_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_default` tinyint(1) DEFAULT '0',
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'active',
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
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_status` enum('pending','contacted','closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'Track follow-up status',
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
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'Business Development',1,2,'2026-02-19 16:17:29','2026-02-19 16:17:29'),(2,'Human Resources',1,1,'2026-02-23 05:42:59','2026-02-23 05:42:59'),(3,'Finance & Accounts',1,4,'2026-02-23 06:06:59','2026-02-23 06:06:59'),(4,'Client Experience ',1,4,'2026-03-29 04:58:55','2026-03-29 09:52:04'),(5,'Marketing, Branding, & PR',1,4,'2026-03-29 05:00:28','2026-03-29 09:57:14'),(6,'Operations & Admin',1,4,'2026-03-29 05:00:53','2026-03-29 09:57:36'),(18,'Legal & Land',1,4,'2026-03-29 12:00:58','2026-03-29 12:01:26'),(19,'Management',1,4,'2026-03-29 12:01:49','2026-03-29 12:01:49'),(20,'Purchase & Procurement',1,4,'2026-03-29 12:02:29','2026-03-29 12:02:29'),(21,'Engineering, Construction, & Design',1,4,'2026-03-29 12:02:57','2026-03-29 12:02:57'),(22,'IT & Development',1,4,'2026-03-29 12:03:15','2026-03-29 12:03:15'),(23,'Internal Audit & Quality Control',1,4,'2026-03-29 12:03:41','2026-03-29 12:03:41'),(24,'Design & Creative ',1,4,'2026-03-29 12:03:59','2026-03-29 12:03:59'),(25,'Planning & Development',1,4,'2026-03-29 12:04:47','2026-03-29 12:04:47');
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
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `department_id` int unsigned NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `designations`
--

LOCK TABLES `designations` WRITE;
/*!40000 ALTER TABLE `designations` DISABLE KEYS */;
INSERT INTO `designations` VALUES (13,'Executive',2,NULL,'2026-03-29 12:04:17','2026-03-29 12:04:17'),(14,'Assistant General Manager (AGM)',2,NULL,'2026-03-29 12:04:42','2026-03-29 12:04:42'),(15,'Executive',1,NULL,'2026-03-29 12:05:07','2026-03-29 12:05:07'),(16,'Intern',1,NULL,'2026-03-29 12:05:25','2026-03-29 12:05:25'),(17,'Senior Executive',1,NULL,'2026-03-29 12:05:53','2026-03-29 12:05:53'),(18,'Deputy General Manager (DGM)',25,NULL,'2026-03-29 12:07:32','2026-03-29 12:07:32'),(19,'Deputy Manager',4,NULL,'2026-03-29 12:10:17','2026-03-29 12:10:17'),(20,'Executive',4,NULL,'2026-03-29 12:10:43','2026-03-29 12:10:43'),(21,'Senior Executive',4,NULL,'2026-03-29 12:18:02','2026-03-29 12:18:02'),(22,'Junior Executive',4,NULL,'2026-03-29 12:19:38','2026-03-29 12:19:38'),(23,'Intern',4,NULL,'2026-03-29 12:19:53','2026-03-29 12:19:53'),(24,'Junior Executive',1,NULL,'2026-03-29 12:25:10','2026-03-29 12:25:10'),(25,'Deputy Manager',1,NULL,'2026-03-29 12:26:20','2026-03-29 12:26:20'),(26,'Management Trainee Officer (MTO)',1,NULL,'2026-03-29 12:29:35','2026-03-29 12:29:35'),(27,'Assistant Manager',0,NULL,'2026-03-30 11:12:20','2026-03-30 11:12:20');
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
  `status` enum('pending','paid','missed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending',
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
  `today_status` enum('Present','Late','Absent','On Leave','Half-Day') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Absent',
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
  `day_off_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=159 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_leave_balances`
--

LOCK TABLES `employee_leave_balances` WRITE;
/*!40000 ALTER TABLE `employee_leave_balances` DISABLE KEYS */;
INSERT INTO `employee_leave_balances` VALUES (1,2,1,20,'2026-03-29 09:40:23','2026-03-29 09:40:23'),(2,2,2,12,'2026-03-29 09:40:23','2026-03-29 09:40:23'),(3,3,1,20,'2026-03-29 10:23:39','2026-03-29 10:23:39'),(4,3,2,12,'2026-03-29 10:23:39','2026-03-29 10:23:39'),(5,4,1,20,'2026-03-29 10:28:20','2026-03-29 10:28:20'),(6,4,2,12,'2026-03-29 10:28:20','2026-03-29 10:28:20'),(7,5,1,20,'2026-03-29 10:31:45','2026-03-29 10:31:45'),(8,5,2,12,'2026-03-29 10:31:45','2026-03-29 10:31:45'),(9,6,1,20,'2026-03-30 04:29:15','2026-03-30 04:29:15'),(10,6,2,12,'2026-03-30 04:29:15','2026-03-30 04:29:15'),(11,7,1,20,'2026-03-30 04:34:26','2026-03-30 04:34:26'),(12,7,2,12,'2026-03-30 04:34:26','2026-03-30 04:34:26'),(13,8,1,20,'2026-03-30 04:40:43','2026-03-30 04:40:43'),(14,8,2,12,'2026-03-30 04:40:43','2026-03-30 04:40:43'),(15,9,1,20,'2026-03-30 04:56:54','2026-03-30 04:56:54'),(16,9,2,12,'2026-03-30 04:56:54','2026-03-30 04:56:54'),(17,10,1,20,'2026-03-30 05:26:05','2026-03-30 05:26:05'),(18,10,2,12,'2026-03-30 05:26:05','2026-03-30 05:26:05'),(19,11,1,20,'2026-03-30 05:30:21','2026-03-30 05:30:21'),(20,11,2,12,'2026-03-30 05:30:21','2026-03-30 05:30:21'),(21,12,1,20,'2026-03-30 05:35:10','2026-03-30 05:35:10'),(22,12,2,12,'2026-03-30 05:35:10','2026-03-30 05:35:10'),(23,13,1,20,'2026-03-30 05:40:37','2026-03-30 05:40:37'),(24,13,2,12,'2026-03-30 05:40:37','2026-03-30 05:40:37'),(25,14,1,20,'2026-03-30 05:47:14','2026-03-30 05:47:14'),(26,14,2,12,'2026-03-30 05:47:14','2026-03-30 05:47:14'),(27,15,1,20,'2026-03-30 05:51:15','2026-03-30 05:51:15'),(28,15,2,12,'2026-03-30 05:51:15','2026-03-30 05:51:15'),(29,16,1,20,'2026-03-30 05:59:32','2026-03-30 05:59:32'),(30,16,2,12,'2026-03-30 05:59:32','2026-03-30 05:59:32'),(31,17,1,20,'2026-03-30 06:43:56','2026-03-30 06:43:56'),(32,17,2,12,'2026-03-30 06:43:56','2026-03-30 06:43:56'),(33,18,1,20,'2026-03-30 06:50:40','2026-03-30 06:50:40'),(34,18,2,12,'2026-03-30 06:50:40','2026-03-30 06:50:40'),(35,19,1,20,'2026-03-30 07:01:11','2026-03-30 07:01:11'),(36,19,2,12,'2026-03-30 07:01:11','2026-03-30 07:01:11'),(37,20,1,20,'2026-03-30 07:06:02','2026-03-30 07:06:02'),(38,20,2,12,'2026-03-30 07:06:02','2026-03-30 07:06:02'),(39,21,1,20,'2026-03-30 07:56:24','2026-03-30 07:56:24'),(40,21,2,12,'2026-03-30 07:56:24','2026-03-30 07:56:24'),(41,22,1,20,'2026-03-30 08:29:14','2026-03-30 08:29:14'),(42,22,2,12,'2026-03-30 08:29:14','2026-03-30 08:29:14'),(43,23,1,20,'2026-03-30 08:42:17','2026-03-30 08:42:17'),(44,23,2,12,'2026-03-30 08:42:17','2026-03-30 08:42:17'),(45,24,1,20,'2026-03-30 08:46:37','2026-03-30 08:46:37'),(46,24,2,12,'2026-03-30 08:46:37','2026-03-30 08:46:37'),(47,25,1,20,'2026-03-30 08:47:19','2026-03-30 08:47:19'),(48,25,2,12,'2026-03-30 08:47:19','2026-03-30 08:47:19'),(49,26,1,20,'2026-03-30 08:50:26','2026-03-30 08:50:26'),(50,26,2,12,'2026-03-30 08:50:26','2026-03-30 08:50:26'),(51,27,1,20,'2026-03-30 08:52:52','2026-03-30 08:52:52'),(52,27,2,12,'2026-03-30 08:52:52','2026-03-30 08:52:52'),(53,28,1,20,'2026-03-30 08:56:43','2026-03-30 08:56:43'),(54,28,2,12,'2026-03-30 08:56:43','2026-03-30 08:56:43'),(55,29,1,20,'2026-03-30 09:00:39','2026-03-30 09:00:39'),(56,29,2,12,'2026-03-30 09:00:39','2026-03-30 09:00:39'),(57,30,1,20,'2026-03-30 09:03:57','2026-03-30 09:03:57'),(58,30,2,12,'2026-03-30 09:03:57','2026-03-30 09:03:57'),(59,31,1,20,'2026-03-30 09:09:53','2026-03-30 09:09:53'),(60,31,2,12,'2026-03-30 09:09:53','2026-03-30 09:09:53'),(61,32,1,20,'2026-03-30 09:12:53','2026-03-30 09:12:53'),(62,32,2,12,'2026-03-30 09:12:53','2026-03-30 09:12:53'),(63,33,1,20,'2026-03-30 09:16:00','2026-03-30 09:16:00'),(64,33,2,12,'2026-03-30 09:16:00','2026-03-30 09:16:00'),(65,34,1,20,'2026-03-30 09:19:00','2026-03-30 09:19:00'),(66,34,2,12,'2026-03-30 09:19:00','2026-03-30 09:19:00'),(67,35,1,20,'2026-03-30 09:23:02','2026-03-30 09:23:02'),(68,35,2,12,'2026-03-30 09:23:02','2026-03-30 09:23:02'),(69,36,1,20,'2026-03-30 09:30:56','2026-03-30 09:30:56'),(70,36,2,12,'2026-03-30 09:30:56','2026-03-30 09:30:56'),(71,37,1,20,'2026-03-30 09:38:06','2026-03-30 09:38:06'),(72,37,2,12,'2026-03-30 09:38:06','2026-03-30 09:38:06'),(73,38,1,20,'2026-03-30 09:38:33','2026-03-30 09:38:33'),(74,38,2,12,'2026-03-30 09:38:33','2026-03-30 09:38:33'),(75,39,1,20,'2026-03-30 09:40:37','2026-03-30 09:40:37'),(76,39,2,12,'2026-03-30 09:40:37','2026-03-30 09:40:37'),(77,40,1,20,'2026-03-30 09:41:04','2026-03-30 09:41:04'),(78,40,2,12,'2026-03-30 09:41:04','2026-03-30 09:41:04'),(79,41,1,20,'2026-03-30 09:44:21','2026-03-30 09:44:21'),(80,41,2,12,'2026-03-30 09:44:21','2026-03-30 09:44:21'),(81,42,1,20,'2026-03-30 09:48:38','2026-03-30 09:48:38'),(82,42,2,12,'2026-03-30 09:48:38','2026-03-30 09:48:38'),(83,43,1,20,'2026-03-30 09:56:21','2026-03-30 09:56:21'),(84,43,2,12,'2026-03-30 09:56:21','2026-03-30 09:56:21'),(85,44,1,20,'2026-03-30 09:59:28','2026-03-30 09:59:28'),(86,44,2,12,'2026-03-30 09:59:28','2026-03-30 09:59:28'),(87,45,1,20,'2026-03-30 10:03:44','2026-03-30 10:03:44'),(88,45,2,12,'2026-03-30 10:03:44','2026-03-30 10:03:44'),(89,46,1,20,'2026-03-30 10:05:23','2026-03-30 10:05:23'),(90,46,2,12,'2026-03-30 10:05:23','2026-03-30 10:05:23'),(91,47,1,20,'2026-03-30 10:07:57','2026-03-30 10:07:57'),(92,47,2,12,'2026-03-30 10:07:57','2026-03-30 10:07:57'),(93,48,1,20,'2026-03-30 10:09:50','2026-03-30 10:09:50'),(94,48,2,12,'2026-03-30 10:09:50','2026-03-30 10:09:50'),(95,49,1,20,'2026-03-30 10:16:53','2026-03-30 10:16:53'),(96,49,2,12,'2026-03-30 10:16:53','2026-03-30 10:16:53'),(97,50,1,20,'2026-03-30 10:20:00','2026-03-30 10:20:00'),(98,50,2,12,'2026-03-30 10:20:00','2026-03-30 10:20:00'),(99,51,1,20,'2026-03-30 10:24:57','2026-03-30 10:24:57'),(100,51,2,12,'2026-03-30 10:24:57','2026-03-30 10:24:57'),(101,52,1,20,'2026-03-30 10:25:00','2026-03-30 10:25:00'),(102,52,2,12,'2026-03-30 10:25:00','2026-03-30 10:25:00'),(103,53,1,20,'2026-03-30 10:31:00','2026-03-30 10:31:00'),(104,53,2,12,'2026-03-30 10:31:00','2026-03-30 10:31:00'),(105,54,1,20,'2026-03-30 10:31:59','2026-03-30 10:31:59'),(106,54,2,12,'2026-03-30 10:31:59','2026-03-30 10:31:59'),(107,55,1,20,'2026-03-30 10:35:13','2026-03-30 10:35:13'),(108,55,2,12,'2026-03-30 10:35:13','2026-03-30 10:35:13'),(109,56,1,20,'2026-03-30 10:35:17','2026-03-30 10:35:17'),(110,56,2,12,'2026-03-30 10:35:17','2026-03-30 10:35:17'),(111,57,1,20,'2026-03-30 10:40:34','2026-03-30 10:40:34'),(112,57,2,12,'2026-03-30 10:40:34','2026-03-30 10:40:34'),(113,58,1,20,'2026-03-30 10:41:19','2026-03-30 10:41:19'),(114,58,2,12,'2026-03-30 10:41:19','2026-03-30 10:41:19'),(115,59,1,20,'2026-03-30 10:44:55','2026-03-30 10:44:55'),(116,59,2,12,'2026-03-30 10:44:55','2026-03-30 10:44:55'),(117,60,1,20,'2026-03-30 10:45:59','2026-03-30 10:45:59'),(118,60,2,12,'2026-03-30 10:45:59','2026-03-30 10:45:59'),(119,61,1,20,'2026-03-30 10:49:04','2026-03-30 10:49:04'),(120,61,2,12,'2026-03-30 10:49:04','2026-03-30 10:49:04'),(121,62,1,20,'2026-03-30 10:49:23','2026-03-30 10:49:23'),(122,62,2,12,'2026-03-30 10:49:23','2026-03-30 10:49:23'),(123,63,1,20,'2026-03-30 10:54:37','2026-03-30 10:54:37'),(124,63,2,12,'2026-03-30 10:54:37','2026-03-30 10:54:37'),(125,64,1,20,'2026-03-30 10:56:23','2026-03-30 10:56:23'),(126,64,2,12,'2026-03-30 10:56:23','2026-03-30 10:56:23'),(127,65,1,20,'2026-03-30 10:59:42','2026-03-30 10:59:42'),(128,65,2,12,'2026-03-30 10:59:42','2026-03-30 10:59:42'),(129,66,1,20,'2026-03-30 11:00:09','2026-03-30 11:00:09'),(130,66,2,12,'2026-03-30 11:00:09','2026-03-30 11:00:09'),(131,67,1,20,'2026-03-30 11:10:48','2026-03-30 11:10:48'),(132,67,2,12,'2026-03-30 11:10:48','2026-03-30 11:10:48'),(133,68,1,20,'2026-03-30 11:17:42','2026-03-30 11:17:42'),(134,68,2,12,'2026-03-30 11:17:42','2026-03-30 11:17:42'),(135,69,1,20,'2026-03-30 11:19:42','2026-03-30 11:19:42'),(136,69,2,12,'2026-03-30 11:19:42','2026-03-30 11:19:42'),(137,70,1,20,'2026-03-30 11:22:17','2026-03-30 11:22:17'),(138,70,2,12,'2026-03-30 11:22:17','2026-03-30 11:22:17'),(139,71,1,20,'2026-03-30 11:22:57','2026-03-30 11:22:57'),(140,71,2,12,'2026-03-30 11:22:57','2026-03-30 11:22:57'),(141,72,1,20,'2026-03-30 11:25:48','2026-03-30 11:25:48'),(142,72,2,12,'2026-03-30 11:25:48','2026-03-30 11:25:48'),(143,73,1,20,'2026-03-30 11:26:09','2026-03-30 11:26:09'),(144,73,2,12,'2026-03-30 11:26:09','2026-03-30 11:26:09'),(145,74,1,20,'2026-03-30 11:33:39','2026-03-30 11:33:39'),(146,74,2,12,'2026-03-30 11:33:39','2026-03-30 11:33:39'),(147,75,1,20,'2026-03-30 11:34:33','2026-03-30 11:34:33'),(148,75,2,12,'2026-03-30 11:34:33','2026-03-30 11:34:33'),(149,76,1,20,'2026-03-30 11:38:41','2026-03-30 11:38:41'),(150,76,2,12,'2026-03-30 11:38:41','2026-03-30 11:38:41'),(151,77,1,20,'2026-03-30 11:38:48','2026-03-30 11:38:48'),(152,77,2,12,'2026-03-30 11:38:48','2026-03-30 11:38:48'),(153,78,1,20,'2026-03-30 11:42:56','2026-03-30 11:42:56'),(154,78,2,12,'2026-03-30 11:42:56','2026-03-30 11:42:56'),(155,79,1,20,'2026-03-30 11:43:09','2026-03-30 11:43:09'),(156,79,2,12,'2026-03-30 11:43:09','2026-03-30 11:43:09'),(157,80,1,20,'2026-03-30 11:52:36','2026-03-30 11:52:36'),(158,80,2,12,'2026-03-30 11:52:36','2026-03-30 11:52:36');
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
  `approval_status` enum('pending','approved','partial','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
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
  `task_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `hours_spent` decimal(4,2) NOT NULL DEFAULT '0.00',
  `status` enum('Pending','In Progress','Done') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
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
  `full_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `employee_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `father_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `emergency_contact` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `qualification` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `experience` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `pass_num` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nid` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department_id` int unsigned NOT NULL,
  `designation_id` int unsigned DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `workplace` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `gross_salary` float DEFAULT NULL,
  `basic_salary` float DEFAULT NULL,
  `account_holder_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `make_user` int DEFAULT '0',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `bank_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `branch_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `social_media1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `social_media2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `social_media3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `photo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employee_type` enum('permanent','contract','director','intern','remote','probation','management') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employee_status` enum('active','inactive','resigned','terminated','on_leave') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'active',
  `responsibilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_by` int DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
    `team_id` int(11) DEFAULT NULL,
  UNIQUE KEY `employee_id` (`employee_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `department_id` (`department_id`),
  KEY `designation_id` (`designation_id`),
  KEY `fk_employee_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,NULL,'Fahim','1','fahim','fahim@gmail.com','Forhad','01734555783','01734555783','BBA','','Dhaka','','23784',1,0,NULL,'Gulshan','2026-01-01',30000,20000,'Fahim','7523423478',1,'$2y$10$JphYkjE9NT3MdD93lmgv7uZ2uu4nWUubfKZHjdFvqN6rq6KTk8UIC','City Bank','Shaymoli','','','','','permanent','active','',4,NULL,'2026-03-29 03:42:23','2026-03-30 05:14:18',''),
(2,16,'Ashfaque Bhuiyan Omi','1078','Ashfaque Omi','ashfaqueomi.tphl25@gmail.com','Md. Alamgir Bhuiyan (Late)','01958253303','01729699900','BBA','','Darul Aman Housing, Mirpur-2, Dhaka - 1206','','2410888859',2,20,'2002-01-01','Site Office','2025-01-01',20000,10000,'Ashfaque Bhuiyan Omi','1341260016760',0,'','EBL','','','','','','permanent','active','',4,NULL,'2026-03-29 09:40:23','2026-03-30 11:58:30',''),
(5,NULL,'Bornali Haque','1157','Bornali Haque','bornalihaque.tphl@gmail.com','Late Md. Sanaul Haque','01335258340','01673002949','','','RUAP, Uttara 18, Dhaka','','',2,13,'2000-08-02','Ashulia Site Office','2026-01-01',17000,8500,'BORNALI HAQUE','1041260066908',1,'$2y$10$eH.KzQlN0pmDgObgrOSMwuGlHSCjxghQAbXzmamc/Y6lPw89nK9aa','EBL','','','','','','permanent','active','',4,NULL,'2026-03-29 10:31:45','2026-03-30 11:56:44',''),
(6,15,'Md. Kowsar Ahammed','1024','kowsar ahammed','kawsar.tphl@gmail.com','Md. Bellal Hossain','01901310845','01724423987','BBA','','Vill: Bhubangati, Post: Biswarigacha, Thana: Dhunat, Zilla: Bogura','','7813436693',1,21,'2001-01-01','Site Office','2025-01-01',31200,15600,'','',0,'','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 04:29:15','2026-03-30 05:19:07',''),
(7,14,'Saleh Ahamed','1023','saleh ahmed','saleh.tphl@gmail.com','','0 1901-3650','01816757355','Bsc','','','','3755061037',1,21,'1998-01-01','Site Office','2025-01-01',24000,12000,'','',0,'','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 04:34:26','2026-03-30 05:18:44',''),
(8,12,'Sanjana Hossain Moure','1025','sanjana moure','moure.tphl@gmail.com','Md.Mobarak Hossain','01901310848','01819203553','BBA','','Mirpur-2 water tank, Block - F, road -3, Dhaka -1216','','',1,15,'2001-01-01','Site Office','2025-01-01',17000,8500,'','',0,'','EBL','','','','','','permanent','active','',4,NULL,'2026-03-30 04:40:43','2026-03-30 05:15:10',''),
(9,13,'Md. Rabbi Hossain','1028','rabbi hossain','rabbihossain.tphl@gmail.com','Md. Jahangir','0 1901-3108','01610874166','BBA','','Baro Bazar, Ashulia Model Town, Savar, Dhaka.','','2864699331',1,20,'2026-01-01','Site Office','2025-01-01',17000,8500,'','',0,'','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 04:56:54','2026-03-30 05:15:44',''),
(10,NULL,'Mehnaz Ahmed','1034','mehnaz ahmed','mehnaz.tphl@gmail.com','Miftah Uddin Ahmed','0 1901-3650','01732339816','Bsc','','South chayabithi, Joydebpur, Gazipur','','9160336244',1,20,'2001-07-16','Corporate Office','2025-06-03',17000,8500,'','',1,'$2y$10$/gk19RzHLf0yn6e2COmZle1S.NRl/Crc.r2O/fAjimhroA/OoO4Q2','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 05:26:05','2026-03-30 05:26:05',''),
(11,NULL,'Mr. Ibrahim Khalil','1035','ibrahim khalil','ibrahim.tphl@gmail.com','MD. MONTAZ ALI','01958253319','01557020388','Bachelor  of Real Estate','','5/9, Dakkhin Rajashon, Savar, Dhaka','','2805006299',1,20,'1988-03-05','Site Office','2025-06-17',20000,10000,'','',1,'$2y$10$Az8vEYQztfIOz0whKTPObeMPsCVRaDP5OD3jXqSzt/yQ5dAnMwunO','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 05:30:21','2026-03-30 05:30:21',''),
(12,NULL,'Shammi Akther','1040','shammi akhter','shammi.tphl@gmail.com','Md.Shahjahan','0 1901-3108','01763469727','B.Sc in  Nutrition & Food Engineering','','13 no sector ,uttara.','','6923766643',1,20,'2001-06-01','Corporate Office','2025-06-26',17000,8500,'','',1,'$2y$10$O9e/Nt2SkTCAwh4YgMzJtunLoKoBM/4Gqmv9hIycUzVIQKpDvsdxG','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 05:35:10','2026-03-30 05:35:10',''),
(13,NULL,'Rejwan Ahmed','1041','rejwan ahmad','rejwantphl@gmail.com','Md.Johur Ali','01901365040','01716468874','Bachelor of Business Administration (BBA)','','Sreemangal, Moulvibazar.','','4660653587',1,21,'2002-07-14','Corporate Office','2025-10-06',24000,12000,'','',1,'$2y$10$ZxUMDVmiZC3tIobTcQFzmeMZmGPxW3rViNd89KHxG8L/d52bxXbAu','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 05:40:37','2026-03-30 05:40:37',''),
(14,NULL,'MD. Feroz Ahamed','1048','feroz ahamed','feroz.tphl@gmail.com','MD. Gaziur Rahman','01958253327','01646627903','Bsc in Civil','','Savar, Dhaka','','3761348154',1,20,'2000-08-06','Site Office','2025-08-15',17000,8500,'','',1,'$2y$10$syN/VJQVFPODQvFA5ACBg.FLdlS1teYz7nryro/YJE6A.2a7mGKo6','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 05:47:14','2026-03-30 05:47:14',''),
(15,NULL,'Mojammel Haque','1049','mojammel haque','mojammel.tphl@gmail.com','Md. Habibur Rahman','01958253324','01646627903','BSc Honors','','B-4, G-1, Sher-E-Bangla Nagar Staff Quarter, Dhaka-1207','','865 729 7449',1,21,'1993-12-20','ATI Model Town (Site Office)','2025-08-16',24000,12000,'','',1,'$2y$10$zJwGI7jw8UlxRk/NeeL20OeEQoLdPfjBRbs93Ch0l9oe5yCBEWJJ6','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 05:51:15','2026-03-30 05:51:15',''),
(16,NULL,'Mehedi Hasan Shuvo','1062','hasan shuvo','shuvo.tphl@gmail.com','','01901365048','','','','','','',1,26,'1997-03-03','Site Office','2025-08-02',17000,8500,'','',1,'$2y$10$2oZs8Bb0f1pKj23AGczXRuf2p6sFMymnDRgO4lIWPvr5XLpxzfEpa','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 05:59:32','2026-03-30 05:59:32',''),
(17,NULL,'Rakib Al Hasan','1051','rakibal hasan','rakib.tphl@gmail.com','Abu Bakar Siddik','01958253321','01723919590','B.sc','','Daffodil smart city, Birulia savar Dhaka','','4654750571',1,20,'1999-06-13','Site Office','2025-08-15',17000,8500,'','',1,'$2y$10$9Qycqk8Oy51JvZm6r1.p8OXymPDXfGbM.5hhqkkF4pCCeQmjHJcfq','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 06:43:56','2026-03-30 06:43:56',''),
(18,NULL,'Md. Nazmul Khan Shihab','1050','khan Shihab','shihab.tphl@gmail.com','Md. Serajul Haque','01958253326','01715394514','Bachelor  of Real Estate','','2450/11,East Dandabor, Palashbari, Savar cantonment -1344, Savar, Dhaka','','1968492643',1,20,'2003-12-10','Corporate Office','2025-07-30',17000,8500,'','',1,'$2y$10$WXxrN0DWThf1fPmnvLNaj.dCQ9vOPq.WiWQwLDjER00tevsxemmrq','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 06:50:40','2026-03-30 06:50:40',''),
(19,NULL,'Munshi Aiman Kabir','1058','aiman kabir','aiman.tphl@gmail.com','Munshi Humayun Kabir','01958253318','01837041564','BBA','','Savar, Dhaka','','6915659434',1,20,'2002-01-20','Site Office','2025-07-29',17000,8500,'','',1,'$2y$10$K06cmrzOAagZ333rxWiP4OLRwWk753LtlPaZEvvuXl2R6bZQwJiVK','','','','','','','permanent','active','',4,NULL,'2026-03-30 07:01:11','2026-03-30 07:01:11',''),
(20,NULL,'Md. Tariqul Islam Illion','1055','tariqul islam','illion.tphl@gmail.com','MD ABUL KALAM AZAD','0 1958-2533','01723051437','BBA','','Ashulia Model Town, Khagan Bazar, Birulia, Savar, Dhaka','','1968356707',1,20,'2001-05-10','Corporate Office','2025-09-01',17000,8500,'','',1,'$2y$10$dm0B1E/O/sjxKrtmet2LCudA0Zro/Ckf3yjWNq.fbrGvN.Je9ud5i','','','','','','','permanent','active','',4,NULL,'2026-03-30 07:06:02','2026-03-30 07:06:02',''),
(21,NULL,'Shafia Afrin Shahana','1197','Shafia Afrin Shahana','shafia.tphl@gmail.com','','01335258332','01580903690','','','Apon Bazar Samiti Market, Rayerbag, Jatrabari, Dhaka','','1954806822',1,15,NULL,'Corporate Office','2026-01-01',23000,11500,'SHAFIA AFRIN SHAHANA','1041260067467',1,'$2y$10$I4jV1fxJ96DT6fAQnIU2pu.7/eC1R1.cJy0a.oxEPGEJKHRQ7edkq','EBL','','','','','','permanent','active','',4,NULL,'2026-03-30 07:56:24','2026-03-30 08:29:48',''),
(22,NULL,'Abir Ahmed','1196','Abir Ahmed','abir0.tphl@gmail.com','','01335258328','01835407105','','','Poshu Hospital Para, Magura Sadar,Magura','','8703180417',1,15,'1997-12-28','Corporate Office','2026-02-18',17000,8500,'ABIR AHMED','1041260067536',1,'$2y$10$eejMPFd5ERwDC0PHtoRxUuTKwV8JqxmRINQrK5.rfPEtOvIarJ.8i','EBL','','','','','','permanent','active','',4,NULL,'2026-03-30 08:29:14','2026-03-30 08:29:14',''),
(23,NULL,'Shanawaz Khan','1195','Shanawaz Khan','shanawaz.tphl@gmail.com','','01335258330','01763963205','','','House-12,Road-4,Block-J,Banasree Meradia Khilgaon Dhaka 1219','','5115227323',1,15,'2002-10-09','Corporate Office','2026-02-18',17000,8500,'SHANAWAZ KHAN','1041260067875',1,'$2y$10$rD9P.B.PqmQnB0djvOhSVe2f4l.rkmLHV4bf.oiWOXhApnrN8jT.i','EBL','','','','','','permanent','active','',4,NULL,'2026-03-30 08:42:17','2026-03-30 08:42:17',''),
(24,NULL,'Md. Saiful Islam Jony','1067','saiful jony','saif.thpl@gmail.com','Md.Badsha Mia Bacchu','01958253340','01911318557','BBA','','104/A/9 Buddhibi Road,Mohammadpur,Dhaka-1207','','5557674677',1,23,'1996-09-30','ATI Model Town (Site Office)','2025-10-01',27000,13500,'MD. SAIFUL ISLAM JONY','1041260063477',1,'$2y$10$Z8fQ2qPgsv8Ud27YlwHLI.49bjKKqmIE8RPupwEx/NzdpP0YfybmW','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 08:46:37','2026-03-30 08:46:37',''),
(25,NULL,'Aranya Bhowmick Dhrubo','1194','Aranya Bhowmick Dhrubo','dhrubo.tphl@gmail.com','','01335258329','01782482200','','','15 Shah-Shaheb Lane Narinda, Dhaka','','3315312474',1,20,'2001-04-06','Corporate Office','2026-02-18',17000,8500,'','',1,'$2y$10$lx1vYYIursBz/Orbh19WOuopN5uG5/WhMKDKqjOJPLdVkPcGRLQZm','','','','','','','permanent','active','',4,NULL,'2026-03-30 08:47:19','2026-03-30 08:47:19',''),
(26,NULL,'Tabassum Akter Soma','1063','tabassum soma','soma.tphl@gmail.com','Md. Munsur Ali','01958253374','01568437121','Bachelor  of Real Estate','','Ashulia Model Town, Savar, Dhaka','','3758694214',1,23,NULL,'Site Office','2025-12-07',8000,4000,'','',1,'$2y$10$GXg86V45d3zrBFl2trwz.OAlB3QWCuo85YjgLONzOYRUntWtsEYym','','','','','','','contract','active','',4,NULL,'2026-03-30 08:50:26','2026-03-30 08:50:26',''),
(27,NULL,'Sadat Hossain','1193','Sadat Hossain','sadathossain11@gmail.com','','01335258331','01302767082','','','116, Jagannath Saha Road, Lalbagh, Dhaka-1211','','2852429642',1,15,'1997-01-01','Ati Model Town (Site Office)','2026-01-01',17000,8500,'SADAT HOSSAIN','1041260067478',1,'$2y$10$66MIG6bb/Sto95wo3Uo97OERTs127Gpq9GrrMtH4uLPcz0fJKUu0y','EBL','','','','','','permanent','active','',4,NULL,'2026-03-30 08:52:52','2026-03-30 09:08:08',''),
(28,NULL,'Md. Ajizul Islam Manik','1075','ajizul manik','manik.tphl@gmail.com','Late Md. Nazrul Islam','01958253338','01903606903','BBA','','1/B , Nondipara, Dhaka ,1219','','3255221362',1,20,'1994-09-26','Corporate Office','2025-10-13',17000,8500,'MD. AJIZUL ISLAM','1041260063262',1,'$2y$10$GK43JFERDmmRU1Ql3wQfdusKHg1QUKfyzFj9NcBu1JkGfKH/PVBmO','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 08:56:43','2026-03-30 08:56:43',''),
(29,NULL,'Tasnur Aman Satu','1082','aman satu','satu.tphl@gmail.com','Md. Aman Ullah','01958253363','01319919871','Bachelor  of Real Estate','','kumkumari- Midha bari','','7361471282',1,20,'2003-12-02','Site Office','2025-10-22',17000,8500,'TANSUR AMAN SATU','1041260062816',1,'$2y$10$bX2dYIO2SxfBZvmOyXInqeIOYNy3cVKKKTaKCJQhz4Qei.hLmBTCW','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 09:00:39','2026-03-30 09:00:39',''),
(30,NULL,'Md. Tasdikul Hasan Sikder','1187','Md. Tasdikul Hasan Sikder','tasdik.tphl@gmail.com','','01335258317','01704555164','','','523 East Kazipara, Dhaka, Mirpur-1216','','2863855777',1,13,'2003-12-24','Corporate Office','2026-02-07',17000,8500,'MD. TASDIKUL HASAN SIKDER','1041260066792',1,'$2y$10$IYtgw5w6cjWl9NPeYe7dwewxOd/Y/9BxkVbkAOZVV5RiA5LRPqYn2','EBL','','','','','','permanent','active','',4,NULL,'2026-03-30 09:03:57','2026-03-30 09:03:57',''),
(31,NULL,'Qazi Nadeem Javed','1084','Qazi Nadeem Javed','nadeem.tphl@gmail.com','Qazi Qadir Alam','‪0195825333','01673089031','Masters','','Sector 10 Road 7 uttara','','2350020026',1,25,'1989-03-05','Corporate Office','2025-11-19',35000,17500,'QAZI NADEEM JAVED','1041260064718',1,'$2y$10$dWmsFrPYBGJkCfJfBXnUjOcdXE6ZNqK9XZFKl5ko3ZteQw0nNoQsO','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 09:09:53','2026-03-30 09:09:53',''),
(32,NULL,'Nayeemur Rahman','1186','Nayeemur Rahman','nayeemtonmoytphl@gmail.com','','01335258318','01711942180','','','Road No. 4, F Block, Mirpur-2','','1960680278',1,15,'2026-05-22','Corporate Office','2026-02-07',17000,8500,'NAYEEMUR RAHMAN','1041260066817',1,'$2y$10$.VVBuN5Lq1XY4Z5BKVG8WeFHCmc3o0GN3rcjUYKFXYCzJXoi6do82','EBL','','','','','','permanent','active','',4,NULL,'2026-03-30 09:12:53','2026-03-30 09:12:53',''),
(33,NULL,'Mahin - Al Beruni','1120','Mahin - Al Beruni','tphl.mahin@gmail.com','MD. Maimul Hoque','01958253328','01715155844','BBA','','House 40/3, Varalipara, Boro Bangram, Sopura, Shah Mokhdum, Rajshahi - 6203','','8710606925',1,26,'2001-09-08','Site Office','2026-01-01',17000,8500,'MAHIN-AL BERUNI','1041260065053',1,'$2y$10$R0KUXo2kgutor4KlwwcBcOwju3DLkoyowWOYIXTuTQriN4TcjVKBq','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 09:16:00','2026-03-30 09:16:00',''),
(35,NULL,'Md. Momidul Islam','1100','Md. Momidul Islam','momidul.tphl@gmail.com','Md Amir Hossain','01958253335','01792700012','','','Nolbata, Hatiandaha, Singra, Natore','','6453089770',1,20,'1997-06-06','ATI Model Town (Site Office)','2025-12-15',17000,8500,'MD MOMIDUL ISLAM','1041260064742',1,'$2y$10$YaXdcRxYQc0gSdmkDYc7BeZtQqZdun2r.DPqDAycXrRmiv8EQ4yLS','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 09:23:02','2026-03-30 09:23:02',''),
(36,NULL,'Rifat Ahsan','1123','Rifat Ahsan','rifat.tphl@gmail.com','Azizul Haque Rashid','01958583350','01615580624','','','Khilkhet kha para','','4213633714',1,20,'1998-01-20','Corporate Office','2026-01-06',17000,8500,'RIFAT AHSAN','1041260065288',1,'$2y$10$/rt4WJ/SidkYyjQg8QNHP.xA1a.8d50zARylQ6lurhAngF69XeEIq','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 09:30:56','2026-03-30 09:30:56',''),
(38,NULL,'Sudipto Azad Hriday','1124','Sudipto Azad Hriday','sudiptoazad.tphl@gmail.com','Md. Abdus Samad Azad','01901365046','01755088763','','','38, Road 3B, Mohammadia Housing Limited, Mohammadpur, Dhaka-1207','','9151890663',1,17,'1997-01-01','ATI Model Town (Site Office)','2025-01-01',24000,12000,'SUDIP AZAD HRIDAY','1041260065370',1,'$2y$10$Jsi.ZZuzQqLX0Vg.MVrRIeg57NM1gCV09L/feEf6ON0zVRJ5QnrH2','EBL','Gulshan- 2','','','','','permanent','inactive','',4,NULL,'2026-03-30 09:38:33','2026-03-30 09:39:38',''),
(39,NULL,'Al Mubin Shuvo','1182','Al Mubin Shuvo','almubintphl@gmail.com','','01335258302','01964464537','','','Kumarkhali,Kushtia','','5564288271',1,15,'2003-08-14','Ati Model Town (Site Office)','2026-02-05',17000,8500,'AL MUBIN SHUVO','1041260066996',1,'$2y$10$QqxgPrxtpxFXZYvnDTg6I.lNuuCvXYplWWBCMTOJB.Z.DM0bnos9u','EBL','','','','','','permanent','active','',4,NULL,'2026-03-30 09:40:37','2026-03-30 09:40:37',''),
(40,NULL,'France Roy','1184','France Roy','franceroy.tphl26@gmail.com','','01335258303','','','','','','',1,13,'1999-01-01','Gulshan','2026-01-01',17000,8500,'','',1,'$2y$10$mUUFoDw1AKsBakNt8MbosunF.OhpBBvTK0x0gaK/JCJFM65GpNyIy','','','','','','','permanent','active','',4,NULL,'2026-03-30 09:41:04','2026-03-30 09:41:04',''),
(41,NULL,'Md. Anwar Parvez','1181','Md. Anwar Parvez','anwar.tphl@gmail.com','','01335258305','01879474485','','','','','6004033616',1,13,'1999-01-25','Ati Model Town (Site Office)','2026-02-05',17000,8500,'MD ANWAR PARVEZ','1041260066781',1,'$2y$10$nf10I.VE0dkfoIo4jVnRvuWOu7WNqfn8gryV8eDSoFzJpfRwhNu0y','EBL','','','','','','permanent','active','',4,NULL,'2026-03-30 09:44:21','2026-03-30 09:44:21',''),
(42,NULL,'Nafisa Anjum','1180','Nafisa Anjum','nafisaanjum.tphl@gmail.com','','01335258319','01724038041','','','Ilisha Road, Ward No-1, Bhola Pourashava.\r\nBhola Sadar, District- Bhola.','','8727836291',1,23,'2003-04-26','Ashulia Site Office','2026-02-05',5000,2500,'','',1,'$2y$10$1Z5wtTHRwfXuYTG.6fg9Re/mZDPzS9RXGGiw88mkc.zbS0p/Udxdq','','','','','','','permanent','active','',4,NULL,'2026-03-30 09:48:38','2026-03-30 09:48:38',''),
(43,NULL,'Md.Minhazul Islam Sumon','1126','Md. Minhazul Islam Sumon','sumon.tphl@gmail.com','Md. Dawoud hossen','01607037251','01797482215','','','Datta para, birulia,Savar,Dhaka','','6445593269',1,20,'1998-08-21','Site Office','2026-01-11',17000,8500,'','',1,'$2y$10$q2nYfG02WCa.ctQ.1tXrP.OnXUph7SqkK5hNh7UXOQJ5Id2GWFE6i','','','','','','','permanent','inactive','',4,NULL,'2026-03-30 09:56:21','2026-03-30 09:56:21',''),
(44,NULL,'Md Tarekul Islam','1169','Md Tarekul Islam','tarekul.tphl@gmail.com','','01335258316','01768856806','','','Kalai, Joypurhat','','7811570162',1,16,'2001-01-01','Ati Model Town (Site Office)','2026-02-01',8000,4000,'MD. TAREKUL ISLAM','1041260067498',1,'$2y$10$8y9fMWFX.KAphn.dH8jCY.6pZjZouf8dT41BBDXndK5Z/Dbp/3/ym','EBL','','','','','','permanent','active','',4,NULL,'2026-03-30 09:59:28','2026-03-30 09:59:28',''),
(45,NULL,'Kazi Jahid Hasan','1127','Kazi Jahid Hasan','jahid.tphl@gmail.com','Kazi Lutfur Rahaman','01335258339','01746601981','','','Shah Ali Thana, Mirpur 1, Dhaka, 1229','','4218961284',1,23,'2002-01-05','ATI Model Town (Site Office)','2026-01-11',8000,4000,'KAZI JAHID HASAN','1041260067514',1,'$2y$10$edIQfajR6szw6CFBKQeqeuzdqCTHTugjgYc6nKaPpe6r8sBBPNOzS','EBL','Gulshan- 2','','','','','contract','active','',4,NULL,'2026-03-30 10:03:44','2026-03-30 10:03:44',''),
(46,NULL,'H M Nazmul Alam','1167','H M Nazmul Alam','nazmulshakib.tphl@gmail.com','','01335258313','01625886520','','','Tulashar, Shilpakala Academy, Aminbag, Shariatpur Sadar','','511-057-9504',1,15,'2001-07-13','Corporate Office','2026-02-01',17000,8500,'H M NAZMUL ALAM','1041260066850',1,'$2y$10$NDit2rtUrhTgQTGUxo3MWekt9mKvlxDSdxM9Od6WZXaO/j2mRijiC','EBL','','','','','','permanent','active','',4,NULL,'2026-03-30 10:05:23','2026-03-30 10:05:23',''),
(47,NULL,'Mst. Tamanna Aktar Meghna','1131','Mst. Tamanna Aktar Meghna','tamanna.tphl@gmail.com','Md. Mostafizur Rahman','01958253348','01718166746','','','Khagan, Birulia, savar, 1340','','7365082259',1,23,'2026-11-17','Site Office','2026-01-15',8000,4000,'','',1,'$2y$10$cUXu.yhPyUZ9chJqTTKUoeCjsqWbkT5cXuYmajMl7BvspSJTGjyCm','EBL','Gulshan- 2','','','','','contract','active','',4,NULL,'2026-03-30 10:07:57','2026-03-30 10:07:57',''),
(48,NULL,'Md. Rayhan Kabir Roni','1161','Md. Rayhan Kabir Roni','rayhan.tphl@gmail.com','','01335-25832','01829-06823','','','Palashbari,Gaibandha','','6023825901',1,15,'2001-12-10','Corporate Office','2026-01-26',17000,8500,'MD RAYHAN KABIR RONI','1041260067208',1,'$2y$10$bQe5ZKp8XZZFXIKrbP0k1OVW2pMOWZcmp7SrtKjpzp7IrtmLYqCYC','EBL','','','','','','permanent','active','',4,NULL,'2026-03-30 10:09:50','2026-03-30 10:09:50',''),
(49,NULL,'Sayed Al Shahab','1160','Sayed Al Shahab','Assayed1937@gamail.com','','01335258322','01611847945','','','\" Village/Street: Rouha, Post Office:Niamatpur - 2310, Karimganj, Kishoreganj\"','','1030789414',1,15,'2000-02-18','Ati Model Town (Site Office)','2026-01-26',17000,8500,'SAYED AL SAHAB','1041260067558',1,'$2y$10$yCMUC7MWGJ4sNZ/xdcCjB.4cvKf5J2cf9ecvp3fnxTWz1azqF29yC','EBL','','','','','','permanent','active','',4,NULL,'2026-03-30 10:16:52','2026-03-30 10:16:52',''),
(50,NULL,'Rafiqul Islam','1132','Rafiqul Islam','rafiqul.tphl@gmail.com','Khayer','01958253345','01766919594','','','Khalpar,Tarabo,Rupganj,Narayanganj, post code:1460','','5563671634',1,20,'2000-09-01','ATI Model Town (Site Office)','2026-01-15',17000,8500,'RAFIQUL ISLAM','1041260065224',1,'$2y$10$KCfFJ1KFGvHUdeIGaii76.3ifuPZfLldfH/iXtA51iqACzGwMAbEa','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 10:20:00','2026-03-30 10:20:00',''),
(51,NULL,'Md. Mahabubur Rahman Mehedee','1158','Md. Mahabubur Rahman Mehedee','mehedee.tphl@gmail.com','','01335258307','01922674249','','','','','4665275352',1,21,'2001-11-07','Ati Model Town (Site Office)','2026-01-25',24000,12000,'MD. MAHABUBUR RAHMAN MEHEDEE','1041260068388',1,'$2y$10$zskY9SmTBZ/WkjwTWR.zTOH/jONrfSbrC/rmGXqXrPf1RJsjJY1Ge','EBL','','','','','','permanent','active','',4,NULL,'2026-03-30 10:24:57','2026-03-30 10:24:57',''),
(52,NULL,'Md. Sakib Al Hasan','1133','Md. Sakib Al Hasan','sakibhasan.tphl@gmail.com','Abdul Gaffar','01335258325','01993241381','','','Changone, Ahulia, Dhaka 1341','','8268549592',1,17,'2002-09-20','ATI Model Town (Site Office)','2026-01-17',24000,12000,'MD SAKIB AL HASAN','1041260065202',1,'$2y$10$cIING7bVqG7T6LRoDUJhyOIiBVKghhilZyp59OoNaZljMARRnBk7y','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 10:25:00','2026-03-30 10:25:00',''),
(53,NULL,'Md Asim Akram','1156','Md Asim Akram','akram.tphl@gmail.com','','01335258308','01737896035','','','','','2412933208',1,15,'2001-11-26','Ati Model Town (Site Office)','2026-01-24',17000,8500,'MD. ASIM AKRAM','1041260066839',1,'$2y$10$bR96JB1JNRBpe2mOX2.OZedyfGx4X710sgklzknJE8cw1GekBB6Xy','EBL','','','','','','permanent','active','',4,NULL,'2026-03-30 10:31:00','2026-03-30 10:31:00',''),
(54,NULL,'Rakib Hasan Rubai','1135','Rakib Hasan Rubai','rubai.tphl@gmail.com','Abdul Gaffar','01335258309','01721289043','','','Nobabgonj Bazar, Lalbagh , Dhaka -1205','','8268549592',1,26,'2003-11-28','ATI Model Town (Site Office)','2026-01-17',17000,8500,'RAKIB HASAN RUBAI','1041260065246',1,'$2y$10$K9UIWPodonWCg0Nfg7ti/.53KhSCQbw52Uw0KQEB3tM4z46JXz60m','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 10:31:59','2026-03-30 10:31:59',''),
(55,NULL,'Md. Rawnak Hassan Seyam','1154','Md. Rawnak Hassan Seyam','rawnak.tphl@gmail.com','','01335-25832','01719141196','','','','','2414491106',1,15,'2001-11-19','Corporate Office','2026-01-22',17000,8500,'MD. RAWNAK HASSAN SEYAM','1041260066768',1,'$2y$10$Rcurnj2w71MPIBLZ5zT3Ke754CHQUQlCREszsc6kFlGOI6H59WugG','EBL','','','','','','permanent','active','',4,NULL,'2026-03-30 10:35:13','2026-03-30 10:35:13',''),
(56,NULL,'Zannatul Ferdous','1136','Zannatul Ferdous','zannatul.tphl@gmail.com','Toaz uddin','01335258315','01674201198','','','Ghoshbagh,Ashulia, Dhaka','','4672259688',1,20,'2000-12-02','Site Office','2025-12-28',17000,85000,'ZANNATUL FERDOUS','1041260065235',1,'$2y$10$a8Z3vRtCJwTssChroQmP8.b9tvSrS0EaG.fb525TLXTcfz0JH.T32','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 10:35:17','2026-03-30 10:35:17',''),
(57,NULL,'Mst. Mahzabin Reza','1139','MST. MAHZABIN REZA','mahzabin.tphl@gmail.com','MD Rasel Reza','01335258311','01926865061','','','','','5114927907',1,23,'2001-09-25','ATI Model Town (Site Office)','2026-01-20',8000,4000,'MST. MAHZABIN REZA','1041260067489',1,'$2y$10$AjaQjDS0XRUJJyQlkmwfqe6eY/FkrXSgS6J5OILgtdTF4ta.dK9ga','EBL','Gulshan- 2','','','','','contract','active','',4,NULL,'2026-03-30 10:40:34','2026-03-30 10:40:34',''),
(58,NULL,'Md. Ali Haider Abdullah','1153','Md. Ali Haider Abdullah','tphl.alihaider@gmail.com','','0 1958-2533','01837234709','','','','','1028428793',1,17,'1999-09-29','Ashulia Site Office','2026-01-22',25000,12500,'','',1,'$2y$10$wVPZJu/YTJjJPVZIpz8YdeWRAszLKM8JqY9tpCtjNutUgq8/gY4vq','','','','','','','permanent','active','',4,NULL,'2026-03-30 10:41:19','2026-03-30 10:41:19',''),
(59,NULL,'Tanvir Hossain Jian','1140','Tanvir Hossain Jian','tanvir.tphl@gmail.com','Alomgir Hossain','01335258314','01712231068','','','Uttor badda, Abdullah bag masjid','','4205166475',1,20,'1999-10-13','Corporate Office','2026-01-20',17000,8500,'Md.Tanvir Hossain','1161260056064',1,'$2y$10$PT5Uca3qXiPMcZc76OZPb.G9qWf0lKf3qIMmb.B49eZVvByRHe0ly','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 10:44:55','2026-03-30 10:44:55',''),
(60,NULL,'Ahnaf Adil Roxy','1151','Ahnaf Adil Roxy','roxy.tphl@gmail.com','','01335-25832','01816729135','','','','','4213421763',1,13,'2002-09-20','Corporate Office','2026-01-20',17000,8500,'AHNAF ADIL ROXY','1041260066872',1,'$2y$10$v0ZeRyQOrjRMflJjY1out.mC75ZbRpYS47E6okHQJ1QvcyCBwA9zy','EBL','','','','','','permanent','active','',4,NULL,'2026-03-30 10:45:59','2026-03-30 10:45:59',''),
(61,NULL,'Md.Tamim','1143','Md.Tamim','tamim.tphl@gmail.com','Md.Mizanur Rahman','01308212749','01410800069','','','Dattapara, Birulia,Savar, Dhaka 1216','','',1,23,'2001-01-19','Site Office','2025-12-29',5000,2500,'','',1,'$2y$10$iIMh11dO/cPEeOTABrcB/eec3cs0eA1lQ3arTL6KuVUb7Ivt9P2Ce','','','','','','','contract','active','',4,NULL,'2026-03-30 10:49:04','2026-03-30 10:49:04',''),
(62,NULL,'Md. Abul Hasan Hasib','1150','Md. Abul Hasan Hasib','hasib.tphl@gmail.com','','01335258320','','','','','','4654241225',1,21,'1999-05-11','Ati Model Town (Site Office)','2026-01-20',25000,12500,'MD. ABUL HASAN HASIB','1041260067569',0,'','EBL','','','','','','permanent','active','',4,NULL,'2026-03-30 10:49:23','2026-03-30 10:49:23',''),
(63,NULL,'Akhoy kumer Nibir Roy','1149','Akhoy kumer Nibir Roy','akhoy.tphl@gmail.com','','01335258301','01720673414','','','','','6913904170',1,15,'2001-09-13','Corporate Office','2026-01-20',17000,8500,'AKHOY KUMER NIBIR ROY','1041260067423',1,'$2y$10$M7PNzLRweWOUZUWd6iD/oeAYsF7HSGappmPdqY7wvG42BAw2GAduq','EBL','','','','','','permanent','active','',4,NULL,'2026-03-30 10:54:37','2026-03-30 10:54:37',''),
(64,NULL,'Md. Amanullah Aman','1145','Md. Amanullah Aman','aman.tphl@gmail.com','MD. ASHRAF ALI','01335258306','01711129544','','','South Bhandaria-8550, Bhandaria, Pirojpur','','6010834478',1,20,'1999-01-22','Site Office','2026-01-01',17000,8500,'MD. AMANULLAH AMAN','1041260067547',1,'$2y$10$PBHeLM63oT63W5iSr14tHuJVpPZHSsrY29OOD.dUHFvvq2E62oVzC','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 10:56:23','2026-03-30 10:56:23',''),
(65,NULL,'Abu Naym','1146','Abu Naym','naymtphl@gmail.com','MD.Dulal Chaprasy','01335258312','01712343549','','','Ati-1312, Keraniganj, Dhaka','','4204089967',1,20,'1999-06-28','ATI Model Town (Site Office)','2026-01-20',17000,8500,'ABU NAYM','1041260068038',1,'$2y$10$xoRCNgUALmHcwCio.1FhMOgV.J/N7bbKSyABX.hQxJst1f3qprA6u','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 10:59:42','2026-03-30 10:59:42',''),
(66,NULL,'Md. Sakib Inam','1148','Md. Sakib Inam','sakibinam.tphl@gmail.com','','01335258310','01772441727','','','','','1962602841',1,16,'2003-01-22','Ati Model Town (Site Office)','2026-01-20',5000,2500,'','',1,'$2y$10$6Wu0Y3LRAw4NpoRsrcAFlOK5Gg36RlUcw/fUFzR40dyNAW2ecGQnq','','','','','','','permanent','active','',4,NULL,'2026-03-30 11:00:09','2026-03-30 11:00:09',''),
(67,NULL,'Md Bakhtiar Hasan','1147','Md Bakhtiar Hasan','bakhtiar.tphl@gmail.com','','01335258300','01736627147','','','','','4602140743',1,13,'1996-12-01','Ati Model Town (Site Office)','2026-01-20',17000,8500,'','',1,'$2y$10$n85C5ClROdqgoLGgj9/J1O7bsgZR4ZCQwAniy0vQFkSHai8NPLHXG','','','','','','','permanent','active','',4,NULL,'2026-03-30 11:10:48','2026-03-30 11:10:48',''),
(68,NULL,'Md Lutfor Rahaman','1183','Md Lutfor Rahaman','lutfor.tphl@gmail.com','','01335258334','01913375972','','','Vill: Asapur, PO: Phancherkanda, Thana: Ghior, Dist: Manikganj, Dhaka','','7350928797',3,27,'1990-11-11','Ashulia Site Office','2026-02-20',40000,20000,'MD. LUTFOR RAHAMAN','1041260066770',1,'$2y$10$/pf0DzZLyuTXUl9vY3q0fuzaeIZ2F.gwO6iQjFGAaJ560oavPPyky','EBL','','','','','','permanent','active','',4,NULL,'2026-03-30 11:17:42','2026-03-30 11:17:42',''),
(69,NULL,'Dipto Kumar Roy','1021','Dipto Kumar Roy','dipto.tphl@gmail.com','Dulal Chandra Roy','01958253358','01765904892','','','Khagan,Ashulia,Saver Dhaka','','2851653333',3,20,'1996-04-14','Site Office','2025-09-18',17000,8500,'DIPTO KUMAR ROY','1041260063670',1,'$2y$10$haij5JtC8w5RdSd1GW60G.QfOtOp0hPe4JsVQMuvnD3.VIjwEWT.i','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 11:19:42','2026-03-30 11:19:42',''),
(70,NULL,'B. M Shaharear Hasan','1170','B. M Shaharear Hasan','shaharear.tphl@gmail.com','','01917331397','01914206266','','','Korotoa road, latifpur kolony, Bogra','','7318135568',3,27,'1994-01-01','Ashulia Site Office','2026-01-01',45000,22500,'B.M SHAHAREAR HASAN','1041260066883',1,'$2y$10$0B.avL0U76y5XCeZLE4I8eEJyJwf5jiaL/k/esXDofYhpT08NnrAy','EBL','','','','','','permanent','active','',4,NULL,'2026-03-30 11:22:17','2026-03-30 11:26:33',''),
(71,NULL,'MD. Sakhawat Hossain Emon','1122','MD. Sakhawat Hossain Emon','emon.tphl@gmail.com','Md. Ismail Hossain Emon','01335258335','01314010972','','','Block-A, Lane-6, Mirpur-6, Dhaka 1216','','1962405062',3,23,'2001-05-28','Site Office','2026-01-04',8000,4000,'','',1,'$2y$10$yRioRoy7IFtTpdLAKr6QFuTsqng0pIdDnYycVcXxZ6r1CF5juG45W','','','','','','','contract','active','',4,NULL,'2026-03-30 11:22:57','2026-03-30 11:22:57',''),
(72,NULL,'MD. Zamiul Islam','1166','MD. Zamiul Islam','zamiul.tphl@gmail.com','','01335258338','01822650468','','','130/6/A Baganbari,  Madatek, Basabo, Dhaka -1214.','','5086466165',3,17,'1993-11-09','Ashulia Site Office','2026-02-01',28000,14000,'MD. JAMIUL ISLAM','1041260066894',1,'$2y$10$RbSxKjCb/hYfFm37QJHu7.6r5Ycfo74lefP1sQvrboL9mlFPacGE6','EBL','','','','','','permanent','active','',4,NULL,'2026-03-30 11:25:48','2026-03-30 11:25:48',''),
(73,NULL,'MD. Ahadul Islam','1155','MD. Ahadul Islam','asif.tphl@gmail.com','MD Nurul Islam','01314010972','01761-69676','','','Amula Dahabharatta, Nikrail, Bhuapur.','','3314775515',3,23,'2002-01-01','Site Office','2026-01-20',5000,2500,'','',1,'$2y$10$8McF5NcYB.R/29fcKKiRZeGrtjih5dLNLjBGu4LXeHUAsIz1BQe8G','','','','','','','contract','active','',4,NULL,'2026-03-30 11:26:09','2026-03-30 11:26:09',''),
(74,NULL,'Sadman Islam Sakib','1061','Sadman Islam Sakib','sakib.tphl@gmail.com','Saiful Islam','01958253344','01912102775','','','H-12, Kusumbag Residential Area, North Bisil  Road, Mirpur-1,Dhaka-1216','','7817318327',1,21,'2025-02-25','Corporate Office','2025-09-01',24000,12000,'S.M Sadman Islam Sakib','1041260062827',1,'$2y$10$EoGOv9SYmo5nySrmVmKn7e8GXHkr4lfKnswOVruz5TcpD/PFjCxty','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 11:33:39','2026-03-30 11:33:39',''),(75,NULL,'Mahaananda Kumar Saha','1185','Mahaananda Kumar Saha','mahaanandakumarsaha@gmail.com','','01335258327','01765904892','','','Faridpur, sadarpur,hatkrishnapur','','5506251882',4,22,'1996-07-21','Ashulia Site Office','2026-02-05',16000,8000,'','',1,'$2y$10$AZDF78LnqVjp6.dGcENEYux6zhAmmSvwv2LyaNPjnIHEzncBt4ugS','','','','','','','permanent','active','',4,NULL,'2026-03-30 11:34:33','2026-03-30 11:34:33',''),
(76,91,'Rubina Islam','1113','Rubina Islam','rubinatphl49@gmail.com','','01958253349','','','','','','1900939719',4,15,'1992-01-01','Ashulia Site Office','2025-01-01',20000,10000,'RUBINA ISLAM','1041260065326',0,'','EBL','','','','','','permanent','active','',4,NULL,'2026-03-30 11:38:41','2026-03-30 11:44:43',''),
(79,NULL,'Moonmoon Akter Oishy','1074','Moonmoon Akter Oishy','oishytphl@gmail.com','MD. Mukter Hossain','01958253355','01403720777','','','Road No -1,  B - 45 Bank Town,  Savar','','1047316906',4,24,'2001-10-19','Site Office','2025-09-30',10000,5000,'MOONMOON AKTER OISHY','1041260063659',1,'$2y$10$wO3G5peIbD4GDSce1ENfX.lk6LgFzygmt09TCwXkn4gjnCrtlq6R2','EBL','Gulshan- 2','','','','','permanent','active','',4,NULL,'2026-03-30 11:43:09','2026-03-30 11:43:09',''),
(80,NULL,'Afrin Jahan Anika','1077','Afrin Jahan Anika','anika.tphl@gmail.com','','01958253335','01623273346','','','39/b senpara porbota mirpur 10','','1032936708',4,13,'2002-10-23','Ashulia Site Office','2025-10-03',17000,8500,'AFRIN JAHAN ANIKA','1041260062962',1,'$2y$10$QpRMiyOu97GfYgByNJg5feF7W8pjRyi0FJsby5n9KhGzmnTufIe1K','EBL','','','','','','permanent','active','',4,NULL,'2026-03-30 11:52:36','2026-03-30 11:52:36','');
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
  `rules_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `declearation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
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
  `flat_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `facing_site` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `price_value` decimal(12,2) DEFAULT NULL,
  `facilities` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `status` enum('available','booked','sold') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'available',
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
INSERT INTO `flats` VALUES (1,1,1200,3,1000000,3,2,2,1,1,1,'Unit A1','north',7000000.00,'Gym',3,'booked','2026-02-19 17:17:08',NULL,'2026-02-19 17:17:08',1),(2,1,1200,2,1200000,2,1,1,1,1,1,'Unit B1','South',5000000.00,'',2,'available','2026-02-21 13:20:06',NULL,'2026-02-21 13:20:06',0);
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
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
  `booking_status` enum('booked','cancelled','completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'booked',
  `created_by` int NOT NULL,
  `sales_by_id` int DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approval_status` enum('not_required','pending','approved','rejected','partial') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'not_required',
  `approved_amount` decimal(12,2) DEFAULT NULL,
  `service_fee_balance` float DEFAULT NULL,
  `landshare_status` enum('active','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `lead_id` (`lead_id`),
  KEY `project_id` (`project_id`),
  KEY `created_by` (`created_by`),
  KEY `flat_id` (`flat_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_bookings`
--

LOCK TABLES `lead_bookings` WRITE;
/*!40000 ALTER TABLE `lead_bookings` DISABLE KEYS */;
INSERT INTO `lead_bookings` VALUES (1,8,1,1,200000.00,0,'2026-03-29','completed',3,3,NULL,'2026-03-29 10:09:03','2026-03-29 10:09:03','not_required',NULL,NULL,'active');
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
  `follow_up_type` enum('call','visit','meeting','whatsapp','email') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('pending','completed','missed','canceled') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `discussion_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `next_follow_up_date` date DEFAULT NULL,
  `assigned_with` int DEFAULT NULL,
  `next_action` enum('site visit','booking','call_back','take_time') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
INSERT INTO `lead_follow_ups` VALUES (1,8,1,1,'2026-02-22','11:36:00','call','completed','Test','2026-02-23',3,NULL,3,NULL,'2026-02-23 05:36:49','2026-03-29 10:09:53');
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
  `payment_type` enum('booking_money','registration','emi','land_share') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `land_share_amount` decimal(12,2) DEFAULT '0.00',
  `payment_method` enum('cash','bank','online','cheque') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'cash',
  `payment_status` enum('paid','partially_paid','pending','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'paid',
  `payment_date` date NOT NULL,
  `emi_no` int DEFAULT NULL,
  `total_emi_no` int DEFAULT NULL,
  `transaction_ref` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_account_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cheque_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `service_fee` float DEFAULT NULL,
  `online_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_payment_booking` (`booking_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_payments`
--

LOCK TABLES `lead_payments` WRITE;
/*!40000 ALTER TABLE `lead_payments` DISABLE KEYS */;
INSERT INTO `lead_payments` VALUES (1,1,'booking_money',200000.00,0.00,'cash','paid','2026-03-29',NULL,0,'Jjjj','','','',NULL,'',3,'2026-03-29 10:09:53');
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
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
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
  `campaign_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `platform` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `which_apartment_size_are_you_interested_in` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `which_floor_are_you_for_shop` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `when_are_you_planning_to_buy` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `would_you_like_to_schedule_a_site_visit` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `when_is_the_best_time_to_contact_you` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `full_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `job_title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `company_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `call_status` enum('not_called','called','received','not_received') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'not_called',
  `call_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lead_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lead_type` enum('Hot','Warm','Cold') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `company` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `value_amount` decimal(15,2) DEFAULT NULL,
  `currency` enum('USD','EURO','RUBLE','POUND','BDT') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'BDT',
  `phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alt_phone_number` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `source_type_id` int DEFAULT NULL,
  `assigned_id` int DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `father_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mother_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `nid_or_passport` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `present_add_bn` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `permanent_add_bn` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `identity_type` enum('NID','Passport') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `permanent_address` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pref_location_id` int DEFAULT NULL,
  `designation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `project_id` int DEFAULT NULL,
  `pref_flat_size` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `communication_type` enum('WA Messaging','WA Conversion') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tags` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `visibility` enum('public','private','selected') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'public',
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'active',
  `last_lead_action` enum('new','booked','follow up','visited','cancel') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'new',
  `deleted_at` datetime DEFAULT NULL,
  `lead_quality` enum('Not Interested','Prospect','Pending') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `lead_image` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lead_name_bn` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `father_name_bn` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mother_name_bn` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `profession_bn` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nationality` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `religion` enum('Islam','Hinduism','Buddhism','Christianity','Other') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nominee_name` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nomi_father_name` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nomi_mother_name` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nomi_profession` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `relation_with_lead` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nomi_dob` date DEFAULT NULL,
  `nomi_nid_passport` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nomi_phone` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nomi_email` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nomi_present_add` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nomi_permanent_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nomi_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `residency_type` enum('local','expatriate') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'local',
  `team_id` int DEFAULT NULL,
  `country` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `preferred_contact_time` enum('morning','afternoon','evening','night') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
  `id` int NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `type` enum('appointment','joining','promotion','increment','experience','noc') COLLATE utf8mb4_general_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `body` longtext COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
  `letter_type` enum('appointment','joining','promotion','increment','experience','noc') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `template_id` int DEFAULT NULL,
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `letter_date` date NOT NULL,
  `ref_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `current_salary` decimal(10,2) DEFAULT NULL,
  `revised_salary` decimal(10,2) DEFAULT NULL,
  `new_designation` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `signatory_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `signatory_title` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cc_email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `send_to_email` tinyint(1) DEFAULT '0',
  `status` enum('draft','issued') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'draft',
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

--
-- Table structure for table `locations`
--

DROP TABLE IF EXISTS `locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `locations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
  `role_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `role_id` int unsigned DEFAULT NULL,
  `person_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','bank','cheque','online') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'cash',
  `payment_status` enum('paid','pending','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'paid',
  `transaction_ref` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_account_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cheque_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `online_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `role_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `role_id` int unsigned DEFAULT NULL,
  `person_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `paid_to` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Recipient of the payment',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','bank','cheque','online') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'cash',
  `payment_status` enum('paid','pending','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'paid',
  `transaction_ref` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_account_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cheque_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `online_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `category_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
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
  `role_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `person_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
-- Table structure for table `manpower_requisitions`
--

DROP TABLE IF EXISTS `manpower_requisitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `manpower_requisitions` (
  `id` int NOT NULL,
  `department_id` int NOT NULL,
  `department_head_id` int NOT NULL,
  `designation_id` int NOT NULL,
  `job_title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `no_of_vacancies` int NOT NULL,
  `job_experience` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `to_be_filled_before` date DEFAULT NULL,
  `age_limit` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `report_to` int DEFAULT NULL,
  `requirement_type` enum('project','replacement','new') COLLATE utf8mb4_general_ci NOT NULL,
  `project_id` int DEFAULT NULL,
  `replacement_reason` text COLLATE utf8mb4_general_ci,
  `job_description` text COLLATE utf8mb4_general_ci,
  `education_qualification` text COLLATE utf8mb4_general_ci,
  `special_skills` text COLLATE utf8mb4_general_ci,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `manpower_requisitions`
--

LOCK TABLES `manpower_requisitions` WRITE;
/*!40000 ALTER TABLE `manpower_requisitions` DISABLE KEYS */;
/*!40000 ALTER TABLE `manpower_requisitions` ENABLE KEYS */;
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
  `message` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cameraLink1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cameraLink2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `moneyReceiptLink` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `purchaseDetailsLink` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `status` enum('pending','approved','paid') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `payment_method` enum('bank','cash','mobile_banking') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'bank',
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
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
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
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `slug` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `location_id` int DEFAULT NULL,
  `community_id` int NOT NULL,
  `building_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total_share` int DEFAULT NULL,
  `unit_per_floor` int DEFAULT NULL,
  `floor_wise_unit_meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `types` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `front_road` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `price_range` enum('Under 50 Lac BDT','50 Lac - 1 Crore BDT','1 Crore - 2 Crore BDT','2 Crore - 5 Crore BDT','Above 5 Crore BDT') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hover_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tag` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `plot_size` float DEFAULT NULL,
  `plot_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `block_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `brochure_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `apartment_count` int DEFAULT NULL,
  `amenities_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `amenities_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `map_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `map_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `project_map_location` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `project_sale_status` enum('Sold Out','On Sale','Upcoming') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'On Sale',
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
  `reviewer_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `reviewer_role` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `project_id` int DEFAULT NULL,
  `reviewer_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `review_video_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rating` tinyint unsigned NOT NULL,
  `review_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `review_date` date DEFAULT NULL,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'active',
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
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (26,7,12,'2026-02-05 07:06:27','2026-02-05 07:06:27'),(27,7,13,'2026-02-05 07:06:27','2026-02-05 07:06:27'),(30,9,19,'0000-00-00 00:00:00','2026-02-19 16:13:53'),(31,6,10,'2026-02-08 12:01:16','2026-02-08 12:01:16'),(32,6,20,'2026-02-08 12:01:16','2026-02-08 12:01:16'),(33,6,13,'2026-02-08 12:01:16','2026-02-08 12:01:16'),(34,5,17,'2026-02-08 12:01:26','2026-02-08 12:01:26'),(35,5,20,'2026-02-08 12:01:26','2026-02-08 12:01:26'),(36,5,7,'2026-02-08 12:01:26','2026-02-08 12:01:26'),(37,5,13,'2026-02-08 12:01:26','2026-02-08 12:01:26'),(45,1,10,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(46,1,3,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(47,1,1,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(48,1,5,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(49,1,21,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(50,1,12,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(51,1,6,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(52,1,14,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(53,1,11,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(54,1,7,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(55,1,4,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(56,1,13,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(57,1,2,'2026-02-21 17:25:08','2026-02-21 17:25:08'),(58,3,10,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(59,3,3,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(60,3,1,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(61,3,5,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(62,3,21,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(63,3,12,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(64,3,6,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(65,3,14,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(66,3,8,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(67,3,11,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(68,3,7,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(69,3,4,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(70,3,13,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(71,3,2,'2026-02-21 17:25:21','2026-02-21 17:25:21'),(72,8,15,'2026-02-23 07:40:38','2026-02-23 07:40:38'),(73,8,20,'2026-02-23 07:40:38','2026-02-23 07:40:38'),(74,4,6,'2026-03-29 10:25:40','2026-03-29 10:25:40'),(75,4,20,'2026-03-29 10:25:40','2026-03-29 10:25:40'),(76,4,8,'2026-03-29 10:25:40','2026-03-29 10:25:40'),(77,4,13,'2026-03-29 10:25:40','2026-03-29 10:25:40');
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
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
  `website_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `website_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `footer_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `white_logo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dark_logo` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alt_phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alt_email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `copyright` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `facebook_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `messenger_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `instagram_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tiktok_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `x_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `youtube_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `header_code` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `body_code` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `client_declaration` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
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
  `visit_status` enum('scheduled','completed','cancelled','no_show') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'scheduled',
  `feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
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
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `subtitle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `start_date` date DEFAULT NULL,
  `deadline` datetime DEFAULT NULL,
  `employee_accept` tinyint(1) DEFAULT '0',
  `status` enum('pending','in_progress','completed','expired') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `progress` int DEFAULT '0',
  `priority` enum('low','medium','high','urgent') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'medium',
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
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
  `full_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `designation_id` int DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `nid` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role_id` int NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `photo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `media_link1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `media_link2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_logout` datetime DEFAULT CURRENT_TIMESTAMP,
  
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `team_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  KEY `fk_user_employee` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,NULL,'Farzana','farzana','01873244236','farz@gmail.com',1,'Dhaka',NULL,1,'$2y$10$tyGb6XsaCzqrB5/fmTtTEualGz9EEsCTS4F0mAz7b/ITjBISkKirS',NULL,NULL,NULL,'2026-03-30 09:32:43',NULL,'2026-02-22 06:47:08','2026-03-30 09:32:43',''),
(2,NULL,'Sojib','sojib','01798342755','sales@gmail.com',1,'Dhaka',NULL,5,'$2y$10$tyGb6XsaCzqrB5/fmTtTEualGz9EEsCTS4F0mAz7b/ITjBISkKirS',NULL,NULL,NULL,'2026-03-29 10:15:39',NULL,'2026-02-22 06:47:08','2026-03-29 10:15:39',''),
(3,1,'Fahim','Fahim','01790128471','fahim@gmail.com',1,'Mohammadpur, Dhaka-1207','23445',8,'$2y$10$Jy1aK9a5d9P7mA0TqHEkT.RS/7kPh0x/fxpmRaOR1JrLJXEV0ObsG',NULL,'','','2026-03-30 09:31:11',4,'2026-02-23 03:24:25','2026-03-30 09:31:11',''),
(4,1008,'Md. Jasim Uddin','Jasim Uddin','01762013007','hr@gmail.com',14,'House:01, Road:03, Block: B, Section:02, Mirpur, Dhaka-1216.','1903663282',4,'$2y$10$bhU084TG9E1hi2gpC3g4nODNLbG5lzKoo7vj/Uv6KeYWXtzduE0Ry',NULL,'','','2026-03-30 09:41:13',4,'2026-02-23 05:44:19','2026-03-30 12:13:58',''),
(5,0,'Mr. Jisan','Mr. Jisan','01390128223','accounts@gmail.com',3,'Mohammadpur, Dhaka-1207','2345676',6,'$2y$10$cIIGEotRMUt4NdR3BelTA.wRCL7CDcC.s.1UY0ncO7lbttcKda2C6',NULL,'','','2026-03-29 10:31:09',6,'2026-02-23 06:11:06','2026-03-29 10:31:09',''),
(6,NULL,'Management','Management','01394378234','management@gmail.com',1,'dhaka','32453',3,'$2y$10$BNGc9RJClg5hlb4tqTasqebYBq3PmZCLGw4yiWjiPN.Gh89QF9JoW',NULL,'','','2026-03-29 10:25:44',1,'2026-03-29 03:44:44','2026-03-29 10:25:44',''),
(7,NULL,'CED','CED','01872364346','ced@gmail.com',1,'Dhaka','823445',7,'$2y$10$a51psvUGsp/WsSE5cY2xNuKpuKSNoG/QZNNxxfWs0I.eSPM1KPAX.',NULL,'','','2026-03-29 04:01:38',6,'2026-03-29 04:01:38','2026-03-29 04:01:38',''),
(8,NULL,'Sales Team Head','Sales Head','01327488234','sales-head@gmail.com',1,'Dhaka','09231943',9,'$2y$10$Hr8gjcm14bLK/Ns6ws/pt.jz0PA5ObSc.jo4Z7epS23UsEkVDUXn.',NULL,'','','2026-03-29 10:37:00',1,'2026-03-29 04:16:47','2026-03-29 10:37:00',''),
(9,3,'Md. rafi','admin_39_','01234567890','rafi@easytechx.com',9,'ADDL Tower, Dhanmondi 27/A','',8,'$2y$10$nId2sBtusb0qFr2136IMrOZVDVkHq8RTjbsJIzgZ8GpaDXDTMhhHG',NULL,'','','2026-03-29 10:24:18',0,'2026-03-29 10:23:39','2026-03-29 10:24:18',''),
(11,5,'Bornali Haque','bornali','01335258340','bornalihaque.tphl@gmail.com',3,'RUAP, Uttara 18, Dhaka','',4,'$2y$10$R9YSZd3L1v262q20VEtdG.dyT9xPRWmHFtjeTvwao5i8i24T5Hpm.',NULL,'','','2026-03-29 10:31:45',0,'2026-03-29 10:31:45','2026-03-29 10:31:45',''),
(12,8,'Sanjana Hossain Moure','sanjana moure','01901310848','moure.tphl@gmail.com',15,'Mirpur-2 water tank, Block - F, road -3, Dhaka -1216','',8,'$2y$10$2J2ciVq5whQHb80.zq0zYuQcH.eb0B3pagwhnmx9TEsy.elK3J1ry',NULL,'','','2026-03-30 05:15:10',0,'2026-03-30 05:15:10','2026-03-30 05:15:10',''),
(13,9,'Md. Rabbi Hossain','rabbi hossain','0 1901-3108','rabbihossain.tphl@gmail.com',20,'Baro Bazar, Ashulia Model Town, Savar, Dhaka.','',0,'$2y$10$J8kru0oQ5ejljqcWLEOJZe76keE/DV.K03Su3ics.G8vASjgJaCpi',NULL,'','','2026-03-30 05:15:44',0,'2026-03-30 05:15:44','2026-03-30 05:15:44',''),
(14,7,'Saleh Ahamed','saleh ahmed','0 1901-3650','saleh.tphl@gmail.com',21,'','',0,'$2y$10$fsUbevYAvi9OQguKgaiyEOzj/63NgEbIFqoqshjTVDdjkBrNhQ.Sq',NULL,'','','2026-03-30 05:18:44',0,'2026-03-30 05:18:44','2026-03-30 05:18:44',''),
(15,6,'Md. Kowsar Ahammed','kowsar ahammed','01901310845','kawsar.tphl@gmail.com',21,'Vill: Bhubangati, Post: Biswarigacha, Thana: Dhunat, Zilla: Bogura','',0,'$2y$10$zLe.p.NR/m2yuzovc3hSsO7UbYYNiY4wreK0kSFksNG7pUL0KbMa.',NULL,'','','2026-03-30 05:19:07',0,'2026-03-30 05:19:07','2026-03-30 05:19:07',''),
(16,0,'Ashfaque Bhuiyan Omi','Ashfaque Omi','01958253303','ashfaqueomi.tphl25@gmail.com',20,'Darul Aman Housing, Mirpur-2, Dhaka - 1206','',2,'$2y$10$29JSW4M8FbD3VNZnoUD8AOXB5LRrqo7vS22WMNfWezI11UlsObVCa',NULL,'','','2026-03-30 05:19:52',0,'2026-03-30 05:19:52','2026-03-30 11:58:30',''),
(18,10,'Mehnaz Ahmed','mehnaz ahmed','0 1901-3650','mehnaz.tphl@gmail.com',20,'South chayabithi, Joydebpur, Gazipur','9160336244',8,'$2y$10$edszxcW7aAhMpXKVGUIZZePVtP.RhKwPuC8vA2baFfd8jmrYS8mEG',NULL,'','','2026-03-30 05:26:05',0,'2026-03-30 05:26:05','2026-03-30 05:26:05',''),
(19,11,'Mr. Ibrahim Khalil','ibrahim khalil','01958253319','ibrahim.tphl@gmail.com',20,'5/9, Dakkhin Rajashon, Savar, Dhaka','2805006299',8,'$2y$10$iSTZaE4q9PCH.qM.53lFp.4EuEh2KoQKQRkD/q4SGrvV9Un84T1Pa',NULL,'','','2026-03-30 05:30:21',0,'2026-03-30 05:30:21','2026-03-30 05:30:21',''),
(20,12,'Shammi Akther','shammi akhter','0 1901-3108','shammi.tphl@gmail.com',20,'13 no sector ,uttara.','6923766643',5,'$2y$10$bOb3g/W82j7pq3Jk9s520uIK1KOulFlbRWN4NVT55EC/1NxigzSJm',NULL,'','','2026-03-30 05:35:10',0,'2026-03-30 05:35:10','2026-03-30 05:35:10',''),
(21,13,'Rejwan Ahmed','rejwan ahmad','01901365040','rejwantphl@gmail.com',21,'Sreemangal, Moulvibazar.','4660653587',5,'$2y$10$kQcMOXqxvt92qxB0nnSFtexGzH.iIcEQMC66.kOAasjnr943R9lBG',NULL,'','','2026-03-30 05:40:37',0,'2026-03-30 05:40:37','2026-03-30 05:40:37',''),
(22,14,'MD. Feroz Ahamed','feroz ahamed','01958253327','feroz.tphl@gmail.com',20,'Savar, Dhaka','3761348154',8,'$2y$10$7hG94FPqPG5HzIWpCm/8QufyocDjtDn.onUagAcqBZEMNRvMX6Z/e',NULL,'','','2026-03-30 05:47:14',0,'2026-03-30 05:47:14','2026-03-30 05:47:14',''),
(23,15,'Mojammel Haque','mojammel haque','01958253324','mojammel.tphl@gmail.com',21,'B-4, G-1, Sher-E-Bangla Nagar Staff Quarter, Dhaka-1207','865 729 7449',5,'$2y$10$.IhiGn25wWUqKu9YuC38xeNy6hpeAwnWuiGLT0fK7SuhS.40f8FS6',NULL,'','','2026-03-30 05:51:15',0,'2026-03-30 05:51:15','2026-03-30 05:51:15',''),
(24,16,'Mehedi Hasan Shuvo','hasan shuvo','01901365048','shuvo.tphl@gmail.com',26,'','',0,'$2y$10$tZXN9MJD2Gvg0/yDAw1chuU1qYmoivutQfpNYdIEWIDLHqQKoaEHq',NULL,'','','2026-03-30 05:59:32',0,'2026-03-30 05:59:32','2026-03-30 05:59:32',''),
(25,17,'Rakib Al Hasan','rakibal hasan','01958253321','rakib.tphl@gmail.com',20,'Daffodil smart city, Birulia savar Dhaka','4654750571',5,'$2y$10$yBQjX3SvmWQXO2cA.ol4B.zPmjIqbyUiqysRN7HTZfXLlo4Z95qfq',NULL,'','','2026-03-30 06:43:56',0,'2026-03-30 06:43:56','2026-03-30 06:43:56',''),
(26,18,'Md. Nazmul Khan Shihab','khan Shihab','01958253326','shihab.tphl@gmail.com',20,'2450/11,East Dandabor, Palashbari, Savar cantonment -1344, Savar, Dhaka','1968492643',8,'$2y$10$b/geGkDCcdjnJY4j931Ia.YyXu1/FBrONUVr8jCr9csPFZXJ8BYMi',NULL,'','','2026-03-30 06:50:40',0,'2026-03-30 06:50:40','2026-03-30 06:50:40',''),
(27,19,'Munshi Aiman Kabir','aiman kabir','01958253318','aiman.tphl@gmail.com',20,'Savar, Dhaka','6915659434',8,'$2y$10$lxze0zOCDKCiYZanahnfO.8tgO6v4MZiK8OBHsMxiXr7/4qH3s2FO',NULL,'','','2026-03-30 07:01:11',0,'2026-03-30 07:01:11','2026-03-30 07:01:11',''),
(28,20,'Md. Tariqul Islam Illion','tariqul islam','0 1958-2533','illion.tphl@gmail.com',20,'Ashulia Model Town, Khagan Bazar, Birulia, Savar, Dhaka','1968356707',8,'$2y$10$Sj/S3IIUnt4EttdRVs/lXOCGvn9i/funVCadYEXd0ENgLdmEXSZF6',NULL,'','','2026-03-30 07:06:02',0,'2026-03-30 07:06:02','2026-03-30 07:06:02',''),
(29,21,'Shafia Afrin Shahana','Shafia Afrin Shahana','01335258332','shafia.tphl@gmail.com',15,'Apon Bazar Samiti Market, Rayerbag, Jatrabari, Dhaka','1954806822',2,'$2y$10$jg9yoH8rHsH2A7wrOb6D8urj1iUv889LWfvhL3dU95RDg8t7HIYpK',NULL,'','','2026-03-30 07:56:24',0,'2026-03-30 07:56:24','2026-03-30 07:56:24',''),
(30,22,'Abir Ahmed','Abir Ahmed','01335258328','abir0.tphl@gmail.com',15,'Poshu Hospital Para, Magura Sadar,Magura','8703180417',8,'$2y$10$IU8I8sJvyYl06UUHgy8XHuzHv66J7mAfVdPACHogEpVjTZ8Wuq2bG',NULL,'','','2026-03-30 08:29:14',0,'2026-03-30 08:29:14','2026-03-30 08:29:14',''),
(31,23,'Shanawaz Khan','Shanawaz Khan','01335258330','shanawaz.tphl@gmail.com',15,'House-12,Road-4,Block-J,Banasree Meradia Khilgaon Dhaka 1219','5115227323',8,'$2y$10$Px7TN7veOADIqwYdPfISbOLfxqwEkSq9S1R8sTPWDUpAYRo4hb4uS',NULL,'','','2026-03-30 08:42:17',0,'2026-03-30 08:42:17','2026-03-30 08:42:17',''),
(32,24,'Md. Saiful Islam Jony','saiful jony','01958253340','saif.thpl@gmail.com',23,'104/A/9 Buddhibi Road,Mohammadpur,Dhaka-1207','5557674677',8,'$2y$10$iJlvDYNOYQ5.AFtGpin74uRVIvXn//mKEgfeHXBvVTXXvqLEteiJa',NULL,'','','2026-03-30 08:46:37',0,'2026-03-30 08:46:37','2026-03-30 08:46:37',''),(33,25,'Aranya Bhowmick Dhrubo','Aranya Bhowmick Dhrubo','01335258329','dhrubo.tphl@gmail.com',20,'15 Shah-Shaheb Lane Narinda, Dhaka','3315312474',8,'$2y$10$U/nrFg9Eyc.ss5Oi2gFWV.BgzE8NXmjGHyhD9aY2VLUaGv9dr8KwO',NULL,'','','2026-03-30 08:47:19',0,'2026-03-30 08:47:19','2026-03-30 08:47:19',''),
(34,26,'Tabassum Akter Soma','tabassum soma','01958253374','soma.tphl@gmail.com',23,'Ashulia Model Town, Savar, Dhaka','3758694214',8,'$2y$10$tfmvA.ksOc8S9hmYTwU5Xut8ERUg7YztJfCydw1f9.K4jRF.HKBom',NULL,'','','2026-03-30 08:50:26',0,'2026-03-30 08:50:26','2026-03-30 08:50:26',''),
(35,27,'SADAT HOSSAIN','SADAT HOSSAIN','01335258331','sadathossain11@gmail.com',15,'116, Jagannath Saha Road, Lalbagh, Dhaka-1211','2852429642',8,'$2y$10$cRtbvkGvyjShTPIsq0egPO4PYdXmFT3jJ5NzOBGAKeu823cA2CA7K',NULL,'','','2026-03-30 08:52:52',0,'2026-03-30 08:52:52','2026-03-30 08:52:52',''),
(36,28,'Md. Ajizul Islam Manik','ajizul manik','01958253338','manik.tphl@gmail.com',20,'1/B , Nondipara, Dhaka ,1219','3255221362',8,'$2y$10$NNOzDnndIUhK14eqejmpUOjoCepUEZJxkSPK6/PwQxFH3qwUsb5sC',NULL,'','','2026-03-30 08:56:43',0,'2026-03-30 08:56:43','2026-03-30 08:56:43',''),
(37,29,'Tasnur Aman Satu','aman satu','01958253363','satu.tphl@gmail.com',20,'kumkumari- Midha bari','7361471282',8,'$2y$10$B8RxH6k07S5uVYeOQdAySusGhyguSq1rwA5ZNa7DcFbo.kd7jwNCe',NULL,'','','2026-03-30 09:00:39',0,'2026-03-30 09:00:39','2026-03-30 09:00:39',''),
(38,30,'Md. Tasdikul Hasan Sikder','Md. Tasdikul Hasan Sikder','01335258317','tasdik.tphl@gmail.com',13,'523 East Kazipara, Dhaka, Mirpur-1216','2863855777',8,'$2y$10$hvyLcozNhFKHKyzSHob2LurUMAn1cka7WeuKy6354xGTxHOCEFMv.',NULL,'','','2026-03-30 09:03:57',0,'2026-03-30 09:03:57','2026-03-30 09:03:57',''),
(40,31,'Qazi Nadeem Javed','Qazi Nadeem Javed','‪0195825333','nadeem.tphl@gmail.com',25,'Sector 10 Road 7 uttara','2350020026',2,'$2y$10$HwTS4uTRRrfpsjH0iIssnO2q8R1aJb/JKRdGmr8vK3Od7JyC8RaU.',NULL,'','','2026-03-30 09:09:53',0,'2026-03-30 09:09:53','2026-03-30 09:09:53',''),
(41,32,'Nayeemur Rahman','Nayeemur Rahman','01335258318','nayeemtonmoytphl@gmail.com',15,'Road No. 4, F Block, Mirpur-2','1960680278',8,'$2y$10$vNpgHQwRP33k2ftwwnzuuO8mJSGtb2K6MSgQyyzBm5hlu1pxQKt0.',NULL,'','','2026-03-30 09:12:53',0,'2026-03-30 09:12:53','2026-03-30 09:12:53',''),
(42,33,'Mahin - Al Beruni','Mahin - Al Beruni','01958253328','tphl.mahin@gmail.com',26,'House 40/3, Varalipara, Boro Bangram, Sopura, Shah Mokhdum, Rajshahi - 6203','8710606925',2,'$2y$10$w0GwPk82B7c/775XmUq6V.XOJnByBk5qE3Ux5I6nJB.0m2d0NhGWW',NULL,'','','2026-03-30 09:16:00',0,'2026-03-30 09:16:00','2026-03-30 09:16:00',''),
(44,35,'Md. Momidul Islam','Md. Momidul Islam','01958253335','momidul.tphl@gmail.com',20,'Nolbata, Hatiandaha, Singra, Natore','6453089770',8,'$2y$10$735.t6axd8CKpzTxu40BE.kPj2RKdYQNoq1l293e1MpkZwCP62cUm',NULL,'','','2026-03-30 09:33:28',1,'2026-03-30 09:23:02','2026-03-30 09:33:28',''),
(48,36,'Rifat Ahsan','Rifat Ahsan','01958583350','rifat.tphl@gmail.com',20,'Khilkhet kha para','4213633714',8,'$2y$10$O3uQB5h/6KwsPWex8ESVzee39365moqUtNr9DIoqhxeJJM1JDLB4e',NULL,'','','2026-03-30 09:32:02',0,'2026-03-30 09:30:56','2026-03-30 09:32:02',''),
(51,38,'Sudipto Azad Hriday','Sudipto Azad Hriday','01901365046','sudiptoazad.tphl@gmail.com',17,'38, Road 3B, Mohammadia Housing Limited, Mohammadpur, Dhaka-1207','9151890663',8,'$2y$10$BMBC8TMV0h.nDrahyrmUO.dhGkDQwII8Ov.UT/Hg.JVRBA6wTMUY2',NULL,'','','2026-03-30 09:38:33',0,'2026-03-30 09:38:33','2026-03-30 09:38:33',''),
(53,39,'Al Mubin Shuvo','Al Mubin Shuvo','01335258302','almubintphl@gmail.com',15,'Kumarkhali,Kushtia','5564288271',8,'$2y$10$mXwL/qloJHAWt2lal/Y9HO4yO0sCjGHKGgr1/AckNXExlPEy1iaKS',NULL,'','','2026-03-30 09:40:37',0,'2026-03-30 09:40:37','2026-03-30 09:40:37',''),
(54,40,'France Roy','France Roy','01335258303','franceroy.tphl26@gmail.com',13,'','',8,'$2y$10$WhAVna/AwmbasRb18o4Qi.TaQytrObLPCqFQ8WcErTuhoAGj0qAf2',NULL,'','','2026-03-30 09:41:04',0,'2026-03-30 09:41:04','2026-03-30 09:41:04',''),
(55,41,'Md. Anwar Parvez','Md. Anwar Parvez','01335258305','anwar.tphl@gmail.com',13,'','6004033616',8,'$2y$10$nD6apWdkQA9A4pxQ2WK.TuXjrF./xCNaUOMtnxEXIhYBUEm6ec5v.',NULL,'','','2026-03-30 09:44:21',0,'2026-03-30 09:44:21','2026-03-30 09:44:21',''),
(56,42,'Nafisa Anjum','Nafisa Anjum','01335258319','nafisaanjum.tphl@gmail.com',23,'Ilisha Road, Ward No-1, Bhola Pourashava.\r\nBhola Sadar, District- Bhola.','8727836291',8,'$2y$10$Njcwsx0qybiXNWr6I4xU6OXrnNZ0Gez0PTeiI9mymsLEByvmFtFau',NULL,'','','2026-03-30 09:48:38',0,'2026-03-30 09:48:38','2026-03-30 09:48:38',''),
(57,43,'Md.Minhazul Islam Sumon','Md. Minhazul Islam Sumon','01607037251','sumon.tphl@gmail.com',20,'Datta para, birulia,Savar,Dhaka','6445593269',0,'$2y$10$Vxxg305sx5c9QTdS1nZe5Ow1VTzQGDXu6Vo101UVl3iRJTGWLHPmq',NULL,'','','2026-03-30 09:56:21',0,'2026-03-30 09:56:21','2026-03-30 09:56:21',''),
(58,44,'Md Tarekul Islam','Md Tarekul Islam','01335258316','tarekul.tphl@gmail.com',16,'Kalai, Joypurhat','7811570162',8,'$2y$10$FgfGch01R5MmOmV06QbSE.Ztcu6ohd1xekC57B9SwWwuMmnbs1KSq',NULL,'','','2026-03-30 09:59:28',0,'2026-03-30 09:59:28','2026-03-30 09:59:28',''),
(59,45,'Kazi Jahid Hasan','Kazi Jahid Hasan','01335258339','jahid.tphl@gmail.com',23,'Shah Ali Thana, Mirpur 1, Dhaka, 1229','4218961284',8,'$2y$10$o1c8bwaEWyTgxCs8OKnmy.155Q2E1XV.Y5Zt2/N.AwJB9HhD39PbC',NULL,'','','2026-03-30 10:03:44',0,'2026-03-30 10:03:44','2026-03-30 10:03:44',''),
(60,46,'H M Nazmul Alam','H M Nazmul Alam','01335258313','nazmulshakib.tphl@gmail.com',15,'Tulashar, Shilpakala Academy, Aminbag, Shariatpur Sadar','511-057-9504',8,'$2y$10$j8oSuPOI0BarG10VQLdlgu3XQLs9u4HXgl7l1S1GgnNFTSjDm5X2C',NULL,'','','2026-03-30 10:05:23',0,'2026-03-30 10:05:23','2026-03-30 10:05:23',''),
(61,47,'Mst. Tamanna Aktar Meghna','Mst. Tamanna Aktar Meghna','01958253348','tamanna.tphl@gmail.com',23,'Khagan, Birulia, savar, 1340','7365082259',8,'$2y$10$GAromZnC1DJDwjgu1qW4.eU0/aoDJbqvJPr3oyFS3HflvESXRJTwi',NULL,'','','2026-03-30 10:07:57',0,'2026-03-30 10:07:57','2026-03-30 10:07:57',''),
(62,48,'Md. Rayhan Kabir Roni','Md. Rayhan Kabir Roni','01335-25832','rayhan.tphl@gmail.com',15,'Palashbari,Gaibandha','6023825901',8,'$2y$10$nN8hAqbsVXHTLsh.zHew2.nqBcRmvzezxII7SAQl3DPS8vx9/0NHy',NULL,'','','2026-03-30 10:09:50',0,'2026-03-30 10:09:50','2026-03-30 10:09:50',''),
(63,49,'Sayed Al Shahab','Sayed Al Shahab','01335258322','Assayed1937@gamail.com',15,'\" Village/Street: Rouha, Post Office:Niamatpur - 2310, Karimganj, Kishoreganj\"','1030789414',8,'$2y$10$rv0RL4khHntFcChFrOYqI.kCHW6ydlwvqCzbfxm/YVRyMNo0OFrou',NULL,'','','2026-03-30 10:16:53',0,'2026-03-30 10:16:53','2026-03-30 10:16:53',''),
(64,50,'Rafiqul Islam','Rafiqul Islam','01958253345','rafiqul.tphl@gmail.com',20,'Khalpar,Tarabo,Rupganj,Narayanganj, post code:1460','5563671634',8,'$2y$10$2N5KDT5gZ0UCL6S8SeRxWuZ3fxTOOWUgw3LABYD4NHzmf06UsJGNS',NULL,'','','2026-03-30 10:20:00',0,'2026-03-30 10:20:00','2026-03-30 10:20:00',''),
(65,51,'Md. Mahabubur Rahman Mehedee','Md. Mahabubur Rahman Mehedee','01335258307','mehedee.tphl@gmail.com',21,'','4665275352',8,'$2y$10$8RKSqAv7Ir8wx2sEjdhPR.h.toEB4e5Xhoj1IGdstx2f89Hd/gKse',NULL,'','','2026-03-30 10:24:57',0,'2026-03-30 10:24:57','2026-03-30 10:24:57',''),
(66,52,'Md. Sakib Al Hasan','Md. Sakib Al Hasan','01335258325','sakibhasan.tphl@gmail.com',17,'Changone, Ahulia, Dhaka 1341','8268549592',8,'$2y$10$A/mGm65CzDyqbYojepkTpOWidQpnLF4HkAhx0vkylEh38peYQuX26',NULL,'','','2026-03-30 10:25:00',0,'2026-03-30 10:25:00','2026-03-30 10:25:00',''),
(67,53,'Md Asim Akram','Md Asim Akram','01335258308','akram.tphl@gmail.com',15,'','2412933208',8,'$2y$10$FYOiBtICro0TkZQIb4VfhOwX5a0nUwvvFVxi2oZD3gp6ZDK75ngoW',NULL,'','','2026-03-30 10:31:00',0,'2026-03-30 10:31:00','2026-03-30 10:31:00',''),
(68,54,'Rakib Hasan Rubai','Rakib Hasan Rubai','01335258309','rubai.tphl@gmail.com',26,'Nobabgonj Bazar, Lalbagh , Dhaka -1205','8268549592',0,'$2y$10$gGBH11phPzR08MAiTN7tee63vhPaYDyoHa3ANBGhuqJwgOKqRPM2e',NULL,'','','2026-03-30 10:31:59',0,'2026-03-30 10:31:59','2026-03-30 10:31:59',''),
(69,55,'Md. Rawnak Hassan Seyam','Md. Rawnak Hassan Seyam','01335-25832','rawnak.tphl@gmail.com',15,'','2414491106',8,'$2y$10$GKoc1DcZdbOmsLGSBPsJU.6NaH/06m1BMsff0OFZGr05BZYXR5BgS',NULL,'','','2026-03-30 10:35:13',0,'2026-03-30 10:35:13','2026-03-30 10:35:13',''),
(70,56,'Zannatul Ferdous','Zannatul Ferdous','01335258315','zannatul.tphl@gmail.com',20,'Ghoshbagh,Ashulia, Dhaka','4672259688',8,'$2y$10$N5VFRRAr6NPeO2sNHAv6hOhir6DidyOkV8l6C54kSifQZw/MVhYiG',NULL,'','','2026-03-30 10:35:17',0,'2026-03-30 10:35:17','2026-03-30 10:35:17',''),
(71,57,'Mst. Mahzabin Reza','MST. MAHZABIN REZA','01335258311','mahzabin.tphl@gmail.com',23,'','5114927907',8,'$2y$10$Y1Zl3QED4PDxFZhq9szMzOObIZ2wSz/pd0Dqy2e9zB8zJi4Yy4E4y',NULL,'','','2026-03-30 10:40:34',0,'2026-03-30 10:40:34','2026-03-30 10:40:34',''),
(72,58,'Md. Ali Haider Abdullah','Md. Ali Haider Abdullah','0 1958-2533','tphl.alihaider@gmail.com',17,'','1028428793',8,'$2y$10$/T5eDgeej3P.JoXuhChGceCnDHOceBwixNyIGehU4lHJ0xHtdqDFe',NULL,'','','2026-03-30 10:41:19',0,'2026-03-30 10:41:19','2026-03-30 10:41:19',''),
(73,59,'Tanvir Hossain Jian','Tanvir Hossain Jian','01335258314','tanvir.tphl@gmail.com',20,'Uttor badda, Abdullah bag masjid','4205166475',8,'$2y$10$8YxvgfxnkLTwgnksEVIeRu3dLINjXElLIbY6LUvy4.t6K3HzQ3b2a',NULL,'','','2026-03-30 10:44:55',0,'2026-03-30 10:44:55','2026-03-30 10:44:55',''),
(74,60,'Ahnaf Adil Roxy','Ahnaf Adil Roxy','01335-25832','roxy.tphl@gmail.com',13,'','4213421763',8,'$2y$10$EidwlET76JrkaxbXZWi1b.WHbW2IdKsfzevS5cbMDnLM3pJPk/arK',NULL,'','','2026-03-30 10:45:59',0,'2026-03-30 10:45:59','2026-03-30 10:45:59',''),
(75,61,'Md.Tamim','Md.Tamim','01308212749','tamim.tphl@gmail.com',23,'Dattapara, Birulia,Savar, Dhaka 1216','',8,'$2y$10$vP/q95cSabGHgVL9h3sBw.MWwc8JGYQiRXLarqEx6zl8p6A43uwk2',NULL,'','','2026-03-30 10:49:04',0,'2026-03-30 10:49:04','2026-03-30 10:49:04',''),
(76,63,'Akhoy kumer Nibir Roy','Akhoy kumer Nibir Roy','01335258301','akhoy.tphl@gmail.com',15,'','6913904170',8,'$2y$10$/A2yZtrb7UELabmGdPX.IuVuX/LcgmoxPoe0CV56vCoxJGAfGOUie',NULL,'','','2026-03-30 10:54:37',0,'2026-03-30 10:54:37','2026-03-30 10:54:37',''),
(77,64,'Md. Amanullah Aman','Md. Amanullah Aman','01335258306','aman.tphl@gmail.com',20,'South Bhandaria-8550, Bhandaria, Pirojpur','6010834478',8,'$2y$10$GvrxHKdWRY2m3tvvUsFMRuAgqeM6L993ISeHJG/42UBRYVYI6UnQ6',NULL,'','','2026-03-30 10:56:23',0,'2026-03-30 10:56:23','2026-03-30 10:56:23',''),
(78,65,'Abu Naym','Abu Naym','01335258312','naymtphl@gmail.com',20,'Ati-1312, Keraniganj, Dhaka','4204089967',8,'$2y$10$CojmU9mTBwgDjkx4CfkdYeaQ57vvBBDxV/seIXX4jy.25RgK19WMG',NULL,'','','2026-03-30 10:59:42',0,'2026-03-30 10:59:42','2026-03-30 10:59:42',''),
(79,66,'Md. Sakib Inam','Md. Sakib Inam','01335258310','sakibinam.tphl@gmail.com',16,'','1962602841',8,'$2y$10$uhKw9sTOb03dEHxMvZb/KekSOvQ6RjfanioES3ZW5pOa4m.VkexNO',NULL,'','','2026-03-30 11:00:09',0,'2026-03-30 11:00:09','2026-03-30 11:00:09',''),
(80,67,'Md Bakhtiar Hasan','Md Bakhtiar Hasan','01335258300','bakhtiar.tphl@gmail.com',13,'','4602140743',8,'$2y$10$3KjxMU.PqmWBXo77Kd3nbO4vYx55T4pByMR7U.kHE91Sro2ch8sLe',NULL,'','','2026-03-30 11:10:48',0,'2026-03-30 11:10:48','2026-03-30 11:10:48',''),
(81,68,'Md Lutfor Rahaman','Md Lutfor Rahaman','01335258334','lutfor.tphl@gmail.com',27,'Vill: Asapur, PO: Phancherkanda, Thana: Ghior, Dist: Manikganj, Dhaka','7350928797',2,'$2y$10$SiSPnIWXvfbAy0DznRqjNebZp6fjgF0LGkHEVG7NPoNy8.J2fdc9O',NULL,'','','2026-03-30 11:17:42',0,'2026-03-30 11:17:42','2026-03-30 11:17:42',''),
(82,69,'Dipto Kumar Roy','Dipto Kumar Roy','01958253358','dipto.tphl@gmail.com',20,'Khagan,Ashulia,Saver Dhaka','2851653333',2,'$2y$10$pB9uAIYgkG6tt/yMZQzVUuiQzSe3shbNwKlu45kAtQqlTrNGZ/JPu',NULL,'','','2026-03-30 11:19:42',0,'2026-03-30 11:19:42','2026-03-30 11:19:42',''),
(83,70,'B. M Shaharear Hasan','B. M Shaharear Hasan','01917331397','shaharear.tphl@gmail.com',27,'Korotoa road, latifpur kolony, Bogra','7318135568',6,'$2y$10$IR.UVy4pARa366NVJj3DGejR6j1MB0KlvUqEi5kvhJeZSLVd3RenW',NULL,'','','2026-03-30 11:22:17',0,'2026-03-30 11:22:17','2026-03-30 11:22:17',''),
(84,71,'MD. Sakhawat Hossain Emon','MD. Sakhawat Hossain Emon','01335258335','emon.tphl@gmail.com',23,'Block-A, Lane-6, Mirpur-6, Dhaka 1216','1962405062',2,'$2y$10$zbxbowLRq8arKWPKWtXlmutMu0HgAD/L0jo0QOuiV5v1tzE/5MBoK',NULL,'','','2026-03-30 11:22:57',0,'2026-03-30 11:22:57','2026-03-30 11:22:57',''),(85,72,'MD. Zamiul Islam','MD. Zamiul Islam','01335258338','zamiul.tphl@gmail.com',17,'130/6/A Baganbari,  Madatek, Basabo, Dhaka -1214.','5086466165',2,'$2y$10$JNlsOeAu7vANay8HvhgzTu8qrFZTw42wEfb9hvza857xBIdxSE5kq',NULL,'','','2026-03-30 11:25:48',0,'2026-03-30 11:25:48','2026-03-30 11:25:48',''),
(86,73,'MD. Ahadul Islam','MD. Ahadul Islam','01314010972','asif.tphl@gmail.com',23,'Amula Dahabharatta, Nikrail, Bhuapur.','3314775515',2,'$2y$10$YlJmkkdgPI02YYTHw5G/o.tJE.7k/v6D34A6bHyNdlPuQztkA1IFe',NULL,'','','2026-03-30 11:26:09',0,'2026-03-30 11:26:09','2026-03-30 11:26:09',''),
(87,74,'Sadman Islam Sakib','Sadman Islam Sakib','01958253344','sakib.tphl@gmail.com',21,'H-12, Kusumbag Residential Area, North Bisil  Road, Mirpur-1,Dhaka-1216','7817318327',8,'$2y$10$EZYoIwrFc/RwFFaj299PeOTVK1Jjf4pVV8dFhN0apKOxd6.BoIP9C',NULL,'','','2026-03-30 11:33:39',0,'2026-03-30 11:33:39','2026-03-30 11:33:39',''),
(88,75,'Mahaananda Kumar Saha','Mahaananda Kumar Saha','01335258327','mahaanandakumarsaha@gmail.com',22,'Faridpur, sadarpur,hatkrishnapur','5506251882',2,'$2y$10$IawCX1UOcrjxps1aO4QTWeip4b8tKdN3JGDyduTJtnqLNYVuIzYXu',NULL,'','','2026-03-30 11:34:33',0,'2026-03-30 11:34:33','2026-03-30 11:34:33',''),
(90,79,'Moonmoon Akter Oishy','Moonmoon Akter Oishy','01958253355','oishytphl@gmail.com',24,'Road No -1,  B - 45 Bank Town,  Savar','1047316906',2,'$2y$10$Sdqb84cXh8GKU9Okw2rRSe7k3N6a0riQJVPBDqnRhqa9px28WsDY.',NULL,'','','2026-03-30 11:43:09',0,'2026-03-30 11:43:09','2026-03-30 11:43:09',''),
(91,76,'Rubina Islam','Rubina Islam','01958253349','rubinatphl49@gmail.com',15,'','',2,'$2y$10$r8GdWRKSpeZjr24W1pjGn.uaye6s/.clnO7G8ESZI3NnV844NrnCS',NULL,'','','2026-03-30 11:44:43',0,'2026-03-30 11:44:43','2026-03-30 11:44:43',''),
(92,80,'Afrin Jahan Anika','Afrin Jahan Anika','01958253335','anika.tphl@gmail.com',13,'39/b senpara porbota mirpur 10','1032936708',2,'$2y$10$nBTOuqyG5vmEhBfJADloguSRdoA383Hrd.mFIuseKxOXG3bkfZYZG',NULL,'','','2026-03-30 11:52:36',0,'2026-03-30 11:52:36','2026-03-30 11:52:36','');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
-- Just set existing team_id values to NULL
UPDATE `users` SET `team_id` = NULL;
UNLOCK TABLES;

--
-- Table structure for table `whatsapp_campaigns`
--

DROP TABLE IF EXISTS `whatsapp_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `whatsapp_campaigns` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `target_project_id` int DEFAULT NULL,
  `message_type` enum('text','image','pdf','button','brocure') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'text',
  `message_content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `schedule_time` datetime NOT NULL,
  `device` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Auto Select',
  `status` enum('Active','Scheduled','Paused','Completed','Failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Scheduled',
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
  `status` enum('pending','sent','delivered','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending',
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

DROP TABLE IF EXISTS `teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teams`
--

LOCK TABLES `teams` WRITE;
/*!40000 ALTER TABLE `teams` DISABLE KEYS */;
/*!40000 ALTER TABLE `teams` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-30 12:20:48
