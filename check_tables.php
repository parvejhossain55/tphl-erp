<?php
$c = require 'app/config/config.php';
$d = new mysqli($c['db_host'], $c['db_user'], $c['db_pass'], $c['db_name']);
$r1 = $d->query("DESCRIBE users");
echo "USERS TABLE:\n";
while($row = $r1->fetch_assoc()) echo $row['Field'] . "\n";

$r2 = $d->query("DESCRIBE employees");
if ($r2) {
    echo "\nEMPLOYEES TABLE:\n";
    while($row = $r2->fetch_assoc()) echo $row['Field'] . "\n";
}