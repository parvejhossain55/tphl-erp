<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="fa-solid fa-hand-holding-dollar text-primary me-2"></i>My Sales Incentives
            </h3>
            <p class="text-muted small mb-0">Track your completed sales bookings, collected payments, incentive eligibility, and wallet disbursements.</p>
        </div>
        <div>
            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fs-7 fw-semibold">
                <i class="fa-solid fa-user-tie me-1"></i> Sales Executive Portal
            </span>
        </div>
    </div>

    <!-- Stat Summary Cards Row -->
    <div class="row mb-4 ">
        <!-- Total Sales Money Collected -->
        <div class="col-xl-3 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-success">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Total Money Collected</span>
                        <div class="avatar avatar-sm bg-success-subtle text-success rounded-2">
                            <i class="fa-solid fa-hand-holding-hand"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-1 text-success">৳<?= number_format($walletSummary['total_sales_collected'] ?? 0, 2) ?></h4>
                    <span class="text-muted extra-small">Accounts verified customer payments</span>
                </div>
            </div>
        </div>

        <!-- Total Incentive Earned -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Total Earned</span>
                        <div class="avatar avatar-sm bg-primary-subtle text-primary rounded-2">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-1 text-dark">৳<?= number_format($walletSummary['total_earned'], 2) ?></h4>
                    <span class="text-muted extra-small">Across completed sales</span>
                </div>
            </div>
        </div>

        <!-- Pending Claims -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Pending Approval</span>
                        <div class="avatar avatar-sm bg-warning-subtle text-warning rounded-2">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-1 text-warning">৳<?= number_format($walletSummary['pending_claims'], 2) ?></h4>
                    <span class="text-muted extra-small">Awaiting management review</span>
                </div>
            </div>
        </div>

        <!-- Disbursed / Paid -->
        <div class="col-xl-2 col-md-6 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Disbursed / Paid</span>
                        <div class="avatar avatar-sm bg-info-subtle text-info rounded-2">
                            <i class="fa-solid fa-money-bill-transfer"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-1 text-info">৳<?= number_format($walletSummary['disbursed_paid'], 2) ?></h4>
                    <span class="text-muted extra-small">Received in bank / cash</span>
                </div>
            </div>
        </div>

        <!-- Available Wallet Balance -->
        <div class="col-xl-3 col-md-6 col-sm-12">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white border-start border-4 border-primary">
                <div class="card-body p-3 position-relative overflow-hidden">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Wallet Available Balance</span>
                        <div class="avatar avatar-sm bg-primary-subtle text-primary rounded-2">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-1 text-primary">৳<?= number_format($walletSummary['available_balance'], 2) ?></h4>
                    <span class="text-muted extra-small">Approved amount ready for payout</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="row g-4">
        <!-- Sales & Incentive Claims Table Card -->
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="fa-solid fa-list-check text-primary me-2"></i>My Project Sales & Incentive Eligibility
                        </h5>
                        <span class="text-muted extra-small">Incentive claim becomes active when both Booking Money & Registration are complete.</span>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted extra-small text-uppercase">
                                <tr>
                                    <th class="ps-4">Booking Ref</th>
                                    <th>Customer & Phone</th>
                                    <th>Project & Flat Unit</th>
                                    <th>Land Share (Calculation Base)</th>
                                    <th>Total Collected</th>
                                    <th>Applied Rate (%)</th>
                                    <th>Incentive Amount</th>
                                    <th class="text-center">Booking Money Status</th>
                                    <th class="text-center">Registration Status</th>
                                    <th class="text-center">Claim Status</th>
                                    <th class="pe-4 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($mySalesBookings)): ?>
                                    <tr>
                                        <td colspan="11" class="text-center py-4 text-muted">
                                            <i class="fa-solid fa-folder-open fs-3 d-block mb-2"></i>
                                            No sales bookings found assigned to your user account.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($mySalesBookings as $sale): ?>
                                        <tr>
                                            <!-- Booking Ref -->
                                            <td class="ps-4">
                                                <span class="fw-bold text-dark d-block"><?= htmlspecialchars($sale['booking_no']) ?></span>
                                                <span class="badge bg-light text-muted border border-light-subtle extra-small">ID #<?= $sale['id'] ?></span>
                                            </td>

                                            <!-- Customer -->
                                            <td>
                                                <div class="fw-semibold text-dark"><?= htmlspecialchars($sale['customer_name']) ?></div>
                                                <span class="text-muted extra-small"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($sale['phone']) ?></span>
                                            </td>

                                            <!-- Project & Unit -->
                                            <td>
                                                <span class="fw-bold text-dark d-block"><?= htmlspecialchars($sale['project_name']) ?></span>
                                                <span class="text-primary extra-small fw-semibold"><?= htmlspecialchars($sale['flat_name']) ?> (<?= $sale['sqft'] ?> sqft)</span>
                                            </td>

                                            <!-- Land Share (Base) & Flat Price -->
                                            <td>
                                                <span class="badge bg-primary-subtle text-primary fw-bold fs-7 mb-1" title="Land Share Value">
                                                    <i class="fa-solid fa-map-location-dot me-1"></i>৳<?= number_format($sale['land_share_value'], 2) ?>
                                                </span>
                                                <span class="text-muted extra-small d-block">Flat: ৳<?= number_format($sale['flat_price'], 2) ?></span>
                                            </td>

                                            <!-- Total Money Collected So Far -->
                                            <td>
                                                <span class="badge bg-success-subtle text-success fw-bold fs-7 px-2 py-1 border border-success-subtle">
                                                    ৳<?= number_format($sale['total_collected'], 2) ?>
                                                </span>
                                            </td>

                                            <!-- Applied Rate -->
                                            <td>
                                                <span class="badge bg-info-subtle text-info fw-bold fs-7 mb-1 d-inline-block"><?= number_format($sale['incentive_percentage'], 2) ?>%</span>
                                                <span class="extra-small text-muted d-block"><?= htmlspecialchars($sale['category_name'] ?? 'Modality') ?></span>
                                            </td>

                                            <!-- Incentive Amount -->
                                            <td>
                                                <h6 class="fw-bold text-success mb-0">৳<?= number_format($sale['calculated_incentive'], 2) ?></h6>
                                                <span class="text-muted extra-small">on Land Share</span>
                                            </td>

                                            <!-- Booking Money Badge -->
                                            <td class="text-center">
                                                <?php if ($sale['booking_completed']): ?>
                                                    <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 extra-small border border-success-subtle d-block mb-1">
                                                        <i class="fa-solid fa-circle-check me-1"></i>Paid & Verified
                                                    </span>
                                                    <span class="extra-small fw-bold text-success">৳<?= number_format($sale['booking_money_collected'], 2) ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-subtle text-warning rounded-pill px-3 py-1 extra-small border border-warning-subtle">
                                                        <i class="fa-solid fa-clock me-1"></i>Pending
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Registration Badge -->
                                            <td class="text-center">
                                                <?php if ($sale['registration_completed']): ?>
                                                    <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 extra-small border border-success-subtle">
                                                        <i class="fa-solid fa-file-signature me-1"></i>Completed
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-1 extra-small border border-danger-subtle">
                                                        <i class="fa-solid fa-hourglass-half me-1"></i>Incomplete
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Claim Status Badge -->
                                            <td class="text-center">
                                                <?php if ($sale['claim_status'] === 'eligible'): ?>
                                                    <span class="badge bg-primary text-white rounded-pill px-3 py-1 extra-small shadow-sm">
                                                        <i class="fa-solid fa-star me-1"></i>Eligible
                                                    </span>
                                                <?php elseif ($sale['claim_status'] === 'claimed'): ?>
                                                    <span class="badge bg-warning text-dark rounded-pill px-3 py-1 extra-small">
                                                        <i class="fa-solid fa-paper-plane me-1"></i>Claimed (Pending Approval)
                                                    </span>
                                                <?php elseif ($sale['claim_status'] === 'approved'): ?>
                                                    <span class="badge bg-info text-white rounded-pill px-3 py-1 extra-small">
                                                        <i class="fa-solid fa-check-double me-1"></i>Approved
                                                    </span>
                                                <?php elseif ($sale['claim_status'] === 'paid'): ?>
                                                    <span class="badge bg-success text-white rounded-pill px-3 py-1 extra-small">
                                                        <i class="fa-solid fa-shield-check me-1"></i>Paid & Settled
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary text-white rounded-pill px-3 py-1 extra-small">
                                                        <i class="fa-solid fa-lock me-1"></i>Not Eligible Yet
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Action Button -->
                                            <td class="pe-4 text-end">
                                                <?php if ($sale['claim_status'] === 'eligible'): ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-success rounded-2 px-3 shadow-sm btn-claim-modal" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#claimIncentiveModal"
                                                            data-toggle="modal"
                                                            data-target="#claimIncentiveModal"
                                                            data-id="<?= $sale['id'] ?>"
                                                            data-booking="<?= htmlspecialchars($sale['booking_no']) ?>"
                                                            data-project="<?= htmlspecialchars($sale['project_name']) ?> - <?= htmlspecialchars($sale['flat_name']) ?>"
                                                            data-amount="৳<?= number_format($sale['calculated_incentive'], 2) ?>">
                                                        <i class="fa-solid fa-paper-plane me-1"></i>Claim Incentive
                                                    </button>
                                                <?php elseif ($sale['claim_status'] === 'claimed'): ?>
                                                    <button class="btn btn-sm btn-outline-warning rounded-2 disabled" disabled>
                                                        <i class="fa-solid fa-clock me-1"></i>Pending Approval
                                                    </button>
                                                <?php elseif ($sale['claim_status'] === 'paid'): ?>
                                                    <button class="btn btn-sm btn-outline-success rounded-2 disabled" disabled>
                                                        <i class="fa-solid fa-circle-check me-1"></i>Disbursed
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-light text-muted rounded-2 disabled" disabled title="Complete Booking Money and Registration to claim">
                                                        <i class="fa-solid fa-lock me-1"></i>Locked
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payout History Log Card -->
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-receipt text-success me-2"></i>Recent Payout & Wallet Credit History
                    </h5>
                    <span class="badge bg-light text-dark border">Total Disbursed Logs: <?= count($payoutHistory) ?></span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted extra-small text-uppercase">
                                <tr>
                                    <th class="ps-4">Disbursement Date</th>
                                    <th>Booking Ref</th>
                                    <th>Payment Method</th>
                                    <th>Bank / Payment Details</th>
                                    <th>Transaction Ref</th>
                                    <th class="pe-4 text-end">Disbursed Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payoutHistory)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-3 text-muted">No payout history recorded yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($payoutHistory as $payout): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <span class="fw-semibold text-dark"><?= date('d M, Y', strtotime($payout['payment_date'])) ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary-subtle text-secondary fw-semibold"><?= htmlspecialchars($payout['disbursement_no']) ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info-subtle text-info fw-semibold"><?= htmlspecialchars($payout['payment_method']) ?></span>
                                            </td>
                                            <td>
                                                <span class="fw-semibold text-dark"><?= htmlspecialchars($payout['bank_name']) ?></span>
                                                <div class="extra-small text-muted"><?= htmlspecialchars($payout['notes']) ?></div>
                                            </td>
                                            <td>
                                                <code class="text-dark bg-light px-2 py-1 rounded border"><?= htmlspecialchars($payout['transaction_ref']) ?></code>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <span class="fw-bold text-success">৳<?= number_format($payout['payment_amount'], 2) ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Claim Incentive -->
<div id="claimIncentiveModal" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content border-0 shadow-lg">
            <form id="submitClaimForm">
                <input type="hidden" id="modalBookingId" name="booking_id" value="">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold text-white mb-0" id="claimIncentiveModalLabel">
                        <i class="fa-solid fa-paper-plane me-2"></i>Submit Sales Incentive Claim
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-white text-dark">
                    <div class="alert alert-info border-0 rounded-3 mb-3 d-flex align-items-center">
                        <i class="fa-solid fa-circle-info fs-4 me-3 text-info"></i>
                        <div class="small">
                            Submitting this claim will send an approval request to <strong>Management</strong>. Once approved, Accounts will disburse payment into your wallet.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-bold">Booking Reference</label>
                        <input type="text" id="modalBookingNo" class="form-control bg-light fw-bold text-dark" value="" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-bold">Project & Flat Unit</label>
                        <input type="text" id="modalProjectUnit" class="form-control bg-light text-dark" value="" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-bold">Claim Incentive Amount</label>
                        <input type="text" id="modalIncentiveAmount" class="form-control bg-success-subtle text-success fw-bold fs-5" value="" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-bold">Preferred Payment Account / Remarks (Optional)</label>
                        <textarea class="form-control" id="modalRemarks" name="remarks" rows="3" placeholder="Enter bank account details or notes for Management approval..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary rounded-2" data-bs-dismiss="modal" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-2 px-4" id="btnSubmitClaim">
                        <i class="fa-solid fa-paper-plane me-1"></i> Submit Claim
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    $(document).on('show.bs.modal', '.modal', function () {
        if (this.parentNode !== document.body) {
            document.body.appendChild(this);
        }
    });

    $(document).on('click', '.btn-claim-modal', function (e) {
        e.preventDefault();
        const id = $(this).attr('data-id');
        const booking = $(this).attr('data-booking');
        const project = $(this).attr('data-project');
        const amount = $(this).attr('data-amount');
        
        $('#modalBookingId').val(id || '');
        $('#modalBookingNo').val(booking || '');
        $('#modalProjectUnit').val(project || '');
        $('#modalIncentiveAmount').val(amount || '');

        $('#claimIncentiveModal').modal('show');
    });

    const submitClaimForm = document.getElementById('submitClaimForm');
    if (submitClaimForm) {
        submitClaimForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitClaim');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Submitting...';

            const formData = new FormData(this);
            fetch('<?= $base_url ?>/incentives/claim', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Submit Claim';
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Submit Claim';
                alert('An error occurred submitting the claim.');
            });
        });
    }
});
</script>
