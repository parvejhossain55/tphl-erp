<?php
$config = require 'C:/xampp/htdocs/TPHL-ERP/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$res = $db->query('SELECT id, report_date, week_no FROM weekly_progress_report WHERE project_id = 13');
while($r = $res->fetch_assoc()) print_r($r);
