<?php
// Live Database Real Department Transaction Auto-Posting Script

require_once __DIR__ . '/app/services/AutoPostingService.php';

$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli("127.0.0.1", "root", "", "tphl_db");

if ($db->connect_error) {
    die("Database Connection Failed: " . $db->connect_error);
}

$db->set_charset("utf8mb4");

echo "=========================================================================\n";
echo "=== REAL DATABASE DEPARTMENTAL TRANSACTIONS AUTO-POSTING AUDIT ===\n";
echo "=========================================================================\n\n";

$autoPosting = new AutoPostingService($db);
$bankAccId = $autoPosting->getAccountIdByCode('1110-002'); // DBBL Bank Main Account
$vendorPayableAccId = $autoPosting->getAccountIdByCode('2110'); // AP - Vendors
$contractorPayableAccId = $autoPosting->getAccountIdByCode('2120'); // AP - Contractors
$salaryPayableAccId = $autoPosting->getAccountIdByCode('2130'); // AP - Employees / Payroll
$materialsWipAccId = $autoPosting->getAccountIdByCode('5120-001'); // Material Purchase WIP
$payrollExpAccId = $autoPosting->getAccountIdByCode('5210-001'); // Staff Salaries & Payroll

$generatedJournals = [];

// -----------------------------------------------------------------------------
// 1. REAL SALES DEPT TRANSACTIONS (Booking Money, Registration, EMI)
// -----------------------------------------------------------------------------
echo "[1] Fetching Real Client Payments from `lead_payments`...\n";
$salesRes = $db->query("
    SELECT lp.id, lp.booking_id, lp.payment_type, lp.amount, lp.payment_date, lb.project_id, lb.lead_id, l.full_name as client_name
    FROM lead_payments lp
    LEFT JOIN lead_bookings lb ON lp.booking_id = lb.id
    LEFT JOIN leads l ON lb.lead_id = l.id
    ORDER BY lp.id DESC
    LIMIT 3
");

if ($salesRes && $salesRes->num_rows > 0) {
    while ($sp = $salesRes->fetch_assoc()) {
        $clientName = $sp['client_name'] ?: 'General Client';
        $payType = strtolower($sp['payment_type']);
        $narration = "Real Sales Payment ({$sp['payment_type']}) - Client: {$clientName} (Booking #{$sp['booking_id']})";
        
        $jId = $autoPosting->postSalesPaymentEntry(
            $sp['id'],
            $bankAccId,
            $sp['amount'],
            $payType,
            $narration,
            $sp['project_id'],
            $sp['lead_id'],
            1
        );

        if ($jId) {
            $generatedJournals[] = $jId;
            echo "  ✓ Generated Voucher for Sales Payment ID #{$sp['id']} ({$sp['payment_type']} - ৳" . number_format($sp['amount'], 2) . ") -> Journal ID: {$jId}\n";
        }
    }
} else {
    echo "  (No sales payment records found in DB, using active sales simulation)\n";
}

// -----------------------------------------------------------------------------
// 2. REAL SITE/CONSTRUCTION CONTRACTOR BILL TRANSACTIONS
// -----------------------------------------------------------------------------
echo "\n[2] Fetching Real Contractor Bills from `contractor_bill_requests`...\n";
$conRes = $db->query("
    SELECT cb.id, cb.project_id, cb.bill_amount, cb.approved_amount, cb.status, c.name as contractor_name
    FROM contractor_bill_requests cb
    LEFT JOIN contractors c ON cb.operator_id = c.id
    ORDER BY cb.id DESC
    LIMIT 2
");

if ($conRes && $conRes->num_rows > 0) {
    while ($cb = $conRes->fetch_assoc()) {
        $cName = $cb['contractor_name'] ?: 'Contractor';
        $amt = (float)($cb['approved_amount'] > 0 ? $cb['approved_amount'] : $cb['bill_amount']);
        if ($amt <= 0) continue;

        // Accrual Entry (Bokea)
        $accrualNarration = "Contractor Bill Approved (Accrued) - Contractor: {$cName} (Bill #{$cb['id']})";
        $jAccrual = $autoPosting->postAccrualEntry(
            'contractor_bill',
            $cb['id'],
            $materialsWipAccId,
            $contractorPayableAccId,
            $amt,
            $accrualNarration,
            $cb['project_id'],
            'contractor',
            1
        );

        if ($jAccrual) {
            $generatedJournals[] = $jAccrual;
            echo "  ✓ Generated Accrual Voucher for Contractor Bill #{$cb['id']} (৳" . number_format($amt, 2) . ") -> Journal ID: {$jAccrual}\n";

            // Disbursement Entry (Payment Cash Out)
            $disbNarration = "Accounts Payment Disbursement for Contractor Bill #{$cb['id']} - Cheque Out";
            $jDisb = $autoPosting->postDisbursementEntry(
                'contractor_bill',
                $cb['id'],
                $contractorPayableAccId,
                $bankAccId,
                $amt,
                $disbNarration,
                $cb['project_id'],
                'contractor',
                1
            );
            if ($jDisb) {
                $generatedJournals[] = $jDisb;
                echo "  ✓ Generated Disbursement Voucher for Contractor Bill #{$cb['id']} (৳" . number_format($amt, 2) . ") -> Journal ID: {$jDisb}\n";
            }
        }
    }
} else {
    echo "  (No contractor bills found in DB)\n";
}

// -----------------------------------------------------------------------------
// 3. REAL MATERIAL PURCHASE / VENDOR REQUISITION TRANSACTIONS
// -----------------------------------------------------------------------------
echo "\n[3] Fetching Real Vendor Payment Requests from `vendor_payment_requests`...\n";
$vpRes = $db->query("
    SELECT vp.id, vp.project_id, vp.net_payable, v.name as vendor_name
    FROM vendor_payment_requests vp
    LEFT JOIN vendors v ON vp.vendor_id = v.id
    ORDER BY vp.id DESC
    LIMIT 2
");

if ($vpRes && $vpRes->num_rows > 0) {
    while ($vp = $vpRes->fetch_assoc()) {
        $vName = $vp['vendor_name'] ?: 'Material Vendor';
        $amt = (float)$vp['net_payable'];
        if ($amt <= 0) continue;

        // Accrual Entry (Vendor Payable Bokea)
        $accrualNarration = "Vendor Material Purchase Accrual - Vendor: {$vName} (Request #{$vp['id']})";
        $jAccrual = $autoPosting->postAccrualEntry(
            'vendor_payment',
            $vp['id'],
            $materialsWipAccId,
            $vendorPayableAccId,
            $amt,
            $accrualNarration,
            $vp['project_id'],
            'vendor',
            1
        );

        if ($jAccrual) {
            $generatedJournals[] = $jAccrual;
            echo "  ✓ Generated Accrual Voucher for Vendor Request #{$vp['id']} (৳" . number_format($amt, 2) . ") -> Journal ID: {$jAccrual}\n";

            // Disbursement Entry (Vendor Cash Out)
            $disbNarration = "Accounts Payment Disbursement for Material Vendor Request #{$vp['id']}";
            $jDisb = $autoPosting->postDisbursementEntry(
                'vendor_payment',
                $vp['id'],
                $vendorPayableAccId,
                $bankAccId,
                $amt,
                $disbNarration,
                $vp['project_id'],
                'vendor',
                1
            );
            if ($jDisb) {
                $generatedJournals[] = $jDisb;
                echo "  ✓ Generated Disbursement Voucher for Vendor Request #{$vp['id']} (৳" . number_format($amt, 2) . ") -> Journal ID: {$jDisb}\n";
            }
        }
    }
} else {
    echo "  (No vendor payment requests found in DB)\n";
}

// -----------------------------------------------------------------------------
// PRINT AUDIT SUMMARY OF GENERATED VOUCHERS
// -----------------------------------------------------------------------------
echo "\n=========================================================================\n";
echo "=== REAL DOUBLE-ENTRY VOUCHERS POSTED TO YOUR DATABASE ===\n";
echo "=========================================================================\n";

if (!empty($generatedJournals)) {
    $idsStr = implode(',', array_map('intval', $generatedJournals));
    $res = $db->query("
        SELECT j.id, j.journal_number, j.voucher_type, j.entry_type, j.total_debit, j.narration
        FROM journals j
        WHERE j.id IN ({$idsStr})
        ORDER BY j.id ASC
    ");

    while ($j = $res->fetch_assoc()) {
        echo "\nVOUCHER: {$j['journal_number']} | TYPE: {$j['voucher_type']} | ENTRY: {$j['entry_type']} | TOTAL: ৳" . number_format($j['total_debit'], 2) . "\n";
        echo "NARRATION: {$j['narration']}\n";
        echo "---------------------------------------------------------------------\n";
        
        $stmtD = $db->prepare("
            SELECT jd.debit, jd.credit, coa.account_code, coa.account_name, coa.account_type
            FROM journal_details jd
            JOIN chart_of_accounts coa ON jd.account_id = coa.id
            WHERE jd.journal_id = ?
            ORDER BY jd.id ASC
        ");
        $stmtD->bind_param("i", $j['id']);
        $stmtD->execute();
        $resD = $stmtD->get_result();
        
        while ($d = $resD->fetch_assoc()) {
            $dr = str_pad("৳" . number_format($d['debit'], 2), 14, " ", STR_PAD_LEFT);
            $cr = str_pad("৳" . number_format($d['credit'], 2), 14, " ", STR_PAD_LEFT);
            echo "  [{$d['account_code']}] " . str_pad($d['account_name'], 40, " ", STR_PAD_RIGHT) . " -> DR: {$dr} | CR: {$cr}\n";
        }
    }
} else {
    echo "No new journals generated.\n";
}

$db->close();
echo "\n=========================================================================\n";
echo "✓ REAL DATABASE AUTOMATED POISTING COMPLETED SUCCESSFULLY!\n";
echo "=========================================================================\n";
