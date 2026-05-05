<?php
/**
 * Move Import CSV button to RIGHT side (after Add New button) in all Phase 2 list views
 */

$files = [
    'app/views/phase2/land_visibility_study/land-visibility-study.php',
    'app/views/phase2/architect_engineering/architect-engineering.php',
    'app/views/phase2/design_types/design-types.php',
    'app/views/phase2/project_drawings/project-drawings.php',
    'app/views/phase2/structure_design/structure-design.php',
    'app/views/phase2/soil_tests/soil-tests.php',
    'app/views/phase2/work_types/work-type-lists.php',
    'app/views/phase2/initial_estimates/initial-estimate-lists.php',
    'app/views/phase2/working_plans/working-plan-lists.php',
    'app/views/phase2/target-fixed/target-lists.php',
];

foreach ($files as $relPath) {
    $filePath = __DIR__ . '/' . $relPath;
    if (!file_exists($filePath)) { echo "NOT FOUND: $relPath\n"; continue; }

    $content = file_get_contents($filePath);
    $original = $content;

    // Pattern: import button appears BEFORE the Add New <a> link
    // We need to move it AFTER the Add New <a> link
    // The import button looks like:
    // <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" ...>Import CSV</button>
    // Followed by the Add New <a>...

    // Strategy: 
    // 1. Extract the import button
    // 2. Remove it from its current position
    // 3. Find the closing </a> of the Add New button
    // 4. Insert it after that </a>

    $importBtnPattern = '/<button type="button" class="btn btn-outline-success btn-sm"[^>]+data-bs-toggle="modal"[^>]+>\s*<i class="bi bi-file-earmark-arrow-up[^"]*"><\/i>\s*Import CSV\s*<\/button>\s*\n?/';

    if (!preg_match($importBtnPattern, $content, $matches)) {
        echo "SKIP (no import btn found): $relPath\n";
        continue;
    }

    $importBtn = trim($matches[0]);

    // Remove import button from current position
    $content = preg_replace($importBtnPattern, '', $content, 1);

    // Find the "Add New" / "New ..." / "Create" link — it's the last <a> tag in the pagetitle div
    // Insert import button after it
    // Look for: </a> that is inside the pagetitle div (the main CTA button)
    // We'll find the first <a ... btn btn-primary ...>...</a> and insert after it
    $addNewPattern = '/(<a\b[^>]+class="[^"]*btn[^"]*btn-primary[^"]*"[^>]*>.*?<\/a>)/s';

    if (preg_match($addNewPattern, $content, $m)) {
        $replacement = $m[1] . "\n    " . $importBtn;
        $content = preg_replace($addNewPattern, $replacement, $content, 1);
        echo "FIXED (right): $relPath\n";
    } else {
        // Fallback: just put it back as-is (no change)
        echo "FALLBACK (no primary btn found): $relPath\n";
        $content = $original;
    }

    file_put_contents($filePath, $content);
}

echo "\nDone!\n";
