<?php
$addPage = true;
$show_breadcrumb = false;
?>

<h4 class="mb-2">Project Structure Design</h4>

<div class="row">
    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-12">
        <div class="card__wrapper">
            <div class="d-flex flex-column flex-sm-row gap-2">
                <a href="<?= $GLOBALS['base_url'] ?>/structure-design/create" class="btn btn-primary w-100">
                    <i class="fa-light fa-plus me-1"></i> Add Design
                </a>
            
                <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#importStructureDesignCsvModal">
                    <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV
                </button>
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
                            <th>Designer Type</th>
                            <th>Designer Name</th>
                            <th>Designer Phone</th>
                            <th>Design Link</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($records)): ?>
                            <?php foreach ($records as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['project_name'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php if (($row['designer_type'] ?? '') === 'internal'): ?>
                                            <span class="bd-badge bg-primary">Internal</span>
                                        <?php else: ?>
                                            <span class="bd-badge bg-info">External</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['designer_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['designer_phone'] ?: 'N/A') ?></td>
                                    <td>
                                        <?php if (!empty($row['design_link'])): ?>
                                            <a href="<?= htmlspecialchars($row['design_link']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fa-solid fa-file-lines me-1"></i>View
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="table__icon-box">
                                        <div class="d-flex align-items-center justify-content-center gap-10">
                                            <a href="<?= $GLOBALS['base_url'] ?>/structure-design/edit?id=<?= $row['id'] ?>" class="table__icon edit">
                                                <i class="fa-sharp fa-light fa-pen"></i>
                                            </a>
                                            <button class="table__icon delete removeBtnCust"
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['project_name'] ?? '') ?>"
                                                data-action="delete-structure-design">
                                                <i class="fa-regular fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No structure designs found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Import CSV Modal: Import Structure Design -->
<div class="modal fade" id="importStructureDesignCsvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Structure Design</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/con-structure-design-import" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>
                    <div style="font-size:12px;">
                        <strong>Required CSV Columns:</strong><br>
                        <code>Project_Name (required), Design_Stage, Consultant_Name, Submission_Date (YYYY-MM-DD), Remarks</code>
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

<script>
    const base_url = "<?= $GLOBALS['base_url'] ?>"
</script>
