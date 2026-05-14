<?php
$addPage = true;
$show_breadcrumb = false;
$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
$canModify = ($roleId === 18);
?>
<div class="mb-4 pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="">
        <h4><?= htmlspecialchars($page_title) ?></h4>
    </div>
    <div class="row justify-content-end mb-3 mt-3">
        <div class="col-12">
            <div class="card__wrapper border-0 p-0 shadow-none bg-transparent">
                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                    <?php if ($canModify): ?>
                    <a href="<?= $GLOBALS['base_url'] ?>/con-architect-engineering/create" class="btn btn-primary text-nowrap">
                        <i class="fa-light fa-plus me-1"></i> Add New Architecture
                    </a>
                    <button type="button" class="btn btn-outline-success text-nowrap" data-bs-toggle="modal" data-bs-target="#importArchitectCsvModal">
                        <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- <h4 class="mb-2">Architect & Engineering</h4> -->

<div class="row">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 hover multiple_tables">
                    <thead>
                        <tr class="table__title">
                            <th>#</th>
                            <th>Project</th>
                            <th>Designer</th>
                            <th>Design Link</th>
                            <th>Notes</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($records)): ?>
                            <?php foreach ($records as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['project_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['user_name'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php if (!empty($row['full_architecture_design_link'])): ?>
                                            <a href="<?= htmlspecialchars($row['full_architecture_design_link']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fa-solid fa-file-lines me-1"></i>View
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['notes'] ?? '—') ?></td>
                                    <td>
                                        <?php 
                                            $status = $row['status'] ?? 'draft';
                                            $badgeClass = 'bg-secondary';
                                            if ($status == 'partially') $badgeClass = 'bg-warning text-dark';
                                            elseif ($status == 'fully') $badgeClass = 'bg-success';
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= ucfirst(htmlspecialchars($status)) ?></span>
                                    </td>
                                    <td class="table__icon-box">
                                        <?php if ($canModify): ?>
                                        <div class="d-flex align-items-center justify-content-center gap-10">
                                            <a href="<?= $GLOBALS['base_url'] ?>/con-architect-engineering/edit?id=<?= $row['id'] ?>" class="table__icon edit">
                                                <i class="fa-sharp fa-light fa-pen"></i>
                                            </a>
                                            <button class="table__icon delete removeBtnCust"
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['project_name'] ?? '') ?>"
                                                data-action="delete-architect-engineering">
                                                <i class="fa-regular fa-trash"></i>
                                            </button>
                                        </div>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                       
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Import CSV Modal: Import Architect & Engineering -->
<div class="modal fade" id="importArchitectCsvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-arrow-up me-2 text-success"></i>Import Architect &amp; Engineering</h5>
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
                                    <td>Exact project name from system</td>
                                    <td><em>The Prime Heights</em></td>
                                </tr>
                                <tr>
                                    <td><code>Architect_Email</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>Registered user email. Leave blank to use uploader.</td>
                                    <td><em>architect@company.com</em></td>
                                </tr>
                                <tr>
                                    <td><code>Design_Link</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>Any URL (Google Drive, Issuu, etc.)</td>
                                    <td><em>https://drive.google.com/...</em></td>
                                </tr>
                                <tr>
                                    <td><code>Notes</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td>Free text</td>
                                    <td><em>Initial draft under review</em></td>
                                </tr>
                                <tr>
                                    <td><code>Status</code></td>
                                    <td><span class="badge bg-secondary">Optional</span></td>
                                    <td><code>draft</code> / <code>partially</code> / <code>fully</code></td>
                                    <td><em>fully</em></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2 d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="downloadAeSampleCsv()">
                            <i class="bi bi-download me-1"></i> Download Sample CSV
                        </button>
                        <span style="font-size:11px;color:#888">ডাউনলোড করে সঠিক format অনুযায়ী ফিলআপ করুন</span>
                    </div>
                </div>

                <hr class="my-3">

                <!-- Upload Form -->
                <form action="<?= $GLOBALS['base_url'] ?>/con-architect-engineering-import" method="post" enctype="multipart/form-data">
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
function downloadAeSampleCsv() {
    const headers = ['Project_Name', 'Architect_Email', 'Design_Link', 'Notes', 'Status'];
    const sample  = ['The Prime Heights', 'architect@company.com', 'https://drive.google.com/your-link', 'Initial draft under review', 'fully'];
    const csvContent = [headers.join(','), sample.map(v => '"' + v + '"').join(',')].join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'architect_engineering_template.csv';
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<script>
    const base_url = "<?= $GLOBALS['base_url'] ?>"
</script>
