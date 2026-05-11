<?php
$files = [
    'app/controllers/Phase2/Con_TargetFixedController.php',
    'app/controllers/Phase2/Con_WorkingPlanController.php',
    'app/controllers/Phase2/Con_WeeklyReportController.php',
    'app/controllers/Phase2/Con_InitialEstimateController.php',
    'app/controllers/Phase2/QCController.php',
    'app/controllers/Phase2/Con_DashboardController.php',
    'app/controllers/Phase2/Con_BOQController.php',
    'app/controllers/Phase2/Con_WorkOrderController.php'
];
foreach($files as $file) {
    if(!file_exists($file)) continue;
    $content = file_get_contents($file);
    $content = str_replace('if (!in_array($roleId, [1, 3, 15, 22])) {', 'if (!in_array($roleId, [1, 3, 22])) {', $content);
    $content = str_replace("if (in_array(\$roleId, [1, 3, 15, 22])) return '1=1';", "if (in_array(\$roleId, [1, 3, 22])) return '1=1';", $content);
    file_put_contents($file, $content);
}
echo "Done";
