<?php
$files = [
    'app/controllers/Phase2/Con_DashboardController.php',
    'app/controllers/Phase2/Con_PurchaseRequestController.php',
    'app/models/Con_PurchaseRequest.php',
    'app/views/phase2/dashboard/purchase-team-lead-dashboard.php',
    'app/views/phase2/purchase-requests/create-purchase-request.php',
    'app/views/phase2/boq/show-boq.php',
];

$allOk = true;
foreach ($files as $f) {
    $output = shell_exec("php -l \"{$f}\" 2>&1");
    $ok = strpos($output, 'No syntax errors') !== false;
    echo ($ok ? '✅' : '❌') . " {$f}\n";
    if (!$ok) { echo "   → {$output}\n"; $allOk = false; }
}
echo $allOk ? "\n✅ All files passed lint check!\n" : "\n❌ Some files have errors!\n";
