<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root = dirname(__DIR__);
$GLOBALS['base_url'] = '/TPHL-ERP';

// Helper and Autoloader
require_once($root . "/app/helpers/helper.php");
require_once($root . "/vendor/autoload.php");

spl_autoload_register(function ($class) use ($root) {
    $class = str_replace('\\', '/', $class);
    $paths = [
        $root . "/app/core/$class.php",
        $root . "/app/models/$class.php",
        $root . "/app/controllers/$class.php",
        $root . "/app/controllers/Phase2/$class.php",
        $root . "/app/services/$class.php",
        $root . "/app/helpers/$class.php",
        $root . "/app/ai/$class.php"
    ];
    foreach ($paths as $file) {
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

$config = require $root . "/app/config/config.php";
$database = new Database($config);
$db = $database->conn;

$poModel = new Con_PurchaseOrder($db);
echo "Database Host: " . $config['db_host'] . "\n";
echo "Database Name: " . $config['db_name'] . "\n";

echo "--- RUNNING poModel->find(1) --- \n";
try {
    $po = $poModel->find(1);
    if ($po) {
        echo "Found PO:\n";
        print_r($po);
    } else {
        echo "PO #1 not found.\n";
    }
} catch (Throwable $e) {
    echo "ERROR OCCURRED: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
