<?php
/**
 * Final fix: Rewrite the pagetitle div in each Phase 2 list page
 * so that Import CSV button is on the RIGHT side
 */

$fixes = [
    [
        'file'    => 'app/views/phase2/land_visibility_study/land-visibility-study.php',
        'old'     => '<div class="mb-3 pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">' . "\r\n" .
                     '    <div class="">' . "\r\n" .
                     '        <h4 class="mb-2">Land Visibility Study</h4>' . "\r\n" .
                     '    ' . "\n" .
                     '    <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#importLandVisibilityCsvModal">' . "\n" .
                     '        <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV' . "\n" .
                     '    </button>' . "\n" .
                     '</div>' . "\r\n" .
                     '    <a href="<?= $GLOBALS[\'base_url\'] ?>/con-land-visibility-studies/create" class="btn btn-primary ">' . "\r\n" .
                     '        <i class="fa-light fa-plus me-1"></i> Add New Study' . "\r\n" .
                     '    </a>' . "\r\n" .
                     '</div>',
        'new'     => '<div class="mb-3 pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">' . "\r\n" .
                     '    <div class="">' . "\r\n" .
                     '        <h4 class="mb-2">Land Visibility Study</h4>' . "\r\n" .
                     '    </div>' . "\r\n" .
                     '    <div class="d-flex gap-2">' . "\r\n" .
                     '        <a href="<?= $GLOBALS[\'base_url\'] ?>/con-land-visibility-studies/create" class="btn btn-primary ">' . "\r\n" .
                     '            <i class="fa-light fa-plus me-1"></i> Add New Study' . "\r\n" .
                     '        </a>' . "\r\n" .
                     '        <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#importLandVisibilityCsvModal">' . "\r\n" .
                     '            <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV' . "\r\n" .
                     '        </button>' . "\r\n" .
                     '    </div>' . "\r\n" .
                     '</div>',
    ],
    [
        'file'    => 'app/views/phase2/soil_tests/soil-tests.php',
        'search'  => 'soil',
        'modal_id'=> 'importSoilTestCsvModal',
        'add_href'=> '/con-soil-tests/create',
        'add_text'=> 'Add Soil Test',
        'title'   => 'Soil Tests',
    ],
    [
        'file'    => 'app/views/phase2/initial_estimates/initial-estimate-lists.php',
        'modal_id'=> 'importEstimateCsvModal',
        'add_href'=> '/con-estimate-create',
        'add_btn' => 'btn btn-primary btn-sm',
        'title'   => 'Estimate',
    ],
    [
        'file'    => 'app/views/phase2/working_plans/working-plan-lists.php',
        'modal_id'=> 'importWorkingPlanCsvModal',
        'add_href'=> '/con-working-plan-create',
        'title'   => 'Working Plans',
    ],
    [
        'file'    => 'app/views/phase2/target-fixed/target-lists.php',
        'modal_id'=> 'importTargetCsvModal',
        'add_href'=> '/con-target-create',
        'title'   => 'Site Targets',
    ],
];

// Universal approach: read file, find import button, remove it, insert after Add New btn
foreach ($fixes as $entry) {
    $filePath = __DIR__ . '/' . $entry['file'];
    if (!file_exists($filePath)) { echo "NOT FOUND: {$entry['file']}\n"; continue; }
    
    $raw = file_get_contents($filePath);
    
    // Normalize line endings for processing
    $content = str_replace("\r\n", "\n", $raw);
    
    if (!isset($entry['modal_id'])) {
        // Direct replace for land-visibility (already has exact old/new)
        $new = str_replace("\r\n", "\n", $entry['new']);
        $old = str_replace("\r\n", "\n", $entry['old']);
        if (strpos($content, str_replace("\r\n", "\n", $old)) !== false) {
            $content = str_replace($old, $new, $content);
            file_put_contents($filePath, str_replace("\n", "\r\n", $content));
            echo "FIXED: {$entry['file']}\n";
        } else {
            echo "OLD PATTERN NOT FOUND: {$entry['file']}\n";
        }
        continue;
    }
    
    $modalId = $entry['modal_id'];
    
    // Extract import button (flexible pattern)
    $btnPattern = '/<button\s+type="button"\s+class="btn btn-outline-success btn-sm"\s+data-bs-toggle="modal"\s+data-bs-target="#' . preg_quote($modalId, '/') . '">\s*<i[^>]+><\/i>\s*Import CSV\s*<\/button>/s';
    
    if (!preg_match($btnPattern, $content, $btnMatch)) {
        echo "BTN NOT FOUND: {$entry['file']}\n";
        continue;
    }
    
    $importBtn = '<button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#' . $modalId . '">' . "\n" .
                 '            <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV' . "\n" .
                 '        </button>';
    
    // Remove button from current position
    $content = preg_replace($btnPattern, '', $content, 1);
    // Clean up any extra blank lines left behind
    $content = preg_replace('/\n{3,}/', "\n\n", $content);
    
    // Find Add New button and insert import btn after it
    $addHref = $entry['add_href'];
    $addPattern = '/(<a\b[^>]*' . preg_quote($addHref, '/') . '[^>]*>[\s\S]*?<\/a>)/';
    
    if (preg_match($addPattern, $content, $m)) {
        $replacement = $m[1] . "\n        " . $importBtn;
        $content = str_replace($m[1], $replacement, $content);
        file_put_contents($filePath, str_replace("\n", "\r\n", $content));
        echo "FIXED (right): {$entry['file']}\n";
    } else {
        file_put_contents($filePath, str_replace("\n", "\r\n", $content));
        echo "PARTIAL (btn removed, Add New not found): {$entry['file']}\n";
    }
}

// ── Files with completely different structure ──────────────────────────────
// design-types, project-drawings, structure-design, work-types, architect-engineering
// These pages either use modal-trigger as Add btn, or have separate layouts
// Add import btn after the existing primary btn

$appendFixes = [
    [
        'file'     => 'app/views/phase2/design_types/design-types.php',
        'modal_id' => 'importDesignTypeCsvModal',
        'wrap_start'=> '<div class="breadcrumb__btn my-3">',
        'wrap_end'  => '</div>',
        'new_wrap'  => '<div class="breadcrumb__btn my-3 d-flex gap-2 align-items-center">',
    ],
    [
        'file'     => 'app/views/phase2/work_types/work-type-lists.php',
        'modal_id' => 'importWorkTypeCsvModal',
        'wrap_start'=> '<div class="breadcrumb__btn my-3">',
        'wrap_end'  => '</div>',
        'new_wrap'  => '<div class="breadcrumb__btn my-3 d-flex gap-2 align-items-center">',
    ],
];

foreach ($appendFixes as $entry) {
    $filePath = __DIR__ . '/' . $entry['file'];
    if (!file_exists($filePath)) { echo "NOT FOUND: {$entry['file']}\n"; continue; }
    
    $content = str_replace("\r\n", "\n", file_get_contents($filePath));
    $modalId = $entry['modal_id'];
    
    // Check if btn already on right (already inside the breadcrumb__btn div)
    if (strpos($content, 'importDesignTypeCsvModal') !== false || strpos($content, 'importWorkTypeCsvModal') !== false) {
        // The modal might be in a distant part — remove btn and re-add near Add btn
        $btnPattern = '/<button\s+type="button"\s+class="btn btn-outline-success btn-sm"\s+data-bs-toggle="modal"\s+data-bs-target="#' . preg_quote($modalId, '/') . '">\s*<i[^>]+><\/i>\s*Import CSV\s*<\/button>/s';
        $content = preg_replace($btnPattern, '', $content, 1);
    }
    
    $importBtn = '<button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#' . $modalId . '">' . "\n" .
                 '        <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV' . "\n" .
                 '    </button>';
    
    // Wrap the button area to flex
    $content = str_replace($entry['wrap_start'], $entry['new_wrap'], $content);
    // Insert import btn before closing </div> of breadcrumb btn div
    $content = str_replace($entry['wrap_end'] . "\n\n<div", "\n    " . $importBtn . "\n" . $entry['wrap_end'] . "\n\n<div", $content);
    
    file_put_contents($filePath, str_replace("\n", "\r\n", $content));
    echo "FIXED (breadcrumb): {$entry['file']}\n";
}

// ── project-drawings & structure-design ────────────────────────────────────
// These have the import btn modal in a completely separate div after the page
// Remove from there and add to the card with the Add button

$cardFixes = [
    [
        'file'      => 'app/views/phase2/project_drawings/project-drawings.php',
        'modal_id'  => 'importProjectDrawingCsvModal',
        'card_start'=> '<div class="d-flex flex-column flex-sm-row gap-2">',
        'card_end'  => '</div>',
        'insert_before' => '</div>',
    ],
    [
        'file'      => 'app/views/phase2/structure_design/structure-design.php',
        'modal_id'  => 'importStructureDesignCsvModal',
        'card_start'=> '<div class="d-flex flex-column flex-sm-row gap-2">',
        'card_end'  => '</div>',
        'insert_before' => '</div>',
    ],
    [
        'file'      => 'app/views/phase2/architect_engineering/architect-engineering.php',
        'modal_id'  => 'importArchitectCsvModal',
        'card_start'=> null,
        'add_href'  => '/con-architect-engineering/create',
    ],
];

foreach ($cardFixes as $entry) {
    $filePath = __DIR__ . '/' . $entry['file'];
    if (!file_exists($filePath)) { echo "NOT FOUND: {$entry['file']}\n"; continue; }
    
    $content = str_replace("\r\n", "\n", file_get_contents($filePath));
    $modalId = $entry['modal_id'];
    
    // Remove existing import btn
    $btnPattern = '/<button\s+type="button"\s+class="btn btn-outline-success btn-sm"\s+data-bs-toggle="modal"\s+data-bs-target="#' . preg_quote($modalId, '/') . '">\s*[\s\S]*?<\/button>/s';
    $content = preg_replace($btnPattern, '', $content, 1);
    $content = preg_replace('/\n{3,}/', "\n\n", $content);
    
    $importBtn = "\n                " . '<button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#' . $modalId . '">' . "\n" .
                 '                    <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV' . "\n" .
                 '                </button>';
    
    if (isset($entry['add_href'])) {
        $addHref = $entry['add_href'];
        $addPattern = '/(<a\b[^>]*' . preg_quote($addHref, '/') . '[^>]*>[\s\S]*?<\/a>)/';
        if (preg_match($addPattern, $content, $m)) {
            $content = str_replace($m[1], $m[1] . $importBtn, $content);
        }
    } elseif (isset($entry['card_start'])) {
        // Insert before closing </div> of the flex container
        $flexDiv = $entry['card_start'];
        $pos = strpos($content, $flexDiv);
        if ($pos !== false) {
            $closePos = strpos($content, $entry['insert_before'], $pos + strlen($flexDiv));
            if ($closePos !== false) {
                $content = substr($content, 0, $closePos) . $importBtn . "\n            " . substr($content, $closePos);
            }
        }
    }
    
    file_put_contents($filePath, str_replace("\n", "\r\n", $content));
    echo "FIXED (card): {$entry['file']}\n";
}

echo "\nAll Done!\n";
