<?php

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/SqlValidator.php';
require_once __DIR__ . '/../app/helpers/DbSchemaHelper.php';
require_once __DIR__ . '/../app/ai/AiBrain.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load config
$config = require __DIR__ . '/../app/config/config.php';

// Establish primary connection
$database = new Database($config);
$db = $database->conn;

echo "Calling AiBrain::handleMessage('hi', 1, \$db)...\n";
$reply = \ai\AiBrain::handleMessage("hi", 1, $db);

echo "\n--- Response Reply ---\n";
echo $reply . "\n";
echo "----------------------\n";
