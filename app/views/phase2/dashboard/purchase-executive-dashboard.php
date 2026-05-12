<?php
$userName = htmlspecialchars($_SESSION['user']['full_name'] ?? 'Executive');
?>

<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4><i class="bi bi-speedometer2 me-2 text-primary"></i>My Purchase Dashboard</h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Purchase Executive</li>
            </ol>
        </nav>
    </div>
    <div class="text-end">
        <span class="badge bg-primary fs-6 px-3 py-2"><i class="bi bi-person-badge me-1"></i> <?= $userName ?></span>
    </div>
</div>

<!-- KPI Cards -->
<section class="section">
    <div class="row  mb-4">
        <!-- Total Assigned -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #4361ee !important;">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="flex-shrink-0 rounded-3 d-flex align-items-center justify-content-center"
                         style="width:56px;height:56px;background:rgba(67,97,238,0.12);">
                        <i class="bi bi-clipboard-check fs-4 text-primary"></i>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold text-primary"><?= $totalAssigned ?></div>
                        <div class="text-muted small">Total Assigned to Me</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending CST -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #f77f00 !important;">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="flex-shrink-0 rounded-3 d-flex align-items-center justify-content-center"
                         style="width:56px;height:56px;background:rgba(247,127,0,0.12);">
                        <i class="bi bi-hourglass-split fs-4 text-warning"></i>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold text-warning"><?= $pendingCst ?></div>
                        <div class="text-muted small">Pending CST</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Completed CST -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #2dc653 !important;">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="flex-shrink-0 rounded-3 d-flex align-items-center justify-content-center"
                         style="width:56px;height:56px;background:rgba(45,198,83,0.12);">
                        <i class="bi bi-check2-circle fs-4 text-success"></i>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold text-success"><?= $completedCst ?></div>
                        <div class="text-muted small">CST Completed</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Quotations -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #7209b7 !important;">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="flex-shrink-0 rounded-3 d-flex align-items-center justify-content-center"
                         style="width:56px;height:56px;background:rgba(114,9,183,0.12);">
                        <i class="bi bi-receipt fs-4" style="color:#7209b7;"></i>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold" style="color:#7209b7;"><?= $totalQuotations ?></div>
                        <div class="text-muted small">Quotations Submitted</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row ">
        <!-- Assigned Requests Table -->
        <div class="col-xl-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-list-task me-2 text-primary"></i>My Assigned Purchase Requests</h6>
                    <a href="<?= $base_url ?>/con-purchase-requests" class="btn btn-sm btn-outline-primary">
                        View All <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($recentAssigned)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Project</th>
                                    <th class="text-center">Items</th>
                                    <th>PR Status</th>
                                    <th>CST</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentAssigned as $pr): ?>
                                <tr>
                                    <td><span class="fw-bold text-muted small">PR-<?= $pr['id'] ?></span></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($pr['project_name'] ?? '—') ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border"><?= $pr['item_count'] ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $badge = match($pr['status']) {
                                            'Approved'  => 'bg-success',
                                            'PO Issued' => 'bg-primary',
                                            'Rejected'  => 'bg-danger',
                                            'Submitted' => 'bg-info',
                                            default     => 'bg-warning text-dark',
                                        };
                                        ?>
                                        <span class="badge <?= $badge ?>"><?= $pr['status'] ?></span>
                                    </td>
                                    <td>
                                        <?php if ($pr['has_cst']): ?>
                                            <span class="badge bg-success"><i class="bi bi-check2"></i> Done</span>
                                        <?php elseif ($pr['quote_count'] > 0): ?>
                                            <span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> <?= $pr['quote_count'] ?> Quote(s)</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">Not Started</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= $base_url ?>/con-purchase-request/<?= $pr['id'] ?>"
                                           class="btn btn-sm btn-outline-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($pr['status'] === 'Approved' && !$pr['has_cst']): ?>
                                        <a href="<?= $base_url ?>/con-quotation-create/<?= $pr['id'] ?>"
                                           class="btn btn-sm btn-primary ms-1" title="Start CST">
                                            <i class="bi bi-receipt"></i> CST
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        No purchase requests assigned to you yet.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- My Projects -->
        <div class="col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-buildings me-2 text-primary"></i>My Projects</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($myProjects)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($myProjects as $proj): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                            <div>
                                <div class="fw-semibold"><?= htmlspecialchars($proj['name']) ?></div>
                                <div class="text-muted small"><?= $proj['request_count'] ?> Purchase Request(s)</div>
                            </div>
                            <span class="badge bg-primary rounded-pill"><?= $proj['request_count'] ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-buildings fs-1 d-block mb-2"></i>
                        No projects assigned yet.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
