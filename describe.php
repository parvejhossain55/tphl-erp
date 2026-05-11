<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_erp');
$res = $db->query('DESCRIBE target_fixed;');
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . ' | ';
}
echo "\n";
