<?php
$mysqli = new mysqli('localhost', 'root', '', 'tphl_db');
$result = $mysqli->query("SELECT email, password FROM users WHERE email IN ('site.engineer1@gmail.com', 'project.manager1@gmail.com', 'purchase.executive1@gmail.com', 'qcaudit@gmail.com')");
while ($row = $result->fetch_assoc()) {
    $pw = $row['password'];
    $is_123456 = password_verify('123456', $pw) ? 'YES' : 'NO';
    echo "Email: {$row['email']} | Hash: {$pw} | Is 123456: {$is_123456}\n";
}
