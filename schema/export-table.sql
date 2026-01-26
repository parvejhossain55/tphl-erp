-- 05/01/2025 Farzana
-- all Table's add a column created_by excluding which table already has tasks,lead_bookings,leads_follow_ups,lead_payments
SET
  SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET
  time_zone = "+00:00";

--
-- Database: `tphl_db`
-- Table structure for table `lead_payments`
--
CREATE TABLE
  `lead_payments` (
    `id` int (10) UNSIGNED NOT NULL,
    `booking_id` int (10) UNSIGNED NOT NULL,
    `payment_type` enum ('booking_money', 'registration', 'emi') NOT NULL,
    `amount` decimal(12, 2) NOT NULL,
    `payment_method` enum ('cash', 'bank', 'online', 'cheque') DEFAULT 'cash',
    `payment_status` enum ('paid', 'partially_paid', 'pending', 'failed') DEFAULT NULL,
    `payment_date` date NOT NULL,
    `total_emi_no` int (11) DEFAULT NULL,
    `emi_total` int (11) DEFAULT NULL,
    `transaction_ref` varchar(100) DEFAULT NULL,
    `bank_name` varchar(100) DEFAULT NULL,
    `bank_account_no` varchar(50) DEFAULT NULL,
    `cheque_no` varchar(50) DEFAULT NULL,
    `online_type` varchar(50) DEFAULT NULL,
    `created_by` int (10) UNSIGNED NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- Dumping data for table `lead_payments`
INSERT INTO
  `lead_payments` (
    `id`,
    `booking_id`,
    `payment_type`,
    `amount`,
    `payment_method`,
    `payment_status`,
    `payment_date`,
    `total_emi_no`,
    `emi_total`,
    `transaction_ref`,
    `bank_name`,
    `bank_account_no`,
    `cheque_no`,
    `online_type`,
    `created_by`,
    `created_at`
  )
VALUES
  (
    1,
    1,
    'booking_money',
    20000000.00,
    'bank',
    'paid',
    '2025-12-21',
    NULL,
    NULL,
    '12345',
    NULL,
    NULL,
    NULL,
    NULL,
    12,
    '2025-12-21 11:01:25'
  ),
  (
    5,
    2,
    'booking_money',
    3000000.00,
    'cash',
    'paid',
    '2025-12-22',
    NULL,
    NULL,
    '123456',
    NULL,
    NULL,
    NULL,
    NULL,
    12,
    '2025-12-22 08:56:21'
  ),
  (
    7,
    5,
    'booking_money',
    2000000.00,
    'bank',
    'paid',
    '2025-12-23',
    NULL,
    NULL,
    '122334',
    NULL,
    NULL,
    NULL,
    NULL,
    12,
    '2025-12-23 08:32:22'
  ),
  (
    9,
    6,
    'booking_money',
    1000000.00,
    'cheque',
    'paid',
    '2025-12-31',
    48,
    NULL,
    'Eva',
    'BRAC Bank',
    '',
    '24343465',
    'bKash',
    12,
    '2025-12-31 09:57:06'
  ),
  (
    10,
    6,
    'registration',
    987000.00,
    'cash',
    'paid',
    '2026-01-04',
    NULL,
    NULL,
    '',
    '',
    '',
    '',
    'bKash',
    1,
    '2026-01-04 05:14:21'
  ),
  (
    11,
    6,
    'emi',
    55555555.00,
    'online',
    'paid',
    '2026-01-04',
    NULL,
    NULL,
    'trx345',
    '',
    '',
    '',
    'bKash',
    1,
    '2026-01-04 10:14:27'
  ),
  (
    12,
    6,
    'emi',
    231233214.00,
    'cash',
    'partially_paid',
    '2026-01-04',
    NULL,
    NULL,
    '',
    '',
    '',
    '',
    'bKash',
    1,
    '2026-01-04 11:22:43'
  );

--
-- Indexes for dumped tables
-- Indexes for table `lead_payments`
--
ALTER TABLE `lead_payments` ADD PRIMARY KEY (`id`),
ADD KEY `fk_payment_booking` (`booking_id`);

--
-- AUTO_INCREMENT for dumped tables
--
--
-- AUTO_INCREMENT for table `lead_payments`
--
ALTER TABLE `lead_payments` MODIFY `id` int (10) UNSIGNED NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 13;

--
-- Constraints for dumped tables
--
--
-- Constraints for table `lead_payments`
--
ALTER TABLE `lead_payments` ADD CONSTRAINT `fk_payment_booking` FOREIGN KEY (`booking_id`) REFERENCES `lead_bookings` (`id`) ON DELETE CASCADE;

COMMIT;

-- Added By Rafi 5 jan-2026 . Table lead_payments added payment_status field to track payment status like paid, partially_paid, pending, failed.
--10-1-2026 Start Added By Farzana 
--1
ALTER TABLE flats
ADD COLUMN status ENUM ('available', 'booked', 'sold') DEFAULT 'available' AFTER created_by,
--2
ALTER TABLE employees ADD employee_type ENUM ('permanent', 'contract', 'director') AFTER photo,
ADD employee_status ENUM (
  'active',
  'inactive',
  'resigned',
  'terminated',
  'on_leave'
) DEFAULT 'active' AFTER employee_type;

--10-1-2026 End Added By Farzana 
--13-1-2026 added by farzana
change logo name
alter
with
  white_logo
ALTER TABLE site_settings
ADD COLUMN dark_logo varchar(200) DEFAULT NULL AFTER white_logo
--13-1-2026 END  added by farzana
--15-1-2026 added By Farzana For AI Chatbox
CREATE TABLE
  chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_role_id INT (11) NOT NULL, -- role_id from session
    message TEXT NOT NULL,
    reply TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  );

--17-1-2026 added By Farzana For AI Chatbox
CREATE TABLE
  head_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL, -- notification receiver
    type ENUM ('lead', 'booking', 'task', 'activity') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    url VARCHAR(255) DEFAULT NULL,
    is_read TINYINT (1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_type (type)
  );

--20-1-2026 added By Farzana For AI Chatbox
CREATE TABLE
  property_reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reviewer_name VARCHAR(150) NOT NULL,
    reviewer_role VARCHAR(100) NULL,
    project_id INT NULL,
    reviewer_image VARCHAR(255) NULL,
    review_video_url VARCHAR(255) NULL,
    rating TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review_text TEXT NOT NULL,
    review_date DATE NULL,
    status ENUM ('active', 'inactive') DEFAULT 'active',
    `created_by` int (10) UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
  );

-- CREATE TABLE
--   emp_attendances (
--     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     employee_id INT UNSIGNED NOT NULL,
--     punch_in_time TIMESTAMP NULL,
--     punch_out_time TIMESTAMP NULL,
--     attendance_date DATE NULL,
--     `created_by` int (10) UNSIGNED NOT NULL,
--     created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--     deleted_at TIMESTAMP NULL
--   );

-- ALTER TABLE emp_attendances
-- ADD COLUMN today_status  ENUM ('Holiday', 'Day Off','Present','Absent','On Leave','Half-Day','Late') NULL AFTER attendance_date
CREATE TABLE emp_attendances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,

    punch_in_time TIMESTAMP NULL,
    punch_out_time TIMESTAMP NULL,

    today_status VARCHAR(20) NOT NULL,

    created_by BIGINT UNSIGNED NOT NULL,

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    UNIQUE KEY unique_employee_date (employee_id, attendance_date),

    CONSTRAINT fk_attendance_employee
        FOREIGN KEY (employee_id)
        REFERENCES employees(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);

-- https://youtu.be/qM5_UKR-vzk
--20-1-2026 END added By Farzana For AI Chatbox

--25/01/2026
CREATE TABLE approval_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    department VARCHAR(50) NOT NULL, 
    request_type VARCHAR(50) NOT NULL, 
    title VARCHAR(150) NOT NULL,

    source_table VARCHAR(50) NOT NULL,
    source_id INT UNSIGNED NOT NULL,

    request_amount DECIMAL(12,2) NULL,
    approved_amount DECIMAL(12,2) NULL,

    status ENUM('pending','approved','rejected','partial') DEFAULT 'pending',

    requested_by INT UNSIGNED NOT NULL,
    approve_role VARCHAR(50) NOT NULL,

    meta_data JSON NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


--25/01/2026


-- farzana