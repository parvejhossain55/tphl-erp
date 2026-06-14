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

echo "Testing live AiBrain::handleMessage for material details with names and categories...\n";
try {
    $reply = \ai\AiBrain::handleMessage("test project from developer প্রজেক্টটির জন্য কি কি মেটেরিয়াল কেনাকাটা বা বরাদ্দের রিকোয়েস্ট (BOQ) করা হয়েছে? মেটেরিয়ালের নাম ও ক্যাটাগরিসহ বিস্তারিত দাও।", 1, $db);
    echo "AI Reply:\n" . $reply . "\n";
} catch (\Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
$db->close();
