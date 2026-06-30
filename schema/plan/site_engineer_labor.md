Labor Requisition Module — Implementation Plan
Background
Site Engineer রা প্রতিটি project phase-এ daily labor দরকার হয়। এখন পর্যন্ত এই labor cost manually track হচ্ছিল। এই module-এ Site Engineer labor requisition submit করবে, Project Manager approve করবে, Management payment approve করবে এবং Accounts payment করবে।

Approval Flow (৪ ধাপ)

Site Engineer
  → Labor Requisition Submit (status: pending)
       ↓
Project Manager
  → Review → Approve / Reject (status: pm_approved / rejected)
       ↓
Site Engineer / PM
  → "Payment Request" Button Click (status: payment_requested)
       ↓
Management
  → Payment Approve (status: management_approved)
       ↓
Accounts
  → Payment Done (status: paid)
         paid_amount, paid_by, payment_method, payment_date
Open Questions / Decisions
IMPORTANT

Labor Type: শুধু Daily Labor নাকি Skilled/Unskilled/Supervisor আলাদা category থাকবে? (নিচে labor_type enum রেখেছি — optional)

IMPORTANT

Multiple Payment? একটা requisition-এ কি partial payment হতে পারে? (এখন single payment ধরছি — প্রয়োজনে payment_logs table পরে যোগ করা যাবে)

NOTE

Phase Target Link: phase_target_id optional রাখা হয়েছে। এটা phase_monthly_targets table-এর FK হবে — কোন মাসের target-এর বিপরীতে এই labor লাগছে তা track করার জন্য।

Proposed Changes
Database (SQL)
[NEW] con_labor_requisitions — Main table
sql

CREATE TABLE `con_labor_requisitions` (
  `id`                       int(10) unsigned NOT NULL AUTO_INCREMENT,
  `requisition_no`           varchar(30) DEFAULT NULL COMMENT 'Auto-generated: LR-2026-0001',
  `project_id`               int(11) NOT NULL,
  `phase_id`                 int(10) unsigned DEFAULT NULL,
  `phase_target_id`          int(10) unsigned DEFAULT NULL COMMENT 'Optional: phase_monthly_targets.id',
  `requested_by`             int(11) NOT NULL COMMENT 'Site Engineer — users.id',
  `requisition_date`         date NOT NULL,
  `work_date_from`           date DEFAULT NULL,
  `work_date_to`             date DEFAULT NULL,
  `labor_type`               enum('General','Skilled','Unskilled','Supervisor','Mason','Helper','Other') DEFAULT 'General',
  `labor_count`              int(11) NOT NULL DEFAULT 1,
  `per_labor_rate`           decimal(10,2) NOT NULL DEFAULT 0.00,
  `working_days`             decimal(5,2) NOT NULL DEFAULT 1 COMMENT 'কতদিনের জন্য',
  `total_amount`             decimal(15,2) GENERATED ALWAYS AS (labor_count * per_labor_rate * working_days) STORED,
  `purpose`                  text NOT NULL,
  `remarks`                  text DEFAULT NULL,
  `attachment`               varchar(255) DEFAULT NULL COMMENT 'Optional proof photo/doc',
  -- Project Manager Approval
  `pm_approved_by`           int(11) DEFAULT NULL COMMENT 'Project Manager — users.id',
  `pm_approved_at`           datetime DEFAULT NULL,
  `pm_note`                  text DEFAULT NULL,
  -- Payment Request
  `payment_requested_by`     int(11) DEFAULT NULL,
  `payment_requested_at`     datetime DEFAULT NULL,
  -- Management Approval
  `management_approved_by`   int(11) DEFAULT NULL COMMENT 'Management — users.id',
  `management_approved_at`   datetime DEFAULT NULL,
  `management_note`          text DEFAULT NULL,
  `approved_amount`          decimal(15,2) DEFAULT NULL COMMENT 'Management may adjust amount',
  -- Accounts Payment
  `paid_amount`              decimal(15,2) DEFAULT NULL,
  `paid_by`                  int(11) DEFAULT NULL COMMENT 'Accounts — users.id',
  `payment_date`             date DEFAULT NULL,
  `payment_method`           enum('Cash','Bank Transfer','Mobile Banking','Cheque') DEFAULT NULL,
  `payment_reference`        varchar(100) DEFAULT NULL COMMENT 'Cheque no / bKash TxnID etc.',
  `payment_note`             text DEFAULT NULL,
  -- Status Flow
  `status` enum(
    'pending',             -- Site Engineer submitted
    'pm_approved',         -- PM approved
    'rejected',            -- PM/Management rejected
    'payment_requested',   -- Payment request sent to Management
    'management_approved', -- Management approved payment
    'paid'                 -- Accounts paid
  ) NOT NULL DEFAULT 'pending',
  `rejection_reason`         text DEFAULT NULL,
  `created_by`               int(11) NOT NULL,
  `created_at`               timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at`               timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_requisition_no` (`requisition_no`),
  KEY `idx_clr_project`    (`project_id`),
  KEY `idx_clr_phase`      (`phase_id`),
  KEY `idx_clr_target`     (`phase_target_id`),
  KEY `idx_clr_requested`  (`requested_by`),
  KEY `idx_clr_status`     (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Site Engineer এর labor requisition এবং payment tracking';
[NEW] con_labor_payment_logs — Payment History (optional but recommended)
sql

CREATE TABLE `con_labor_payment_logs` (
  `id`              int(10) unsigned NOT NULL AUTO_INCREMENT,
  `requisition_id`  int(10) unsigned NOT NULL,
  `amount`          decimal(15,2) NOT NULL,
  `paid_by`         int(11) NOT NULL,
  `payment_date`    date NOT NULL,
  `payment_method`  enum('Cash','Bank Transfer','Mobile Banking','Cheque') DEFAULT 'Cash',
  `reference`       varchar(100) DEFAULT NULL,
  `note`            text DEFAULT NULL,
  `created_at`      timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_clpl_req` (`requisition_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
[MODIFY] con_financial_transactions — ref_type তে labor_requisition add করতে হবে
sql

-- ref_type enum-এ 'labor_requisition' যোগ করো
ALTER TABLE con_financial_transactions 
  MODIFY COLUMN ref_type enum(
    'contractor_bill','vendor_payment','fund_request',
    'labor_requisition'  -- ← নতুন
  ) DEFAULT NULL;
Backend (PHP Models & Controllers)
[NEW] app/models/Con_LaborRequisition.php
getAll($filters) — project, status, date filter
getBySiteEngineer($userId) — site engineer নিজের list দেখবে
find($id) — full detail with joins
create($data) — auto-generate requisition_no (LR-YYYY-XXXX)
pmApprove($id, $pmId, $note) — PM approve
pmReject($id, $pmId, $reason) — PM reject
requestPayment($id, $userId) — payment request button
managementApprove($id, $mgmtId, $approvedAmount, $note) — management approve
markPaid($id, $data) — accounts payment + log entry
[NEW] app/controllers/LaborRequisitionController.php
index() — list (role-based filter)
create() / store() — site engineer form
show($id) — detail page
pmApprove($id) / pmReject($id) — PM actions
requestPayment($id) — payment request trigger
managementApprove($id) — management action
markPaid($id) — accounts payment
Frontend (Views)
[NEW] app/views/phase2/labor_requisition/
File	কাজ
index.php	List সব requisitions (role-based columns)
create.php	Site Engineer form
show.php	Detail + Approval timeline
payment_form.php	Accounts payment করার form
Status Flow Visual

[pending] 
  → PM Approve → [pm_approved]
  → PM Reject  → [rejected]
[pm_approved]
  → "Payment Request" button → [payment_requested]
[payment_requested]
  → Management Approve → [management_approved]
  → Management Reject  → [rejected]
[management_approved]
  → Accounts Pay → [paid]
Verification Plan
SQL Test
con_labor_requisitions table সঠিকভাবে তৈরি হয়েছে কিনা
Generated column total_amount সঠিক calculate করছে কিনা
requisition_no auto-generate সঠিক কিনা
Manual Verification
Site Engineer login → Labor Requisition create করো
Project Manager login → Approve করো
"Payment Request" button দেখা যাচ্ছে কিনা
Management login → Payment approve করো
Accounts login → Payment mark as paid করো
Status timeline সঠিক দেখাচ্ছে কিনা