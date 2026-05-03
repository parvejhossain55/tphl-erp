<?php
/**
 * Fix Import CSV button position to RIGHT side in all Phase 2 list pages
 * Direct string manipulation based on actual file structure
 */

$files = [
    // [file, modal_id, Add New link text/href snippet, page title]
    [
        'file'     => 'app/views/phase2/land_visibility_study/land-visibility-study.php',
        'modal_id' => 'importLandVisibilityCsvModal',
        'add_new'  => '/con-land-visibility-studies/create',
        'btn_text' => '<i class="fa-light fa-plus me-1"></i> Add New Study',
        'btn_class'=> 'btn btn-primary ',
    ],
    [
        'file'     => 'app/views/phase2/architect_engineering/architect-engineering.php',
        'modal_id' => 'importArchitectCsvModal',
        'add_new'  => '/con-architect-engineering/create',
        'btn_text' => null,
        'btn_class'=> 'btn btn-primary',
    ],
    [
        'file'     => 'app/views/phase2/soil_tests/soil-tests.php',
        'modal_id' => 'importSoilTestCsvModal',
        'add_new'  => '/con-soil-tests/create',
        'btn_text' => null,
        'btn_class'=> 'btn btn-primary',
    ],
    [
        'file'     => 'app/views/phase2/initial_estimates/initial-estimate-lists.php',
        'modal_id' => 'importEstimateCsvModal',
        'add_new'  => '/con-estimate-create',
        'btn_text' => null,
        'btn_class'=> 'btn btn-primary',
    ],
];

// For files where the button is INSIDE the left <div>, move it outside to the right
foreach ($files as $entry) {
    $filePath = __DIR__ . '/' . $entry['file'];
    if (!file_exists($filePath)) { echo "NOT FOUND: {$entry['file']}\n"; continue; }

    $content = file_get_contents($filePath);
    $modalId = $entry['modal_id'];

    // Build the import button string
    $importBtn = '<button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#' . $modalId . '">' . "\n" .
                 '        <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV' . "\n" .
                 '    </button>';

    // Remove the import button from wherever it currently is (inside left div or anywhere)
    // It could be in different forms, use a flexible regex
    $btnRegex = '/' .
        '\s*<button\s+type="button"\s+class="btn btn-outline-success btn-sm"\s+data-bs-toggle="modal"\s+data-bs-target="#' . preg_quote($modalId, '/') . '">' .
        '[\s\S]*?' .
        '<\/button>' .
        '/';

    $btnFound = preg_match($btnRegex, $content, $matches);
    if (!$btnFound) {
        echo "SKIP (btn not found): {$entry['file']}\n";
        continue;
    }

    // Remove current import button
    $content = preg_replace($btnRegex, '', $content, 1);

    // Now fix the pagetitle div structure:
    // The left <div> may have gotten broken. Rebuild clean pagetitle.
    // Find the pagetitle div, clean up and ensure button is on right
    // Strategy: find Add New <a> href containing the add_new path, inject import btn after closing </a>

    $addNewHref = $entry['add_new'];
    // Match: <a href="...{add_new}..." class="btn ...">...</a>
    $addNewPattern = '/(<a\s[^>]*href="[^"]*' . preg_quote($addNewHref, '/') . '[^"]*"[^>]*>[\s\S]*?<\/a>)/';

    if (preg_match($addNewPattern, $content, $m)) {
        $addNewFull = $m[1];
        $replacement = $addNewFull . "\n    " . $importBtn;
        $content = str_replace($addNewFull, $replacement, $content);
        echo "FIXED (right): {$entry['file']}\n";
    } else {
        echo "FALLBACK: {$entry['file']}\n";
    }

    // Also clean up any broken </div> left from removal
    // The left <div></div> might now be empty — collapse it
    $content = preg_replace('/<div class="">\s*<h4[^>]*>[\s\S]*?<\/h4>\s*\n?\s*<\/div>/', '<div class=""><h4 class="mb-2">' . '...</h4></div>', $content);

    file_put_contents($filePath, $content);
}

echo "\nDone!\n";
