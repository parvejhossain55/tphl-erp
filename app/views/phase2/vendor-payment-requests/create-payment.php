<div class="pagetitle d-flex justify-content-between align-items-center">
    <div>
        <h1><?= htmlspecialchars($page_title) ?></h1>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= $base_url ?>/con-vendor-payment-requests">Payment Requests</a></li>
                <li class="breadcrumb-item active">New Payment</li>
            </ol>
        </nav>
    </div>
    <a href="<?= $base_url ?>/con-vendor-payment-requests" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<section class="section">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card__wrapper shadow-sm">
                <div class="card__header">
                    <h6 class="card__title mb-0">
                        <i class="bi bi-wallet2 me-2"></i>New Vendor Payment Request
                    </h6>
                </div>
                <div class="card__body p-4">
                    <form action="<?= $base_url ?>/con-vendor-payment-store" method="POST" id="paymentForm">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Project <span class="text-danger">*</span></label>
                                <select name="project_id" class="form-select" required>
                                    <option value="">-- Select Project --</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Date</label>
                                <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Vendor <span class="text-danger">*</span></label>
                                <select name="vendor_id" id="vendorSelect" class="form-select" required>
                                    <option value="">-- Select Vendor --</option>
                                    <?php foreach ($vendors as $v): ?>
                                        <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['company_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Payment & Penalty Calculation -->
                        <div class="bg-light p-4 rounded border mb-4">
                            <h6 class="text-secondary fw-bold border-bottom pb-2 mb-3">Financial Details</h6>
                            <div class="row g-3 align-items-center">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Invoice Amount (৳) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="invoice_amount" id="invoiceAmt" class="form-control form-control-lg text-end fw-bold" placeholder="0.00" required min="0.01">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-danger">Pending Penalty (৳)</label>
                                    <input type="text" id="penaltyAmt" class="form-control form-control-lg text-end text-danger fw-bold bg-white" readonly value="0.00">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-success">Net Payable (৳)</label>
                                    <input type="text" id="netPayableAmt" class="form-control form-control-lg text-end text-success fw-bold bg-white" readonly value="0.00">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="3" placeholder="Enter any extra details..."></textarea>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-5 py-2 fw-bold" onclick="return confirm('Create Payment Request? Unlinked penalties will be mapped automatically.');">
                                <i class="bi bi-send-check me-1"></i> Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const vendorSelect = document.getElementById('vendorSelect');
    const invoiceInput = document.getElementById('invoiceAmt');
    const penaltyInput = document.getElementById('penaltyAmt');
    const netInput     = document.getElementById('netPayableAmt');

    function calculateNet() {
        const inv = parseFloat(invoiceInput.value) || 0;
        const pen = parseFloat(penaltyInput.value) || 0;
        const net = Math.max(0, inv - pen);
        netInput.value = net.toFixed(2);
    }

    vendorSelect.addEventListener('change', function() {
        const vid = this.value;
        if (!vid) {
            penaltyInput.value = '0.00';
            calculateNet();
            return;
        }

        // Fetch pending penalties for this vendor
        fetch(`<?= $GLOBALS['base_url'] ?>/con-vendor-payment-ajax-penalty?vendor_id=${vid}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    penaltyInput.value = parseFloat(data.penalty).toFixed(2);
                    calculateNet();
                } else {
                    penaltyInput.value = '0.00';
                    calculateNet();
                }
            })
            .catch(err => {
                console.error("Failed to fetch penalty:", err);
                penaltyInput.value = '0.00';
                calculateNet();
            });
    });

    invoiceInput.addEventListener('input', calculateNet);
});
</script>
