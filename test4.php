<?php
session_start();
$_SESSION['user'] = ['id' => 127, 'role_id' => 17];
require 'app/config/config.php';
require 'app/core/Controller.php';
require 'app/core/Model.php';
require 'app/models/Project.php';
require 'app/models/ProjectOtherCost.php';
require 'app/controllers/ProjectOtherCostController.php';

$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$c = new ProjectOtherCostController($db);
$c->index();
