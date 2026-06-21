<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) die($db->connect_error);

echo "=== management hash ===\n";
$res = $db->query("SELECT password FROM users WHERE email = 'management@gmail.com'");
$row = $res->fetch_assoc();
echo "Hash: " . $row['password'] . "\n";

// Let's verify password_verify('123456', hash) or password_verify('password', hash)
if (password_verify('123456', $row['password'])) {
    echo "Password is 123456\n";
} elseif (password_verify('password', $row['password'])) {
    echo "Password is password\n";
} else {
    echo "Password is unknown, updating it to 'password'\n";
    $newHash = password_hash('password', PASSWORD_DEFAULT);
    $db->query("UPDATE users SET password = '$newHash' WHERE email = 'management@gmail.com'");
    echo "Updated password for management@gmail.com to 'password'\n";
}
?>
