<?php
$base_url = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Target Detail';
$t = $target;
$months = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

$approvalSteps = [
    'site_eng' => ['label' => 'Site Engineer', 'col' => 'approved_by_site_eng', 'icon' => 'bi-person-check'],
    'qc'       => ['label' => 'QC Officer',   'col' => 'approved_by_qc',      'icon' => 'bi-shield-check'],
    'pc'       => ['label' => 'PC',           'col' => 'approved_by_pc',      'icon' => 'bi-star-half'],
    'pd'       => ['label' => 'Project Director', 'col' => 'approved_by_pd',   'icon' => 'bi-briefcase'],
    'manager'  => ['label' => 'Manager',      'col' => 'approved_by_manager', 'icon' => 'bi-person-badge'],
];
$totalApprovals = array_sum(array_map(fn($s) => (int)$t[$s['col']], $approvalSteps));
?>
<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h4 class="page-title fw-semibold fs-18 mb-0"><?= htmlspecialchars($t['target_title']) ?></h4>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-targets">Site Targets</a></li>
                <li class="breadcrumb-item active">Detail</li>
            </ol>
        </div>
        <div class="my-auto d-flex gap-2">
            <a href="<?= $base_url ?>/con-target-edit/<?= $t['id'] ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <a href="<?= $base_url ?>/con-targets" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row ">
        <!-- Left: Target Info -->
        <div class="col-xl-7">
            <div class="card custom-card">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-bullseye me-2 text-primary"></i> Target Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="35%">Project</th>
                            <td><?= htmlspecialchars($t['project_name'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <th>Working Plan</th>
                            <td><?= htmlspecialchars($t['plan_description'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <th>Design Type</th>
                            <td><span class="badge bg-primary"><?= htmlspecialchars($t['design_type_name'] ?? '-') ?></span></td>
                        </tr>
                        <tr>
                            <th>Year / Month</th>
                            <td><?= $t['year'] ?> / <?= $months[(int)$t['month']] ?></td>
                        </tr>
                        <tr>
                            <th>Week</th>
                            <td><?= $t['week_number'] ? 'Week ' . $t['week_number'] : 'Monthly Target' ?></td>
                        </tr>
                        <tr>
                            <th>Assigned To</th>
                            <td><?= htmlspecialchars($t['created_by_name'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <th>Created</th>
                            <td><?= date('d M Y', strtotime($t['created_at'])) ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Progress Card -->
            <div class="card custom-card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-graph-up me-2 text-success"></i> Progress</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-3">
                        <div class="col-6">
                            <div class="fs-1 fw-bold text-info"><?= number_format($t['target_percentage'], 1) ?>%</div>
                            <div class="text-muted small">Target %</div>
                        </div>
                        <div class="col-6">
                            <div class="fs-1 fw-bold text-<?= (float)$t['gain_percentage'] >= (float)$t['target_percentage'] ? 'success' : 'warning' ?>">
                                <?= number_format($t['gain_percentage'], 1) ?>%
                            </div>
                            <div class="text-muted small">Gain %</div>
                        </div>
                    </div>
                    <div class="progress mb-2" style="height:18px;">
                        <div class="progress-bar bg-info progress-bar-striped" role="progressbar"
                            style="width: <?= min($t['target_percentage'], 100) ?>%">
                            Target: <?= number_format($t['target_percentage'], 1) ?>%
                        </div>
                    </div>
                    <div class="progress" style="height:18px;">
                        <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar"
                            style="width: <?= min($t['gain_percentage'], 100) ?>%">
                            Gain: <?= number_format($t['gain_percentage'], 1) ?>%
                        </div>
                    </div>

                    <?php if (!empty($t['photo_link'])): ?>
                        <div class="mt-3">
                            <img src="<?= $base_url ?>/<?= htmlspecialchars($t['photo_link']) ?>" class="img-fluid rounded" style="max-height:220px;" alt="Site Photo">
                        </div>
                    <?php endif; ?>

                    <?php if ($t['field_test_details']): ?>
                        <div class="mt-3 p-3 bg-light rounded">
                            <strong>Field Test:</strong><br><?= nl2br(htmlspecialchars($t['field_test_details'])) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($t['lab_test_result']): ?>
                        <div class="mt-2 p-3 bg-light rounded">
                            <strong>Lab Result:</strong><br><?= nl2br(htmlspecialchars($t['lab_test_result'])) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Update Gain Form -->
                    <hr>
                    <h6 class="fw-semibold">Update Actual Gain</h6>
                    <form action="<?= $base_url ?>/con-target-gain/<?= $t['id'] ?>" method="POST" enctype="multipart/form-data">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label small">Gain %</label>
                                <input type="number" name="gain_percentage" class="form-control form-control-sm" step="0.01" min="0" max="100"
                                    value="<?= $t['gain_percentage'] ?>">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small">Site Photo</label>
                                <input type="file" name="photo" class="form-control form-control-sm" accept="image/*">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Field Test Details</label>
                                <textarea name="field_test_details" rows="2" class="form-control form-control-sm"><?= htmlspecialchars($t['field_test_details'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Lab Test Result</label>
                                <textarea name="lab_test_result" rows="2" class="form-control form-control-sm"><?= htmlspecialchars($t['lab_test_result'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-upload me-1"></i> Update Gain</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right: Approval Steps -->
        <div class="col-xl-5">
            <div class="card custom-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0"><i class="bi bi-shield-check me-2 text-success"></i> Approval Steps</h6>
                    <span class="badge bg-<?= $totalApprovals >= 5 ? 'success' : ($totalApprovals > 0 ? 'warning' : 'secondary') ?>">
                        <?= $totalApprovals ?>/5
                    </span>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($approvalSteps as $key => $step): ?>
                            <?php $approved = (int)$t[$step['col']]; ?>
                            <li class="list-group-item d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi <?= $step['icon'] ?> fs-5 text-<?= $approved ? 'success' : 'muted' ?>"></i>
                                    <span><?= $step['label'] ?></span>
                                </div>
                                <?php if ($approved): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-lg"></i> Approved</span>
                                <?php else: ?>
                                    <form action="<?= $base_url ?>/con-target-approve/<?= $t['id'] ?>" method="POST" class="d-inline">
                                        <input type="hidden" name="step" value="<?= $key ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success"
                                            onclick="return confirm('Confirm approval as <?= $step['label'] ?>?')">
                                            <i class="bi bi-check-circle me-1"></i> Approve
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php if ($totalApprovals >= 5): ?>
                    <div class="card-footer bg-success-subtle text-success text-center fw-semibold">
                        <i class="bi bi-check-circle-fill me-1"></i> Fully Approved — Contractor Bill Can Be Raised
                    </div>
                <?php endif; ?>
            </div>

            <!-- QC Review Info -->
            <?php if ($t['qc_reviewed_by_name']): ?>
                <div class="card custom-card mt-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-check-fill text-teal fs-4"></i>
                            <div>
                                <div class="fw-semibold">QC Reviewed by</div>
                                <div class="text-muted small"><?= htmlspecialchars($t['qc_reviewed_by_name']) ?></div>
                                <div class="text-muted small"><?= $t['qc_reviewed_at'] ? date('d M Y H:i', strtotime($t['qc_reviewed_at'])) : '' ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>