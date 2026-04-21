<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
$r = $db->query('DESCRIBE lead_payments');
while ($row = $r->fetch_assoc()) {
    echo $row['Field'] . ' | ' . $row['Type'] . PHP_EOL;
}
