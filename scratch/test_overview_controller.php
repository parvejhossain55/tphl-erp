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

// Emulate simple MVC Controller base class
class Controller {
    public function view($name, $data = [], $layout = true) {
        echo "Successfully rendered view: $name\n";
        echo "Data count of projects: " . count($data['projects']) . "\n";
        foreach ($data['projects'] as $p) {
            if ($p['id'] == 13) {
                echo "Project 13 - Planned: " . $p['planned_progress_pct'] . "%, Actual: " . $p['progress_pct'] . "%\n";
                foreach ($p['phases'] as $ph) {
                    echo "  Phase: " . $ph['phase_name'] . " - Weight: " . $ph['phase_weight'] . "%, Progress: " . $ph['phase_actual_pct'] . "%, Contribution: " . $ph['phase_contribution'] . "%\n";
                    if ($ph['phase_name'] === 'Structure') {
                        echo "    Approved Qty: " . $ph['total_approved_qty'] . " / BOQ: " . $ph['total_boq'] . "\n";
                    }
                }
            }
        }
    }
}

require_once 'app/controllers/Phase2/Con_ProjectOverviewDashboardController.php';

$controller = new \Phase2\Con_ProjectOverviewDashboardController($db);
$controller->index();
