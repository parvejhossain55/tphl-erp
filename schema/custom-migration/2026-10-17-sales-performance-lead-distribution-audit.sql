-- =========================================================================
-- Migration: Sales Team Performance, Lead Distribution Audit & History
-- Date: 2026-10-17
-- Description:
--   1. Adds assigned_by, first_contacted_at, drop_reason, drop_note to `leads`.
--   2. Creates `lead_assignment_history` to log team/user lead distribution.
--   3. Creates `lead_call_logs` to maintain complete multi-call & note history.
--   4. Safely backfills existing records to maintain 100% data consistency.
-- =========================================================================

-- 1. Add missing columns to `leads` table if they do not exist
SET @dbname = DATABASE();

SET @precheck = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'leads' AND COLUMN_NAME = 'assigned_by');
SET @sql = IF(@precheck = 0, 'ALTER TABLE `leads` ADD COLUMN `assigned_by` INT(11) NULL AFTER `assigned_at`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @precheck = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'leads' AND COLUMN_NAME = 'first_contacted_at');
SET @sql = IF(@precheck = 0, 'ALTER TABLE `leads` ADD COLUMN `first_contacted_at` DATETIME NULL AFTER `assigned_by`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @precheck = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'leads' AND COLUMN_NAME = 'drop_reason');
SET @sql = IF(@precheck = 0, 'ALTER TABLE `leads` ADD COLUMN `drop_reason` VARCHAR(150) NULL AFTER `lead_type`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @precheck = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'leads' AND COLUMN_NAME = 'drop_note');
SET @sql = IF(@precheck = 0, 'ALTER TABLE `leads` ADD COLUMN `drop_note` TEXT NULL AFTER `drop_reason`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Create `lead_assignment_history` table
CREATE TABLE IF NOT EXISTS `lead_assignment_history` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `lead_id` INT UNSIGNED NOT NULL,
  `from_user_id` INT NULL,
  `to_user_id` INT NOT NULL,
  `from_team_id` INT NULL,
  `to_team_id` INT NULL,
  `assigned_by` INT NOT NULL,
  `assigned_at` DATETIME NOT NULL,
  `action_type` ENUM('assigned', 'reassigned', 'transferred', 'bulk_assigned') DEFAULT 'assigned',
  `notes` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_lah_lead` (`lead_id`),
  KEY `idx_lah_to_user` (`to_user_id`),
  KEY `idx_lah_assigned_by` (`assigned_by`),
  KEY `idx_lah_assigned_at` (`assigned_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create `lead_call_logs` table
CREATE TABLE IF NOT EXISTS `lead_call_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `lead_id` INT UNSIGNED NOT NULL,
  `user_id` INT NOT NULL,
  `call_status` ENUM('not_called','called','received','not_received') NULL,
  `communication_type` VARCHAR(100) NULL,
  `call_note` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_lcl_lead` (`lead_id`),
  KEY `idx_lcl_user` (`user_id`),
  KEY `idx_lcl_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Backfill existing leads data safely
-- For leads with assigned_id, initialize assigned_by from created_by if assigned_by is NULL
UPDATE `leads` 
SET `assigned_by` = `created_by` 
WHERE `assigned_by` IS NULL AND `assigned_id` IS NOT NULL;

-- Backfill first_contacted_at from the earliest follow up record
UPDATE `leads` l 
INNER JOIN (
    SELECT `lead_id`, MIN(`created_at`) AS `first_time` 
    FROM `lead_follow_ups` 
    WHERE `deleted_at` IS NULL 
    GROUP BY `lead_id`
) fu ON l.id = fu.lead_id 
SET l.`first_contacted_at` = fu.`first_time` 
WHERE l.`first_contacted_at` IS NULL;




/*
১. সেলস টিম পারফরম্যান্স ও KPI ড্যাশবোর্ডে (sales-team-kpi.php)
১০০% নির্ভুল কনভার্সন রেট (Conversion Rate):
আগে যা হতো: আপনি যখন নির্দিষ্ট কোনো মাস (যেমন: October 2026) ফিল্টার করতেন, ভিজিট ও বুকিং ফিল্টার হলেও টোটাল লিড আসত অল-টাইম (হাজার হাজার লিড)। ফলে কনভার্সন রেট ভুল আসত (যেমন: ০.০৫%)।
এখন যা হবে: ফিল্টার করা নির্দিষ্ট মাসের লিড সংখ্যার বিপরীতেই ভিজিট ও বুকিং ক্যালকুলেট হবে, ফলে প্রকৃত কনভার্সন রেট (যেমন: ২০%, ২৫%) একদম নিখুঁত দেখাবে।
নতুন ম্যাট্রিক "Avg First Response":
সেলস এক্সিকিউটিভদের পারফরম্যান্স কার্ডে এখন তাদের প্রথম রেসপন্স করার গড় সময় (ঘণ্টা/দিন) দেখতে পাবেন। কে লিড পাওয়ার কত দ্রুত ক্লায়েন্টকে প্রথম কল/যোগাযোগ করেছে তা ট্র্যাকিং করা সম্ভব হবে।

২. ডিজিটাল মার্কেটিং লিড আপলোড ও ট্র্যাকিং (Lead Ownership)
আগে যা হতো: ডিজিটাল মার্কেটিং এক্সিকিউটিভ লিড আপলোড করার পর যখন সেলস ম্যানেজার তা সেলস পার্সনদের মাঝে ডিস্ট্রিবিউট বা রিঅ্যাসাইন করতেন, কোড ভুলবশত created_by ফিল্ডে সেলস ম্যানেজারের আইডি বসিয়ে দিত। ফলে মার্কেটিং টিম কোন লিড এনেছে তার হিসেব নষ্ট হয়ে যেত।
এখন যা হবে:
লিড কে আপলোড করেছে (created_by = Marketing Executive) তা আজীবন সুরক্ষিত থাকবে।
কে লিডটি ডিস্ট্রিবিউট করেছে (assigned_by = Sales Manager) তা আলাদাভাবে ট্র্যাক থাকবে।
ফলে মার্কেটিং ও সেলস কোনো টিমের ক্রেডিট বা কাজের ডেটা আর মিসম্যাচ হবে না।

৩. Today Leads ও My All Assigned Leads (Lead.php)
সেলস এক্সিকিউটিভদের "Today Leads" এবং "My All Assigned Leads" পেজের কুয়েরিতে এখন assigned_by_name এবং creator_name দুটোই আলাদাভাবে যুক্ত করা হয়েছে।
এখন সেলস টিম দেখতে পারবে লিডটি মার্কেটিং টিম থেকে কবে এসেছে এবং কোন ম্যানেজার তাকে কবে হ্যান্ডওভার করেছেন।

৪. লিড ডিস্ট্রিবিউশন হিস্টোরি ও অডিট ট্রেইল (lead_assignment_history)
ম্যানেজার যখন বাল্ক অ্যাসাইন করবেন কিংবা ফলো-আপের সময় "Lead Transferred" করে অন্য সহকর্মীকে লিড দেওয়া হবে:
প্রতিটা মুভমেন্টের অডিট ট্রেইল (from_user_id, to_user_id, assigned_by, assigned_at, action_type) জমা থাকবে।
কোনো লিড হাতবদল নিয়ে ইন্টারনাল কনফিউশন হলে সুনির্দিষ্ট টাইমস্ট্যাম্প দিয়ে হিস্টোরি ট্র্যাক করা যাবে।

৫. কল লগ ও অ্যাটেম্পট হিস্টোরি (lead_call_logs)
আগে যা হতো: সেলস পার্সন ড্যাশবোর্ড থেকে কল স্ট্যাটাস (Called, Received, Not Received) বা কল নোট পরিবর্তন করলে আগের কথাগুলো ওভাররাইট হয়ে মুছে যেত।
এখন যা হবে: প্রতিটি কল অ্যাটেম্পট এবং নোট স্বয়ংক্রিয়ভাবে টাইমস্ট্যাম্পসহ lead_call_logs টেবিলে সংরক্ষিত থাকবে। ফলে ক্লায়েন্টকে দিনে কতবার চেষ্টা করা হয়েছে তার পূর্ণ হিস্টোরি পাওয়া যাবে।
*/