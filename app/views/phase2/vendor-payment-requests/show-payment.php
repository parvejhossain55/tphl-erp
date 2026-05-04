<?php
$base_url = $GLOBALS['base_url'];
$page_title = $page_title ?? 'Payment Request Details';

$statusMap = [
    'Draft' => ['label' => 'Draft', 'color' => 'warning'],
    'Submitted' => ['label' => 'Submitted', 'color' => 'info'],
    'Approved' => ['label' => 'Approved', 'color' => 'success'],
    'Rejected' => ['label' => 'Rejected', 'color' => 'danger'],
    'Paid' => ['label' => 'Paid', 'color' => 'secondary'],
    'Partially_Paid' => ['label' => 'Partially Paid', 'color' => 'info'],
    'Fully_Paid' => ['label' => 'Fully Paid', 'color' => 'secondary'],
];
$status = $request['status'] ?? 'Draft';
$statusInfo = $statusMap[$status] ?? ['label' => str_replace('_', ' ', $status), 'color' => 'secondary'];

$canApproveReq = $canApprove ?? false;
$canPayReq = $canProcessPayment ?? false;
$userRole = $userRole ?? null;
$payments = $payments ?? [];
$totalPaid = $total_paid ?? 0;
$dueAmount = $due_amount ?? 0;
$payableAmount = $payable_amount ?? 0;

$isSubmitted = ($status === 'Submitted');
$isApproved = in_array($status, ['Approved', 'Partially_Paid']);
$isRejected = ($status === 'Rejected');
$isFullyPaid = in_array($status, ['Fully_Paid', 'Paid']);
$canReject = $canApproveReq && !$isRejected && !$isFullyPaid;
$showPaymentForm = $canPayReq && $isApproved && $dueAmount > 0;

// Payment type badge
$typeBadge = match ($request['payment_type'] ?? 'Final') {
    'Advance' => ['class' => 'bg-primary', 'label' => 'Advance'],
    'Partial' => ['class' => 'bg-warning text-dark', 'label' => 'Partial'],
    default => ['class' => 'bg-success', 'label' => 'Final'],
};

$penaltyAmt = (float) ($request['penalty_amount'] ?? 0);
$netPayable = (float) ($request['net_payable'] ?? 0);
$invoiceAmt = (float) ($request['invoice_amount'] ?? 0);
$advancePaid = (float) ($advance_paid ?? 0);
?>

<!-- Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div class="my-auto">
        <h3 class="page-title fw-semibold fs-18 mb-0">Vendor Payment Request #<?= $request['id'] ?></h4>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-vendor-payment-requests">Payment
                            Requests</a></li>
                    <li class="breadcrumb-item active">VPR-<?= $request['id'] ?></li>
                </ol>
            </nav>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <span class="badge bg-<?= $statusInfo['color'] ?> fs-6 px-3 py-2"><?= $statusInfo['label'] ?></span>
        <span class="badge <?= $typeBadge['class'] ?> fs-6 px-3 py-2"><?= $typeBadge['label'] ?> Payment</span>
        <a href="<?= $base_url ?>/con-vendor-payment-requests" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content (Left) -->
        <div class="col-lg-8">
            <!-- Request Information Card -->
            <div class="card custom-card mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-info-circle me-2"></i>Request Information</h6>
                </div>
                <div class="card-body">
                    <div class="row ">
                        <div class="col-md-6">
                            <div class="info-label text-uppercase text-muted small fw-bold">Project</div>
                            <div class=""><?= htmlspecialchars($request['project_name'] ?? '—') ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label text-uppercase text-muted small fw-bold">Vendor</div>
                            <div class=""><span
                                    class="badge bg-secondary"><?= htmlspecialchars($request['vendor_name'] ?? '—') ?></span>
                            </div>
                        </div>
                        <?php if (!empty($request['purchase_order_id']) && $request['purchase_order_id'] > 0): ?>
                            <div class="col-md-6">
                                <div class="info-label text-uppercase text-muted small fw-bold">Purchase Order</div>
                                <div class="">
                                    <a
                                        href="<?= $base_url ?>/con-purchase-order/<?= $request['purchase_order_id'] ?>">PO-<?= $request['purchase_order_id'] ?></a>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <div class="info-label text-uppercase text-muted small fw-bold">Invoice Number</div>
                            <div class=""><?= htmlspecialchars($request['invoice_number'] ?? '—') ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label text-uppercase text-muted small fw-bold">Delivery Verified</div>
                            <div class="">
                                <?php if ($request['delivery_verified']): ?>
                                    <span class="badge bg-success">Yes</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Not Yet</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label text-uppercase text-muted small fw-bold">QC Verified</div>
                            <div class="">
                                <?php if ($request['qc_verified']): ?>
                                    <span class="badge bg-success">Yes</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Not Yet</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Financial Summary Row -->
                        <div class="col-md-12">
                            <div class="row g-2 mt-1">
                                <div class="col-md-3">
                                    <div class="border rounded p-3 text-center">
                                        <small class="text-muted d-block mb-1">Invoice Amount</small>
                                        <h5 class="text-primary mb-0 fw-bold">৳ <?= number_format($invoiceAmt, 2) ?>
                                        </h5>
                                    </div>
                                </div>
                                <?php if ($penaltyAmt > 0): ?>
                                    <div class="col-md-3">
                                        <div class="border rounded p-3 text-center">
                                            <small class="text-muted d-block mb-1">Penalty Deducted</small>
                                            <h5 class="text-danger mb-0 fw-bold">- ৳ <?= number_format($penaltyAmt, 2) ?>
                                            </h5>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 text-center">
                                        <small class="text-muted d-block mb-1">Net Payable</small>
                                        <h5 class="text-success mb-0 fw-bold">৳ <?= number_format($netPayable, 2) ?>
                                        </h5>
                                    </div>
                                </div>
                                <?php if (!empty($request['approved_amount'])): ?>
                                    <div class="col-md-3">
                                        <div class="border rounded p-3 text-center border-success">
                                            <small class="text-muted d-block mb-1">Approved Amount</small>
                                            <h5 class="text-success mb-0 fw-bold">৳
                                                <?= number_format($request['approved_amount'], 2) ?></h5>
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

                        <div class="col-md-6">
                            <div class="info-label text-uppercase text-muted small fw-bold">Created By</div>
                            <span class="text-muted">
                                <?= htmlspecialchars($request['created_by_name'] ?? '—') ?>
                            </span>
                            <div class="">

                                <?php if (!empty($request['created_at'])): ?>
                                    <small
                                        class="text-muted">(<?= date('d M Y h:i A', strtotime($request['created_at'])) ?>)</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($request['approved_by_name'])): ?>
                            <div class="col-md-6">
                                <div class="info-label text-uppercase text-muted small fw-bold">Approved By</div>
                                <div class=""><strong><?= htmlspecialchars($request['approved_by_name']) ?></strong></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($request['payment_attachment'])): ?>
                            <div class="col-md-12">
                                <div class="info-label text-uppercase text-muted small fw-bold mb-2">
                                    <i class="bi bi-paperclip me-1"></i>Payment Attachment
                                </div>
                                <?php
                                $attPath = $request['payment_attachment'];
                                $attExt = strtolower(pathinfo($attPath, PATHINFO_EXTENSION));
                                $attUrl = $base_url . '/' . $attPath;
                                $imgExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                                ?>
                                <?php if (in_array($attExt, $imgExts)): ?>
                                    <a href="<?= $attUrl ?>" target="_blank" title="View Attachment">
                                        <img src="<?= $attUrl ?>" alt="Payment Attachment"
                                            style="max-height: 120px; max-width: 100%; border-radius: 6px; border: 1px solid #dee2e6; object-fit: cover;">
                                    </a>
                                    <div class="mt-1">
                                        <a href="<?= $attUrl ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-box-arrow-up-right me-1"></i>Open Full View
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <a href="<?= $attUrl ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                        <?php if ($attExt === 'pdf'): ?>
                                            <i class="bi bi-file-earmark-pdf me-1 text-danger"></i>
                                        <?php elseif (in_array($attExt, ['doc', 'docx'])): ?>
                                            <i class="bi bi-file-earmark-word me-1 text-primary"></i>
                                        <?php else: ?>
                                            <i class="bi bi-file-earmark me-1"></i>
                                        <?php endif; ?>
                                        View Attachment (<?= strtoupper($attExt) ?>)
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Linked Penalties -->
            <?php if (!empty($penalties)): ?>
                <div class="card custom-card mb-3">
                    <div class="card-header">
                        <h6 class="card-title mb-0 text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Deducted
                            Penalties</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Penalty ID</th>
                                    <th>Date</th>
                                    <th>Reason</th>
                                    <th class="text-end">Amount (৳)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($penalties as $p): ?>
                                    <tr>
                                        <td>PEN-<?= $p['id'] ?></td>
                                        <td><?= !empty($p['penalty_date']) ? date('d M Y', strtotime($p['penalty_date'])) : '—' ?>
                                        </td>
                                        <td><?= htmlspecialchars($p['reason'] ?? '') ?></td>
                                        <td class="text-end text-danger"><?= number_format($p['penalty_amount'] ?? 0, 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
                                            <td><?php
                                            $displayDate = $pay['transaction_date_str'] ?? $pay['transaction_date'] ?? null;
                                            echo $displayDate ? date('d M Y', strtotime($displayDate)) : '—';
                                            ?></td>
                                            <td class="text-end fw-semibold">৳ <?= number_format($pay['amount_paid'], 2) ?></td>
                                            <td><span
                                                    class="badge bg-<?= $pay['payment_type'] === 'Full' ? 'success' : 'info' ?>"><?= $pay['payment_type'] ?></span>
                                            </td>
                                            <td><?= str_replace('BankTransfer', 'Bank Transfer', $pay['payment_method']) ?></td>
                                            <td><?= htmlspecialchars($pay['reference_number'] ?? '—') ?></td>
                                            <td><?= htmlspecialchars($pay['processed_by_name'] ?? '—') ?></td>
                                            <td>
                                                <?php
                                                $proofs = json_decode($pay['disbursement_proof'] ?? '[]', true);
                                                if (!empty($proofs)):
                                                    foreach ($proofs as $pi => $proof): ?>
                                                        <a href="<?= $base_url ?>/<?= $proof ?>" target="_blank"
                                                            class="btn btn-sm btn-outline-info">
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

            <!-- PO Payment History (other requests for same PO) -->
            <?php if (!empty($po_payments) && count($po_payments) > 1): ?>
                <div class="card custom-card mb-3">
                    <div class="card-header">
                        <h6 class="card-title mb-0"><i class="bi bi-list-check me-2"></i>PO Payment History</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>VPR</th>
                                    <th>Type</th>
                                    <th>Created</th>
                                    <th class="text-end">Invoice</th>
                                    <th class="text-end">Net Payable</th>
                                    <th class="text-end">Total Paid</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($po_payments as $pp): ?>
                                    <tr class="<?= $pp['id'] == $request['id'] ? 'table-primary fw-bold' : '' ?>">
                                        <td>VPR-<?= $pp['id'] ?></td>
                                        <td>
                                            <span
                                                class="badge <?= $pp['payment_type'] === 'Advance' ? 'bg-primary' : ($pp['payment_type'] === 'Partial' ? 'bg-warning text-dark' : 'bg-success') ?>">
                                                <?= $pp['payment_type'] ?>
                                            </span>
                                        </td>
                                        <td><?= !empty($pp['created_at']) ? date('d M Y', strtotime($pp['created_at'])) : '—' ?>
                                        </td>
                                        <td class="text-end">৳ <?= number_format($pp['invoice_amount'] ?? 0, 2) ?></td>
                                        <td class="text-end">৳ <?= number_format($pp['net_payable'] ?? 0, 2) ?></td>
                                        <td class="text-end fw-semibold text-success">৳
                                            <?= number_format($pp['total_paid'] ?? 0, 2) ?></td>
                                        <td>
                                            <?php
                                            $ppBadge = $statusMap[$pp['status']] ?? ['color' => 'secondary', 'label' => $pp['status']];
                                            ?>
                                            <span class="badge bg-<?= $ppBadge['color'] ?>"><?= $ppBadge['label'] ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
                    <div class="mb-3">
                        <small class="text-muted d-block">Created At</small>
                        <strong
                            class="text-muted"><?= date('d M Y h:i A', strtotime($request['created_at'])) ?></strong>
                    </div>
                    <?php if (!empty($request['approved_by_name'])): ?>
                        <div class="mb-3">
                            <small class="text-muted d-block">Approved By</small>
                            <strong><?= htmlspecialchars($request['approved_by_name']) ?></strong>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <small class="text-muted d-block">Last Updated</small>
                        <strong><?= !empty($request['updated_at']) ? date('d M Y h:i A', strtotime($request['updated_at'])) : '—' ?></strong>
                    </div>
                </div>
            </div>

            <!-- Approval Actions (Management only, Submitted status) -->
            <?php if ($canApproveReq && $isSubmitted): ?>
                <div class="card custom-card border-warning mb-3">
                    <div class="card-header bg-warning bg-opacity-10">
                        <h6 class="card-title mb-0"><i class="bi bi-shield-lock me-2"></i>Approval Action</h6>
                    </div>
                    <div class="card-body">
                        <form action="<?= $base_url ?>/con-vendor-payment-approve/<?= $request['id'] ?>" method="POST"
                            id="approveVprForm" class="mb-3">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Approved Amount (৳)</label>
                                <input type="number" name="approved_amount" id="approveAmtInput" class="form-control"
                                    value="<?= number_format($netPayable, 2, '.', '') ?>" step="0.01" min="0.01" required>
                                <small class="text-muted">Net payable: ৳ <?= number_format($netPayable, 2) ?></small>
                            </div>
                            <button type="button" id="approveVprBtn" class="btn btn-success w-100 fw-bold">
                                <i class="bi bi-check-circle me-1"></i> Approve Payment
                            </button>
                        </form>
                        <form action="<?= $base_url ?>/con-vendor-payment-reject/<?= $request['id'] ?>" method="POST"
                            id="rejectVprForm">
                            <button type="button" id="rejectVprBtn" class="btn btn-outline-danger w-100 btn-sm">
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
                    This payment request has been rejected.
                </div>
            <?php endif; ?>

            <!-- Add Payment Button / Section -->
            <?php if ($showPaymentForm): ?>
                <div class="card custom-card border-success mb-3">
                    <div class="card-header bg-success bg-opacity-10">
                        <h6 class="card-title mb-0"><i class="bi bi-wallet2 me-2"></i>Process Payment</h6>
                    </div>
                    <div class="card-body text-center">
                        <p class="text-muted mb-2">Remaining Due: <strong class="text-danger fs-5">৳
                                <?= number_format($dueAmount, 2) ?></strong></p>
                        <button class="btn btn-success w-100 fw-bold" data-bs-toggle="modal"
                            data-bs-target="#addPaymentModal">
                            <i class="bi bi-plus-circle me-1"></i>
                            <?= empty($payments) ? 'Add First Payment' : 'Add Another Payment' ?>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Payment Modal -->
<?php if ($showPaymentForm): ?>
    <div class="modal fade" id="addPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="<?= $base_url ?>/con-vendor-payment-payment/<?= $request['id'] ?>" method="POST"
                    enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-wallet2 me-2"></i>Add Payment — VPR-<?= $request['id'] ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row ">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Payment Amount (৳) *</label>
                                <input type="number" name="amount" class="form-control" step="0.01" min="0.01"
                                    max="<?= number_format($dueAmount, 2, '.', '') ?>" required
                                    placeholder="Max: <?= number_format($dueAmount, 2) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Payment Date *</label>
                                <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Payment Method *</label>
                                <select name="payment_method" id="vprPaymentMethod" class="form-select" required>
                                    <option value="Cash">Cash</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="BankTransfer">Bank Transfer</option>
                                    <option value="MobileWallet">Mobile Wallet</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Reference Number</label>
                                <input type="text" name="reference_no" class="form-control"
                                    placeholder="Cheque/Transaction No.">
                            </div>
                            <!-- Bank Transfer Field (hidden by default) -->
                            <div class="col-md-6 vpr-bank-field" style="display: none;">
                                <label class="form-label fw-semibold">Select Bank *</label>
                                <select name="bank_id" class="form-select">
                                    <option value="">-- Select Bank --</option>
                                    <?php if (!empty($banks)): ?>
                                        <?php foreach ($banks as $bank): ?>
                                            <option value="<?= $bank['id'] ?>"><?= htmlspecialchars($bank['bank_name']) ?> —
                                                <?= htmlspecialchars($bank['branch_name']) ?></option>
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
                                <textarea name="notes" class="form-control" rows="2"
                                    placeholder="Optional payment notes..."></textarea>
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
    document.addEventListener('DOMContentLoaded', function () {
        var methodSelect = document.getElementById('vprPaymentMethod');
        var bankFields = document.querySelectorAll('.vpr-bank-field');

        function toggleBankField() {
            var show = methodSelect && methodSelect.value === 'BankTransfer';
            bankFields.forEach(function (el) {
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

        // ── Approve Button ─────────────────────────────────────────────────────
        var approveBtn = document.getElementById('approveVprBtn');
        var approveForm = document.getElementById('approveVprForm');
        if (approveBtn && approveForm) {
            approveBtn.addEventListener('click', function () {
                var amt = document.getElementById('approveAmtInput');
                if (!amt || !amt.value || parseFloat(amt.value) <= 0) {
                    Swal.fire({ icon: 'warning', title: 'Amount Required', text: 'Please enter a valid approved amount.', confirmButtonColor: '#198754' });
                    return;
                }
                Swal.fire({
                    title: 'Approve Payment?',
                    text: 'Amount: ৳ ' + parseFloat(amt.value).toLocaleString('en-BD', { minimumFractionDigits: 2 }),
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Approve',
                    cancelButtonText: 'Cancel'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        approveForm.submit();
                    }
                });
            });
        }

        // ── Reject Button ──────────────────────────────────────────────────────
        var rejectBtn = document.getElementById('rejectVprBtn');
        var rejectForm = document.getElementById('rejectVprForm');
        if (rejectBtn && rejectForm) {
            rejectBtn.addEventListener('click', function () {
                Swal.fire({
                    title: 'Reject Payment Request?',
                    text: 'This action will reject the request. Are you sure?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Reject',
                    cancelButtonText: 'Cancel'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        rejectForm.submit();
                    }
                });
            });
        }
    });
</script>