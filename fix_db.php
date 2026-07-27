<?php
require_once 'c:/xampp/htdocs/TPHL-ERP/config/database.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
// For AST-2026-0014, price should be 42000
$db->query("UPDATE office_assets SET purchase_price = 42000 WHERE asset_code = 'AST-2026-0014'");
// For AST-2026-0013, if it was also from a returned amount, wait, the screenshot only highlighted AST-2026-0014
echo "Updated.";
