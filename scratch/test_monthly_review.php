<?php
// Set up database connection
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Set up mock session
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['user'] = ['role_id' => 1, 'id' => 1];

// Require files
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/controllers/Phase2/Con_MonthlyReviewController.php';

$controller = new Phase2\Con_MonthlyReviewController($db);

// We want to test show($projectId, $phaseId, $yearMonth)
// Project ID: 13, Phase ID: 1, Month: '2026-06'
// Let's invoke the show method or at least check if it compiles without syntax errors.
echo "Testing compilation and loading:\n";
try {
    // To prevent the controller from calling view() and trying to render the HTML (which will exit or fail),
    // we can inspect the code or mock view method if possible. Since we cannot easily mock the view method (it's inherited),
    // let's do a syntax check using php -l on the controller file first.
    echo "MonthlyReviewController syntax check: ";
    passthru('php -l ' . escapeshellarg(__DIR__ . '/../app/controllers/Phase2/Con_MonthlyReviewController.php'));
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
