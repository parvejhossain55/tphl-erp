<?php
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) { die('Connection failed: ' . $db->connect_error); }

echo "=== Fixing con_work_orders.status ENUM ===" . PHP_EOL . PHP_EOL;

// Step 1: ALTER TABLE to add all needed status values
$alter = "ALTER TABLE con_work_orders 
    MODIFY COLUMN status ENUM(
        'draft',
        'active', 
        'approved',
        'rejected',
        'work_completed',
        'qc_approved',
        'closed',
        'completed',
        'cancelled'
    ) NOT NULL DEFAULT 'draft'";

if ($db->query($alter)) {
    echo "✅ ENUM updated with all status values!" . PHP_EOL;
} else {
    echo "❌ ALTER failed: " . $db->error . PHP_EOL;
    exit;
}

// Step 2: Fix work order ID=1 — it has approved_by set so it should be 'approved'
$upd = $db->query("UPDATE con_work_orders SET status='approved' WHERE id=1 AND approved_by IS NOT NULL AND (status = '' OR status IS NULL OR status = 'completed')");
echo "✅ Updated " . $db->affected_rows . " record(s): status → 'approved'" . PHP_EOL;

// Step 3: Verify
echo PHP_EOL . "=== Verification ===" . PHP_EOL;
$rows = $db->query("SELECT id, status, approved_by, qc_approved_by FROM con_work_orders")->fetch_all(MYSQLI_ASSOC);
foreach ($rows as $r) {
    echo "ID={$r['id']}: status='{$r['status']}', approved_by=" . ($r['approved_by'] ?? 'NULL') . ", qc_approved_by=" . ($r['qc_approved_by'] ?? 'NULL') . PHP_EOL;
}

echo PHP_EOL . "✅ Done! Now check con-contractor-bill-create again." . PHP_EOL;
