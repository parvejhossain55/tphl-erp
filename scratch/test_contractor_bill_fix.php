<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/helpers/helper.php';

// Mock DB connection if needed, or instantiate database
$root = dirname(__DIR__);
$config = require $root . "/app/config/config.php";

spl_autoload_register(function ($class) use ($root) {
    $class = str_replace('\\', '/', $class);

    $paths = [
        $root . "/app/core/$class.php",
        $root . "/app/models/$class.php",
        $root . "/app/controllers/$class.php",
        $root . "/app/controllers/Phase2/$class.php",   // Phase 2 controllers
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

echo "--- PROJECTS IN DATABASE ---\n";
$pRes = $db->query("SELECT id, name FROM projects");
while ($row = $pRes->fetch_assoc()) {
    echo "ID: {$row['id']} - Name: {$row['name']}\n";
}

echo "\n--- WEEKLY PROGRESS RECORDS IN DATABASE ---\n";
$wRes = $db->query("SELECT pwp.id, pwp.project_id, pwp.overall_progress_pct, pwp.entry_status, pp.phase_name, pmt.target_year
                    FROM phase_weekly_progress pwp
                    INNER JOIN phase_monthly_targets pmt ON pmt.id = pwp.target_id
                    INNER JOIN project_phases pp ON pp.id = pwp.phase_id");
while ($row = $wRes->fetch_assoc()) {
    echo "ID: {$row['id']} - ProjID: {$row['project_id']} - Phase: {$row['phase_name']} - Year: {$row['target_year']} - Progress: {$row['overall_progress_pct']}% - Status: {$row['entry_status']}\n";
}

echo "\nAttempting to instantiate Phase2\\Con_ContractorBillController...\n";
try {
    $controller = new Phase2\Con_ContractorBillController($db);
    echo "SUCCESS: Controller instantiated successfully!\n";
    
    // Test the helper method via reflection (it is private)
    $ref = new ReflectionClass($controller);
    $method = $ref->getMethod('findTarget');
    $method->setAccessible(true);
    
    echo "Testing findTarget with a non-existent target id (999999)...\n";
    $res = $method->invoke($controller, 999999);
    var_dump($res);
    
    echo "Testing ajaxGetTargetsByProject for project with ID (13 or first found ID)...\n";
    // Let's temporarily update one weekly progress session to 'Approved' for testing
    $db->query("UPDATE phase_weekly_progress SET entry_status = 'Approved' WHERE id = 1");
    
    $_GET['project_id'] = 13; 
    ob_start();
    $controller->ajaxGetTargetsByProject();
    $output = ob_get_clean();
    echo "AJAX JSON Output (after setting status to Approved):\n" . $output . "\n";
    
    // Restore it back
    $db->query("UPDATE phase_weekly_progress SET entry_status = 'Submitted' WHERE id = 1");
    
    echo "Test completed successfully!\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
