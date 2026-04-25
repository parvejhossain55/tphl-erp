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
                            <div class="info-label text-uppercase text-muted small fw-bold">Client Name</div>
                            <div class="info-value fw-semibold"><?= htmlspecialchars($booking['client_name'] ?? '—') ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label text-uppercase text-muted small fw-bold">Phone</div>
                            <div class="info-value"><?= htmlspecialchars($booking['client_phone'] ?? '—') ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label text-uppercase text-muted small fw-bold">Project</div>
                            <div class="info-value"><?= htmlspecialchars($booking['project_name'] ?? '—') ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-label text-uppercase text-muted small fw-bold">Flat</div>
                            <div class="info-value"><?= htmlspecialchars($booking['flat_name'] ?? '—') ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-label text-uppercase text-muted small fw-bold">Floor</div>
                            <div class="info-value"><?= $booking['floor_no'] ?? '—' ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-label text-uppercase text-muted small fw-bold">Booking Date</div>
                            <div class="info-value"><?= !empty($booking['booking_date']) ? date('d M Y', strtotime($booking['booking_date'])) : '—' ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-label text-uppercase text-muted small fw-bold">Agreement Value</div>
                            <div class="info-value fw-bold text-primary">৳ <?= number_format($booking['agreement_value'] ?? 0, 2) ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-label text-uppercase text-muted small fw-bold">Flat Price</div>
                            <div class="info-value">৳ <?= number_format($booking['flat_price'] ?? 0, 2) ?></div>
                        </div>
                    </div>
                </div>
            </div>

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
                                <tr class="<?= $pay['verification_status'] !== 'verified' ? 'text-muted' : '' ?>">
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
                <div class="card-header bg-warning bg-opacity-10">
                    <h6 class="card-title mb-0"><i class="bi bi-calculator me-2"></i>Refund Calculation</h6>
                </div>
                <div class="card-body">
                    <form action="<?= $base_url ?>/client-refund-store" method="POST" id="refundForm">
                        <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">

                        <!-- Total Paid (read-only) -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Total Verified Paid (৳)</label>
                            <input type="text" class="form-control bg-light" 
                                   value="<?= number_format($totalPaid, 2) ?>" readonly>
                            <input type="hidden" name="total_paid" value="<?= $totalPaid ?>">
                        </div>

                        <!-- Retention Amount -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Retention Amount (৳) <span class="text-danger">*</span></label>
                            <input type="number" name="retention_amount" id="retentionAmount"
                                   class="form-control" step="0.01" min="0"
                                   max="<?= number_format($totalPaid, 2, '.', '') ?>"
                                   placeholder="Company charges to retain" required>
                            <small class="text-muted">Amount the company will retain (non-refundable)</small>
                        </div>

                        <!-- Refund Amount (auto-calculated) -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Refund Amount (৳)</label>
                            <div class="border rounded p-3 text-center bg-success bg-opacity-10">
                                <h4 class="text-success fw-bold mb-0" id="refundAmountDisplay">৳ 0.00</h4>
                            </div>
                        </div>

                        <hr>

                        <!-- Reason -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                            <textarea name="reason" class="form-control" rows="3" 
                                      placeholder="Enter the reason for refund..." required></textarea>
                        </div>

                        <button type="submit" class="btn btn-warning w-100 fw-bold"
                                onclick="return confirm('Submit this refund request for approval?');">
                            <i class="bi bi-send me-1"></i> Submit Refund Request
                        </button>
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

    function updateRefund() {
        var retention = parseFloat(retentionInput.value) || 0;
        var refund = Math.max(0, totalPaid - retention);
        display.textContent = '৳ ' + refund.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        display.parentElement.className = refund > 0
            ? 'border rounded p-3 text-center bg-success bg-opacity-10'
            : 'border rounded p-3 text-center bg-danger bg-opacity-10';
        if (refund <= 0) display.className = 'text-danger fw-bold mb-0';
        else display.className = 'text-success fw-bold mb-0';
    }

    retentionInput.addEventListener('input', updateRefund);
    updateRefund();
});
</script>
