<?php
require_once __DIR__ . '/../app/config/database.php';
$res = $GLOBALS['db']->query("SELECT COUNT(*) FROM target_fixed WHERE project_id=13");
var_dump($res->fetch_row());
$res2 = $GLOBALS['db']->query("SELECT COUNT(*) FROM weekly_progress_report WHERE project_id=13");
var_dump($res2->fetch_row());
$res3 = $GLOBALS['db']->query("SELECT COUNT(*) FROM con_financial_transactions WHERE project_id=13");
var_dump($res3->fetch_row());
