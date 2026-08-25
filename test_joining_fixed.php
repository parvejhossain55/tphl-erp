<?php
require_once "app/config/config.php";
require_once "app/core/Database.php";
require_once "app/controllers/HrmApiController.php";
$_GET["from_date"] = "2026-08-01";
$_GET["to_date"] = "2026-08-31";
$_GET["payroll_project_id"] = "2";
$db = new Database();
$controller = new HrmApiController($db->getConnection());
ob_start();
$controller->getJoiningListData();
$content = ob_get_clean();
file_put_contents("test_out.txt", $content);
?>
