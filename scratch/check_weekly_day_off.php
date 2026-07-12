<?php
require_once __DIR__ . '/../app/helpers/helper.php';
$config = require __DIR__ . '/../app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$res = $db->query("SELECT * FROM emp_weekly_day_off LIMIT 10");
if ($res) {
    if ($res->num_rows == 0) {
        echo "No records found in emp_weekly_day_off.";
    } else {
        while ($row = $res->fetch_assoc()) {
            print_r($row);
        }
    }
} else {
    echo "Query failed: " . $db->error;
}
