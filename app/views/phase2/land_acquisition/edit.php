<?php
$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
$canModify = in_array($roleId, [1, 3, 14]);
?>

<div class="mb-3 pagetitle d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4 class="mb-2">Manage Land Acquisition: <?= htmlspecialchars($acquisition['land_owner_name']) ?></h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-land-acquisitions">Land Acquisitions</a></li>
                <li class="breadcrumb-item active" aria-current="page">Manage</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-land-acquisitions" class="btn btn-outline-secondary">
        <i class="fa-light fa-arrow-left me-1"></i> Back to Portfolio
    </a>
</div>

<div class="row">
    <!-- Main Info & Edit Form Column -->
    <div class="col-xl-7 col-lg-6 mb-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header border-0 py-3">
                <h5 class="mb-0 fw-bold text-primary"><i class="fa-light fa-file-invoice me-2"></i>Edit Land Details</h5>
            </div>
            <div class="card-body">
                <form action="<?= $base_url ?>/con-land-acquisitions/update" method="POST">
                    <input type="hidden" name="id" value="<?= $acquisition['id'] ?>">
                    <input type="hidden" name="action" value="update_info">

                    <div class="row mb-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Land / Project Name (Tentative / Final) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="land_project_name" value="<?= htmlspecialchars($acquisition['land_project_name'] ?? '') ?>" required <?= !$canModify ? 'readonly' : '' ?> placeholder="e.g. Project No 60 (New)">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Link to Existing Project</label>
                            <select class="form-select select2" name="project_id" <?= !$canModify ? 'disabled' : '' ?>>
                                <option value="">-- Not Finalized / Tentative Deal --</option>
                                <?php foreach ($projects as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= $acquisition['project_id'] == $p['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['name']) ?> <?= !empty($p['project_status']) ? '('.$p['project_status'].')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Landowner Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="land_owner_name" value="<?= htmlspecialchars($acquisition['land_owner_name']) ?>" required <?= !$canModify ? 'readonly' : '' ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Landowner Phone</label>
                            <input type="text" class="form-control" name="land_owner_phone" value="<?= htmlspecialchars($acquisition['land_owner_phone'] ?? '') ?>" <?= !$canModify ? 'readonly' : '' ?>>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Land Receiver Name (কোম্পানির পক্ষে গ্রহণকারী)</label>
                            <input type="text" class="form-control" name="land_receiver_name" value="<?= htmlspecialchars($acquisition['land_receiver_name'] ?? '') ?>" <?= !$canModify ? 'readonly' : '' ?> placeholder="Receiver full name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Location / Address</label>
                            <input type="text" class="form-control" name="location" value="<?= htmlspecialchars($acquisition['location'] ?? '') ?>" <?= !$canModify ? 'readonly' : '' ?>>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Media / Broker Info (মিডিয়া / দালাল তথ্য)</label>
                            <input type="text" class="form-control" name="media_broker_info" value="<?= htmlspecialchars($acquisition['media_broker_info'] ?? '') ?>" <?= !$canModify ? 'readonly' : '' ?> placeholder="Broker name, phone & commission notes">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Power of Attorney (POA) Details (পাওয়ার অব অ্যাটর্নি তথ্য)</label>
                            <textarea class="form-control" name="power_of_attorney" rows="1" <?= !$canModify ? 'readonly' : '' ?> placeholder="POA holder, deed number, date etc."><?= htmlspecialchars($acquisition['power_of_attorney'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Total Area</label>
                            <input type="number" step="0.0001" class="form-control" name="total_area" value="<?= $acquisition['total_area'] ?>" required <?= !$canModify ? 'readonly' : '' ?>>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Area Unit</label>
                            <select class="form-select" name="area_unit" <?= !$canModify ? 'disabled' : '' ?> required>
                                <option value="decimal" <?= $acquisition['area_unit'] === 'decimal' ? 'selected' : '' ?>>Decimal</option>
                                <option value="katha" <?= $acquisition['area_unit'] === 'katha' ? 'selected' : '' ?>>Katha</option>
                                <option value="bigha" <?= $acquisition['area_unit'] === 'bigha' ? 'selected' : '' ?>>Bigha</option>
                                <option value="sft" <?= $acquisition['area_unit'] === 'sft' ? 'selected' : '' ?>>Square Feet</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Price per Unit</label>
                            <input type="number" step="0.01" class="form-control" name="price_per_unit" value="<?= $acquisition['price_per_unit'] ?>" required <?= !$canModify ? 'readonly' : '' ?>>
                        </div>
                    </div>

                    <div class="mb-3 alert alert-success-subtle border border-success-subtle py-2 px-3 rounded-2 d-flex justify-content-between align-items-center">
                        <span class="fw-semibold text-success">Total Land Agreed Valuation:</span>
                        <strong class="text-success fs-6">BDT <?= number_format($acquisition['total_amount'], 2) ?></strong>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" name="status" <?= !$canModify ? 'disabled' : '' ?>>
                                <option value="selected" <?= $acquisition['status'] === 'selected' ? 'selected' : '' ?>>Selected</option>
                                <option value="bayana" <?= $acquisition['status'] === 'bayana' ? 'selected' : '' ?>>Bayana</option>
                                <option value="registered" <?= $acquisition['status'] === 'registered' ? 'selected' : '' ?>>Registered</option>
                                <option value="cancelled" <?= $acquisition['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Remarks</label>
                            <textarea class="form-control" name="remarks" rows="2" <?= !$canModify ? 'readonly' : '' ?>><?= htmlspecialchars($acquisition['remarks'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="fw-bold mb-3 text-secondary"><i class="fa-light fa-tasks me-2"></i>Process Status Tracking</h6>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-warning"><i class="fa-light fa-handshake me-1"></i> Bayana Status</label>
                            <select class="form-select" name="bayana_status" <?= !$canModify ? 'disabled' : '' ?>>
                                <option value="pending" <?= ($acquisition['bayana_status'] ?? 'pending') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="cancel" <?= ($acquisition['bayana_status'] ?? '') === 'cancel' ? 'selected' : '' ?>>Cancelled</option>
                                <option value="complete" <?= ($acquisition['bayana_status'] ?? '') === 'complete' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-warning"><i class="fa-light fa-calendar-check me-1"></i> Bayana Completion Date</label>
                            <input type="date" class="form-control" name="bayana_complete_date" value="<?= htmlspecialchars($acquisition['bayana_complete_date'] ?? '') ?>" <?= !$canModify ? 'readonly' : '' ?>>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-success"><i class="fa-light fa-file-signature me-1"></i> Registration Status</label>
                            <select class="form-select" name="registration_status" <?= !$canModify ? 'disabled' : '' ?>>
                                <option value="pending" <?= ($acquisition['registration_status'] ?? 'pending') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="cancel" <?= ($acquisition['registration_status'] ?? '') === 'cancel' ? 'selected' : '' ?>>Cancelled</option>
                                <option value="complete" <?= ($acquisition['registration_status'] ?? '') === 'complete' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-success"><i class="fa-light fa-calendar-check me-1"></i> Registration Completion Date</label>
                            <input type="date" class="form-control" name="registration_complete_date" value="<?= htmlspecialchars($acquisition['registration_complete_date'] ?? '') ?>" <?= !$canModify ? 'readonly' : '' ?>>
                        </div>
                    </div>

                    <?php if ($canModify): ?>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Update Main Info & Statuses</button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Registration & Mutation Section -->
        <div class="card border-0 shadow-sm">
            <?php $regComplete = ($acquisition['registration_status'] ?? '') === 'complete'; ?>
            <div class="card-header border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-success"><i class="fa-light fa-stamp me-2"></i>Deed Registration & Mutation Details</h5>
                <?php if ($regComplete): ?>
                    <span class="badge bg-success py-2 px-3"><i class="fa-light fa-circle-check me-1"></i> Registration Completed</span>
                <?php endif; ?>
            </div>
            <?php if ($regComplete): ?>
                <div class="alert alert-success rounded-0 border-0 mb-0 py-2 px-4" style="font-size:13px;">
                    <i class="fa-light fa-lock me-1"></i> This registration has been marked as <strong>Completed</strong>. The form is locked. To make changes, update the Registration Status in the section above.
                </div>
            <?php endif; ?>
            <div class="card-body">
                <form action="<?= $base_url ?>/con-land-acquisitions/update" method="POST">
                    <input type="hidden" name="id" value="<?= $acquisition['id'] ?>">
                    <input type="hidden" name="action" value="update_registration">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Registration Deed Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="registration_no" value="<?= htmlspecialchars($registration['registration_no'] ?? '') ?>" required <?= (!$canModify || $regComplete) ? 'readonly' : '' ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Registration Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="registration_date" value="<?= htmlspecialchars($registration['registration_date'] ?? date('Y-m-d')) ?>" required <?= (!$canModify || $regComplete) ? 'readonly' : '' ?>>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Sub-Registry Office</label>
                            <input type="text" class="form-control" name="sub_registry_office" value="<?= htmlspecialchars($registration['sub_registry_office'] ?? '') ?>" <?= (!$canModify || $regComplete) ? 'readonly' : '' ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Registration / Govt Stamp Cost (BDT)</label>
                            <input type="number" step="0.01" class="form-control" name="registration_cost" value="<?= $registration['registration_cost'] ?? 0.00 ?>" <?= (!$canModify || $regComplete) ? 'readonly' : '' ?>>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mutation Status</label>
                            <select class="form-select" name="mutation_status" <?= (!$canModify || $regComplete) ? 'disabled' : '' ?>>
                                <option value="Pending" <?= ($registration['mutation_status'] ?? '') === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Completed" <?= ($registration['mutation_status'] ?? '') === 'Completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mutation Date</label>
                            <input type="date" class="form-control" name="mutation_date" value="<?= htmlspecialchars($registration['mutation_date'] ?? '') ?>" <?= (!$canModify || $regComplete) ? 'readonly' : '' ?>>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Deed Document Link</label>
                            <input type="url" class="form-control" name="deed_document_link" placeholder="Google Drive, Dropbox, etc." value="<?= htmlspecialchars($registration['deed_document_link'] ?? '') ?>" <?= (!$canModify || $regComplete) ? 'readonly' : '' ?>>
                            <?php if (!empty($registration['deed_document_link'])): ?>
                                <div class="mt-2">
                                    <a href="<?= htmlspecialchars($registration['deed_document_link']) ?>" target="_blank" class="btn btn-sm btn-success">
                                        <i class="fa-light fa-external-link me-1"></i> View Deed Document
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Registration Remarks</label>
                        <textarea class="form-control" name="remarks" rows="2" <?= (!$canModify || $regComplete) ? 'readonly' : '' ?>><?= htmlspecialchars($registration['remarks'] ?? '') ?></textarea>
                    </div>

                    <?php if ($canModify && !$regComplete): ?>
                        <div class="text-end">
                            <button type="submit" class="btn btn-success">Save Registration Details</button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Bayana Payments & Requisitions Column -->
    <div class="col-xl-5 col-lg-6">
        <!-- Requisition & Accounts Payment Ledger -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header border-0 py-3 bg-white d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-bold text-success"><i class="fa-light fa-receipt me-2"></i>Accounts Payment Ledger</h5>
                    <small class="text-muted" style="font-size:11px;">Requisitions approved by Management & paid by Accounts</small>
                </div>
                <a href="<?= $base_url ?>/con-land-requisitions?land_acquisition_id=<?= $acquisition['id'] ?>" class="btn btn-sm btn-success">
                    <i class="fa-light fa-plus me-1"></i> New Req
                </a>
            </div>
            <div class="card-body p-3">
                <!-- Financial Summary Cards -->
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-2 border rounded bg-light">
                            <span class="text-muted small d-block" style="font-size:10px;">Land Agreed Price</span>
                            <strong class="text-dark">BDT <?= number_format((float)$acquisition['total_amount'], 2) ?></strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded bg-light">
                            <span class="text-muted small d-block" style="font-size:10px;">Paid to Landowner</span>
                            <strong class="text-success">BDT <?= number_format($cumLandPaid, 2) ?></strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded bg-light">
                            <span class="text-muted small d-block" style="font-size:10px;">Remaining Land Due</span>
                            <strong class="<?= $remainingLandDue > 0 ? 'text-danger' : 'text-success' ?>">BDT <?= number_format($remainingLandDue, 2) ?></strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded bg-light">
                            <span class="text-muted small d-block" style="font-size:10px;">Extra Costs Paid</span>
                            <strong class="text-info">BDT <?= number_format($cumExtraPaid, 2) ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Requisitions Timeline / List -->
                <h6 class="fw-bold small text-secondary mb-2"><i class="fa-light fa-list-check me-1"></i>Requisitions & Payment History (<?= count($requisitions) ?>)</h6>
                <?php if (empty($requisitions)): ?>
                    <div class="text-center py-4 text-muted small">
                        No requisitions requested for this land yet.
                        <div class="mt-2">
                            <a href="<?= $base_url ?>/con-land-requisitions?land_acquisition_id=<?= $acquisition['id'] ?>" class="btn btn-sm btn-outline-success">
                                <i class="fa-light fa-plus me-1"></i>Create First Requisition
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush border rounded-2" style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($requisitions as $idx => $r): ?>
                            <?php 
                                $isPaid = ($r['accounts_status'] === 'Paid');
                                $isMgmtApp = ($r['mgmt_status'] === 'Approved');
                                $isLandCost = in_array($r['requisition_type'], ['Bayana', 'Registration']);
                            ?>
                            <div class="list-group-item p-3 <?= $isPaid ? 'bg-light-subtle' : '' ?>">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div>
                                        <span class="badge bg-secondary me-1">Req #<?= $idx + 1 ?></span>
                                        <strong><?= htmlspecialchars($r['requisition_type']) ?></strong>
                                        <?php if (!empty($r['cost_purpose'])): ?>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle"><?= htmlspecialchars($r['cost_purpose']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!$isLandCost): ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:9px;">Extra Cost</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-end">
                                        <strong class="text-success fs-6">BDT <?= number_format((float)$r['requested_amount'], 2) ?></strong>
                                    </div>
                                </div>
                                
                                <div class="small text-muted mb-2" style="font-size:11px;">
                                    <?= htmlspecialchars($r['purpose']) ?>
                                </div>

                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-1 small pt-2 border-top" style="font-size:10px;">
                                    <div>
                                        <?php if ($r['mgmt_status'] === 'Approved'): ?>
                                            <span class="badge bg-success py-0 px-1"><i class="fa-light fa-check me-1"></i>Mgmt Approved</span>
                                        <?php elseif ($r['mgmt_status'] === 'Rejected'): ?>
                                            <span class="badge bg-danger py-0 px-1"><i class="fa-light fa-xmark me-1"></i>Mgmt Rejected</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark py-0 px-1"><i class="fa-light fa-clock me-1"></i>Pending Mgmt</span>
                                        <?php endif; ?>

                                        <?php if ($isPaid): ?>
                                            <span class="badge bg-success py-0 px-1"><i class="fa-light fa-check-double me-1"></i>Paid</span>
                                        <?php elseif ($r['mgmt_status'] === 'Approved'): ?>
                                            <span class="badge bg-warning text-dark py-0 px-1"><i class="fa-light fa-wallet me-1"></i>Ready for Payment</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border py-0 px-1">Waiting Mgmt</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isPaid): ?>
                                        <div class="text-success fw-semibold">
                                            <i class="fa-light fa-check-double me-1"></i>Paid: <?= date('d M Y', strtotime($r['accounts_paid_at'])) ?>
                                            <?php if (!empty($r['transaction_no'])): ?>
                                                (Txn: <code><?= htmlspecialchars($r['transaction_no']) ?></code>)
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif ($r['mgmt_status'] === 'Approved'): ?>
                                        <div class="text-warning fw-bold">
                                            <i class="fa-light fa-wallet me-1"></i>Pending Accounts Payment
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted">
                                            <i class="fa-light fa-clock me-1"></i>Pending Management Approval
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($isPaid): ?>
                                    <div class="bg-light p-2 rounded mt-2 border" style="font-size:11px;">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>
                                                <strong class="text-primary"><i class="fa-light fa-arrow-turn-down-right me-1"></i>Paid For:</strong> 
                                                <?= $isLandCost ? 'Landowner ('.htmlspecialchars($acquisition['land_owner_name']).')' : htmlspecialchars($r['cost_purpose'] ?? 'Extra Cost') ?>
                                            </span>
                                            <span>Method: <strong><?= htmlspecialchars($r['payment_method'] ?? 'N/A') ?></strong></span>
                                        </div>
                                        <?php if (!empty($r['accounts_note'])): ?>
                                            <div class="text-muted small mt-1">
                                                <i class="fa-light fa-comment-dots me-1 text-primary"></i><strong>Accounts Note:</strong> "<?= htmlspecialchars($r['accounts_note']) ?>"
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($r['attachment_path'])): ?>
                                            <div class="mt-1">
                                                <a href="<?= $base_url ?>/public/<?= htmlspecialchars($r['attachment_path']) ?>" target="_blank" class="badge bg-info-subtle text-info border border-info-subtle text-decoration-none">
                                                    <i class="fa-light fa-paperclip me-1"></i>Payment Receipt / Voucher
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($isLandCost): ?>
                                    <div class="mt-2 text-muted small d-flex justify-content-between" style="font-size:10px;">
                                        <span>Cumulative Land Paid: <strong>BDT <?= number_format($r['cum_land_paid'], 2) ?></strong></span>
                                        <span>Remaining Due: <strong class="<?= $r['remaining_land_due'] > 0 ? 'text-danger' : 'text-success' ?>">BDT <?= number_format($r['remaining_land_due'], 2) ?></strong></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header border-0 py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-bold text-warning"><i class="fa-light fa-wallet me-2"></i>Bayana (Advance) Payments</h5>
                    <?php if ($totalBayanaBudget > 0): ?>
                        <small class="text-muted" style="font-size:11px;">
                            Paid: <strong>BDT <?= number_format($totalBayanaManualPaid, 2) ?></strong>
                            / Budget: <strong>BDT <?= number_format($totalBayanaBudget, 2) ?></strong>
                        </small>
                    <?php endif; ?>
                </div>
                <?php if ($canModify && !($bayanaPaidFull ?? false)): ?>
                <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="collapse" data-bs-target="#newBayanaForm">
                    <i class="fa-light fa-plus me-1"></i> Add Payment
                </button>
                <?php elseif ($bayanaPaidFull ?? false): ?>
                <span class="badge bg-success py-2 px-3"><i class="fa-light fa-circle-check me-1"></i> Fully Paid</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <!-- Add Bayana Payment Form -->
                <div class="collapse mb-4" id="newBayanaForm">
                    <div class="card card-body  border-0">
                        <h6 class="fw-bold mb-3">Record Bayana Payment</h6>
                        <form action="<?= $base_url ?>/con-land-acquisitions/update" method="POST">
                            <input type="hidden" name="id" value="<?= $acquisition['id'] ?>">
                            <input type="hidden" name="action" value="add_bayana">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Payment Amount (BDT) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" name="payment_amount" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="payment_date" value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Payment Method</label>
                                <select class="form-select" name="payment_method">
                                    <option value="Bank" selected>Bank Transfer / Cheque</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Mobile Banking">Mobile Banking (bKash/Nagad/Rocket)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Transaction No. / Cheque No.</label>
                                <input type="text" class="form-control" name="transaction_no">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Document Link</label>
                                <input type="url" class="form-control" name="bayana_document_link" placeholder="Scanned receipt or bank statement link">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Remarks</label>
                                <textarea class="form-control" name="remarks" rows="2"></textarea>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-warning">Save Payment</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Bayana List -->
                <div class="timeline">
                    <?php if (empty($bayanas)): ?>
                        <div class="text-center py-4 text-muted">No bayana payments recorded yet.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($bayanas as $b): ?>
                                <div class="list-group-item px-0 py-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="mb-0 fw-bold">BDT <?= number_format($b['payment_amount'], 2) ?></h6>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($b['payment_method']) ?></span>
                                    </div>
                                    <div class="small text-muted mb-2">
                                        Date: <strong><?= date('M d, Y', strtotime($b['payment_date'])) ?></strong> | By: <?= htmlspecialchars($b['creator_name']) ?>
                                    </div>
                                    <?php if (!empty($b['transaction_no'])): ?>
                                        <div class="small mb-1">Txn: <code><?= htmlspecialchars($b['transaction_no']) ?></code></div>
                                    <?php endif; ?>
                                    <?php if (!empty($b['remarks'])): ?>
                                        <p class="mb-2 text-muted small" style="font-style: italic;">"<?= htmlspecialchars($b['remarks']) ?>"</p>
                                    <?php endif; ?>
                                    <?php if (!empty($b['bayana_document_link'])): ?>
                                        <a href="<?= htmlspecialchars($b['bayana_document_link']) ?>" target="_blank" class="btn btn-sm btn-outline-warning py-0 px-2" style="font-size:11px;">
                                            <i class="fa-light fa-file-arrow-down me-1"></i>View Document
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$page_script = "
$(document).ready(function() {
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            width: '100%',
            placeholder: 'Search or Select Project...'
        });
    }
});
";
?>
