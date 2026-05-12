<?php
session_start();
$_SESSION['user'] = ['id' => 1, 'role_id' => 1]; // Admin role
$GLOBALS['base_url'] = 'http://localhost';

require __DIR__ . '/app/config/config.php';
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');

require __DIR__ . '/app/core/Controller.php';
require __DIR__ . '/app/core/Model.php';
require __DIR__ . '/app/models/Project.php';
require __DIR__ . '/app/models/Con_WorkType.php';
require __DIR__ . '/app/models/Con_MaterialReceiving.php';
require __DIR__ . '/app/models/Con_QCChecklist.php';
require __DIR__ . '/app/models/Con_QCInspection.php';
require __DIR__ . '/app/models/Con_QCInspectionItem.php';
require __DIR__ . '/app/models/Con_QCMaterialTest.php';
require __DIR__ . '/app/models/Con_QCTestType.php';

// Stub getImage
function getImage() { return ''; }
function isActive() { return ''; }

require __DIR__ . '/app/controllers/Phase2/QCController.php';

$controller = new \Phase2\QCController($db);
ob_start();
$controller->createMaterialTest();
$output = ob_get_clean();

// Check if options are in the HTML
if (strpos($output, '<option value="1">GRN-1') !== false) {
    echo "SUCCESS: GRN option is in HTML!\n";
} else {
    echo "ERROR: GRN option missing from HTML!\n";
}

if (strpos($output, 'The Prime Heights') !== false) {
    echo "SUCCESS: Project name is in HTML!\n";
} else {
    echo "ERROR: Project name missing from HTML!\n";
}
