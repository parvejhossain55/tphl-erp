<?php
$addPage = true;
$show_breadcrumb = false;
$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
$canModify = ($roleId === 16);

// ── CSV import skipped-row report ────────────────────────────────────
$csvSkipped = $_SESSION['csv_legal_sheet_skipped'] ?? [];
unset($_SESSION['csv_legal_sheet_skipped']); // clear after reading
?>

<div class="content-wrapper">
    <div class="content">
        <div class="container-fluid">

            <?php if (!empty($csvSkipped)): ?>
            <!-- CSV Import Error Report -->
            <div class="card shadow-sm border-0 mb-4" style="border-left: 4px solid #f59e0b !important; border-radius:12px; overflow:hidden">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="fa-solid fa-triangle-exclamation text-warning fs-5"></i>
                        <h6 class="mb-0 fw-semibold text-warning">
                            <?= count($csvSkipped) ?> row(s) were <u>not imported</u> from the CSV
                        </h6>
                    </div>
                    <div class="table-responsive" style="max-height:320px; overflow-y:auto;">
                        <table class="table table-sm table-bordered mb-0" style="font-size:13px;">
                            <thead class="table-warning">
                                <tr>
                                    <th style="width:70px">CSV Row</th>
                                    <th>Project</th>
                                    <th>Title</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($csvSkipped as $sk): ?>
                                <tr>
                                    <td class="text-center fw-bold"><?= (int)$sk['row'] ?></td>
                                    <td><?= htmlspecialchars($sk['project'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($sk['title'] ?? '—') ?></td>
                                    <td class="text-danger"><?= htmlspecialchars($sk['reason'] ?? '') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h4 class="mb-0 text-dark font-weight-bold">
                    <i class="fal fa-file-contract me-2" style="color:var(--tphl-blue)"></i>
                    Project Approval Sheets
                </h4>
                <div class="d-flex gap-2 flex-wrap">
                    <?php if ($canModify): ?>
                    <button type="button" class="btn btn-outline-success" style="border-radius:8px"
                        data-bs-toggle="modal" data-bs-target="#importLegalSheetCsvModal">
                        <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV
                    </button>
                    <a href="<?= $GLOBALS['base_url'] ?>/create-legal-sheet" class="btn btn-primary" style="border-radius:8px">
                        <i class="fal fa-plus-circle me-1"></i> Add Legal Sheet
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Table Card -->
            <div class="card shadow-sm border-0" style="border-radius:12px; overflow:hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 custom-table multiple-table">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-secondary text-uppercase font-weight-bold" style="font-size:12px; padding:15px">ID</th>
                                    <th class="text-secondary text-uppercase font-weight-bold" style="font-size:12px; padding:15px">Project</th>
                                    <th class="text-secondary text-uppercase font-weight-bold" style="font-size:12px; padding:15px">Sheet Type</th>
                                    <th class="text-secondary text-uppercase font-weight-bold" style="font-size:12px; padding:15px">Title & Version</th>
                                    <th class="text-secondary text-uppercase font-weight-bold" style="font-size:12px; padding:15px">Submitted By</th>
                                    <th class="text-secondary text-uppercase font-weight-bold text-center" style="font-size:12px; padding:15px">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($sheets)): ?>
                                    <?php foreach ($sheets as $s): ?>
                                        <tr>
                                            <td class="text-muted" style="padding:15px">#<?= $s['id'] ?></td>
                                            <td class="font-weight-bold text-dark" style="padding:15px">
                                                <i class="fal fa-building text-secondary me-1"></i> <?= htmlspecialchars($s['project_name']) ?>
                                            </td>
                                            <td style="padding:15px">
                                                <span class="badge" style="background:rgba(var(--clr-theme-primary-rgb),0.1); color:var(--clr-theme-primary); padding:5px 10px; border-radius:6px; font-weight:600">
                                                    <?= htmlspecialchars($s['sheet_type']) ?>
                                                </span>
                                            </td>
                                            <td style="padding:15px">
                                                <div class="font-weight-bold text-dark"><?= htmlspecialchars($s['title']) ?></div>
                                                <div class=" d-none" style="font-size:12px; color:#6c757d">
                                                    Version: <span class="badge badge-primary border text-muted"><?= htmlspecialchars($s['version']) ?></span>
                                                </div>
                                            </td>
                                            <td style="padding:15px">
                                                <i class="fal fa-user-circle text-muted me-1"></i> <?= htmlspecialchars($s['submitted_by_name']) ?>
                                                <br><small class="text-muted"><?= date('d M Y', strtotime($s['created_at'])) ?></small>
                                            </td>
                                            <td class="text-center" style="padding:15px">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <a href="<?= htmlspecialchars($s['link']) ?>" target="_blank" 
                                                       class="btn btn-sm btn-info text-white" title="View Link" style="border-radius:6px; padding:4px 10px">
                                                        <i class="fal fa-external-link"></i>
                                                    </a>
                                                    <?php if ($canModify): ?>
                                                    <a href="<?= $GLOBALS['base_url'] ?>/edit-legal-sheet/<?= $s['id'] ?>" 
                                                       class="btn btn-sm btn-light border" title="Edit" style="border-radius:6px; padding:4px 10px">
                                                        <i class="fal fa-edit text-primary"></i>
                                                    </a>
                                                    <a href="<?= $GLOBALS['base_url'] ?>/delete-legal-sheet/<?= $s['id'] ?>" 
                                                       class="btn btn-sm btn-light border" title="Delete" 
                                                       onclick="return confirm('Are you sure you want to delete this sheet?')"
                                                       style="border-radius:6px; padding:4px 10px">
                                                        <i class="fal fa-trash-alt text-danger"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <div style="font-size:40px; margin-bottom:10px; opacity:0.3"><i class="fal fa-folder-open"></i></div>
                                            No legal sheets found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Import CSV Modal: Legal Sheets -->
<div class="modal fade" id="importLegalSheetCsvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-arrow-up me-2 text-success"></i>Import Legal Sheets from CSV</h5>
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
                                    <td>Exact Project Name from system</td>
                                    <td><em>The Prime Heights</em></td>
                                </tr>
                                <tr>
                                    <td><code>Sheet_Type</code></td>
                                    <td><span class="badge bg-danger">Required</span></td>
                                    <td>e.g. Land Document, Approval, NOC</td>
                                    <td><em>Land Document</em></td>
                                </tr>
                                <tr>
                                    <td><code>Title</code></td>
                                    <td><span class="badge bg-danger">Required</span></td>
                                    <td>Sheet title / description</td>
                                    <td><em>Plot Purchase Deed</em></td>
                                </tr>
                                <tr>
                                    <td><code>Link</code></td>
                                    <td><span class="badge bg-danger">Required</span></td>
                                    <td>Valid URL / Google Drive link</td>
                                    <td><em>https://drive.google.com/...</em></td>
                                </tr>
                                <tr>
                                    <td><code>Remark</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>Any additional notes</td>
                                    <td><em>Submitted to RAJUK</em></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2 d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="downloadLegalSheetSampleCsv()">
                            <i class="bi bi-download me-1"></i> Download Sample CSV
                        </button>
                        <span style="font-size:11px;color:#888">ডাউনলোড করে সঠিক format অনুযায়ী ফিলআপ করুন</span>
                    </div>
                </div>

                <hr class="my-3">

                <!-- Upload Form -->
                <form action="<?= $GLOBALS['base_url'] ?>/legal-sheet-import-csv" method="post" enctype="multipart/form-data">
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
function downloadLegalSheetSampleCsv() {
    const headers = [
        'Project_Name', 'Sheet_Type', 'Title', 'Link', 'Remark'
    ];
    const sampleRows = [
        ['The Prime Heights', 'Land Document', 'Plot Purchase Deed', 'https://drive.google.com/example1', 'Original copy scanned'],
        ['Green Valley Tower', 'Approval', 'RAJUK Approval Letter', 'https://drive.google.com/example2', 'Revised version']
    ];

    const csvContent = [
        headers.join(','),
        ...sampleRows.map(row => row.map(v => '"' + v + '"').join(','))
    ].join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'legal_sheet_template.csv';
    a.click();
    URL.revokeObjectURL(url);
}
</script>
