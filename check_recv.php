<?php
$c = require 'C:/xampp/htdocs/TPHL-ERP/app/config/config.php';
$db = new mysqli($c['db_host'], $c['db_user'], $c['db_pass'], $c['db_name']);

// Check actual overall_status in material_receiving
echo "=== material_receiving ===\n";
$r = $db->query("SELECT id, purchase_order_id, overall_status FROM material_receiving LIMIT 10");
while ($row = $r->fetch_assoc()) {
    echo "ID:{$row['id']} PO:{$row['purchase_order_id']} status:{$row['overall_status']}\n";
}

echo "\n=== material_receiving_items ===\n";
$r = $db->query("SELECT mri.id, mri.receiving_id, mri.accepted_qty, mri.status, poi.unit_price, poi.selected_vendor_id
                 FROM material_receiving_items mri
                 JOIN purchase_order_items poi ON poi.id = mri.purchase_order_item_id
                 LIMIT 10");
while ($row = $r->fetch_assoc()) {
    echo "ID:{$row['id']} vendor:{$row['selected_vendor_id']} acc_qty:{$row['accepted_qty']} unit_price:{$row['unit_price']} status:{$row['status']}\n";
}

echo "\n=== Vendor total receivable ===\n";
$r = $db->query("SELECT 
    poi.selected_vendor_id,
    SUM(mri.accepted_qty * poi.unit_price) AS total_receivable
    FROM material_receiving_items mri
    JOIN material_receiving mr ON mr.id = mri.receiving_id
    JOIN purchase_order_items poi ON poi.id = mri.purchase_order_item_id
    WHERE mri.status IN ('Accepted','Partially Accepted')
    GROUP BY poi.selected_vendor_id");
while ($row = $r->fetch_assoc()) {
    echo "vendor_id:{$row['selected_vendor_id']} total:{$row['total_receivable']}\n";
}
