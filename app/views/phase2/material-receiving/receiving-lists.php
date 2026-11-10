<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Material Receiving</li>
            </ol>
        </nav>
    </div>
</div>

<section class="section">
    <div class="card__wrapper shadow-sm">
        <div class="card__body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped custom-table mb-0 multiple-table">
                    <thead class="table-light">
                        <tr>
                            <th>GRN #</th>
                            <th>Project</th>
                            <th>Warehouse</th>
                            <th class="text-center">Items</th>
                            <th>QC Status</th>
                            <th>Received By</th>
                            <th>Date</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($receivings)): ?>
                            <?php foreach ($receivings as $r): ?>
                            <tr>
                                <td><span class="fw-bold text-primary">GRN-<?= $r['id'] ?></span></td>
                                <td class="fw-bold"><?= htmlspecialchars($r['project_name'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($r['warehouse_name'] ?? '—') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">
                                        <?= $r['item_count'] ?> items
                                    </span>
                                    <?php if ($r['accepted_count'] > 0): ?>
                                        <span class="badge bg-success ms-1"><?= $r['accepted_count'] ?> ✓</span>
                                    <?php endif; ?>
                                    <?php if ($r['rejected_count'] > 0): ?>
                                        <span class="badge bg-danger ms-1"><?= $r['rejected_count'] ?> ✗</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $badge = match($r['overall_status']) {
                                        'All Accepted'       => 'bg-success',
                                        'All Rejected'       => 'bg-danger',
                                        'Partially Accepted' => 'bg-warning text-dark',
                                        default              => 'bg-info',
                                    };
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= $r['overall_status'] ?></span>
                                </td>
                                <td><?= htmlspecialchars($r['received_by_name'] ?? '—') ?></td>
                                <td class="text-muted small"><?= date('d M Y', strtotime($r['receiving_date'])) ?></td>
                                <td class="text-center">
                                    <?php 
                                    $canPerformQC = in_array($currentRoleId, [1, 3, 13, 20, 22, 23, 24, 25]) 
                                                    || (function_exists('hasPermission') && (hasPermission('QC & Internal Audit') || hasPermission('QC Dashboard')));
                                    ?>
                                    <?php if ($r['overall_status'] === 'Pending QC'): ?>
                                        <?php if ($canPerformQC): ?>
                                            <a href="<?= $base_url ?>/con-material-receiving-qc/<?= $r['id'] ?>" class="btn btn-sm btn-warning fw-bold" title="Perform QC Review">
                                                <i class="bi bi-clipboard-check me-1"></i> QC Review
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= $base_url ?>/con-material-receiving-qc/<?= $r['id'] ?>" class="btn btn-sm btn-outline-secondary" title="View GRN">
                                                <i class="bi bi-eye me-1"></i> View
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="<?= $base_url ?>/con-material-receiving-qc/<?= $r['id'] ?>" class="btn btn-sm btn-outline-info" title="View QC Results">
                                            <i class="bi bi-eye me-1"></i> View Results
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="bi bi-box-seam me-2"></i>No material receivings.
                                    Create one from a Purchase Order's detail page.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
