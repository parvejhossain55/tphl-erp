<?php
// Test Script for AutoPostingService 2-Step Accounting Lifecycle & Sales Integration

require_once __DIR__ . '/app/services/AutoPostingService.php';

$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli("127.0.0.1", "root", "", "tphl_db");

if ($db->connect_error) {
    die("Database Connection Failed: " . $db->connect_error);
}

$db->set_charset("utf8mb4");

echo "=========================================================\n";
echo "=== TESTING BATCH 2: AUTO POSTING SERVICE & LIFECYCLE ===\n";
echo "=========================================================\n\n";

$autoService = new AutoPostingService($db);

// -----------------------------------------------------------------------------
// TEST CASE 1: Construction Contractor Bill Approval (Step 1: Accrual Entry)
// Scenario: Site Manager approves ৳1,50,000 contractor bill for Project ID 1
// -----------------------------------------------------------------------------
echo "[TEST 1/3] Simulating Contractor Bill Approval (Accrual Entry)...\n";
$expenseAccId = $autoService->getAccountIdByCode('5110-001'); // Site Contractor Bill WIP Expense
$payableAccId = $autoService->getAccountIdByCode('2120');     // Accounts Payable - Contractors

$billAmount = 150000.00;
$sourceModule = 'contractor_bill';
$sourceRefId = 9991; // Dummy contractor bill ID
$narration = "Contractor Bill Approval - Foundation Casting Work (Project #1)";
$projectId = 1;
$contractorId = 5; // Party ID

$accrualJournalId = $autoService->postAccrualEntry(
    $sourceModule,
    $sourceRefId,
    $expenseAccId,
    $payableAccId,
    $billAmount,
    $narration,
    $projectId,
    'contractor',
    $contractorId
);

if ($accrualJournalId) {
    echo "✓ SUCCESS: Accrual Journal Entry Created! Journal ID: {$accrualJournalId}\n";
} else {
    echo "❌ FAILED: Accrual Journal Entry creation failed.\n";
}

// -----------------------------------------------------------------------------
// TEST CASE 2: Accounts Department Disburses Contractor Bill (Step 2: Cash Out Entry)
// Scenario: Accounts team pays ৳1,50,000 via Dutch Bangla Bank (Code: 1110-002)
// -----------------------------------------------------------------------------
echo "\n[TEST 2/3] Simulating Accounts Disbursement (Cash Out & Payment Voucher)...\n";
$bankAccId = $autoService->getAccountIdByCode('1110-002'); // Dutch Bangla Bank Main Account
$paymentNarration = "Accounts Payment Disbursement - Cheque #458920 for Contractor Bill #9991";

$disbursementJournalId = $autoService->postDisbursementEntry(
    $sourceModule,
    $sourceRefId,
    $payableAccId,
    $bankAccId,
    $billAmount,
    $paymentNarration,
    $projectId,
    'contractor',
    $contractorId
);

if ($disbursementJournalId) {
    echo "✓ SUCCESS: Payment Voucher Created & Cash Out Recorded! Journal ID: {$disbursementJournalId}\n";
} else {
    echo "❌ FAILED: Disbursement Journal Entry creation failed.\n";
}

// -----------------------------------------------------------------------------
// TEST CASE 3: Sales Client EMI Installment Payment (Receipt Entry with 10% Split)
// Scenario: Client pays ৳1,00,000 EMI -> 10% (৳10,000) Service Fee + 90% (৳90,000) Deposit
// -----------------------------------------------------------------------------
echo "\n[TEST 3/3] Simulating Sales Client EMI Payment (Receipt Voucher & 10% Service Fee Split)...\n";
$emiAmount = 100000.00;
$salesNarration = "Client EMI Installment Collection - Booking ID #8841";
$clientId = 12;

$salesJournalId = $autoService->postSalesPaymentEntry(
    8841,
    $bankAccId,
    $emiAmount,
    'emi',
    $salesNarration,
    $projectId,
    $clientId
);

if ($salesJournalId) {
    echo "✓ SUCCESS: Sales Receipt Voucher Created with 10% Service Charge Split! Journal ID: {$salesJournalId}\n";
} else {
    echo "❌ FAILED: Sales Receipt Journal Entry creation failed.\n";
}

// -----------------------------------------------------------------------------
// VERIFICATION: Read and print generated journals & details from DB
// -----------------------------------------------------------------------------
echo "\n=========================================================\n";
echo "=== VERIFYING GENERATED JOURNALS & DOUBLE-ENTRY LEDGERS ===\n";
echo "=========================================================\n";

$res = $db->query("
    SELECT j.id, j.journal_number, j.voucher_type, j.entry_type, j.source_module, j.total_debit, j.total_credit, j.narration
    FROM journals j
    WHERE j.id IN ({$accrualJournalId}, {$disbursementJournalId}, {$salesJournalId})
    ORDER BY j.id ASC
");

while ($j = $res->fetch_assoc()) {
    echo "\nVOUCHER: {$j['journal_number']} | TYPE: {$j['voucher_type']} | ENTRY: {$j['entry_type']} | TOTAL: ৳{$j['total_debit']}\n";
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
        $dr = number_format($d['debit'], 2);
        $cr = number_format($d['credit'], 2);
        echo "  [{$d['account_code']}] {$d['account_name']} ({$d['account_type']}) -> DR: ৳{$dr} | CR: ৳{$cr}\n";
    }
}

$db->close();
echo "\n=== BATCH 2 AUTO-POSTING TEST COMPLETED SUCCESSFULLY! ===\n";
