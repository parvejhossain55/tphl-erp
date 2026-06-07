<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$hash = password_hash('123456', PASSWORD_DEFAULT);
$db->query("UPDATE users SET password = '$hash'");

$res = $db->query("SELECT email FROM users WHERE role_id = 13 LIMIT 1");
if ($row = $res->fetch_assoc()) {
    echo "QC_EMAIL=" . $row['email'] . "\n";
} else {
    // If no QC account exists, let's make one
    $email = "qc@tphl.com";
    $db->query("INSERT INTO users (full_name, email, password, role_id, status) VALUES ('QC Tester', '$email', '$hash', 13, 1)");
    echo "QC_EMAIL=$email\n";
}
$db->close();
