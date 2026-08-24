<?php
$config = require 'app/config/config.php';
$mysqli = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

$queries = [
    "ALTER TABLE payslips ADD COLUMN late_unpaid_absent_days DECIMAL(10,2) DEFAULT '0.00' AFTER net_salary",
    "ALTER TABLE payslips ADD COLUMN bank_amount DECIMAL(10,2) DEFAULT '0.00' AFTER company_bank_id",
    "ALTER TABLE payslips ADD COLUMN cash_amount DECIMAL(10,2) DEFAULT '0.00' AFTER bank_amount",
    "ALTER TABLE payslips ADD COLUMN remarks TEXT DEFAULT NULL AFTER cash_amount",
    "ALTER TABLE payslips ADD COLUMN attendance_remarks TEXT DEFAULT NULL AFTER remarks"
];

foreach ($queries as $q) {
    echo "Executing: $q\n";
    if (!$mysqli->query($q)) {
        if ($mysqli->errno != 1060) {
            echo "Error: " . $mysqli->error . "\n";
        } else {
            echo "Column already exists.\n";
        }
    } else {
        echo "Success.\n";
    }
}
