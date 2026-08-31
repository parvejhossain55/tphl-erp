<?php
require 'app/bootstrap.php';
require_once 'app/core/Controller.php';
require_once 'app/controllers/HrmApiController.php';
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$ctrl = new HrmApiController($db);
$ctrl->getTipsoiLateHistory(55);