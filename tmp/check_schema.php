<?php
$config = require "app/config/config.php";
$conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$result = $conn->query("DESCRIBE letter_templates");
$fields = [];
while($row = $result->fetch_assoc()) {
    $fields[] = $row['Field'] . " (" . $row['Type'] . ")";
}
echo implode(", ", $fields);
