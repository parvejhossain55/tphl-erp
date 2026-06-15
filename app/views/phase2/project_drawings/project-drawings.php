<?php
$addPage = true;
$show_breadcrumb = false;
$canModify       = $canModify       ?? false;
$filterProjectId = $filterProjectId ?? 0;
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

<div class="row justify-content-end mb-3">
    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-12">
        <div class="card__wrapper border-0 p-0 shadow-none bg-transparent">
            <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                <?php if ($canModify): ?>
                <a href="<?= $GLOBALS['base_url'] ?>/con-project-drawings/create" class="btn btn-primary text-nowrap">
                    <i class="fa-light fa-plus me-1"></i> Add Drawing
                </a>
            
                <button type="button" class="btn btn-outline-success text-nowrap" data-bs-toggle="modal" data-bs-target="#importProjectDrawingCsvModal">
                    <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 hover ">
                    <thead>
                        <tr class="table__title">
                            <th>#</th>
                            <th>Project</th>
                            <th>Drawing Type</th>
                            <th>Designer</th>
                            <th>Source / Received From</th>
                            <th>Status</th>
                            <th>Design Link</th>
                            <?php if ($canModify): ?><th>Actions</th><?php endif; ?>
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
                                    <td class="table__icon-box">
                                        <div class="d-flex align-items-center justify-content-center gap-10">
                                            <a href="<?= $GLOBALS['base_url'] ?>/con-project-drawings/edit?id=<?= $row['id'] ?>" class="table__icon edit">
                                                <i class="fa-sharp fa-light fa-pen"></i>
                                            </a>
                                            <button class="table__icon delete removeBtnCust"
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['project_name'] ?? '') ?>"
                                                data-action="delete-project-drawing">
                                                <i class="fa-regular fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= $canModify ? 8 : 7 ?>" class="text-center text-muted py-4">No drawings found.</td>
                            </tr>
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
                                    <td>Exact Design Type</td>
                                    <td><em>Architectural</em></td>
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
    const sampleInternal = [
        'The Prime Heights', 'Architectural', '1', 'designer@example.com', 
        '', '', '', 'https://example.com/design1', 
        'Draft'
    ];
    const sampleExternal = [
        'The Prime Heights', 'Structural', '0', '', 
        'John Doe', 'Lead Engineer', 'ABC Engineering', 'https://example.com/design2', 
        'Submitted'
    ];
    
    const csvContent = [
        headers.join(','),
        sampleInternal.map(v => '"' + v + '"').join(','),
        sampleExternal.map(v => '"' + v + '"').join(',')
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
    const base_url = "<?= $GLOBALS['base_url'] ?>"
</script>
