<?php
/**
 * Fix BOM (Byte Order Mark) issue in all Phase 2 importCsv controllers.
 *
 * Problem: Excel-generated CSV files start with UTF-8 BOM (\xEF\xBB\xBF),
 * which gets prepended to the first header column name.
 * So "Project_Name" becomes "\xEF\xBB\xBFProject_Name" and the lookup fails.
 *
 * Fix: After reading the header row, strip the BOM from the first element.
 * Replace:   $header = array_map('trim', fgetcsv($handle));
 * With:      $header = array_map('trim', fgetcsv($handle));
 *            $header[0] = ltrim($header[0], "\xEF\xBB\xBF");
 */

$controllerDir = __DIR__ . '/app/controllers/Phase2/';

$files = [
    'ArchitectEngineeringController.php',
    'Con_InitialEstimateController.php',
    'Con_TargetFixedController.php',
    'Con_WorkingPlanController.php',
    'Con_WorkTypeController.php',
    'DesignTypeController.php',
    'LandVisibilityStudyController.php',
    'ProjectDrawingController.php',
    'ProjectStructuralDesignController.php',
    'SoilTestController.php',
];

$oldPattern = '$header = array_map(\'trim\', fgetcsv($handle));';
$newPattern = '$header = array_map(\'trim\', fgetcsv($handle));' . "\n" .
              '        $header[0] = ltrim($header[0], "\\xEF\\xBB\\xBF"); // Strip UTF-8 BOM (Excel CSV)';

foreach ($files as $filename) {
    $path = $controllerDir . $filename;
    if (!file_exists($path)) {
        echo "NOT FOUND: $filename\n";
        continue;
    }

    $content = file_get_contents($path);

    if (strpos($content, 'Strip UTF-8 BOM') !== false) {
        echo "SKIP (already fixed): $filename\n";
        continue;
    }

    $count = substr_count($content, $oldPattern);
    if ($count === 0) {
        echo "PATTERN NOT FOUND: $filename\n";
        continue;
    }

    // Replace ALL occurrences (some files may have the pattern multiple times)
    $content = str_replace($oldPattern, $newPattern, $content);
    file_put_contents($path, $content);
    echo "FIXED ($count occurrence" . ($count > 1 ? 's' : '') . "): $filename\n";
}

echo "\nDone!\n";
