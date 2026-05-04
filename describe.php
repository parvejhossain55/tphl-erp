<?php
require 'C:/xampp/htdocs/TPHL-ERP/config/database.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$result = $db->query('DESCRIBE material_receiving');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
