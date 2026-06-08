<?php
session_start();
$_SESSION['user'] = ['id' => 1, 'role_id' => 1];

$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

require_once 'app/core/Controller.php';
require_once 'app/controllers/Phase2/Con_ProjectOverviewDashboardController.php';

$controller = new Phase2\Con_ProjectOverviewDashboardController($db);

$queryString = $argv[1] ?? '';
$_GET = [];
parse_str($queryString, $_GET);

ob_start();
$controller->index();
$output = ob_get_clean();
echo $output;
