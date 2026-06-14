<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('BASE_PATH', 'c:/xampp/htdocs/TPHL-ERP');
require_once BASE_PATH . '/app/helpers/DbSchemaHelper.php';
require_once BASE_PATH . '/app/ai/AiBrain.php';

$validatorFile = BASE_PATH . '/app/ai/SqlValidator.php';
if (file_exists($validatorFile)) {
    require_once $validatorFile;
}

$config = require BASE_PATH . '/app/config/config.php';
$db = new \mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$db->set_charset("utf8mb4");

echo "Testing live AiBrain::handleMessage for budget details (170,000 spent)...\n";
try {
    $reply = \ai\AiBrain::handleMessage("test project from developer প্রজেক্টটির মোট বাজেট কত, এ পর্যন্ত কত বাজেট ব্যবহার করা হয়েছে (Spent), এবং বাকি বাজেট কত আছে?", 1, $db);
    echo "AI Reply:\n" . $reply . "\n";
} catch (\Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
$db->close();
