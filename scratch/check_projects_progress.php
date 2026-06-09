<?php
$GLOBALS['base_url'] = 'http://localhost';
session_start();
$_SESSION['user'] = ['id' => 1, 'role_id' => 1];

$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

require_once 'app/core/Controller.php';
require_once 'app/controllers/Phase2/Con_ProjectOverviewDashboardController.php';

$controller = new Phase2\Con_ProjectOverviewDashboardController($db);

$ref = new ReflectionMethod($controller, 'calculatePhaseBasedProgress');
$ref->setAccessible(true);

$res = $db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL");
$projectIds = [];
while ($row = $res->fetch_assoc()) {
    $projectIds[] = (int)$row['id'];
    echo "ID: " . $row['id'] . " - Name: " . $row['name'] . "\n";
}

$progressMap = $ref->invoke($controller, $projectIds);
foreach ($progressMap as $pid => $data) {
    echo "Project ID $pid - Actual: " . $data['actual'] . "%, Planned: " . $data['planned'] . "%\n";
}
