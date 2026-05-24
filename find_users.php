<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
$result = $db->query("SELECT u.email, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name LIKE '%Purchase%' OR r.name LIKE '%Procurement%' OR r.name LIKE '%PM%' OR r.name LIKE '%Project Manager%' OR r.name LIKE '%Site Engineer%'");
while($row = $result->fetch_assoc()) {
    echo $row['email'] . " - " . $row['role_name'] . "\n";
}
