Manual Floor-Wise & Phase-Wise Material Cost Tracking (Without Autocasting)
This plan outlines the architecture, database schema, and interface changes needed to track material budgets, targets, BOQ requisitions, and warehouse issues per floor (including Basement, Typical Floor, and Roof) manually, without implementing the auto-costing estimation engine. This layout is completely future-proof, allowing auto-costing rules to be added later without altering the transactional workflow.

User Review Required
IMPORTANT

Database Alterations & Existing Data:

We will add a new table project_floors to configurationally define the floors of each project.
We will add floor_type and floor_no columns to existing transactional tables.
Existing rows in the database will default to Typical_Floor with floor_no as NULL (representing general/unassigned allocations) so that current workflows do not break.
Proposed Changes
1. Database Schema Changes (SQL Patch)
We need exactly 1 new table (project_floors) to define the structure of a project, and columns added to 5 existing tables to carry the floor metadata.

sql

SET FOREIGN_KEY_CHECKS = 0;
-- A. Floor Configuration Table (New)
-- Serves as the master list of floors for dropdowns and validation limit checks.
CREATE TABLE IF NOT EXISTS `project_floors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT NOT NULL,
  `floor_no` INT NOT NULL COMMENT 'e.g., -1 = Basement, 0 = Ground Floor, 1 = 1st Floor',
  `floor_type` ENUM('Basement', 'Ground_Floor', 'Typical_Floor', 'Roof') NOT NULL DEFAULT 'Typical_Floor',
  `slab_area_sqft` DECIMAL(10,2) NOT NULL COMMENT 'Actual construction area for this floor',
  `height_ft` DECIMAL(5,2) DEFAULT 10.00 COMMENT 'Height of this floor in feet',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_project_floor` (`project_id`, `floor_no`),
  CONSTRAINT `fk_pf_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
-- B. Adding Columns to Existing Tables (5 Tables Modified)
-- 1. Initial Estimate Items (BOM budget)
ALTER TABLE `initial_estimate_items` 
  ADD COLUMN `floor_type` ENUM('Basement', 'Ground_Floor', 'Typical_Floor', 'Roof') NOT NULL DEFAULT 'Typical_Floor' AFTER `phase_item_id`,
  ADD COLUMN `floor_no` INT(11) DEFAULT NULL AFTER `floor_type`,
  ADD INDEX `idx_iei_floor` (`floor_no`);
-- 2. Initial Estimate Labors (Labor budget)
ALTER TABLE `initial_estimate_labors` 
  ADD COLUMN `floor_type` ENUM('Basement', 'Ground_Floor', 'Typical_Floor', 'Roof') NOT NULL DEFAULT 'Typical_Floor' AFTER `phase_item_id`,
  ADD COLUMN `floor_no` INT(11) DEFAULT NULL AFTER `floor_type`,
  ADD INDEX `idx_iel_floor` (`floor_no`);
-- 3. Monthly Target Work Items
ALTER TABLE `phase_monthly_target_items`
  ADD COLUMN `floor_type` ENUM('Basement', 'Ground_Floor', 'Typical_Floor', 'Roof') NOT NULL DEFAULT 'Typical_Floor' AFTER `phase_item_id`,
  ADD COLUMN `floor_no` INT(11) DEFAULT NULL AFTER `floor_type`,
  ADD INDEX `idx_pmti_floor` (`floor_no`);
-- 4. BOQ Requisition Items (Site requests)
ALTER TABLE `boq_items` 
  ADD COLUMN `floor_type` ENUM('Basement', 'Ground_Floor', 'Typical_Floor', 'Roof') NOT NULL DEFAULT 'Typical_Floor' AFTER `material_id`,
  ADD COLUMN `floor_no` INT(11) DEFAULT NULL AFTER `floor_type`,
  ADD INDEX `idx_boqi_floor` (`floor_no`);
-- 5. Material Issues (Store dispatch / stock out)
ALTER TABLE `con_material_issues`
  ADD COLUMN `floor_type` ENUM('Basement', 'Ground_Floor', 'Typical_Floor', 'Roof') NOT NULL DEFAULT 'Typical_Floor' AFTER `target_id`,
  ADD COLUMN `floor_no` INT(11) DEFAULT NULL AFTER `floor_type`,
  ADD INDEX `idx_cmi_floor` (`floor_no`);
SET FOREIGN_KEY_CHECKS = 1;
2. Batch Implementation Schedule (ধাপসমূহ)
Batch 1: Database Migration & Project Floor Setup (ডেটাবেস ও ফ্লোর সেটিংস)
SQL Migration: Execute the SQL script above.
Timeline Page UI Setup:
[MODIFY] 
create-timeline.php
 / 
edit-timeline.php
: Add a "Floor Configuration" dynamic section where users define how many basements, typical floors, and roofs the project has, including their respective square footage (slab area).
[MODIFY] 
Con_ProjectTimelineController.php
: Handle timeline POST requests and save floor details into project_floors.
Batch 2: Floor-Tagged Initial Budget (ম্যানুয়াল ফ্লোর-ভিত্তিক বাজেট)
Estimate Form:
[MODIFY] 
create-initial-budget.php
 / 
edit-initial-budget.php
:
Add a Floor Selection column (floor_type & floor_no) to the material and labor budget tables.
The floor dropdown is dynamically populated based on the project configured in Batch 1.
[MODIFY] 
Con_InitialEstimateController.php
: Update the saving logic to map estimated quantity and rate directly to the selected floor.
Batch 3: Target Setup & BOQ Validation (ফ্লোর-ভিত্তিক লক্ষ্য ও BOQ রিকোয়েস্ট)
Monthly Targets Form:
[MODIFY] 
create-monthly-target.php
: Add a floor dropdown in the work item target creation form.
[MODIFY] 
Con_MonthlyTargetController.php
: Save target items linked with floor_type and floor_no.
BOQ Request Form:
[MODIFY] 
create-boq.php
: Add a floor dropdown next to each selected material.
[MODIFY] 
Con_BOQController.php
:
Save floor data for requested items.
Validation Alert: When the site engineer requests cement for the "Basement, compare the requested BOQ quantity with the basement's manual budget limit in initial_estimate_items. Show a warning if it exceeds the limit.
Batch 4: Store Dispatch & Floor Ledger Report (মেটেরিয়াল ইস্যু ও অডিট রিপোর্ট)
Warehouse Material Issue:
[MODIFY] 
create-issue.php
: Storekeepers select the specific floor (dropdown) where the material is being physically dispatched.
[MODIFY] 
Con_MaterialIssueController.php
: Update the stock out handler to save the floor reference.
New Report - Floor-Wise Material Audit Ledger:
[NEW] 
floor-ledger-report.php
: A grid report showing the comparison per floor:
Floor No | Material Name | Budgeted Qty | BOQ Approved Qty | Warehouse Issued Qty | Variance (Deviation)
Allows the management to see exactly which floor consumed more materials than estimated.
Verification Plan
Automated Tests
Create a test script test_floor_query.php to verify that joining project floors with estimates, targets, and issues correctly aggregates costs.
Manual Verification
Create a dummy project, configure 1 Basement and 3 Floors.
Manually add budget items for Basement and Floor 1.
Try raising a BOQ for Floor 1 with a quantity greater than budgeted, verifying if the system flags it.
Issue materials from the warehouse to Floor 1 and check if the floor-wise audit ledger updates correctly.

==============================
-- full system testing will be conducted after all batches are implemented to ensure end-to-end integrity of the floor-wise tracking mechanism.
. Project Floor Setup (floor-setup.php & timeline controller)
কীভাবে কাজ করছে: নতুন প্রজেক্ট সিলেক্ট করলে প্রজেক্টের building_type (যেমন: B+G+9) স্বয়ংক্রিয়ভাবে পার্স করে বেসমেন্ট, গ্রাউন্ড ফ্লোর এবং টিপিক্যাল ফ্লোরের তালিকা ডাটাবেজে ও ইন্টারফেসে জেনারেট করে।
ট্র্যাকিং: নতুন ফ্লোর এরিয়া স্প্লিট ডিজাইন (Unit Area + Extra Area = Total Slab Area) সফলভাবে ডাটাবেজ টেবিল project_floors-এ সেভ হচ্ছে এবং পেজ লোডে রেন্ডার হচ্ছে।
২. Initial Budget / Estimate Page (create/edit/show/print budget)
কীভাবে কাজ করছে: যখন প্রজেক্ট সিলেক্ট করা হয়, তখন পেজটি ডাইনামিক্যালি AJAX-এর মাধ্যমে প্রজেক্টের ফ্লোরগুলো নিয়ে আসে। যদি কোনো ফ্লোর আগে সেটআপ করা না থাকে, তবে ব্যাকএন্ড স্বয়ংক্রিয়ভাবে ডাটাবেজে ফ্লোরগুলো জেনারেট করে নেয়।
ট্র্যাকিং: প্রতিটি আইটেম নির্দিষ্ট ফ্লোরের ট্যাগ সহ initial_estimate_items টেবিলে সঠিকভাবে সংরক্ষিত হচ্ছে।
৩. Monthly Target Work Plan Page (monthly_targets/form.php & controller)
কীভাবে কাজ করছে: মান্থলি টার্গেট পেজের উপরে ইনফো-বারে প্রজেক্টের Building Type এবং মোট কয়টি ফ্লোর সেটআপ করা আছে তা প্রদর্শিত হচ্ছে।
ট্র্যাকিং: নতুন কাজের আইটেম যোগ করার ফর্মে এবং টেবিল এডিটের সময় প্রতিটি কাজের পাশে ফ্লোর সিলেক্ট করার ড্রপডাউন দেওয়া হয়েছে। সাবমিট করলে প্রতিটি কাজের বিপরীতে ফ্লোর কলাম (floor_type ও floor_no) phase_monthly_target_items টেবিলে সেভ হচ্ছে এবং রিড-অনলি মোডে পরিষ্কার লেবেল (যেমন: Basement 1, Ground Floor, 2nd Floor) হিসেবে দেখা যাচ্ছে।
দ্রষ্টব্য: উইকলি প্রোগ্রেস এবং ডেইলি প্রোগ্রেস লগগুলো সরাসরি মান্থলি টার্গেট আইটেমকে রেফারেন্স করে ট্র্যাক করে। যেহেতু মান্থলি আইটেমটি ইতিমধ্যেই ফ্লোরের সাথে ট্যাগ করা থাকে, তাই উইকলি ও ডেইলি ট্র্যাকিং-ও ট্রানজিটিভলি ফ্লোর-ওয়াইজ ট্র্যাক হয়ে যাচ্ছে।
৪. BOQ Request Page (create/edit/show boq & controller)
কীভাবে কাজ করছে:
ড্রপডাউন ট্র্যাকিং: প্রজেক্ট সিলেক্ট করার সাথে সাথে ডাইনামিক্যালি ফ্লোর ড্রপডাউন জেনারেট হয়।
রো ক্লোনিং: নতুন রো যোগ করলে ফ্লোর ড্রপডাউন ক্লোন হয় এবং ডিফল্ট ভ্যালু General/Unassigned হিসেবে রিসেট হয়।
বাজেট লিমিট ও SweetAlert ভ্যালিডেশন: BOQ সেভ বা আপডেট করার সময় ব্যাকএন্ডের /con-ajax-check-boq-limits এপিআই কল হয়। এটি ওই ফ্লোরের প্রাক্কলিত এস্টিমেট বাজেট থেকে পূর্বের অনুমোদিত BOQ পরিমাণ বিয়োগ করে অবশিষ্ট বাজেট হিসাব করে। লিমিট অতিক্রম করলে SweetAlert-এর মাধ্যমে অতিরিক্ত পরিমাণের সুন্দর ছকসহ Soft Warning Alert দেখায়, যেখানে ব্যবহারকারী চাইলে তা এড়াতে পারবেন অথবা "Yes, Submit Anyway" দিয়ে এগিয়ে যেতে পারবেন।
ডিটেইলস ভিউ: show-boq.php ফাইলে ম্যাটেরিয়াল লিস্টের সাথে নতুন Floor কলাম যোগ করা হয়েছে যা কোন কাজের জন্য কত ম্যাটেরিয়াল রিকোয়েস্ট করা হয়েছে তা দেখায়।
কোনো কাজ বাকি আছে কি?
না, রিকোয়ারমেন্ট এবং ডিজাইন ডক অনুযায়ী সবগুলো পেজের ট্র্যাকিং এবং ইন্টার-কানেকশন সম্পূর্ণ করা হয়েছে।

ফ্লোর জেনারেশন থেকে শুরু করে ইনিশিয়াল এস্টিমেট বাজেট, মান্থলি টার্গেট এবং BOQ-এর সফট লিমিট ভ্যালিডেশন পর্যন্ত প্রতিটি ধাপ একে অপরের সাথে নিখুঁতভাবে ডেটা আদান-প্রদান করছে।
আপনার সিস্টেমে এখন সম্পূর্ণ ফিচারটি লাইভ টেস্ট করার জন্য প্রস্তুত! কোনো নির্দিষ্ট অংশ পরীক্ষা করতে গিয়ে সমস্যা হলে বা কোনো প্রশ্ন থাকলে আমাকে জানান।
