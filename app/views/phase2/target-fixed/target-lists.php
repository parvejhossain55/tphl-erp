<?php
$base_url = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Site Targets';
?>
<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-semibold fs-18 mb-0">Site Targets</h4>
            <div class="d-flex">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item active">Site Targets</li>
                </ol>
            </div>
        </div>
    </div>
    <?php if (!in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 20, 22])): ?>
        <div class="row justify-content-end mb-3 mt-3">
            <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-12">
                <div class="card__wrapper border-0 p-0 shadow-none bg-transparent">
                    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                        <a href="<?= $base_url ?>/con-target-create" class="btn btn-primary text-nowrap">
                            <i class="bi bi-plus-circle me-1"></i> New Target
                        </a>
                        <button type="button" class="btn btn-outline-success text-nowrap" data-bs-toggle="modal"
                            data-bs-target="#importTargetCsvModal">
                            <i class="bi bi-file-earmark-arrow-up me-1"></i> Import CSV
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Card -->
    <div class="card custom-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0"><i class="bi bi-bullseye me-2 text-primary"></i> All Site Targets</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="targetTable" class="table table-bordered table-hover text-nowrap">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Project</th>
                            <th>Target Title</th>
                            <th>Design Type</th>
                            <th>Year/Month</th>
                            <th>Week</th>
                            <th>Target %</th>
                            <th>Gain %</th>
                            <th>Approval</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($targets)): ?>
                            <?php foreach ($targets as $i => $t): ?>
                                <?php
                                $pmApproved = !empty($t['approved_by_manager']);
                                ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($t['project_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($t['target_title']) ?></td>
                                    <td><?= htmlspecialchars($t['design_type_name'] ?? '-') ?></td>
                                    <td><?= $t['year'] ?>/<?= str_pad($t['month'], 2, '0', STR_PAD_LEFT) ?></td>
                                    <td><?= $t['week_number'] ? 'Week ' . $t['week_number'] : '-' ?></td>
                                    <td>
                                        <span class="badge bg-info"><?= number_format($t['target_percentage'], 1) ?>%</span>
                                    </td>
                                    <td>
                                        <?php $gain = (float) $t['gain_percentage']; ?>
                                        <span
                                            class="badge bg-<?= $gain >= (float) $t['target_percentage'] ? 'success' : ($gain > 0 ? 'warning' : 'secondary') ?>">
                                            <?= number_format($gain, 1) ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($pmApproved): ?>
                                            <span class="badge bg-success mb-1">✓ Approved</span>
                                            <?php if (!empty($t['manager_approver_name'])): ?>
                                                <div style="font-size:10px;color:#059669;font-weight:700;line-height:1.4">
                                                    <?= htmlspecialchars($t['manager_approver_name']) ?>
                                                </div>
                                                <?php if (!empty($t['manager_approver_role'])): ?>
                                                    <div style="font-size:9px;color:#6b7280;font-style:italic">
                                                        <?= htmlspecialchars($t['manager_approver_role']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 20, 22])): ?>
                                        <a href="<?= $base_url ?>/con-boq-create?target_id=<?= $t['id'] ?>"
                                            class="btn btn-sm btn-outline-info" title="Create BOM">
                                            <i class="bi bi-box-seam"></i> BOM
                                        </a>
                                        <?php endif; ?>
                                        <a href="<?= $base_url ?>/con-target/<?= $t['id'] ?>"
                                            class="btn btn-sm btn-outline-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (!in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 20, 22])): ?>
                                        <?php if ($pmApproved): ?>
                                            <a href="<?= $base_url ?>/con-target-edit/<?= $t['id'] ?>"
                                                class="btn btn-sm btn-outline-secondary disabled" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= $base_url ?>/con-target-edit/<?= $t['id'] ?>"
                                                class="btn btn-sm btn-outline-secondary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">No targets found. <a
                                        href="<?= $base_url ?>/con-target-create">Create one</a></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>



<!-- Import CSV Modal -->
<div class="modal fade" id="importTargetCsvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Targets</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal"><i
                        class="fa-solid fa-xmark-large"></i></button>
            </div>
            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/con-target-import" method="post"
                    enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>
                    <div class="mb-3" style="font-size:12px;">
                        <strong>Required CSV Columns:</strong><br>
                        <code>Project_Name (required), Target_Title, Year (required), Month (1-12 required), Week_Number (1-4), Target_Percentage (0-100 required)</code>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm ms-2"><i class="bi bi-upload me-1"></i>
                            Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function () {
        $('#targetTable').DataTable({ responsive: true, pageLength: 25 });
    });
</script>