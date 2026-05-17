<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $db->query('SELECT id, start_date FROM initial_estimate_budgets ORDER BY id DESC LIMIT 5');
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
