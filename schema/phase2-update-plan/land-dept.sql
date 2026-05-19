mplementation Plan: Land Department Module Update
Date: 18/05/26 | Client Request

কী করতে হবে?
Client চান Land Department-এর জন্য সম্পূর্ণ নতুন Land Acquisition Management সিস্টেম। বর্তমান land_visibility_study শুধু জমি পরিদর্শন রিপোর্ট রাখে — এটা দিয়ে জমি কেনার পুরো প্রক্রিয়া track করা যায় না।

Client-এর ৩টি দাবি বিশ্লেষণ
নম্বর	দাবি	মানে কী?
1	Land select → বায়না → Track → Register → Portfolio Track	Land Acquisition Master (জমি কেনার পুরো lifecycle)
2	Requisition → Accounts → CED দেখবে	Fund Requisition (বায়না দেওয়ার জন্য টাকা চাওয়া)
3	Extra Cost → Fund Requisition → Accounts	Extra Cost Request (রেজিস্ট্রেশনের সময় অতিরিক্ত খরচ)
+	Monthly Report	Land Department Monthly Summary
নতুন Database Tables (৫টি)
Table 1: land_acquisitions (মূল জমি রেকর্ড)
sql
CREATE TABLE land_acquisitions (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    project_id       INT NOT NULL,
    land_owner_name  VARCHAR(255) NOT NULL,
    land_location    TEXT NOT NULL,
    land_area        DECIMAL(10,4) DEFAULT 0,
    land_area_unit   VARCHAR(50) DEFAULT 'Shotangsh',
    total_land_value DECIMAL(15,2) DEFAULT 0,
    status           ENUM('Selected','Bayana','Registered','Cancelled') DEFAULT 'Selected',
    notes            TEXT,
    created_by       INT NOT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
Table 2: land_bayana_payments (বায়না পেমেন্ট)
sql
CREATE TABLE land_bayana_payments (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    land_acquisition_id INT NOT NULL,
    bayana_amount       DECIMAL(15,2) NOT NULL,
    payment_date        DATE NOT NULL,
    payment_method      VARCHAR(100),
    receipt_no          VARCHAR(100),
    attachment          VARCHAR(500),
    status              ENUM('Pending','Paid') DEFAULT 'Pending',
    created_by          INT NOT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
Table 3: land_registrations (রেজিস্ট্রেশন ট্র্যাক)
sql
CREATE TABLE land_registrations (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    land_acquisition_id INT NOT NULL,
    registration_no     VARCHAR(200),
    registration_date   DATE,
    registration_cost   DECIMAL(15,2) DEFAULT 0,
    govt_fees           DECIMAL(15,2) DEFAULT 0,
    stamp_duty          DECIMAL(15,2) DEFAULT 0,
    lawyer_fees         DECIMAL(15,2) DEFAULT 0,
    other_costs         DECIMAL(15,2) DEFAULT 0,
    document_link       VARCHAR(500),
    status              ENUM('Pending','Completed') DEFAULT 'Pending',
    created_by          INT NOT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
Table 4: land_fund_requisitions (Fund Requisition → Accounts)
sql
CREATE TABLE land_fund_requisitions (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    land_acquisition_id INT NOT NULL,
    requisition_type    ENUM('Bayana','Registration','Extra Cost') NOT NULL,
    requested_amount    DECIMAL(15,2) NOT NULL,
    purpose             TEXT NOT NULL,
    requested_by        INT NOT NULL,
    accounts_status     ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    accounts_by         INT,
    accounts_note       TEXT,
    ced_status          ENUM('Pending','Seen','Noted') DEFAULT 'Pending',
    ced_by              INT,
    approved_amount     DECIMAL(15,2),
    approved_at         TIMESTAMP NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
Table 5: land_monthly_reports (Monthly Report)
sql
CREATE TABLE land_monthly_reports (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    project_id       INT NOT NULL,
    report_month     VARCHAR(20) NOT NULL,
    total_lands      INT DEFAULT 0,
    total_area       DECIMAL(10,4) DEFAULT 0,
    total_bayana     DECIMAL(15,2) DEFAULT 0,
    total_registered DECIMAL(15,2) DEFAULT 0,
    total_cost       DECIMAL(15,2) DEFAULT 0,
    summary_notes    TEXT,
    created_by       INT NOT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
নতুন Files
[NEW] Controller: app/controllers/Phase2/LandAcquisitionController.php
Methods:

index() — সব land list (Portfolio)
create() / store() — নতুন জমি add
edit() / update() — edit
show() — একটি জমির পুরো detail (timeline)
addBayana() / storeBayana() — বায়না entry
addRegistration() / storeRegistration() — রেজিস্ট্রেশন entry
createRequisition() / storeRequisition() — Fund Requisition
approveRequisition() — Accounts approve
monthlyReport() — Monthly report
[NEW] Model: app/models/Con_LandAcquisition.php
[NEW] Views: app/views/phase2/land_acquisition/
index.php — Portfolio list
create.php — নতুন জমি form
show.php — Detail + timeline
bayana-form.php — বায়না form
registration-form.php — Registration form
requisition-form.php — Fund Requisition form
monthly-report.php — Monthly Report
Workflow
Land Officer
    |
    v
(1) জমি নির্বাচন → "Land Acquisition" create (status: Selected)
    |
    v
Fund Requisition (Bayana) → Accounts-এ পাঠায়
    |
Accounts Approve → CED দেখতে পাবে
    |
    v
(2) বায়না payment entry → status: "Bayana"
    |
    v
Registration করার সময় Extra Cost হলে → Fund Requisition (Extra Cost)
    |
Accounts Approve
    |
    v
(3) Registration complete → status: "Registered"
    |
    v
Monthly Report (সব project summary)
Sidebar Menu Update
Engineering Department dropdown-এ:

Engineering Department
├── Land Visibility Study  (আগের মতো)
├── Land Acquisitions      [NEW] permission: Land Study
├── Fund Requisitions      [NEW] permission: Land Study
└── Land Monthly Report    [NEW] permission: Land Study
Accounts sidebar-এ (Construction Finance):