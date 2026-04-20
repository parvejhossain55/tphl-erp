<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-material-receivings">Material Receiving</a></li>
                <li class="breadcrumb-item active">QC Review</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-material-receivings" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<?php
$isPending = ($receiving['overall_status'] === 'Pending QC');
?>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-11">

            <!-- Header Info -->
            <div class="card__wrapper shadow-sm mb-4">
                <div class="card__body p-4">
                    <div class="row ">
                        <div class="col-md-2">
                            <label class="small text-uppercase text-muted d-block">GRN Number</label>
                            <span class="fw-bold fs-5 text-primary">GRN-<?= $receiving['id'] ?></span>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-uppercase text-muted d-block">Project</label>
                            <span class="fw-bold"><?= htmlspecialchars($receiving['project_name']) ?></span>
                        </div>
                        <div class="col-md-2">
                            <label class="small text-uppercase text-muted d-block">Warehouse</label>
                            <span class="fw-bold"><?= htmlspecialchars($receiving['warehouse_name']) ?></span>
                        </div>
                        <div class="col-md-2">
                            <label class="small text-uppercase text-muted d-block">QC Status</label>
                            <?php
                            $badge = match($receiving['overall_status']) {
                                'Accepted'           => 'bg-success',
                                'Rejected'           => 'bg-danger',
                                'Partially Accepted' => 'bg-warning text-dark',
                                default              => 'bg-info',
                            };
                            ?>
                            <span class="badge <?= $badge ?> px-3"><?= $receiving['overall_status'] ?></span>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-uppercase text-muted d-block">Received</label>
                            <span class="fw-bold">
                                <?= date('d M Y', strtotime($receiving['receiving_date'])) ?>
                                by <?= htmlspecialchars($receiving['received_by_name'] ?? '—') ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- QC Review Table -->
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-clipboard-check me-2"></i>
                        <?= $isPending ? 'Perform QC Inspection' : 'QC Results' ?>
                    </h6>
                </div>
                <div class="card__body p-4">

                    <?php if ($isPending): ?>
                    <!-- QC FORM — Accept/Reject each item -->
                    <form action="<?= $base_url ?>/con-material-receiving-qc/<?= $receiving['id'] ?>" method="POST" id="qcForm">

                        <div class="alert alert-warning small mb-3">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <strong>Important:</strong> Accepted items will <strong>automatically update stock</strong>
                            (con_stocks + con_stock_movements). Rejected items will flag a
                            <strong>vendor penalty</strong>. This action cannot be undone.
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:5%;">#</th>
                                        <th style="width:25%;">Material</th>
                                        <th style="width:10%;">Vendor</th>
                                        <th style="width:12%;" class="text-end">Received Qty</th>
                                        <th style="width:18%;">QC Decision</th>
                                        <th style="width:30%;">Reject Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $i => $item): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td class="fw-bold">
                                            <?= htmlspecialchars($item['material_name']) ?>
                                            <span class="text-muted small">(<?= $item['unit'] ?>)</span>
                                        </td>
                                        <td class="small"><?= htmlspecialchars($item['vendor_name'] ?? '—') ?></td>
                                        <td class="text-end fw-bold"><?= number_format($item['received_quantity'], 3) ?></td>
                                        <td>
                                            <select name="item_status[<?= $item['id'] ?>]"
                                                    class="form-select form-select-sm qc-decision" required
                                                    data-row="<?= $i ?>">
                                                <option value="">-- Select --</option>
                                                <option value="Accepted">✓ Accept</option>
                                                <option value="Rejected">✗ Reject</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="issue_reason[<?= $item['id'] ?>]"
                                                   class="form-control form-control-sm qc-reason"
                                                   data-row="<?= $i ?>"
                                                   placeholder="Required if rejected..."
                                                   disabled>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 text-end">
                            <a href="<?= $base_url ?>/con-material-receivings" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-success px-4 fw-bold"
                                    onclick="return confirm('Submit QC Review? Accepted items will auto-update stock. This cannot be undone.');">
                                <i class="bi bi-check-circle me-1"></i> Submit QC Review
                            </button>
                        </div>
                    </form>

                    <?php else: ?>
                    <!-- QC COMPLETED — Show Results -->
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Material</th>
                                    <th>Vendor</th>
                                    <th class="text-end">Received Qty</th>
                                    <th class="text-center">QC Result</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $i => $item): ?>
                                <tr class="<?= ($item['status'] === 'Rejected') ? 'table-danger' : '' ?>">
                                    <td><?= $i + 1 ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($item['material_name']) ?> (<?= $item['unit'] ?>)</td>
                                    <td class="small"><?= htmlspecialchars($item['vendor_name'] ?? '—') ?></td>
                                    <td class="text-end fw-bold"><?= number_format($item['received_quantity'], 3) ?></td>
                                    <td class="text-center">
                                        <?php if ($item['status'] === 'Accepted'): ?>
                                            <span class="badge bg-success px-3"><i class="bi bi-check-lg me-1"></i>Accepted</span>
                                        <?php elseif ($item['status'] === 'Rejected'): ?>
                                            <span class="badge bg-danger px-3"><i class="bi bi-x-lg me-1"></i>Rejected</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= $item['status'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small"><?= htmlspecialchars($item['issue_reason'] ?? '—') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 p-3 bg-light rounded">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <span class="fw-bold text-success fs-5">
                                    <?= count(array_filter($items, fn($it) => $it['status'] === 'Accepted')) ?>
                                </span>
                                <div class="small text-muted">Accepted (Stock Updated)</div>
                            </div>
                            <div class="col-md-4">
                                <span class="fw-bold text-danger fs-5">
                                    <?= count(array_filter($items, fn($it) => $it['status'] === 'Rejected')) ?>
                                </span>
                                <div class="small text-muted">Rejected (Penalty Raised)</div>
                            </div>
                            <div class="col-md-4">
                                <span class="fw-bold fs-5">
                                    <?= count($items) ?>
                                </span>
                                <div class="small text-muted">Total Items</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Enable/disable reject reason based on QC decision
    document.querySelectorAll('.qc-decision').forEach(function (sel) {
        sel.addEventListener('change', function () {
            var row    = this.getAttribute('data-row');
            var reason = document.querySelector('.qc-reason[data-row="' + row + '"]');
            if (this.value === 'Rejected') {
                reason.disabled = false;
                reason.required = true;
                reason.focus();
            } else {
                reason.disabled = true;
                reason.required = false;
                reason.value    = '';
            }
        });
    });
});
</script>
