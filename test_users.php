<?php
$mysqli = new mysqli('localhost', 'root', '', 'tphl_db');
$roles = [1, 15, 12, 23, 24, 13, 18, 21, 16];
$roleList = implode(',', $roles);
$result = $mysqli->query("SELECT email, role_id, (SELECT name FROM roles WHERE id=role_id) as role_name FROM users WHERE role_id IN ($roleList)");
while($row = $result->fetch_assoc()) {
    echo $row['role_name'] . ' - ' . $row['email'] . "\n";
}
