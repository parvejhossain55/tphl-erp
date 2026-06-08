<?php
$GLOBALS['base_url'] = 'http://localhost';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user'] = [
    'id' => 1,
    'role_id' => 1
];

$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Helpers
function cpFmt($amount) {
    return '৳' . number_format($amount);
}

function getImage($type, $img) {
    return $img;
}

// Instantiate controller to get data
require_once 'app/core/Controller.php';
require_once 'app/controllers/Phase2/Con_DashboardController.php';
$controller = new \Phase2\Con_DashboardController($db);

$ref = new ReflectionClass($controller);
$method = $ref->getMethod('calculatePhaseBasedProgress');
$method->setAccessible(true);
$dynamicProgressMap = $method->invoke($controller, [13]);

// Fetch project 13 from DB
$sql = "
    SELECT p.*, p.total_share AS total_floors, COALESCE(l.name,'') AS location_name, 0 AS total_budget, 0 AS total_spent, NULL AS expected_handover_date
    FROM projects p
    LEFT JOIN locations l ON l.id = p.location_id
    WHERE p.id = 13
";
$pr = $db->query($sql)->fetch_assoc();
$pr['progress_pct'] = $dynamicProgressMap[13]['actual'];
$pr['planned_progress_pct'] = $dynamicProgressMap[13]['planned'];
$pr['overall_project_actual'] = $dynamicProgressMap[13]['actual'];
$pr['overall_project_planned'] = $dynamicProgressMap[13]['planned'];
$pr['phases'] = $dynamicProgressMap[13]['phases'];

$projects = [$pr];
$base = $GLOBALS['base_url'];

// Mock stats and other variables passed to cons-dashboard
$stats = [
    'total_projects' => 1,
    'total_budget' => 5000000,
    'total_material' => 3000000,
    'total_labour' => 2000000,
    'stock_alerts' => 0,
    'pending_approvals' => 0,
    'budget_requests' => 0,
    'lab_alerts' => 0
];
$budgetUtil = ['total' => 5000000, 'spent' => 1000000, 'pct' => 20, 'remaining' => 4000000];
$stockAlerts = [];
$pendingAlerts = [];
$soilAlerts = [];
$pendingInitialBudgets = [];

// Include cons-dashboard.php
ob_start();
try {
    include 'app/views/phase2/dashboard/cons-dashboard.php';
    $output = ob_get_clean();
    echo "cons-dashboard.php rendered successfully!\n";
    echo "Length of output HTML: " . strlen($output) . " characters.\n";
    // Check if the new table elements and collapsible parts are in the HTML
    if (strpos($output, 'projectOverviewTable') !== false) {
        echo "Success: Found projectOverviewTable inside the rendered view!\n";
    } else {
        echo "Warning: projectOverviewTable NOT found!\n";
    }
    if (strpos($output, 'phases-breakdown-13') !== false) {
        echo "Success: Found collapsible breakdown row for Project 13!\n";
    } else {
        echo "Warning: phases-breakdown-13 NOT found!\n";
    }
} catch (Exception $e) {
    ob_get_clean();
    echo "Error rendering cons-dashboard: " . $e->getMessage() . "\n";
}
