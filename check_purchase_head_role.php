<?php
$db = new mysqli("127.0.0.1", "root", "", "tphl_db");
$res = $db->query("SELECT id, full_name, role_id, email FROM users WHERE full_name LIKE '%Purchase%' OR full_name LIKE '%Operations%' OR full_name LIKE '%Audit%' LIMIT 10");
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | Name: {$row['full_name']} | Role ID: {$row['role_id']} | Email: {$row['email']}\n";
}
