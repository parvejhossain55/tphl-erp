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
function toBengaliNum($number) {
    $en = array('0','1','2','3','4','5','6','7','8','9','.');
    $bn = array('০','১','২','৩','৪','৫','৬','৭','৮','৯','.');
    return str_replace($en, $bn, (string)$number);
}

function getBengaliMonthName($dateStr) {
    if (empty($dateStr)) return '';
    $bengaliMonths = [
        'January' => 'জানুয়ারি', 'February' => 'ফেব্রুয়ারি', 'March' => 'মার্চ',
        'April' => 'এপ্রিল', 'May' => 'মে', 'June' => 'জুন',
        'July' => 'জুলাই', 'August' => 'আগস্ট', 'September' => 'সেপ্টেম্বর',
        'October' => 'অক্টোবর', 'November' => 'নভেম্বর', 'December' => 'ডিসেম্বর'
    ];
    $engMonth = date('F', strtotime($dateStr));
    return $bengaliMonths[$engMonth] ?? '';
}

function cpFmt($amount) {
    return '৳' . number_format($amount);
}

function getImage($type, $img) {
    return $img;
}

// Instantiate controller to get data
require_once 'app/core/Controller.php';
require_once 'app/controllers/Phase2/Con_ProjectOverviewDashboardController.php';
$controller = new \Phase2\Con_ProjectOverviewDashboardController($db);

// We use Reflection to call index, but since index renders a view, we can just capture variables or call the calculation method directly
$ref = new ReflectionClass($controller);
$method = $ref->getMethod('calculatePhaseBasedProgress');
$method->setAccessible(true);
$dynamicProgressMap = $method->invoke($controller, [13]);

// Fetch project 13 from DB
$sql = "
    SELECT p.*, COALESCE(l.name,'') AS location_name, 0 AS total_budget, 0 AS total_spent, NULL AS expected_handover_date
    FROM projects p
    LEFT JOIN locations l ON l.id = p.location_id
    WHERE p.id = 13
";
$pr = $db->query($sql)->fetch_assoc();
$pr['calculated_status'] = 'Planning';
$pr['progress_pct'] = $dynamicProgressMap[13]['actual'];
$pr['planned_progress_pct'] = $dynamicProgressMap[13]['planned'];
$pr['phases'] = $dynamicProgressMap[13]['phases'];

$projects = [$pr];

// Include overview-rows.php
ob_start();
include 'app/views/phase2/dashboard/overview-rows.php';
$output = ob_get_clean();

echo "Render Output snippet for Project 13:\n";
// Output the section containing popover content and Contribution/complete texts
echo "Looking for Qty Used in output:\n";
$lines = explode("\n", $output);
$found = false;
foreach ($lines as $i => $line) {
    if (strpos($line, 'Qty Used') !== false) {
        echo "Line " . ($i+1) . ": " . trim($line) . "\n";
        $found = true;
    }
}
if (!$found) {
    echo "No lines with 'Qty Used' found in the render output.\n";
}
