<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
$result = $db->query("SHOW COLUMNS FROM con_warehouses");
if($result) {
    while($row = $result->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
} else {
    echo $db->error;
}
