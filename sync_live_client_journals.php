<?php
/**
 * Standalone Script to Auto-Generate & Sync Missing Client Payment Journals on Live Server
 * Place in root or run via CLI: php sync_live_client_journals.php
 */
$config = require __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/services/AutoPostingService.php';
require_once __DIR__ . '/app/models/GeneralJournal.php';

$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) {
    die("Database Connection Failed: " . $db->connect_error);
}

$autoPoster = new AutoPostingService($db);

$res = $db->query("
    SELECT lp.*, lb.project_id, lb.lead_id, l.full_name as client_name
    FROM lead_payments lp
    LEFT JOIN lead_bookings lb ON lp.booking_id = lb.id
    LEFT JOIN leads l ON lb.lead_id = l.id
    WHERE lp.verification_status = 'verified' AND lp.amount > 0
");

$created = 0;
$skipped = 0;

if ($res && $res->num_rows > 0) {
    while ($p = $res->fetch_assoc()) {
        $paymentId = (int)$p['id'];
        
        // Check if journal entry already exists for this payment_id
        $chk = $db->query("SELECT id FROM journals WHERE source_module = 'sales_payment' AND source_ref_id = $paymentId LIMIT 1");
        if ($chk && $chk->num_rows > 0) {
            $skipped++;
            continue;
        }

        $methodLower = strtolower(trim($p['payment_method'] ?? 'cash'));
        $bankNameStr = $p['bank_name'] ?? ($p['bank'] ?? '');
        $bankAccId   = $autoPoster->getBankOrCashAccountId($methodLower, $bankNameStr);
        $clientName  = !empty($p['client_name']) ? $p['client_name'] : ("Lead #" . ($p['lead_id'] ?? 'N/A'));

        $narr = "Client Receipt (" . $p['payment_type'] . "): " . strtoupper(str_replace('_', ' ', $p['payment_type'])) . " from " . $clientName;
        if (!empty($p['transaction_ref'])) {
            $narr .= " (Ref: " . $p['transaction_ref'] . ")";
        }

        $success = $autoPoster->postSalesPaymentEntry(
            $paymentId,
            $bankAccId,
            (float)$p['amount'],
            $p['payment_type'],
            $narr,
            $p['project_id'],
            $p['lead_id'],
            $p['created_by'] ?: 1
        );

        if ($success) {
            $created++;
        }
    }
}

echo "✓ Sync Completed Successfully!\n";
echo "-> Created New Client Payment Journals: $created\n";
echo "-> Skipped (Already Existed): $skipped\n";
