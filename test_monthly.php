<?php
require_once 'c:/xampp/htdocs/TPHL-ERP/config/database.php';
require_once 'c:/xampp/htdocs/TPHL-ERP/app/models/CentralStatement.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$cs = new CentralStatement($db);
try {
    $cs->getMonthlyCashOut('2026-01-01', '2026-01-31', 'all');
    echo "Success!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
