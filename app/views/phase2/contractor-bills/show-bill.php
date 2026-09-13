<?php
$base_url   = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Contractor Bill Detail';

$statusMap = [
    'draft'          => ['label' => 'Draft',          'color' => 'warning'],
    'submitted'      => ['label' => 'Submitted',      'color' => 'info'],
    'verified'       => ['label' => 'Verified',       'color' => 'primary'],
    'qc_approved'    => ['label' => 'QC Approved',    'color' => 'secondary'],
    'approved'       => ['label' => 'Approved',       'color' => 'success'],
    'rejected'       => ['label' => 'Rejected',       'color' => 'danger'],
    'paid'           => ['label' => 'Paid',           'color' => 'secondary'],
    'partially_paid' => ['label' => 'Partially Paid', 'color' => 'info'],
    'fully_paid'     => ['label' => 'Fully Paid',     'color' => 'secondary'],
];
$status     = $bill['status'] ?? 'submitted';
$statusInfo = $statusMap[$status] ?? ['label' => ucfirst(str_replace('_', ' ', $status)), 'color' => 'secondary'];

$canApproveReq   = $canApprove ?? false;
$canApproveQCReq = $canApproveQC ?? false;
$canPayReq       = $canProcessPayment ?? false;
$payments        = $payments ?? [];
$totalPaid       = $total_paid ?? 0;
$dueAmount       = $due_amount ?? 0;
$payableAmount   = $payable_amount ?? 0;

$isSubmitted     = in_array($status, ['submitted', 'verified']);
$isQCApproved    = ($status === 'qc_approved');
$isApproved      = in_array($status, ['approved', 'partially_paid']);
$isRejected      = ($status === 'rejected');
$isFullyPaid     = in_array($status, ['fully_paid', 'paid']);
$showPaymentForm = $canPayReq && $isApproved && $dueAmount > 0;

// Settlement variables
$settlements           = $settlements ?? [];
$totalPaidToContractor = $total_paid_to_contractor ?? 0;
$currentUserId         = $current_user_id ?? null;
// Site engineer can submit settlement when accounts has paid (any paid status)
$billIsPaid            = in_array($status, ['approved', 'partially_paid', 'fully_paid', 'paid']);
$canSubmitSettlement   = $billIsPaid && $totalPaid > 0;
?>

<!-- Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div class="my-auto">
        <h4 class="page-title fw-semibold fs-18 mb-0">Contractor Bill #<?= $bill['id'] ?></h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-contractor-bills">Contractor Bills</a></li>
                <li class="breadcrumb-item active">#<?= $bill['id'] ?></li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <?php if (!empty($payments) || $totalPaid > 0): ?>
            <a href="<?= $base_url ?>/expense-adjustments/create?parent_type=contractor&cashout_request_id=<?= $bill['id'] ?>&project_id=<?= $bill['project_id'] ?>" class="btn btn-warning btn-sm">
                <i class="bi bi-sliders me-1"></i> Settlement / Adjustment
            </a>
            <a href="<?= $base_url ?>/print-debit-voucher?module=contractor_bill&id=<?= $bill['id'] ?>" target="_blank" class="btn btn-primary btn-sm">
                <i class="bi bi-printer me-1"></i> Debit Voucher
            </a>
        <?php endif; ?>
        <span class="badge bg-<?= $statusInfo['color'] ?> fs-6 px-3 py-2"><?= $statusInfo['label'] ?></span>
        <a href="<?= $base_url ?>/con-contractor-bills" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content (Left) -->
        <div class="col-lg-8">
            <!-- Bill Details Card -->
            <div class="card custom-card mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-receipt me-2"></i>Bill Details</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 text-muted">
                            <div class="info-label text-uppercase text-muted small fw-bold">Project</div>
                            <div class="info-value"><?= htmlspecialchars($bill['project_name'] ?? '—') ?></div>
                        </div>
                        <div class="col-md-6 text-muted">
                            <div class="info-label text-uppercase text-muted small fw-bold">Contractor</div>
                            <div class="info-value">
                                <?= htmlspecialchars($bill['operator_name'] ?? '—') ?>
                                <?php if (!empty($bill['operator_phone'])): ?>
                                    <small class="text-muted">(<?= $bill['operator_phone'] ?>)</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6 text-muted">
                            <div class="info-label text-uppercase text-muted small fw-bold">Bill Date</div>
                            <div class="info-value"><?= date('d M Y', strtotime($bill['bill_date'])) ?></div>
                        </div>
                        <div class="col-md-6 text-muted">
                            <div class="info-label text-uppercase text-muted small fw-bold">Trade</div>
                            <div class="info-value"><?= htmlspecialchars($bill['operator_trade'] ?? '—') ?></div>
                        </div>
                        <div class="col-md-12">
                            <div class="info-label text-uppercase text-muted small fw-bold">Work Description</div>
                            <div class="info-value text-muted"><?= nl2br(htmlspecialchars($bill['work_description'] ?? '—')) ?></div>
                        </div>
                        <?php if (!empty($bill['attachment_path'])): ?>
                        <div class="col-md-12 text-muted">
                            <div class="info-label text-uppercase text-muted small fw-bold">Attachment</div>
                            <a href="<?= $base_url ?>/<?= $bill['attachment_path'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-paperclip me-1"></i> View Attachment
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($bill['verification_note'])): ?>
                        <div class="col-md-12 text-muted">
                            <div class="info-label text-uppercase text-muted small fw-bold">Verification Note</div>
                            <div class="info-value text-info"><?= htmlspecialchars($bill['verification_note']) ?></div>
                        </div>
                        <?php endif; ?>

                        <!-- Financial Summary Row -->
                        <div class="col-md-12">
                            <div class="row g-2 mt-1">
                                <div class="col-md-3">
                                    <div class="border rounded p-3 text-center">
                                        <small class="text-muted d-block mb-1">Bill Amount</small>
                                        <h5 class="text-primary mb-0 fw-bold">৳ <?= number_format($bill['bill_amount'], 2) ?></h5>
                                    </div>
                                </div>
                                <?php if (!empty($bill['approved_amount'])): ?>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 text-center border-success">
                                        <small class="text-muted d-block mb-1">Approved Amount</small>
                                        <h5 class="text-success mb-0 fw-bold">৳ <?= number_format($bill['approved_amount'], 2) ?></h5>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if ($isApproved || $isFullyPaid): ?>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 text-center">
                                        <small class="text-muted d-block mb-1">Total Paid</small>
                                        <h5 class="text-success mb-0 fw-bold">৳ <?= number_format($totalPaid, 2) ?></h5>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 text-center">
                                        <small class="text-muted d-block mb-1">Due Amount</small>
                                        <?php if ($dueAmount > 0): ?>
                                            <h5 class="text-danger mb-0 fw-bold">৳ <?= number_format($dueAmount, 2) ?></h5>
                                        <?php else: ?>
                                            <h5 class="text-success mb-0 fw-bold">৳ 0.00</h5>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6 text-muted">
                            <div class="info-label text-uppercase text-muted small fw-bold">Submitted By</div>
                            <div class="info-value">
                                <?= htmlspecialchars($bill['created_by_name'] ?? '—') ?>
                                <?php if (!empty($bill['created_at'])): ?>
                                    <small class="text-muted">(<?= date('d M Y h:i A', strtotime($bill['created_at'])) ?>)</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($bill['verified_by_name'])): ?>
                        <div class="col-md-6 text-muted">
                            <div class="info-label text-uppercase text-muted small fw-bold">Verified By</div>
                            <div class="info-value"><strong><?= htmlspecialchars($bill['verified_by_name']) ?></strong>
                                <?php if (!empty($bill['verified_at'])): ?>
                                    <small class="text-muted">(<?= date('d M Y h:i A', strtotime($bill['verified_at'])) ?>)</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($bill['approved_by_name'])): ?>
                        <div class="col-md-6 text-muted mt-2">
                            <div class="info-label text-uppercase text-muted small fw-bold">Approved By</div>
                            <div class="info-value"><strong><?= htmlspecialchars($bill['approved_by_name']) ?></strong></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($bill['qc_approved_by_id'])): ?>
                        <div class="col-md-6 text-muted mt-2">
                            <div class="info-label text-uppercase text-muted small fw-bold">QC Approved By</div>
                            <div class="info-value"><strong>ID: <?= htmlspecialchars($bill['qc_approved_by_id']) ?></strong>
                                <?php if (!empty($bill['qc_approved_at'])): ?>
                                    <small class="text-muted">(<?= date('d M Y h:i A', strtotime($bill['qc_approved_at'])) ?>)</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($bill['qc_note'])): ?>
                        <div class="col-md-12 text-muted mt-2">
                            <div class="info-label text-uppercase text-muted small fw-bold">QC Note</div>
                            <div class="info-value text-muted fst-italic">"<?= nl2br(htmlspecialchars($bill['qc_note'])) ?>"</div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($bill['management_note'])): ?>
                        <div class="col-md-12 text-muted mt-2">
                            <div class="info-label text-uppercase text-muted small fw-bold">Management Note</div>
                            <div class="info-value text-muted fst-italic">"<?= nl2br(htmlspecialchars($bill['management_note'])) ?>"</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                
            </div>

            <?php if (!empty($bill['target_id'])): ?>
            <!-- Linked Site Target Details -->
            <div class="card custom-card mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-geo-alt me-2 text-success"></i>Linked Site Target & Week Details
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3 text-muted">
                            <div class="info-label text-uppercase text-muted small fw-bold">Target Phase</div>
                            <div class="info-value text-dark fw-semibold"><?= htmlspecialchars($bill['target_phase_name'] ?? '—') ?></div>
                        </div>
                        <div class="col-md-6 mb-3 text-muted">
                            <div class="info-label text-uppercase text-muted small fw-bold">Target Week</div>
                            <div class="info-value text-dark fw-semibold">
                                Week <?= htmlspecialchars($bill['target_week_number'] ?? '—') ?>
                                <?php if (!empty($bill['target_week_start_date']) && !empty($bill['target_week_end_date'])): ?>
                                    <span class="text-muted small fw-normal">(<?= date('d M Y', strtotime($bill['target_week_start_date'])) ?> - <?= date('d M Y', strtotime($bill['target_week_end_date'])) ?>)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6 text-muted">
                            <div class="info-label text-uppercase text-muted small fw-bold">Target Month & Year</div>
                            <div class="info-value text-dark fw-semibold">
                                <?php
                                if (!empty($bill['target_month_number'])) {
                                    $monthName = DateTime::createFromFormat('!m', $bill['target_month_number'])->format('F');
                                    echo htmlspecialchars($monthName) . ' ' . htmlspecialchars($bill['target_year']);
                                } else {
                                    echo '—';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="col-md-6 text-muted">
                            <div class="info-label text-uppercase text-muted small fw-bold">Overall Weekly Progress</div>
                            <div class="info-value text-dark fw-semibold">
                                <span class="fw-bold text-success"><?= number_format($bill['target_overall_progress_pct'] ?? 0, 2) ?>%</span> Achieved
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($target_items)): ?>
                    <div class="mt-4">
                        <h6 class="fs-14 fw-semibold mb-2 text-dark"><i class="bi bi-list-task me-1 text-success"></i>Weekly Progress Items Achieved</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light text-muted small">
                                    <tr>
                                        <th>Work Item</th>
                                        <th class="text-end">Planned Qty</th>
                                        <th class="text-end">Achieved Qty</th>
                                        <th class="text-center">Progress %</th>
                                        <th>Assigned Contractor</th>
                                        <th>Status / Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($target_items as $item): ?>
                                    <?php 
                                    $isCurrentContractor = ($item['contractor_id'] == $bill['operator_id']);
                                    $rowClass = $isCurrentContractor ? 'table-success bg-opacity-10 fw-semibold' : '';
                                    ?>
                                    <tr class="<?= $rowClass ?>">
                                        <td>
                                            <?= htmlspecialchars($item['work_item_name']) ?>
                                            <?php if ($isCurrentContractor): ?>
                                                <span class="badge bg-success-transparent ms-1 small">Current Contractor</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end text-nowrap"><?= number_format($item['planned_qty'] ?? 0, 2) ?> <?= htmlspecialchars($item['unit'] ?? '') ?></td>
                                        <td class="text-end text-nowrap"><?= number_format($item['achieved_qty'] ?? 0, 2) ?> <?= htmlspecialchars($item['unit'] ?? '') ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= $item['percent_complete'] >= 100 ? 'success' : 'primary' ?>-transparent">
                                                <?= number_format($item['percent_complete'] ?? 0, 2) ?>%
                                            </span>
                                        </td>
                                        <td class="text-muted"><?= htmlspecialchars($item['contractor_name'] ?? '—') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $item['status'] === 'Completed' ? 'success' : ($item['status'] === 'In Progress' ? 'info' : 'warning') ?>-transparent small mb-1 d-block w-fit">
                                                <?= htmlspecialchars($item['status']) ?>
                                            </span>
                                            <?php if (!empty($item['remarks_action'])): ?>
                                                <small class="text-muted d-block text-wrap"><em><?= htmlspecialchars($item['remarks_action']) ?></em></small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($monthly_target_items)): ?>
                    <div class="mt-4 border-top pt-3">
                        <h6 class="fs-14 fw-semibold mb-2 text-dark"><i class="bi bi-calendar-check me-1 text-primary"></i>Monthly Target & Cumulative Progress</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light text-muted small">
                                    <tr>
                                        <th>Work Item</th>
                                        <th>Unit</th>
                                        <th class="text-end">Monthly Target Qty</th>
                                        <th class="text-end">Monthly Achieved Qty</th>
                                        <th class="text-center">Overall Progress %</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($monthly_target_items as $mItem): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($mItem['work_item_name']) ?></td>
                                        <td><?= htmlspecialchars($mItem['unit'] ?? '') ?></td>
                                        <td class="text-end text-nowrap"><?= number_format($mItem['monthly_target_qty'] ?? 0, 2) ?></td>
                                        <td class="text-end text-nowrap"><?= number_format($mItem['actual_qty'] ?? 0, 2) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= $mItem['actual_progress_pct'] >= 100 ? 'success' : 'primary' ?>-transparent">
                                                <?= number_format($mItem['actual_progress_pct'] ?? 0, 2) ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $mItem['status'] === 'Completed' ? 'success' : ($mItem['status'] === 'In Progress' ? 'info' : 'warning') ?>-transparent small">
                                                <?= htmlspecialchars($mItem['status'] ?? 'Draft') ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Payment History -->
            <?php if (!empty($payments)): ?>
            <div class="card custom-card mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-clock-history me-2"></i>Payment History</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Amount (৳)</th>
                                    <th>Type</th>
                                    <th>Method</th>
                                    <th>Reference</th>
                                    <th>Processed By</th>
                                    <th>Proof</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $idx => $pay): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td><?= date('d M Y', strtotime($pay['transaction_date'])) ?></td>
                                    <td class="text-end fw-semibold">৳ <?= number_format($pay['amount_paid'], 2) ?></td>
                                    <td><span class="text-muted badge bg-<?= $pay['payment_type'] === 'Full' ? 'success' : 'info' ?>"><?= $pay['payment_type'] ?></span></td>
                                    <td><?= str_replace('BankTransfer', 'Bank Transfer', $pay['payment_method']) ?></td>
                                    <td><?= htmlspecialchars($pay['reference_number'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($pay['processed_by_name'] ?? '—') ?></td>
                                    <td>
                                        <?php
                                        $proofs = json_decode($pay['disbursement_proof'] ?? '[]', true);
                                        if (!empty($proofs)):
                                            foreach ($proofs as $pi => $proof): ?>
                                                <a href="<?= $base_url ?>/<?= $proof ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                    <i class="bi bi-file-earmark"></i> Doc <?= $pi + 1 ?>
                                                </a>
                                            <?php endforeach;
                                        else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar (Right) -->
        <div class="col-lg-4">
            <!-- Status Info Card -->
            <div class="card custom-card mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-info-circle me-2"></i>Status Info</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Current Status</small>
                        <span class="badge bg-<?= $statusInfo['color'] ?> fs-6"><?= $statusInfo['label'] ?></span>
                    </div>
                    <?php if (in_array($status, ['submitted', 'verified'])): ?>
                        <div class="mb-3">
                            <small class="text-muted d-block fw-bold text-warning">Pending Approval From</small>
                            <span class="d-block small"><i class="bi bi-shield-check me-1 text-warning"></i> QC & Internal Audit</span>
                            <span class="d-block small"><i class="bi bi-shield-lock me-1 text-success"></i> Management (Admin / Operation Head / PM Head)</span>
                        </div>
                    <?php elseif ($status === 'qc_approved'): ?>
                        <div class="mb-3">
                            <small class="text-muted d-block fw-bold text-warning">Pending Approval From</small>
                            <span class="d-block small"><i class="bi bi-shield-lock me-1 text-success"></i> Management (Admin / Operation Head / PM Head)</span>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <small class="text-muted d-block text-muted">Submitted At</small>
                        <strong class="text-muted"><?= date('d M Y h:i A', strtotime($bill['created_at'])) ?></strong>
                    </div>
                    <?php if (!empty($bill['verified_by_name'])): ?>
                    <div class="mb-3 text-muted">
                        <small class="text-muted d-block">Verified By</small>
                        <strong><?= htmlspecialchars($bill['verified_by_name']) ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($bill['approved_by_name'])): ?>
                    <div class="mb-3 text-muted">
                        <small class="text-muted d-block">Approved By</small>
                        <strong><?= htmlspecialchars($bill['approved_by_name']) ?></strong>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3 text-muted">
                        <small class="text-muted d-block">Last Updated</small>
                        <strong><?= !empty($bill['updated_at']) ? date('d M Y h:i A', strtotime($bill['updated_at'])) : '—' ?></strong>
                    </div>
                </div>
            </div>


            <!-- QC Approval Actions -->
            <?php if ($canApproveQCReq && ($status === 'verified' || $status === 'submitted')): ?>
            <div class="card custom-card border-warning mb-3">
                <div class="card-header bg-warning bg-opacity-10">
                    <h6 class="card-title mb-0"><i class="bi bi-shield-check me-2"></i>QC Approval</h6>
                </div>
                <div class="card-body">
                    <form action="<?= $base_url ?>/con-contractor-bill-approve/<?= $bill['id'] ?>" method="POST" class="mb-3">
                        <input type="hidden" name="status" value="qc_approved">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Approved Amount (৳)</label>
                            <input type="number" name="approved_amount" class="form-control"
                                   value="<?= number_format($bill['bill_amount'], 2, '.', '') ?>"
                                   step="0.01" min="0.01" max="<?= $bill['bill_amount'] ?>" required>
                            <small class="text-muted">Bill amount: ৳ <?= number_format($bill['bill_amount'], 2) ?></small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">QC Note (Optional)</label>
                            <textarea name="approval_note" class="form-control form-control-sm" rows="2" placeholder="Add QC remarks..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-warning w-100 fw-bold"
                                onclick="return confirm('QC Approve this bill?');">
                            <i class="bi bi-check-circle me-1"></i> QC Approve
                        </button>
                    </form>
                    <form action="<?= $base_url ?>/con-contractor-bill-reject/<?= $bill['id'] ?>" method="POST">
                        <button type="submit" class="btn btn-outline-danger w-100 btn-sm"
                                onclick="return confirm('Reject this bill?');">
                            <i class="bi bi-x-circle me-1"></i> Reject
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Management Approval Actions -->
            <?php if ($canApproveReq && ($isQCApproved || $status === 'submitted' || $status === 'verified')): ?>
            <div class="card custom-card border-success mb-3">
                <div class="card-header bg-success bg-opacity-10">
                    <h6 class="card-title mb-0"><i class="bi bi-shield-lock me-2"></i>Management Approval (Operation / PM Head / Admin)</h6>
                </div>
                <div class="card-body">
                    <form action="<?= $base_url ?>/con-contractor-bill-approve/<?= $bill['id'] ?>" method="POST" class="mb-3">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Approved Amount (৳)</label>
                            <input type="number" name="approved_amount" class="form-control"
                                   value="<?= number_format($bill['approved_amount'] ?? $bill['bill_amount'], 2, '.', '') ?>"
                                   step="0.01" min="0.01" max="<?= $bill['bill_amount'] ?>" required>
                            <small class="text-muted">Bill amount: ৳ <?= number_format($bill['bill_amount'], 2) ?></small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Management Note (Optional)</label>
                            <textarea name="management_note" class="form-control form-control-sm" rows="2" placeholder="Add management remarks..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100 fw-bold"
                                onclick="return confirm('Approve this bill?');">
                            <i class="bi bi-check-circle me-1"></i> Approve Bill
                        </button>
                    </form>
                    <form action="<?= $base_url ?>/con-contractor-bill-reject/<?= $bill['id'] ?>" method="POST">
                        <button type="submit" class="btn btn-outline-danger w-100 btn-sm"
                                onclick="return confirm('Reject this bill?');">
                            <i class="bi bi-x-circle me-1"></i> Reject
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Status Messages -->
            <?php if ($isApproved && !$showPaymentForm): ?>
                <div class="alert alert-success text-center">
                    <i class="bi bi-check-circle-fill fs-4 d-block mb-2"></i>
                    Approved — awaiting payment processing.
                </div>
            <?php elseif ($isFullyPaid): ?>
                <div class="alert alert-secondary text-center">
                    <i class="bi bi-cash-coin fs-4 d-block mb-2"></i>
                    All payments have been fully processed.
                </div>
            <?php elseif ($isRejected): ?>
                <div class="alert alert-danger text-center">
                    <i class="bi bi-x-circle-fill fs-4 d-block mb-2"></i>
                    This bill has been rejected.
                </div>
            <?php endif; ?>

            <!-- Add Payment Button -->
            <?php if ($showPaymentForm): ?>
            <div class="card custom-card border-success mb-3">
                <div class="card-header bg-success bg-opacity-10">
                    <h6 class="card-title mb-0"><i class="bi bi-wallet2 me-2"></i>Process Payment</h6>
                </div>
                <div class="card-body text-center">
                    <p class="text-muted mb-2">Remaining Due: <strong class="text-danger fs-5">৳ <?= number_format($dueAmount, 2) ?></strong></p>
                    <button class="btn btn-success w-100 fw-bold" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                        <i class="bi bi-plus-circle me-1"></i>
                        <?= empty($payments) ? 'Add First Payment' : 'Add Another Payment' ?>
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================
     SETTLEMENT & ACCOUNTABILITY SECTION
     ============================================================ -->
<div class="container-fluid mt-3">
    <div class="card custom-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="card-title mb-0">
                <i class="bi bi-patch-check me-2 text-success"></i>
                Contractor Settlement &amp; Labour Accountability
            </h6>
            <div class="d-flex gap-2 align-items-center">
                <?php if ($totalPaid > 0 || !empty($payments)): ?>
                    <a href="<?= $base_url ?>/expense-adjustments/create?parent_type=contractor&cashout_request_id=<?= $bill['id'] ?>&project_id=<?= $bill['project_id'] ?>" class="btn btn-sm btn-outline-warning fw-bold">
                        <i class="bi bi-sliders me-1"></i> Accounts Adjustment (Return/Extra)
                    </a>
                <?php endif; ?>
                <?php if ($canSubmitSettlement): ?>
                <button class="btn btn-sm btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#settlementModal">
                    <i class="bi bi-file-earmark-plus me-1"></i> Submit New Settlement
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">

            <!-- Summary strip -->
            <div class="row mb-3 g-2">
                <div class="col-md-3">
                    <div class="p-3 rounded bg-success bg-opacity-10 text-center">
                        <small class="text-light d-block">Total Paid by Accounts</small>
                        <strong class="text-light fs-5">৳ <?= number_format($totalPaid, 2) ?></strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 rounded bg-primary bg-opacity-10 text-center">
                        <small class="text-light d-block">Paid to Contractor</small>
                        <strong class="text-light fs-5">৳ <?= number_format($totalPaidToContractor, 2) ?></strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <?php $unsettled = max(0, $totalPaid - $totalPaidToContractor); ?>
                    <div class="p-3 rounded <?= $unsettled > 0 ? 'bg-warning bg-opacity-15' : 'bg-secondary bg-opacity-10' ?> text-center">
                        <small class="text-light d-block">Unsettled Balance</small>
                        <strong class="<?= $unsettled > 0 ? 'text-light' : 'text-muted' ?> fs-5">৳ <?= number_format($unsettled, 2) ?></strong>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 rounded bg-info bg-opacity-10 text-center">
                        <small class="text-light d-block">Settlements Submitted</small>
                        <strong class="text-light fs-5"><?= count($settlements) ?></strong>
                    </div>
                </div>
            </div>

            <?php if (empty($settlements)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-clipboard-x fs-2 d-block mb-2 text-secondary"></i>
                    <?= $billIsPaid ? 'No settlements submitted yet. Click <strong>Submit New Settlement</strong> to start.' : 'Settlement can be submitted once accounts has processed payment.' ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Submitted By</th>
                                <th class="text-end">Received (৳)</th>
                                <th class="text-end">Paid to Contractor (৳)</th>
                                <th class="text-end">Balance (৳)</th>
                                <th class="text-center">Labour</th>
                                <th>Method</th>
                                <th class="text-center">Proof</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($settlements as $si => $sett):
                                $settStatusColors = [
                                    'submitted'         => 'warning text-dark',
                                    'accounts_received' => 'info text-dark',
                                    'verified'          => 'success',
                                ];
                                $settStatusLabels = [
                                    'submitted'         => 'Submitted',
                                    'accounts_received' => 'Balance Returned',
                                    'verified'          => 'Verified',
                                ];
                                $settColor = $settStatusColors[$sett['status']] ?? 'secondary';
                                $settLabel = $settStatusLabels[$sett['status']] ?? ucfirst($sett['status']);
                                $proofList = !empty($sett['proof_images']) ? json_decode($sett['proof_images'], true) : [];
                            ?>
                            <tr>
                                <td><?= $si + 1 ?></td>
                                <td><?= date('d M Y', strtotime($sett['paid_date'])) ?></td>
                                <td><?= htmlspecialchars($sett['submitted_by_name'] ?? '—') ?></td>
                                <td class="text-end">৳ <?= number_format($sett['received_amount'], 2) ?></td>
                                <td class="text-end fw-semibold text-success">৳ <?= number_format($sett['paid_amount'], 2) ?></td>
                                <td class="text-end <?= $sett['balance_amount'] > 0 ? 'text-warning fw-semibold' : 'text-muted' ?>">
                                    ৳ <?= number_format($sett['balance_amount'], 2) ?>
                                </td>
                                <td class="text-center"><?= (int)$sett['labour_count'] ?> pax</td>
                                <td><?= htmlspecialchars($sett['payment_method'] ?? '—') ?></td>
                                <td class="text-center">
                                    <?php if (!empty($proofList)): ?>
                                        <?php foreach ($proofList as $pi => $proof): ?>
                                            <a href="<?= $base_url ?>/<?= htmlspecialchars($proof) ?>" target="_blank" class="btn btn-xs btn-outline-secondary me-1">
                                                <i class="bi bi-image"></i> <?= $pi + 1 ?>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $settColor ?>"><?= $settLabel ?></span>
                                    <?php if ($sett['balance_verified']): ?>
                                        <br><small class="text-success"><i class="bi bi-check-circle"></i> Accts Verified</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <!-- Mark balance returned (submitter) -->
                                    <?php if ($sett['balance_amount'] > 0 && !$sett['balance_returned'] && $sett['submitted_by'] == $currentUserId): ?>
                                        <form action="<?= $base_url ?>/con-contractor-bill-balance-return/<?= $sett['id'] ?>" method="POST" class="d-inline">
                                            <button type="submit" class="btn btn-xs btn-outline-warning"
                                                onclick="return confirm('Mark ৳<?= number_format($sett['balance_amount'], 2) ?> as returned to accounts?')">
                                                <i class="bi bi-arrow-return-left"></i> Return Balance
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <!-- Accounts Verify -->
                                    <?php if (!$sett['balance_verified'] && $canPayReq): ?>
                                        <button class="btn btn-xs btn-outline-success mt-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#verifySettModal<?= $sett['id'] ?>">
                                            <i class="bi bi-check2-all"></i> Verify
                                        </button>
                                    <?php elseif ($sett['balance_verified']): ?>
                                        <small class="text-muted d-block"><?= date('d M Y', strtotime($sett['balance_verified_at'])) ?></small>
                                    <?php endif; ?>
                                    <!-- Note -->
                                    <?php if (!empty($sett['work_note'])): ?>
                                        <span class="d-block small text-muted fst-italic mt-1" title="<?= htmlspecialchars($sett['work_note']) ?>">
                                            <i class="bi bi-chat-left-text"></i> Note
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($expenseAdjustments)): ?>
                <div class="mt-4 pt-3 border-top">
                    <h6 class="fw-bold text-warning mb-2">
                        <i class="bi bi-sliders me-1"></i> Central Accounts Adjustments (Return Unused / Extra Money)
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Requester</th>
                                    <th>Type</th>
                                    <th class="text-end">Amount (৳)</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expenseAdjustments as $adj): 
                                    $typeBadge = ($adj['adjustment_type'] === 'return') ? 'info' : 'warning text-dark';
                                    $typeLabel = ($adj['adjustment_type'] === 'return') ? 'Return Unused' : 'Extra Money';
                                    $statusClass = match ($adj['status']) {
                                        'pending' => 'warning text-dark',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'partially_paid' => 'info text-dark',
                                        'fully_paid' => 'success',
                                        default => 'secondary'
                                    };
                                ?>
                                <tr>
                                    <td class="fw-bold">REQ-<?= $adj['id'] ?></td>
                                    <td><?= date('d M Y', strtotime($adj['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($adj['requester_name']) ?></td>
                                    <td><span class="badge bg-<?= $typeBadge ?>"><?= $typeLabel ?></span></td>
                                    <td class="text-end fw-bold">৳ <?= number_format($adj['amount'], 2) ?></td>
                                    <td class="text-center"><span class="badge bg-<?= $statusClass ?>"><?= ucfirst(str_replace('_', ' ', $adj['status'])) ?></span></td>
                                    <td class="text-center">
                                        <a href="<?= $base_url ?>/expense-adjustments/show?id=<?= $adj['id'] ?>" class="btn btn-xs btn-outline-primary" style="font-size: 0.75rem;">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================
     SETTLEMENT SUBMIT MODAL
     ============================================================ -->
<?php if ($canSubmitSettlement): ?>
<div class="modal fade" id="settlementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?= $base_url ?>/con-contractor-bill-settlement/<?= $bill['id'] ?>" method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-success bg-opacity-10">
                    <h5 class="modal-title"><i class="bi bi-patch-check me-2"></i>Submit Contractor Settlement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Record how much was paid to the contractor, how many labourers worked, and upload payment proof.
                        If you received more than you paid, the balance will be calculated automatically.
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Amount Received from Accounts (৳) *</label>
                            <input type="number" name="received_amount" class="form-control" step="0.01" min="0"
                                   value="<?= number_format($totalPaid, 2, '.', '') ?>"
                                   placeholder="Total received from accounts">
                            <small class="text-muted">Total paid by accounts: ৳<?= number_format($totalPaid, 2) ?></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Amount Paid to Contractor (৳) *</label>
                            <input type="number" name="paid_amount" id="sett_paid_amount" class="form-control" step="0.01" min="0.01" required
                                   placeholder="Actual amount paid to contractor">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Date *</label>
                            <input type="date" name="paid_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Method *</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="Cash">Cash</option>
                                <option value="Cheque">Cheque</option>
                                <option value="BankTransfer">Bank Transfer</option>
                                <option value="MobileWallet">Mobile Wallet (bKash/Nagad)</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Reference / Voucher No.</label>
                            <input type="text" name="reference_no" class="form-control" placeholder="Cheque / Transaction No.">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Number of Labourers</label>
                            <input type="number" name="labour_count" class="form-control" min="0" value="0"
                                   placeholder="How many labourers worked?">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Proof of Payment (Images/PDF) *</label>
                            <input type="file" name="proof_images[]" class="form-control" multiple required
                                   accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                            <small class="text-muted">JPG, PNG, PDF — Max 5MB each. You can select multiple files.</small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Work Notes</label>
                            <textarea name="work_note" class="form-control" rows="2"
                                placeholder="Describe the work done, any issues, or observations..."></textarea>
                        </div>
                        <!-- Live balance preview -->
                        <div class="col-md-12">
                            <div id="sett_balance_preview" class="alert alert-warning py-2 d-none">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Balance remaining: <strong id="sett_balance_amt">৳ 0</strong> — you will be asked to return this to accounts.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold">
                        <i class="bi bi-check-circle me-1"></i> Submit Settlement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Verify Settlement Modals -->
<?php foreach ($settlements as $sett): if ($sett['balance_verified'] || !$canPayReq) continue; ?>
<div class="modal fade" id="verifySettModal<?= $sett['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= $base_url ?>/con-contractor-bill-settlement-verify/<?= $sett['id'] ?>" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-check2-all me-2"></i>Verify Settlement #<?= $sett['id'] ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm table-bordered">
                        <tr><th>Received from Accounts</th><td>৳ <?= number_format($sett['received_amount'], 2) ?></td></tr>
                        <tr><th>Paid to Contractor</th><td>৳ <?= number_format($sett['paid_amount'], 2) ?></td></tr>
                        <tr><th>Balance Remaining</th><td class="fw-bold <?= $sett['balance_amount'] > 0 ? 'text-warning' : 'text-success' ?>">৳ <?= number_format($sett['balance_amount'], 2) ?></td></tr>
                        <tr><th>Balance Returned?</th><td><?= $sett['balance_returned'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-warning text-dark">Not yet</span>' ?></td></tr>
                        <tr><th>Labour Count</th><td><?= (int)$sett['labour_count'] ?> pax</td></tr>
                        <tr><th>Submitted By</th><td><?= htmlspecialchars($sett['submitted_by_name'] ?? '—') ?></td></tr>
                    </table>
                    <label class="form-label fw-semibold mt-2">Accounts Verification Note</label>
                    <textarea name="accounts_note" class="form-control" rows="2" placeholder="Optional note from accounts..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold">
                        <i class="bi bi-check-circle me-1"></i> Confirm Verified
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Add Payment Modal -->
<?php if ($showPaymentForm): ?>
<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?= $base_url ?>/con-contractor-bill-payment/<?= $bill['id'] ?>" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-wallet2 me-2"></i>Add Payment — Bill #<?= $bill['id'] ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row ">
                        <div class="col-md-6 text-muted">
                            <label class="form-label fw-semibold">Payment Amount (৳) *</label>
                            <input type="number" name="amount" class="form-control" step="0.01" min="0.01"
                                   max="<?= number_format($dueAmount, 2, '.', '') ?>" required
                                   placeholder="Max: <?= number_format($dueAmount, 2) ?>">
                        </div>
                        <div class="col-md-6 text-muted">
                            <label class="form-label fw-semibold">Payment Date *</label>
                            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6 text-muted">
                            <label class="form-label fw-semibold">Payment Method *</label>
                            <select name="payment_method" id="billPaymentMethod" class="form-select" required>
                                <option value="Cash">Cash</option>
                                <option value="Cheque">Cheque</option>
                                <option value="BankTransfer">Bank Transfer</option>
                                <option value="MobileWallet">Mobile Wallet</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 text-muted">
                            <label class="form-label fw-semibold">Reference Number</label>
                            <input type="text" name="reference_no" class="form-control" placeholder="Cheque/Transaction No.">
                        </div>
                        <div class="col-md-6 text-muted bill-bank-field" style="display: none;">
                            <label class="form-label fw-semibold">Select Bank *</label>
                            <select name="bank_id" class="form-select">
                                <option value="">-- Select Bank --</option>
                                <?php if (!empty($banks)): ?>
                                    <?php foreach ($banks as $bank): ?>
                                        <option value="<?= $bank['id'] ?>"><?= htmlspecialchars($bank['bank_name']) ?> — <?= htmlspecialchars($bank['branch_name']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Disbursement Proof (Required) *</label>
                            <input type="file" name="disbursement_proof[]" class="form-control" multiple required
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <small class="text-muted">PDF, DOC, JPG, PNG — Max 5MB each</small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Optional payment notes..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold">
                        <i class="bi bi-check-circle me-1"></i> Submit Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var methodSelect = document.getElementById('billPaymentMethod');
    var bankFields = document.querySelectorAll('.bill-bank-field');

    function toggleBankField() {
        var show = methodSelect && methodSelect.value === 'BankTransfer';
        bankFields.forEach(function(el) {
            el.style.display = show ? '' : 'none';
            var sel = el.querySelector('select');
            if (sel) sel.required = show;
        });
    }

    if (methodSelect) {
        methodSelect.addEventListener('change', toggleBankField);
        var modal = document.getElementById('addPaymentModal');
        if (modal) {
            modal.addEventListener('shown.bs.modal', toggleBankField);
        }
        toggleBankField();
    }
});
</script>

<script>
// Settlement modal: live balance calculation preview
document.addEventListener('DOMContentLoaded', function () {
    var paidInput    = document.getElementById('sett_paid_amount');
    var preview      = document.getElementById('sett_balance_preview');
    var balanceLabel = document.getElementById('sett_balance_amt');
    var receivedInput = document.querySelector('#settlementModal input[name="received_amount"]');

    function updateBalance() {
        if (!paidInput || !preview || !balanceLabel) return;
        var received = parseFloat(receivedInput ? receivedInput.value : 0) || 0;
        var paid     = parseFloat(paidInput.value) || 0;
        var balance  = Math.max(0, received - paid);
        if (balance > 0) {
            balanceLabel.textContent = '৳ ' + balance.toFixed(2);
            preview.classList.remove('d-none');
        } else {
            preview.classList.add('d-none');
        }
    }

    if (paidInput) paidInput.addEventListener('input', updateBalance);
    if (receivedInput) receivedInput.addEventListener('input', updateBalance);
});
</script>
