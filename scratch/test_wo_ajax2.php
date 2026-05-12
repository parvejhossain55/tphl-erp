<?php
require_once 'app/config/database.php';
require_once 'app/core/Controller.php';
require_once 'app/controllers/Phase2/Con_DashboardController.php';

$controller = new Phase2\Con_DashboardController($db);
$controller->ajaxWorkOrders(13);
