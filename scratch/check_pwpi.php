<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$res = $db->query("
    SELECT * FROM phase_weekly_progress WHERE phase_id IN (SELECT id FROM project_phases WHERE project_id = 13)
");
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
print_r($rows);

