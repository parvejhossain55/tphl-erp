<?php
$files = [
    'app/controllers/Phase2/ProjectDrawingController.php',
    'app/controllers/Phase2/Con_DashboardController.php',
    'app/views/phase2/dashboard/draft-dashboard.php',
    'app/views/phase2/project_drawings/create-project-drawing.php',
];
foreach ($files as $f) {
    $output = shell_exec("php -l \"$f\" 2>&1");
    echo "$f: $output\n";
}
