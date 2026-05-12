<?php
$config = require 'C:/xampp/htdocs/TPHL-ERP/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$id = 13;
$yr = 2026;
$mo = 5;
$wNum = 1;

$s2 = $db->prepare("
    SELECT dpr.*, se.full_name AS engineer_name, t.target_title,
           pm.full_name AS pm_name, qc.full_name AS qc_name, cb.full_name AS creator_name,
           wp.work_description AS work_plan_title,
           wo.title AS work_order_title, c.name AS contractor_name, c.phone AS contractor_phone,
           t.year AS target_year, t.month AS target_month, t.week_number
    FROM   weekly_progress_report dpr
    LEFT JOIN users se ON se.id = dpr.site_engineer_id
    LEFT JOIN users pm ON pm.id = dpr.approved_by_pm
    LEFT JOIN users qc ON qc.id = dpr.approved_by_qc
    LEFT JOIN users cb ON cb.id = dpr.created_by
    LEFT JOIN target_fixed t ON t.id = dpr.target_id
    LEFT JOIN project_working_plans wp ON wp.id = dpr.work_plan_id
    LEFT JOIN con_work_orders wo ON wo.id = dpr.work_order_id
    LEFT JOIN contractors c ON c.id = wo.contractor_id
    WHERE  dpr.project_id = ? AND YEAR(dpr.report_date) = ? AND MONTH(dpr.report_date) = ? AND dpr.week_no = ?
    ORDER  BY dpr.report_date ASC");

$s2->bind_param('iiii', $id, $yr, $mo, $wNum);
$s2->execute();
$dprs = $s2->get_result()->fetch_all(MYSQLI_ASSOC);

print_r($dprs);
