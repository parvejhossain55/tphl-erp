<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $db->query("SELECT id, project_id, status, total_estimated_cost FROM initial_estimate_budgets WHERE project_id = 13");
print_r($res->fetch_all(MYSQLI_ASSOC));
