<?php
// Simulate the environment of TPHL-ERP
$_SESSION['user'] = [
    'id' => 1,
    'full_name' => 'Farzana',
    'role_id' => 2, // Admin or some role
    'permissions' => []
];

// Mock isActive
function isActive($pages, $current_route) {
    return '';
}

// Mock isMenuOpen
function isMenuOpen($pages, $current_route) {
    return '';
}

// Mock hasPermission
function hasPermission($perm) {
    return true;
}

// Mock getImage / getSetting
function getImage($a, $b) { return ''; }
function getSetting($a) { return ''; }
function getFollowUpCount($a, $b) { return 0; }

$base_url = '/TPHL-ERP';
$GLOBALS['base_url'] = '/TPHL-ERP';
$current_route = 'home';

ob_start();
include __DIR__ . '/../app/views/layouts/sidebar.php';
$html = ob_get_clean();

if (strpos($html, 'Tutorial Video') !== false) {
    echo "SUCCESS: 'Tutorial Video' is found in the rendered HTML!\n";
} else {
    echo "FAILED: 'Tutorial Video' is NOT found in the rendered HTML!\n";
}

// Print last 500 characters of the rendered HTML to see the end of the sidebar
echo "\n--- LAST 1000 CHARS OF HTML ---\n";
echo substr($html, -1000);
?>
