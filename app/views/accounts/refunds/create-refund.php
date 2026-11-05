<?php
$base_url  = $GLOBALS['base_url'];
$booking   = $booking ?? [];
$totalPaid = $total_paid ?? 0;
$payments  = $payments ?? [];
?>

<!-- Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div class="my-auto">
        <h4 class="page-title fw-semibold fs-18 mb-0">Create Refund Request</h4>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/accounts-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/client-refunds">Client Refunds</a></li>
                <li class="breadcrumb-item active">New Refund</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/client-refunds" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="container-fluid">
    <?php include __DIR__ . '/../../layouts/management_approval_notice.php'; ?>
    <div class="row">
        <!-- Left: Booking Info + Form -->
        <div class="col-lg-8">
            <!-- Booking Details -->
            <div class="card custom-card mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-house-door me-2"></i>Booking Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-label text-uppercase small fw-bold" style="color:var(--text-muted, #adb5bd);">Client Name</div>
                            <div class="info-value fw-semibold fs-15" style="color: #fff !important;"><?= htmlspecialchars($booking['client_name'] ?? '—') ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label text-uppercase small fw-bold" style="color:var(--text-muted, #adb5bd);">Phone</div>
                            <div class="info-value fs-15" style="color: #fff !important;"><?= htmlspecialchars($booking['client_phone'] ?? '—') ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label text-uppercase small fw-bold" style="color:var(--text-muted, #adb5bd);">Project</div>
                            <div class="info-value fs-15" style="color: #fff !important;"><?= htmlspecialchars($booking['project_name'] ?? '—') ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-label text-uppercase small fw-bold" style="color:var(--text-muted, #adb5bd);">Flat</div>
                            <div class="info-value fs-15" style="color: #fff !important;"><?= htmlspecialchars($booking['flat_name'] ?? '—') ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-label text-uppercase small fw-bold" style="color:var(--text-muted, #adb5bd);">Floor</div>
                            <div class="info-value fs-15" style="color: #fff !important;"><?= $booking['floor_no'] ?? '—' ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-label text-uppercase small fw-bold" style="color:var(--text-muted, #adb5bd);">Booking Date</div>
                            <div class="info-value fs-15" style="color: #fff !important;"><?= !empty($booking['booking_date']) ? date('d M Y', strtotime($booking['booking_date'])) : '—' ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-label text-uppercase small fw-bold" style="color:var(--text-muted, #adb5bd);">Agreement Value</div>
                            <div class="info-value fw-bold text-success fs-15">৳ <?= number_format($booking['agreement_value'] ?? 0, 2) ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-label text-uppercase small fw-bold" style="color:var(--text-muted, #adb5bd);">Flat Price</div>
                            <div class="info-value fs-15" style="color: #fff !important;">৳ <?= number_format($booking['flat_price'] ?? 0, 2) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales Note Alert -->
            <?php if (!empty($existing_refund['reason'])): ?>
            <div class="alert alert-warning border border-warning d-flex align-items-center mb-3 shadow-sm">
                <i class="bi bi-chat-left-quote-fill text-warning fs-4 me-3"></i>
                <div>
                    <strong class="d-block text-dark">Sales Person's Refund Note / Reason:</strong>
                    <span class="text-dark fw-medium"><?= nl2br(htmlspecialchars($existing_refund['reason'])) ?></span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Payment History (Read-only) -->
            <?php if (!empty($payments)): ?>
            <div class="card custom-card mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0"><i class="bi bi-clock-history me-2"></i>Payment History (Verified Only Count)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Amount (৳)</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $idx => $pay): ?>
                                <tr style="<?= $pay['verification_status'] !== 'verified' ? 'opacity:0.6;' : '' ?>">
                                    <td><?= $idx + 1 ?></td>
                                    <td><?= date('d M Y', strtotime($pay['payment_date'])) ?></td>
                                    <td><?= ucfirst(str_replace('_', ' ', $pay['payment_type'])) ?></td>
                                    <td class="text-end fw-semibold">৳ <?= number_format($pay['amount'], 2) ?></td>
                                    <td><?= ucfirst($pay['payment_method'] ?? '') ?></td>
                                    <td>
                                        <?php
                                        $vColor = match ($pay['verification_status'] ?? '') {
                                            'verified' => 'success',
                                            'rejected' => 'danger',
                                            default    => 'warning',
                                        };
                                        ?>
                                        <span class="badge bg-<?= $vColor ?>"><?= ucfirst($pay['verification_status'] ?? 'pending') ?></span>
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

        <!-- Right: Refund Calculation + Submit -->
        <div class="col-lg-4">
            <div class="card custom-card border-warning mb-3">
                <div class="card-header border-bottom border-warning">
                    <h6 class="card-title mb-0"><i class="bi bi-calculator me-2"></i>Refund Calculation</h6>
                </div>
                <div class="card-body">
                    <form action="<?= $base_url ?>/client-refund-store" method="POST" enctype="multipart/form-data" id="refundForm">
                        <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">

                        <!-- Total Paid (read-only) -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Total Verified Paid (৳)</label>
                            <input type="text" class="form-control fw-bold text-success" 
                                   value="<?= number_format($totalPaid, 2) ?>" readonly style="background: transparent;">
                            <input type="hidden" name="total_paid" value="<?= $totalPaid ?>">
                        </div>

                        <!-- Retention Amount -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Retention Amount (৳) <span class="text-danger">*</span></label>
                            <input type="number" name="retention_amount" id="retentionAmount"
                                   class="form-control" step="0.01" min="0"
                                   value="<?= htmlspecialchars($existing_refund['retention_amount'] ?? '0') ?>"
                                   placeholder="Company charges to retain" required>
                            <small style="color:var(--text-muted, #adb5bd);">Amount the company will retain (non-refundable charge)</small>
                        </div>

                        <!-- Refund Amount (auto-calculated) -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Net Refund Amount (৳)</label>
                            <input type="hidden" name="refund_amount" id="refundAmountHidden" value="<?= htmlspecialchars($existing_refund['refund_amount'] ?? '0') ?>">
                            <div class="border border-success rounded p-3 text-center" id="refundBox">
                                <h4 class="text-success fw-bold mb-0" id="refundAmountDisplay">৳ 0.00</h4>
                            </div>
                        </div>
                        <!-- Account Head -->
                        <div class="mb-3">
                            <?= renderCOADropdown($coaList ?? [], $existing_refund['account_id'] ?? null, 'account_id', false, 'clientrefund') ?>
                        </div>

                        <!-- Document Upload -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Supporting Document</label>
                            <input type="file" name="document" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                            <small class="text-muted">Allowed: JPG, PNG, PDF, DOCX (Max: 5MB)</small>
                        </div>

                        <hr>

                        <!-- Reason -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Reason / Justification <span class="text-danger">*</span></label>
                            <textarea name="reason" class="form-control" rows="3" 
                                      placeholder="Enter the reason for refund and retention calculation..." required><?= htmlspecialchars($existing_refund['reason'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm"
                                    onclick="return confirm('Submit this refund request for Management and Role 20 approval?');">
                                <i class="bi bi-send-check me-1"></i> Submit for Management & Role 20 Approval
                            </button>
                        </div>

                        <div class="rounded-2 p-2 bg-light border small text-muted" style="font-size: 11.5px; line-height: 1.5;">
                            <i class="bi bi-info-circle text-primary me-1"></i>
                            সাবমিট করার পর রিকোয়েস্টটি <strong>ম্যানেজমেন্ট ও Role 20</strong> এর অনুমোদনের জন্য যাবে। অনুমোদন সম্পন্ন হলে অ্যাকাউন্টস ভাউচার/চেক কপি অ্যাটাচ করে রিফান্ড পরিশোধ করতে পারবে।
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var totalPaid = <?= $totalPaid ?>;
    var retentionInput = document.getElementById('retentionAmount');
    var display = document.getElementById('refundAmountDisplay');
    var refundBox = document.getElementById('refundBox');
    var hiddenRefund = document.getElementById('refundAmountHidden');

    function updateRefund() {
        var retention = parseFloat(retentionInput.value) || 0;
        var refund = Math.max(0, totalPaid - retention);
        hiddenRefund.value = refund.toFixed(2);
        display.textContent = '৳ ' + refund.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        if (refund <= 0) {
            refundBox.className = 'border border-danger rounded p-3 text-center';
            display.className = 'text-danger fw-bold mb-0';
        } else {
            refundBox.className = 'border border-success rounded p-3 text-center';
            display.className = 'text-success fw-bold mb-0';
        }
    }

    retentionInput.addEventListener('input', updateRefund);
    updateRefund();
});
</script>
