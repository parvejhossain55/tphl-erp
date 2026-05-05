<?php
/**
 * Inject Import CSV button + Modal into Phase 2 list views
 * Pattern matched from Phase 1 lead-lists.php
 */

$modules = [
    'land_visibility_study' => [
        'file'        => 'app/views/phase2/land_visibility_study/land-visibility-study.php',
        'modal_id'    => 'importLandVisibilityCsvModal',
        'title'       => 'Import Land Visibility Study',
        'route'       => '/con-land-visibility-import',
        'columns'     => ['Project_Name (required)', 'Land_Legal_Doc_Status (Pending/Verified/Rejected)', 'Digital_Survey_Report_Link', 'Govt_Approval_Doc_Link', 'Others_Doc_Link'],
        'btn_inject_after' => 'class="btn btn-primary ">',
        'btn_label'   => 'Import CSV',
    ],
    'architect_engineering' => [
        'file'        => 'app/views/phase2/architect_engineering/architect-engineering.php',
        'modal_id'    => 'importArchitectCsvModal',
        'title'       => 'Import Architect & Engineering',
        'route'       => '/con-architect-engineering-import',
        'columns'     => ['Project_Name (required)', 'Consultant_Name', 'Design_Stage', 'Submission_Date (YYYY-MM-DD)', 'Remarks'],
        'btn_label'   => 'Import CSV',
    ],
    'design_types' => [
        'file'        => 'app/views/phase2/design_types/design-types.php',
        'modal_id'    => 'importDesignTypeCsvModal',
        'title'       => 'Import Design Types',
        'route'       => '/con-design-type-import',
        'columns'     => ['Name (required)', 'Description'],
        'btn_label'   => 'Import CSV',
    ],
    'project_drawings' => [
        'file'        => 'app/views/phase2/project_drawings/project-drawings.php',
        'modal_id'    => 'importProjectDrawingCsvModal',
        'title'       => 'Import Project Drawings',
        'route'       => '/con-project-drawing-import',
        'columns'     => ['Project_Name (required)', 'Design_Type_Name (required)', 'Version', 'Submission_Date (YYYY-MM-DD)', 'Remarks'],
        'btn_label'   => 'Import CSV',
    ],
    'structure_design' => [
        'file'        => 'app/views/phase2/structure_design/structure-design.php',
        'modal_id'    => 'importStructureDesignCsvModal',
        'title'       => 'Import Structure Design',
        'route'       => '/con-structure-design-import',
        'columns'     => ['Project_Name (required)', 'Design_Stage', 'Consultant_Name', 'Submission_Date (YYYY-MM-DD)', 'Remarks'],
        'btn_label'   => 'Import CSV',
    ],
    'soil_tests' => [
        'file'        => 'app/views/phase2/soil_tests/soil-tests.php',
        'modal_id'    => 'importSoilTestCsvModal',
        'title'       => 'Import Soil Tests',
        'route'       => '/con-soil-test-import',
        'columns'     => ['Project_Name (required)', 'Testing_Agency', 'Report_Date (YYYY-MM-DD)', 'Bearing_Capacity', 'Report_Link'],
        'btn_label'   => 'Import CSV',
    ],
    'work_types' => [
        'file'        => 'app/views/phase2/work_types/work-type-lists.php',
        'modal_id'    => 'importWorkTypeCsvModal',
        'title'       => 'Import Work Types',
        'route'       => '/con-work-type-import',
        'columns'     => ['Name (required)', 'Description'],
        'btn_label'   => 'Import CSV',
    ],
];

foreach ($modules as $key => $mod) {
    $filePath = __DIR__ . '/' . $mod['file'];
    if (!file_exists($filePath)) {
        echo "NOT FOUND: {$mod['file']}\n";
        continue;
    }

    $content = file_get_contents($filePath);

    // Skip if already injected
    if (strpos($content, $mod['modal_id']) !== false) {
        echo "ALREADY DONE: {$mod['file']}\n";
        continue;
    }

    $colList = implode(', ', $mod['columns']);
    $route   = $mod['route'];
    $modalId = $mod['modal_id'];
    $title   = $mod['title'];

    // ─── Build the import button snippet ───
    $importBtn = <<<HTML

    <button type="button" class="btn btn-outline-success btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#{$modalId}">
        <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV
    </button>
HTML;

    // ─── Build the modal snippet ───
    $modal = <<<PHP


<!-- Import CSV Modal: {$title} -->
<div class="modal fade" id="{$modalId}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{$title}</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= \$GLOBALS['base_url'] ?>{$route}" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>
                    <div class="mb-3 p-2 bg-light rounded" style="font-size:12px;">
                        <strong>Required CSV Columns:</strong><br>
                        <code>{$colList}</code>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm ms-2">
                            <i class="bi bi-upload me-1"></i> Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
PHP;

    // ─── Inject button: find the "Add New" button line and append after it ───
    // Look for common "Add New" patterns in the pagetitle div
    $patterns = [
        '/(<a[^>]+href[^>]+\/create[^>]*>[^<]*<\/a>)\s*(\n\s*<\/div>)/m',  // <a ...create...>...</a> \n </div>
        '/(<a[^>]+href[^>]+\/create[^>]*>[^<]*<\/a>)/m',
    ];

    $injected = false;
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            $content = preg_replace(
                $pattern,
                '$1' . "\n" . $importBtn,
                $content,
                1
            );
            $injected = true;
            break;
        }
    }

    if (!$injected) {
        // Fallback: inject button just before first </div> inside pagetitle
        $content = preg_replace(
            '/(class="[^"]*pagetitle[^"]*"[^>]*>.*?)((<\/div>)\s*\n)/ms',
            '$1' . $importBtn . "\n" . '$2',
            $content,
            1
        );
        $injected = true;
    }

    // ─── Inject modal: before closing </div> at end of file or before <script> ───
    if (strpos($content, '<script>') !== false) {
        $content = str_replace('<script>', $modal . "\n\n<script>", $content, );
    } else {
        $content .= $modal;
    }

    file_put_contents($filePath, $content);
    echo "UPDATED: {$mod['file']}\n";
}

echo "\nDone!\n";
