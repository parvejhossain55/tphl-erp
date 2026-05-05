<?php
/**
 * Fix all con-batch2-import redirects to the correct list page routes
 */
$fixes = [
    'LandVisibilityStudyController.php'     => 'con-land-visibility-studies',
    'ArchitectEngineeringController.php'    => 'con-architect-engineering',
    'ProjectDrawingController.php'          => 'con-project-drawings',
    'SoilTestController.php'               => 'con-soil-tests',
];

foreach ($fixes as $filename => $correctRoute) {
    $path = __DIR__ . '/app/controllers/Phase2/' . $filename;
    if (!file_exists($path)) { echo "NOT FOUND: $filename\n"; continue; }
    
    $content = file_get_contents($path);
    $count = substr_count($content, 'con-batch2-import');
    
    $content = str_replace('con-batch2-import', $correctRoute, $content);
    file_put_contents($path, $content);
    
    echo "Fixed $count occurrences in: $filename → /$correctRoute\n";
}

echo "\nDone!\n";
