<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/helpers/helper.php';

$root = dirname(__DIR__);
$config = require $root . "/app/config/config.php";

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

$database = new Database($config);
$db = $database->conn;

echo "Attempting to instantiate Phase2\\Con_WorkOrderController...\n";
try {
    $controller = new Phase2\Con_WorkOrderController($db);
    echo "SUCCESS: Controller instantiated successfully!\n";
    
    echo "Testing ajaxGetWorkTypesByDesign for design_id 2 (Structural drawing)...\n";
    $_GET['design_id'] = 2;
    
    ob_start();
    $controller->ajaxGetWorkTypesByDesign();
    $output = ob_get_clean();
    
    echo "Output: \n" . $output . "\n";
    
    echo "Testing ajaxGetWorkTypesByDesign for design_id 1 (Architectural drawing)...\n";
    $_GET['design_id'] = 1;
    
    ob_start();
    $controller->ajaxGetWorkTypesByDesign();
    $output = ob_get_clean();
    
    echo "Output: \n" . $output . "\n";
    
    echo "Test completed successfully!\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n";
}
