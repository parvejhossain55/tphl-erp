<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$query = "
    SELECT DISTINCT p.id, p.name 
    FROM projects p 
    LEFT JOIN journals j ON j.project_id = p.id
    LEFT JOIN lead_bookings lb ON lb.project_id = p.id
    LEFT JOIN cashout_requests cr ON cr.project_id = p.id
    WHERE p.deleted_at IS NULL 
    AND (j.id IS NOT NULL OR lb.id IS NOT NULL OR cr.id IS NOT NULL)
    ORDER BY p.name ASC
";
$res = $db->query($query);
echo "=== PROJECTS WITH TRANSACTIONS FOR CENTRAL STATEMENT ===\n";
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | Name: {$row['name']}\n";
}
