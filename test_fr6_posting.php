<?php
require_once __DIR__ . '/app/services/AutoPostingService.php';

$db = new mysqli("127.0.0.1", "root", "", "tphl_db");
$autoPosting = new AutoPostingService($db);

$resFR = $db->query("SELECT * FROM fund_requisitions WHERE id = 6 LIMIT 1");
$fr6 = $resFR->fetch_assoc();

print_r($fr6);

$officeExpenseAccId = $autoPosting->getAccountIdByCode('6110-001'); // Office Expense
$salaryPayableAccId = $autoPosting->getAccountIdByCode('2130');     // Employee Payable

$narration = "Fund Requisition (FR-6): " . $fr6['purpose'];
$jId = $autoPosting->postAccrualEntry(
    'fund_requisition',
    $fr6['id'],
    $officeExpenseAccId,
    $salaryPayableAccId,
    $fr6['amount'],
    $narration,
    $fr6['project_id'],
    'employee',
    $fr6['requested_by']
);

echo "\n✓ SUCCESS: Journal Entry Generated for FR-6 (Loan for bricks for abc vendor - ৳100,000)! Journal ID: {$jId}\n";
