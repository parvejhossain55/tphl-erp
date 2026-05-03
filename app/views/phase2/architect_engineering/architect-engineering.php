<?php
$addPage = true;
$show_breadcrumb = false;
?>
<div class="mb-4 pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="">
        <h4><?= htmlspecialchars($page_title) ?></h4>
       
    
    
</div>
    <div class="d-flex gap-2">
          <a href="<?= $GLOBALS['base_url'] ?>/con-architect-engineering/create" class="btn btn-primary">
                    <i class="fa-light fa-plus me-1"></i> Add New Architecture
                </a>
        <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#importArchitectCsvModal">
            <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV
        </button>
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
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Architect & Engineering</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/con-architect-engineering-import" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>
                    <div style="font-size:12px;">
                        <strong>Required CSV Columns:</strong><br>
                        <code>Project_Name (required), Architect_Email (optional - user email), Design_Link, Notes, Status (draft/partially/fully)</code>
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
