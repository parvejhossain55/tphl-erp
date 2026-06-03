<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$tables = ['con_work_orders', 'boq_master', 'con_material_issues', 'contractor_bill_requests'];
foreach ($tables as $t) {
    echo "\n=== Columns of $t ===\n";
    $res = $db->query("DESCRIBE $t");
    if ($res) {
        $cols = $res->fetch_all(MYSQLI_ASSOC);
        foreach ($cols as $c) {
            if (strpos($c['Field'], 'target_id') !== false || strpos($c['Field'], 'plan_id') !== false || strpos($c['Field'], 'weekly_progress') !== false) {
                print_r($c);
            }
        }
    }
}

$db->close();
?>
