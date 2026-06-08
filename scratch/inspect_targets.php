<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

require_once 'app/models/Con_MonthlyTarget.php';

$model = new Con_MonthlyTarget($db);
$summary = $model->getProgressSummary(1);
print_r($summary);

$items = $model->getItems(1);
print_r($items);
