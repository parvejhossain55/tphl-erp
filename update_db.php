<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// 1. Add wastage_type if not exists
$res = $db->query("SHOW COLUMNS FROM con_wastage LIKE 'wastage_type'");
if ($res->num_rows == 0) {
    $sql = "ALTER TABLE con_wastage ADD COLUMN wastage_type ENUM('storage_damage', 'selling_scrap') NOT NULL DEFAULT 'selling_scrap' AFTER warehouse_id";
    if ($db->query($sql)) {
        echo "wastage_type added successfully.\n";
    } else {
        echo "Error adding wastage_type: " . $db->error . "\n";
    }
} else {
    echo "wastage_type already exists.\n";
}

// 2. Modify status enum to include all workflow statuses
$sql = "ALTER TABLE con_wastage MODIFY COLUMN status ENUM('Pending QC', 'Pending Purchase', 'Pending Payment', 'Completed', 'Rejected') NOT NULL DEFAULT 'Pending QC'";
if ($db->query($sql)) {
    echo "status column enum updated successfully.\n";
} else {
    echo "Error updating status column: " . $db->error . "\n";
}

// 3. Add approval columns if not exists
$columnsToAdd = [
    'qc_approved_by' => "INT UNSIGNED DEFAULT NULL AFTER recorded_by",
    'qc_approved_at' => "DATETIME DEFAULT NULL AFTER qc_approved_by",
    'qc_note' => "TEXT DEFAULT NULL AFTER qc_approved_at",
    'qc_doc' => "VARCHAR(255) DEFAULT NULL AFTER qc_note",
    'purchase_approved_by' => "INT UNSIGNED DEFAULT NULL AFTER qc_doc",
    'purchase_approved_at' => "DATETIME DEFAULT NULL AFTER purchase_approved_by",
    'purchase_note' => "TEXT DEFAULT NULL AFTER purchase_approved_at",
    'purchase_doc' => "VARCHAR(255) DEFAULT NULL AFTER purchase_note",
    'payment_received_by' => "INT UNSIGNED DEFAULT NULL AFTER purchase_doc",
    'payment_received_at' => "DATETIME DEFAULT NULL AFTER payment_received_by",
    'payment_method' => "VARCHAR(100) DEFAULT NULL AFTER payment_received_at",
    'payment_doc' => "VARCHAR(255) DEFAULT NULL AFTER payment_method"
];

foreach ($columnsToAdd as $col => $definition) {
    $res = $db->query("SHOW COLUMNS FROM con_wastage LIKE '$col'");
    if ($res->num_rows == 0) {
        $sql = "ALTER TABLE con_wastage ADD COLUMN $col $definition";
        if ($db->query($sql)) {
            echo "Column $col added successfully.\n";
        } else {
            echo "Error adding column $col: " . $db->error . "\n";
        }
    } else {
        echo "Column $col already exists.\n";
    }
}

$db->close();
echo "Database migration finished.\n";
