<?php
// Batch 1 Migration & COA Seeder Script

$config = require __DIR__ . '/../../app/config/config.php';

mysqli_report(MYSQLI_REPORT_OFF); // Disable throwing exceptions on connect fail

$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) {
    echo "Config connection failed ({$config['db_user']}@{$config['db_name']}). Trying local XAMPP root fallback...\n";
    $db = new mysqli("127.0.0.1", "root", "", "tphl_db");
    if ($db->connect_error) {
        die("Fallback Connection Failed: " . $db->connect_error);
    }
}

$db->set_charset("utf8mb4");

echo "=== BATCH 1: MIGRATION & SEEDING STARTED ===\n";

// Helper function to check if column exists
function columnExists($db, $table, $column) {
    $res = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $res && $res->num_rows > 0;
}

// 1. ALTER chart_of_accounts
echo "[1/4] Updating `chart_of_accounts` schema...\n";

if (!columnExists($db, 'chart_of_accounts', 'level')) {
    $db->query("ALTER TABLE `chart_of_accounts` ADD COLUMN `level` ENUM('main', 'sub', 'parent', 'ledger') NOT NULL DEFAULT 'ledger' AFTER `account_type`");
}
if (!columnExists($db, 'chart_of_accounts', 'root_type')) {
    $db->query("ALTER TABLE `chart_of_accounts` ADD COLUMN `root_type` ENUM('asset', 'liability', 'equity', 'income', 'expense') NOT NULL DEFAULT 'asset' AFTER `level`");
}
if (!columnExists($db, 'chart_of_accounts', 'is_system_account')) {
    $db->query("ALTER TABLE `chart_of_accounts` ADD COLUMN `is_system_account` TINYINT(1) DEFAULT 0 COMMENT '1 for system code like 10% Service Fee'");
}
if (!columnExists($db, 'chart_of_accounts', 'project_id')) {
    $db->query("ALTER TABLE `chart_of_accounts` ADD COLUMN `project_id` INT NULL AFTER `parent_id`");
}

// 2. ALTER journals
echo "[2/4] Updating `journals` schema...\n";
if (!columnExists($db, 'journals', 'source_module')) {
    $db->query("ALTER TABLE `journals` ADD COLUMN `source_module` VARCHAR(50) NULL COMMENT 'sales, payroll, requisition, contractor, vendor'");
}
if (!columnExists($db, 'journals', 'source_ref_id')) {
    $db->query("ALTER TABLE `journals` ADD COLUMN `source_ref_id` INT NULL COMMENT 'FK to lead_payments, payslips, contractor_bill_requests etc.'");
}
if (!columnExists($db, 'journals', 'entry_type')) {
    $db->query("ALTER TABLE `journals` ADD COLUMN `entry_type` ENUM('accrual', 'disbursement', 'adjustment') DEFAULT 'disbursement'");
}
if (!columnExists($db, 'journals', 'approval_status')) {
    $db->query("ALTER TABLE `journals` ADD COLUMN `approval_status` ENUM('pending', 'approved', 'rejected') DEFAULT 'approved'");
}

// 3. ALTER journal_details
echo "[3/4] Updating `journal_details` schema...\n";
if (!columnExists($db, 'journal_details', 'project_id')) {
    $db->query("ALTER TABLE `journal_details` ADD COLUMN `project_id` INT NULL AFTER `account_id`");
}
if (!columnExists($db, 'journal_details', 'party_type')) {
    $db->query("ALTER TABLE `journal_details` ADD COLUMN `party_type` VARCHAR(50) NULL COMMENT 'client, vendor, contractor, employee'");
}
if (!columnExists($db, 'journal_details', 'party_id')) {
    $db->query("ALTER TABLE `journal_details` ADD COLUMN `party_id` INT NULL COMMENT 'FK to leads, vendors, contractors, employees'");
}

// 4. CREATE accounting_cash_flow_categories
echo "[4/4] Ensuring `accounting_cash_flow_categories` table exists...\n";
$db->query("
CREATE TABLE IF NOT EXISTS `accounting_cash_flow_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `account_id` INT NOT NULL,
  `cash_flow_type` ENUM('operating', 'investing', 'financing') NOT NULL DEFAULT 'operating',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

echo "✓ DB Schema Alters Successfully Applied!\n";

// 5. Seed Real Estate 4-Tier Chart of Accounts
echo "=== SEEDING STANDARD REAL ESTATE 4-TIER CHART OF ACCOUNTS ===\n";

// Clear existing accounts safely
$db->query("SET FOREIGN_KEY_CHECKS = 0");
$db->query("TRUNCATE TABLE chart_of_accounts");
$db->query("SET FOREIGN_KEY_CHECKS = 1");

$accounts = [
    // [id, parent_id, account_code, account_name, account_type, level, root_type, is_system_account]
    
    // LEVEL 1: MAIN
    [1, null, '1000', 'Assets', 'Asset', 'main', 'asset', 1],
    [2, null, '2000', 'Liabilities', 'Liability', 'main', 'liability', 1],
    [3, null, '3000', 'Equity', 'Equity', 'main', 'equity', 1],
    [4, null, '4000', 'Revenue', 'Revenue', 'main', 'income', 1],
    [5, null, '5000', 'Expenses', 'Expense', 'main', 'expense', 1],

    // LEVEL 2: SUB (Under Assets 1000)
    [10, 1, '1100', 'Current Assets', 'Asset', 'sub', 'asset', 1],
    [11, 1, '1200', 'Non-Current & Fixed Assets', 'Asset', 'sub', 'asset', 1],
    [12, 1, '1300', 'Construction Work-In-Progress (WIP)', 'Asset', 'sub', 'asset', 1],

    // LEVEL 2: SUB (Under Liabilities 2000)
    [20, 2, '2100', 'Current Liabilities', 'Liability', 'sub', 'liability', 1],
    [21, 2, '2200', 'Unearned Revenue & Customer Advances', 'Liability', 'sub', 'liability', 1],

    // LEVEL 2: SUB (Under Equity 3000)
    [30, 3, '3100', "Owner's Capital & Retained Earnings", 'Equity', 'sub', 'equity', 1],

    // LEVEL 2: SUB (Under Revenue 4000)
    [40, 4, '4100', 'Real Estate Operating Revenue', 'Revenue', 'sub', 'income', 1],
    [41, 4, '4200', 'Other Operating Income', 'Revenue', 'sub', 'income', 1],

    // LEVEL 2: SUB (Under Expenses 5000)
    [50, 5, '5100', 'Direct Construction Expenses', 'Expense', 'sub', 'expense', 1],
    [51, 5, '5200', 'Administrative & Payroll Expenses', 'Expense', 'sub', 'expense', 1],

    // LEVEL 3: PARENT / CONTROL ACCOUNTS
    // Under 1100 Current Assets
    [101, 10, '1110', 'Cash and Bank Accounts', 'Asset', 'parent', 'asset', 1],
    [102, 10, '1120', 'Accounts & Client Receivable', 'Asset', 'parent', 'asset', 1],
    // Under 1300 Construction WIP
    [103, 12, '1310', 'Project Civil Construction WIP', 'Asset', 'parent', 'asset', 1],
    [104, 12, '1320', 'Project Material Inventory WIP', 'Asset', 'parent', 'asset', 1],

    // Under 2100 Current Liabilities
    [201, 20, '2110', 'Accounts Payable - Vendors', 'Liability', 'parent', 'liability', 1],
    [202, 20, '2120', 'Accounts Payable - Contractors', 'Liability', 'parent', 'liability', 1],
    [203, 20, '2130', 'Payroll & Employee Payable', 'Liability', 'parent', 'liability', 1],
    // Under 2200 Customer Advances
    [204, 21, '2210', 'Client Land Share Advances', 'Liability', 'parent', 'liability', 1],
    [205, 21, '2220', 'Client Unit Booking Advances', 'Liability', 'parent', 'liability', 1],

    // Under 3100 Capital
    [301, 30, '3110', 'Paid-up Capital', 'Equity', 'parent', 'equity', 1],
    [302, 30, '3120', 'Retained Earnings', 'Equity', 'parent', 'equity', 1],

    // Under 4100 Real Estate Revenue
    [401, 40, '4110', 'Company Service Charge Revenue (10%)', 'Revenue', 'parent', 'income', 1],
    [402, 40, '4120', 'Land & Unit Share Revenue', 'Revenue', 'parent', 'income', 1],

    // Under 5100 Direct Construction
    [501, 50, '5110', 'Contractor Construction Expense', 'Expense', 'parent', 'expense', 1],
    [502, 50, '5120', 'Raw Material Purchase Expense', 'Expense', 'parent', 'expense', 1],
    // Under 5200 Admin Expenses
    [503, 51, '5210', 'Salaries & Payroll Expense', 'Expense', 'parent', 'expense', 1],
    [504, 51, '5220', 'Office & Utilities Expense', 'Expense', 'parent', 'expense', 1],

    // LEVEL 4: LEDGER ACCOUNTS (CHILD ACCOUNTS)
    [1001, 101, '1110-001', 'Petty Cash Account', 'Asset', 'ledger', 'asset', 1],
    [1002, 101, '1110-002', 'Dutch Bangla Bank Main Account', 'Asset', 'ledger', 'asset', 1],
    [1003, 101, '1110-003', 'Islami Bank Main Account', 'Asset', 'ledger', 'asset', 1],
    
    [2001, 204, '2210-001', 'Client Land Share Booking Deposit Fund', 'Liability', 'ledger', 'liability', 1],
    [2002, 205, '2220-001', 'Client EMI Installment Deposit Fund (90%)', 'Liability', 'ledger', 'liability', 1],
    
    [4001, 401, '4110-001', 'Company 10% Service Fee Income', 'Revenue', 'ledger', 'income', 1],
    [5001, 503, '5210-001', 'Monthly Employee Salaries Expense', 'Expense', 'ledger', 'expense', 1],
    [5002, 501, '5110-001', 'Site Contractor Bill WIP Expense', 'Expense', 'ledger', 'expense', 1],
    [5003, 502, '5120-001', 'Material Purchase WIP Expense', 'Expense', 'ledger', 'expense', 1]
];

$stmt = $db->prepare("INSERT INTO chart_of_accounts (id, parent_id, account_code, account_name, account_type, level, root_type, is_system_account, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");

foreach ($accounts as $acc) {
    $stmt->bind_param("iisssssi", $acc[0], $acc[1], $acc[2], $acc[3], $acc[4], $acc[5], $acc[6], $acc[7]);
    if (!$stmt->execute()) {
        echo "Error inserting " . $acc[2] . " - " . $acc[3] . ": " . $stmt->error . "\n";
    } else {
        echo "✓ Inserted [" . strtoupper($acc[5]) . "] " . $acc[2] . " - " . $acc[3] . "\n";
    }
}

$stmt->close();
$db->close();
echo "\n=== BATCH 1: MIGRATION & SEEDING COMPLETED SUCCESSFULLY! ===\n";
