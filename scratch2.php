<?php
session_start();
$_SESSION['user'] = ['id' => 1, 'role_id' => 1];

require_once 'app/config/database.php';
require_once 'app/core/Controller.php';
require_once 'app/core/Model.php';
require_once 'app/models/Con_WorkingPlan.php';
require_once 'app/controllers/Phase2/Con_WorkingPlanController.php';

$_GET['project_id'] = 1;

$c = new Phase2\Con_WorkingPlanController($GLOBALS['db']);
$c->ajaxGetProjectData();
