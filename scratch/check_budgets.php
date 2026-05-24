<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$res = $db->query("SELECT id, project_id, work_type_id, total_estimated_cost, weight_percentage, start_date, end_date FROM initial_estimate_budgets");
if ($res) {
    echo "=== Budgets ===\n";
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
}
