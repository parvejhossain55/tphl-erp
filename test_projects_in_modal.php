<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$res = $db->query("SELECT id, name FROM projects ORDER BY name ASC");
$projects = $res->fetch_all(MYSQLI_ASSOC);
echo "Fetched " . count($projects) . " projects:\n";
foreach($projects as $p) {
    echo " - ID: {$p['id']} | Name: {$p['name']}\n";
}
