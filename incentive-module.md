Project Incentive Module Implementation Plan
This document details the architecture, database schema changes, workflow logic, UI components, and implementation strategy for the Incentive Module in the ERP.

📌 Executive Summary & Key Features
The Incentive Module enables automated calculation, claiming, approval, and disbursement of project sales incentives based on Project Category Incentive Percentages (project_categories.incentive_percentage).

Core Requirements Covered:
Category-Wise Incentive %:
Incentive is calculated from flat total price (`flats.price_value`) * incentive_percentage / 100 based on the project's category.
Eligibility Condition:
A sale is eligible for incentive claiming when both Booking Money and Registration are completed and verified for a lead's booking (lead_bookings).
Sales Person View ("My Incentives"):
Sales Executives, Managers, and Sales Team Heads can view their project bookings, verify booking + registration status, see calculated incentive amounts, and click "Claim Incentive".
Accounts & Management View ("Incentive Management"):
Accounts and Management roles can view company-wide incentive metrics: Total Earned, Claimed, Approved, Paid/Withdrawn, and Remaining Balance per sales person.
Accounts can Approve/Reject claims and record Disbursements / Payouts (via Cash, Bank, Cheque, etc.).
External / Non-Sales Employee Incentive Assignment:
Management can manually assign a project sale incentive to non-sales employees (e.g. Engineers, HR, external staff) by selecting the Employee, Project/Booking, Sale Amount, and custom Incentive % / Amount.
Manually assigned incentives enter the Accounts disbursement queue alongside regular sales claims.
🗄️ Database Changes Required
We will introduce two new database tables:

1. project_incentive_claims
Stores all incentive claims (both auto-eligible sales claims and manually assigned external employee incentives).

sql

CREATE TABLE IF NOT EXISTS `project_incentive_claims` (
  `id` INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT(10) UNSIGNED NOT NULL,
  `project_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL COMMENT 'Sales Person or Non-sales Employee receiving incentive',
  `is_external` TINYINT(1) DEFAULT 0 COMMENT '0 = Sales Person, 1 = Non-sales / External Employee manual assignment',
  `assigned_by` INT(11) DEFAULT NULL COMMENT 'User ID of Management member who assigned external incentive',
  `sale_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `incentive_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `incentive_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `booking_completed` TINYINT(1) DEFAULT 0,
  `registration_completed` TINYINT(1) DEFAULT 0,
  `claim_status` ENUM('eligible', 'claimed', 'approved', 'rejected', 'partially_paid', 'paid') NOT NULL DEFAULT 'eligible',
  `claimed_at` DATETIME DEFAULT NULL,
  `approved_by` INT(11) DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`user_id`),
  INDEX (`booking_id`),
  INDEX (`project_id`),
  INDEX (`claim_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
2. project_incentive_payments
Tracks partial or full payout disbursements made by Accounts to employees.

sql

CREATE TABLE IF NOT EXISTS `project_incentive_payments` (
  `id` INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `claim_id` INT(10) UNSIGNED NOT NULL,
  `user_id` INT(11) NOT NULL,
  `payment_amount` DECIMAL(15,2) NOT NULL,
  `payment_method` ENUM('Cash', 'Bank Transfer', 'Mobile Banking', 'Cheque') NOT NULL DEFAULT 'Bank Transfer',
  `transaction_ref` VARCHAR(100) DEFAULT NULL,
  `payment_date` DATE NOT NULL,
  `bank_name` VARCHAR(100) DEFAULT NULL,
  `disbursed_by` INT(11) NOT NULL COMMENT 'Accounts User ID',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`claim_id`),
  INDEX (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

 Technical Architecture & Proposed Changes
Yes
Click Claim
Select Non-Sales Employee & Project Sale
Review & Approve
Make Payout
Lead Booking Completed
Booking & Registration Paid?
System Flags Booking as Eligible for Incentive
Sales Person Portal: My Incentives
Claim Status -> Claimed
Management Manual Assignment
Claim Created: is_external = 1
Accounts & Management Portal: Incentive Management
Claim Status -> Approved
Record Incentive Payment
Update Claim Status: Paid / Partially Paid



ডাইনামিক কমিশন মডিউল ইমপ্লিমেন্টেশন প্ল্যান (Implementation Plan)
আপনার দেওয়া উত্তরের ওপর ভিত্তি করে প্ল্যানটি পুরোপুরি আপডেট করা হয়েছে। এখন কোনো কনফিউশন নেই।

আমাদের কাজের ধাপসমূহ (Proposed Changes)
ধাপ ১: ডাটাবেস আপডেট (Database Schema Updates)
ডাইনামিক রুলসগুলো (যেমন: ইনসেনটিভের ক্যাপ, টিম লিডারের টার্গেট) সেভ করার জন্য আমাদের ডাটাবেসের কয়েকটি টেবিলে পরিবর্তন আনতে হবে।

১. incentive_slabs টেবিল
ইনসেনটিভের সর্বোচ্চ ও সর্বনিম্ন সীমা (Cap) যুক্ত করা হবে।

[MODIFY] নতুন কলাম: lowest_incentive_cap (সর্বনিম্ন ইনসেনটিভ)
[MODIFY] নতুন কলাম: highest_incentive_cap (সর্বোচ্চ ইনসেনটিভ)
২. sales_targets টেবিল
বর্তমানে টার্গেট দেওয়া হয় প্রজেক্ট অনুযায়ী। এখন থেকে এটি ক্যাটাগরি অনুযায়ী হবে।

[MODIFY] নতুন কলাম: category_id যুক্ত করা হবে, যাতে প্রজেক্টের বদলে ক্যাটাগরি (যেমন: Ashulia Model Town - Condominium) অনুযায়ী টার্গেট সেট করা যায়।
৩. নতুন টেবিল: incentive_eligibility_rules
০-২০%, ২১-৫০%, ৫১-৭০% এবং ৭০%+ অর্জনের ওপর ভিত্তি করে কে কত শতাংশ ইনসেনটিভ পাবে, তা ডাইনামিক করার জন্য এই টেবিলটি তৈরি করা হবে।

কলামসমূহ: id, rule_type (এক্সিকিউটিভ, টিম লিডার ইত্যাদি), min_achievement_pct (সর্বনিম্ন অর্জন %), max_achievement_pct (সর্বোচ্চ অর্জন %), payout_pct (প্রাপ্ত ইনসেনটিভের হার)।
৪. hierarchy_commissions টেবিল
টিম লিডার এবং কো-অর্ডিনেটরদের "প্রতি ৫০ লাখ টাকার জন্য" যে নির্দিষ্ট কমিশন দেওয়ার নিয়ম, সেটি ডায়নামিক করার জন্য।

[MODIFY] amount_per_50_lakh কলামের নাম পরিবর্তন করে reward_amount রাখা হবে।
[NEW] নতুন কলাম: collection_threshold_amount যুক্ত করা হবে (যার ডিফল্ট মান হবে ৫০ লাখ)। ভবিষ্যতে এটি পরিবর্তন করে ৬০ বা ৭০ লাখও করা যাবে।
ধাপ ২: মডিউল ইন্টারফেস বা উইন্ডো আপডেট (UI/UX)
অ্যাডমিন যাতে খুব সহজেই এই রুলসগুলো কন্ট্রোল করতে পারেন, সেজন্য উইন্ডোগুলোতে কিছু পরিবর্তন আসবে।

১. টার্গেট অ্যাসাইনমেন্ট মডিউল (Target Assignment)
টার্গেট দেওয়ার পেজে এমপ্লয়ি এবং মাসের পাশাপাশি এখন থেকে ক্যাটাগরি সিলেক্ট করার অপশন থাকবে (আগে প্রজেক্ট সিলেক্ট করার অপশন ছিল)।
এখানে ম্যানেজার ম্যানুয়ালি টার্গেটের পরিমাণটি (Amount) বসিয়ে দেবেন।
২. ইনসেনটিভ স্লাব কনফিগারেশন (Incentive Slabs Setup)
স্লাব তৈরির ফর্মে "Lowest Incentive" এবং "Highest Incentive" বসানোর অপশন যোগ করা হবে।
পার্সেন্টেজ রুলস (যেমন: ২১-৫০% অর্জন করলে ২০% ইনসেনটিভ পাবে) সেট করার জন্য নতুন একটি সেটিংস পেজ তৈরি করা হবে।
৩. হায়ারার্কি কমিশন সেটিংস (Hierarchy Commission Settings)
টিম লিডার ও সেলস হেডদের কমিশন সেটিংসে collection_threshold_amount (যেমন: ৫০ লাখ) এবং reward_amount (যেমন: ২০০০ টাকা) ইনপুট দেওয়ার ব্যবস্থা করা হবে।
ধাপ ৩: ইনসেনটিভ ক্যালকুলেশনের মূল লজিক (Core Logic)
ক্যালকুলেশনের পুরো হিসাব-নিকাশ ব্যাকএন্ডে (যেমন: IncentiveCalculationService.php-তে) স্বয়ংক্রিয়ভাবে হবে।

৩.১: সেলস এক্সিকিউটিভ (KAM/KAE) ক্যালকুলেশন
১. মোট অর্জন (Achievement): নগদ টাকার হিসাবের বদলে, ওই মাসে বুকিং হওয়া ফ্ল্যাটগুলোর ল্যান্ড শেয়ারের মূল্য (Land Share Value) যোগ করে কর্মীর মোট অর্জন হিসাব করা হবে। ২. অর্জনের পার্সেন্টেজ: (মোট ল্যান্ড শেয়ার ভ্যালু / অ্যাসাইন করা টার্গেট অ্যামাউন্ট) * ১০০। ৩. অ্যাসাইনড প্রজেক্টের হিসাব:

পার্সেন্টেজ অনুযায়ী তার এলিজিবিলিটি চেক করা হবে (যেমন: ৬০% অর্জন করলে সে ৫১-৭০% এর ব্র্যাকেটে পড়বে, অর্থাৎ সে ৫০% ইনসেনটিভ পাবে)।
ল্যান্ড শেয়ারের ভলিউম অনুযায়ী স্লাব রেট (০.৫০%, ০.৭৫% ইত্যাদি) বের করা হবে। ৪. আন-অ্যাসাইনড প্রজেক্টের হিসাব:
যদি সে অ্যাসাইনড প্রজেক্টের নূন্যতম ক্রাইটেরিয়া পূরণ না করে (অর্থাৎ ইনসেনটিভ ০% থাকে): তাহলে সে আন-অ্যাসাইনড প্রজেক্টের জন্য কোনো ইনসেনটিভ পাবে না।
যদি সে নূন্যতম ক্রাইটেরিয়া পূরণ করে (অর্থাৎ ইনসেনটিভ ০% এর বেশি হয়): তাহলে তার আন-অ্যাসাইনড বুকিংয়ের ল্যান্ড শেয়ার ভ্যালুকে অ্যাসাইনড ল্যান্ড শেয়ার ভ্যালুর সাথে যোগ করে একটি বড় স্লাব নির্ধারণ করা হবে এবং সেই অনুযায়ী আন-অ্যাসাইনড ইনসেনটিভ হিসাব করা হবে। ৫. ক্যাপ (Cap) প্রয়োগ: সবশেষে ইনসেনটিভের সর্বনিম্ন ও সর্বোচ্চ সীমা (Highest/Lowest Cap) মিলিয়ে দেখা হবে।
৩.২: টিম লিডার ও কো-অর্ডিনেটর ক্যালকুলেশন
১. তাদের টিমের মোট টার্গেট এবং মোট বুকিংয়ের ল্যান্ড শেয়ার ভ্যালু বের করা হবে। ২. অর্জনের পার্সেন্টেজ হিসাব করা হবে। ৩. এলিজিবিলিটি রুল: ৫০% এর নিচে হলে কোনো টাকা নেই। ৫১-৬৯% হলে টাকার অর্ধেক (৫০%) পাবে। ৭০% বা
 তার বেশি হলে পুরো টাকা পাবে। ৪. অ্যামাউন্ট হিসাব: (টিমের মোট ল্যান্ড শেয়ার ভ্যালু / ৫০ লাখ) * ২০০০ টাকা। এই হিসাবের ওপর উপরের এলিজিবিলিটি পার্সেন্টেজ প্রয়োগ করা হবে।

৩.৩: হেড অফ সেলস ক্যালকুলেশন
১. পুরো কোম্পানির মোট টার্গেট এবং মোট ল্যান্ড শেয়ার ভ্যালু বের করা হবে। ২. টিম লিডারের মতোই (৩.২ ধাপের মতো) পুরো কোম্পানির কালেকশনের ওপর ভিত্তি করে তার হিসাব হবে।

ভেরিফিকেশন প্ল্যান (Testing)
সব কাজ শেষ হওয়ার পর আমরা ৩টি ভিন্ন ভিন্ন পরিস্থিতি তৈরি করে টেস্ট করবো:

টেস্ট ১: একজন কর্মী তার টার্গেটের মাত্র ১০% অর্জন করেছে। (সে ০% ইনসেনটিভ পাবে এবং আন-অ্যাসাইনড প্রজেক্টের সুবিধাও পাবে না)।
টেস্ট ২: একজন কর্মী ১০০% টার্গেট পূরণ করেছে এবং আন-অ্যাসাইনড প্রজেক্টেও সেল করেছে। (তার দুটি ল্যান্ড শেয়ার ভ্যালু যোগ হয়ে বড় স্লাব অনুযায়ী ইনসেনটিভ পাবে)।
টেস্ট ৩: একজন টিম লিডারের টিম ৬০% টার্গেট পূরণ করেছে। (সে ৫০ লাখের শর্ত অনুযায়ী মোট টাকার ৫০% পাবে)।
Agent
TPHL-ERP


AI may make mistakes. Double-check all generated code.



ডায়নামিক সেলস ইনসেনটিভ ও টার্গেট ইমপ্লিমেন্টেশন প্ল্যান (Two-Tier Real-Time)
আপনার দেওয়া নিয়মগুলো একদম পরিষ্কার। এখানে ২ টি আলাদা লেয়ার (Tier) বা ধাপে ইনসেনটিভ হিসাব করা হবে। একজন কর্মী তার ডেজিগনেশন অনুযায়ী দুটি ইনসেনটিভই পেতে পারেন।

ইনসেনটিভ রুলস ও লজিক (Incentive Logic)
১. ব্যক্তিগত টার্গেট ইনসেনটিভ (Personal/Individual Incentive)
কাদের জন্য প্রযোজ্য: Sales Executive, Team Lead, Sales Head, Coordinator — অর্থাৎ যার নামেই ব্যক্তিগত টার্গেট (sales_targets) দেওয়া থাকবে, তিনি এই ইনসেনটিভ পাবেন। হিসাবের নিয়ম:

কর্মী তার নিজের করা সেলসের (Land Share Value) ওপর ভিত্তি করে ব্যক্তিগত টার্গেট কতটুকু পূরণ করেছে তা দেখা হবে।
০ - ২০% অর্জন: কোনো ইনসেনটিভ পাবেন না।
২১% বা তার বেশি অর্জন: তিনি স্ল্যাব অনুযায়ী নির্ধারিত ইনসেনটিভ পাবেন (যেমন: ২১-৫০% এর জন্য ২০%, ৫১-৭০% এর জন্য ৫০%, এবং ৭০%+ এর জন্য ১০০%)।
২. টিম বা গ্লোবাল টার্গেট ইনসেনটিভ (Team/Global Incentive)
কাদের জন্য প্রযোজ্য: Sales Manager, Coordinator, Sales Head — যাদের আন্ডারে টিম আছে বা যারা পুরো সেলস টিমের দায়িত্বে আছেন। হিসাবের নিয়ম:

যাদের নিজস্ব টিম আছে (Team Lead/Manager): তাদের পুরো টিমের মোট টার্গেট এবং টিমের মোট বিক্রির হিসাব করে টিম অ্যাচিভমেন্ট পার্সেন্টেজ বের করা হবে।
যাদের নির্দিষ্ট টিম নেই কিন্তু পুরো সেলসের দায়িত্বে আছেন (Sales Head): পুরো কোম্পানির সেলস টিমের সবার টার্গেট এবং মোট বিক্রির হিসাব করে গ্লোবাল অ্যাচিভমেন্ট পার্সেন্টেজ বের করা হবে।
০ - 
৫০% অর্জন: টিমের এই অর্জনের ওপর তিনি কোনো ইনসেনটিভ পাবেন না।
৫১% বা তার বেশি অর্জন: তিনি টিম ইনসেনটিভ রুলস অনুযায়ী ইনসেনটিভ পাবেন (যেমন: ৫১-৬৯% হলে ৫০% এবং ৭০%+ হলে ১০০% — যা প্রতি ৫০ লক্ষ টাকার সেলসের ওপর একটি নির্দিষ্ট এমাউন্ট, যেমন: ১০০০ বা ২০০০ টাকা হতে পারে)।
আমরা যেভাবে ইমপ্লিমেন্ট করবো (Implementation Workflow)
১. ડায়নামিক সার্ভিস মেকানিজম (app/services/IncentiveCalculationService.php)
আমরা একটি সার্ভিস ক্লাস তৈরি করবো যা রিয়েল-টাইমে এই দুটি ইনসেনটিভ আলাদাভাবে হিসাব করবে:

বুকিং হওয়ার সাথে সাথে সিস্টেম চেক করবে যে সেলস পার্সনের ব্যক্তিগত টার্গেট কতটুকু পূরণ হয়েছে এবং তাকে ব্যক্তিগত ইনসেনটিভ দেবে।
এরপর সিস্টেম চেক করবে তার টিম লিডার বা সেলস হেড কে আছেন। তাদের টিমের মোট টার্গেট পূরণের হার চেক করে রিয়েল-টাইমে তাদের অ্যাকাউন্টে টিম ইনসেনটিভ যোগ করবে।
২. ডাটাবেস চেক (Database Setup)
আমরা নিশ্চিত করবো যে ডাটাবেসের incentive_eligibility_rules টেবিলে ব্যক্তিগত (০-২০% = ০) এবং টিমের (০-৫০% = ০) রুলসগুলো আলাদাভাবে কনফিগার করা আছে।
৩. ম্যানেজমেন্ট ভিউ (app/views/incentives/manage-incentives.php)
একাউন্টস ও ম্যানেজমেন্টের ভিউতে একটি মাস সিলেক্ট করার অপশন (Month Filter) যুক্ত করবো, যা ডিফল্টভাবে বর্তমান মাস সিলেক্ট করে রাখবে।
ম্যানেজমেন্ট দেখতে পাবে কে "Individual Incentive" পেয়েছে এবং কে "Team Incentive" পেয়েছে।
৪. ডেমো ও টেস্টিং (Demo Booking Script)
আমরা একটি test_demo_booking.php স্ক্রিপ্ট লিখবো যা ডামি বুকিং তৈরি করবে। এটি রান করলে আপনি দেখতে পাবেন যে সেলস হেড বা টিম লিডার তার নিজের সেলের জন্য "ব্যক্তিগত ইনসেনটিভ" পাচ্ছেন এবং টিমের সেলের জন্য আলাদাভাবে "টিম ইনসেনটিভ" পাচ্ছেন।
অনুমোদন (Approval)
আপনার দেওয়া এই পারফেক্ট লজিক অনুযায়ী আমি ইমপ্লিমেন্টেশন প্ল্যানটি আপডেট করেছি। প্ল্যানটি সঠিক মনে হলে 'Proceed' বাটনে ক্লিক করুন, আমি এখনই কোড লেখা এবং ডেমো স্ক্রিপ্ট তৈরি করা 
 করে দেবো!
