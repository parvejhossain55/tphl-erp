<?php
$config = require 'C:/xampp/htdocs/TPHL-ERP/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

require_once 'C:/xampp/htdocs/TPHL-ERP/app/core/Controller.php';
require_once 'C:/xampp/htdocs/TPHL-ERP/app/controllers/Phase2/Con_DashboardController.php';

$ctrl = new \Phase2\Con_DashboardController($db);
ob_start();
$ctrl->ajaxWeeklyData(13);
$out = ob_get_clean();
echo $out;
