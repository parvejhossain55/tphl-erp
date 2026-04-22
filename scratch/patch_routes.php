<?php
$file = __DIR__ . '/../public/index.php';
$content = file_get_contents($file);

// Find the SECOND occurrence of the dashboard route (the one in the Phase2 section at line ~711)
// and replace it with the full set of new routes
$old = "// \u2500\u2500 Dashboard \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\r\n\$routes['con-dashboard'] = ['controller' => 'Phase2\\Con_DashboardController', 'method' => 'index'];";

$new = "// \u2500\u2500 Dashboard + Project Detail + AJAX Tab Routes \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\r\n"
     . "\$routes['con-dashboard']           = ['controller' => 'Phase2\\Con_DashboardController', 'method' => 'index'];\r\n"
     . "\$routes['con-project/(\\d+)']       = ['controller' => 'Phase2\\Con_DashboardController', 'method' => 'projectDetail'];\r\n"
     . "\$routes['con-ajax-workplan/(\\d+)'] = ['controller' => 'Phase2\\Con_DashboardController', 'method' => 'ajaxWorkPlan'];\r\n"
     . "\$routes['con-ajax-targets/(\\d+)']  = ['controller' => 'Phase2\\Con_DashboardController', 'method' => 'ajaxTargets'];\r\n"
     . "\$routes['con-ajax-weekly/(\\d+)']   = ['controller' => 'Phase2\\Con_DashboardController', 'method' => 'ajaxWeeklyData'];";

// Count occurrences
$count = substr_count($content, "\$routes['con-dashboard']");
echo "Found con-dashboard $count times\n";

// Replace the SECOND occurrence only
$pos1 = strpos($content, "\$routes['con-dashboard']");
$pos2 = strpos($content, "\$routes['con-dashboard']", $pos1 + 1);
echo "First at: $pos1, Second at: $pos2\n";

// Replace the block at pos2 (the one in Phase2 section)
if ($pos2 !== false) {
    // Find the line start and end
    $lineEnd   = strpos($content, "\n", $pos2);
    $lineStart = strrpos(substr($content, 0, $pos2), "\n") + 1;
    
    // Also grab the comment line before it
    $commentStart = strrpos(substr($content, 0, $lineStart - 1), "\n") + 1;
    $segment = substr($content, $commentStart, $lineEnd - $commentStart + 1);
    echo "Segment found:\n$segment\n";

    $content = substr($content, 0, $commentStart) . $new . "\r\n" . substr($content, $lineEnd + 1);
    file_put_contents($file, $content);
    echo "SUCCESS: Routes patched!\n";
} else {
    echo "ERROR: Could not find second occurrence\n";
}
