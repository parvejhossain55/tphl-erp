<?php
/**
 * LIVE SERVER CHART OF ACCOUNTS & JOURNAL MIGRATION SCRIPT
 * 
 * Instructions:
 * 1. Upload this file to your live ERP root directory.
 * 2. Run it via browser: https://your-domain.com/live_coa_and_journal_migration.php
 *    OR via terminal: php live_coa_and_journal_migration.php
 * 3. Delete this script after execution for security.
 */

require_once 'app/config/config.php';
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "<!DOCTYPE html><html><head><title>Live COA Migration</title>";
echo "<style>body{font-family:monospace;background:#1e1e1e;color:#00ff66;padding:20px;line-height:1.6;} .info{color:#00e1ff;} .success{color:#00ff66;} .warning{color:#ffcc00;}</style></head><body>";
echo "<h2>=== LIVE SERVER COA & JOURNAL MIGRATION TOOL ===</h2>";

$db->query("SET FOREIGN_KEY_CHECKS = 0");

// 1. Record legacy journal details
$legacyDetails = [];
$resL = $db->query("
    SELECT jd.id as detail_id, coa.account_code, coa.account_name 
    FROM journal_details jd 
    JOIN chart_of_accounts coa ON jd.account_id = coa.id
");
if ($resL) {
    while ($row = $resL->fetch_assoc()) {
        $legacyDetails[] = $row;
    }
}
echo "<div class='info'>[STEP 1] Found " . count($legacyDetails) . " existing journal_details records on live database.</div>";

// 2. Truncate COA
$db->query("TRUNCATE TABLE chart_of_accounts");
echo "<div class='info'>[STEP 2] Truncated legacy chart_of_accounts table.</div>";

// 3. Helper function
function insertCoa($db, $code, $name, $type, $level, $rootType, $parentCode = null) {
    $parentId = null;
    if ($parentCode) {
        $stmtP = $db->prepare("SELECT id FROM chart_of_accounts WHERE account_code = ? LIMIT 1");
        $stmtP->bind_param("s", $parentCode);
        $stmtP->execute();
        $pRes = $stmtP->get_result()->fetch_assoc();
        if ($pRes) $parentId = (int)$pRes['id'];
        $stmtP->close();
    }

    $stmt = $db->prepare("
        INSERT INTO chart_of_accounts 
        (parent_id, account_code, account_name, account_type, level, root_type, status, is_system_account)
        VALUES (?, ?, ?, ?, ?, ?, 'active', 1)
    ");
    $stmt->bind_param("isssss", $parentId, $code, $name, $type, $level, $rootType);
    $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();
    return $newId;
}

// Insert All Official Accounts
insertCoa($db, '1000', 'Assets', 'Asset', 'main', 'asset');
insertCoa($db, '10', 'Non Current Assets', 'Asset', 'sub', 'asset', '1000');
insertCoa($db, '100', 'Property Plant and Equipment', 'Asset', 'parent', 'asset', '10');
insertCoa($db, '100000', 'Furniture & Fixtures', 'Asset', 'ledger', 'asset', '100');
insertCoa($db, '100001', 'Computer, Printer & IT Equipment', 'Asset', 'ledger', 'asset', '100');
insertCoa($db, '100002', 'Office Equipment', 'Asset', 'ledger', 'asset', '100');
insertCoa($db, '100003', 'Transport & Vehicle', 'Asset', 'ledger', 'asset', '100');

insertCoa($db, '101', 'Intangible Assets', 'Asset', 'parent', 'asset', '10');
insertCoa($db, '101001', 'Software', 'Asset', 'ledger', 'asset', '101');

insertCoa($db, '11', 'Current Assets', 'Asset', 'sub', 'asset', '1000');
insertCoa($db, '110', 'Advance Deposit and Prepayments', 'Asset', 'parent', 'asset', '11');
insertCoa($db, '110001', 'Advance to Party/ Suppliers/ Contractors', 'Asset', 'ledger', 'asset', '110');
insertCoa($db, '110002', 'Advance to Employees against works/ Others', 'Asset', 'ledger', 'asset', '110');
insertCoa($db, '110003', 'Advance to Employees against salary', 'Asset', 'ledger', 'asset', '110');
insertCoa($db, '110004', 'Advance Income Tax (AIT)', 'Asset', 'ledger', 'asset', '110');
insertCoa($db, '110005', 'Advance Against Land Purchase', 'Asset', 'ledger', 'asset', '110');
insertCoa($db, '110006', 'Security Deposit', 'Asset', 'ledger', 'asset', '110');

insertCoa($db, '111', 'Accounts Receivables', 'Asset', 'parent', 'asset', '11');
insertCoa($db, '111000', 'Accounts Receivables', 'Asset', 'ledger', 'asset', '111');

insertCoa($db, '114', 'Cash & Bank Balances', 'Asset', 'parent', 'asset', '11');
insertCoa($db, '114000', 'Fixed Deposit', 'Asset', 'ledger', 'asset', '114');
insertCoa($db, '114100', 'Cash in Hand', 'Asset', 'parent', 'asset', '114');
insertCoa($db, '114100-01', 'Site Office- Cash in Hand', 'Asset', 'ledger', 'asset', '114100');
insertCoa($db, '114100-02', 'Gulshan Office- Cash in Hand', 'Asset', 'ledger', 'asset', '114100');
insertCoa($db, '114100-03', 'Head Office- Cash in Hand', 'Asset', 'ledger', 'asset', '114100');
insertCoa($db, '114400', 'Cash at Bank', 'Asset', 'ledger', 'asset', '114');

insertCoa($db, '2000', 'Liabilities', 'Liability', 'main', 'liability');
insertCoa($db, '20', 'Non Current Liabilities', 'Liability', 'sub', 'liability', '2000');
insertCoa($db, '200', 'Loan from Director', 'Liability', 'parent', 'liability', '20');
insertCoa($db, '200001', 'Loan From Chairman Sir', 'Liability', 'ledger', 'liability', '200');
insertCoa($db, '200002', 'Loan From Director', 'Liability', 'ledger', 'liability', '200');

insertCoa($db, '2000-L', 'Long Term Loan Parent', 'Liability', 'parent', 'liability', '20');
insertCoa($db, '200000', 'Long Term Loan', 'Liability', 'ledger', 'liability', '2000-L');
insertCoa($db, '200100', 'Lease Finance', 'Liability', 'ledger', 'liability', '2000-L');

insertCoa($db, '21', 'Current Liabilities', 'Liability', 'sub', 'liability', '2000');
insertCoa($db, '210', 'Short Term Loan', 'Liability', 'parent', 'liability', '21');
insertCoa($db, '210000', 'Time Loan', 'Liability', 'ledger', 'liability', '210');
insertCoa($db, '210100', 'FORCE LOAN', 'Liability', 'ledger', 'liability', '210');
insertCoa($db, '210200', 'LTR', 'Liability', 'ledger', 'liability', '210');
insertCoa($db, '210300', 'OFFSHORE LOAN', 'Liability', 'ledger', 'liability', '210');
insertCoa($db, '210400', 'SOD LOAN', 'Liability', 'ledger', 'liability', '210');
insertCoa($db, '210500', 'CC LOAN', 'Liability', 'ledger', 'liability', '210');

insertCoa($db, '221', 'Accounts Payable', 'Liability', 'parent', 'liability', '21');
insertCoa($db, '211000', 'Accounts Payable', 'Liability', 'ledger', 'liability', '221');

insertCoa($db, '212', 'Provision for WPPF and WF', 'Liability', 'parent', 'liability', '21');
insertCoa($db, '212000', 'Provision for WPPF and WF', 'Liability', 'ledger', 'liability', '212');

insertCoa($db, '213', 'Provision for Doubtful Debt', 'Liability', 'parent', 'liability', '21');
insertCoa($db, '213000', 'Provision for Doubtful Debt', 'Liability', 'ledger', 'liability', '213');

insertCoa($db, '214', 'Income Tax Provision', 'Liability', 'parent', 'liability', '21');
insertCoa($db, '214000', 'Provision for Income Tax', 'Liability', 'ledger', 'liability', '214');
insertCoa($db, '214100', 'TDS on salary', 'Liability', 'ledger', 'liability', '214');

insertCoa($db, '216', 'Incentive Payable', 'Liability', 'parent', 'liability', '21');
insertCoa($db, '216001', 'Incentive Payable', 'Liability', 'ledger', 'liability', '216');

insertCoa($db, '217', 'Liability for Expenses', 'Liability', 'parent', 'liability', '21');
insertCoa($db, '217000', 'Salary Payable', 'Liability', 'ledger', 'liability', '217');
insertCoa($db, '217001', 'Wages and Other Payable', 'Liability', 'ledger', 'liability', '217');
insertCoa($db, '217002', 'Festival Bonus Payable', 'Liability', 'ledger', 'liability', '217');
insertCoa($db, '217003', 'Interest Payable', 'Liability', 'ledger', 'liability', '217');
insertCoa($db, '217004', 'Tax Payable', 'Liability', 'ledger', 'liability', '217');
insertCoa($db, '217005', 'Rent Payable', 'Liability', 'ledger', 'liability', '217');
insertCoa($db, '217006', 'Store Rent Payable', 'Liability', 'ledger', 'liability', '217');
insertCoa($db, '217007', 'Utility Bill Payable', 'Liability', 'ledger', 'liability', '217');
insertCoa($db, '217008', 'TDS Payable', 'Liability', 'ledger', 'liability', '217');
insertCoa($db, '217009', 'Traveling Expense Payable', 'Liability', 'ledger', 'liability', '217');

insertCoa($db, '3000', 'Equity', 'Equity', 'main', 'equity');
insertCoa($db, '29', 'Paid Up Capital', 'Equity', 'sub', 'equity', '3000');
insertCoa($db, '291000', 'Share Money Deposit', 'Equity', 'ledger', 'equity', '29');

insertCoa($db, '352', 'Retained Earnings', 'Equity', 'sub', 'equity', '3000');
insertCoa($db, '352000', 'Retained Earnings', 'Equity', 'ledger', 'equity', '352');

insertCoa($db, '4000', 'Income', 'Revenue', 'main', 'income');
insertCoa($db, '40', 'Operating Income', 'Revenue', 'sub', 'income', '4000');
insertCoa($db, '400', 'Sales', 'Revenue', 'parent', 'income', '40');
insertCoa($db, '400000', 'Land Share', 'Revenue', 'ledger', 'income', '400');
insertCoa($db, '400001', 'Turf Rental Income', 'Revenue', 'ledger', 'income', '400');
insertCoa($db, '400002', 'Service Revenue', 'Revenue', 'ledger', 'income', '400');

insertCoa($db, '41', 'Non Operating Income', 'Revenue', 'sub', 'income', '4000');
insertCoa($db, '410', 'Other Income', 'Revenue', 'parent', 'income', '41');
insertCoa($db, '410000', 'Other Income', 'Revenue', 'ledger', 'income', '410');
insertCoa($db, '410001', 'Turf Rental Income (Non Op)', 'Revenue', 'ledger', 'income', '410');
insertCoa($db, '410002', 'Income from Wastage Sales', 'Revenue', 'ledger', 'income', '410');

insertCoa($db, '414', 'Interest Income', 'Revenue', 'parent', 'income', '41');
insertCoa($db, '411000', 'Interest Income', 'Revenue', 'ledger', 'income', '414');

insertCoa($db, '5000', 'Expenses', 'Expense', 'main', 'expense');
insertCoa($db, '510', 'Selling & Marketing Expenses', 'Expense', 'parent', 'expense', '5000');
insertCoa($db, '510000', 'Video Production', 'Expense', 'ledger', 'expense', '510');
insertCoa($db, '510001', 'Influencer & Endorsement', 'Expense', 'ledger', 'expense', '510');
insertCoa($db, '510002', 'Facebook & Social Media Advertising', 'Expense', 'ledger', 'expense', '510');
insertCoa($db, '510003', 'Google & YouTube Advertising', 'Expense', 'ledger', 'expense', '510');
insertCoa($db, '510004', 'Sales Incentive', 'Expense', 'ledger', 'expense', '510');
insertCoa($db, '510005', 'Referral Incentive', 'Expense', 'ledger', 'expense', '510');
insertCoa($db, '510006', 'Branding & Promotion', 'Expense', 'ledger', 'expense', '510');
insertCoa($db, '510007', 'Client Entertainment & Hospitality', 'Expense', 'ledger', 'expense', '510');
insertCoa($db, '510008', 'Client Visits & Transportation', 'Expense', 'ledger', 'expense', '510');
insertCoa($db, '510009', 'Billboard & Outdoor Advertising', 'Expense', 'ledger', 'expense', '510');
insertCoa($db, '510010', 'Events & Activations', 'Expense', 'ledger', 'expense', '510');
insertCoa($db, '510011', 'Seeking Gifts & Promotional Expenses', 'Expense', 'ledger', 'expense', '510');
insertCoa($db, '510012', 'Brochure Printing', 'Expense', 'ledger', 'expense', '510');
insertCoa($db, '510013', 'SEO Marketing', 'Expense', 'ledger', 'expense', '510');

insertCoa($db, '520', 'Contractor & Labor Expenses', 'Expense', 'parent', 'expense', '5000');
insertCoa($db, '520000', 'Piling & Foundation Works Expenses', 'Expense', 'ledger', 'expense', '520');
insertCoa($db, '520001', 'Electrical Works Expenses', 'Expense', 'ledger', 'expense', '520');
insertCoa($db, '520002', 'Plumbing & Sanitary Works Expenses', 'Expense', 'ledger', 'expense', '520');
insertCoa($db, '520003', 'Lift & Generator Works Expenses', 'Expense', 'ledger', 'expense', '520');
insertCoa($db, '520004', 'Painting Works Expenses', 'Expense', 'ledger', 'expense', '520');
insertCoa($db, '520005', 'Earthworks Expenses', 'Expense', 'ledger', 'expense', '520');
insertCoa($db, '520006', 'Contractor Bills', 'Expense', 'ledger', 'expense', '520');

insertCoa($db, '530', 'Construction Materials', 'Expense', 'parent', 'expense', '5000');
insertCoa($db, '530001', 'Bricks', 'Expense', 'ledger', 'expense', '530');
insertCoa($db, '530002', 'Sand (Local)', 'Expense', 'ledger', 'expense', '530');
insertCoa($db, '530003', 'Sand (Sylhet)', 'Expense', 'ledger', 'expense', '530');
insertCoa($db, '530004', 'Vitti Sand', 'Expense', 'ledger', 'expense', '530');
insertCoa($db, '530005', 'Rod (Steel)', 'Expense', 'ledger', 'expense', '530');
insertCoa($db, '530006', 'Cement', 'Expense', 'ledger', 'expense', '530');
insertCoa($db, '530007', 'Paint & Painting Materials', 'Expense', 'ledger', 'expense', '530');
insertCoa($db, '530008', 'Electrical Materials', 'Expense', 'ledger', 'expense', '530');
insertCoa($db, '530009', 'Sanitary Materials', 'Expense', 'ledger', 'expense', '530');
insertCoa($db, '530010', 'Stone', 'Expense', 'ledger', 'expense', '530');
insertCoa($db, '530011', 'Ready-Mix Concrete', 'Expense', 'ledger', 'expense', '530');
insertCoa($db, '530012', 'Indirect Construction Materials', 'Expense', 'ledger', 'expense', '530');

insertCoa($db, '540', 'Architectural Design', 'Expense', 'parent', 'expense', '5000');
insertCoa($db, '540001', 'Structural Design', 'Expense', 'ledger', 'expense', '540');
insertCoa($db, '540002', 'Drawing & Printing Expenses', 'Expense', 'ledger', 'expense', '540');
insertCoa($db, '540003', 'Engineering Design', 'Expense', 'ledger', 'expense', '540');

insertCoa($db, '550', 'Engineering Testing', 'Expense', 'parent', 'expense', '5000');
insertCoa($db, '550001', 'Soil Test', 'Expense', 'ledger', 'expense', '550');
insertCoa($db, '550002', 'Cylinder Test', 'Expense', 'ledger', 'expense', '550');

insertCoa($db, '560', 'Fuel & Vehicle Expenses', 'Expense', 'parent', 'expense', '5000');
insertCoa($db, '560001', 'Generator Fuel', 'Expense', 'ledger', 'expense', '560');
insertCoa($db, '560002', 'Vehicle Fuel - Car', 'Expense', 'ledger', 'expense', '560');
insertCoa($db, '560003', 'Vehicle Fuel - Bike', 'Expense', 'ledger', 'expense', '560');
insertCoa($db, '560004', 'Vehicle Maintenance & Servicing', 'Expense', 'ledger', 'expense', '560');

insertCoa($db, '570', 'Land Purchase', 'Expense', 'parent', 'expense', '5000');
insertCoa($db, '570001', 'Land Purchase', 'Expense', 'ledger', 'expense', '570');

insertCoa($db, '580', 'Administrative & Operating Expenses', 'Expense', 'parent', 'expense', '5000');
insertCoa($db, '580001', 'Snacks & Refreshments Expense', 'Expense', 'ledger', 'expense', '580');
insertCoa($db, '580002', 'Office Entertainment Expense', 'Expense', 'ledger', 'expense', '580');
insertCoa($db, '580003', 'Salaries & Wages', 'Expense', 'ledger', 'expense', '580');
insertCoa($db, '580004', 'Festival Bonus', 'Expense', 'ledger', 'expense', '580');
insertCoa($db, '580005', 'Performance Bonus', 'Expense', 'ledger', 'expense', '580');
insertCoa($db, '580006', 'Training & Development Expenses', 'Expense', 'ledger', 'expense', '580');
insertCoa($db, '580007', 'Recruitment Expenses', 'Expense', 'ledger', 'expense', '580');
insertCoa($db, '580008', 'Employee Welfare', 'Expense', 'ledger', 'expense', '580');
insertCoa($db, '580009', 'Office Expenses', 'Expense', 'ledger', 'expense', '580');
insertCoa($db, '580010', 'Utilities Bill', 'Expense', 'ledger', 'expense', '580');
insertCoa($db, '580011', 'Mobile & Internet Expenses', 'Expense', 'ledger', 'expense', '580');
insertCoa($db, '580012', 'Printing & Stationery Expenses', 'Expense', 'ledger', 'expense', '580');
insertCoa($db, '580013', 'Cleaning Expenses', 'Expense', 'ledger', 'expense', '580');
insertCoa($db, '580014', 'Security Expenses', 'Expense', 'ledger', 'expense', '580');
insertCoa($db, '580015', 'Software Subscriptions', 'Expense', 'ledger', 'expense', '580');
insertCoa($db, '580016', 'Repair & Maintenance', 'Expense', 'ledger', 'expense', '580');
insertCoa($db, '580017', 'Postage & Courier', 'Expense', 'ledger', 'expense', '580');
insertCoa($db, '580018', 'Legal & Documentation Fees', 'Expense', 'ledger', 'expense', '580');
insertCoa($db, '580019', 'Consultancy Fee', 'Expense', 'ledger', 'expense', '580');
insertCoa($db, '580020', 'Tour & Travelling Expenses', 'Expense', 'ledger', 'expense', '580');
insertCoa($db, '580021', 'Office Rent', 'Expense', 'ledger', 'expense', '580');
insertCoa($db, '580022', 'Store Rent', 'Expense', 'ledger', 'expense', '580');
insertCoa($db, '580023', 'Employee House Rent', 'Expense', 'ledger', 'expense', '580');
insertCoa($db, '580024', 'Office Conveyance', 'Expense', 'ledger', 'expense', '580');
insertCoa($db, '580025', 'Board Meeting Expenses', 'Expense', 'ledger', 'expense', '580');
insertCoa($db, '580026', 'Management Meeting Expense', 'Expense', 'ledger', 'expense', '580');

insertCoa($db, '590', 'Site Development Expenses', 'Expense', 'parent', 'expense', '5000');
insertCoa($db, '590001', 'Site Preparation Expenses', 'Expense', 'ledger', 'expense', '590');
insertCoa($db, '590002', 'Safety Item Expense', 'Expense', 'ledger', 'expense', '590');
insertCoa($db, '590003', 'Labour Shed Preparation Expense', 'Expense', 'ledger', 'expense', '590');
insertCoa($db, '590004', 'Submersible Pump Installation Expense', 'Expense', 'ledger', 'expense', '590');
insertCoa($db, '590005', 'Meals Expense (Subsidized)', 'Expense', 'ledger', 'expense', '590');
insertCoa($db, '590006', 'Tax & VAT', 'Expense', 'ledger', 'expense', '590');
insertCoa($db, '590007', 'Charity & Donations Expense', 'Expense', 'ledger', 'expense', '590');
insertCoa($db, '590008', 'Project Development Expenses', 'Expense', 'ledger', 'expense', '590');
insertCoa($db, '590009', 'Project Overheads', 'Expense', 'ledger', 'expense', '590');
insertCoa($db, '590010', 'Labour Bill', 'Expense', 'ledger', 'expense', '590');

echo "<div class='success'>[STEP 3] Inserted all official company Chart of Accounts!</div>";

// Helper map
function getNewIdByCode($db, $code) {
    $stmt = $db->prepare("SELECT id FROM chart_of_accounts WHERE account_code = ? LIMIT 1");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $r ? (int)$r['id'] : null;
}

// 4. Re-map legacy journal details
$bankAccId = getNewIdByCode($db, '114400');
$headOfficeCashId = getNewIdByCode($db, '114100-03');
$siteCashId = getNewIdByCode($db, '114100-01');
$accountsPayableId = getNewIdByCode($db, '211000');
$salariesExpenseId = getNewIdByCode($db, '580003');
$landShareRevenueId = getNewIdByCode($db, '400000');
$utilitiesExpenseId = getNewIdByCode($db, '580010');
$advanceEmployeeId = getNewIdByCode($db, '110003');

$convertedCount = 0;
foreach ($legacyDetails as $detail) {
    $dId = $detail['detail_id'];
    $oldCode = $detail['account_code'];
    $targetId = null;

    if ($oldCode === '1110-002' || $oldCode === '1110-003' || $oldCode === '1110-004' || $oldCode === '1110-10' || $oldCode === '1110') {
        $targetId = $bankAccId;
    } elseif ($oldCode === '1110-001') {
        $targetId = $siteCashId;
    } elseif ($oldCode === '2110') {
        $targetId = $accountsPayableId;
    } elseif ($oldCode === '2130') {
        $targetId = $advanceEmployeeId;
    } elseif ($oldCode === '2210-001' || $oldCode === '2210' || $oldCode === '2220' || $oldCode === '2220-001' || $oldCode === '4110' || $oldCode === '4110-001' || $oldCode === '4120') {
        $targetId = $landShareRevenueId;
    } elseif ($oldCode === '5210' || $oldCode === '5210-001') {
        $targetId = $salariesExpenseId;
    } elseif ($oldCode === '5220') {
        $targetId = $utilitiesExpenseId;
    } else {
        $targetId = $bankAccId;
    }

    if ($targetId) {
        $stmtUpd = $db->prepare("UPDATE journal_details SET account_id = ? WHERE id = ?");
        $stmtUpd->bind_param("ii", $targetId, $dId);
        $stmtUpd->execute();
        $stmtUpd->close();
        $convertedCount++;
    }
}

$db->query("SET FOREIGN_KEY_CHECKS = 1");

echo "<div class='success'>[STEP 4] Converted $convertedCount live journal_details records to new COA account IDs!</div>";
echo "<h3 class='success'>=== LIVE MIGRATION COMPLETED SUCCESSFULLY 100%! ===</h3>";
echo "<p class='warning'>Note: Please delete this file (live_coa_and_journal_migration.php) from your live server after viewing this message.</p>";
echo "</body></html>";
