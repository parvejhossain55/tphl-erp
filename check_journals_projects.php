<?php
$db = new mysqli("127.0.0.1", "root", "", "tphl_db");
$res = $db->query("SELECT j.id, j.journal_number, j.project_id, p.name as project_name FROM journals j LEFT JOIN projects p ON j.project_id = p.id ORDER BY j.id DESC LIMIT 15");
while($row = $res->fetch_assoc()) {
    echo "Journal #{$row['journal_number']} (ID: {$row['id']}) -> project_id: " . ($row['project_id'] ?? 'NULL') . " | project_name: " . ($row['project_name'] ?? 'NONE') . "\n";
}

echo "\n--- Active Projects in Database ---\n";
$resP = $db->query("SELECT id, name FROM projects LIMIT 10");
while($p = $resP->fetch_assoc()) {
    echo "Project ID: {$p['id']} -> Name: {$p['name']}\n";
}
