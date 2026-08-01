<?php
// Auto Sync Script: Generates Double-Entry Journal Vouchers for all Expense Vouchers

require_once __DIR__ . '/app/services/AutoPostingService.php';

$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli("127.0.0.1", "root", "", "tphl_db");

if ($db->connect_error) {
    die("Database Connection Failed: " . $db->connect_error);
}

$db->set_charset("utf8mb4");

echo "=========================================================================\n";
echo "=== EXPENSE VOUCHERS GENERAL JOURNAL AUTO-SYNC PROCESS ===\n";
echo "=========================================================================\n\n";

$autoPosting = new AutoPostingService($db);
$bankAccId = $autoPosting->getAccountIdByCode('1110-002'); // DBBL Bank Main Account
$vendorPayableAccId = $autoPosting->getAccountIdByCode('2110'); // AP - Vendors
$contractorPayableAccId = $autoPosting->getAccountIdByCode('2120'); // AP - Contractors
$salaryPayableAccId = $autoPosting->getAccountIdByCode('2130'); // AP - Employees / Payroll / Cashout
$wipExpenseAccId = $autoPosting->getAccountIdByCode('5120-001'); // Material / Construction WIP Expense
$officeExpenseAccId = $autoPosting->getAccountIdByCode('6110-001'); // Office Expense

$syncedCount = 0;

// 1. SYNC FUND REQUISITIONS (FR-6: Loan for bricks - ৳100,000, etc.)
echo "[1] Checking Approved & Paid Fund Requisitions (fund_requisitions table)...\n";
$frRes = $db->query("
    SELECT id, amount, status, purpose, project_id, requested_by
    FROM fund_requisitions
    WHERE status IN ('approved', 'paid')
");

if ($frRes && $frRes->num_rows > 0) {
    while ($fr = $frRes->fetch_assoc()) {
        $reqNo = 'FR-' . $fr['id'];
        $amount = (float)$fr['amount'];
        if ($amount <= 0) continue;

        // Check if journal already exists
        $check = $db->query("SELECT id FROM journals WHERE source_module = 'fund_requisition' AND source_ref_id = {$fr['id']} LIMIT 1");
        if ($check && $check->num_rows > 0) continue;

        $narration = "Fund Requisition (" . $reqNo . "): " . ($fr['purpose'] ?: 'Operational Expense');
        $jAccrual = $autoPosting->postAccrualEntry(
            'fund_requisition',
            $fr['id'],
            $officeExpenseAccId,
            $salaryPayableAccId,
            $amount,
            $narration,
            $fr['project_id'],
            'employee',
            $fr['requested_by']
        );

        if ($jAccrual) {
            $syncedCount++;
            echo "  ✓ Generated Accrual Journal for Fund Requisition #{$reqNo} (Amount: ৳" . number_format($amount, 2) . ") -> Journal ID: {$jAccrual}\n";

            if (strtolower($fr['status']) === 'paid') {
                $jDisb = $autoPosting->postDisbursementEntry(
                    'fund_requisition',
                    $fr['id'],
                    $salaryPayableAccId,
                    $bankAccId,
                    $amount,
                    "Payment Disbursement for Fund Requisition #" . $reqNo,
                    $fr['project_id'],
                    'employee',
                    $fr['requested_by']
                );
                if ($jDisb) {
                    echo "  ✓ Generated Disbursement Journal for Fund Requisition #" . $reqNo . " -> Journal ID: {$jDisb}\n";
                }
            }
        }
    }
} else {
    echo "  (No approved/paid fund requisitions found)\n";
}

// 2. SYNC CASHOUT REQUESTS
echo "\n[2] Checking Approved & Paid Cashout Requests...\n";
$coRes = $db->query("
    SELECT id, amount, status, project_id
    FROM cashout_requests
    WHERE status IN ('approved', 'paid', 'completed')
");

if ($coRes && $coRes->num_rows > 0) {
    while ($co = $coRes->fetch_assoc()) {
        $coNo = 'CO-' . $co['id'];
        $amount = (float)$co['amount'];
        if ($amount <= 0) continue;

        $check = $db->query("SELECT id FROM journals WHERE source_module = 'cashout_request' AND source_ref_id = {$co['id']} LIMIT 1");
        if ($check && $check->num_rows > 0) continue;

        $narration = "Cashout Request (" . $coNo . ")";
        $jAccrual = $autoPosting->postAccrualEntry(
            'cashout_request',
            $co['id'],
            $officeExpenseAccId,
            $salaryPayableAccId,
            $amount,
            $narration,
            $co['project_id'],
            'employee',
            1
        );

        if ($jAccrual) {
            $syncedCount++;
            echo "  ✓ Generated Accrual Journal for Cashout Request #{$coNo} (Amount: ৳" . number_format($amount, 2) . ") -> Journal ID: {$jAccrual}\n";
        }
    }
} else {
    echo "  (No approved cashout requests found)\n";
}

echo "\n=========================================================================\n";
echo "✓ ALL APPROVED EXPENSE VOUCHERS SYNCED TO GENERAL JOURNAL SUCCESSFULLY!\n";
echo "=========================================================================\n";
