<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "=== project_drawings CREATE TABLE ===\n";
$res1 = $db->query("SHOW CREATE TABLE project_drawings");
if ($res1) {
    $row = $res1->fetch_assoc();
    echo $row['Create Table'] . "\n\n";
}

echo "=== work_types CREATE TABLE ===\n";
$res2 = $db->query("SHOW CREATE TABLE work_types");
if ($res2) {
    $row = $res2->fetch_assoc();
    echo $row['Create Table'] . "\n\n";
}
