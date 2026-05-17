<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) { die('Connection failed: ' . $db->connect_error); }

$alters = [
    "ALTER TABLE weekly_progress_report ADD COLUMN qc_gain_percentage DECIMAL(5,2) DEFAULT 0.00 AFTER approved_by_qc",
    "ALTER TABLE weekly_progress_report ADD COLUMN qc_photo VARCHAR(255) NULL AFTER qc_gain_percentage",
    "ALTER TABLE weekly_progress_report ADD COLUMN qc_field_test_details TEXT NULL AFTER qc_photo",
    "ALTER TABLE weekly_progress_report ADD COLUMN qc_lab_test_result TEXT NULL AFTER qc_field_test_details",
];

foreach ($alters as $sql) {
    if ($db->query($sql)) {
        echo "✅ OK: " . $sql . PHP_EOL;
    } else {
        echo "❌ FAIL (" . $db->error . "): " . $sql . PHP_EOL;
    }
}

echo PHP_EOL . "Done!" . PHP_EOL;
