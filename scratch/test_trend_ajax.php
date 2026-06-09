<?php
global $base_url;
$base_url = 'http://localhost';
$GLOBALS['base_url'] = 'http://localhost';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Emulate Controller
class Controller {
    public function view($name, $data = [], $layout = true) {}
}

require_once 'app/controllers/Phase2/Con_ProjectOverviewDashboardController.php';
$controller = new \Phase2\Con_ProjectOverviewDashboardController($db);

// We will simulate $_GET queries and run index()
$_SESSION['user'] = ['id' => 1, 'role_id' => 1];

echo "=== TEST ALL-TIME TREND FOR PORTFOLIO (project_id = 0) ===\n";
$_GET = ['ajax_get_trend_data' => 1, 'project_id' => 0, 'mode' => 'all_time'];
ob_start();
try {
    $controller->index();
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
$out = ob_get_clean();
echo $out . "\n\n";

echo "=== TEST ALL-TIME TREND FOR PROJECT 13 ===\n";
$_GET = ['ajax_get_trend_data' => 1, 'project_id' => 13, 'mode' => 'all_time'];
ob_start();
try {
    $controller->index();
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
$out = ob_get_clean();
echo $out . "\n\n";

echo "=== TEST YEARLY TREND FOR PROJECT 13 ===\n";
$_GET = ['ajax_get_trend_data' => 1, 'project_id' => 13, 'mode' => 'yearly', 'year' => 2026];
ob_start();
try {
    $controller->index();
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
$out = ob_get_clean();
echo $out . "\n\n";

echo "=== TEST MONTHLY TREND (Week-wise) FOR PROJECT 13 TARGET 1 ===\n";
$_GET = ['ajax_get_trend_data' => 1, 'project_id' => 13, 'mode' => 'monthly', 'target_id' => 1];
ob_start();
try {
    $controller->index();
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
$out = ob_get_clean();
echo $out . "\n\n";
