<?php

$mysqli = new mysqli("127.0.0.1", "root", "", "tphl_db");

echo "=== Project Categories ===\n";
$res = $mysqli->query("SELECT * FROM project_categories");
while ($r = $res->fetch_assoc()) {
    echo "ID: {$r['id']} | Name: {$r['name']} | Inc %: {$r['incentive_percentage']}\n";
}

echo "\n=== Projects (Incentive % != NULL / 0) ===\n";
$resP = $mysqli->query("SELECT id, name, incentive_percentage, category_id FROM projects WHERE incentive_percentage IS NOT NULL AND incentive_percentage > 0");
while ($p = $resP->fetch_assoc()) {
    echo "ID: {$p['id']} | Name: {$p['name']} | Inc %: {$p['incentive_percentage']}\n";
}
