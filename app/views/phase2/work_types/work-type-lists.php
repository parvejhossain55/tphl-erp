<?php
$page_title = "Work Types";
$page = 'Work Types';
$show_breadcrumb = true;
?>

<div class="breadcrumb__btn my-3 d-flex gap-2 align-items-center">
    <a href="<?= $GLOBALS['base_url'] ?>/con-work-type-create" class="btn btn-primary">
        <i class="fa-light fa-plus me-1"></i> Add Work Type
    </a>

    <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#importWorkTypeCsvModal">
        <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV
    </button>
</div>

<div class="row mt-4">
    <div class="col-xxl-12">
        <div class="card__wrapper shadow-sm">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 hover">
                    <thead>
                        <tr class="table__title">
                            <th>#</th>
                            <th>Work Type Name</th>
                            <th>Design Type</th>
                            <th>Description</th>
                            <th>Created By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <?php if (!empty($records)): ?>
                            <?php foreach ($records as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['work_type_name']) ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= htmlspecialchars($row['design_type_name'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['description']) ?></td>
                                    <td><?= htmlspecialchars($row['created_by_name'] ?? 'N/A') ?></td>
                                    <td class="table__icon-box">
                                        <div class="d-flex align-items-center justify-content-start gap-10">
                                            <a href="<?= $GLOBALS['base_url'] ?>/con-work-type-edit/<?= $row['id'] ?>" class="table__icon edit">
                                                <i class="fa-sharp fa-light fa-pen"></i>
                                            </a>
                                            <form action="<?= $GLOBALS['base_url'] ?>/con-work-type-delete" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this work type?');">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="submit" class="table__icon delete removeBtnCust" style="border:none;background:none">
                                                    <i class="fa-regular fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No work types found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<!-- Import CSV Modal: Import Work Types -->
<div class="modal fade" id="importWorkTypeCsvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Work Types</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/con-work-type-import" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>
                    <div style="font-size:12px;">
                        <strong>Required CSV Columns:</strong><br>
                        <code>Name (required), Description</code>
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