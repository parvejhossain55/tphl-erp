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
                    <?php if (!empty($receiving['site_eng_note']) || !empty($receiving['site_eng_photo'])): ?>
                    <hr class="my-3">
                    <div class="row bg-light p-3 rounded mx-0">
                        <div class="col-md-8">
                            <label class="small text-uppercase text-muted d-block fw-bold">Site Engineer Note</label>
                            <p class="mb-0 small"><?= nl2br(htmlspecialchars($receiving['site_eng_note'] ?? '—')) ?></p>
                        </div>
                        <div class="col-md-4">
                            <label class="small text-uppercase text-muted d-block fw-bold">Site Engineer Photo/Doc</label>
                            <?php if (!empty($receiving['site_eng_photo'])): ?>
                                <a href="<?= $base_url . $receiving['site_eng_photo'] ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-eye"></i> View Document
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
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
                    <form action="<?= $base_url ?>/con-material-receiving-qc/<?= $receiving['id'] ?>" method="POST" id="qcForm" enctype="multipart/form-data">

                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">QC General Note</label>
                                <textarea name="qc_note" class="form-control" rows="2" placeholder="Overall quality remarks..."></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">QC Photo/Document</label>
                                <input type="file" name="qc_photo" class="form-control" accept="image/*,.pdf,.doc,.docx">
                                <small class="text-muted">Optional: Upload lab report or inspection photo.</small>
                            </div>
                        </div>

                        <div class="alert alert-warning small mb-3">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <strong>Important:</strong> Accepted items will <strong>await PM approval</strong> for stock update.
                            Rejected items will flag a <strong>vendor penalty</strong> immediately.
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
                                    onclick="return confirm('Submit QC Review? Accepted items will await PM approval.');">
                                <i class="bi bi-check-circle me-1"></i> Submit QC Review
                            </button>
                        </div>
                    </form>

                    <?php else: ?>
                    <!-- QC COMPLETED — Show Results -->
                    <?php if (!empty($receiving['qc_note']) || !empty($receiving['qc_photo'])): ?>
                    <div class="row bg-light p-3 rounded mx-0 mb-4 border border-info">
                        <div class="col-md-8">
                            <label class="small text-uppercase text-muted d-block fw-bold text-info">QC Note</label>
                            <p class="mb-0 small"><?= nl2br(htmlspecialchars($receiving['qc_note'] ?? '—')) ?></p>
                        </div>
                        <div class="col-md-4">
                            <label class="small text-uppercase text-muted d-block fw-bold text-info">QC Photo/Doc</label>
                            <?php if (!empty($receiving['qc_photo'])): ?>
                                <a href="<?= $base_url . $receiving['qc_photo'] ?>" target="_blank" class="btn btn-sm btn-info text-white">
                                    <i class="bi bi-eye"></i> View QC Doc
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

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
                                <div class="small text-muted">QC Accepted</div>
                            </div>
                            <div class="col-md-4">
                                <span class="fw-bold text-danger fs-5">
                                    <?= count(array_filter($items, fn($it) => $it['status'] === 'Rejected')) ?>
                                </span>
                                <div class="small text-muted">QC Rejected</div>
                            </div>
                            <div class="col-md-4">
                                <span class="fw-bold fs-5">
                                    <?= count($items) ?>
                                </span>
                                <div class="small text-muted">Total Items</div>
                            </div>
                        </div>
                    </div>

                    <!-- PM APPROVAL SECTION -->
                    <?php if ($receiving['overall_status'] === 'Pending PM'): ?>
                    <?php 
                        if (session_status() === PHP_SESSION_NONE) session_start();
                        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
                        if (in_array($roleId, [1, 3, 15, 20])): 
                    ?>
                    <hr class="my-4">
                    <div class="alert alert-info border-info mb-0">
                        <h6 class="alert-heading fw-bold"><i class="bi bi-person-check-fill me-2"></i>PM / Management Approval Required</h6>
                        <p class="small mb-3">QC has completed their review. Please verify the QC results and approve. <strong>Stock will be updated upon your approval.</strong></p>
                        
                        <form action="<?= $base_url ?>/con-material-receiving-pm/<?= $receiving['id'] ?>" method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Approval Note (Optional)</label>
                                <textarea name="pm_note" class="form-control" rows="2" placeholder="Any final remarks..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary" onclick="return confirm('Approve this GRN? Stock will be officially updated.')">
                                <i class="bi bi-check2-all me-1"></i> Approve GRN & Update Stock
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                        <div class="alert alert-warning mt-3 mb-0 small">
                            <i class="bi bi-hourglass-split me-1"></i> Waiting for Management/PM Approval to update stock.
                        </div>
                    <?php endif; ?>
                    <?php elseif (in_array($receiving['overall_status'], ['Accepted', 'Partially Accepted', 'Rejected']) && !empty($receiving['pm_approved_at'])): ?>
                        <div class="mt-3 alert alert-success small mb-0">
                            <i class="bi bi-check-circle-fill me-1"></i> Fully Approved by PM/Management on <?= date('d M Y h:i A', strtotime($receiving['pm_approved_at'])) ?>
                            <?php if(!empty($receiving['pm_note'])): ?>
                                <br><strong class="mt-1 d-block">PM Note:</strong> <?= htmlspecialchars($receiving['pm_note']) ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
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
