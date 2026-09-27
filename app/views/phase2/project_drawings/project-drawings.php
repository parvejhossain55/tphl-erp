<?php
$addPage = true;
$show_breadcrumb = false;
$canModify          = $canModify          ?? false;
$filterProjectId    = $filterProjectId    ?? 0;
$filterDesignTypeId = $filterDesignTypeId ?? 0;
$filterStatus       = $filterStatus       ?? '';
$searchTerm         = $searchTerm         ?? '';
$projects           = $projects           ?? [];
$design_types       = $design_types       ?? [];
$records            = $records            ?? [];
?>

<h4 class="mb-2">
  Project Drawings
  <?php if (!$canModify): ?>
    <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;background:rgba(59,130,246,.1);color:#3b82f6;vertical-align:middle;margin-left:8px">
      <i class="fal fa-eye me-1"></i>View Only
    </span>
  <?php endif; ?>
  <?php if ($filterProjectId > 0 && !empty($records[0]['project_name'])): ?>
    <small class="text-muted" style="font-size:13px;font-weight:400;margin-left:10px">&mdash; <?= htmlspecialchars($records[0]['project_name']) ?></small>
  <?php endif; ?>
</h4>

<!-- Top Filter & Action Card (like flats-list) -->
<div class="card__wrapper mb-3">
    <form action="" method="get">

        <!-- Row 1: Filter inputs -->
        <div class="row g-2 align-items-center mb-2">

            <!-- Project Filter -->
            <div class="col-xl-3 col-md-4 col-sm-6">
                <select class="form-control form-control-sm select2" name="project_id">
                    <option value="">All Projects</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?= $project['id'] ?>"
                            <?= $filterProjectId == $project['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($project['name']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <!-- Drawing Type Filter -->
            <div class="col-xl-3 col-md-4 col-sm-6">
                <select class="form-control form-control-sm select2" name="design_type_id">
                    <option value="">All Drawing Types</option>
                    <?php foreach ($design_types as $dt): ?>
                        <option value="<?= $dt['id'] ?>"
                            <?= $filterDesignTypeId == $dt['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dt['name']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="col-xl-2 col-md-3 col-sm-6">
                <select class="form-control form-control-sm" name="status">
                    <option value="">All Status</option>
                    <option value="Draft"     <?= $filterStatus === 'Draft'     ? 'selected' : '' ?>>Draft</option>
                    <option value="Submitted" <?= $filterStatus === 'Submitted' ? 'selected' : '' ?>>Submitted</option>
                    <option value="Approved"  <?= $filterStatus === 'Approved'  ? 'selected' : '' ?>>Approved</option>
                    <option value="Rejected"  <?= $filterStatus === 'Rejected'  ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>

            <!-- Search -->
            <div class="col-xl-2 col-md-3 col-sm-6">
                <input type="text" class="form-control form-control-sm" name="q" id="drawingSearchInput"
                    placeholder="Search Drawings..."
                    value="<?= htmlspecialchars($searchTerm ?? '') ?>">
            </div>

            <!-- Filter + Reset -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-secondary btn-sm flex-fill">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                    <a href="<?= $GLOBALS['base_url'] ?>/con-project-drawings" class="btn btn-success btn-sm flex-fill">
                        <i class="fa-solid fa-rotate-right me-1"></i> Reset
                    </a>
                </div>
            </div>

        </div>

        <!-- Row 2: Action Buttons -->
        <?php if ($canModify): ?>
        <div class="row mt-3">
            <div class="col-12">
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-primary btn-sm" href="<?= $GLOBALS['base_url'] ?>/con-project-drawings/create">
                        <i class="fa-solid fa-plus me-1"></i> Add Drawing
                    </a>
                    <button type="button" class="btn btn-outline-success btn-sm"
                        data-bs-toggle="modal" data-bs-target="#importProjectDrawingCsvModal">
                        <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </form>
</div>

<!-- Table Card (like flats-list) -->
<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 hover" id="projectDrawingsTable">
                    <thead>
                        <tr class="table__title">
                            <th style="width:50px">#</th>
                            <th>Project</th>
                            <th>Drawing Type</th>
                            <th>Designer</th>
                            <th>Source / Received From</th>
                            <th>Status</th>
                            <th>Design Link</th>
                            <?php if ($canModify): ?><th style="text-align:center;">Actions</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($records)): ?>
                            <?php foreach ($records as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['project_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['design_type_name'] ?? 'N/A') ?></td>
                                    <td>
                                        <?= $row['is_in_house']
                                            ? htmlspecialchars($row['internal_designer_name'] ?? 'In-House')
                                            : htmlspecialchars($row['designer_name'] ?? 'External') ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['upload_designer_name'])): ?>
                                            <span class="fw-semibold" style="font-size:12.5px;">
                                                <?= htmlspecialchars($row['upload_designer_name']) ?>
                                            </span>
                                            <?php if (!empty($row['upload_designer_details'])): ?>
                                                <br><small class="text-muted" style="font-size:11px;"><?= nl2br(htmlspecialchars($row['upload_designer_details'])) ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size:12px;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $sBadge = match($row['status'] ?? 'Draft') {
                                            'Approved'  => 'success',
                                            'Rejected'  => 'danger',
                                            'Submitted' => 'info',
                                            default     => 'warning'
                                        };
                                        ?>
                                        <span class="bd-badge bg-<?= $sBadge ?>"><?= htmlspecialchars($row['status'] ?? 'Draft') ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['design_link'])): ?>
                                            <a href="<?= htmlspecialchars($row['design_link']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fa-solid fa-file-lines me-1"></i>View
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($canModify): ?>
                                    <td class="table__icon-box text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-10">
                                            <?php if (($row['status'] ?? 'Draft') === 'Draft'): ?>
                                                <a href="<?= $GLOBALS['base_url'] ?>/con-project-drawings/edit?id=<?= $row['id'] ?>" class="table__icon edit" title="Edit Draft">
                                                    <i class="fa-sharp fa-light fa-pen"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="table__icon delete removeBtnCust"
                                                    data-id="<?= $row['id'] ?>"
                                                    data-name="<?= htmlspecialchars($row['project_name'] ?? '') ?>"
                                                    data-action="delete-project-drawing" title="Delete Drawing">
                                                    <i class="fa-regular fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Import CSV Modal: Import Project Drawings -->
<div class="modal fade" id="importProjectDrawingCsvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-arrow-up me-2 text-success"></i>Import Project Drawings</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">

                <!-- Column Guide -->
                <div class="mb-3">
                    <p class="fw-semibold mb-2" style="font-size:13px;">📋 CSV Column Reference</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" style="font-size:11.5px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Column Header</th>
                                    <th>Required?</th>
                                    <th>Allowed Values</th>
                                    <th>Example</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>Project_Name</code></td>
                                    <td><span class="badge bg-danger">Required</span></td>
                                    <td>Exact Project Name</td>
                                    <td><em>The Prime Heights</em></td>
                                </tr>
                                <tr>
                                    <td><code>Design_Type_Name</code></td>
                                    <td><span class="badge bg-danger">Required</span></td>
                                    <td>Exact Design Type (Electrical, Plumbing, Fire Department, etc.)</td>
                                    <td><em>Electrical</em></td>
                                </tr>
                                <tr>
                                    <td><code>Is_In_House</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>1 (Yes) or 0 (No)</td>
                                    <td><em>1</em></td>
                                </tr>
                                <tr>
                                    <td><code>Internal_Designer_Email</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>Valid User Email (if Is_In_House=1)</td>
                                    <td><em>designer@example.com</em></td>
                                </tr>
                                <tr>
                                    <td><code>Designer_Name</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>Name (if Is_In_House=0)</td>
                                    <td><em>John Doe</em></td>
                                </tr>
                                <tr>
                                    <td><code>Designer_Designation</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>Designation (if Is_In_House=0)</td>
                                    <td><em>Lead Architect</em></td>
                                </tr>
                                <tr>
                                    <td><code>Designer_Company</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>Company (if Is_In_House=0)</td>
                                    <td><em>ABC Designs</em></td>
                                </tr>
                                <tr>
                                    <td><code>Design_Link</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>Valid URL</td>
                                    <td><em>https://example.com/design</em></td>
                                </tr>

                                <tr>
                                    <td><code>Status</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>Draft, Submitted, Approved, Rejected</td>
                                    <td><em>Draft</em></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2 d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="downloadProjectDrawingSampleCsv()">
                            <i class="bi bi-download me-1"></i> Download Sample CSV
                        </button>
                        <span style="font-size:11px;color:#888">ডাউনলোড করে সঠিক format অনুযায়ী ফিলআপ করুন</span>
                    </div>
                </div>

                <hr class="my-3">

                <!-- Upload Form -->
                <form action="<?= $GLOBALS['base_url'] ?>/con-project-drawing-import" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        <div class="form-text">শুধুমাত্র <strong>.csv</strong> ফাইল। Excel থেকে "Save As → CSV UTF-8" করে export করুন।</div>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bi bi-upload me-1"></i> Import Now
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<script>
function downloadProjectDrawingSampleCsv() {
    const headers = [
        'Project_Name', 'Design_Type_Name', 'Is_In_House', 'Internal_Designer_Email', 
        'Designer_Name', 'Designer_Designation', 'Designer_Company', 'Design_Link', 
        'Status'
    ];
    const sampleElectrical = [
        'The Prime Heights', 'Electrical', '1', 'electrical@example.com', 
        '', '', '', 'https://example.com/electrical-drawing', 
        'Draft'
    ];
    const samplePlumbing = [
        'The Prime Heights', 'Plumbing', '1', 'plumbing@example.com', 
        '', '', '', 'https://example.com/plumbing-drawing', 
        'Draft'
    ];
    const sampleFireDepart = [
        'The Prime Heights', 'Fire Department', '0', '', 
        'Fire Safety Consultant', 'Lead Inspector', 'Fire Tech Safety Ltd', 'https://example.com/fire-safety-drawing', 
        'Submitted'
    ];
    
    const csvContent = [
        headers.join(','),
        sampleElectrical.map(v => '"' + v + '"').join(','),
        samplePlumbing.map(v => '"' + v + '"').join(','),
        sampleFireDepart.map(v => '"' + v + '"').join(',')
    ].join('\n');
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'project_drawings_template.csv';
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<script>
    var base_url = "<?= $GLOBALS['base_url'] ?>";

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof jQuery !== 'undefined' && typeof jQuery.fn.DataTable !== 'undefined') {
            const urlParams = new URLSearchParams(window.location.search);
            const qParam = urlParams.get('q') || '';

            const dtTable = $('#projectDrawingsTable').DataTable({
                destroy: true,
                responsive: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                order: [], // Preserve default order (id DESC)
                columnDefs: [
                    { orderable: false, searchable: false, targets: 0 },  // # serial column
                    <?php if ($canModify): ?>
                    { orderable: false, searchable: false, targets: -1 }, // Actions column
                    <?php endif; ?>
                ],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search in table...",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    emptyTable: "No drawings found.",
                    paginate: {
                        first: '<i class="fa-solid fa-angles-left"></i>',
                        previous: '<i class="fa-solid fa-angle-left"></i>',
                        next: '<i class="fa-solid fa-angle-right"></i>',
                        last: '<i class="fa-solid fa-angles-right"></i>'
                    }
                },
                drawCallback: function(settings) {
                    // Continuous serial numbers across pagination
                    var api = this.api();
                    var startIndex = api.context[0]._iDisplayStart;
                    api.column(0, {page: 'current'}).nodes().each(function(cell, i) {
                        cell.innerHTML = startIndex + i + 1;
                    });
                }
            });

            // If initial search parameter exists in URL, filter table immediately
            if (qParam) {
                dtTable.search(qParam).draw();
            }

            // Real-time live auto-search when typing in top filter search input
            $('#drawingSearchInput').on('keyup input', function () {
                dtTable.search(this.value).draw();
            });
        }
    });
</script>
