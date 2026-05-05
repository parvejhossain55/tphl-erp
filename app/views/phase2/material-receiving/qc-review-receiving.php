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
if (session_status() === PHP_SESSION_NONE) session_start();
$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
// Roles: 1=Super Admin, 3=Admin, 13=QC Engineer, 22/23=Other Admins
$canQC = in_array($roleId, [1, 3, 13, 22, 23]);
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
                    <?php if (!empty($receiving['qc_note']) || !empty($receiving['qc_photo'])): ?>
                    <div class="row  p-3 rounded mx-0 mb-4 border border-info">
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

                    <div class="mt-3 p-3  rounded">
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
                        if (in_array($roleId, [1, 3, 15, 20, 22, 23])): 
                    ?>
                    <hr class="my-4" style="border-color:#e2e8f0;">
                    <div class="con-panel" style="border-color:rgba(139,92,246,.4); background:rgba(139,92,246,.04); border-radius:12px; padding:20px;">
                        <div class="d-flex align-items-center mb-3">
                            <div style="width:40px;height:40px;border-radius:50%;background:rgba(139,92,246,.15);display:flex;align-items:center;justify-content:center;margin-right:15px;">
                                <i class="fal fa-user-check" style="color:#7c3aed;font-size:18px;"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1" style="color:#6d28d9;font-size:15px;">PM / Management Approval Required</h6>
                                <p class="text-muted small mb-0">QC has completed their review. Please verify the results and approve to update stock.</p>
                            </div>
                        </div>
                        
                        <form action="<?= $GLOBALS['base_url'] ?>/con-material-receiving-pm/<?= $receiving['id'] ?>" method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold" style="font-size:12px;color:#4b5563;">Approval Note (Optional)</label>
                                <textarea name="pm_note" class="form-control" rows="2" placeholder="Any final remarks..." style="border-radius:8px;border:1px solid #cbd5e1;font-size:13px;"></textarea>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);color:#fff;border-radius:8px;font-weight:600;font-size:13px;padding:8px 20px;border:none;box-shadow:0 4px 6px -1px rgba(139,92,246,.3);" onclick="return confirm('Approve this GRN? Stock will be officially updated.')">
                                    <i class="fal fa-check-double me-1"></i> Acknowledge &amp; Update Stock
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php else: ?>
                        <div class="con-panel mt-4" style="border-color:rgba(245,158,11,.3); background:rgba(245,158,11,.05); border-radius:10px; padding:15px;">
                            <div class="d-flex align-items-center">
                                <i class="fal fa-hourglass-half me-2" style="color:#d97706;font-size:16px;"></i>
                                <span style="color:#b45309;font-size:13px;font-weight:600;">Waiting for Management / PM Approval to update stock.</span>
                            </div>
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
