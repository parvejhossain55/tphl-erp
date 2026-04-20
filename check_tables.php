<?php
$db = new mysqli('localhost','root','','tphl_db');

// Check FK constraints on material_receiving
$r = $db->query("SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE TABLE_NAME IN ('material_receiving','material_receiving_items') 
AND REFERENCED_TABLE_NAME IS NOT NULL
AND TABLE_SCHEMA = 'tphl_db'");
echo "=== FK Constraints ===\n";
while($row = $r->fetch_assoc()) {
    echo $row['TABLE_NAME'].'.'.$row['COLUMN_NAME'].' → '.$row['REFERENCED_TABLE_NAME'].'.'.$row['REFERENCED_COLUMN_NAME'].' ('.$row['CONSTRAINT_NAME'].")\n";
}

// Try a test insert
echo "\n=== Test INSERT ===\n";
$stmt = $db->prepare("INSERT INTO material_receiving (purchase_order_id, warehouse_id, received_by, receiving_date, received_status, overall_status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) { echo "PREPARE FAILED: ".$db->error."\n"; exit; }

$po_id = 1; $wh = 1; $rb = 6; $rd = '2026-04-19'; $rs = 'Full'; $os = 'Pending QC'; $n = 'test'; $cb = 6;
$stmt->bind_param('iiissssi', $po_id, $wh, $rb, $rd, $rs, $os, $n, $cb);
if ($stmt->execute()) {
    echo "SUCCESS! ID = ".$db->insert_id."\n";
    // Delete test record
    $db->query("DELETE FROM material_receiving WHERE id = ".$db->insert_id);
    echo "Test record cleaned up.\n";
} else {
    echo "FAILED: ".$stmt->error."\n";
}
$db->close();
