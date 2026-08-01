<?php
require_once __DIR__ . '/app/core/Controller.php';
require_once __DIR__ . '/app/models/AssetManagement.php';
require_once __DIR__ . '/app/controllers/Phase2/AssetManagementController.php';

$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli("127.0.0.1", "root", "", "tphl_db");

$_SESSION['user'] = ['id' => 1, 'role_id' => 1];

$controller = new \Phase2\AssetManagementController($db);
echo "✓ SUCCESS: Phase2\\AssetManagementController instantiated successfully!\n";
