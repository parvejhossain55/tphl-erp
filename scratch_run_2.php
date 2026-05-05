<?php
$config = require __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/core/Controller.php';
require_once __DIR__ . '/app/core/Model.php';
require_once __DIR__ . '/app/models/Con_MaterialReturn.php';
require_once __DIR__ . '/app/controllers/Phase2/Con_MaterialReturnController.php';

$database = new Database($config);
$db = $database->conn;

$controller = new \Phase2\Con_MaterialReturnController($db);
ob_start();
$controller->index();
$output = ob_get_clean();
echo "Output length: " . strlen($output) . "\n";
echo substr($output, 0, 500);
