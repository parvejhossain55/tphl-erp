<?php
/**
 * Phase 2 Batch 3 CSV Import Injection Script
 * 1) Fix existing modals: remove bg-light, move button to LEFT
 * 2) Add Batch 3 import button+modal to list pages
 * 3) Add importCsv() to Batch 3 controllers
 */

$baseDir = __DIR__;

// ─────────────────────────────────────────────────────────────────────────────
// STEP 1: Fix existing Batch 2 list views
// - Remove class="mb-3 p-2 bg-light rounded" → just remove bg-light+p-2
// - Move import button from right side to left (before Add New button)
// ─────────────────────────────────────────────────────────────────────────────

$batch2Files = [
    'app/views/phase2/land_visibility_study/land-visibility-study.php',
    'app/views/phase2/architect_engineering/architect-engineering.php',
    'app/views/phase2/design_types/design-types.php',
    'app/views/phase2/project_drawings/project-drawings.php',
    'app/views/phase2/structure_design/structure-design.php',
    'app/views/phase2/soil_tests/soil-tests.php',
    'app/views/phase2/work_types/work-type-lists.php',
];

foreach ($batch2Files as $relPath) {
    $filePath = $baseDir . '/' . $relPath;
    if (!file_exists($filePath)) continue;
    $content = file_get_contents($filePath);

    // Fix 1: Remove bg-light p-2 rounded styling from column info div
    $content = str_replace(
        'class="mb-3 p-2 bg-light rounded" style="font-size:12px;"',
        'style="font-size:12px;"',
        $content
    );

    // Fix 2: Move import button to LEFT side of the header
    // Pattern: pagetitle div has <div> + <a ...Add New...> + <button import>
    // We need: pagetitle div has <div class="d-flex gap-2"> [importBtn][addNewBtn]
    // Find the import button line and the Add New link, swap them
    // Detect: button ms-2 data-bs-toggle="modal"
    $content = str_replace(
        '<div class="mb-3 pagetitle d-flex justify-content-between align-items-center">',
        '<div class="mb-3 pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">',
        $content
    );

    // Remove ms-2 from import button (it was needed when on right, not when on left)
    $content = preg_replace(
        '/<button type="button" class="btn btn-outline-success btn-sm ms-2"/',
        '<button type="button" class="btn btn-outline-success btn-sm"',
        $content
    );

    file_put_contents($filePath, $content);
    echo "Fixed Batch2: $relPath\n";
}

// ─────────────────────────────────────────────────────────────────────────────
// STEP 2: Batch 3 modules — add button + modal to list pages
// ─────────────────────────────────────────────────────────────────────────────

$batch3Modules = [
    'initial_estimates' => [
        'file'     => 'app/views/phase2/initial_estimates/initial-estimate-lists.php',
        'modal_id' => 'importEstimateCsvModal',
        'title'    => 'Import Initial Estimate Budgets',
        'route'    => '/con-estimate-import',
        'cols'     => 'Project_Name (required), Work_Type_Name, Total_Area_Sqft, Material_Cost_Total, Labor_Cost_Total, Other_Costs, Total_Estimated_Cost, Start_Date (YYYY-MM-DD), End_Date (YYYY-MM-DD)',
        'find_str' => '<a href="<?= $base_url ?>/con-estimate-create" class="btn btn-primary btn-sm">',
    ],
    'working_plans' => [
        'file'     => 'app/views/phase2/working_plans/working-plan-lists.php',
        'modal_id' => 'importWorkingPlanCsvModal',
        'title'    => 'Import Working Plans',
        'route'    => '/con-working-plan-import',
        'cols'     => 'Project_Name (required), Year (required), Month (1-12 required), Work_Description (required), Planned_Quantity, Estimated_Cost',
        'find_str' => null,  // Will handle separately
    ],
    'target_fixed' => [
        'file'     => 'app/views/phase2/target-fixed/target-lists.php',
        'modal_id' => 'importTargetCsvModal',
        'title'    => 'Import Targets',
        'route'    => '/con-target-import',
        'cols'     => 'Project_Name (required), Target_Title, Year (required), Month (1-12 required), Week_Number (1-4), Target_Percentage (0-100 required)',
        'find_str' => null,  // Will handle separately
    ],
];

foreach ($batch3Modules as $key => $mod) {
    $filePath = $baseDir . '/' . $mod['file'];
    if (!file_exists($filePath)) { echo "NOT FOUND: {$mod['file']}\n"; continue; }
    $content = file_get_contents($filePath);
    if (strpos($content, $mod['modal_id']) !== false) { echo "SKIP: {$mod['file']}\n"; continue; }

    $modalId = $mod['modal_id'];
    $title   = $mod['title'];
    $route   = $mod['route'];
    $cols    = $mod['cols'];

    $importBtn = '<button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#' . $modalId . '"><i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV</button>';

    $modal = <<<PHP


<!-- Import CSV Modal -->
<div class="modal fade" id="{$modalId}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{$title}</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal"><i class="fa-solid fa-xmark-large"></i></button>
            </div>
            <div class="modal-body">
                <form action="<?= \$GLOBALS['base_url'] ?>{$route}" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>
                    <div class="mb-3" style="font-size:12px;">
                        <strong>Required CSV Columns:</strong><br>
                        <code>{$cols}</code>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm ms-2"><i class="bi bi-upload me-1"></i> Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
PHP;

    // For estimate-lists: inject button before Add New link in pagetitle
    if ($mod['find_str']) {
        $content = str_replace(
            $mod['find_str'],
            $importBtn . "\n    " . $mod['find_str'],
            $content
        );
    } else {
        // Generic: insert button in pagetitle div — after <div> opening, before </div>
        // Look for pagetitle area: find the closing </div> of the left sub-div
        // Inject button right before the last element in pagetitle
        $content = preg_replace(
            '/(class="mb-3 pagetitle[^"]*"[^>]*>)(.*?)(<\/div>)/ms',
            '$1$2$3' . "\n    " . $importBtn,
            $content,
            1
        );
    }

    // Inject modal before </section> or at end before <script> or at file end
    if (strpos($content, '</section>') !== false) {
        $content = str_replace('</section>', '</section>' . $modal, $content);
    } elseif (strpos($content, '<script>') !== false) {
        $content = str_replace('<script>', $modal . "\n<script>", $content);
    } else {
        $content .= $modal;
    }

    // Wrap pagetitle with flex-wrap
    $content = str_replace(
        'class="mb-3 pagetitle d-flex justify-content-between align-items-center"',
        'class="mb-3 pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2"',
        $content
    );

    file_put_contents($filePath, $content);
    echo "UPDATED Batch3: {$mod['file']}\n";
}

echo "\nDone!\n";
