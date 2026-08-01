<?php
require_once __DIR__ . '/app/services/AutoPostingService.php';

$db = new mysqli("127.0.0.1", "root", "", "tphl_db");
$autoPosting = new AutoPostingService($db);

$res = $db->query("SELECT * FROM project_extra_assets WHERE overall_status IN ('approved', 'paid', 'purchased') OR mgmt_status = 'approved'");

echo "=========================================================================\n";
echo "=== OFFICE ASSET PROCUREMENT JOURNAL AUTO-SYNC ===\n";
echo "=========================================================================\n\n";

if ($res && $res->num_rows > 0) {
    while ($req = $res->fetch_assoc()) {
        $id = $req['id'];
        $amount = (float)$req['total_price'];
        if ($amount <= 0) continue;

        // Check if journal entry exists
        $check = $db->query("SELECT id FROM journals WHERE source_module = 'asset_procurement' AND source_ref_id = {$id} LIMIT 1");
        if ($check && $check->num_rows > 0) {
            echo "  (Journal entry for Procurement PR-{$id} already exists)\n";
            continue;
        }

        $assetExpenseAccId = $autoPosting->getAccountIdByCode('5220'); // Office & Asset Expense
        $vendorPayableAccId = $autoPosting->getAccountIdByCode('2110'); // Accounts Payable - Vendors

        $jId = $autoPosting->postAccrualEntry(
            'asset_procurement',
            $id,
            $assetExpenseAccId,
            $vendorPayableAccId,
            $amount,
            "Office Asset Procurement (PR-{$id}): " . $req['item_name'] . " (Qty: {$req['quantity']})",
            $req['project_id'],
            'vendor',
            $req['assigned_to'] ?? $req['requested_by'],
            1
        );

        if ($jId) {
            echo "  ✓ Generated Accrual Journal JV for Procurement #PR-{$id} (Amount: ৳" . number_format($amount, 2) . ") -> Journal ID: {$jId}\n";
        }
    }
} else {
    echo "  (No approved/paid procurement requests found to sync)\n";
}

echo "\n=========================================================================\n";
echo "✓ PROCUREMENT JOURNALS SYNCED SUCCESSFULLY!\n";
echo "=========================================================================\n";
