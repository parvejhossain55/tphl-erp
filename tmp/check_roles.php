<?php
$config = require 'app/config/config.php';
$conn = new mysqli($config['host'], $config['user'], $config['pass'], $config['name']);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$result = $conn->query("SELECT * FROM roles");
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
$conn->close();
