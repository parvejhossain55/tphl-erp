<?php
$file = __DIR__ . '/public/index.php';
$content = file_get_contents($file);

$newRoutes = "
// === BATCH 2 IMPORTS ===
\$router->get('/con-batch2-import', function() use (\$db) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset(\$_SESSION['user'])) { header('Location: ' . \$GLOBALS['base_url'] . '/login'); exit; }
    require_once __DIR__ . '/../app/views/phase2/import/batch2.php';
});

\$router->post('/con-land-visibility-import', 'Phase2\LandVisibilityStudyController@importCsv');
\$router->post('/con-soil-test-import', 'Phase2\SoilTestController@importCsv');
\$router->post('/con-project-drawing-import', 'Phase2\ProjectDrawingController@importCsv');
\$router->post('/con-architect-engineering-import', 'Phase2\ArchitectEngineeringController@importCsv');
";

if (strpos($content, '/con-batch2-import') === false) {
    // Insert right before "// Construction Vendor Penalty" or somewhere appropriate
    $content = str_replace("// Construction Vendor Penalty", $newRoutes . "\n// Construction Vendor Penalty", $content);
    file_put_contents($file, $content);
    echo "Routes added.\n";
} else {
    echo "Routes already exist.\n";
}
