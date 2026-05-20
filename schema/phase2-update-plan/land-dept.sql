mplementation Plan: Land Dept + Petty Cash + Fund Requisition
Date: 18/05/26 | Based on Client Meeting Notes

Overview
তিনটি প্রধান ফিচার implement করতে হবে:

Land Department Module — সম্পূর্ণ নতুন Land Acquisition tracking
Petty Cash Module — সবার জন্য (সব department)
Fund Requisition (General) — সবার জন্য (সব department)
সবকিছু সম্ভব। বর্তমান আর্কিটেকচারের সাথে সম্পূর্ণ compatible।

User Review Required
IMPORTANT

নিচের বিষয়গুলোতে আপনার confirmation দরকার:

Petty Cash: প্রতিটি department-এর জন্য আলাদা budget allocate হবে, নাকি employee যেকোনো সময় request করতে পারবে?
Fund Requisition: Land Dept-এর Fund Requisition আলাদা flow (Accounts → CED), কিন্তু অন্যান্য dept-এর জন্য flow কী হবে? (Dept Head → Accounts → Management?)
CED: এটি কোন role_id? একটি নির্দিষ্ট role আছে নাকি?
Part 1: Land Department Module
ব্যবসায়িক ফ্লো (আপনার নোট অনুযায়ী)

Land Officer
  ↓
① জমি নির্বাচন → "Land Acquisition" তৈরি করে (status: Selected)
  ↓
  Fund Requisition (Bayana টাকার জন্য) → Accounts team দেখবে → CED দেখবে
  ↓
② বায়না payment করে → status: "Bayana"
  ↓
  Registration করার সময় Extra Cost হলে → আরেকটি Fund Requisition
  ↓
③ Registration সম্পন্ন → status: "Registered"
  ↓
  Monthly Report (Project-wise land portfolio summary)
নতুন Database Tables (৫টি)
New Table: land_acquisitions (মূল জমি রেকর্ড)

sql

CREATE TABLE land_acquisitions (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    project_id       INT NOT NULL,
    land_owner_name  VARCHAR(255) NOT NULL,
    land_owner_phone VARCHAR(20),
    land_location    TEXT NOT NULL,
    mouza            VARCHAR(200),
    dag_no           VARCHAR(100),
    khatian_no       VARCHAR(100),
    land_area        DECIMAL(10,4) DEFAULT 0,
    land_area_unit   ENUM('Shotangsh','Decimel','Bigha','Katha','Sqft') DEFAULT 'Shotangsh',
    total_land_value DECIMAL(15,2) DEFAULT 0,
    per_unit_price   DECIMAL(15,2) DEFAULT 0,
    status           ENUM('Selected','Bayana','Registration_In_Progress','Registered','Cancelled') DEFAULT 'Selected',
    notes            TEXT,
    created_by       INT NOT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
New Table: land_bayana_payments (বায়না payment রেকর্ড)

sql

CREATE TABLE land_bayana_payments (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    land_acquisition_id INT NOT NULL,
    bayana_amount       DECIMAL(15,2) NOT NULL,
    payment_date        DATE NOT NULL,
    payment_method      ENUM('Cash','Bank Transfer','Cheque') DEFAULT 'Cash',
    bank_name           VARCHAR(200),
    cheque_no           VARCHAR(100),
    receipt_no          VARCHAR(100),
    attachment          VARCHAR(500),
    notes               TEXT,
    status              ENUM('Pending','Paid') DEFAULT 'Pending',
    created_by          INT NOT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
New Table: land_registrations (রেজিস্ট্রেশন ট্র্যাক)

sql

CREATE TABLE land_registrations (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    land_acquisition_id INT NOT NULL,
    registration_no     VARCHAR(200),
    registration_date   DATE,
    deed_no             VARCHAR(200),
    registration_office VARCHAR(300),
    registration_cost   DECIMAL(15,2) DEFAULT 0,
    govt_fees           DECIMAL(15,2) DEFAULT 0,
    stamp_duty          DECIMAL(15,2) DEFAULT 0,
    lawyer_fees         DECIMAL(15,2) DEFAULT 0,
    other_costs         DECIMAL(15,2) DEFAULT 0,
    document_link       VARCHAR(500),
    status              ENUM('Pending','Completed') DEFAULT 'Pending',
    notes               TEXT,
    created_by          INT NOT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
New Table: land_fund_requisitions (Fund Requisition → Accounts → CED)

sql

CREATE TABLE land_fund_requisitions (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    land_acquisition_id  INT NOT NULL,
    requisition_type     ENUM('Bayana','Registration','Extra Cost','Other') NOT NULL,
    requested_amount     DECIMAL(15,2) NOT NULL,
    purpose              TEXT NOT NULL,
    requested_by         INT NOT NULL,
    -- Accounts Step
    accounts_status      ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    accounts_by          INT DEFAULT NULL,
    accounts_note        TEXT,
    accounts_approved_at TIMESTAMP NULL,
    approved_amount      DECIMAL(15,2) DEFAULT NULL,
    -- CED Step (view only)
    ced_status           ENUM('Pending','Seen') DEFAULT 'Pending',
    ced_by               INT DEFAULT NULL,
    ced_seen_at          TIMESTAMP NULL,
    -- Payment
    payment_status       ENUM('Unpaid','Partial','Paid') DEFAULT 'Unpaid',
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
New Table: land_monthly_reports (Monthly Summary — auto generate)

sql

CREATE TABLE land_monthly_reports (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    project_id          INT NOT NULL,
    report_month        VARCHAR(7) NOT NULL COMMENT 'YYYY-MM format',
    total_lands         INT DEFAULT 0,
    total_area          DECIMAL(10,4) DEFAULT 0,
    total_land_value    DECIMAL(15,2) DEFAULT 0,
    total_bayana_paid   DECIMAL(15,2) DEFAULT 0,
    total_reg_cost      DECIMAL(15,2) DEFAULT 0,
    lands_selected      INT DEFAULT 0,
    lands_bayana        INT DEFAULT 0,
    lands_registered    INT DEFAULT 0,
    lands_cancelled     INT DEFAULT 0,
    summary_notes       TEXT,
    generated_by        INT NOT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
নতুন Files (Land Department)
[NEW] Controller
app/controllers/Phase2/LandAcquisitionController.php

Methods:

index() — Land portfolio list (project-wise ফিল্টার সহ)
create() / store() — নতুন জমি add
edit($id) / update($id) — Edit existing
show($id) — একটি জমির পুরো timeline (status history, bayana, registration)
delete($id) — Soft delete (Cancelled status)
addBayana($id) / storeBayana() — বায়না payment entry করা
addRegistration($id) / storeRegistration() — Registration info entry
createRequisition($id) / storeRequisition() — Fund Requisition submit
approveRequisition($id) — Accounts Approve/Reject
cedView($id) — CED mark as seen
monthlyReport() — Monthly report generate/view
[NEW] Model
app/models/Con_LandAcquisition.php

Methods: getAll(), getAllByProject(), find(), create(), update(), getWithDetails(), getPortfolioSummary(), getMonthlyStats()

[NEW] Views: app/views/phase2/land_acquisition/
File	Purpose
index.php	Portfolio list (table + filter)
create.php	নতুন জমি form
show.php	Detail page + timeline + bayana + registration
bayana-form.php	বায়না payment form
registration-form.php	Registration details form
requisition-form.php	Fund Requisition form
monthly-report.php	Monthly summary report
[MODIFY] Sidebar
Land Dashboard dropdown:


Engineering Department
├── Land Visibility Study  (বর্তমানে আছে)
├── Land Acquisitions      [NEW] ← জমি কেনার portfolio
├── Land Requisitions      [NEW] ← fund requisition list
└── Land Monthly Report    [NEW] ← monthly report
Accounts sidebar (Construction Finance):


Construction Finance
├── ...existing...
└── Land Fund Requisitions [NEW] ← accounts team review করবে
Part 2: Petty Cash Module (সবার জন্য)
NOTE

বর্তমানে cashout_requests table আছে, কিন্তু এটি project-specific এবং অন্য কাজের জন্য। Petty Cash হলো ছোট ছোট দৈনন্দিন খরচের জন্য আলাদা ব্যবস্থা।

ব্যবসায়িক ফ্লো

Employee (যেকোনো dept)
  ↓
Petty Cash Request submit করে (খরচের receipt সহ)
  ↓
Department Head / Line Manager Approve
  ↓
Accounts Team Payment process করে
  ↓
Employee Reimbursed / সরাসরি পেমেন্ট
নতুন Database Tables (২টি)
New Table: petty_cash_requests

sql

--
-- Table structure for table `land_acquisitions`
--
DROP TABLE IF EXISTS `land_acquisitions`;
CREATE TABLE `land_acquisitions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `land_owner_name` varchar(255) NOT NULL,
  `land_owner_phone` varchar(50) DEFAULT NULL,
  `total_area` decimal(12,4) NOT NULL COMMENT 'in decimals/kathas',
  `area_unit` varchar(20) DEFAULT 'decimal',
  `price_per_unit` decimal(15,2) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `location` varchar(500) DEFAULT NULL,
  `status` enum('selected','bayana','registered','cancelled') DEFAULT 'selected',
  `remarks` text DEFAULT NULL,
  `created_by` int NOT NULL COMMENT 'user_id',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `land_bayana_payments`
--
DROP TABLE IF EXISTS `land_bayana_payments`;
CREATE TABLE `land_bayana_payments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `land_acquisition_id` int unsigned NOT NULL,
  `payment_amount` decimal(15,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(100) DEFAULT 'Bank',
  `transaction_no` varchar(100) DEFAULT NULL,
  `bayana_document_link` varchar(500) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int NOT NULL COMMENT 'user_id',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `land_registrations`
--
DROP TABLE IF EXISTS `land_registrations`;
CREATE TABLE `land_registrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `land_acquisition_id` int unsigned NOT NULL,
  `registration_no` varchar(100) NOT NULL,
  `registration_date` date NOT NULL,
  `sub_registry_office` varchar(255) DEFAULT NULL,
  `registration_cost` decimal(15,2) NOT NULL,
  `mutation_status` enum('Pending','Completed') DEFAULT 'Pending',
  `mutation_date` date DEFAULT NULL,
  `deed_document_link` varchar(500) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int NOT NULL COMMENT 'user_id',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `land_fund_requisitions`
--
DROP TABLE IF EXISTS `land_fund_requisitions`;
CREATE TABLE `land_fund_requisitions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `land_acquisition_id` int unsigned NOT NULL,
  `requisition_type` enum('Bayana','Registration','Extra Cost','Other') NOT NULL,
  `requested_amount` decimal(15,2) NOT NULL,
  `purpose` text NOT NULL,
  `requested_by` int NOT NULL COMMENT 'users.id',
  `mgmt_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `mgmt_by` int DEFAULT NULL COMMENT 'users.id',
  `mgmt_note` text DEFAULT NULL,
  `mgmt_approved_at` timestamp NULL DEFAULT NULL,
  `accounts_status` enum('Pending','Paid','Rejected') DEFAULT 'Pending',
  `accounts_by` int DEFAULT NULL COMMENT 'users.id',
  `accounts_note` text DEFAULT NULL,
  `accounts_paid_at` timestamp NULL DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_no` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `land_monthly_reports`
--
DROP TABLE IF EXISTS `land_monthly_reports`;
CREATE TABLE `land_monthly_reports` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `report_month` varchar(7) NOT NULL COMMENT 'YYYY-MM',
  `total_lands` int DEFAULT 0,
  `total_area` decimal(12,4) DEFAULT 0,
  `total_land_value` decimal(15,2) DEFAULT 0,
  `total_bayana_paid` decimal(15,2) DEFAULT 0,
  `total_reg_cost` decimal(15,2) DEFAULT 0,
  `lands_selected` int DEFAULT 0,
  `lands_bayana` int DEFAULT 0,
  `lands_registered` int DEFAULT 0,
  `lands_cancelled` int DEFAULT 0,
  `summary_notes` text DEFAULT NULL,
  `generated_by` int NOT NULL COMMENT 'users.id',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `petty_cash_requests`
--
DROP TABLE IF EXISTS `petty_cash_requests`;
CREATE TABLE `petty_cash_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `department_id` int NOT NULL,
  `project_id` int DEFAULT NULL,
  `requested_by` int NOT NULL COMMENT 'users.id',
  `expense_date` date NOT NULL,
  `expense_category` enum('Transport','Food','Office Supplies','Communication','Site Expense','Repair','Miscellaneous') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `purpose` text NOT NULL,
  `receipt_path` varchar(500) DEFAULT NULL,
  `status` enum('pending','approved','rejected','paid') DEFAULT 'pending',
  `approved_by` int DEFAULT NULL COMMENT 'users.id',
  `approved_at` timestamp NULL DEFAULT NULL,
  `approval_note` text DEFAULT NULL,
  `paid_by` int DEFAULT NULL COMMENT 'users.id',
  `paid_at` timestamp NULL DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'Cash',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

নতুন Files (Petty Cash)
[NEW] Controller
app/controllers/PettyCashController.php (Phase2 namespace নয়, সব dept-এর জন্য)

Methods:

index() — সব request list (role-based filter)
create() / store() — নতুন request
show($id) — Detail
approve($id) / reject($id) — Department head action
processPayment($id) — Accounts team payment করবে
myRequests() — নিজের request list
limitSettings() — Admin dept limit set করবে
[NEW] Model: app/models/PettyCash.php
[NEW] Views: app/views/petty-cash/
File	Purpose
index.php	All requests list
my-requests.php	My own requests
create.php	Submit request form
show.php	Detail + approval action
limit-settings.php	Admin - dept wise limit
[MODIFY] Sidebar (সব dept-এর জন্য)

সব sidebar-এ (HR, Accounts, Construction, Sales, etc.)
└── Petty Cash          [NEW]
    ├── My Requests
    └── All Requests (managers/accounts দেখতে পাবে)
Part 3: Fund Requisition (সবার জন্য)
NOTE

বর্তমানে operational_fund_requests শুধু Construction dept-এর Site Operation-এর জন্য (Site Operator → Management → Accounts)। এটাকে generalize করে সব dept-এর জন্য তৈরি করতে হবে।

ব্যবসায়িক ফ্লো

যেকোনো Employee
  ↓
Fund Requisition submit (purpose, amount, date, project/dept)
  ↓
Department Head Approve
  ↓
Accounts Team Review + Process Payment
  ↓
Management (MD/Director) Final Approve (বড় amount-এর জন্য)
নতুন Database Table (১টি)
New Table: general_fund_requisitions

sql

CREATE TABLE general_fund_requisitions (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    department_id       INT DEFAULT NULL,
    project_id          INT DEFAULT NULL,
    requisition_no      VARCHAR(50) UNIQUE,
    requested_by        INT NOT NULL,
    fund_type           ENUM('Operational','Capital','Emergency','Training',
                             'Event','Travel','Other') DEFAULT 'Operational',
    amount              DECIMAL(15,2) NOT NULL,
    purpose             TEXT NOT NULL,
    required_date       DATE,
    attachment          VARCHAR(500),
    -- Step 1: Dept Head
    dept_status         ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    dept_approved_by    INT DEFAULT NULL,
    dept_approved_at    TIMESTAMP NULL,
    dept_note           TEXT,
    -- Step 2: Accounts
    accounts_status     ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    accounts_by         INT DEFAULT NULL,
    accounts_at         TIMESTAMP NULL,
    accounts_note       TEXT,
    approved_amount     DECIMAL(15,2) DEFAULT NULL,
    -- Step 3: Management (conditional — large amounts)
    mgmt_required       TINYINT(1) DEFAULT 0,
    mgmt_status         ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    mgmt_approved_by    INT DEFAULT NULL,
    mgmt_approved_at    TIMESTAMP NULL,
    -- Payment
    payment_status      ENUM('Unpaid','Partial','Paid') DEFAULT 'Unpaid',
    overall_status      ENUM('pending','approved','rejected','paid') DEFAULT 'pending',
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
নতুন Files (General Fund Requisition)
[NEW] Controller: app/controllers/FundRequisitionController.php
Methods: index(), create(), store(), show($id), deptApprove($id), accountsApprove($id), mgmtApprove($id), processPayment($id), myRequisitions()

[NEW] Model: app/models/GeneralFundRequisition.php
[NEW] Views: app/views/fund-requisitions/
[MODIFY] Sidebar (সব dept sidebar-এ)

Finance / Accounts Section
└── Fund Requisitions    [NEW]
    ├── My Requisitions
    └── All Requisitions (approvers দেখতে পাবে)
Proposed Changes Summary
New Database Tables (৮টি)
Table	Module
land_acquisitions	Land Dept
land_bayana_payments	Land Dept
land_registrations	Land Dept
land_fund_requisitions	Land Dept
land_monthly_reports	Land Dept
petty_cash_requests	Petty Cash
petty_cash_limits	Petty Cash
general_fund_requisitions	Fund Requisition
Files to Create/Modify
Land Department
[NEW] 
LandAcquisitionController.php
[NEW] 
Con_LandAcquisition.php
[NEW] app/views/phase2/land_acquisition/ (৭টি view)
[MODIFY] 
LandVisibilityStudyController.php
 (sidebar update)
[MODIFY] 
tphl_db.sql
 (new tables add)
[MODIFY] 
drop_table.sql
 (new DROP statements)
Petty Cash
[NEW] app/controllers/PettyCashController.php
[NEW] app/models/PettyCash.php
[NEW] app/views/petty-cash/ (৫টি view)
[MODIFY] All sidebar files (petty cash menu add)
Fund Requisition (General)
[NEW] app/controllers/FundRequisitionController.php
[NEW] app/models/GeneralFundRequisition.php
[NEW] app/views/fund-requisitions/ (৪টি view)
[MODIFY] All sidebar files (fund requisition menu add)
[MODIFY] Router / routes file (new routes register)
Implementation Order (সুপারিশকৃত)
Phase	কাজ	অগ্রাধিকার
Phase A	Database tables তৈরি (SQL migration)	প্রথম
Phase B	Land Acquisition Module (Controller + Model + Views)	দ্বিতীয়
Phase C	Land Fund Requisition (Accounts → CED flow)	তৃতীয়
Phase D	Petty Cash Module (Controller + Model + Views)	চতুর্থ
Phase E	General Fund Requisition Module	পঞ্চম
Phase F	Sidebar updates + Routes registration	শেষে
Open Questions
WARNING

কাজ শুরুর আগে নিশ্চিত হতে হবে:

CED Role: CED (Chief Executive Director?) কোন role_id? তাদের শুধু "দেখার" permission থাকবে নাকি approve/reject করার ক্ষমতাও থাকবে?
Petty Cash Approval: Department Head মানে কোন role? প্রতিটি dept-এর জন্য আলাদা approver নাকি সব department-এর জন্য একজন?
General Fund Requisition: অন্যান্য department-এর জন্য ঠিক কোন কোন department এই module ব্যবহার করবে? (HR, Sales, Accounts, Land?)
Amount Threshold: কত টাকার উপরে Management approval লাগবে? (যেমন: ৫০,০০০+ টাকায় MD approval)