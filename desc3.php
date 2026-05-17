<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $db->query('DESC initial_estimate_budgets');
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
