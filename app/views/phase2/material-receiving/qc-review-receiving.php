<div class="pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="mb-4">
        <h4><?= htmlspecialchars($page_title) ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-material-receivings">Material Receiving</a></li>
                <li class="breadcrumb-item active">QC Review</li>
            </ol>
        </nav>
    </div>
  
</div>

<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
// Roles: 1=Super Admin, 3=Admin, 13=QC Engineer, 20=Operation Head, 22=PM Head, 23=Purchase Exec, 24=QC & Audit Head, 25=QC Exec
$canQC = in_array($roleId, [1, 3, 13, 20, 22, 23, 24, 25])
         || hasPermission('QC & Internal Audit')
         || hasPermission('QC Dashboard');
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
                                <?php if (!empty($receiving['received_by_emp_id'])): ?>
                                    <span class="badge bg-light text-dark border ms-1" style="font-size:10px;">ID: <?= htmlspecialchars($receiving['received_by_emp_id']) ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    <?php if (!empty($receiving['site_eng_note']) || !empty($receiving['site_eng_photo'])): ?>
                    <hr class="my-3">
                    <div class="row  p-3 rounded mx-0">
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

            <!-- Lab Test Results -->
            <?php if (!empty($labTests)): ?>
            <div class="card__wrapper shadow-sm mb-4">
                <div class="card__header bg-light">
                    <h6 class="card__title m-2 text-primary">
                        <i class="bi bi-flask me-2"></i>Lab Test Results
                    </h6>
                </div>
                <div class="card__body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Test Name</th>
                                    <th>Result Value</th>
                                    <th>Status</th>
                                    <th>Lab Report</th>
                                    <th>Tested By</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($labTests as $test): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($test['test_name']) ?></td>
                                    <td><?= htmlspecialchars($test['result_value']) ?></td>
                                    <td>
                                        <?php if ($test['test_status'] === 'pass'): ?>
                                            <span class="badge bg-success px-2 py-1"><i class="bi bi-check-circle me-1"></i>Pass</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger px-2 py-1"><i class="bi bi-x-circle me-1"></i>Fail</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($test['lab_report_link'])): ?>
                                            <a href="<?= htmlspecialchars($test['lab_report_link']) ?>" target="_blank" class="btn btn-sm btn-outline-info p-1" style="font-size:11px;">
                                                <i class="bi bi-link-45deg"></i> Link
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($test['attachment'])): ?>
                                            <a href="<?= $base_url . $test['attachment'] ?>" target="_blank" class="btn btn-sm btn-outline-primary p-1 ms-1" style="font-size:11px;">
                                                <i class="bi bi-file-earmark-pdf"></i> Doc
                                            </a>
                                        <?php endif; ?>
                                        <?php if (empty($test['lab_report_link']) && empty($test['attachment'])): ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?= htmlspecialchars($test['created_by_name'] ?? '—') ?></td>
                                    <td class="small text-muted"><?= date('d M Y h:i A', strtotime($test['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- QC Review Table -->
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title m-2">
                        <i class="bi bi-clipboard-check me-2"></i>
                        <?= $isPending ? 'Perform QC Inspection' : 'QC Results' ?>
                    </h6>
                </div>
                <div class="card__body p-4">

                    <?php if ($isPending && $canQC): ?>
                    <!-- QC FORM — Accept/Reject each item -->
                    <form action="<?= $base_url ?>/con-material-receiving-qc/<?= $receiving['id'] ?>" method="POST" id="qcForm" enctype="multipart/form-data">
                        <!-- ... (form content) ... -->
                        <div class="row  mb-4">
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
                                                    data-row="<?= $i ?>"
                                                    onchange="togglePartial(<?= $i ?>, <?= $item['received_quantity'] ?>)">
                                                <option value="">-- Select --</option>
                                                <option value="Accepted">✓ Accept All</option>
                                                <option value="Rejected">✗ Reject All</option>
                                                <option value="Partially Accepted">◐ Partial Accept</option>
                                            </select>
                                            <div id="partial-qty-<?= $i ?>" style="display:none;" class="mt-2">
                                                <div class="input-group input-group-sm mb-1">
                                                    <span class="input-group-text bg-success text-white">Accept</span>
                                                    <input type="number" step="0.001" name="accepted_qty[<?= $item['id'] ?>]" id="acc-<?= $i ?>" class="form-control" onkeyup="calcRej(<?= $i ?>, <?= $item['received_quantity'] ?>)">
                                                </div>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-danger text-white">Reject</span>
                                                    <input type="number" step="0.001" name="rejected_qty[<?= $item['id'] ?>]" id="rej-<?= $i ?>" class="form-control" readonly>
                                                </div>
                                            </div>
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
                    
                    <?php if ($isPending && !$canQC): ?>
                        <div class="alert alert-warning small mb-3">
                            <i class="bi bi-hourglass-split me-1"></i> This GRN is currently awaiting Quality Control (QC) review. You are viewing this in read-only mode.
                        </div>
                    <?php endif; ?>
                    <!-- QC COMPLETED — Show Results -->
                    <?php if (!$isPending): ?>
                    <div class="card__wrapper shadow-sm mb-4 border border-info" style="border-radius:12px; overflow:hidden;">
                        <div class="card__header bg-light d-flex justify-content-between align-items-center py-2 px-3">
                            <h6 class="card__title mb-0 text-info fw-bold">
                                <i class="bi bi-shield-check me-2"></i>QC Inspection Details
                            </h6>
                            <span class="badge <?= $badge ?> px-3"><?= htmlspecialchars($receiving['overall_status']) ?></span>
                        </div>
                        <div class="card__body p-3">
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label class="small text-uppercase text-muted d-block fw-bold">Inspected &amp; Reviewed By</label>
                                    <span class="fw-bold text-dark">
                                        <i class="bi bi-person-check text-primary me-1"></i>
                                        <?= htmlspecialchars($receiving['qc_checked_by_name'] ?? 'QC Inspector') ?>
                                        <?php if (!empty($receiving['qc_checked_by_emp_id'])): ?>
                                            <span class="badge bg-light text-dark border ms-1" style="font-size:11px;">ID: <?= htmlspecialchars($receiving['qc_checked_by_emp_id']) ?></span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="small text-uppercase text-muted d-block fw-bold">Inspection Date &amp; Time</label>
                                    <span class="fw-bold text-dark">
                                        <i class="bi bi-clock-history text-secondary me-1"></i>
                                        <?= !empty($receiving['qc_checked_at']) ? date('d M Y, h:i A', strtotime($receiving['qc_checked_at'])) : (!empty($receiving['updated_at']) ? date('d M Y, h:i A', strtotime($receiving['updated_at'])) : '—') ?>
                                    </span>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="small text-uppercase text-muted d-block fw-bold">QC Document / Report</label>
                                    <?php if (!empty($receiving['qc_photo'])): ?>
                                        <a href="<?= $base_url . $receiving['qc_photo'] ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-eye me-1"></i> View Document
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($receiving['qc_note'])): ?>
                                <div class="col-12 mt-2 pt-2 border-top">
                                    <label class="small text-uppercase text-muted d-block fw-bold">QC Inspection Note</label>
                                    <p class="mb-0 text-secondary" style="font-size:13px;"><?= nl2br(htmlspecialchars($receiving['qc_note'])) ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
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
                                    <th class="text-end">Delivered Qty</th>
                                    <th class="text-end text-success">QC Accepted</th>
                                    <th class="text-end text-danger">QC Rejected</th>
                                    <th class="text-center">QC Result</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalAcceptedSum = 0;
                                $totalRejectedSum = 0;
                                foreach ($items as $i => $item): 
                                    $recvQty = (float)$item['received_quantity'];
                                    $rejQty  = ($item['status'] === 'Rejected') ? $recvQty : (float)($item['return_quantity'] ?? 0);
                                    $accQty  = max(0, $recvQty - $rejQty);
                                    
                                    $totalAcceptedSum += $accQty;
                                    $totalRejectedSum += $rejQty;
                                ?>
                                <tr class="<?= ($item['status'] === 'Rejected') ? 'table-danger' : (($item['status'] === 'Partially Accepted') ? 'table-warning' : '') ?>">
                                    <td><?= $i + 1 ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($item['material_name']) ?> (<?= $item['unit'] ?>)</td>
                                    <td class="small"><?= htmlspecialchars($item['vendor_name'] ?? '—') ?></td>
                                    <td class="text-end fw-bold"><?= number_format($recvQty, 3) ?></td>
                                    <td class="text-end fw-bold text-success"><?= number_format($accQty, 3) ?></td>
                                    <td class="text-end fw-bold text-danger"><?= ($rejQty > 0) ? number_format($rejQty, 3) : '0.000' ?></td>
                                    <td class="text-center">
                                        <?php if ($item['status'] === 'Accepted'): ?>
                                            <span class="badge bg-success px-3"><i class="bi bi-check-lg me-1"></i>Accepted</span>
                                        <?php elseif ($item['status'] === 'Rejected'): ?>
                                            <span class="badge bg-danger px-3"><i class="bi bi-x-lg me-1"></i>Rejected</span>
                                        <?php elseif ($item['status'] === 'Partially Accepted'): ?>
                                            <span class="badge bg-warning text-dark px-3"><i class="bi bi-pie-chart-half me-1"></i>Partially Accepted</span>
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

                    <div class="mt-3 p-3 bg-light rounded border">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <span class="fw-bold text-success fs-5">
                                    <?= number_format($totalAcceptedSum, 3) ?>
                                </span>
                                <div class="small text-muted fw-bold">Total QC Accepted Qty</div>
                            </div>
                            <div class="col-md-4">
                                <span class="fw-bold text-danger fs-5">
                                    <?= number_format($totalRejectedSum, 3) ?>
                                </span>
                                <div class="small text-muted fw-bold">Total QC Rejected Qty</div>
                            </div>
                            <div class="col-md-4">
                                <span class="fw-bold fs-5 text-primary">
                                    <?= count($items) ?>
                                </span>
                                <div class="small text-muted fw-bold">Total Items</div>
                            </div>
                        </div>
                    </div>

                    <!-- HEAD / AUDIT APPROVAL SECTION -->
                    <?php if (!empty($receiving['head_approved_by'])): ?>
                    <div class="card__wrapper shadow-sm mt-4 border border-success" style="border-radius:12px; overflow:hidden;">
                        <div class="card__header bg-light d-flex justify-content-between align-items-center py-2 px-3">
                            <h6 class="card__title mb-0 text-success fw-bold">
                                <i class="bi bi-patch-check-fill me-2"></i>QC &amp; Internal Audit Head Approval
                            </h6>
                            <span class="badge bg-success px-3">Endorsed &amp; Approved</span>
                        </div>
                        <div class="card__body p-3">
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label class="small text-uppercase text-muted d-block fw-bold">Head / Audit Approved By</label>
                                    <span class="fw-bold text-dark">
                                        <i class="bi bi-person-badge text-success me-1"></i>
                                        <?= htmlspecialchars($receiving['head_approved_by_name'] ?? 'Audit Head') ?>
                                        <?php if (!empty($receiving['head_approved_by_emp_id'])): ?>
                                            <span class="badge bg-light text-dark border ms-1" style="font-size:11px;">ID: <?= htmlspecialchars($receiving['head_approved_by_emp_id']) ?></span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="small text-uppercase text-muted d-block fw-bold">Approval Date &amp; Time</label>
                                    <span class="fw-bold text-dark">
                                        <i class="bi bi-clock-history text-secondary me-1"></i>
                                        <?= !empty($receiving['head_approved_at']) ? date('d M Y, h:i A', strtotime($receiving['head_approved_at'])) : '—' ?>
                                    </span>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="small text-uppercase text-muted d-block fw-bold">Approval Status</label>
                                    <span class="badge bg-success px-3 py-1" style="font-size:12px;">
                                        <i class="bi bi-check-all me-1"></i> Approved
                                    </span>
                                </div>
                                <?php if (!empty($receiving['head_note'])): ?>
                                <div class="col-12 mt-2 pt-2 border-top">
                                    <label class="small text-uppercase text-muted d-block fw-bold">Head Remarks / Note</label>
                                    <p class="mb-0 text-secondary" style="font-size:13px;"><?= nl2br(htmlspecialchars($receiving['head_note'])) ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php elseif (!$isPending): ?>
                        <?php 
                        $canHeadApprove = in_array($roleId, [1, 3, 15, 20, 22, 24, 25, 33]) || (function_exists('hasPermission') && hasPermission('QC & Internal Audit'));
                        if ($canHeadApprove): 
                        ?>
                        <div class="con-panel mt-4 shadow-sm" style="border:1.5px solid rgba(var(--clr-theme-secondary-rgb),.4); background:rgba(var(--clr-theme-secondary-rgb),.03); border-radius:12px; padding:20px;">
                            <div class="d-flex align-items-center mb-3">
                                <div style="width:42px;height:42px;border-radius:50%;background:rgba(var(--clr-theme-secondary-rgb),.15);display:flex;align-items:center;justify-content:center;margin-right:15px;flex-shrink:0;">
                                    <i class="fal fa-user-check" style="color:var(--clr-theme-secondary);font-size:20px;"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1" style="color:#6d28d9;font-size:15px;">QC &amp; Internal Audit Head Endorsement / Approval</h6>
                                    <p class="text-muted small mb-0">Initial QC review was completed by <strong><?= htmlspecialchars($receiving['qc_checked_by_name'] ?? 'QC Inspector') ?><?= !empty($receiving['qc_checked_by_emp_id']) ? ' (ID: ' . htmlspecialchars($receiving['qc_checked_by_emp_id']) . ')' : '' ?></strong>. As QC &amp; Internal Audit Head, you can endorse and approve this GRN.</p>
                                </div>
                            </div>
                            
                            <form action="<?= $GLOBALS['base_url'] ?>/con-material-receiving-head/<?= $receiving['id'] ?>" method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-bold" style="font-size:12px;color:#4b5563;">Head Approval / Audit Note (Optional)</label>
                                    <textarea name="head_note" class="form-control" rows="2" placeholder="Remarks, audit comments or final sign-off note..." style="border-radius:8px;border:1px solid #cbd5e1;font-size:13px;"></textarea>
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn px-4 fw-bold" style="background:linear-gradient(135deg,var(--clr-theme-secondary),#6d28d9);color:#fff;border-radius:8px;font-size:13px;padding:9px 22px;border:none;box-shadow:0 4px 6px -1px rgba(var(--clr-theme-secondary-rgb),.3);" onclick="return confirm('Confirm Head Approval for this GRN?')">
                                        <i class="fal fa-check-double me-1"></i> Approve as QC &amp; Internal Audit Head
                                    </button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- PM APPROVAL SECTION (Optional) -->
                    <?php if ($receiving['overall_status'] === 'Pending PM'): ?>
                    <?php 
                        if (session_status() === PHP_SESSION_NONE) session_start();
                        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
                        if (in_array($roleId, [1, 3, 15, 20, 22, 23])): 
                    ?>
                    <hr class="my-4" style="border-color:#e2e8f0;">
                    <div class="con-panel" style="border-color:rgba(var(--clr-theme-secondary-rgb),.4); background:rgba(var(--clr-theme-secondary-rgb),.04); border-radius:12px; padding:20px;">
                        <div class="d-flex align-items-center mb-3">
                            <div style="width:40px;height:40px;border-radius:50%;background:rgba(var(--clr-theme-secondary-rgb),.15);display:flex;align-items:center;justify-content:center;margin-right:15px;">
                                <i class="fal fa-user-check" style="color:var(--clr-theme-secondary);font-size:18px;"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1" style="color:#6d28d9;font-size:15px;">PM Approval Required</h6>
                                <p class="text-muted small mb-0">QC has completed their review. Please verify the results and approve to update stock.</p>
                            </div>
                        </div>
                        
                        <form action="<?= $GLOBALS['base_url'] ?>/con-material-receiving-pm/<?= $receiving['id'] ?>" method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold" style="font-size:12px;color:#4b5563;">Approval Note (Optional)</label>
                                <textarea name="pm_note" class="form-control" rows="2" placeholder="Any final remarks..." style="border-radius:8px;border:1px solid #cbd5e1;font-size:13px;"></textarea>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn" style="background:linear-gradient(135deg,var(--clr-theme-secondary),#6d28d9);color:#fff;border-radius:8px;font-weight:600;font-size:13px;padding:8px 20px;border:none;box-shadow:0 4px 6px -1px rgba(var(--clr-theme-secondary-rgb),.3);" onclick="return confirm('Approve this GRN? Stock will be officially updated.')">
                                    <i class="fal fa-check-double me-1"></i> Acknowledge &amp; Update Stock
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                    <?php elseif (!empty($receiving['pm_approved_at'])): ?>
                        <div class="mt-3 alert alert-success small mb-0">
                           Fully Approved by PM <?= !empty($receiving['pm_checked_by_name']) ? '<strong>(' . htmlspecialchars($receiving['pm_checked_by_name']) . ')</strong>' : '' ?> on <?= date('d M Y h:i A', strtotime($receiving['pm_approved_at'])) ?>
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
    function togglePartial(rowId, totalQty) {
        const select = document.querySelector(`.qc-decision[data-row="${rowId}"]`);
        const val = select.value;
        const partialDiv = document.getElementById(`partial-qty-${rowId}`);
        const reasonInput = document.querySelector(`.qc-reason[data-row="${rowId}"]`);
        
        if (val === 'Partially Accepted') {
            partialDiv.style.display = 'block';
            reasonInput.disabled = false;
            reasonInput.placeholder = 'Reason for rejected portion...';
            reasonInput.required = true;
            document.getElementById(`acc-${rowId}`).required = true;
        } else {
            partialDiv.style.display = 'none';
            document.getElementById(`acc-${rowId}`).required = false;
            if (val === 'Rejected') {
                reasonInput.disabled = false;
                reasonInput.placeholder = 'Required if rejected...';
                reasonInput.required = true;
                reasonInput.focus();
            } else {
                reasonInput.disabled = true;
                reasonInput.value = '';
                reasonInput.required = false;
            }
        }
    }
    
    function calcRej(rowId, totalQty) {
        const accInput = document.getElementById(`acc-${rowId}`);
        const rejInput = document.getElementById(`rej-${rowId}`);
        let acc = parseFloat(accInput.value) || 0;
        if (acc > totalQty) {
            acc = totalQty;
            accInput.value = acc;
        } else if (acc < 0) {
            acc = 0;
            accInput.value = acc;
        }
        rejInput.value = (totalQty - acc).toFixed(3);
    }
</script>
